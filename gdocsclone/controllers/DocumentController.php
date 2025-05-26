<?php
session_start();
require_once __DIR__ . '/../core/dbConfig.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/ActivityLog.php';

$documentModel = new Document($pdo);
$activityLogModel = new ActivityLog($pdo);

// Handle autosave AJAX request
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_GET['id'], $_POST['autosave']) &&
    isset($_SESSION['user_id'])
) {
    $doc = $documentModel->getById($_GET['id']);
    if ($doc && $doc['user_id'] == $_SESSION['user_id']) {
        $title = $_POST['title'] ?? $doc['title'];
        $content = $_POST['content'] ?? $doc['content'];
        $documentModel->update($_GET['id'], $title, $content);
        $activityLogModel->log($_GET['id'], $_SESSION['user_id'], 'edited the document');
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// Handle document deletion
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_id']) &&
    isset($_SESSION['user_id'])
) {
    $doc = $documentModel->getById($_POST['delete_id']);
    if ($doc && $doc['user_id'] == $_SESSION['user_id']) {
        $documentModel->delete($_POST['delete_id']);
        $activityLogModel->log($_POST['delete_id'], $_SESSION['user_id'], 'deleted the document');
        header('Location: ../views/user/dashboard.php');
        exit;
    } else {
        echo "Unauthorized or document not found.";
        exit;
    }
}

// Handle adding a user to a document
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_user'], $_POST['user_id'], $_POST['document_id']) &&
    isset($_SESSION['user_id'])
) {
    $doc = $documentModel->getById($_POST['document_id']);
    // Only allow owner to share
    if ($doc && $doc['user_id'] == $_SESSION['user_id']) {
        $documentModel->addUser($_POST['document_id'], $_POST['user_id'], 1);
        $activityLogModel->log($_POST['document_id'], $_SESSION['user_id'], "added user ID {$_POST['user_id']} as collaborator");
        echo "User added";
    } else {
        echo "Unauthorized";
    }
    exit;
}

// You can add more document-related logic here (e.g., create, etc.)