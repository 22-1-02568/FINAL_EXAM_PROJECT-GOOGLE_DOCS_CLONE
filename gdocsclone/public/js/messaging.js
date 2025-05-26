document.addEventListener('DOMContentLoaded', function () {
    const messagesDiv = document.getElementById('messages');
    const form = document.getElementById('messageForm');
    const input = document.getElementById('messageInput');
    const documentId = form.querySelector('input[name="document_id"]').value;

    function fetchMessages() {
        fetch(`../../controllers/MessageController.php?document_id=${documentId}`)
            .then(res => res.json())
            .then(data => {
                messagesDiv.innerHTML = '';
                data.forEach(msg => {
                    const msgDiv = document.createElement('div');
                    msgDiv.innerHTML = `<strong>${msg.username}:</strong> ${msg.message} <span style="color:#888;font-size:0.8em;">(${msg.created_at})</span>`;
                    messagesDiv.appendChild(msgDiv);
                });
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('../../controllers/MessageController.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(() => {
            input.value = '';
            fetchMessages();
        });
    });

    // Poll for new messages every 2 seconds
    setInterval(fetchMessages, 2000);
    fetchMessages();
});