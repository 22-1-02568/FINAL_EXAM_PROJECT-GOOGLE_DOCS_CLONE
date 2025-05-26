<?php
session_start();
require_once __DIR__ . '/../../core/dbConfig.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../models/Document.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['document_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$document_id = intval($_GET['document_id']);
$activityLogModel = new ActivityLog($pdo);
$documentModel = new Document($pdo);

// Get document info
$document = $documentModel->getById($document_id);
$logs = $activityLogModel->getByDocument($document_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - <?php echo htmlspecialchars($document['title']); ?> - GDocs Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px) saturate(180%);
            border-radius: 1.25rem;
            border: 1px solid rgba(209, 213, 219, 0.3);
        }
        .timeline-dot {
            background: #4285f4;
        }
        .timeline-dot.group-hover {
            background: #3367d6;
        }
        .timeline-line {
            background: #bbdefb;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-blue-50 to-blue-100 min-h-screen">
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Quick Stats -->
        <?php if ($logs): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card shadow-lg p-6 text-center">
                <i class="fas fa-edit text-blue-500 text-3xl mb-3"></i>
                <h4 class="text-2xl font-bold text-blue-700"><?php echo count($logs); ?></h4>
                <p class="text-blue-600">Total Activities</p>
            </div>
            <div class="glass-card shadow-lg p-6 text-center">
                <i class="fas fa-users text-blue-500 text-3xl mb-3"></i>
                <h4 class="text-2xl font-bold text-blue-700"><?php echo count(array_unique(array_column($logs, 'username'))); ?></h4>
                <p class="text-blue-600">Contributors</p>
            </div>
            <div class="glass-card shadow-lg p-6 text-center">
                <i class="fas fa-calendar text-blue-400 text-3xl mb-3"></i>
                <h4 class="text-2xl font-bold text-blue-700"><?php echo date('M j', strtotime($logs[0]['created_at'])); ?></h4>
                <p class="text-blue-600">Last Activity</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Document Header -->
        <div class="glass-card shadow-lg p-6 mb-8 flex flex-col md:flex-row md:items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-blue-700 mb-2">Activity Log</h2>
                <p class="text-blue-600 flex items-center">
                    <i class="fas fa-file-alt text-blue-500 mr-2"></i>
                    <?php echo htmlspecialchars($document['title'] ?: 'Untitled Document'); ?>
                </p>
            </div>
            <div class="flex items-center space-x-3 mt-4 md:mt-0">
                <a href="editor.php?id=<?php echo $document_id; ?>" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow transition flex items-center">
                    <i class="fas fa-edit mr-2"></i> Back to Document
                </a>
                <a href="messages.php?document_id=<?php echo $document_id; ?>" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow transition flex items-center">
                    <i class="fas fa-comments mr-2"></i> Open Chat
                </a>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="glass-card shadow-lg p-0 md:p-6">
            <h3 class="text-xl font-semibold text-blue-700 mb-6 flex items-center px-6 pt-6">
                <i class="fas fa-history text-blue-500 mr-3"></i>
                Document History
            </h3>
            <?php if ($logs): ?>
            <div class="relative px-6 pb-6">
                <!-- Timeline line -->
                <div class="absolute left-8 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-200 to-blue-400 rounded-full"></div>
                <div class="space-y-8">
                    <?php foreach ($logs as $index => $log): ?>
                    <div class="relative flex items-start group">
                        <!-- Timeline dot -->
                        <div class="flex-shrink-0 w-6 h-6 rounded-full border-4 border-white shadow-md z-10 bg-gradient-to-br from-blue-400 to-blue-800 flex items-center justify-center">
                            <span class="text-white font-bold text-xs">
                                <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                            </span>
                        </div>
                        <!-- Activity content -->
                        <div class="ml-8 flex-1 bg-white/90 rounded-xl p-4 shadow hover:shadow-md transition-shadow duration-200 border border-blue-100">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-blue-800"><?php echo htmlspecialchars($log['username']); ?></span>
                                    <?php
                                    $action = htmlspecialchars($log['action']);
                                    $icon = 'fas fa-edit text-blue-500';
                                    if (strpos($action, 'created') !== false) {
                                        $icon = 'fas fa-plus-circle text-blue-600';
                                    } elseif (strpos($action, 'deleted') !== false) {
                                        $icon = 'fas fa-trash text-red-600';
                                    } elseif (strpos($action, 'shared') !== false) {
                                        $icon = 'fas fa-share-alt text-blue-600';
                                    } elseif (strpos($action, 'title') !== false) {
                                        $icon = 'fas fa-heading text-blue-400';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-50 text-blue-600 ml-2">
                                        <i class="<?php echo $icon; ?> mr-1"></i>
                                        <?php echo ucfirst(explode(' ', $action)[0]); ?>
                                    </span>
                                </div>
                                <time class="text-xs text-blue-400 flex items-center">
                                    <i class="far fa-clock mr-1"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                </time>
                            </div>
                            <div class="text-blue-700">
                                <?php echo $action; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-history text-blue-200 text-6xl mb-4"></i>
                <h4 class="text-xl font-medium text-blue-500 mb-2">No Activity Yet</h4>
                <p class="text-blue-400">Document activity will appear here as changes are made.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate timeline items
        const timelineItems = document.querySelectorAll('.space-y-6 > div');
        timelineItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, 100 + (index * 100));
        });
        
        // Animate stats cards
        const statsCards = document.querySelectorAll('.grid > div');
        statsCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 300 + (index * 100));
        });
    });
    </script>
</body>
</html>