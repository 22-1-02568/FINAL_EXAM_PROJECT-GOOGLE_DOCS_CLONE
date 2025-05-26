<?php
require_once __DIR__ . '/../core/dbConfig.php';

class Message
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByDocument($document_id)
    {
        $stmt = $this->pdo->prepare("SELECT messages.*, users.username FROM messages JOIN users ON messages.user_id = users.id WHERE document_id = ? ORDER BY created_at ASC");
        $stmt->execute([$document_id]);
        return $stmt->fetchAll();
    }

    public function create($document_id, $user_id, $message)
    {
        $stmt = $this->pdo->prepare("INSERT INTO messages (document_id, user_id, message) VALUES (?, ?, ?)");
        return $stmt->execute([$document_id, $user_id, $message]);
    }
}