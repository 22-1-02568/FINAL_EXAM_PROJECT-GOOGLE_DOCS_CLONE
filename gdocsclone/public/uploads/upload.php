<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}
$targetDir = __DIR__ . '/';
$filename = uniqid() . '_' . basename($_FILES['image']['name']);
$targetFile = $targetDir . $filename;
if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
    echo json_encode(['url' => '/GdocsClone/public/uploads/' . $filename]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed']);
}