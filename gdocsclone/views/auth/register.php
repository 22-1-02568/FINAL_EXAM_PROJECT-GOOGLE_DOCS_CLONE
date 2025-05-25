<?php
require_once __DIR__ . '/../../core/dbConfig.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController($pdo);
    $role = $_POST['role'] ?? 'user';
    $result = $auth->register($_POST['username'], $_POST['password'], $_POST['email'], $role);
    
    if (isset($result['success'])) {
        header('Location: login.php?registered=1');
        exit;
    } else {
        $message = $result['error'];
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GoogleDocsClone</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #FFFFFF; }
        .login-bg {
            background: #FFFFFF;
        }
        .glass-effect {
            backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            border: 1px solid rgba(209, 213, 219, 0.3);
        }
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        .shape {
            position: absolute;
            opacity: 0.08;
            animation: float 6s ease-in-out infinite;
        }
        .shape:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        .shape:nth-child(2) {
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        .shape:nth-child(3) {
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        .input-focus:focus {
            border-color: #1a73e8; /* Google Docs blue */
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape w-32 h-32 bg-blue-200 rounded-full"></div>
        <div class="shape w-24 h-24 bg-blue-300 rounded-full"></div>
        <div class="shape w-16 h-16 bg-blue-100 rounded-full"></div>
    </div>

    <div class="glass-effect p-8 md:p-12 w-full max-w-md shadow-2xl relative z-10">
        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full mb-4 shadow">
                <i class="fas fa-user-plus text-2xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-blue-700 mb-2">Create Account</h1>
            <p class="text-blue-500">Join GoogleDocsClone to start collaborating</p>
        </div>

        <!-- Error Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    <p class="text-red-700 text-sm"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Register Form -->
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-blue-700 mb-2">
                    <i class="fas fa-user mr-2"></i>Username
                </label>
                <input type="text" name="username" required 
                    class="input-focus w-full px-4 py-3 border border-gray-300 text-blue-800 rounded-lg focus:outline-none transition-all duration-200"
                    placeholder="Choose a username">
            </div>

            <div>
                <label class="block text-sm font-semibold  text-blue-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email
                </label>
                <input type="email" name="email" required 
                    class="input-focus w-full px-4 py-3 border text-blue-800 border-gray-300 text-blue-800 rounded-lg focus:outline-none transition-all duration-200"
                    placeholder="Enter your email">
            </div>

            <div>
                <label class="block text-sm font-semibold text-blue-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <div class="relative">
                    <input type="password" name="password" required id="password"
                        class="input-focus w-full px-4 py-3 border text-blue-800 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 pr-12"
                        placeholder="Create a strong password">
                    <button type="button" onclick="togglePassword()" 
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-400 hover:text-blue-600">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-blue-700 mb-2">
                    <i class="fas fa-user-shield mr-2"></i>Account Type
                </label>
                <select name="role" required 
                    class="input-focus w-full px-4 py-3 border border-gray-300 text-blue-800 rounded-lg focus:outline-none transition-all duration-200">
                    <option value="user">Regular User</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <button type="submit" 
                class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-4 rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                <i class="fas fa-user-plus mr-2"></i>Create Account
            </button>
        </form>

        <!-- Login Link -->
        <div class="mt-8 text-center">
            <p class="text-blue-500">
                Already have an account? 
                <a href="login.php" class="text-blue-700 hover:text-blue-600 font-semibold">
                    Sign in here
                </a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form animation
        window.addEventListener('load', function() {
            const form = document.querySelector('.glass-effect');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                form.style.transition = 'all 0.6s ease';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>