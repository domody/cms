// Warns before leaving an editor with unsaved changes. A form opts in with
// data-unsaved-guard; components dispatch `editor-dirty` for edits that are
// not typed (reorder, show/hide, picking an image) and `editor-saved` once
// a save has gone through.
(() => {
    let dirty = false;

    const markTyped = (event) => {
        // The media picker's upload dialog sits inside the form but isn't content.
        if (event.target.closest('form[data-unsaved-guard]') && !event.target.closest('[role="dialog"]')) {
            dirty = true;
        }
    };

    document.addEventListener('input', markTyped);
    document.addEventListener('change', markTyped);
    window.addEventListener('editor-dirty', () => { dirty = true; });
    window.addEventListener('editor-saved', () => { dirty = false; });

    // A save that redirects (creating an entry) navigates away on purpose.
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ succeed }) => {
            succeed(({ effects }) => {
                if (effects.redirect) {
                    dirty = false;
                }
            });
        });
    });

    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();
