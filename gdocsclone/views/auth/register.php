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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - GoogleDocsClone</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #fff;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .register-container {
            width: 360px;
            padding: 40px 40px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-radius: 8px;
            text-align: center;
        }
        .logo {
            font-size: 48px;
            color: #4285f4;
            font-weight: 700;
            margin-bottom: 24px;
            user-select: none;
        }
        h1 {
            font-weight: 400;
            font-size: 24px;
            margin-bottom: 24px;
            color: #202124;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            height: 44px;
            font-size: 16px;
            padding: 0 12px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            outline: none;
            transition: border-color 0.2s ease;
            width: 100%;
            box-sizing: border-box;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: #4285f4;
            box-shadow: 0 0 3px #4285f4;
        }
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #5f6368;
            font-size: 18px;
        }
        .error-message {
            color: #d93025;
            font-size: 14px;
            margin-bottom: 12px;
            text-align: left;
        }
        button[type="submit"] {
            background-color: #1a73e8;
            color: white;
            font-weight: 500;
            height: 44px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s ease;
        }
        button[type="submit"]:hover {
            background-color: #1669c1;
        }
        .footer-text {
            margin-top: 24px;
            font-size: 14px;
            color: #5f6368;
        }
        .footer-text a {
            color: #1a73e8;
            text-decoration: none;
        }
        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container" role="main">
        <div class="logo" aria-label="Google Docs Clone">GDocs</div>
        <h1>Create your Google Docs account</h1>
        <?php if ($message): ?>
            <div class="error-message" role="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" novalidate>
            <input type="text" name="username" placeholder="Username" required autofocus autocomplete="username" />
            <input type="email" name="email" placeholder="Email" required autocomplete="email" />
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Create a password" required autocomplete="new-password" />
                <button type="button" class="toggle-password" aria-label="Toggle password visibility" onclick="togglePassword()">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
            <select name="role" required>
                <option value="user">Regular User</option>
                <option value="admin">Administrator</option>
            </select>
            <button type="submit">Create account</button>
        </form>
        <div class="footer-text">
            Already have an account? <a href="login.php">Sign in</a>
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
    </script>
</body>
</html>