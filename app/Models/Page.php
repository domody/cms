<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\LogsActivity;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['site_id', 'slug', 'title', 'status', 'published_at'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use BelongsToSite, HasFactory, LogsActivity;

    /**
     * @return array<int, string>
     */
    public function activityIgnored(): array
    {
        return ['published_at'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Record which images the page's blocks (visible or not) hold. Runs
     * outside a site, so every lookup names the page's site.
     */
    public function syncMediaUsage(): void
    {
        $definitions = BlockDefinition::withoutGlobalScopes()->where('site_id', $this->site_id)->get()->keyBy('key');

        $ids = PageBlock::withoutGlobalScopes()->where('page_id', $this->id)->get()
            ->flatMap(fn (PageBlock $block) => array_map(
                fn (string $field) => Media::idFrom($block->content[$field] ?? null),
                $definitions->get($block->block_key)?->imageFieldNames() ?? [],
            ))
            ->filter()->unique()->values()->all();

        MediaUsage::syncSubject('page', $this->id, $this->site_id, $ids);
    }

    /**
     * @return HasMany<PageBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('position');
    }
}
