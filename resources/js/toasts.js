const MAX_PER_CORNER = 5;
const AUTO_DISMISS_MS = 4000;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('toasts', (initial = []) => ({
        toasts: [],
        nextId: 1,

        init() {
            initial.forEach((toast) => this.add(toast));
        },

        // Livewire named params arrive as the event detail; tolerate a wrapped array too.
        add(detail) {
            const data = Array.isArray(detail) ? detail[0] : detail;

            if (!data || !data.message) {
                return;
            }

            const toast = {
                id: this.nextId++,
                type: data.type || 'info',
                title: data.title || null,
                message: data.message,
                position: data.position || 'top-right',
                sticky: data.type === 'error',
                remaining: AUTO_DISMISS_MS,
                startedAt: 0,
                timer: null,
            };

            this.toasts.push(toast);

            const sameCorner = this.inCorner(toast.position);

            if (sameCorner.length > MAX_PER_CORNER) {
                this.remove(sameCorner[0].id);
            }

            // Re-read through the reactive proxy so the timer sees the stored toast.
            this.resume(this.toasts.find((item) => item.id === toast.id));
        },

        inCorner(position) {
            return this.toasts.filter((toast) => toast.position === position);
        },

        remove(id) {
            const toast = this.toasts.find((item) => item.id === id);

            if (toast) {
                clearTimeout(toast.timer);
            }

            this.toasts = this.toasts.filter((item) => item.id !== id);
        },

        pause(toast) {
            if (toast.sticky || toast.timer === null) {
                return;
            }

            clearTimeout(toast.timer);
            toast.timer = null;
            toast.remaining = Math.max(0, toast.remaining - (Date.now() - toast.startedAt));
        },

        resume(toast) {
            if (!toast || toast.sticky) {
                return;
            }

            toast.startedAt = Date.now();
            toast.timer = setTimeout(() => this.remove(toast.id), toast.remaining);
        },
    }));
});
