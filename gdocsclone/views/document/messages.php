<?php
session_start();
require_once __DIR__ . '/../../core/dbConfig.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['document_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$document_id = intval($_GET['document_id']);

// Get document title
$stmt = $pdo->prepare("SELECT title FROM documents WHERE id = ?");
$stmt->execute([$document_id]);
$document = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Chat - <?php echo htmlspecialchars($document['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../public/js/messaging.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    body { font-family: 'Inter', sans-serif; }
    #messages::-webkit-scrollbar {
        width: 6px;
    }
    #messages::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #messages::-webkit-scrollbar-thumb {
        background: #bbf7d0;
        border-radius: 10px;
    }
    #messages::-webkit-scrollbar-thumb:hover {
        background: #34d399;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .message-item {
        animation: slideIn 0.3s ease-out forwards;
    }
    .glass-card {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(8px) saturate(180%);
        border-radius: 1.25rem;
        border: 1px solid rgba(209, 213, 219, 0.3);
    }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-blue-50 to-blue-100 min-h-screen">
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Chat Card -->
        <div class="glass-card shadow-lg rounded-2xl flex flex-col h-[650px]">
            <!-- Chat Header -->
            <div class="flex items-center justify-between border-b border-blue-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center text-white font-bold text-xl shadow">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="font-bold text-blue-700 text-lg leading-tight"><?php echo htmlspecialchars($document['title']); ?></div>
                        <div class="text-xs text-blue-400">Document Chat</div>
                    </div>
                </div>
                <a href="editor.php?id=<?php echo $document_id; ?>" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>

            <!-- Messages List -->
            <div id="messages" class="flex-1 overflow-y-auto px-4 py-6 space-y-4 bg-gradient-to-b from-blue-50/60 to-blue-50/60 rounded-b-xl">
                <!-- Messages will be injected here -->
            </div>

            <!-- Input Bar -->
            <form id="messageForm" class="border-t border-blue-100 px-4 py-3 bg-white/80 rounded-b-2xl flex gap-2 sticky bottom-0 z-10">
                <input type="hidden" name="document_id" value="<?php echo $document_id; ?>">
                <input type="text" 
                       name="message" 
                       id="messageInput" 
                       placeholder="Type your message..." 
                       autocomplete="off" 
                       required
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white shadow-sm">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i> Send
                </button>
            </form>
        </div>
    </main>

    <script>
    function createMessageElement(msg) {
        const isCurrentUser = msg.user_id == <?php echo $_SESSION['user_id']; ?>;
        const align = isCurrentUser ? 'justify-end' : 'justify-start';
        const bubbleColor = isCurrentUser ? 'bg-blue-100 border-blue-200' : 'bg-white border-gray-200';
        const textColor = isCurrentUser ? 'text-blue-800' : 'text-gray-700';
        const avatarBg = isCurrentUser
            ? 'bg-gradient-to-br from-blue-400 to-blue-500'
            : 'bg-gray-200';
        const avatarText = msg.username ? msg.username.charAt(0).toUpperCase() : '?';

        return Object.assign(document.createElement('div'), {
            className: `message-item flex ${align} items-end gap-2`,
            innerHTML: `
                ${!isCurrentUser ? `
                    <div class="w-9 h-9 rounded-full ${avatarBg} flex items-center justify-center text-white font-bold text-base shadow mr-2">
                        ${avatarText}
                    </div>
                ` : ''}
                <div class="max-w-[70%] ${bubbleColor} ${textColor} rounded-2xl px-4 py-2 shadow border relative">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-xs">${msg.username}</span>
                        <span class="text-xs text-blue-400">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                    <div class="break-words text-sm">${msg.message}</div>
                </div>
                ${isCurrentUser ? `
                    <div class="w-9 h-9 rounded-full ${avatarBg} flex items-center justify-center text-white font-bold text-base shadow ml-2">
                        ${avatarText}
                    </div>
                ` : ''}
            `
        });
    }

    // Override the default message display
    const messagesDiv = document.getElementById('messages');
    const originalFetchMessages = window.fetchMessages;
    window.fetchMessages = function() {
        fetch(`../../controllers/MessageController.php?document_id=<?php echo $document_id; ?>`)
            .then(res => res.json())
            .then(data => {
                messagesDiv.innerHTML = '';
                data.forEach(msg => {
                    messagesDiv.appendChild(createMessageElement(msg));
                });
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            });
    };
    </script>
</body>
</html>
