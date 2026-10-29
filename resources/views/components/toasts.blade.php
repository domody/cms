<div x-data="toasts(@js(session('toasts', [])))" x-on:toast.window="add($event.detail)">
    @foreach (\App\Models\ToastPosition::all() as $position)
        <div class="pointer-events-none fixed z-50 flex flex-col gap-2 p-4 sm:w-96 {{ \App\Models\ToastPosition::getClass($position) }}">
            <template x-for="toast in inCorner('{{ $position }}')" :key="toast.id">
                <div
                    class="pointer-events-auto flex items-start gap-2 rounded-lg border px-2 py-2 text-sm shadow-sm bg-background border"
                    :class="{
                        'text-success': toast.type === 'success',
                        'text-danger': toast.type === 'error',
                        'text-warning': toast.type === 'warning',
                        'text-foreground': toast.type === 'info',
                    }"
                    :role="toast.type === 'error' ? 'alert' : 'status'"
                    :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
                    x-on:mouseenter="pause(toast)"
                    x-on:mouseleave="resume(toast)"
                    x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 translate-y-2 motion-reduce:translate-y-0"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <template x-if="toast.type === 'success'"><x-hugeicons-circle-check class="mt-0.5 size-4 shrink-0" aria-hidden="true" /></template>
                    <template x-if="toast.type === 'error'"><x-hugeicons-cancel-circle class="mt-0.5 size-4 shrink-0" aria-hidden="true" /></template>
                    <template x-if="toast.type === 'warning'"><x-hugeicons-alert-02 class="mt-0.5 size-4 shrink-0" aria-hidden="true" /></template>
                    <template x-if="toast.type === 'info'"><x-hugeicons-information-circle class="mt-0.5 size-4 shrink-0" aria-hidden="true" /></template>

                    <div class="min-w-0 flex-1">
                        <p x-show="toast.title" x-text="toast.title" class="font-medium"></p>
                        <p x-text="toast.message"></p>
                    </div>

                    <button type="button" class="btn-icon -m-1 shrink-0 text-current opacity-60 hover:bg-foreground/5 hover:text-current hover:opacity-100" aria-label="Dismiss notification" x-on:click="remove(toast.id)">
                        <x-hugeicons-cancel-01 aria-hidden="true" />
                    </button>
                </div>
            </template>
        </div>
    @endforeach
</div>
