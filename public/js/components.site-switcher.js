document.querySelectorAll('[data-site-switcher]').forEach((switcher) => {
    const toggle = switcher.querySelector('[data-site-switcher-toggle]');
    const panel = switcher.querySelector('[data-site-switcher-panel]');

    // The panel is position: fixed so the sidebar's overflow doesn't clip it.
    const position = () => {
        const rect = toggle.getBoundingClientRect();

        panel.style.top = `${rect.top}px`;
        panel.style.left = `${rect.right + 8}px`;
    };

    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('hidden', !open);

        if (open) {
            position();
        }
    };

    toggle.addEventListener('click', () => {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    document.addEventListener('click', (event) => {
        if (!switcher.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    window.addEventListener('resize', () => setOpen(false));
});
