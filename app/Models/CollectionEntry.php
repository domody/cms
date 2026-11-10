<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\LogsActivity;
use Database\Factories\CollectionEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One item of a collection (a team member, a testimonial, ...). `data` holds
 * the values of the collection definition's fields; the title and slug are
 * columns because every entry has them.
 */
#[Fillable(['site_id', 'collection_key', 'slug', 'title', 'status', 'position', 'data'])]
class CollectionEntry extends Model
{
    /** @use HasFactory<CollectionEntryFactory> */
    use BelongsToSite, HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::created(fn (self $entry) => $entry->syncMediaUsage());
        static::updated(fn (self $entry) => $entry->wasChanged('data') && $entry->syncMediaUsage());
        static::deleted(fn (self $entry) => $entry->syncMediaUsage(deleted: true));
    }

    /**
     * Record which images the entry holds. Runs outside a site, so every
     * lookup names the entry's site.
     */
    public function syncMediaUsage(bool $deleted = false): void
    {
        $definition = CollectionDefinition::withoutGlobalScopes()
            ->where('site_id', $this->site_id)
            ->where('key', $this->collection_key)
            ->first();

        $ids = $deleted ? [] : Media::idsAt($this->data ?? [], $definition?->imageFieldNames() ?? []);

        MediaUsage::syncSubject('collection_entry', $this->id, $this->site_id, $ids);
    }

    /**
     * Reordering is logged once per drag by the list, not once per entry.
     *
     * @return array<int, string>
     */
    public function activityIgnored(): array
    {
        return ['position'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
            'position' => 'integer',
            'data' => 'array',
        ];
    }

    /**
     * The published entries of a collection, in order, in the shape handed to
     * frontends.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function publishedPayloads(string $collectionKey): array
    {
        return self::payloads(
            self::where('collection_key', $collectionKey)
                ->where('status', PageStatus::PUBLISHED)
                ->orderBy('position')
                ->get(),
        );
    }

    /**
     * Entries in the shape handed to frontends: collection key, slug, title, then each
     * field, with image fields resolved to {url, alt, width, height}.
     *
     * @param  Collection<int, CollectionEntry>  $entries
     * @return array<int, array<string, mixed>>
     */
    public static function payloads(Collection $entries): array
    {
        $definitions = CollectionDefinition::all()->keyBy('key');

        $items = $entries->map(function (CollectionEntry $entry) use ($definitions): array {
            $definition = $definitions->get($entry->collection_key);
            $content = $entry->data ?? [];

            foreach ($definition?->editableFields() ?? [] as $field) {
                $content[$field['name']] ??= $field['type'] === 'repeater' ? [] : null;
            }

            return ['content' => $content, 'images' => $definition?->imageFieldNames() ?? []];
        })->all();

        $resolved = Media::resolveImages($items);

        return $entries->map(fn (CollectionEntry $entry, int $index): array => [
            'collection' => $entry->collection_key,
            'slug' => $entry->slug,
            'title' => $entry->title,
        ] + $resolved[$index])->values()->all();
    }

    /**
     * A slug for a new entry that is unique within its collection: the
     * wanted text slugified, with -2, -3, ... appended on a clash.
     */
    public static function uniqueSlug(string $collectionKey, string $wanted, ?int $ignoreId = null): string
    {
        $base = Str::slug($wanted) ?: 'entry';
        $slug = $base;
        $suffix = 2;

        while (self::where('collection_key', $collectionKey)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "$base-$suffix";
            $suffix++;
        }

        return $slug;
    }

    /**
     * The position after the last entry of a collection.
     */
    public static function nextPosition(string $collectionKey): int
    {
        $max = self::where('collection_key', $collectionKey)->max('position');

        return $max === null ? 0 : $max + 1;
    }
}
