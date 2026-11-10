<form id="entry-editor-form" data-unsaved-guard wire:submit="save" class="space-y-4">

    <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-3">
        <label class="block text-sm font-medium text-gray-700 sm:col-span-2">
            Title <span class="text-red-600">*</span>
            <input type="text" wire:model="title" class="input mt-1">
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
        <label class="block text-sm font-medium text-gray-700">
            Status
            <select wire:model="status" class="input mt-1">
                @foreach (\App\Models\PageStatus::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
        <label class="block text-sm font-medium text-gray-700 sm:col-span-3">
            Slug
            <input type="text" wire:model="slug" placeholder="Generated from the title" class="input mt-1">
            @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
    </div>

    @if ($this->definition->editableFields() !== [])
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4">
            @foreach ($this->definition->editableFields() as $field)
                <x-block-field :field="$field" path="data.{{ $field['name'] }}" :value="$data[$field['name']] ?? []" wire:key="field-{{ $field['name'] }}" />
            @endforeach
        </div>
    @endif
</form>
