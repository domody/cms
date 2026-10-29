<?php

namespace App\Livewire;

use App\Livewire\Concerns\Toasts;
use App\Models\ActivityLog;
use App\Models\CollectionDefinition;
use App\Models\CollectionEntry;
use App\Models\Scopes\CurrentSite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The entries of one collection: drag to reorder (saved straight away) and
 * delete. Creating and editing happen on their own pages.
 */
class CollectionEntryList extends Component
{
    use Toasts;

    #[Locked]
    public int $definitionId;

    public function mount(CollectionDefinition $definition): void
    {
        $this->definitionId = $definition->id;
    }

    #[Computed]
    public function definition(): CollectionDefinition
    {
        return CollectionDefinition::findOrFail($this->definitionId);
    }

    /**
     * @return Collection<int, CollectionEntry>
     */
    #[Computed]
    public function entries(): Collection
    {
        return $this->definition->entries()->get();
    }

    /**
     * Drag-and-drop reorder (wire:sort): move entry $id to zero-based $position.
     */
    public function sortEntry(int|string $id, int $position): void
    {
        $ids = $this->entries->pluck('id')->all();
        $from = array_search((int) $id, $ids, true);

        if ($from === false) {
            return;
        }

        $moved = array_splice($ids, $from, 1);
        array_splice($ids, max(0, min($position, count($ids))), 0, $moved);

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $entryId) {
                CollectionEntry::whereKey($entryId)->update(['position' => $index]);
            }
        });

        unset($this->entries);

        ActivityLog::write('collection', $this->definition->id, $this->definition->label, 'reordered');

        $this->revalidateFrontend();
    }

    public function delete(int $id): void
    {
        abort_unless($this->canDelete, 403);

        $entry = $this->entries->firstWhere('id', $id);

        if ($entry === null) {
            return;
        }

        $entry->delete();
        unset($this->entries);

        $this->revalidateFrontend();

        $this->toast('success', 'Entry deleted.');
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->hasSiteAccess(app(CurrentSite::class)->get(), 'siteManage');
    }

    private function revalidateFrontend(): void
    {
        app(CurrentSite::class)->get()?->revalidateFrontend(CollectionDefinition::revalidationTags($this->definition->key));
    }

    public function render(): View
    {
        return view('livewire.collection-entry-list');
    }
}
