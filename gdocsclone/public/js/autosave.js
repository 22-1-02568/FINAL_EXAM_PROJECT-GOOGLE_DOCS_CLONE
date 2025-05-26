document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('docForm');
    const titleInput = form.querySelector('input[name="title"]');
    const editorDiv = document.getElementById('editor');
    let timeoutId = null;

    function autosave() {
        const params = new URLSearchParams(window.location.search);
        const docId = params.get('id');
        if (!docId) return; // Only autosave for existing documents

        const data = new FormData();
        data.append('title', titleInput.value);
        data.append('content', editorDiv.innerHTML);
        data.append('autosave', '1');

        fetch(`../../controllers/DocumentController.php?id=${docId}`, {
            method: 'POST',
            body: data
        });
    }

    function scheduleAutosave() {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(autosave, 1000); // Save 1s after last change
    }

    titleInput.addEventListener('input', scheduleAutosave);
    editorDiv.addEventListener('input', scheduleAutosave);
});