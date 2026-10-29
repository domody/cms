<?php

namespace App\Livewire;

use App\Livewire\Concerns\Toasts;
use App\Models\DuplicateMediaException;
use App\Models\Media;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Picks (or uploads) one image from the current site's media library.
 * Bind it with wire:model: the bound value is the media id, or null.
 */
class MediaPicker extends Component
{
    use Toasts, WithFileUploads;

    #[Modelable]
    public ?int $mediaId = null;

    public bool $open = false;

    public $file = null;

    public string $alt = '';

    /**
     * What the library shows: '' = every image, 'root' = only the top level,
     * or a folder id.
     */
    public string $folder = '';

    public string $search = '';

    /**
     * How many images are shown; "Load more" adds a page.
     */
    public int $limit = self::PAGE_SIZE;

    private const PAGE_SIZE = 24;

    #[Computed]
    public function selected(): ?Media
    {
        return $this->mediaId === null ? null : Media::find($this->mediaId);
    }

    /**
     * The newest images matching the search and folder: one more than
     * $limit, so the view can tell whether there is more to load.
     *
     * @return Collection<int, Media>
     */
    #[Computed]
    public function library(): Collection
    {
        $search = trim($this->search);
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';

        return Media::query()
            ->when($this->folder === 'root', fn ($query) => $query->whereNull('folder_id'))
            ->when(ctype_digit($this->folder), fn ($query) => $query->where('folder_id', (int) $this->folder))
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->whereRaw("lower(original_name) like ? escape '!'", [$like])
                ->orWhereRaw("lower(alt) like ? escape '!'", [$like])))
            ->latest('id')
            ->limit($this->limit + 1)
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->limit = self::PAGE_SIZE;
    }

    public function updatedFolder(): void
    {
        $this->limit = self::PAGE_SIZE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    public function openLibrary(): void
    {
        $this->open = true;
    }

    public function closeLibrary(): void
    {
        $this->reset('open', 'file', 'alt', 'search', 'folder', 'limit');
        $this->resetErrorBag();
    }

    public function choose(int $id): void
    {
        if (Media::find($id) === null) {
            return;
        }

        $this->mediaId = $id;
        $this->dispatch('editor-dirty');
        $this->closeLibrary();
    }

    public function clear(): void
    {
        $this->mediaId = null;
        $this->dispatch('editor-dirty');
    }

    public function saveUpload(): void
    {
        $this->validate([
            'file' => Media::fileRules(),
            'alt' => ['required', 'string', 'max:255'],
        ]);

        try {
            $media = Media::storeUpload($this->file, $this->alt, ctype_digit($this->folder) ? (int) $this->folder : null);
        } catch (DuplicateMediaException $e) {
            // Already in the library: use that one rather than storing a copy.
            $media = $e->existing;
            $this->toast('info', "\"{$media->original_name}\" is already in the library, so it was used.");
        }

        $this->mediaId = $media->id;
        $this->dispatch('editor-dirty');
        $this->closeLibrary();
        unset($this->library);

        $this->toast('success', 'Image ready.');
    }

    public function render(): View
    {
        return view('livewire.media-picker');
    }
}
