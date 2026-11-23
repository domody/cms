<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Throwable;

/**
 * A site (a client's website). Users belong to sites many-to-many through
 * the site_user pivot, with a per-site role. The slug is the first URL
 * segment of the admin panel (/{slug}/dashboard), so it must not collide
 * with a top-level route.
 */
#[Fillable(['name', 'slug', 'revalidate_url'])]
#[Hidden(['revalidate_secret'])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasApiTokens, HasFactory;

    /**
     * Slugs that would shadow a top-level route.
     *
     * @var array<int, string>
     */
    public const RESERVED_SLUGS = ['login', 'logout', 'livewire', 'api', 'up', 'storage', 'build', 'admin', 'account'];

    /**
     * Mirrors the column default, so a freshly created model is on Starter.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'plan' => SitePlan::STARTER,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revalidate_secret' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Site $site): void {
            if (in_array($site->slug, self::RESERVED_SLUGS, true)) {
                throw new InvalidArgumentException("The slug [{$site->slug}] is reserved.");
            }
        });

        // Deleting the row cascades the site's records in the database, which
        // doesn't run model events, so its uploaded files are removed here.
        static::deleted(function (Site $site): void {
            Storage::disk(config('filesystems.media_disk'))->deleteDirectory("sites/{$site->id}");
        });
    }

    /**
     * Issue an API token owned by this site: it authenticates a frontend as
     * the site itself, not as any user, so it is unaffected by changes to
     * people's accounts. The site_id column mirrors the owner so deleting the
     * site removes its tokens in the database.
     *
     * @param  array<int, string>  $abilities
     */
    public function issueToken(string $name, array $abilities = ['read']): NewAccessToken
    {
        $token = $this->createToken($name, $abilities);
        $token->accessToken->forceFill(['site_id' => $this->id])->save();

        return $token;
    }

    /**
     * Tell the site's frontend that content changed, so it drops its cached
     * copies and edits show up straight away. POSTs the cache tags to the
     * revalidate URL with the shared secret. Does nothing unless both are set.
     * Never throws: a frontend that is down or slow must not stop an editor
     * saving, so a failure is only logged.
     *
     * Tags: `page:{slug}` and `collection:{key}`.
     *
     * @param  array<int, string>  $tags
     */
    public function revalidateFrontend(array $tags): void
    {
        if (blank($this->revalidate_url) || blank($this->revalidate_secret) || $tags === []) {
            return;
        }

        try {
            Http::timeout(3)
                ->connectTimeout(2)
                ->acceptJson()
                ->withHeaders(['X-Revalidate-Secret' => $this->revalidate_secret])
                ->post($this->revalidate_url, ['tags' => array_values(array_unique($tags))])
                ->throw();
        } catch (Throwable $e) {
            Log::warning("Could not revalidate the frontend of site [{$this->slug}]: {$e->getMessage()}");
        }
    }

    /**
     * Every cache tag of the site's content, for changes that can touch any
     * of it (an image's alt text, a sync). Call inside the site.
     *
     * @return array<int, string>
     */
    public function allContentTags(): array
    {
        return [
            ...Page::pluck('slug')->map(fn (string $slug) => "page:$slug")->all(),
            ...CollectionDefinition::pluck('key')->map(fn (string $key) => "collection:$key")->all(),
        ];
    }

    /**
     * Media storage the site's plan allows, in bytes, or null for no limit.
     */
    public function storageLimit(): ?int
    {
        return SitePlan::storageLimit($this->plan);
    }

    /**
     * @return BelongsToMany<User, $this, SiteMembership>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(SiteMembership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Members holding the owner role on this site.
     *
     * @return BelongsToMany<User, $this, SiteMembership>
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', SiteRole::OWNER);
    }
}
