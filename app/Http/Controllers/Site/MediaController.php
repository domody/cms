<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DuplicateMediaException;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\MediaUsage;
use App\Models\Scopes\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MediaController extends Controller
{
    private const PER_PAGE = 24;

    /**
     * One folder of the library (the root by default), or, with a search,
     * every folder. Each image shows where it is used.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'folder' => ['nullable', 'integer'],
            'media' => ['nullable', 'integer'],
            'subfolders' => ['nullable', 'boolean'],
        ]);

        $search = trim($filters['q'] ?? '');
        $subfolders = (bool) ($filters['subfolders'] ?? false);
        $folder = isset($filters['folder']) ? MediaFolder::find($filters['folder']) : null;
        $folder?->loadCount('media');
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';

        $media = Media::query()
            ->with('usages')
            ->when(
                $search !== '',
                fn ($query) => $query->where(fn ($match) => $match
                    ->whereRaw("lower(original_name) like ? escape '!'", [$like])
                    ->orWhereRaw("lower(alt) like ? escape '!'", [$like])),
            )
            // Subfolders of the library root means every image.
            ->when(
                $subfolders,
                fn ($query) => $folder ? $query->whereIn('folder_id', $folder->subtreeIds()) : $query,
                fn ($query) => $query->where('folder_id', $folder?->id),
            )
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $limit = app(CurrentSite::class)->get()->storageLimit();
        $used = Media::storageUsed();

        $selected = isset($filters['media']) ? Media::with('usages')->find($filters['media']) : null;
        $usageLinks = MediaUsage::describe($media->getCollection()->flatMap->usages->merge($selected?->usages ?? []));

        return view('media.index', [
            'media' => $media,
            'selected' => $selected,
            'selectedUsages' => ($selected?->usages ?? collect())->map(fn ($usage) => $usageLinks->get($usage->id))->filter()->values(),
            'folder' => $folder,
            'trail' => $folder?->trail() ?? collect(),
            'folders' => $search === '' ? MediaFolder::where('parent_id', $folder?->id)->withCount('media')->orderBy('name')->get() : collect(),
            'folderTree' => MediaFolder::tree(),
            'libraryCount' => Media::count(),
            'folderPaths' => MediaFolder::paths(),
            'search' => $search,
            'subfolders' => $subfolders,
            'maxMegabytes' => Media::MAX_KILOBYTES / 1024,
            'extensions' => Media::EXTENSIONS,
            'storageUsed' => $used,
            'storageLimit' => $limit,
            'canDelete' => auth()->user()->hasSiteAccess(app(CurrentSite::class)->get(), 'siteManage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => Media::fileRules(),
            'alt' => ['required', 'string', 'max:255'],
            'folder_id' => ['nullable', 'integer', $this->folderExists()],
        ]);

        try {
            Media::storeUpload($data['file'], $data['alt'], $data['folder_id'] ?? null);
        } catch (DuplicateMediaException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        toast('success', 'Image uploaded.');

        return redirect()->route('media.index', ['folder' => $data['folder_id'] ?? null]);
    }

    /**
     * Alt text and, when sent, the folder the image sits in.
     */
    public function update(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate([
            'alt' => ['required', 'string', 'max:255'],
            'folder_id' => ['nullable', 'integer', $this->folderExists()],
        ]);

        $media->alt = $data['alt'];

        if ($request->exists('folder_id')) {
            $media->folder_id = $data['folder_id'] ?? null;
        }

        $media->save();

        $this->revalidateFrontend();

        toast('success', 'Image saved.');

        return back();
    }

    /**
     * Swap the file, keeping the image's id so every page using it updates.
     */
    public function replace(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate(['file' => Media::fileRules()]);

        try {
            $media->replaceFile($data['file']);
        } catch (DuplicateMediaException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        ActivityLog::record($media, 'replaced');

        $this->revalidateFrontend();

        toast('success', 'File replaced.');

        return back();
    }

    /**
     * An image in use is only deleted when `force` is sent (the confirmation
     * names where it is used); the fields that held it then show no image.
     */
    public function destroy(Request $request, Media $media): RedirectResponse
    {
        if (! $request->boolean('force') && $media->usages()->exists()) {
            toast('error', 'This image is in use. Open its "Used in" list, or confirm the deletion.', 'Not deleted');

            return back();
        }

        $media->delete();

        $this->revalidateFrontend();

        toast('success', 'Image deleted.');

        return back();
    }

    public function bulkMove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
            'folder_id' => ['nullable', 'integer', $this->folderExists()],
        ]);

        $moved = Media::whereKey($data['ids'])->update(['folder_id' => $data['folder_id'] ?? null]);

        toast('success', "{$moved} ".($moved === 1 ? 'image' : 'images').' moved.');

        return back();
    }

    /**
     * Deletes the unused images among the selection; images in use are kept
     * (delete those one at a time, where the confirmation shows where).
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $deleted = 0;
        $kept = 0;

        foreach (Media::whereKey($data['ids'])->withCount('usages')->get() as $media) {
            if ($media->usages_count > 0) {
                $kept++;

                continue;
            }

            $media->delete();
            $deleted++;
        }

        $this->revalidateFrontend();

        toast($deleted > 0 ? 'success' : 'warning', "{$deleted} deleted".($kept > 0 ? ", {$kept} kept because they are in use." : '.'));

        return back();
    }

    /**
     * The folder must belong to the current site: the exists rule ignores
     * SiteScope, so the site is constrained explicitly.
     */
    private function folderExists(): mixed
    {
        return Rule::exists('media_folders', 'id')->where('site_id', app(CurrentSite::class)->id());
    }

    /**
     * An image can appear in any page or entry, so everything is revalidated.
     */
    private function revalidateFrontend(): void
    {
        $site = app(CurrentSite::class)->get();

        $site->revalidateFrontend($site->allContentTags());
    }
}
