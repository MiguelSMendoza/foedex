document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.querySelector('[data-markdown-input="true"]');

    if (!textarea) {
        return;
    }

    textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Tab') {
            event.preventDefault();

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            textarea.value = `${textarea.value.substring(0, start)}    ${textarea.value.substring(end)}`;
            textarea.selectionStart = textarea.selectionEnd = start + 4;
        }
    });
});
