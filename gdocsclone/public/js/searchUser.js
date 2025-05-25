document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('userSearch');
    const results = document.getElementById('userResults');
    const params = new URLSearchParams(window.location.search);
    const docId = params.get('id');

    if (!input || !results || !docId) return;

    function fetchUsers(query = '') {
        fetch(`../../controllers/UserController.php?search=${encodeURIComponent(query)}&document_id=${docId}`)
            .then(res => res.json())
            .then(data => {
                results.innerHTML = '';
                if (data.length === 0) {
                    results.innerHTML = '<div style="color:#888;">No users found.</div>';
                }
                data.forEach(user => {
                    const wrapper = document.createElement('div');
                    wrapper.style.display = 'flex';
                    wrapper.style.alignItems = 'center';
                    wrapper.style.marginBottom = '5px';

                    const info = document.createElement('span');
                    info.textContent = user.username + ' (' + user.email + ')';
                    info.style.flex = '1';

                    const addBtn = document.createElement('button');
                    addBtn.textContent = 'Add';
                    addBtn.style.marginLeft = '10px';
                    addBtn.onclick = function () {
                        fetch('../../controllers/DocumentController.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `add_user=1&user_id=${user.id}&document_id=${docId}`
                        })
                        .then(res => res.text())
                        .then(msg => {
                            addBtn.disabled = true;
                            addBtn.textContent = 'Added';
                            info.style.color = 'green';
                            info.textContent += ' (Shared)';
                        });
                    };

                    wrapper.appendChild(info);
                    wrapper.appendChild(addBtn);
                    results.appendChild(wrapper);
                });
            });
    }

    input.addEventListener('input', function () {
        fetchUsers(input.value);
    });

    input.addEventListener('focus', function () {
        fetchUsers('');
    });
});