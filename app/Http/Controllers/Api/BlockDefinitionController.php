<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BlockDefinition;
use App\Models\CollectionDefinition;
use App\Models\CollectionEntry;
use App\Models\MediaUsage;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageStatus;
use App\Models\Scopes\CurrentSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlockDefinitionController extends Controller
{
    /**
     * Field types a manifest may declare. `collection` is only valid on blocks;
     * `repeater` rows hold the simple types only.
     *
     * @var array<int, string>
     */
    private const FIELD_TYPES = ['text', 'textarea', 'richtext', 'image', 'link', 'boolean', 'select', 'repeater'];

    /**
     * Sync the frontend code's manifest into the CMS.
     *
     * Block and collection definitions are upserted by key. Declared pages
     * are created if missing; on existing pages only blocks whose type isn't
     * present yet are appended, so user-edited content, order and visibility
     * are never overwritten.
     *
     * The manifest is the source of truth for what the CMS shows: definitions
     * it no longer declares are removed (they only mirror the manifest), which
     * hides their page blocks and entries, and stored field values it dropped
     * stop being served or editable. That stored content is reported under
     * `removed` and only deleted when `prune` is true.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'prune' => ['sometimes', 'boolean'],
            'blocks' => ['present', 'array'],
            'blocks.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'blocks.*.label' => ['required', 'string', 'max:255'],
            'blocks.*.version' => ['sometimes', 'integer', 'min:1'],
            'blocks.*.fields' => ['required', 'array'],
            ...$this->fieldRules('blocks.*.fields', [...self::FIELD_TYPES, 'collection']),
            'blocks.*.fields.*.limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'blocks.*.fields.*.collection' => ['sometimes', 'nullable', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'collections' => ['sometimes', 'array'],
            'collections.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'collections.*.label' => ['required', 'string', 'max:255'],
            'collections.*.version' => ['sometimes', 'integer', 'min:1'],
            'collections.*.fields' => ['required', 'array'],
            ...$this->fieldRules('collections.*.fields', self::FIELD_TYPES),
            'pages' => ['sometimes', 'array'],
            'pages.*.slug' => ['required', 'string', 'max:255'],
            'pages.*.title' => ['required', 'string', 'max:255'],
            'pages.*.blocks' => ['required', 'array'],
            'pages.*.blocks.*.type' => ['required', 'string', 'max:64'],
            'pages.*.blocks.*.content' => ['sometimes', 'array'],
        ]);

        $declaredCollections = collect($data['collections'] ?? []);

        foreach ($declaredCollections as $index => $collection) {
            foreach ($collection['fields'] as $fieldIndex => $field) {
                if (in_array($field['name'], CollectionDefinition::RESERVED_FIELD_NAMES, true)) {
                    throw ValidationException::withMessages([
                        "collections.$index.fields.$fieldIndex.name" => "[{$field['name']}] is reserved: every entry already has it.",
                    ]);
                }
            }
        }

        // `collections` is optional: when it is left out, existing collections are untouched.
        $hasCollections = $request->has('collections');
        $knownCollections = $declaredCollections->pluck('key')
            ->when(! $hasCollections, fn ($keys) => $keys->merge(CollectionDefinition::pluck('key')))
            ->all();

        foreach ($data['blocks'] as $index => $block) {
            foreach ($block['fields'] as $fieldIndex => $field) {
                if ($field['type'] === 'collection' && filled($field['collection'] ?? null) && ! in_array($field['collection'], $knownCollections, true)) {
                    throw ValidationException::withMessages([
                        "blocks.$index.fields.$fieldIndex.collection" => "Unknown collection [{$field['collection']}]: declare it under `collections`.",
                    ]);
                }
            }
        }

        $prune = (bool) ($data['prune'] ?? false);
        $removed = [];

        $result = DB::transaction(function () use ($data, $declaredCollections, $hasCollections, $prune, &$removed) {
            foreach ($data['blocks'] as $block) {
                BlockDefinition::updateOrCreate(
                    ['key' => $block['key']],
                    [
                        'label' => $block['label'],
                        'version' => $block['version'] ?? 1,
                        'schema' => $block['fields'],
                    ],
                );
            }

            foreach ($declaredCollections as $collection) {
                CollectionDefinition::updateOrCreate(
                    ['key' => $collection['key']],
                    [
                        'label' => $collection['label'],
                        'version' => $collection['version'] ?? 1,
                        'schema' => $collection['fields'],
                    ],
                );
            }

            BlockDefinition::whereNotIn('key', collect($data['blocks'])->pluck('key'))->delete();

            if ($hasCollections) {
                CollectionDefinition::whereNotIn('key', $declaredCollections->pluck('key'))->delete();
            }

            $knownKeys = BlockDefinition::pluck('key')->all();
            $createdBlocks = 0;

            foreach ($data['pages'] ?? [] as $index => $declared) {
                foreach ($declared['blocks'] as $blockIndex => $block) {
                    if (! in_array($block['type'], $knownKeys, true)) {
                        throw ValidationException::withMessages([
                            "pages.$index.blocks.$blockIndex.type" => "Unknown block type [{$block['type']}].",
                        ]);
                    }
                }

                $page = Page::firstOrCreate(
                    ['slug' => $declared['slug']],
                    ['title' => $declared['title'], 'status' => PageStatus::PUBLISHED, 'published_at' => now()],
                );

                $present = $page->blocks()->pluck('block_key')->all();
                $nextPosition = $present === [] ? 0 : $page->blocks()->max('position') + 1;

                foreach ($declared['blocks'] as $block) {
                    if (in_array($block['type'], $present, true)) {
                        continue;
                    }

                    $page->blocks()->create([
                        'block_key' => $block['type'],
                        'position' => $nextPosition++,
                        'content' => $block['content'] ?? [],
                    ]);
                    $present[] = $block['type'];
                    $createdBlocks++;
                }
            }

            $removed = $this->orphans();

            if ($prune && $removed !== []) {
                $this->prune();
                ActivityLog::write('manifest', null, 'Manifest sync', 'pruned', collect($removed)
                    ->mapWithKeys(fn (string $description, int $index) => ["removed.$index" => ['old' => $description, 'new' => null]])
                    ->all());
            }

            return [
                'definitions' => count($data['blocks']),
                'collections' => $declaredCollections->count(),
                'blocks_created' => $createdBlocks,
            ];
        });

        $result += ['removed' => $removed, 'pruned' => $prune && $removed !== []];

        if ($result['blocks_created'] > 0 || $result['collections'] > 0 || $removed !== []) {
            $site = app(CurrentSite::class)->get();

            $site->revalidateFrontend($site->allContentTags());
        }

        return response()->json($result);
    }

    /**
     * What is still stored but no longer declared by the manifest, as
     * readable lines: page blocks of dropped block types, entries of dropped
     * collections, and field values under keys a definition dropped.
     *
     * @return array<int, string>
     */
    private function orphans(): array
    {
        $blocks = BlockDefinition::all()->keyBy('key');
        $collections = CollectionDefinition::all()->keyBy('key');
        $lines = [];

        $blockCounts = PageBlock::whereNotIn('block_key', $blocks->keys())
            ->selectRaw('block_key as k, count(*) as total')->groupBy('block_key')->pluck('total', 'k');
        $entryCounts = CollectionEntry::whereNotIn('collection_key', $collections->keys())
            ->selectRaw('collection_key as k, count(*) as total')->groupBy('collection_key')->pluck('total', 'k');

        foreach ($blockCounts as $key => $total) {
            $lines[] = "block type [$key]: $total page block(s)";
        }

        foreach ($entryCounts as $key => $total) {
            $lines[] = "collection [$key]: $total entry(ies)";
        }

        $stray = [];

        foreach (PageBlock::whereIn('block_key', $blocks->keys())->get() as $block) {
            foreach ($blocks[$block->block_key]->undeclaredPaths($block->content ?? []) as $path) {
                $stray["block {$block->block_key}.$path"] = ($stray["block {$block->block_key}.$path"] ?? 0) + 1;
            }
        }

        foreach (CollectionEntry::whereIn('collection_key', $collections->keys())->get() as $entry) {
            foreach ($collections[$entry->collection_key]->undeclaredPaths($entry->data ?? []) as $path) {
                $stray["collection {$entry->collection_key}.$path"] = ($stray["collection {$entry->collection_key}.$path"] ?? 0) + 1;
            }
        }

        foreach ($stray as $path => $total) {
            $lines[] = "field [$path]: stored on $total row(s)";
        }

        return $lines;
    }

    /**
     * Delete what orphans() reports. Done without model events, so a prune is
     * one activity entry (written by the caller) and not one per row; the
     * media usage of what changed is rebuilt by hand.
     */
    private function prune(): void
    {
        $siteId = app(CurrentSite::class)->id();
        $blocks = BlockDefinition::all()->keyBy('key');
        $collections = CollectionDefinition::all()->keyBy('key');
        $pageIds = collect();
        $deletedEntryIds = collect();
        $changedEntries = collect();

        Model::withoutEvents(function () use ($blocks, $collections, &$pageIds, &$deletedEntryIds, &$changedEntries): void {
            $orphanBlocks = PageBlock::whereNotIn('block_key', $blocks->keys());
            $pageIds = $pageIds->merge($orphanBlocks->pluck('page_id'));
            $orphanBlocks->delete();

            $orphanEntries = CollectionEntry::whereNotIn('collection_key', $collections->keys());
            $deletedEntryIds = $orphanEntries->pluck('id');
            $orphanEntries->delete();

            foreach (PageBlock::whereIn('block_key', $blocks->keys())->get() as $block) {
                $content = $blocks[$block->block_key]->declaredContent($block->content ?? []);

                if ($content != ($block->content ?? [])) {
                    $block->update(['content' => $content]);
                    $pageIds->push($block->page_id);
                }
            }

            foreach (CollectionEntry::whereIn('collection_key', $collections->keys())->get() as $entry) {
                $data = $collections[$entry->collection_key]->declaredContent($entry->data ?? []);

                if ($data != ($entry->data ?? [])) {
                    $entry->update(['data' => $data]);
                    $changedEntries->push($entry);
                }
            }
        });

        Page::whereIn('id', $pageIds->unique())->get()->each->syncMediaUsage();
        $changedEntries->each->syncMediaUsage();
        $deletedEntryIds->each(fn (int $id) => MediaUsage::syncSubject('collection_entry', $id, $siteId, []));
    }

    /**
     * Field types allowed inside a repeater's `fields`.
     *
     * @var array<int, string>
     */
    private const REPEATER_FIELD_TYPES = ['text', 'textarea', 'richtext', 'image', 'link', 'boolean', 'select'];

    /**
     * Rules for a list of field declarations at $prefix (e.g. "blocks.*.fields").
     *
     * @param  array<int, string>  $types
     * @return array<string, array<int, mixed>>
     */
    private function fieldRules(string $prefix, array $types): array
    {
        return [
            "$prefix.*.name" => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
            "$prefix.*.type" => ['required', 'string', Rule::in($types)],
            "$prefix.*.label" => ['sometimes', 'nullable', 'string', 'max:255'],
            "$prefix.*.required" => ['sometimes', 'boolean'],
            "$prefix.*.options" => ['sometimes', 'array'],
            "$prefix.*.options.*" => ['string', 'max:255'],
            // After the rules above: the validated array is built in rule
            // order, so rows with `fields` would otherwise come first and the
            // list would be stored as an object with scrambled keys.
            ...($types === self::REPEATER_FIELD_TYPES ? [] : [
                "$prefix.*.fields" => ["required_if:$prefix.*.type,repeater", 'array', 'min:1'],
                ...$this->fieldRules("$prefix.*.fields", self::REPEATER_FIELD_TYPES),
            ]),
        ];
    }
}
