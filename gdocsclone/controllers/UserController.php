<?php
session_start();
require_once __DIR__ . '/../core/dbConfig.php';

// Only admins can suspend users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../views/auth/login.php');
        exit;
    }
    $userId = $_POST['user_id'];
    $suspended = isset($_POST['suspended']) ? intval($_POST['suspended']) : 0;
    $stmt = $pdo->prepare("UPDATE users SET suspended = ? WHERE id = ? AND role != 'admin'");
    $stmt->execute([$suspended, $userId]);
    header('Location: ../views/admin/dashboard.php');
    exit;
}

// Anyone logged in can search for users to share documents
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../views/auth/login.php');
        exit;
    }
    $search = '%' . $_GET['search'] . '%';

    // Exclude current user and already-shared users
    $exclude = [];
    if (isset($_GET['document_id'])) {
        $stmt = $pdo->prepare("SELECT user_id FROM document_users WHERE document_id = ?");
        $stmt->execute([$_GET['document_id']]);
        $exclude = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    $exclude[] = $_SESSION['user_id'];

    $sql = "SELECT id, username, email FROM users WHERE (username LIKE ? OR email LIKE ?)";
    $params = [$search, $search];

    if ($exclude) {
        $excludePlaceholders = implode(',', array_fill(0, count($exclude), '?'));
        $sql .= " AND id NOT IN ($excludePlaceholders)";
        $params = array_merge($params, $exclude);
    }

    $sql .= " LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}
?>