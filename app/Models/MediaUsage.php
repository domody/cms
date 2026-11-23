<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Where an image is used: one row per image and page (or collection entry)
 * holding it. Kept in step by the models that own the content (PageBlock,
 * CollectionEntry), and rebuilt in bulk by `cms:media-rebuild`.
 */
#[Fillable(['site_id', 'media_id', 'subject_type', 'subject_id'])]
class MediaUsage extends Model
{
    use BelongsToSite;

    const UPDATED_AT = null;

    /**
     * Make the images used by a subject exactly $mediaIds (ids not in the
     * site's library are ignored). Works outside a site, hence the explicit
     * site id.
     *
     * @param  array<int, int>  $mediaIds
     */
    public static function syncSubject(string $type, int $subjectId, int $siteId, array $mediaIds): void
    {
        $valid = $mediaIds === [] ? [] : Media::withoutGlobalScopes()
            ->where('site_id', $siteId)
            ->whereIn('id', $mediaIds)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($type, $subjectId, $siteId, $valid): void {
            self::withoutGlobalScopes()
                ->where(['site_id' => $siteId, 'subject_type' => $type, 'subject_id' => $subjectId])
                ->delete();

            if ($valid !== []) {
                self::withoutGlobalScopes()->insert(array_map(fn (int $mediaId) => [
                    'site_id' => $siteId,
                    'media_id' => $mediaId,
                    'subject_type' => $type,
                    'subject_id' => $subjectId,
                    'created_at' => now(),
                ], $valid));
            }
        });
    }

    /**
     * Labels and edit links for usage rows, for the current site, keyed by
     * usage id (rows whose page or entry is gone are left out).
     *
     * @param  Collection<int, MediaUsage>  $usages
     * @return Collection<int, array{label: string, url: string}>
     */
    public static function describe(Collection $usages): Collection
    {
        $pages = Page::whereIn('id', $usages->where('subject_type', 'page')->pluck('subject_id'))->get()->keyBy('id');
        $entries = CollectionEntry::whereIn('id', $usages->where('subject_type', 'collection_entry')->pluck('subject_id'))->get()->keyBy('id');

        return $usages->mapWithKeys(function (self $usage) use ($pages, $entries): array {
            if ($usage->subject_type === 'page' && $page = $pages->get($usage->subject_id)) {
                return [$usage->id => ['label' => "Page: {$page->title}", 'url' => route('pages.edit', $page)]];
            }

            if ($usage->subject_type === 'collection_entry' && $entry = $entries->get($usage->subject_id)) {
                return [$usage->id => [
                    'label' => "Entry: {$entry->title}",
                    'url' => route('collections.edit', ['collection' => $entry->collection_key, 'entry' => $entry->id]),
                ]];
            }

            return [];
        });
    }
}
