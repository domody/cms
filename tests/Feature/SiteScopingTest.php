<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceAcl;
use App\Http\Middleware\SetCurrentSite;
use App\Livewire\CollectionEntryEditor;
use App\Livewire\CollectionEntryList;
use App\Livewire\PageEditor;
use App\Models\ActivityLog;
use App\Models\BlockDefinition;
use App\Models\CollectionDefinition;
use App\Models\CollectionEntry;
use App\Models\Concerns\BelongsToSite;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageStatus;
use App\Models\Scopes\CurrentSite;
use App\Models\Scopes\NoCurrentSiteException;
use App\Models\Scopes\SiteScope;
use App\Models\Site;
use App\Models\SitePlan;
use App\Models\SiteRole;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Livewire\Livewire;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class SiteScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A throwaway site-owned model, standing in for a future content
        // model - proves the trait/scope work generically.
        Schema::create('dummy_site_owned_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    private function enter(Site $site): void
    {
        app(CurrentSite::class)->set($site);
    }

    public function test_site_owned_reads_throw_with_no_current_site(): void
    {
        $site = Site::factory()->create();
        DummyRecord::query()->create(['site_id' => $site->id, 'name' => 'secret']);

        $this->expectException(NoCurrentSiteException::class);

        DummyRecord::count();
    }

    public function test_a_deliberate_cross_site_query_can_opt_out_of_the_scope(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        DummyRecord::query()->create(['site_id' => $siteA->id]);
        DummyRecord::query()->create(['site_id' => $siteB->id]);

        $this->assertSame(2, DummyRecord::withoutGlobalScope(SiteScope::class)->count());
    }

    public function test_site_owned_reads_are_limited_to_the_current_site(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        DummyRecord::query()->create(['site_id' => $siteA->id]);
        DummyRecord::query()->create(['site_id' => $siteB->id]);
        DummyRecord::query()->create(['site_id' => $siteB->id]);

        $this->enter($siteB);

        $this->assertSame(2, DummyRecord::count());
    }

    public function test_media_is_limited_to_the_current_site(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        Media::factory()->create(['site_id' => $siteA->id]);
        $own = Media::factory()->create(['site_id' => $siteB->id]);

        $this->enter($siteB);

        $this->assertSame([$own->id], Media::pluck('id')->all());
    }

    public function test_a_member_uploads_media_stamped_with_their_site_and_cannot_touch_another_sites(): void
    {
        Storage::fake('public');

        $own = Site::factory()->create(['slug' => 'own-site']);
        $other = Site::factory()->create(['slug' => 'other-site']);
        $foreign = Media::factory()->create(['site_id' => $other->id]);
        $user = User::factory()->memberOf($own, SiteRole::OWNER)->create();

        $this->actingAs($user)
            ->post('/own-site/media', ['file' => UploadedFile::fake()->image('hero.jpg', 800, 600), 'alt' => 'A hero'])
            ->assertRedirect('/own-site/media');

        $stored = Media::withoutGlobalScope(SiteScope::class)->where('site_id', $own->id)->firstOrFail();
        $this->assertSame('A hero', $stored->alt);
        $this->assertSame(800, $stored->width);
        Storage::disk('public')->assertExists($stored->path);

        $this->actingAs($user)->delete("/own-site/media/{$foreign->id}")->assertNotFound();
        $this->assertDatabaseHas('media', ['id' => $foreign->id]);
    }

    public function test_media_uploads_reject_svg_and_missing_alt_text(): void
    {
        Storage::fake('public');

        $site = Site::factory()->create(['slug' => 'own-site']);
        $user = User::factory()->memberOf($site)->create();

        $this->actingAs($user)
            ->post('/own-site/media', ['file' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'), 'alt' => ''])
            ->assertSessionHasErrors(['file', 'alt']);
    }

    public function test_users_are_global_not_site_scoped(): void
    {
        User::factory()->count(3)->create();

        $this->assertSame(3, User::count());
    }

    public function test_login_finds_a_user_without_any_site_context(): void
    {
        User::factory()->create(['email' => 'someone@example.com']);

        $this->assertTrue(Auth::attempt(['email' => 'someone@example.com', 'password' => 'password']));
    }

    public function test_a_user_can_belong_to_many_sites_with_a_role_on_each(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        $user = User::factory()->memberOf($siteA, SiteRole::OWNER)->create();
        $user->sites()->attach($siteB->id, ['role' => SiteRole::EDITOR]);

        $this->assertCount(2, $user->sites);
        $this->assertSame(SiteRole::OWNER, $user->roleOn($siteA));
        $this->assertSame(SiteRole::EDITOR, $user->roleOn($siteB));
        $this->assertSame($siteA->id, $user->defaultSite()->id);
    }

    public function test_root_redirects_to_the_first_site_the_user_joined(): void
    {
        $first = Site::factory()->create(['slug' => 'first-site']);
        $second = Site::factory()->create(['slug' => 'second-site']);
        $user = User::factory()->memberOf($first)->create();
        $user->sites()->attach($second->id, ['role' => 'editor']);

        $this->actingAs($user)->get('/')->assertRedirect('/first-site/dashboard');
    }

    public function test_member_can_enter_their_site_but_not_one_they_do_not_belong_to(): void
    {
        $own = Site::factory()->create(['slug' => 'own-site']);
        $other = Site::factory()->create(['slug' => 'other-site']);
        $user = User::factory()->memberOf($own)->create();

        $this->actingAs($user)->get('/own-site/dashboard')->assertOk();
        $this->actingAs($user)->get('/other-site/dashboard')->assertNotFound();
        $this->actingAs($user)->get('/no-such-site/dashboard')->assertNotFound();
    }

    public function test_guests_are_sent_to_login_not_a_404(): void
    {
        Site::factory()->create(['slug' => 'own-site']);

        $this->get('/own-site/dashboard')->assertRedirect(route('login'));
    }

    public function test_a_multi_site_user_only_sees_the_selected_sites_pages(): void
    {
        $siteA = Site::factory()->create(['slug' => 'site-a']);
        $siteB = Site::factory()->create(['slug' => 'site-b']);
        $user = User::factory()->memberOf($siteA)->create();
        $user->sites()->attach($siteB->id, ['role' => 'editor']);

        Page::factory()->create(['site_id' => $siteA->id, 'slug' => 'about', 'title' => 'About A']);
        Page::factory()->create(['site_id' => $siteB->id, 'slug' => 'contact', 'title' => 'Contact B']);

        $this->actingAs($user);

        $this->get('/site-a/pages')->assertOk()->assertSee('About A')->assertDontSee('Contact B');
        $this->get('/site-b/pages')->assertOk()->assertSee('Contact B')->assertDontSee('About A');
        $this->get('/site-a/pages/contact')->assertNotFound();
        $this->get('/site-b/pages/about')->assertNotFound();
    }

    public function test_session_authentication_resolves_and_scopes_through_the_full_stack(): void
    {
        // A real session cookie value (not actingAs(), which sets the guard's
        // user directly) reaching a site route.
        Route::middleware(['web', 'auth', SetCurrentSite::class])
            ->get('/{site}/_test-probe', fn () => ['records' => DummyRecord::count()]);

        $siteA = Site::factory()->create(['slug' => 'site-a']);
        $siteB = Site::factory()->create(['slug' => 'site-b']);
        $user = User::factory()->memberOf($siteA)->create();
        DummyRecord::query()->create(['site_id' => $siteA->id]);
        DummyRecord::query()->create(['site_id' => $siteB->id]);
        DummyRecord::query()->create(['site_id' => $siteB->id]);

        $sessionKey = 'login_web_'.sha1(SessionGuard::class);

        $this->withSession([$sessionKey => $user->id])
            ->get('/site-a/_test-probe')
            ->assertOk()
            ->assertExactJson(['records' => 1]);

        $this->withSession([$sessionKey => $user->id])
            ->get('/site-b/_test-probe')
            ->assertNotFound();
    }

    public function test_an_api_token_only_sees_the_site_it_is_bound_to(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();

        Page::factory()->published()->create(['site_id' => $siteA->id, 'slug' => 'about']);
        Page::factory()->published()->create(['site_id' => $siteB->id, 'slug' => 'contact']);

        $tokenA = $siteA->issueToken('frontend-a', ['*'])->plainTextToken;

        $this->withToken($tokenA)->getJson('/api/pages/about')->assertOk();
        $this->withToken($tokenA)->getJson('/api/pages/contact')->assertNotFound();
    }

    public function test_the_api_resolves_image_fields_to_this_sites_media_and_nulls_anything_else(): void
    {
        $site = Site::factory()->create();
        $other = Site::factory()->create();
        $user = User::factory()->memberOf($site)->create();

        $own = Media::factory()->create(['site_id' => $site->id, 'alt' => 'Own image', 'width' => 640, 'height' => 480]);
        $foreign = Media::factory()->create(['site_id' => $other->id]);

        BlockDefinition::factory()->create(['site_id' => $site->id, 'key' => 'gallery', 'schema' => [
            ['name' => 'main', 'type' => 'image'],
            ['name' => 'stolen', 'type' => 'image'],
            ['name' => 'legacy', 'type' => 'image'],
            ['name' => 'unset', 'type' => 'image'],
        ]]);
        $page = Page::factory()->published()->create(['site_id' => $site->id, 'slug' => 'home']);
        PageBlock::factory()->create(['page_id' => $page->id, 'block_key' => 'gallery', 'content' => [
            'main' => $own->id,
            'stolen' => $foreign->id,
            'legacy' => 'https://example.com/old.jpg',
        ]]);

        $content = $this->withToken($site->issueToken('frontend', ['*'])->plainTextToken)
            ->getJson('/api/pages/home')
            ->assertOk()
            ->json('data.blocks.0.content');

        $this->assertSame(['url' => $own->url(), 'alt' => 'Own image', 'width' => 640, 'height' => 480, 'variants' => [], 'srcset' => null], $content['main']);
        $this->assertNull($content['stolen']);
        $this->assertNull($content['legacy']);
        $this->assertNull($content['unset']);
    }

    public function test_the_page_editor_rejects_another_sites_media_id_on_save(): void
    {
        $site = Site::factory()->create(['slug' => 'own-site']);
        $other = Site::factory()->create();
        $user = User::factory()->memberOf($site)->create();

        $own = Media::factory()->create(['site_id' => $site->id]);
        $foreign = Media::factory()->create(['site_id' => $other->id]);

        BlockDefinition::factory()->create(['site_id' => $site->id, 'key' => 'banner', 'schema' => [
            ['name' => 'image', 'type' => 'image'],
        ]]);
        $page = Page::factory()->create(['site_id' => $site->id, 'slug' => 'home']);
        $block = PageBlock::factory()->create(['page_id' => $page->id, 'block_key' => 'banner', 'content' => []]);

        $this->enter($site);
        $this->actingAs($user);

        Livewire::test(PageEditor::class, ['page' => $page])
            ->set('blocks.0.content.image', $foreign->id)
            ->call('save')
            ->assertHasErrors(['blocks.0.content.image']);

        Livewire::test(PageEditor::class, ['page' => $page])
            ->set('blocks.0.content.image', $own->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($own->id, $block->refresh()->content['image']);
    }

    public function test_collection_definitions_and_entries_are_limited_to_the_current_site(): void
    {
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        CollectionDefinition::factory()->create(['site_id' => $siteA->id, 'key' => 'team']);
        CollectionDefinition::factory()->create(['site_id' => $siteB->id, 'key' => 'team']);
        CollectionEntry::factory()->create(['site_id' => $siteA->id]);
        $own = CollectionEntry::factory()->create(['site_id' => $siteB->id]);

        $this->enter($siteB);

        $this->assertSame(1, CollectionDefinition::count());
        $this->assertSame([$own->id], CollectionEntry::pluck('id')->all());
    }

    public function test_sync_declares_collections_and_the_api_serves_only_this_sites_published_entries_in_order(): void
    {
        $site = Site::factory()->create();
        $other = Site::factory()->create();
        $user = User::factory()->memberOf($site)->create();
        $token = $site->issueToken('frontend', ['*'])->plainTextToken;

        // Other-site fixtures first: once a request has run, the current site
        // is set and the factory would stamp new records with it.
        $foreignPhoto = Media::factory()->create(['site_id' => $other->id]);
        CollectionEntry::factory()->published()->create(['site_id' => $other->id, 'title' => 'Elsewhere']);

        $this->withToken($token)->putJson('/api/block-definitions', [
            'blocks' => [
                ['key' => 'team-grid', 'label' => 'Team grid', 'fields' => [
                    ['name' => 'heading', 'type' => 'text'],
                    ['name' => 'members', 'type' => 'collection', 'limit' => 2],
                ]],
            ],
            'collections' => [
                ['key' => 'team', 'label' => 'Team', 'fields' => [
                    ['name' => 'role', 'type' => 'text'],
                    ['name' => 'photo', 'type' => 'image'],
                ]],
            ],
            'pages' => [
                ['slug' => 'about', 'title' => 'About', 'blocks' => [['type' => 'team-grid', 'content' => ['heading' => 'Us', 'members' => 'team']]]],
            ],
        ])->assertOk()->assertJson(['collections' => 1]);

        $photo = Media::factory()->create(['site_id' => $site->id, 'alt' => 'Ann']);

        $entry = fn (array $attributes) => CollectionEntry::factory()->create(['site_id' => $site->id] + $attributes);
        $entry(['title' => 'Cara', 'slug' => 'cara', 'status' => 'published', 'position' => 2, 'data' => ['role' => 'CTO']]);
        $entry(['title' => 'Ann', 'slug' => 'ann', 'status' => 'published', 'position' => 0, 'data' => ['role' => 'CEO', 'photo' => $photo->id]]);
        $entry(['title' => 'Hidden', 'slug' => 'hidden', 'status' => 'draft', 'position' => 1]);
        $entry(['title' => 'Ben', 'slug' => 'ben', 'status' => 'published', 'position' => 1, 'data' => ['photo' => $foreignPhoto->id]]);

        $list = $this->withToken($token)->getJson('/api/collections/team')->assertOk()->json('data');

        $this->assertSame(['ann', 'ben', 'cara'], array_column($list, 'slug'));
        $this->assertSame('team', $list[0]['collection']);
        $this->assertSame('CEO', $list[0]['role']);
        $this->assertSame($photo->url(), $list[0]['photo']['url']);
        $this->assertNull($list[1]['photo']);
        $this->assertNull($list[1]['role']);

        $this->withToken($token)->getJson('/api/collections/team/ann')->assertOk()->assertJsonPath('data.title', 'Ann');
        $this->withToken($token)->getJson('/api/collections/team/hidden')->assertNotFound();
        $this->withToken($token)->getJson('/api/collections/nope')->assertNotFound();

        $block = $this->withToken($token)->getJson('/api/pages/about')->assertOk()->json('data.blocks.0.content');
        $this->assertSame(['ann', 'ben'], array_column($block['members'], 'slug'));
    }

    public function test_an_editor_points_a_collection_field_at_any_collection_of_their_own_site(): void
    {
        $site = Site::factory()->create(['slug' => 'own-site']);
        $other = Site::factory()->create();
        $user = User::factory()->memberOf($site)->create();
        CollectionDefinition::factory()->create(['site_id' => $site->id, 'key' => 'law-team', 'label' => 'Law team']);
        CollectionDefinition::factory()->create(['site_id' => $site->id, 'key' => 'common-law-team', 'label' => 'Common law team']);
        CollectionDefinition::factory()->create(['site_id' => $other->id, 'key' => 'elsewhere']);
        CollectionEntry::factory()->published()->create(['site_id' => $site->id, 'collection_key' => 'law-team', 'title' => 'Old', 'slug' => 'old']);
        CollectionEntry::factory()->published()->create(['site_id' => $site->id, 'collection_key' => 'common-law-team', 'title' => 'New', 'slug' => 'new']);

        BlockDefinition::factory()->create(['site_id' => $site->id, 'key' => 'team-grid', 'schema' => [
            ['name' => 'members', 'type' => 'collection'],
        ]]);
        $page = Page::factory()->published()->create(['site_id' => $site->id, 'slug' => 'team']);
        PageBlock::factory()->create(['page_id' => $page->id, 'block_key' => 'team-grid', 'content' => ['members' => 'law-team']]);

        $this->enter($site);
        $this->actingAs($user);
        URL::defaults(['site' => $site->slug]);

        Livewire::test(PageEditor::class, ['page' => $page])
            ->set('blocks.0.content.members', 'elsewhere')
            ->call('save')
            ->assertHasErrors(['blocks.0.content.members']);

        Livewire::test(PageEditor::class, ['page' => $page])
            ->set('blocks.0.content.members', 'common-law-team')
            ->call('save')
            ->assertHasNoErrors();

        $members = BlockDefinition::resolveBlocks($page->blocks()->get())[0]['content']['members'];
        $this->assertSame(['new'], array_column($members, 'slug'));
        $this->assertSame('common-law-team', $members[0]['collection']);
    }

    public function test_sync_rejects_reserved_collection_field_names(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->memberOf($site)->create();
        $token = $site->issueToken('frontend', ['*'])->plainTextToken;

        $this->withToken($token)->putJson('/api/block-definitions', [
            'blocks' => [],
            'collections' => [['key' => 'team', 'label' => 'Team', 'fields' => [['name' => 'title', 'type' => 'text']]]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['collections.0.fields.0.name']);
    }

    public function test_a_member_creates_collection_entries_with_unique_slugs_and_cannot_open_another_sites(): void
    {
        $site = Site::factory()->create(['slug' => 'own-site']);
        $other = Site::factory()->create(['slug' => 'other-site']);
        $user = User::factory()->memberOf($site)->create();

        $definition = CollectionDefinition::factory()->create(['site_id' => $site->id, 'key' => 'team']);
        CollectionDefinition::factory()->create(['site_id' => $other->id, 'key' => 'team']);
        $foreign = CollectionEntry::factory()->create(['site_id' => $other->id]);

        $this->enter($site);
        $this->actingAs($user);

        foreach (range(1, 2) as $_) {
            Livewire::test(CollectionEntryEditor::class, ['definition' => $definition])
                ->set('title', 'Ann Lee')
                ->set('data.role', 'CEO')
                ->call('save')
                ->assertHasNoErrors();
        }

        Livewire::test(CollectionEntryEditor::class, ['definition' => $definition])->call('save')->assertHasErrors(['title']);

        $entries = CollectionEntry::orderBy('id')->get();
        $this->assertSame(['ann-lee', 'ann-lee-2'], $entries->pluck('slug')->all());
        $this->assertSame([0, 1], $entries->pluck('position')->all());
        $this->assertSame('CEO', $entries[0]->data['role']);
        $this->assertSame('draft', $entries[0]->status);

        $this->get("/own-site/collections/team/entries/{$foreign->id}/edit")->assertNotFound();
        $this->get("/own-site/collections/team/entries/{$entries[0]->id}/edit")->assertOk();
        $this->get('/own-site/collections/team')->assertOk();
        $this->get('/own-site/collections')->assertOk()->assertSee('/own-site/collections/team');
    }

    public function test_an_admin_creates_a_site_and_a_customer_cannot_reach_the_management_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->memberOf(Site::factory()->create(['slug' => 'own-site']))->create();

        $this->actingAs($admin)
            ->post('/admin/sites', ['name' => 'Acme Corp'])
            ->assertRedirect('/admin/sites/acme-corp');
        $this->assertDatabaseHas('sites', ['slug' => 'acme-corp', 'name' => 'Acme Corp']);

        $this->actingAs($admin)->post('/admin/sites', ['name' => 'Admin', 'slug' => 'admin'])->assertSessionHasErrors('slug');
        $this->actingAs($admin)->post('/admin/sites', ['name' => 'Acme again', 'slug' => 'acme-corp'])->assertSessionHasErrors('slug');

        $this->actingAs($customer)->get('/admin/sites/create')->assertNotFound();
        $this->actingAs($customer)->post('/admin/sites', ['name' => 'Mine'])->assertNotFound();
        $this->actingAs($customer)->get('/admin/users/create')->assertNotFound();
        $this->actingAs($customer)->post('/admin/sites/own-site/tokens', ['name' => 'x', 'type' => 'read'])->assertNotFound();
    }

    public function test_deleting_a_site_needs_its_slug_typed_and_removes_its_content(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $site = Site::factory()->create(['slug' => 'doomed']);
        $page = Page::factory()->create(['site_id' => $site->id]);

        $this->actingAs($admin)->delete('/admin/sites/doomed', ['confirm_slug' => 'nope'])->assertSessionHasErrors('confirm_slug');
        $this->assertDatabaseHas('sites', ['slug' => 'doomed']);

        $this->actingAs($admin)->delete('/admin/sites/doomed', ['confirm_slug' => 'doomed'])->assertRedirect('/admin/sites');
        $this->assertDatabaseMissing('sites', ['slug' => 'doomed']);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_an_admin_creates_a_user_on_a_site_and_the_generated_password_works_once_shown(): void
    {
        $admin = User::factory()->admin()->create();
        $site = Site::factory()->create(['slug' => 'acme']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Casey Client',
            'email' => 'casey@example.com',
            'access_level' => 'customer',
            'site_id' => $site->id,
            'role' => 'owner',
        ])->assertSessionHas('shown_password');

        $password = session('shown_password');
        $user = User::where('email', 'casey@example.com')->firstOrFail();

        $this->assertFalse($user->isAdmin());
        $this->assertSame('owner', $user->roleOn($site));

        auth()->logout();
        $this->post('/login', ['email' => 'casey@example.com', 'password' => $password])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_access_level_cannot_be_mass_assigned_through_the_user_form_by_a_customer(): void
    {
        $customer = User::factory()->memberOf(Site::factory()->create())->create();

        $this->actingAs($customer)->post("/admin/users/{$customer->id}", ['name' => 'x', 'email' => 'x@example.com', 'access_level' => 'admin'])->assertNotFound();
        $this->assertFalse($customer->refresh()->isAdmin());
    }

    public function test_a_disabled_user_cannot_log_in_or_keep_using_a_session(): void
    {
        $site = Site::factory()->create(['slug' => 'own-site']);
        $user = User::factory()->memberOf($site)->create();
        $user->disable();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get('/own-site/dashboard')->assertRedirect(route('login'));

        $user->enable();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    }

    public function test_an_admin_cannot_disable_or_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/admin/users/{$admin->id}/disabled")->assertStatus(422);
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertStatus(422);
        $this->assertFalse($admin->refresh()->isDisabled());
    }

    public function test_token_abilities_separate_reading_from_syncing_and_revoking_is_per_site(): void
    {
        $admin = User::factory()->admin()->create();
        $site = Site::factory()->create(['slug' => 'acme']);
        $other = Site::factory()->create(['slug' => 'other']);

        $this->actingAs($admin)->post('/admin/sites/acme/tokens', ['name' => 'Prod', 'type' => 'read'])->assertSessionHas('new_token');
        $read = session('new_token')['plain'];
        $this->actingAs($admin)->post('/admin/sites/acme/tokens', ['name' => 'CI', 'type' => 'sync'])->assertSessionHas('new_token');
        $sync = session('new_token')['plain'];

        $payload = ['blocks' => []];

        app('auth')->forgetGuards();
        $this->withToken($read)->getJson('/api/pages/home')->assertNotFound();
        app('auth')->forgetGuards();
        $this->withToken($read)->putJson('/api/block-definitions', $payload)->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($sync)->putJson('/api/block-definitions', $payload)->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($sync)->getJson('/api/pages/home')->assertNotFound();

        $token = $site->tokens()->where('name', 'Prod')->firstOrFail();
        app('auth')->forgetGuards();
        $this->actingAs($admin)->delete("/admin/sites/other/tokens/{$token->id}")->assertNotFound();
        $this->actingAs($admin)->delete("/admin/sites/acme/tokens/{$token->id}")->assertRedirect();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_a_user_changes_their_own_password_only_with_the_current_one(): void
    {
        $user = User::factory()->memberOf(Site::factory()->create())->create();

        $this->actingAs($user)
            ->post('/account', ['current_password' => 'wrong', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($user)
            ->post('/account', ['current_password' => 'password', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertRedirect('/account');

        $this->assertTrue(Hash::check('new-password-1', $user->refresh()->password));
        $this->actingAs($user)->get('/account')->assertOk();
    }

    public function test_the_create_admin_command_makes_an_admin(): void
    {
        $this->artisan('cms:create-admin', ['--name' => 'Root', '--email' => 'root@example.com', '--password' => 'long-enough-1'])
            ->assertSuccessful();

        $admin = User::where('email', 'root@example.com')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('long-enough-1', $admin->password));

        $this->artisan('cms:create-admin', ['--name' => 'Root', '--email' => 'root@example.com', '--password' => 'long-enough-1'])
            ->assertFailed();
    }

    public function test_saving_content_pings_only_that_sites_frontend_and_a_failing_frontend_never_blocks_the_save(): void
    {
        $site = Site::factory()->create(['revalidate_url' => 'https://site-a.test/api/revalidate']);
        $site->revalidate_secret = 'shh';
        $site->save();
        $other = Site::factory()->create(['revalidate_url' => 'https://site-b.test/api/revalidate']);
        $other->revalidate_secret = 'other-secret';
        $other->save();

        $user = User::factory()->memberOf($site)->create();
        $page = Page::factory()->create(['site_id' => $site->id, 'slug' => 'about']);
        $definition = CollectionDefinition::factory()->create(['site_id' => $site->id, 'key' => 'team']);
        BlockDefinition::factory()->create(['site_id' => $site->id, 'key' => 'team-grid', 'schema' => [
            ['name' => 'members', 'type' => 'collection'],
        ]]);
        PageBlock::factory()->create(['page_id' => $page->id, 'block_key' => 'team-grid', 'content' => ['members' => 'team']]);

        $this->enter($site);
        $this->actingAs($user);
        URL::defaults(['site' => $site->slug]);

        Http::fake(['site-a.test/*' => Http::response('', 200)]);

        Livewire::test(PageEditor::class, ['page' => $page])->set('title', 'About us')->call('save')->assertHasNoErrors();
        Livewire::test(CollectionEntryEditor::class, ['definition' => $definition])->set('title', 'Ann')->call('save')->assertHasNoErrors();

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://site-a.test/api/revalidate'
            && $request->header('X-Revalidate-Secret') === ['shh']
            && $request['tags'] === ['page:about']);
        Http::assertSent(fn ($request) => $request['tags'] === ['collection:team', 'page:about']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'site-b.test'));

        // A frontend that is down: the content is still saved.
        Http::fake(['site-a.test/*' => Http::response('boom', 500)]);

        Livewire::test(PageEditor::class, ['page' => $page])->set('title', 'About again')->call('save')->assertHasNoErrors();
        $this->assertSame('About again', $page->refresh()->title);
    }

    public function test_a_browser_session_cannot_call_the_token_api(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->getJson('/api/pages/about')->assertForbidden();

        app('auth')->forgetGuards();
        $this->getJson('/api/pages/about')->assertUnauthorized();
    }

    public function test_site_tokens_do_not_depend_on_any_users_account_and_go_with_their_site(): void
    {
        // Deleting a site removes its upload directory; keep that off the real disk.
        Storage::fake('public');

        $site = Site::factory()->create(['slug' => 'acme']);
        $admin = User::factory()->admin()->create();
        Page::factory()->published()->create(['site_id' => $site->id, 'slug' => 'about']);

        $this->actingAs($admin)->post('/admin/sites/acme/tokens', ['name' => 'Prod', 'type' => 'read']);
        $token = session('new_token')['plain'];

        $admin->disable();
        $admin->delete();
        app('auth')->forgetGuards();
        app('auth')->shouldUse('web');

        $this->withToken($token)->getJson('/api/pages/about')->assertOk();

        $site->delete();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_new_records_are_stamped_with_the_current_site(): void
    {
        $site = Site::factory()->create();
        $this->enter($site);

        $record = DummyRecord::query()->create(['name' => 'x', 'site_id' => null]);

        $this->assertSame($site->id, $record->fresh()->site_id);
    }

    public function test_site_id_cannot_be_mass_assigned_to_a_different_site_on_create(): void
    {
        $own = Site::factory()->create();
        $other = Site::factory()->create();
        $this->enter($own);

        $record = DummyRecord::query()->create(['name' => 'x', 'site_id' => $other->id]);

        $this->assertSame($own->id, $record->site_id);
    }

    public function test_site_id_cannot_be_reassigned_on_update(): void
    {
        $own = Site::factory()->create();
        $other = Site::factory()->create();
        $this->enter($own);

        $record = DummyRecord::query()->create(['name' => 'x']);

        $this->expectException(RuntimeException::class);

        $record->update(['site_id' => $other->id]);
    }

    public function test_an_admin_can_enter_a_site_they_are_not_a_member_of(): void
    {
        $site = Site::factory()->create(['slug' => 'client-site']);
        Page::factory()->create(['site_id' => $site->id, 'slug' => 'about', 'title' => 'About Client']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/client-site/dashboard')->assertOk();
        $this->actingAs($admin)->get('/client-site/pages')->assertOk()->assertSee('About Client');
    }

    public function test_a_customer_cannot_enter_a_site_they_are_not_a_member_of(): void
    {
        Site::factory()->create(['slug' => 'client-site']);
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/client-site/dashboard')->assertNotFound();
    }

    public function test_a_customer_gets_a_404_on_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/dashboard')->assertNotFound();
    }

    public function test_an_admin_reaches_the_admin_area(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/dashboard')->assertOk();
    }

    public function test_an_admin_sees_every_site_and_user_with_roles_in_the_admin_lists(): void
    {
        $acme = Site::factory()->create(['name' => 'Acme']);
        $globex = Site::factory()->create(['name' => 'Globex']);
        $owner = User::factory()->create(['name' => 'Olive Owner']);
        $editor = User::factory()->create(['name' => 'Eddie Editor']);
        $acme->users()->attach($owner, ['role' => SiteRole::OWNER]);
        $acme->users()->attach($editor, ['role' => SiteRole::EDITOR]);
        $globex->users()->attach($editor, ['role' => SiteRole::EDITOR]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/sites')
            ->assertOk()->assertSeeInOrder(['Acme', 'Olive Owner', 'Globex'])->assertDontSee('Eddie Editor');
        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()->assertSee('Eddie Editor')->assertSee('2 Sites')->assertSee('x2');
    }

    public function test_a_customer_gets_a_404_on_the_admin_site_and_user_lists(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/sites')->assertNotFound();
        $this->actingAs($customer)->get('/admin/users')->assertNotFound();
    }

    public function test_guests_are_sent_to_login_from_the_admin_area(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_root_sends_an_admin_to_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_routes_without_a_controller_action_in_the_acl_are_denied(): void
    {
        Route::middleware(['web', 'auth', EnforceAcl::class])->get('/_acl-probe', fn () => 'ok');

        $this->actingAs(User::factory()->admin()->create())->get('/_acl-probe')->assertNotFound();
    }

    public function test_a_customer_manages_only_the_members_of_their_own_site(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $other = Site::factory()->create(['slug' => 'elsewhere']);
        $owner = User::factory()->memberOf($site, SiteRole::OWNER)->create();
        $mine = User::factory()->memberOf($site)->create();
        $foreign = User::factory()->memberOf($other)->create();

        $this->actingAs($owner)->get('/own/users')->assertOk()->assertSee($mine->email)->assertDontSee($foreign->email);
        $this->actingAs($owner)->get("/own/users/{$mine->id}")->assertOk()->assertSee($mine->email);
        $this->actingAs($owner)->get('/own/users/create')->assertOk();
        $this->actingAs($owner)->get("/own/users/{$foreign->id}")->assertNotFound();
        $this->actingAs($owner)->post("/own/users/{$foreign->id}/role", ['role' => SiteRole::OWNER])->assertNotFound();
        $this->actingAs($owner)->delete("/own/users/{$foreign->id}")->assertNotFound();

        $this->actingAs($owner)->post("/own/users/{$mine->id}", ['name' => 'Renamed', 'email' => 'renamed@example.com'])->assertRedirect();
        $this->assertSame('Renamed', $mine->refresh()->name);
    }

    public function test_a_customer_adds_new_and_existing_users_and_cannot_edit_shared_accounts(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $other = Site::factory()->create(['slug' => 'elsewhere']);
        $owner = User::factory()->memberOf($site, SiteRole::OWNER)->create();
        $shared = User::factory()->memberOf($other)->create(['name' => 'Shared Person']);

        $this->actingAs($owner)->post('/own/users', ['name' => 'New Person', 'email' => 'new@example.com', 'role' => SiteRole::EDITOR])
            ->assertSessionHas('shown_password');
        $new = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame(SiteRole::EDITOR, $new->roleOn($site));
        $this->assertFalse($new->isAdmin());

        $this->actingAs($owner)->post('/own/users', ['name' => 'Ignored', 'email' => $shared->email, 'role' => SiteRole::ADMIN])->assertRedirect();
        $this->assertSame(SiteRole::ADMIN, $shared->roleOn($site));
        $this->assertSame('Shared Person', $shared->refresh()->name);

        $this->actingAs($owner)->post("/own/users/{$shared->id}", ['name' => 'Hijacked', 'email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($owner)->post("/own/users/{$shared->id}/password", ['password' => 'new-password-1'])->assertForbidden();

        $this->actingAs($owner)->delete("/own/users/{$shared->id}")->assertRedirect();
        $this->assertNull($shared->roleOn($site));
        $this->assertSame(SiteRole::EDITOR, $shared->roleOn($other));
        $this->assertDatabaseHas('users', ['id' => $shared->id]);
    }

    public function test_a_site_keeps_its_last_owner_and_members_cannot_change_their_own_role(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $owner = User::factory()->memberOf($site, SiteRole::OWNER)->create();
        $admin = User::factory()->memberOf($site, SiteRole::ADMIN)->create();

        $this->actingAs($owner)->post("/own/users/{$owner->id}/role", ['role' => SiteRole::EDITOR])->assertStatus(422);
        $this->actingAs($owner)->delete("/own/users/{$owner->id}")->assertStatus(422);
        // Admins cannot change roles at all, or remove an owner.
        $this->actingAs($admin)->post("/own/users/{$owner->id}/role", ['role' => SiteRole::EDITOR])->assertNotFound();
        $this->actingAs($admin)->delete("/own/users/{$owner->id}")->assertForbidden();
        $this->assertSame(SiteRole::OWNER, $owner->roleOn($site));
    }

    public function test_saving_a_page_records_who_changed_what_and_history_is_site_scoped_and_immutable(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $other = Site::factory()->create(['slug' => 'elsewhere']);
        $user = User::factory()->memberOf($site, SiteRole::ADMIN)->create(['name' => 'Edie Editor']);
        $stranger = User::factory()->memberOf($other, SiteRole::ADMIN)->create();

        BlockDefinition::factory()->create(['site_id' => $site->id, 'key' => 'hero', 'schema' => [
            ['name' => 'heading', 'type' => 'text'],
        ]]);
        $page = Page::factory()->create(['site_id' => $site->id, 'slug' => 'home', 'title' => 'Home']);
        PageBlock::factory()->create(['page_id' => $page->id, 'block_key' => 'hero', 'content' => ['heading' => 'Old']]);

        $this->enter($site);
        $this->actingAs($user);

        Livewire::test(PageEditor::class, ['page' => $page])
            ->set('title', 'Welcome')
            ->set('blocks.0.content.heading', 'New')
            ->call('save')
            ->assertHasNoErrors();

        $log = ActivityLog::where('subject_type', 'page')->where('event', 'updated')->get()->first(fn ($entry) => isset($entry->changes['title']));
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Edie Editor', $log->actor());
        $this->assertSame(['old' => 'Home', 'new' => 'Welcome'], $log->changes['title']);

        $blockLog = ActivityLog::where('subject_type', 'page')->get()->first(fn ($entry) => isset($entry->changes['hero → content.heading']));
        $this->assertSame(['old' => 'Old', 'new' => 'New'], $blockLog->changes['hero → content.heading']);

        // Saving again without edits records nothing new.
        $count = ActivityLog::count();
        Livewire::test(PageEditor::class, ['page' => $page])->call('save');
        $this->assertSame($count, ActivityLog::count());

        $this->actingAs($user)->get('/own/history')->assertOk()->assertSee('Edie Editor')->assertSee('Welcome');
        $this->actingAs($stranger)->get('/own/history')->assertNotFound();
        $this->actingAs($stranger)->get('/elsewhere/history')->assertOk()->assertDontSee('Edie Editor');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be');

        try {
            $log->update(['event' => 'tampered']);
        } catch (LogicException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public function test_site_roles_decide_what_a_member_can_reach_inside_the_site(): void
    {
        Storage::fake('public');

        $site = Site::factory()->create(['slug' => 'own']);
        $owner = User::factory()->memberOf($site, SiteRole::OWNER)->create();
        $admin = User::factory()->memberOf($site, SiteRole::ADMIN)->create();
        $editor = User::factory()->memberOf($site, SiteRole::EDITOR)->create();
        $platformAdmin = User::factory()->admin()->create();
        $image = Media::factory()->create(['site_id' => $site->id]);
        Page::factory()->create(['site_id' => $site->id, 'slug' => 'home']);

        // Everyone in the site works on content, media and uploads.
        foreach ([$owner, $admin, $editor, $platformAdmin] as $user) {
            $this->actingAs($user)->get('/own/dashboard')->assertOk();
            $this->actingAs($user)->get('/own/pages/home/edit')->assertOk();
            $this->actingAs($user)->get('/own/media')->assertOk();
        }

        // Editors stop there.
        $this->actingAs($editor)->get('/own/users')->assertNotFound();
        $this->actingAs($editor)->get('/own/history')->assertNotFound();
        $this->actingAs($editor)->delete("/own/media/{$image->id}")->assertNotFound();
        $this->actingAs($editor)->post('/own/media/bulk-delete', ['ids' => [$image->id]])->assertNotFound();
        $this->assertDatabaseHas('media', ['id' => $image->id]);

        // Admins manage people and deletions, but not roles.
        $this->actingAs($admin)->get('/own/users')->assertOk();
        $this->actingAs($admin)->get('/own/history')->assertOk();
        $this->actingAs($admin)->post("/own/users/{$editor->id}/role", ['role' => SiteRole::ADMIN])->assertNotFound();
        $this->assertSame(SiteRole::EDITOR, $editor->roleOn($site));

        // Owners (and platform admins) change roles.
        $this->actingAs($owner)->post("/own/users/{$editor->id}/role", ['role' => SiteRole::ADMIN])->assertRedirect();
        $this->assertSame(SiteRole::ADMIN, $editor->roleOn($site));
        $this->actingAs($platformAdmin)->get('/own/users')->assertOk();

        $this->actingAs($admin)->delete("/own/media/{$image->id}")->assertRedirect();
        $this->assertDatabaseMissing('media', ['id' => $image->id]);
    }

    public function test_the_sidebar_only_offers_what_the_members_role_reaches(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $editor = User::factory()->memberOf($site, SiteRole::EDITOR)->create();
        $owner = User::factory()->memberOf($site, SiteRole::OWNER)->create();

        $this->actingAs($editor)->get('/own/dashboard')->assertSee('/own/pages', false)->assertDontSee('/own/users', false)->assertDontSee('/own/history', false);
        $this->actingAs($owner)->get('/own/dashboard')->assertSee('/own/users', false)->assertSee('/own/history', false);
    }

    public function test_admins_cannot_hand_out_ownership_or_touch_owners(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $owner = User::factory()->memberOf($site, SiteRole::OWNER)->create();
        $admin = User::factory()->memberOf($site, SiteRole::ADMIN)->create();

        $this->actingAs($admin)->post('/own/users', ['name' => 'Boss', 'email' => 'boss@example.com', 'role' => SiteRole::OWNER])
            ->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'boss@example.com']);

        $this->actingAs($admin)->post("/own/users/{$owner->id}", ['name' => 'Hijack', 'email' => 'h@example.com'])->assertForbidden();
        $this->actingAs($admin)->post("/own/users/{$owner->id}/password", ['password' => 'new-password-1'])->assertForbidden();

        $this->actingAs($admin)->post('/own/users', ['name' => 'Ed', 'email' => 'ed@example.com', 'role' => SiteRole::EDITOR])->assertRedirect();
        $this->assertSame(SiteRole::EDITOR, User::where('email', 'ed@example.com')->firstOrFail()->roleOn($site));
    }

    public function test_deleting_an_entry_in_the_list_needs_an_admin_or_owner(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $editor = User::factory()->memberOf($site, SiteRole::EDITOR)->create();
        $admin = User::factory()->memberOf($site, SiteRole::ADMIN)->create();
        $definition = CollectionDefinition::factory()->create(['site_id' => $site->id, 'key' => 'team']);
        $entry = CollectionEntry::factory()->create(['site_id' => $site->id, 'collection_key' => 'team']);

        $this->enter($site);
        URL::defaults(['site' => $site->slug]);

        $this->actingAs($editor);
        Livewire::test(CollectionEntryList::class, ['definition' => $definition])->call('delete', $entry->id)->assertForbidden();
        $this->assertDatabaseHas('collection_entries', ['id' => $entry->id]);

        $this->actingAs($admin);
        Livewire::test(CollectionEntryList::class, ['definition' => $definition])->call('delete', $entry->id);
        $this->assertDatabaseMissing('collection_entries', ['id' => $entry->id]);
    }

    public function test_editors_can_publish_and_unpublish_pages(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $editor = User::factory()->memberOf($site, SiteRole::EDITOR)->create();
        $page = Page::factory()->create(['site_id' => $site->id, 'slug' => 'home', 'status' => PageStatus::DRAFT]);

        $this->enter($site);
        $this->actingAs($editor);

        Livewire::test(PageEditor::class, ['page' => $page])->set('status', PageStatus::PUBLISHED)->call('save')->assertHasNoErrors();
        $this->assertSame(PageStatus::PUBLISHED, $page->refresh()->status);

        Livewire::test(PageEditor::class, ['page' => $page])->set('status', PageStatus::DRAFT)->call('save')->assertHasNoErrors();
        $this->assertSame(PageStatus::DRAFT, $page->refresh()->status);
    }

    public function test_media_is_searched_paginated_and_filed_into_folders_within_the_site(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $other = Site::factory()->create(['slug' => 'elsewhere']);
        $user = User::factory()->memberOf($site)->create();
        $folder = MediaFolder::create(['site_id' => $site->id, 'name' => 'Team']);
        $foreignFolder = MediaFolder::create(['site_id' => $other->id, 'name' => 'Theirs']);

        Media::factory()->count(30)->create(['site_id' => $site->id, 'original_name' => 'generic.jpg']);
        Media::factory()->create(['site_id' => $site->id, 'original_name' => 'Sunset-Beach.jpg', 'alt' => 'Evening', 'folder_id' => $folder->id]);
        Media::factory()->create(['site_id' => $other->id, 'original_name' => 'sunset-elsewhere.jpg']);

        $this->actingAs($user)->get('/own/media')->assertOk()->assertSee('Team')->assertDontSee('Theirs')->assertDontSee('Sunset-Beach.jpg');
        $this->actingAs($user)->get('/own/media?page=2')->assertOk();
        $this->actingAs($user)->get("/own/media?folder={$folder->id}")->assertOk()->assertSee('Sunset-Beach.jpg')->assertDontSee('generic.jpg');
        $this->actingAs($user)->get('/own/media?q=SUNSET')->assertOk()->assertSee('Sunset-Beach.jpg')->assertDontSee('sunset-elsewhere.jpg');
        $this->actingAs($user)->get('/own/media?q=100%25')->assertOk();

        $image = Media::where('original_name', 'generic.jpg')->firstOrFail();
        $this->actingAs($user)->post("/own/media/{$image->id}", ['alt' => 'x', 'folder_id' => $foreignFolder->id])->assertSessionHasErrors('folder_id');
        $this->actingAs($user)->post("/own/media/{$image->id}", ['alt' => 'x', 'folder_id' => $folder->id])->assertRedirect();
        $this->assertSame($folder->id, $image->refresh()->folder_id);

        $this->actingAs($user)->post('/own/media/bulk-move', ['ids' => [$image->id], 'folder_id' => ''])->assertRedirect();
        $this->assertNull($image->refresh()->folder_id);
    }

    public function test_deleting_a_folder_moves_its_contents_up_and_names_stay_unique_per_level(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $user = User::factory()->memberOf($site)->create();
        $parent = MediaFolder::create(['site_id' => $site->id, 'name' => 'Parent']);
        $child = MediaFolder::create(['site_id' => $site->id, 'name' => 'Child', 'parent_id' => $parent->id]);
        $image = Media::factory()->create(['site_id' => $site->id, 'folder_id' => $child->id]);

        $this->actingAs($user)->post('/own/media-folders', ['name' => 'Parent'])->assertSessionHasErrors('name');
        $this->actingAs($user)->post('/own/media-folders', ['name' => 'Child', 'parent_id' => $parent->id])->assertSessionHasErrors('name');
        $this->actingAs($user)->post('/own/media-folders', ['name' => 'Sibling', 'parent_id' => $parent->id])->assertRedirect();

        $this->actingAs($user)->delete("/own/media-folders/{$child->id}")->assertRedirect();
        $this->assertSame($parent->id, $image->refresh()->folder_id);
        $this->assertDatabaseMissing('media_folders', ['id' => $child->id]);
    }

    public function test_uploads_get_resized_copies_a_hash_and_identical_files_are_refused(): void
    {
        Storage::fake('public');

        $site = Site::factory()->create(['slug' => 'own']);
        $user = User::factory()->memberOf($site)->create();

        $this->actingAs($user)->post('/own/media', ['file' => UploadedFile::fake()->image('wide.png', 2000, 1000), 'alt' => 'Wide'])->assertRedirect();

        $media = Media::withoutGlobalScopes()->where('site_id', $site->id)->firstOrFail();
        $this->assertSame(64, strlen($media->hash));
        $this->assertSame(['thumb', 'medium', 'large'], array_keys($media->variants));

        foreach ($media->variants as $variant) {
            Storage::disk('public')->assertExists($variant['path']);
        }

        $this->assertSame($media->size + array_sum(array_column($media->variants, 'size')), $media->stored_bytes);
        $this->assertCount(3, $media->toPayload()['variants']);
        $this->assertStringContainsString('320w', $media->toPayload()['srcset']);

        // The same bytes again are refused; a narrow image gets no copies.
        $this->actingAs($user)->post('/own/media', ['file' => UploadedFile::fake()->createWithContent('same.png', Storage::disk('public')->get($media->path)), 'alt' => 'Again'])
            ->assertSessionHasErrors('file');
        $this->assertSame(1, Media::withoutGlobalScopes()->where('site_id', $site->id)->count());

        $this->actingAs($user)->post('/own/media', ['file' => UploadedFile::fake()->image('small.png', 200, 100), 'alt' => 'Small'])->assertRedirect();
        $this->assertNull(Media::withoutGlobalScopes()->where('original_name', 'small.png')->firstOrFail()->variants);

        // Deleting removes the copies as well.
        $paths = $media->filePaths();
        $media->delete();

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_replacing_a_file_keeps_the_id_and_cleans_up_the_old_files(): void
    {
        Storage::fake('public');

        $site = Site::factory()->create(['slug' => 'own']);
        $user = User::factory()->memberOf($site)->create();
        $this->actingAs($user)->post('/own/media', ['file' => UploadedFile::fake()->image('a.png', 1000, 500), 'alt' => 'Keep me'])->assertRedirect();
        $media = Media::withoutGlobalScopes()->where('site_id', $site->id)->firstOrFail();
        $oldPaths = $media->filePaths();

        $this->actingAs($user)->post("/own/media/{$media->id}/replace", ['file' => UploadedFile::fake()->image('b.png', 900, 450)])->assertRedirect();

        $media->refresh();
        $this->assertSame('Keep me', $media->alt);
        $this->assertSame('b.png', $media->original_name);
        $this->assertSame(900, $media->width);
        Storage::disk('public')->assertExists($media->path);

        foreach ($oldPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_the_plan_storage_limit_blocks_uploads_and_bespoke_is_unlimited(): void
    {
        Storage::fake('public');

        $starter = Site::factory()->create(['slug' => 'own', 'plan' => SitePlan::STARTER]);
        $bespoke = Site::factory()->create(['slug' => 'big', 'plan' => SitePlan::BESPOKE]);
        $user = User::factory()->memberOf($starter)->create();
        $bigUser = User::factory()->memberOf($bespoke)->create();

        Media::factory()->create(['site_id' => $starter->id, 'size' => 1024 ** 3, 'stored_bytes' => 1024 ** 3]);
        Media::factory()->create(['site_id' => $bespoke->id, 'size' => 1024 ** 4, 'stored_bytes' => 1024 ** 4]);

        $this->actingAs($user)->post('/own/media', ['file' => UploadedFile::fake()->image('x.png', 100, 100), 'alt' => 'x'])->assertSessionHasErrors('file');
        $this->assertSame(1, Media::withoutGlobalScopes()->where('site_id', $starter->id)->count());

        $this->actingAs($bigUser)->post('/big/media', ['file' => UploadedFile::fake()->image('x.png', 100, 100), 'alt' => 'x'])->assertRedirect();
        $this->assertSame(2, Media::withoutGlobalScopes()->where('site_id', $bespoke->id)->count());
    }

    public function test_media_usage_follows_page_blocks_and_entries_and_guards_deletion(): void
    {
        $site = Site::factory()->create(['slug' => 'own']);
        $admin = User::factory()->memberOf($site, SiteRole::ADMIN)->create();
        $used = Media::factory()->create(['site_id' => $site->id]);
        $free = Media::factory()->create(['site_id' => $site->id]);

        BlockDefinition::factory()->create(['site_id' => $site->id, 'key' => 'banner', 'schema' => [['name' => 'image', 'type' => 'image']]]);
        CollectionDefinition::factory()->create(['site_id' => $site->id, 'key' => 'team', 'schema' => [['name' => 'photo', 'type' => 'image']]]);
        $page = Page::factory()->create(['site_id' => $site->id, 'slug' => 'home']);
        $block = PageBlock::factory()->create(['page_id' => $page->id, 'block_key' => 'banner', 'content' => ['image' => $used->id]]);
        $entry = CollectionEntry::factory()->create(['site_id' => $site->id, 'collection_key' => 'team', 'data' => ['photo' => $used->id]]);

        $this->assertDatabaseHas('media_usages', ['media_id' => $used->id, 'subject_type' => 'page', 'subject_id' => $page->id]);
        $this->assertDatabaseHas('media_usages', ['media_id' => $used->id, 'subject_type' => 'collection_entry', 'subject_id' => $entry->id]);

        $this->actingAs($admin)->get('/own/media')->assertOk()->assertSee('Used in 2 places');

        // In use: refused unless forced; bulk delete keeps it and removes the free one.
        $this->actingAs($admin)->delete("/own/media/{$used->id}")->assertRedirect();
        $this->assertDatabaseHas('media', ['id' => $used->id]);
        $this->actingAs($admin)->post('/own/media/bulk-delete', ['ids' => [$used->id, $free->id]])->assertRedirect();
        $this->assertDatabaseHas('media', ['id' => $used->id]);
        $this->assertDatabaseMissing('media', ['id' => $free->id]);

        // Clearing the field and deleting the entry empties the usage.
        $block->update(['content' => ['image' => null]]);
        $entry->delete();
        $this->assertDatabaseCount('media_usages', 0);

        $block->update(['content' => ['image' => $used->id]]);
        $this->actingAs($admin)->delete("/own/media/{$used->id}", ['force' => 1])->assertRedirect();
        $this->assertDatabaseMissing('media', ['id' => $used->id]);
        $this->assertDatabaseCount('media_usages', 0);
    }

    public function test_the_media_rebuild_command_backfills_hashes_copies_and_usages(): void
    {
        Storage::fake('public');

        $site = Site::factory()->create(['slug' => 'own']);
        $path = UploadedFile::fake()->image('old.png', 1000, 500)->store("sites/{$site->id}", 'public');
        $media = Media::factory()->create(['site_id' => $site->id, 'path' => $path, 'hash' => null, 'variants' => null, 'width' => null, 'height' => null]);

        BlockDefinition::factory()->create(['site_id' => $site->id, 'key' => 'banner', 'schema' => [['name' => 'image', 'type' => 'image']]]);
        $page = Page::factory()->create(['site_id' => $site->id]);
        PageBlock::factory()->create(['page_id' => $page->id, 'block_key' => 'banner', 'content' => ['image' => $media->id]]);
        DB::table('media_usages')->delete();

        $this->artisan('cms:media-rebuild')->assertSuccessful();

        $media->refresh();
        $this->assertSame(hash('sha256', Storage::disk('public')->get($path)), $media->hash);
        $this->assertSame(1000, $media->width);
        $this->assertNotEmpty($media->variants);
        $this->assertDatabaseHas('media_usages', ['media_id' => $media->id, 'subject_id' => $page->id]);
    }

    public function test_reserved_slugs_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Site::factory()->create(['slug' => 'login']);
    }
}

class DummyRecord extends Model
{
    use BelongsToSite;

    protected $table = 'dummy_site_owned_records';

    protected $guarded = [];
}
