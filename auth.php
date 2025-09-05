<?php
session_start();

require_once __DIR__ . '/models/User.php';

class Auth {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function login($secretKey) {
        $userAuth = $this->user->authenticateUser($secretKey);
        
        if ($userAuth) {
            $_SESSION['user_id'] = $userAuth['id'];
            $_SESSION['secret_key'] = $secretKey;
            $_SESSION['user_timezone'] = $userAuth['timezone'];
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
        session_start();
    }
    
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
    
    public function createFirstUser() {
        $userCount = $this->user->getUserCount();
        
        if ($userCount === 0) {
            // Default to Asia/Makassar for Indonesian users
            $defaultTimezone = 'Asia/Makassar';
            $newUser = $this->user->createUser($defaultTimezone);
            if ($newUser) {
                $_SESSION['user_id'] = $newUser['id'];
                $_SESSION['secret_key'] = $newUser['secret_key'];
                $_SESSION['user_timezone'] = $defaultTimezone;
                $_SESSION['is_first_user'] = true;
                return $newUser['secret_key'];
            }
        }
        
        return false;
    }
}

// Initialize auth globally
$auth = new Auth();
