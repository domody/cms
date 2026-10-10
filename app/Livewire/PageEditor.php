<?php

namespace App\Livewire;

use App\Models\BlockDefinition;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Inline accordion editor for a page: title/status, then every block in
 * order with its fields (generated from the block's definition), plus
 * move up/down and show/hide. Nothing is persisted until save().
 */
class PageEditor extends Component
{
    #[Locked]
    public int $pageId;

    public string $title = '';

    public string $status = 'draft';

    /**
     * Client-side working copy of the page's blocks. Only `id` is trusted
     * on save (re-checked against the tenant-scoped rows), never `block_key`.
     *
     * @var array<int, array{id: int, block_key: string, is_visible: bool, content: array<string, mixed>}>
     */
    public array $blocks = [];

    public function mount(Page $page): void
    {
        $this->pageId = $page->id;

        $this->loadState();
    }

    /**
     * @return Collection<string, BlockDefinition>
     */
    #[Computed]
    public function definitions(): Collection
    {
        return BlockDefinition::all()->keyBy('key');
    }

    public function moveUp(int $index): void
    {
        $this->swap($index, $index - 1);
    }

    public function moveDown(int $index): void
    {
        $this->swap($index, $index + 1);
    }

    public function toggleVisibility(int $index): void
    {
        if (isset($this->blocks[$index])) {
            $this->blocks[$index]['is_visible'] = ! $this->blocks[$index]['is_visible'];
        }
    }

    public function save(): void
    {
        $page = Page::findOrFail($this->pageId);
        $rows = $page->blocks()->get()->keyBy('id');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published'],
        ];
        $attributes = [];

        foreach ($this->blocks as $index => $state) {
            $definition = $this->definitionFor($rows->get($state['id']));

            if ($definition === null) {
                continue;
            }

            $rules = array_merge($rules, $definition->contentRules("blocks.$index.content"));

            foreach ($definition->fields() as $field) {
                $attributes["blocks.$index.content.{$field['name']}"] = "{$definition->label} → {$field['label']}";
                $attributes["blocks.$index.content.{$field['name']}.label"] = "{$definition->label} → {$field['label']} label";
                $attributes["blocks.$index.content.{$field['name']}.url"] = "{$definition->label} → {$field['label']} URL";
            }
        }

        $this->validate($rules, [], $attributes);

        DB::transaction(function () use ($page, $rows): void {
            $page->update([
                'title' => $this->title,
                'status' => $this->status,
                'published_at' => $this->status === 'published' ? ($page->published_at ?? now()) : $page->published_at,
            ]);

            $position = 0;

            foreach ($this->blocks as $state) {
                $row = $rows->get($state['id']);

                if ($row === null) {
                    continue;
                }

                $definition = $this->definitionFor($row);
                $content = $row->content ?? [];

                foreach ($definition?->fields() ?? [] as $field) {
                    $content[$field['name']] = $this->normalize(
                        $field['type'],
                        $state['content'][$field['name']] ?? BlockDefinition::blankValue($field['type']),
                    );
                }

                $row->update([
                    'position' => $position++,
                    'is_visible' => (bool) $state['is_visible'],
                    'content' => $content,
                    'schema_version' => $definition?->version ?? $row->schema_version,
                ]);
            }
        });

        $this->loadState();

        session()->flash('status', 'Page saved.');
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
        $this->blocks = $page->blocks
            ->map(fn (PageBlock $block) => [
                'id' => $block->id,
                'block_key' => $block->block_key,
                'is_visible' => $block->is_visible,
                'content' => $this->contentWithBlanks($block),
            ])
            ->all();
    }

    /**
     * Every schema field gets a key, so the form always has something to bind to.
     *
     * @return array<string, mixed>
     */
    private function contentWithBlanks(PageBlock $block): array
    {
        $content = $block->content ?? [];

        foreach ($this->definitionFor($block)?->fields() ?? [] as $field) {
            $value = $content[$field['name']] ?? BlockDefinition::blankValue($field['type']);

            $content[$field['name']] = $field['type'] === 'link'
                ? ['label' => $value['label'] ?? null, 'url' => $value['url'] ?? null]
                : $value;
        }

        return $content;
    }

    private function definitionFor(?PageBlock $block): ?BlockDefinition
    {
        return $block === null ? null : $this->definitions->get($block->block_key);
    }

    private function normalize(string $type, mixed $value): mixed
    {
        if ($type === 'boolean') {
            return (bool) $value;
        }

        if ($type === 'link') {
            $label = filled($value['label'] ?? null) ? trim($value['label']) : null;
            $url = filled($value['url'] ?? null) ? trim($value['url']) : null;

            return $label === null && $url === null ? null : ['label' => $label, 'url' => $url];
        }

        return filled($value) ? Str::of($value)->trim()->toString() : null;
    }

    private function swap(int $from, int $to): void
    {
        if (! isset($this->blocks[$from], $this->blocks[$to])) {
            return;
        }

        [$this->blocks[$from], $this->blocks[$to]] = [$this->blocks[$to], $this->blocks[$from]];
    }
}
