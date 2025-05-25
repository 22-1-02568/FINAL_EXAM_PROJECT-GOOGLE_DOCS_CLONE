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
    $title = $_POST['title'];
    $content = $_POST['content'];
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? htmlspecialchars($doc['title']) : 'New Document'; ?> - GDocs Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        }
        
        #editor:focus {
            box-shadow: 0 0 0 2px #1a73e8, 0 0 0 1px rgba(99, 102, 241, 0.4);
        }
        
        #editor h1, #editor h2, #editor h3 {
            margin: 1.5rem 0 1rem 0;
            font-weight: 600;
        }
        
        #editor h1 { font-size: 2rem; color: #1f2937; }
        #editor h2 { font-size: 1.5rem; color: #374151; }
        #editor h3 { font-size: 1.25rem; color: #4b5563; }
        
        #editor p { margin: 1rem 0; }
        #editor ul, #editor ol { margin: 1rem 0; padding-left: 2rem; }
        #editor li { margin: 0.5rem 0; }
        
        /* Toolbar Styles */
        .toolbar {
            position: static;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            background: white;
            margin-bottom: 0;
            padding: 1rem 0.5rem;
            min-width: 56px;
            align-items: center;
        }
        
        .tool-btn {
            width: 44px;
            height: 44px;
            justify-content: center;
            margin: 0;
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
        
        /* File upload area */
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: #1a73e8;
            background: #f8fafc;
        }
        
        .file-upload-area.dragover {
            border-color: #1a73e8;
            background: #eef2ff;
        }
        
        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-white min-h-screen">
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Success Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-blue-100 text-blue-800 rounded-lg shadow-sm fade-in flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Document Form -->
        <form method="POST" id="docForm" enctype="multipart/form-data" class="fade-in">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex-1">
                    <input type="text" name="title" id="documentTitle" 
                           value="<?php echo htmlspecialchars($doc['title']); ?>" 
                           placeholder="Untitled Document"
                           class="w-full text-4xl font-bold border-none bg-transparent focus:outline-none text-gray-900 placeholder-gray-400"
                           style="font-family: 'Inter', sans-serif;">
                </div>
                <div class="flex items-center gap-3 mt-2 sm:mt-0 sm:ml-4">
                    <span id="saveStatus" class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-circle text-blue-400 mr-1" style="font-size: 0.5rem;"></i>
                        All changes saved
                    </span>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $isEdit ? 'Update Document' : 'Save Document'; ?>
                    </button>
                </div>
            </div>

            <div class="flex gap-6">
                <!-- Sidebar Toolbar (left) -->
                <div class="toolbar flex flex-col gap-2 p-3 min-w-[56px] items-center bg-white rounded-xl shadow-lg h-fit sticky top-8">
                    <!-- Text Formatting -->
                    <button type="button" class="tool-btn" onclick="format('bold')" title="Bold (Ctrl+B)">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('italic')" title="Italic (Ctrl+I)">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('underline')" title="Underline (Ctrl+U)">
                        <i class="fas fa-underline"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('strikeThrough')" title="Strikethrough">
                        <i class="fas fa-strikethrough"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Headings -->
                    <button type="button" class="tool-btn" onclick="formatBlock('H1')" title="Heading 1">H1</button>
                    <button type="button" class="tool-btn" onclick="formatBlock('H2')" title="Heading 2">H2</button>
                    <button type="button" class="tool-btn" onclick="formatBlock('H3')" title="Heading 3">H3</button>
                    <button type="button" class="tool-btn" onclick="formatBlock('P')" title="Paragraph">
                        <i class="fas fa-paragraph"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Lists -->
                    <button type="button" class="tool-btn" onclick="format('insertUnorderedList')" title="Bullet List">
                        <i class="fas fa-list-ul"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('insertOrderedList')" title="Numbered List">
                        <i class="fas fa-list-ol"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Alignment -->
                    <button type="button" class="tool-btn" onclick="format('justifyLeft')" title="Align Left">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('justifyCenter')" title="Align Center">
                        <i class="fas fa-align-center"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('justifyRight')" title="Align Right">
                        <i class="fas fa-align-right"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Other actions -->
                    <button type="button" class="tool-btn" onclick="format('createLink')" title="Insert Link">
                        <i class="fas fa-link"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('unlink')" title="Remove Link">
                        <i class="fas fa-unlink"></i>
                    </button>
                </div>

                <!-- Editor Area (center) -->
                <div class="flex-1">
                    <div class="bg-white rounded-xl shadow-lg mb-6">
                        <div id="editor" contenteditable="true"><?php echo htmlspecialchars($doc['content']); ?></div>
                    </div>
                    <input type="hidden" name="content" id="contentInput">

                    <!-- Share Document section BELOW the editor area -->
                    <?php if ($isEdit): ?>
                    <div class="bg-white-800 rounded-xl text-blue-100 shadow-lg p-6 mb-8 mt-4">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-share-alt text-blue-600 mr-3"></i>
                            Share Document
                        </h3>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <input type="text" id="userSearch" 
                                   placeholder="Search users by username or email..." 
                                   autocomplete="off"
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <button type="button" onclick="shareDocument()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-200 flex items-center">
                                <i class="fas fa-paper-plane mr-2"></i> Share
                            </button>
                        </div>
                        <div id="userResults" class="mt-4"></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Chat & Activity Sidebar (right) -->
                <?php if ($isEdit): ?>
                <aside class="flex flex-col gap-4 w-48 min-w-[140px] max-w-xs h-fit sticky top-8">
                    <a href="messages.php?document_id=<?php echo $_GET['id']; ?>" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-comments mr-2"></i> Chat
                    </a>
                    <a href="activity.php?document_id=<?php echo $_GET['id']; ?>" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-history mr-2"></i> Activity
                    </a>
                </aside>
                <?php endif; ?>
            </div>
        </form>

    </main>
=======
    </style>
</head>
<body class="bg-white min-h-screen">
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Success Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-blue-100 text-blue-800 rounded-lg shadow-sm fade-in flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Document Form -->
        <form method="POST" id="docForm" enctype="multipart/form-data" class="fade-in">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex-1">
                    <input type="text" name="title" id="documentTitle" 
                           value="<?php echo htmlspecialchars($doc['title']); ?>" 
                           placeholder="Untitled Document"
                           class="w-full text-4xl font-bold border-none bg-transparent focus:outline-none text-gray-900 placeholder-gray-400"
                           style="font-family: 'Inter', sans-serif;">
                </div>
                <div class="flex items-center gap-3 mt-2 sm:mt-0 sm:ml-4">
                    <span id="saveStatus" class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-circle text-blue-400 mr-1" style="font-size: 0.5rem;"></i>
                        All changes saved
                    </span>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $isEdit ? 'Update Document' : 'Save Document'; ?>
                    </button>
                </div>
            </div>

            <div class="flex gap-6">
                <!-- Sidebar Toolbar (left) -->
                <div class="toolbar flex flex-col gap-2 p-3 min-w-[56px] items-center bg-white rounded-xl shadow-lg h-fit sticky top-8">
                    <!-- Text Formatting -->
                    <button type="button" class="tool-btn" onclick="format('bold')" title="Bold (Ctrl+B)">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('italic')" title="Italic (Ctrl+I)">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('underline')" title="Underline (Ctrl+U)">
                        <i class="fas fa-underline"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('strikeThrough')" title="Strikethrough">
                        <i class="fas fa-strikethrough"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Headings -->
                    <button type="button" class="tool-btn" onclick="formatBlock('H1')" title="Heading 1">H1</button>
                    <button type="button" class="tool-btn" onclick="formatBlock('H2')" title="Heading 2">H2</button>
                    <button type="button" class="tool-btn" onclick="formatBlock('H3')" title="Heading 3">H3</button>
                    <button type="button" class="tool-btn" onclick="formatBlock('P')" title="Paragraph">
                        <i class="fas fa-paragraph"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Lists -->
                    <button type="button" class="tool-btn" onclick="format('insertUnorderedList')" title="Bullet List">
                        <i class="fas fa-list-ul"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('insertOrderedList')" title="Numbered List">
                        <i class="fas fa-list-ol"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Alignment -->
                    <button type="button" class="tool-btn" onclick="format('justifyLeft')" title="Align Left">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('justifyCenter')" title="Align Center">
                        <i class="fas fa-align-center"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('justifyRight')" title="Align Right">
                        <i class="fas fa-align-right"></i>
                    </button>
                    <hr class="my-2 w-8 border-gray-200">
                    <!-- Other actions -->
                    <button type="button" class="tool-btn" onclick="format('createLink')" title="Insert Link">
                        <i class="fas fa-link"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="format('unlink')" title="Remove Link">
                        <i class="fas fa-unlink"></i>
                    </button>
                </div>

                <!-- Editor Area (center) -->
                <div class="flex-1">
                    <div class="bg-white rounded-xl shadow-lg mb-6">
                        <div id="editor" contenteditable="true"><?php echo htmlspecialchars($doc['content']); ?></div>
                    </div>
                    <input type="hidden" name="content" id="contentInput">

                    <!-- Share Document section BELOW the editor area -->
                    <?php if ($isEdit): ?>
                    <div class="bg-blue-800 rounded-xl text-blue-100 shadow-lg p-6 mb-8 mt-4">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-share-alt text-blue-600 mr-3"></i>
                            Share Document
                        </h3>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <input type="text" id="userSearch" 
                                   placeholder="Search users by username or email..." 
                                   autocomplete="off"
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <button type="button" onclick="shareDocument()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-200 flex items-center">
                                <i class="fas fa-paper-plane mr-2"></i> Share
                            </button>
                        </div>
                        <div id="userResults" class="mt-4"></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Chat & Activity Sidebar (right) -->
                <?php if ($isEdit): ?>
                <aside class="flex flex-col gap-4 w-48 min-w-[140px] max-w-xs h-fit sticky top-8">
                    <a href="messages.php?document_id=<?php echo $_GET['id']; ?>" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-comments mr-2"></i> Chat
                    </a>
                    <a href="activity.php?document_id=<?php echo $_GET['id']; ?>" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-history mr-2"></i> Activity
                    </a>
                </aside>
                <?php endif; ?>
            </div>
        </form>

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
                } else {
                    button.classList.remove('active');
                }
            }
        });
    }

    // Form submission handler
    document.getElementById('docForm').addEventListener('submit', function(e) {
        const content = document.getElementById('editor').innerHTML;
        document.getElementById('contentInput').value = content;
    });

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
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
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
    document.addEventListener('DOMContentLoaded', function() {
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
    });

    // Share document function
    function shareDocument() {
        const userSearch = document.getElementById('userSearch').value;
        if (userSearch.trim()) {
            // Add sharing logic here
            alert('Sharing feature would be implemented here');
        }
    }
    </script>
</body>
</html>