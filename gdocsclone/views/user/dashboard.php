<?php
session_start();
require_once __DIR__ . '/../../core/dbConfig.php';
require_once __DIR__ . '/../../models/Document.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$documentModel = new Document($pdo);
$owned = $documentModel->getByUser($_SESSION['user_id']);
$shared = $documentModel->getSharedWith($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GDocs Clone</title>
    <link rel="stylesheet" href="dashboard_admin_styles.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 px-2 md:px-8 py-8">
        <!-- Centered Welcome -->
        <div class="w-full flex flex-col items-center mb-10">
            <h2 class="text-4xl font-extrabold text-blue-800 mb-2 text-center">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h2>
            <p class="text-blue-400 text-lg text-center">Ready to create or collaborate? Your documents are below.</p>
        </div>

        <!-- 3 Cards in a Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- My Documents Card -->
            <div class="bg-white rounded-3xl shadow-xl p-6 flex flex-col min-h-[340px]">
                <h3 class="text-2xl font-bold text-blue-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-folder-open"></i> My Documents
                </h3>
                <div class="flex-1 flex flex-col gap-4 overflow-y-auto">
                    <?php if (empty($owned)): ?>
                        <div class="rounded-xl shadow flex items-center justify-center text-blue-500 font-semibold text-lg h-32">
                            You haven't created any documents yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($owned as $doc): ?>
                            <div class="bg-gradient-to-br from-blue-400 to-blue-100 rounded-xl shadow p-4 flex flex-col justify-between h-32 group transition-transform hover:scale-105">
                                <a href="../document/editor.php?id=<?php echo $doc['id']; ?>" class="flex-1 flex flex-col justify-between">
                                    <div class="text-lg font-bold text-blue-800 group-hover:text-yellow-200 truncate">
                                        <?php echo htmlspecialchars($doc['title'] ?: 'Untitled Document'); ?>
                                    </div>
                                    <div class="mt-2 text-xs text-blue-800">
                                        <i class="far fa-clock mr-1"></i> <?php echo date('M j, Y g:i A', strtotime($doc['updated_at'])); ?>
                                    </div>
                                </a>
                                <div class="flex items-center space-x-2 mt-2">
                                    <a href="../document/editor.php?id=<?php echo $doc['id']; ?>" class="text-white hover:text-yellow-200 p-1 rounded-full" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../document/activity.php?document_id=<?php echo $doc['id']; ?>" class="text-white hover:text-yellow-200 p-1 rounded-full" title="View Activity">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <form method="POST" action="../../controllers/DocumentController.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $doc['id']; ?>">
                                        <button type="submit" class="text-white hover:text-red-200 p-1 rounded-full" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Shared With Me Card -->
            <div class="bg-white rounded-3xl shadow-xl p-6 flex flex-col min-h-[340px]">
                <h3 class="text-2xl font-bold text-blue-700 flex items-center gap-2 mb-4">
                    <i class="fas fa-share-alt"></i> Shared with Me
                </h3>
                <div class="flex-1 flex flex-col gap-4 overflow-y-auto">
                    <?php if (empty($shared)): ?>
                        <div class="rounded-xl shadow flex items-center justify-center text-blue-400 font-semibold text-lg h-32">
                            No documents have been shared with you yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($shared as $doc): ?>
                            <div class="bg-gradient-to-br from-blue-600 to-blue-200 rounded-xl shadow p-4 flex flex-col justify-between h-32 group transition-transform hover:scale-105">
                                <a href="../document/editor.php?id=<?php echo $doc['id']; ?>" class="flex-1 flex flex-col justify-between">
                                    <div class="text-lg font-bold text-white group-hover:text-yellow-200 truncate">
                                        <?php echo htmlspecialchars($doc['title'] ?: 'Untitled Document'); ?>
                                    </div>
                                    <div class="mt-2 text-xs text-blue-100">
                                        <i class="far fa-clock mr-1"></i> <?php echo date('M j, Y g:i A', strtotime($doc['updated_at'])); ?>
                                    </div>
                                </a>
                                <div class="flex items-center space-x-2 mt-2">
                                    <a href="../document/editor.php?id=<?php echo $doc['id']; ?>" class="text-white hover:text-yellow-200 p-1 rounded-full" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../document/activity.php?document_id=<?php echo $doc['id']; ?>" class="text-white hover:text-yellow-200 p-1 rounded-full" title="View Activity">
                                        <i class="fas fa-history"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create New Document Card -->
            <div class="bg-white rounded-3xl shadow-xl p-6 flex flex-col items-center justify-center min-h-[340px]">
                <h3 class="text-2xl font-bold text-blue-700 flex items-center gap-2 mb-4">
                    <i class="fas fa-plus-circle"></i> Create New
                </h3>
                <a href="../document/editor.php"
                   class="flex items-center justify-center bg-gradient-to-r from-blue-600 to-blue-200 hover:from-blue-200 hover:to-blue-600 text-white px-8 py-4 rounded-2xl shadow-lg text-lg font-semibold transition-all duration-200 mt-4">
                    <i class="fas fa-plus mr-3"></i> Create New Document
                </a>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade-in animation to the document items
        const items = document.querySelectorAll('.divide-y > div');
        items.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 100 + (index * 50)); // Staggered animation
        });
    });
    </script>
</body>
</html>
