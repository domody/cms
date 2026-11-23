import * as figures from '@lucasmarkes/hairline';

const mounted = new Map();

function mountAll() {
    for (const [element, figure] of mounted) {
        if (!element.isConnected) {
            figure.destroy();
            mounted.delete(element);
        }
    }

    document.querySelectorAll('[data-hairline]').forEach((element) => {
        if (mounted.has(element)) {
            return;
        }

        const name = element.dataset.hairline;

        if (typeof figures[name] !== 'function') {
            console.warn(`Unknown hairline figure: ${name}`);
            return;
        }

        // shortcut: pinned to light, the CMS has no dark mode yet; drop `theme` when it does.
        mounted.set(element, figures[name](element, { theme: 'light' }));
    });
}

document.addEventListener('DOMContentLoaded', mountAll);
document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', mountAll);
});
