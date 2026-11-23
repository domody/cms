<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\Scopes\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MediaFolderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        return $this->processPost($request);
    }

    public function update(Request $request, MediaFolder $folder): RedirectResponse
    {
        return $this->processPost($request, $folder);
    }

    /**
     * Deleting a folder keeps what is in it: subfolders and images move up
     * to the folder's parent.
     */
    public function destroy(MediaFolder $folder): RedirectResponse
    {
        MediaFolder::where('parent_id', $folder->id)->update(['parent_id' => $folder->parent_id]);
        Media::where('folder_id', $folder->id)->update(['folder_id' => $folder->parent_id]);

        $folder->delete();

        toast('success', "Folder \"{$folder->name}\" deleted. Its contents moved up.");

        return redirect()->route('media.index', ['folder' => $folder->parent_id]);
    }

    /**
     * Create a folder (inside `parent_id`, or at the top) or rename one.
     */
    private function processPost(Request $request, ?MediaFolder $folder = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => $folder === null
                ? ['nullable', 'integer', Rule::exists('media_folders', 'id')->where('site_id', app(CurrentSite::class)->id())]
                : ['prohibited'],
        ]);

        $parentId = $folder === null ? ($data['parent_id'] ?? null) : $folder->parent_id;
        $name = trim($data['name']);

        if ($folder === null && $parentId !== null && MediaFolder::findOrFail($parentId)->trail()->count() >= MediaFolder::MAX_DEPTH) {
            throw ValidationException::withMessages(['name' => 'Folders can be nested at most '.MediaFolder::MAX_DEPTH.' deep.']);
        }

        $taken = MediaFolder::where('parent_id', $parentId)
            ->where('name', $name)
            ->when($folder, fn ($query) => $query->whereKeyNot($folder->id))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['name' => 'A folder with this name already exists here.']);
        }

        if ($folder === null) {
            MediaFolder::create(['parent_id' => $parentId, 'name' => $name]);
        } else {
            $folder->update(['name' => $name]);
        }

        toast('success', 'Folder saved.');

        return redirect()->route('media.index', ['folder' => $folder?->id ?? $parentId]);
    }
}
