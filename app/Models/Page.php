<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'slug', 'title', 'status', 'published_at'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PageBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('position');
    }
}
