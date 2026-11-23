<?php

namespace App\Http\Resources;

use App\Models\BlockDefinition;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape consumed by the frontends: visible blocks only, in order,
 * as {type, content} so the frontend can map type -> component.
 *
 * @mixin Page
 */
class PageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'published_at' => $this->published_at,
            'blocks' => BlockDefinition::resolveBlocks(
                $this->blocks->where('is_visible', true)->values(),
            ),
        ];
    }
}
