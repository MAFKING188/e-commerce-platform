// Partner console interactions: confirm modal + helpers.
(() => {
    const modal = document.getElementById('pc-confirm-modal');
    if (!modal) return;

    const overlay = modal.querySelector('.pc-modal__overlay');
    const dialog = modal.querySelector('.pc-modal__dialog');
    const titleEl = modal.querySelector('[data-modal-title]');
    const textEl = modal.querySelector('[data-modal-text]');
    const cancelBtn = modal.querySelector('[data-modal-cancel]');
    const confirmBtn = modal.querySelector('[data-modal-confirm]');

    let onConfirm = null;

    function open(title, text, confirmLabel, callback) {
        titleEl.textContent = title;
        textEl.textContent = text;
        confirmBtn.textContent = confirmLabel;
        onConfirm = callback;
        modal.classList.add('is-open');
        confirmBtn.focus();
    }

    function close() {
        modal.classList.remove('is-open');
        onConfirm = null;
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm]');
        if (!trigger) return;

        event.preventDefault();
        open(
            trigger.dataset.confirmTitle || 'Are you sure?',
            trigger.dataset.confirmMessage || 'This action cannot be undone.',
            trigger.dataset.confirmLabel || 'Confirm',
            () => {
                const form = trigger.closest('form');
                if (form) form.submit();
                else if (trigger.href) window.location.href = trigger.href;
            }
        );
    });

    confirmBtn.addEventListener('click', () => {
        if (onConfirm) onConfirm();
        close();
    });

    cancelBtn.addEventListener('click', close);
    overlay.addEventListener('click', close);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });

    window.pcConfirm = (title, text, callback) => open(title, text, 'Confirm', callback);
})();