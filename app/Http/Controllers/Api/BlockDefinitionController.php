<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockDefinition;
use App\Models\CollectionDefinition;
use App\Models\Page;
use App\Models\PageStatus;
use App\Models\Scopes\CurrentSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlockDefinitionController extends Controller
{
    /**
     * Field types a manifest may declare. `collection` is only valid on blocks.
     *
     * @var array<int, string>
     */
    private const FIELD_TYPES = ['text', 'textarea', 'richtext', 'image', 'link', 'boolean', 'select'];

    /**
     * Sync the frontend code's manifest into the CMS.
     *
     * Block and collection definitions are upserted by key. Declared pages
     * are created if missing; on existing pages only blocks whose type isn't
     * present yet are appended, so user-edited content, order and visibility
     * are never overwritten. Nothing is ever deleted.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'blocks' => ['present', 'array'],
            'blocks.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'blocks.*.label' => ['required', 'string', 'max:255'],
            'blocks.*.version' => ['sometimes', 'integer', 'min:1'],
            'blocks.*.fields' => ['required', 'array'],
            ...$this->fieldRules('blocks.*.fields', [...self::FIELD_TYPES, 'collection']),
            'blocks.*.fields.*.limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
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

        $result = DB::transaction(function () use ($data, $declaredCollections) {
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

            return [
                'definitions' => count($data['blocks']),
                'collections' => $declaredCollections->count(),
                'blocks_created' => $createdBlocks,
            ];
        });

        if ($result['blocks_created'] > 0 || $result['collections'] > 0) {
            $site = app(CurrentSite::class)->get();

            $site->revalidateFrontend($site->allContentTags());
        }

        return response()->json($result);
    }

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
        ];
    }
}
