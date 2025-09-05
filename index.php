<?php
require_once __DIR__ . '/auth.php';

// Require authentication
$auth->requireAuth();

$userId = $auth->getCurrentUserId();

// Set timezone if user has preference
$userTimezone = $_SESSION['user_timezone'] ?? 'UTC';
date_default_timezone_set($userTimezone);

// Check for login success message
$loginSuccess = false;
if (isset($_SESSION['login_success'])) {
    $loginSuccess = true;
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memo Notepad</title>
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Toast Container -->
    <div id="toast-container"></div>
    
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button id="sidebar-toggle" class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1>
                <i class="fas fa-sticky-note"></i>
                Memo Notepad
            </h1>
        </div>
        
        <div class="header-right">
            <button id="new-memo-btn" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                New Memo
            </button>
            
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle">
                    <i class="fas fa-user"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="/settings" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <a href="/changelog" class="dropdown-item">
                        <i class="fas fa-history"></i>
                        Changelog
                    </a>
                    <a href="/logout" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="search-container">
                <input type="text" id="search-input" placeholder="Search memos...">
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <div class="sidebar-content">
            <div id="memo-list" class="memo-list">
                <!-- Memo items will be loaded here -->
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div id="welcome-screen" class="welcome-screen">
            <div class="welcome-content">
                <i class="fas fa-sticky-note welcome-icon"></i>
                <h2>Welcome to Memo Notepad</h2>
                <p>Create your first memo to get started</p>
                <button id="create-first-memo" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus"></i>
                    Create First Memo
                </button>
            </div>
        </div>
        
        <div id="memo-view" class="memo-view" style="display: none;">
            <div class="memo-header">
                <h2 id="memo-title"></h2>
                <div class="memo-actions">
                    <button id="edit-memo-btn" class="btn btn-secondary">
                        <i class="fas fa-edit"></i>
                        Edit
                    </button>
                    <button id="delete-memo-btn" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </div>
            </div>
            <div class="memo-meta">
                <span id="memo-created">Created: <span id="memo-created-date"></span></span>
                <span id="memo-updated">Updated: <span id="memo-updated-date"></span></span>
            </div>
            <div id="memo-content" class="memo-content"></div>
        </div>
        
        <div id="memo-edit" class="memo-edit" style="display: none;">
            <div class="edit-header">
                <input type="text" id="edit-title" placeholder="Memo title..." class="title-input">
                <div class="edit-actions">
                    <button id="save-memo-btn" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                    <button id="cancel-edit-btn" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
            <textarea id="edit-content" placeholder="Write your memo here..." class="content-textarea"></textarea>
        </div>
    </main>
    
    <!-- Back to Top Button -->
    <button id="back-to-top" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Memo</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this memo? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button id="confirm-delete" class="btn btn-danger">Delete</button>
                <button id="cancel-delete" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
    
    <script>
        // Pass user timezone to JavaScript
        window.userTimezone = '<?php echo $userTimezone; ?>';
        
        // Show login success toast
        <?php if ($loginSuccess): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                showToast('Welcome back! You have successfully logged in.', 'success');
            }, 500);
        });
        <?php endif; ?>
    </script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
