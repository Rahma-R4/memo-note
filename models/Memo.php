<?php

require_once __DIR__ . '/../config/Database.php';

class Memo {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function createMemo($userId, $title, $content) {
        try {
            $database = new Database();
            $currentDateTime = $database->getCurrentDateTime();
            
            $stmt = $this->db->prepare("
                INSERT INTO memos (user_id, title, content, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $content, $currentDateTime, $currentDateTime]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updateMemo($id, $userId, $title, $content) {
        try {
            $database = new Database();
            $currentDateTime = $database->getCurrentDateTime();
            
            $stmt = $this->db->prepare("
                UPDATE memos 
                SET title = ?, content = ?, updated_at = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$title, $content, $currentDateTime, $id, $userId]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function deleteMemo($id, $userId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM memos WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getMemo($id, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, title, content, created_at, updated_at 
                FROM memos 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getUserMemos($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, title, content, created_at, updated_at 
                FROM memos 
                WHERE user_id = ? 
                ORDER BY updated_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function searchMemos($userId, $query) {
        try {
            $searchTerm = '%' . $query . '%';
            $stmt = $this->db->prepare("
                SELECT id, title, content, created_at, updated_at 
                FROM memos 
                WHERE user_id = ? AND (title LIKE ? OR content LIKE ?)
                ORDER BY updated_at DESC
            ");
            $stmt->execute([$userId, $searchTerm, $searchTerm]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
