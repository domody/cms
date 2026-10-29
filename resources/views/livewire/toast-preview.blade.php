{{-- TEMP: remove with App\Livewire\ToastPreview --}}
<div class="flex max-w-md flex-col gap-2 py-2">
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('success', 'Changes saved.', 'Page published')">Success + title</button>
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('success', 'Changes saved.')">Success</button>
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('error', 'hero-banner.png is larger than the 5 MB limit.', 'Upload failed')">Error + title</button>
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('error', 'Could not save the page. Please try again.')">Error</button>
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('warning', 'You have edits on “Pricing” that haven’t been saved.', 'Unsaved changes')">Warning + title</button>
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('warning', 'This block type is deprecated and will be removed soon.')">Warning</button>
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('info', 'Jordan Lee can now edit pages on this site.', 'New teammate added')">Info + title</button>
    <button type="button" class="rounded-md border px-3 py-2 text-left text-sm hover:bg-muted" wire:click="toast('info', 'A draft of this page was restored from your last session.')">Info</button>
</div>
