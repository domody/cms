<?php

namespace App\Models;

use App\Models\Scopes\CurrentSite;
use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * Append-only record of a change on a site: who did it (a user, or an API
 * token when the frontend syncs), when, to what, and what changed.
 *
 * `changes` is {field: {old, new}} (dotted paths for nested content). Actor
 * and subject are snapshotted as text so entries outlive the rows they
 * describe. Written by the LogsActivity trait and, for things that are not
 * models (membership, reordering), by write().
 */
class ActivityLog extends Model
{
    const UPDATED_AT = null;

    /**
     * Human names of subject types, for the history page and its filter.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'page' => 'Page',
        'collection_entry' => 'Collection entry',
        'collection' => 'Collection',
        'media' => 'Media',
        'media_folder' => 'Media folder',
        'user' => 'Member',
        'manifest' => 'Manifest',
    ];

    /** Longest value kept in a diff, so a long text doesn't bloat the table. */
    private const MAX_VALUE_LENGTH = 300;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new SiteScope);

        // Immutable: the log is only worth anything if nobody can edit it.
        static::updating(fn () => throw new LogicException('Activity log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Activity log entries cannot be deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
            'subject_id' => 'integer',
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? Str::headline($type);
    }

    public function actor(): string
    {
        return $this->actor_name ?? 'System';
    }

    /**
     * Record one entry. Without an explicit site it uses the current one.
     * An "updated" event with nothing changed is not recorded.
     *
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    public static function write(string $type, ?int $id, string $label, string $event, ?array $changes = null, ?int $siteId = null): ?self
    {
        $siteId ??= app(CurrentSite::class)->id();

        if ($siteId === null || ($event === 'updated' && $changes === [])) {
            return null;
        }

        $actor = auth()->user();
        $user = $actor instanceof User ? $actor : null;
        $isApi = request()->is('api/*');

        return self::withoutGlobalScopes()->create([
            'site_id' => $siteId,
            'user_id' => $user?->id,
            'actor_name' => $user?->name ?? ($isApi ? 'API token'.self::tokenName($actor) : null),
            'source' => $user ? 'web' : ($isApi ? 'api' : 'console'),
            'event' => $event,
            'subject_type' => $type,
            'subject_id' => $id,
            'subject_label' => Str::limit($label, 250, ''),
            'changes' => $changes ?: null,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Record an event about a model, using its activity type and label.
     *
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    public static function record(Model $subject, string $event, ?array $changes = null): ?self
    {
        return self::write(
            $subject->activityType(),
            $subject->getKey(),
            $subject->activityLabel(),
            $event,
            $changes,
            $subject->site_id,
        );
    }

    /**
     * Field-by-field difference between two attribute sets. Nested keyed
     * arrays (block content) are compared per key, as dotted paths; empty
     * strings and null count as equal.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diff(array $old, array $new, string $prefix = ''): array
    {
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            $path = $prefix === '' ? (string) $key : "$prefix.$key";
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;

            $isKeyed = fn ($value) => is_array($value) && $value !== [] && ! array_is_list($value);

            if ($isKeyed($before) || $isKeyed($after)) {
                $changes += self::diff((array) $before, (array) $after, $path);

                continue;
            }

            if (self::display($before) === self::display($after)) {
                continue;
            }

            $changes[$path] = ['old' => self::display($before), 'new' => self::display($after)];
        }

        return $changes;
    }

    private static function display(mixed $value): mixed
    {
        if (is_array($value)) {
            $value = $value === [] ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            $value = Str::limit(trim($value), self::MAX_VALUE_LENGTH);
        }

        return $value === '' ? null : $value;
    }

    private static function tokenName(mixed $actor): string
    {
        $name = $actor instanceof Site ? $actor->currentAccessToken()?->name : null;

        return $name === null ? '' : " ({$name})";
    }
}
