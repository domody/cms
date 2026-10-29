// Delegated so they survive the live-search swap of #media-content.
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Buttons that submit the bulk form with their own formaction.
document.addEventListener('click', (event) => {
    const message = event.target.closest('button[data-confirm]')?.dataset.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }

    if (event.target.closest('[data-clear-selection]')) {
        document.querySelectorAll('input[name="ids[]"]').forEach((box) => { box.checked = false; });
        syncBulkBar();
    }
});

document.addEventListener('change', (event) => {
    if (event.target.matches('input[name="ids[]"]')) {
        syncBulkBar();
    }

    if (event.target.matches('input[data-auto-submit]')) {
        event.target.form.submit();
    }
});

// Show the bulk bar only while something is selected.
function syncBulkBar() {
    const bulkForm = document.getElementById('bulk-form');
    const count = document.querySelectorAll('input[name="ids[]"]:checked').length;

    bulkForm.querySelector('[data-selected-count]').textContent = count;
    bulkForm.classList.toggle('hidden', count === 0);
    bulkForm.classList.toggle('flex', count > 0);
}

syncBulkBar();

// Live search: refetch the page and swap only the content area.
// Delegated, because the form lives inside the swapped area.
let timer;
let latest = 0;

document.addEventListener('input', (event) => {
    const form = event.target.closest('[data-live-search-form]');
    if (!form) {
        return;
    }

    clearTimeout(timer);
    timer = setTimeout(async () => {
        const request = ++latest;
        const url = `${form.action}?${new URLSearchParams(new FormData(form))}`;
        const html = await (await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();

        if (request !== latest) {
            return; // a newer keystroke superseded this response
        }

        const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('media-content');
        const field = event.target.name;
        document.getElementById('media-content').replaceWith(fresh);
        const next = fresh.querySelector(`[data-live-search-form] [name="${field}"]`);
        next.focus();
        next.setSelectionRange?.(next.value.length, next.value.length);
        history.replaceState(null, '', url);
        syncBulkBar();
    }, 300);
});
