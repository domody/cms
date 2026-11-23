// Field outline: marks the field currently at the top of the scroll area with aria-current.
(() => {
    const root = document.querySelector('[data-scroll-root]');

    if (!root) {
        return;
    }

    let queued = false;

    const update = () => {
        queued = false;

        const links = [...document.querySelectorAll('[data-field-outline] a[href^="#"]')];
        const offset = root.getBoundingClientRect().top + 96;
        let current = links[0];

        for (const link of links) {
            const target = document.getElementById(link.hash.slice(1));

            if (target && target.getBoundingClientRect().top <= offset) {
                current = link;
            }
        }

        links.forEach((link) => link.setAttribute('aria-current', link === current ? 'true' : 'false'));
    };

    const queue = () => {
        if (!queued) {
            queued = true;
            requestAnimationFrame(update);
        }
    };

    root.addEventListener('scroll', queue, { passive: true });
    document.addEventListener('livewire:init', () => {
        Livewire.hook('commit', ({ succeed }) => succeed(() => queueMicrotask(queue)));
    });
    queue();
})();
