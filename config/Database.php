<?php

class Database {
    private $db;
    private $dbPath;
    
    public function __construct() {
        $this->dbPath = __DIR__ . '/../data/memo_notepad.db';
        $this->ensureDataDirectory();
        $this->connect();
        $this->createTables();
    }
    
    private function ensureDataDirectory() {
        $dataDir = dirname($this->dbPath);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
    }
    
    private function connect() {
        try {
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function createTables() {
        // Create users table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secret_key TEXT UNIQUE NOT NULL,
                timezone TEXT DEFAULT 'UTC',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Add timezone column to existing users table if it doesn't exist
        try {
            $this->db->exec("ALTER TABLE users ADD COLUMN timezone TEXT DEFAULT 'UTC'");
        } catch (PDOException $e) {
            // Column already exists, ignore error
        }
        
        // Create memos table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS memos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ");
        
        // Create indexes for better performance
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_memos_user_id ON memos (user_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_memos_updated_at ON memos (updated_at DESC)");
    }
    
    public function getCurrentDateTime() {
        return date('Y-m-d H:i:s');
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function getDatabasePath() {
        return $this->dbPath;
    }
}
