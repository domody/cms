<?php

namespace App\Livewire;

use App\Livewire\Concerns\Toasts;
use App\Models\CollectionDefinition;
use App\Models\CollectionEntry;
use App\Models\PageStatus;
use App\Models\Scopes\CurrentSite;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create or edit one entry of a collection. The form's fields come from the
 * collection definition. Nothing is persisted until save().
 */
class CollectionEntryEditor extends Component
{
    use Toasts;

    #[Locked]
    public int $definitionId;

    #[Locked]
    public ?int $entryId = null;

    public string $title = '';

    public string $slug = '';

    public string $status = PageStatus::DRAFT;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(CollectionDefinition $definition, ?CollectionEntry $entry = null): void
    {
        $this->definitionId = $definition->id;

        if ($entry !== null) {
            $this->entryId = $entry->id;
            $this->title = $entry->title;
            $this->slug = $entry->slug;
            $this->status = $entry->status;
        }

        $this->data = $definition->contentWithBlanks($entry?->data ?? []);
    }

    #[Computed]
    public function definition(): CollectionDefinition
    {
        return CollectionDefinition::findOrFail($this->definitionId);
    }

    public function save(): void
    {
        $definition = $this->definition;
        $entry = $this->entryId === null ? null : CollectionEntry::findOrFail($this->entryId);

        $attributes = [];

        foreach ($definition->editableFields() as $field) {
            $attributes["data.{$field['name']}"] = $field['label'];
            $attributes["data.{$field['name']}.label"] = "{$field['label']} label";
            $attributes["data.{$field['name']}.url"] = "{$field['label']} URL";
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('collection_entries', 'slug')
                    ->where('site_id', $definition->site_id)
                    ->where('collection_key', $definition->key)
                    ->ignore($entry?->id),
            ],
            'status' => ['required', Rule::in(PageStatus::all())],
            ...$definition->contentRules('data'),
        ], [], $attributes);

        $values = [
            'title' => trim($this->title),
            'slug' => filled($this->slug) ? $this->slug : CollectionEntry::uniqueSlug($definition->key, $this->title, $entry?->id),
            'status' => $this->status,
            'data' => $definition->normalizedContent($this->data),
        ];

        $site = app(CurrentSite::class)->get();

        if ($entry === null) {
            $entry = CollectionEntry::create($values + [
                'collection_key' => $definition->key,
                'position' => CollectionEntry::nextPosition($definition->key),
            ]);

            $site->revalidateFrontend(CollectionDefinition::revalidationTags($definition->key));

            toast('success', 'Entry created.');

            $this->redirectRoute('collections.edit', [
                'site' => $site->slug,
                'collection' => $definition->key,
                'entry' => $entry->id,
            ]);

            return;
        }

        $entry->update($values);

        $site->revalidateFrontend(CollectionDefinition::revalidationTags($definition->key));

        $this->slug = $values['slug'];
        $this->data = $definition->contentWithBlanks($entry->data);

        $this->dispatch('editor-saved');
        $this->toast('success', 'Entry saved.');
    }

    public function render(): View
    {
        return view('livewire.collection-entry-editor');
    }
}
