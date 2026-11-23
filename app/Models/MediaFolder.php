<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A folder of the site's media library. Folders nest up to MAX_DEPTH deep;
 * deleting one moves what it holds up to its parent.
 */
#[Fillable(['site_id', 'parent_id', 'name'])]
class MediaFolder extends Model
{
    use BelongsToSite, LogsActivity;

    const MAX_DEPTH = 5;

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MediaFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    /**
     * Every folder of the site as "Parent / Child", keyed by id, sorted by
     * that path. For selects and breadcrumbs.
     *
     * @return Collection<int, string>
     */
    public static function paths(): Collection
    {
        $all = self::all()->keyBy('id');

        return $all
            ->map(function (self $folder) use ($all): string {
                $names = [];

                for ($node = $folder; $node !== null && count($names) <= self::MAX_DEPTH; $node = $all->get($node->parent_id)) {
                    array_unshift($names, $node->name);
                }

                return implode(' / ', $names);
            })
            ->sort(SORT_NATURAL | SORT_FLAG_CASE);
    }

    /**
     * The top-level folders, each with `children` loaded all the way down and
     * a `total_media` count of the images in it and in everything below it.
     *
     * @return Collection<int, MediaFolder>
     */
    public static function tree(): Collection
    {
        $byParent = self::withCount('media')->orderBy('name')->get()->groupBy(fn (self $folder) => $folder->parent_id ?? 0);

        $build = function (int $parentId, int $depth) use (&$build, $byParent): Collection {
            return ($byParent->get($parentId) ?? collect())->each(function (self $folder) use (&$build, $depth): void {
                $children = $depth < self::MAX_DEPTH ? $build($folder->id, $depth + 1) : collect();

                $folder->setRelation('children', $children);
                $folder->total_media = $folder->media_count + $children->sum('total_media');
            });
        };

        return $build(0, 1);
    }

    /**
     * The ids of a folder and everything below it.
     *
     * @return array<int, int>
     */
    public function subtreeIds(): array
    {
        $byParent = self::all()->groupBy('parent_id');
        $ids = [$this->id];

        for ($i = 0; $i < count($ids); $i++) {
            array_push($ids, ...$byParent->get($ids[$i], collect())->pluck('id')->all());
        }

        return $ids;
    }

    /**
     * This folder and its ancestors, root first.
     *
     * @return Collection<int, MediaFolder>
     */
    public function trail(): Collection
    {
        $all = self::all()->keyBy('id');
        $trail = collect();

        for ($node = $all->get($this->id); $node !== null && $trail->count() <= self::MAX_DEPTH; $node = $all->get($node->parent_id)) {
            $trail->prepend($node);
        }

        return $trail;
    }
}
