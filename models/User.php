<?php

require_once __DIR__ . '/../config/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function generateSecretKey() {
        return bin2hex(random_bytes(16)); // 32 character hex string
    }
    
    public function createUser($timezone = 'UTC') {
        $secretKey = $this->generateSecretKey();
        
        try {
            $stmt = $this->db->prepare("INSERT INTO users (secret_key, timezone) VALUES (?, ?)");
            $stmt->execute([$secretKey, $timezone]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'secret_key' => $secretKey
            ];
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function authenticateUser($secretKey) {
        try {
            $stmt = $this->db->prepare("SELECT id, timezone FROM users WHERE secret_key = ?");
            $stmt->execute([$secretKey]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Update last login
                $updateStmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                return [
                    'id' => $user['id'],
                    'timezone' => $user['timezone']
                ];
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updateUserTimezone($userId, $timezone) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET timezone = ? WHERE id = ?");
            return $stmt->execute([$timezone, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getUserTimezone($userId) {
        try {
            $stmt = $this->db->prepare("SELECT timezone FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['timezone'] : 'UTC';
        } catch (PDOException $e) {
            return 'UTC';
        }
    }
    
    public function getUserCount() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
