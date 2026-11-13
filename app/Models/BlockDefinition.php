<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasContentFields;
use Database\Factories\BlockDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A block type declared by the frontend code (e.g. "hero") and its
 * editable fields. Synced from the code's manifest, never created by users.
 */
#[Fillable(['site_id', 'key', 'label', 'version', 'schema'])]
class BlockDefinition extends Model
{
    /** @use HasFactory<BlockDefinitionFactory> */
    use BelongsToSite, HasContentFields, HasFactory;

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
     * Blocks in the shape handed to frontends, {type, content}. Image fields
     * are resolved from media ids to {url, alt, width, height}, and
     * `collection` fields (which store a collection key, or are locked to the
     * one the field declares) are filled with that collection's published
     * entries, or an empty list if none is chosen.
     *
     * @param  Collection<int, PageBlock>  $blocks
     * @return Collection<int, array{type: string, content: array<string, mixed>}>
     */
    public static function resolveBlocks(Collection $blocks): Collection
    {
        $definitions = static::all()->keyBy('key');

        // The manifest is the source of truth: blocks of a type it no longer
        // declares are not served, and neither are stored keys it dropped.
        $blocks = $blocks->filter(fn (PageBlock $block) => $definitions->has($block->block_key))->values();

        $items = $blocks->map(fn (PageBlock $block): array => [
            'content' => $definitions->get($block->block_key)->declaredContent($block->content ?? []),
            'images' => $definitions->get($block->block_key)->imageFieldNames(),
        ])->all();

        $resolved = Media::resolveImages($items);
        $entries = [];

        return $blocks->map(function (PageBlock $block, int $index) use ($definitions, $resolved, &$entries): array {
            $content = $resolved[$index];

            foreach ($definitions->get($block->block_key)->fields() as $field) {
                if ($field['type'] === 'collection') {
                    $key = $field['collection'] ?? $content[$field['name']] ?? null;
                    $list = is_string($key) && $key !== '' ? $entries[$key] ??= CollectionEntry::publishedPayloads($key) : [];
                    $content[$field['name']] = $field['limit'] === null ? $list : array_slice($list, 0, $field['limit']);
                }
            }

            return ['type' => $block->block_key, 'content' => $content];
        })->values();
    }
}
