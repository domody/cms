@props(['title', 'value'])

{{-- A password or token that is only shown once, with a copy button. --}}
<x-callout tone="warning" :title="$title">
    <p>Copy it now: it is only shown once.</p>
    <div class="mt-2 flex gap-2" x-data="{ copied: false }">
        <input type="text" readonly value="{{ $value }}" x-ref="secret" onfocus="this.select()" aria-label="{{ $title }}" class="input bg-background font-mono text-xs">
        <x-button variant="outline" icon="copy-01" x-on:click="navigator.clipboard ? navigator.clipboard.writeText($refs.secret.value) : ($refs.secret.select(), document.execCommand('copy')); copied = true; setTimeout(() => copied = false, 2000)">
            <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
        </x-button>
    </div>
</x-callout>
