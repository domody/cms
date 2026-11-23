<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\LogsActivity;
use Database\Factories\PageBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One block on a page. `block_key` points at a BlockDefinition; `content`
 * holds the user-editable values keyed by field name.
 */
#[Fillable(['site_id', 'page_id', 'block_key', 'position', 'is_visible', 'content', 'schema_version'])]
class PageBlock extends Model
{
    /** @use HasFactory<PageBlockFactory> */
    use BelongsToSite, HasFactory, LogsActivity;

    protected static function booted(): void
    {
        // The images a page uses are the images in all of its blocks.
        static::created(fn (self $block) => $block->syncMediaUsage());
        static::updated(fn (self $block) => $block->wasChanged('content') && $block->syncMediaUsage());
        static::deleted(fn (self $block) => $block->syncMediaUsage());
    }

    private function syncMediaUsage(): void
    {
        Page::withoutGlobalScopes()->find($this->page_id)?->syncMediaUsage();
    }

    /**
     * Reordering is logged once per save by the editor, not once per block.
     *
     * @return array<int, string>
     */
    public function activityIgnored(): array
    {
        return ['position', 'schema_version'];
    }

    public function activityParent(): ?Model
    {
        // Looked up by the block's own page_id, so it needs no current site.
        return $this->page()->withoutGlobalScopes()->first();
    }

    public function activityName(): string
    {
        return $this->block_key;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
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
