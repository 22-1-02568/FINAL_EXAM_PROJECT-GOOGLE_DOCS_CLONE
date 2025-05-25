<?php
require_once __DIR__ . '/../core/dbConfig.php';

class ActivityLog
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function log($document_id, $user_id, $action)
    {
        $stmt = $this->pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
        return $stmt->execute([$document_id, $user_id, $action]);
    }

    public function getByDocument($document_id)
    {
        $stmt = $this->pdo->prepare("SELECT activity_logs.*, users.username FROM activity_logs JOIN users ON activity_logs.user_id = users.id WHERE document_id = ? ORDER BY created_at DESC");
        $stmt->execute([$document_id]);
        return $stmt->fetchAll();
    }
}