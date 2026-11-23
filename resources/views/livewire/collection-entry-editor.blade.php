@php
    $fields = $this->definition->editableFields();
    $errorKeys = collect($errors->keys());

    // Cards in schema order: each repeater gets its own, runs of other fields share one.
    $sections = [];
    foreach ($fields as $field) {
        if ($field['type'] === 'repeater' || $sections === [] || end($sections)[0]['type'] === 'repeater') {
            $sections[] = [$field];
        } else {
            $sections[array_key_last($sections)][] = $field;
        }
    }
@endphp

<form id="entry-editor-form" data-unsaved-guard wire:submit="save" @class(['grid items-start gap-8','lg:grid-cols-[minmax(0,1fr)_14rem]' => count($fields) > 4])>
    <div class="min-w-0 space-y-4">
        <x-card>
            <div class="grid gap-x-4 gap-y-5 sm:grid-cols-3">
                <x-field label="Title" name="title" required class="sm:col-span-2">
                    <input type="text" wire:model="title" class="input text-base font-medium">
                </x-field>
                <x-field label="Status" name="status">
                    <select wire:model="status" class="input">
                        @foreach (\App\Models\PageStatus::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Slug" name="slug" hint="Used in the entry's URL. Leave empty to generate it from the title." class="sm:col-span-3">
                    <input type="text" wire:model="slug" placeholder="generated-from-the-title" class="input font-mono text-[13px]">
                </x-field>
            </div>
        </x-card>

        @foreach ($sections as $section)
            <x-card wire:key="section-{{ $section[0]['name'] }}">
                @if ($section[0]['type'] === 'repeater')
                    <x-block-field :field="$section[0]" path="data.{{ $section[0]['name'] }}" :value="$data[$section[0]['name']] ?? []" wire:key="field-{{ $section[0]['name'] }}" />
                @else
                    <x-field-list :fields="$section" path="data" :values="$data" />
                @endif
            </x-card>
        @endforeach
    </div>

    @if (count($fields) > 4)
        {{-- Outline: every field at a glance, jump to it, see which need attention. --}}
        <nav aria-label="Fields" class="hidden lg:sticky lg:top-20 lg:block lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto" data-field-outline>
            <p class="mb-2 px-2 text-xs font-medium text-secondary-foreground">On this entry</p>
            <ul class="space-y-px border-s">
                @foreach ($fields as $field)
                    @php($hasError = $errorKeys->contains(fn ($key) => $key === "data.{$field['name']}" || str_starts_with($key, "data.{$field['name']}.")))
                    <li>
                        <a href="#field-data-{{ $field['name'] }}" class="-ms-px flex items-center gap-2 border-s border-transparent py-1 pe-2 ps-3 text-[13px] text-secondary-foreground hover:border-foreground/30 hover:text-foreground aria-[current=true]:border-foreground aria-[current=true]:font-medium aria-[current=true]:text-foreground">
                            <span class="truncate">{{ $field['label'] }}</span>
                            @if ($field['type'] === 'repeater')
                                <span class="ms-auto text-xs tabular-nums text-muted-foreground">{{ count($data[$field['name']] ?? []) }}</span>
                            @endif
                            @if ($hasError)
                                <span class="ms-auto size-1.5 shrink-0 rounded-full bg-danger" title="Needs attention"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</form>
