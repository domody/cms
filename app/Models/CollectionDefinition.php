<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasContentFields;
use Database\Factories\CollectionDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A repeatable content type declared by the frontend code (e.g. "team") and
 * the fields each entry has. Synced from the code's manifest, never created
 * by users; the entries themselves are edited in the CMS.
 */
#[Fillable(['site_id', 'key', 'label', 'version', 'schema'])]
class CollectionDefinition extends Model
{
    /** @use HasFactory<CollectionDefinitionFactory> */
    use BelongsToSite, HasContentFields, HasFactory;

    /**
     * Entry keys a field name must not use, since the API puts them on every entry.
     *
     * @var array<int, string>
     */
    const RESERVED_FIELD_NAMES = ['slug', 'title', 'collection'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
            'version' => 'integer',
            'schema' => 'array',
        ];
    }

    /**
     * Cache tags to revalidate when a collection's entries change: its own,
     * and those of every page with a block that embeds it (the API inlines the
     * entries into the page). Call inside the site.
     *
     * @return array<int, string>
     */
    public static function revalidationTags(string $key): array
    {
        $collectionFields = BlockDefinition::all()
            ->mapWithKeys(fn (BlockDefinition $block) => [
                $block->key => collect($block->fields())->where('type', 'collection')
                    ->map(fn (array $field) => ['name' => $field['name'], 'collection' => $field['collection']])
                    ->values()->all(),
            ])
            ->filter();

        $pageIds = PageBlock::whereIn('block_key', $collectionFields->keys())->get()
            ->filter(fn (PageBlock $block) => collect($collectionFields[$block->block_key])
                ->contains(fn (array $field) => ($field['collection'] ?? $block->content[$field['name']] ?? null) === $key))
            ->pluck('page_id');

        $pageSlugs = Page::whereIn('id', $pageIds)->pluck('slug');

        return ["collection:$key", ...$pageSlugs->map(fn (string $slug) => "page:$slug")->all()];
    }

    /**
     * @return HasMany<CollectionEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CollectionEntry::class, 'collection_key', 'key')->orderBy('position');
    }
}
