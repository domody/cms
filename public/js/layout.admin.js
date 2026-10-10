document.querySelectorAll('[data-sidebar-group]').forEach((group) => {
    const toggle = group.querySelector('[data-sidebar-group-toggle]');
    const panel = group.querySelector('[data-sidebar-group-panel]');
    const indicator = group.querySelector('[data-sidebar-group-indicator]');

    toggle.addEventListener('click', () => {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        panel.classList.toggle('hidden', isExpanded);
        indicator.textContent = isExpanded ? '+' : '−';
    });
});
