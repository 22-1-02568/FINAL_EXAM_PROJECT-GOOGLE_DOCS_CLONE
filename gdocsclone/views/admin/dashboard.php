<?php
session_start();
require_once __DIR__ . '/../../core/dbConfig.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, role, suspended, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Get statistics
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$suspendedCount = $pdo->query("SELECT COUNT(*) FROM users WHERE suspended = 1")->fetchColumn();
$documentCount = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GDocs Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .stat-card {
            background: linear-gradient(135deg, #8ab4f8 0%, #4285f4 100%);
        }
        .stat-card-2 {
            background: linear-gradient(135deg, #669df6 0%, #3367d6 100%);
        }
        .stat-card-3 {
            background: linear-gradient(135deg, #aecbfa 0%, #8ab4f8 100%);
        }
        .stat-card-4 {
            background: linear-gradient(135deg, #fcd34d 0%, #fbbf24 100%);
        }
        .glass-effect {
            backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.75);
            border-radius: 12px;
            border: 1px solid rgba(209, 213, 219, 0.3);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #bbdefb;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #8ab4f8;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200 min-h-screen">
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Page Header (always on top) -->
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-blue-700 mb-2 flex items-center justify-center gap-2">
                <i class="fas fa-tachometer-alt text-blue-500"></i>
                Admin Dashboard
            </h1>
            <p class="text-blue-500 text-lg">Manage users and monitor system activity</p>
        </div>

        <!-- Flex row: Sidebar + Main Content -->
        <div class="flex flex-row gap-8 items-start">
            <!-- Sidebar Statistics -->
            <aside class="w-64 flex-shrink-0 flex flex-col gap-4">
                <div class="stat-card rounded-xl p-4 text-white shadow flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-full">
                        <i class="fas fa-users text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-white/80">Total Users</p>
                        <p class="text-xl font-bold"><?php echo $userCount; ?></p>
                    </div>
                </div>
                <div class="stat-card-2 rounded-xl p-4 text-white shadow flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-full">
                        <i class="fas fa-user-shield text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-white/80">Administrators</p>
                        <p class="text-xl font-bold"><?php echo $adminCount; ?></p>
                    </div>
                </div>
                <div class="stat-card-3 rounded-xl p-4 text-white shadow flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-full">
                        <i class="fas fa-file-alt text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-white/80">Documents</p>
                        <p class="text-xl font-bold"><?php echo $documentCount; ?></p>
                    </div>
                </div>
                <div class="stat-card-4 rounded-xl p-4 text-white shadow flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-full">
                        <i class="fas fa-user-slash text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-white/80">Suspended</p>
                        <p class="text-xl font-bold"><?php echo $suspendedCount; ?></p>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col gap-8">
                <!-- Users Management Section -->
                <div class="glass-effect p-6 shadow-2xl mb-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                        <h2 class="text-2xl font-bold text-blue-700 mb-4 sm:mb-0">
                            <i class="fas fa-users-cog mr-2 text-blue-500"></i>User Management
                        </h2>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <input type="text" placeholder="Search users..." id="userSearch" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 font-medium">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                        </div>
                    </div>
                    
                    <!-- User Management Table -->
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full border-separate border-spacing-y-3">
                            <thead>
                                <tr>
                                    <th class="text-left px-6 py-3 font-semibold text-blue-700 bg-transparent">User</th>
                                    <th class="text-left px-6 py-3 font-semibold text-blue-700 bg-transparent">Email</th>
                                    <th class="text-left px-6 py-3 font-semibold text-blue-700 bg-transparent">Role</th>
                                    <th class="text-left px-6 py-3 font-semibold text-blue-700 bg-transparent">Status</th>
                                    <th class="text-left px-6 py-3 font-semibold text-blue-700 bg-transparent">Joined</th>
                                    <th class="text-left px-6 py-3 font-semibold text-blue-700 bg-transparent">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr class="bg-white/80 hover:bg-blue-50/80 transition rounded-xl shadow group">
                                    <td class="py-4 px-6 rounded-l-xl">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm shadow">
                                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-blue-800"><?php echo htmlspecialchars($user['username']); ?></p>
                                                <p class="text-xs text-blue-400">ID: <?php echo $user['id']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-blue-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                            <?php echo $user['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <i class="fas fa-crown mr-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-user mr-1"></i>
                                            <?php endif; ?>
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                            <?php echo $user['suspended'] ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php if ($user['suspended']): ?>
                                                <i class="fas fa-ban mr-1"></i>Suspended
                                            <?php else: ?>
                                                <i class="fas fa-check mr-1"></i>Active
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-blue-600 text-xs"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="py-4 px-6 rounded-r-xl">
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <form method="POST" action="../../controllers/UserController.php" class="inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="suspended" value="<?php echo $user['suspended'] ? '0' : '1'; ?>"
                                                        class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-colors duration-200
                                                        <?php echo $user['suspended'] ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'; ?>">
                                                    <?php if ($user['suspended']): ?>
                                                        <i class="fas fa-unlock mr-1"></i>Unsuspend
                                                    <?php else: ?>
                                                        <i class="fas fa-ban mr-1"></i>Suspend
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-blue-300 text-xs italic">Protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="glass-effect p-6 shadow-2xl">
                    <h2 class="text-2xl font-bold text-blue-700 mb-6">
                        <i class="fas fa-file-alt mr-2 text-blue-500"></i>All Documents
                    </h2>
                    
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-4 px-6 font-semibold text-blue-700">Document</th>
                                    <th class="text-left py-4 px-6 font-semibold text-blue-700">Owner</th>
                                    <th class="text-left py-4 px-6 font-semibold text-blue-700">Last Updated</th>
                                    <th class="text-left py-4 px-6 font-semibold text-blue-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require_once __DIR__ . '/../../models/Document.php';
                                $documentModel = new Document($pdo);
                                $docs = $documentModel->getAll();
                                foreach ($docs as $doc): ?>
                                <tr class="border-b border-gray-100 hover:bg-blue-50/50 transition-colors duration-200">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg flex items-center justify-center text-white mr-3">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-blue-800"><?php echo htmlspecialchars($doc['title']); ?></p>
                                                <p class="text-sm text-blue-400">ID: <?php echo $doc['id']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xs mr-2">
                                                <?php echo strtoupper(substr($doc['username'], 0, 2)); ?>
                                            </div>
                                            <span class="text-blue-800"><?php echo htmlspecialchars($doc['username']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-blue-600 text-sm"><?php echo date('M j, Y \a\t g:i A', strtotime($doc['updated_at'])); ?></td>
                                    <td class="py-4 px-6">
                                        <a href="../document/editor.php?id=<?php echo $doc['id']; ?>"
                                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple search functionality
        document.getElementById('userSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>