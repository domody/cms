<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PageBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One block on a page. `block_key` points at a BlockDefinition; `content`
 * holds the user-editable values keyed by field name.
 */
#[Fillable(['tenant_id', 'page_id', 'block_key', 'position', 'is_visible', 'content', 'schema_version'])]
class PageBlock extends Model
{
    /** @use HasFactory<PageBlockFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'position' => 'integer',
            'is_visible' => 'boolean',
            'content' => 'array',
            'schema_version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
