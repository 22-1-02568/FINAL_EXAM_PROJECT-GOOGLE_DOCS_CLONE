<?php
session_start();
require_once __DIR__ . '/../../core/dbConfig.php';
require_once __DIR__ . '/../../models/Document.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$documentModel = new Document($pdo);
$doc = ['title' => '', 'content' => ''];
$isEdit = false;
$message = '';

if (isset($_GET['id'])) {
    $doc = $documentModel->getById($_GET['id']);
    $isEdit = true;
    // Check if user is owner or shared
    $stmt = $pdo->prepare("SELECT 1 FROM documents WHERE id = ? AND user_id = ? UNION SELECT 1 FROM document_users WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_GET['id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        die('Document not found or access denied.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    require_once __DIR__ . '/../../models/ActivityLog.php';
    $activityLogModel = new ActivityLog($pdo);

    if ($isEdit) {
        $changes = [];
        if ($doc['title'] !== $title) {
            $changes[] = "title changed from '{$doc['title']}' to '{$title}'";
        }
        if ($doc['content'] !== $content) {
            $changes[] = "content updated";
        }
        $documentModel->update($_GET['id'], $title, $content);
        if ($changes) {
            $activityLogModel->log($_GET['id'], $_SESSION['user_id'], implode('; ', $changes));
        }
        $message = 'Document updated!';
    } else {
        $documentModel->create($_SESSION['user_id'], $title, $content);
        $lastId = $pdo->lastInsertId();
        $activityLogModel->log($lastId, $_SESSION['user_id'], 'created the document');
        $message = 'Document created!';
    }
    $doc['title'] = $title;
    $doc['content'] = $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $isEdit ? htmlspecialchars($doc['title']) : 'New Document'; ?> - GDocs Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="../../public/js/autosave.js"></script>

    <style>
        /* Editor Styles */
        #editor {
            line-height: 1.6;
            font-size: 1.1rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            border: none;
            resize: none;
            background: white;
            padding: 2rem;
            min-height: 500px;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            transition: box-shadow 0.2s ease;
            width: 100%;
            max-width: 100%;
            overflow-wrap: break-word;
        }

        #editor:focus {
            box-shadow: 0 0 0 2px #1a73e8, 0 0 0 1px rgba(99, 102, 241, 0.4);
        }

        #editor h1,
        #editor h2,
        #editor h3 {
            margin: 1.5rem 0 1rem 0;
            font-weight: 600;
        }

        #editor h1 {
            font-size: 2rem;
            color: #1f2937;
        }

        #editor h2 {
            font-size: 1.5rem;
            color: #374151;
        }

        #editor h3 {
            font-size: 1.25rem;
            color: #4b5563;
        }

        #editor p {
            margin: 1rem 0;
        }

        #editor ul,
        #editor ol {
            margin: 1rem 0;
            padding-left: 2rem;
        }

        #editor li {
            margin: 0.5rem 0;
        }

        /* Toolbar Styles - horizontal top toolbar */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
            align-items: center;
            user-select: none;
        }

        .tool-btn {
            width: 36px;
            height: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 6px;
            border: 1px solid transparent;
            background: transparent;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 16px;
        }

        .tool-btn:hover {
            background: #e0e7ff;
            border-color: #c7d2fe;
            color: #1a73e8;
            transform: translateY(-1px);
        }

        .tool-btn:active {
            transform: translateY(0);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .tool-btn.active {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }

        /* Grouping buttons */
        .toolbar-group {
            display: flex;
            gap: 8px;
            border-right: 1px solid #e5e7eb;
            padding-right: 8px;
            margin-right: 8px;
        }

        .toolbar-group:last-child {
            border-right: none;
            margin-right: 0;
            padding-right: 0;
        }

        /* Title and Save button container */
        .title-save-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .title-input {
            font-size: 2rem;
            font-weight: 700;
            border: none;
            background: transparent;
            outline: none;
            color: #111827;
            width: 100%;
            max-width: 600px;
            font-family: 'Inter', sans-serif;
        }

        .save-button {
            background-color: #1a73e8;
            color: white;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(26, 115, 232, 0.5);
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .save-button:hover {
            background-color: #155ab6;
            transform: scale(1.05);
        }

        .save-button:focus {
            outline: 2px solid #1a73e8;
            outline-offset: 2px;
        }

        /* Save status text */
        #saveStatus {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Right sidebar */
        aside {
            flex-shrink: 0;
            width: 300px;
            max-width: 300px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 16px;
            position: sticky;
            top: 80px;
            height: fit-content;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        aside a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background-color: #2563eb;
            color: white;
            padding: 10px 12px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease;
            margin-bottom: 8px;
        }

        aside a:hover {
            background-color: #1e40af;
        }

        /* Container for editor and sidebar */
        .editor-container {
            display: flex;
            gap: 24px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .editor-container {
                flex-direction: column;
            }

            aside {
                width: 100%;
                max-width: 100%;
                position: relative;
                top: auto;
            }

            .title-save-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .save-button {
                width: 100%;
                justify-content: center;
            }
        }

        /* Share section styles */
        #shareSection {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        #userResults {
            margin-top: 8px;
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: white;
        }

        .user-result {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #e2e8f0;
        }

        .user-result:hover {
            background-color: #e0e7ff;
        }

        /* Chat messages styles */
        #chatMessages {
            flex-grow: 1;
            overflow-y: auto;
            max-height: 400px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px;
            background: #f9fafb;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
        }

        .chat-message {
            margin-bottom: 16px;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chat-message .sender {
            font-weight: 600;
            color: #2563eb;
        }

        .chat-message .timestamp {
            font-size: 0.75rem;
            color: #6b7280;
            margin-left: 8px;
        }

        .chat-message .content {
            margin-top: 4px;
            white-space: pre-wrap;
        }

        #chatInputContainer {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        #chatInput {
            flex-grow: 1;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s ease;
        }
        #chatInput:focus {
            border-color: #2563eb;
            box-shadow: 0 0 5px rgba(37, 99, 235, 0.5);
        }

        #chatSendBtn {
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #chatSendBtn:hover {
            background-color: #1e40af;
            transform: scale(1.05);
        }
        #chatSendBtn:active {
            transform: scale(1);
        }

        #chatSendBtn:hover {
            background-color: #1e40af;
        }
    </style>
</head>
<body class="bg-white min-h-screen">
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Success Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-blue-100 text-blue-800 rounded-lg shadow-sm fade-in flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Title and Save Button -->
        <form method="POST" id="docForm" enctype="multipart/form-data" class="title-save-container flex items-center gap-4" onsubmit="return submitForm(event)">
            <input
                type="text"
                name="title"
                id="documentTitle"
                value="<?php echo htmlspecialchars($doc['title']); ?>"
                placeholder="Untitled Document"
                class="title-input"
                style="font-family: 'Inter', sans-serif;"
            />
            <span id="saveStatus" class="ml-4 flex items-center">
                <i class="fas fa-circle text-blue-400 mr-1" style="font-size: 0.5rem;"></i>
                All changes saved
            </span>
            <input type="hidden" name="content" id="contentInput" />
            <button type="submit" class="save-button">
                <i class="fas fa-save"></i>
                <?php echo $isEdit ? 'Update Document' : 'Save Document'; ?>
            </button>
        </form>

        <!-- Toolbar -->
        <div class="toolbar" role="toolbar" aria-label="Text formatting toolbar">
            <div class="toolbar-group" aria-label="Text style">
                <button type="button" class="tool-btn" onclick="format('bold')" title="Bold (Ctrl+B)" aria-pressed="false">
                    <i class="fas fa-bold"></i>
                </button>
                <button type="button" class="tool-btn" onclick="format('italic')" title="Italic (Ctrl+I)" aria-pressed="false">
                    <i class="fas fa-italic"></i>
                </button>
                <button type="button" class="tool-btn" onclick="format('underline')" title="Underline (Ctrl+U)" aria-pressed="false">
                    <i class="fas fa-underline"></i>
                </button>
                <button type="button" class="tool-btn" onclick="format('strikeThrough')" title="Strikethrough" aria-pressed="false">
                    <i class="fas fa-strikethrough"></i>
                </button>
            </div>
            <div class="toolbar-group" aria-label="Headings">
                <button type="button" class="tool-btn" onclick="formatBlock('H1')" title="Heading 1" aria-pressed="false">H1</button>
                <button type="button" class="tool-btn" onclick="formatBlock('H2')" title="Heading 2" aria-pressed="false">H2</button>
                <button type="button" class="tool-btn" onclick="formatBlock('H3')" title="Heading 3" aria-pressed="false">H3</button>
                <button type="button" class="tool-btn" onclick="formatBlock('P')" title="Paragraph" aria-pressed="false">
                    <i class="fas fa-paragraph"></i>
                </button>
            </div>
            <div class="toolbar-group" aria-label="Lists">
                <button type="button" class="tool-btn" onclick="format('insertUnorderedList')" title="Bullet List" aria-pressed="false">
                    <i class="fas fa-list-ul"></i>
                </button>
                <button type="button" class="tool-btn" onclick="format('insertOrderedList')" title="Numbered List" aria-pressed="false">
                    <i class="fas fa-list-ol"></i>
                </button>
            </div>
            <div class="toolbar-group" aria-label="Alignment">
                <button type="button" class="tool-btn" onclick="format('justifyLeft')" title="Align Left" aria-pressed="false">
                    <i class="fas fa-align-left"></i>
                </button>
                <button type="button" class="tool-btn" onclick="format('justifyCenter')" title="Align Center" aria-pressed="false">
                    <i class="fas fa-align-center"></i>
                </button>
                <button type="button" class="tool-btn" onclick="format('justifyRight')" title="Align Right" aria-pressed="false">
                    <i class="fas fa-align-right"></i>
                </button>
            </div>
            <div class="toolbar-group" aria-label="Links">
                <button type="button" class="tool-btn" onclick="format('createLink')" title="Insert Link" aria-pressed="false">
                    <i class="fas fa-link"></i>
                </button>
                <button type="button" class="tool-btn" onclick="format('unlink')" title="Remove Link" aria-pressed="false">
                    <i class="fas fa-unlink"></i>
                </button>
            </div>
        </div>

        <!-- Editor and Sidebar Container -->
        <div class="editor-container">
            <div class="flex-1">
                <div class="bg-white rounded-xl shadow-lg mb-6">
                    <div id="editor" contenteditable="true"><?php echo htmlspecialchars($doc['content']); ?></div>
                </div>

                <!-- Share Document section BELOW the editor area -->
                <div id="shareSection" class="mb-6">
                    <h3 class="text-lg font-semibold mb-2">Share Document</h3>
                    <input
                        type="text"
                        id="userSearch"
                        placeholder="Search users by username or email..."
                        autocomplete="off"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        <?php if (!$isEdit): ?> disabled <?php endif; ?>
                    />
                    <button
                        type="button"
                        id="shareBtn"
                        class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-200 flex items-center"
                        <?php if (!$isEdit): ?> disabled <?php endif; ?>
                    >
                        <i class="fas fa-paper-plane mr-2"></i> Share
                    </button>
                    <div id="userResults" class="mt-4"></div>
                    <div id="shareMessage" class="mt-2 text-sm"></div>
                    <?php if (!$isEdit): ?>
                        <p class="text-gray-500 mt-2">Please save the document to enable sharing.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat & Activity Sidebar (right) -->
            <aside>
                <a href="messages.php?document_id=<?php echo $isEdit ? intval($_GET['id']) : 0; ?>" title="Chat" <?php if (!$isEdit): ?> class="opacity-50 pointer-events-none" <?php endif; ?>>
                    <i class="fas fa-comments mr-2"></i> Chat
                </a>
                <a href="activity.php?document_id=<?php echo $isEdit ? intval($_GET['id']) : 0; ?>" title="Activity" <?php if (!$isEdit): ?> class="opacity-50 pointer-events-none" <?php endif; ?>>
                    <i class="fas fa-history mr-2"></i> Activity
                </a>

                <!-- Chat messages UI -->
                <div id="chatMessages" <?php if (!$isEdit): ?> style="opacity: 0.5; pointer-events: none;" <?php endif; ?>></div>
                <div id="chatInputContainer" <?php if (!$isEdit): ?> style="opacity: 0.5; pointer-events: none;" <?php endif; ?>>
                    <input type="text" id="chatInput" placeholder="Type a message..." <?php if (!$isEdit): ?> disabled <?php endif; ?> />
                    <button id="chatSendBtn" title="Send message" <?php if (!$isEdit): ?> disabled <?php endif; ?>><i class="fas fa-paper-plane"></i></button>
                </div>
            </aside>
        </div>
    </main>

    <script src="../../public/js/searchUser.js"></script>

    <script>
        // Enhanced editor functionality
        function format(command, value = null) {
            document.execCommand(command, false, value);
            updateToolbarState();
        }

        function formatBlock(tag) {
            document.execCommand('formatBlock', false, tag);
            updateToolbarState();
        }

        // Update toolbar button states
        function updateToolbarState() {
            const commands = ['bold', 'italic', 'underline', 'strikeThrough'];
            commands.forEach(command => {
                const button = document.querySelector(`[onclick="format('${command}')"]`);
                if (button) {
                    if (document.queryCommandState(command)) {
                        button.classList.add('active');
                        button.setAttribute('aria-pressed', 'true');
                    } else {
                        button.classList.remove('active');
                        button.setAttribute('aria-pressed', 'false');
                    }
                }
            });
        }

        // Form submission handler
        function submitForm(e) {
            e.preventDefault();
            const content = document.getElementById('editor').innerHTML;
            document.getElementById('contentInput').value = content;
            e.target.submit();
        }

        // Auto-save functionality
        let saveTimeout;
        function autoSave() {
            clearTimeout(saveTimeout);
            const saveStatus = document.getElementById('saveStatus');

            saveStatus.innerHTML = '<i class="fas fa-circle text-yellow-400 mr-1" style="font-size: 0.5rem;"></i> Saving...';

            saveTimeout = setTimeout(() => {
                saveStatus.innerHTML = '<i class="fas fa-circle text-blue-400 mr-1" style="font-size: 0.5rem;"></i> All changes saved';
            }, 1000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'b':
                        e.preventDefault();
                        format('bold');
                        break;
                    case 'i':
                        e.preventDefault();
                        format('italic');
                        break;
                    case 'u':
                        e.preventDefault();
                        format('underline');
                        break;
                    case 's':
                        e.preventDefault();
                        document.getElementById('docForm').submit();
                        break;
                }
            }
        });

        // Initialize editor
        document.addEventListener('DOMContentLoaded', function () {
            const editor = document.getElementById('editor');

            // Add event listeners for auto-save
            editor.addEventListener('input', autoSave);
            editor.addEventListener('keyup', updateToolbarState);
            editor.addEventListener('mouseup', updateToolbarState);

            // Initial toolbar state
            updateToolbarState();

            // Focus editor if creating new document
            <?php if (!$isEdit): ?>
            document.getElementById('documentTitle').focus();
            <?php endif; ?>

            // Share functionality
            let selectedUserId = null;
            const userSearchInput = document.getElementById('userSearch');
            const userResultsDiv = document.getElementById('userResults');
            const shareBtn = document.getElementById('shareBtn');
            const shareMessage = document.getElementById('shareMessage');

            if (userSearchInput) {
                userSearchInput.addEventListener('input', function () {
                    const query = this.value.trim();
                    userResultsDiv.innerHTML = '';
                    shareMessage.textContent = '';
                    selectedUserId = null;
                    shareBtn.disabled = true;

                    if (query.length < 2) {
                        return;
                    }

                    fetch(`../../public/js/searchUser.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(users => {
                            if (users.length === 0) {
                                userResultsDiv.innerHTML = '<div class="user-result">No users found</div>';
                                return;
                            }
                            users.forEach(user => {
                                const div = document.createElement('div');
                                div.className = 'user-result';
                                div.textContent = user.username + ' (' + user.email + ')';
                                div.dataset.userId = user.id;
                                div.addEventListener('click', () => {
                                    selectedUserId = user.id;
                                    userSearchInput.value = div.textContent;
                                    userResultsDiv.innerHTML = '';
                                    shareBtn.disabled = false;
                                    shareMessage.textContent = '';
                                });
                                userResultsDiv.appendChild(div);
                            });
                        })
                        .catch(() => {
                            userResultsDiv.innerHTML = '<div class="user-result">Error searching users</div>';
                        });
                });
            }

            if (shareBtn) {
                shareBtn.addEventListener('click', () => {
                    if (!selectedUserId) return;
                    shareBtn.disabled = true;
                    shareMessage.textContent = 'Sharing...';

                    fetch('../../controllers/DocumentController.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `add_user=1&user_id=${encodeURIComponent(selectedUserId)}&document_id=<?php echo $isEdit ? intval($_GET['id']) : 0; ?>`,
                    })
                        .then(response => response.text())
                        .then(text => {
                            shareMessage.textContent = text;
                            shareBtn.disabled = false;
                        })
                        .catch(() => {
                            shareMessage.textContent = 'Error sharing document.';
                            shareBtn.disabled = false;
                        });
                });
            }

            // Messages functionality
            const chatMessagesDiv = document.getElementById('chatMessages');
            const chatInput = document.getElementById('chatInput');
            const chatSendBtn = document.getElementById('chatSendBtn');
            const documentId = <?php echo $isEdit ? intval($_GET['id']) : 0; ?>;

            function fetchMessages() {
                if (!documentId) return;
                fetch(`../../controllers/MessageController.php?document_id=${documentId}`)
                    .then(response => response.json())
                    .then(messages => {
                        chatMessagesDiv.innerHTML = '';
                        messages.forEach(msg => {
                            const msgDiv = document.createElement('div');
                            msgDiv.className = 'chat-message';
                            const senderSpan = document.createElement('span');
                            senderSpan.className = 'sender';
                            senderSpan.textContent = msg.username || 'Unknown';
                            const timestampSpan = document.createElement('span');
                            timestampSpan.className = 'timestamp';
                            const date = new Date(msg.created_at);
                            timestampSpan.textContent = date.toLocaleString();
                            const contentDiv = document.createElement('div');
                            contentDiv.className = 'content';
                            contentDiv.textContent = msg.message;
                            msgDiv.appendChild(senderSpan);
                            msgDiv.appendChild(timestampSpan);
                            msgDiv.appendChild(contentDiv);
                            chatMessagesDiv.appendChild(msgDiv);
                        });
                        chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
                    })
                    .catch(() => {
                        chatMessagesDiv.innerHTML = '<div class="chat-message">Error loading messages.</div>';
                    });
            }

            if (chatSendBtn) {
                chatSendBtn.addEventListener('click', () => {
                    const message = chatInput.value.trim();
                    if (!message || !documentId) return;
                    chatSendBtn.disabled = true;
                    fetch('../../controllers/MessageController.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `document_id=${documentId}&message=${encodeURIComponent(message)}`,
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                chatInput.value = '';
                                fetchMessages();
                            } else {
                                alert('Error sending message.');
                            }
                            chatSendBtn.disabled = false;
                        })
                        .catch(() => {
                            alert('Error sending message.');
                            chatSendBtn.disabled = false;
                        });
                });
            }

            // Initial fetch and periodic refresh
            fetchMessages();
            setInterval(fetchMessages, 2000);
        });
    </script>
</body>
</html>
