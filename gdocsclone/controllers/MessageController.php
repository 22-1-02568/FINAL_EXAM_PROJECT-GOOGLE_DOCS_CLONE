<?php
session_start();
require_once __DIR__ . '/../core/dbConfig.php';
require_once __DIR__ . '/../models/Message.php';

function userHasAccess($pdo, $document_id, $user_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM documents WHERE id = ? AND user_id = ? UNION SELECT 1 FROM document_users WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $user_id, $document_id, $user_id]);
    return $stmt->fetch() ? true : false;
}

$messageModel = new Message($pdo);

// Before allowing message actions:
if (!userHasAccess($pdo, $_POST['document_id'] ?? $_GET['document_id'], $_SESSION['user_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

// Handle posting a new message
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SESSION['user_id'], $_POST['document_id'], $_POST['message'])
) {
    $messageModel->create($_POST['document_id'], $_SESSION['user_id'], $_POST['message']);
    // If AJAX, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    // Otherwise, redirect back to messages page
    header('Location: ../views/document/messages.php?document_id=' . intval($_POST['document_id']));
    exit;
}

// Handle fetching messages (AJAX)
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['document_id'])
) {
    $messages = $messageModel->getByDocument($_GET['document_id']);
    header('Content-Type: application/json');
    echo json_encode($messages);
    exit;
}