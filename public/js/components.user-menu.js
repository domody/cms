document.querySelectorAll('[data-user-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-user-menu-toggle]');
    const panel = menu.querySelector('[data-user-menu-panel]');

    // The panel is position: fixed so the sidebar's overflow doesn't clip it.
    // It sits to the right of the toggle, bottom-aligned (the toggle is at the sidebar's bottom).
    const position = () => {
        const rect = toggle.getBoundingClientRect();

        panel.style.left = `${rect.right + 8}px`;
        panel.style.top = `${Math.max(8, rect.bottom - panel.offsetHeight)}px`;
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
        if (!menu.contains(event.target)) {
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
