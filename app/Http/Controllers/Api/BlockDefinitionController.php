<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncBlockDefinitionsRequest;
use App\Models\BlockDefinition;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BlockDefinitionController extends Controller
{
    /**
     * Sync the frontend code's manifest into the CMS.
     *
     * Definitions are upserted by key. Declared pages are created if
     * missing; on existing pages only blocks whose type isn't present yet
     * are appended, so user-edited content, order and visibility are never
     * overwritten. Nothing is ever deleted.
     */
    public function sync(SyncBlockDefinitionsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = DB::transaction(function () use ($data) {
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
                    ['title' => $declared['title'], 'status' => 'published', 'published_at' => now()],
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

            return ['definitions' => count($data['blocks']), 'blocks_created' => $createdBlocks];
        });

        return response()->json($result);
    }
}
