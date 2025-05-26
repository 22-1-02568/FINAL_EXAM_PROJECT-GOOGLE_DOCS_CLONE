<?php
require_once __DIR__ . '/../core/dbConfig.php';

class Document
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByUser($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($user_id, $title, $content = '')
    {
        $stmt = $this->pdo->prepare("INSERT INTO documents (user_id, title, content) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $title, $content]);
    }

    public function update($id, $title, $content)
    {
        $stmt = $this->pdo->prepare("UPDATE documents SET title = ?, content = ? WHERE id = ?");
        return $stmt->execute([$title, $content, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM documents WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAll()
    {
        $stmt = $this->pdo->query("SELECT documents.*, users.username FROM documents JOIN users ON documents.user_id = users.id ORDER BY updated_at DESC");
        return $stmt->fetchAll();
    }

    public function addUser($document_id, $user_id, $can_edit = 0)
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO document_users (document_id, user_id, can_edit) VALUES (?, ?, ?)");
        return $stmt->execute([$document_id, $user_id, $can_edit]);
    }

    public function getSharedWith($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT documents.* FROM documents JOIN document_users ON documents.id = document_users.document_id WHERE document_users.user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}