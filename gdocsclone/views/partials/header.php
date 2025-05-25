<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #FFFFFF;
    }
    
    /* Custom Animations */
    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #c7d2fe;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #818cf8;
    }
    
    /* Dropdown Menu Animation */
    .dropdown-content {
        visibility: hidden;
        opacity: 0;
        transform: translateY(-10px);
        transition: visibility 0s, opacity 0.2s, transform 0.2s;
    }
    
    .dropdown:hover .dropdown-content {
        visibility: visible;
        opacity: 1;
        transform: translateY(0);
    }
</style>

<header class="w-full bg-white shadow-md border-b border-gray-300 z-30">
    <div class="max-w-full mx-auto flex items-center justify-between px-8 py-4 rounded-b-3xl bg-white backdrop-blur-md" style="box-shadow:0 2px 4px 0 rgba(0,0,0,0.1);">
        <!-- Brand Left -->
        <div class="flex-1 flex items-center justify-start">
            <span class="font-extrabold text-2xl text-gray-900 tracking-wide select-none">GDocs</span>
        </div>
        <!-- Dashboard Button & User Dropdown Right -->
        <div class="flex items-center gap-4">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../admin/dashboard.php" class="flex items-center gap-2 px-6 py-2 rounded-full bg-blue-100 text-blue-700 font-semibold shadow hover:bg-blue-200 transition text-lg">
                        <i class="fa-solid fa-home"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="../user/dashboard.php" class="flex items-center gap-2 px-6 py-2 rounded-full bg-blue-100 text-blue-700 font-semibold shadow hover:bg-blue-200 transition text-lg">
                        <i class="fa-solid fa-home"></i> Dashboard
                    </a>
                <?php endif; ?>
                <div class="relative dropdown">
                    <button class="flex items-center gap-2 px-6 py-2 rounded-full bg-blue-700 text-white font-semibold shadow hover:bg-blue-800 focus:outline-none text-lg">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['role']); ?>
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </button>
                    <div class="dropdown-content absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg py-2 z-20 border border-blue-700">
                        <div class="px-4 py-2 text-xs text-gray-500 border-b bg-white border-gray-100">
                            <span>Signed in as</span>
                            <p class="font-semibold text-blue-700"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                        </div>
                        <a href="../auth/logout.php" class="block px-4 py-2 text-sm text-blue-700 bg-white hover:bg-blue-50 hover:text-red-700 rounded-b-xl transition">
                            <i class="fa-solid fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="../auth/login.php" class="px-6 py-2 rounded-full bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200 transition text-lg">
                    Login
                </a>
                <a href="../auth/register.php" class="px-6 py-2 rounded-full bg-blue-600 text-white font-semibold shadow hover:bg-blue-700 transition text-lg">
                    Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdown = document.querySelector('.dropdown');
        if (dropdown) {
            dropdown.addEventListener('click', function(e) {
                const dropdownContent = this.querySelector('.dropdown-content');
                if (dropdownContent.style.visibility === 'visible') {
                    dropdownContent.style.visibility = 'hidden';
                    dropdownContent.style.opacity = '0';
                    dropdownContent.style.transform = 'translateY(-10px)';
                } else {
                    dropdownContent.style.visibility = 'visible';
                    dropdownContent.style.opacity = '1';
                    dropdownContent.style.transform = 'translateY(0)';
                }
            });
        }
    });
</script>
