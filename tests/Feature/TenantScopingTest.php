<?php

namespace Tests\Feature;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class TenantScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A throwaway tenant-owned model, standing in for a future content
        // model (Page, Post, ...) - proves the trait/scope work generically,
        // without adding a real content model to the app itself.
        Schema::create('dummy_tenant_owned_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    public function test_authenticated_user_only_sees_records_from_their_own_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        User::factory()->for($tenantA)->count(2)->create();
        User::factory()->for($tenantB)->count(3)->create();

        $this->actingAs($userA);

        $this->assertSame(3, User::count());
        $this->assertTrue(User::all()->every(fn (User $user) => $user->tenant_id === $tenantA->id));
    }

    public function test_unauthenticated_reads_of_tenant_owned_content_fail_closed(): void
    {
        $tenant = Tenant::factory()->create();
        DummyTenantOwnedRecord::query()->create(['tenant_id' => $tenant->id, 'name' => 'secret']);

        $this->assertSame(0, DummyTenantOwnedRecord::count());
    }

    public function test_unauthenticated_direct_query_against_user_fails_closed(): void
    {
        // Unlike login/password-reset (which go through the tenant-unscoped
        // provider below), a plain query against User gets no special
        // treatment: unauthenticated means fail closed, same as any other
        // tenant-owned model.
        $tenant = Tenant::factory()->create();
        User::factory()->for($tenant)->create(['email' => 'someone@example.com']);

        $this->assertFalse(User::where('email', 'someone@example.com')->exists());
    }

    public function test_login_finds_a_user_across_tenants_via_the_unscoped_provider(): void
    {
        // Login has to find a user by email before any tenant is known -
        // that's what TenantUnscopedEloquentUserProvider exists for.
        $tenant = Tenant::factory()->create();
        User::factory()->for($tenant)->create(['email' => 'someone@example.com']);

        $this->assertTrue(Auth::attempt(['email' => 'someone@example.com', 'password' => 'password']));
        $this->assertSame($tenant->id, Auth::user()->tenant_id);
    }

    public function test_session_authentication_resolves_without_infinite_recursion(): void
    {
        // A session that already carries a user id, resolved by a *fresh*
        // guard that has never cached a user - this is the path that used
        // to recurse: SessionGuard::user() finds the id in the session and
        // calls retrieveById(); the scope's old `auth()->user()` check
        // re-entered SessionGuard::user(), which found the same session id
        // and called retrieveById() again, forever. It's fixed now because
        // retrieveById() runs through TenantUnscopedEloquentUserProvider,
        // whose query never has TenantScope attached in the first place -
        // there's nothing left to recurse into. actingAs() or loginUsingId()
        // would not catch a regression here: both set/cache the guard's
        // user directly and never exercise retrieveById() against an
        // already-populated session.
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();

        $sessionKey = 'login_web_'.sha1(SessionGuard::class);
        $response = $this->withSession([$sessionKey => $user->id])->get('/admin');

        $response->assertRedirect('/admin/'.$tenant->slug);
    }

    public function test_authenticated_panel_page_loads_for_the_correct_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();

        $sessionKey = 'login_web_'.sha1(SessionGuard::class);
        $response = $this->withSession([$sessionKey => $user->id])->get('/admin/'.$tenant->slug);

        $response->assertOk();
    }

    public function test_sanctum_token_authentication_resolves_without_infinite_recursion(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk();
        $response->assertJson(['id' => $user->id]);
    }

    public function test_sanctum_authenticated_api_request_only_sees_its_own_tenants_data(): void
    {
        // Proves the scope actually isolates tenants over a token-authenticated
        // API request, not just that the request succeeds. The route is
        // defined here, in the test, purely as a probe - no endpoint is
        // added to the app itself.
        Route::middleware('auth:sanctum')->get('/_test-probe', fn () => [
            'tenant_ids' => User::query()->pluck('tenant_id')->unique()->values(),
            'record_count' => DummyTenantOwnedRecord::count(),
        ]);

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        User::factory()->for($tenantB)->create();

        DummyTenantOwnedRecord::query()->create(['tenant_id' => $tenantA->id]);
        DummyTenantOwnedRecord::query()->create(['tenant_id' => $tenantB->id]);
        DummyTenantOwnedRecord::query()->create(['tenant_id' => $tenantB->id]);

        $token = $userA->createToken('probe')->plainTextToken;

        $response = $this->withToken($token)->getJson('/_test-probe');

        $response->assertOk();
        $response->assertExactJson([
            'tenant_ids' => [$tenantA->id],
            'record_count' => 1,
        ]);
    }

    public function test_new_tenant_owned_records_are_stamped_with_the_authenticated_users_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();

        $this->actingAs($user);

        $record = DummyTenantOwnedRecord::query()->create(['name' => 'from factory', 'tenant_id' => null]);

        $this->assertSame($tenant->id, $record->fresh()->tenant_id);
    }

    public function test_tenant_id_cannot_be_mass_assigned_to_a_different_tenant_on_create(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->for($ownTenant)->create();

        $this->actingAs($user);

        $record = DummyTenantOwnedRecord::query()->create(['name' => 'x', 'tenant_id' => $otherTenant->id]);

        $this->assertSame($ownTenant->id, $record->tenant_id);
    }

    public function test_tenant_id_cannot_be_reassigned_on_update(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->for($ownTenant)->create();

        $this->actingAs($user);

        $record = DummyTenantOwnedRecord::query()->create(['name' => 'x']);

        $this->expectException(RuntimeException::class);

        $record->update(['tenant_id' => $otherTenant->id]);
    }
}

class DummyTenantOwnedRecord extends Model
{
    use BelongsToTenant;

    protected $table = 'dummy_tenant_owned_records';

    protected $guarded = [];
}
