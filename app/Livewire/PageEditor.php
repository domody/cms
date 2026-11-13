<?php

namespace App\Livewire;

use App\Livewire\Concerns\Toasts;
use App\Models\ActivityLog;
use App\Models\BlockDefinition;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageStatus;
use App\Models\Scopes\CurrentSite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Editor for a page: title/status, a "Page Structure" list of every block
 * (drag to reorder, show/hide, select), and a card with the selected block's
 * fields (generated from its definition). Nothing is persisted until save().
 */
class PageEditor extends Component
{
    use Toasts;

    #[Locked]
    public int $pageId;

    public string $title = '';

    public string $status = PageStatus::DRAFT;

    /**
     * Client-side working copy of the page's blocks. Only `id` is trusted
     * on save (re-checked against the tenant-scoped rows), never `block_key`.
     *
     * @var array<int, array{id: int, block_key: string, is_visible: bool, content: array<string, mixed>}>
     */
    public array $blocks = [];

    /**
     * Id of the block whose fields are shown in the edit card.
     */
    public ?int $selectedBlockId = null;

    public function mount(Page $page): void
    {
        $this->pageId = $page->id;

        $this->loadState();
        $this->selectedBlockId = $this->blocks[0]['id'] ?? null;
    }

    public function selectBlock(int $id): void
    {
        if (collect($this->blocks)->contains('id', $id)) {
            $this->selectedBlockId = $id;
        }
    }

    /**
     * @return Collection<string, BlockDefinition>
     */
    #[Computed]
    public function definitions(): Collection
    {
        return BlockDefinition::all()->keyBy('key');
    }

    /**
     * Drag-and-drop reorder (wire:sort): move block $id to zero-based $position.
     */
    public function sortBlock(int|string $id, int $position): void
    {
        $from = collect($this->blocks)->search(fn (array $block) => $block['id'] === (int) $id);

        if ($from === false) {
            return;
        }

        $moved = array_splice($this->blocks, $from, 1);
        array_splice($this->blocks, max(0, min($position, count($this->blocks))), 0, $moved);

        $this->dispatch('editor-dirty');
    }

    public function toggleVisibility(int $index): void
    {
        if (isset($this->blocks[$index])) {
            $this->blocks[$index]['is_visible'] = ! $this->blocks[$index]['is_visible'];
            $this->dispatch('editor-dirty');
        }
    }

    public function save(): void
    {
        $page = Page::findOrFail($this->pageId);
        $rows = $page->blocks()->get()->keyBy('id');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(PageStatus::all())],
        ];
        $attributes = [];

        foreach ($this->blocks as $index => $state) {
            $definition = $this->definitionFor($rows->get($state['id']));

            if ($definition === null) {
                continue;
            }

            $rules = array_merge($rules, $definition->contentRules("blocks.$index.content"));

            foreach ($definition->editableFields() as $field) {
                $attributes["blocks.$index.content.{$field['name']}"] = "{$definition->label} → {$field['label']}";
                $attributes["blocks.$index.content.{$field['name']}.label"] = "{$definition->label} → {$field['label']} label";
                $attributes["blocks.$index.content.{$field['name']}.url"] = "{$definition->label} → {$field['label']} URL";
            }
        }

        try {
            $this->validate($rules, [], $attributes);
        } catch (ValidationException $e) {
            foreach ($this->blocks as $index => $state) {
                if (collect($e->validator->errors()->keys())->contains(fn ($key) => str_starts_with($key, "blocks.$index."))) {
                    $this->selectedBlockId = $state['id'];

                    break;
                }
            }

            $this->toast('error', 'Fix the highlighted fields, then save again.', 'Page not saved');

            throw $e;
        }

        $previousOrder = $rows->filter(fn (PageBlock $row) => $this->definitionFor($row) !== null)->sortBy('position')->keys()->values()->all();

        DB::transaction(function () use ($page, $rows): void {
            $page->update([
                'title' => $this->title,
                'status' => $this->status,
                'published_at' => $this->status === PageStatus::PUBLISHED ? ($page->published_at ?? now()) : $page->published_at,
            ]);

            $position = 0;

            foreach ($this->blocks as $state) {
                $row = $rows->get($state['id']);

                if ($row === null) {
                    continue;
                }

                $definition = $this->definitionFor($row);
                $content = $definition?->normalizedContent($state['content']) ?? $row->content ?? [];

                $row->update([
                    'position' => $position++,
                    'is_visible' => (bool) $state['is_visible'],
                    'content' => $content,
                    'schema_version' => $definition?->version ?? $row->schema_version,
                ]);
            }
        });

        if ($previousOrder !== collect($this->blocks)->pluck('id')->intersect($previousOrder)->values()->all()) {
            ActivityLog::record($page, 'reordered');
        }

        $this->loadState();

        app(CurrentSite::class)->get()?->revalidateFrontend(["page:{$page->slug}"]);

        $this->dispatch('editor-saved');
        $this->toast('success', 'Page saved.');
    }

    public function render(): View
    {
        return view('livewire.page-editor');
    }

    private function loadState(): void
    {
        $page = Page::with('blocks')->findOrFail($this->pageId);

        $this->title = $page->title;
        $this->status = $page->status;
        // Blocks whose type the manifest no longer declares are hidden.
        $this->blocks = $page->blocks
            ->filter(fn (PageBlock $block) => $this->definitionFor($block) !== null)
            ->map(fn (PageBlock $block) => [
                'id' => $block->id,
                'block_key' => $block->block_key,
                'is_visible' => $block->is_visible,
                'content' => $this->definitionFor($block)->contentWithBlanks($block->content ?? []),
            ])
            ->values()
            ->all();
    }

    private function definitionFor(?PageBlock $block): ?BlockDefinition
    {
        return $block === null ? null : $this->definitions->get($block->block_key);
    }
}
