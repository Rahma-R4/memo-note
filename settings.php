<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/config/Database.php';

// Require authentication
$auth->requireAuth();

$userId = $auth->getCurrentUserId();
$user = new User();
$database = new Database();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'regenerate_key':
            $newKey = $user->generateSecretKey();
            $stmt = $database->getConnection()->prepare("UPDATE users SET secret_key = ? WHERE id = ?");
            if ($stmt->execute([$newKey, $userId])) {
                $_SESSION['secret_key'] = $newKey;
                $message = "New security key generated successfully. Please save it securely: " . $newKey;
                $messageType = 'success';
            } else {
                $message = "Failed to regenerate security key.";
                $messageType = 'error';
            }
            break;
            
        case 'update_timezone':
            $timezone = $_POST['timezone'] ?? 'UTC';
            if (in_array($timezone, timezone_identifiers_list())) {
                // Update timezone in database
                if ($user->updateUserTimezone($userId, $timezone)) {
                    $_SESSION['user_timezone'] = $timezone;
                    $message = "Timezone updated successfully.";
                    $messageType = 'success';
                } else {
                    $message = "Failed to update timezone in database.";
                    $messageType = 'error';
                }
            } else {
                $message = "Invalid timezone selected.";
                $messageType = 'error';
            }
            break;
    }
}

// Get database info
$dbPath = $database->getDatabasePath();
$dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
$dbSizeFormatted = formatBytes($dbSize);

// Get user stats
$conn = $database->getConnection();
$memoCount = $conn->query("SELECT COUNT(*) FROM memos WHERE user_id = $userId")->fetchColumn();
$userCreated = $conn->query("SELECT created_at FROM users WHERE id = $userId")->fetchColumn();

// Get current timezone
$currentTimezone = $_SESSION['user_timezone'] ?? 'UTC';

function formatBytes($size, $precision = 2) {
    if ($size <= 0) {
        return '0 B';
    }
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Memo Notepad</title>
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
            <h1>
                <i class="fas fa-cog"></i>
                Settings
            </h1>
        </div>
        
        <div class="header-right">
            <a href="/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to App
            </a>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="settings-content">
        <div class="settings-container">
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Security Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
                    <p>Manage your security key and authentication settings</p>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Security Key</h3>
                        <p>Your 32-character hexadecimal security key for authentication</p>
                        <div class="current-key">
                            <strong>Current Key:</strong> 
                            <code id="current-key"><?php echo htmlspecialchars($_SESSION['secret_key']); ?></code>
                            <button type="button" onclick="copyKey()" class="btn-copy">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="setting-action">
                        <form method="POST" id="regenerate-form">
                            <input type="hidden" name="action" value="regenerate_key">
                            <button type="button" id="regenerate-btn" class="btn btn-warning">
                                <i class="fas fa-sync-alt"></i>
                                Regenerate Key
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Database Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-database"></i> Database Management</h2>
                    <p>Backup and manage your memo database</p>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Database Backup</h3>
                        <p>Download a backup of your SQLite database file</p>
                    </div>
                    <div class="setting-action">
                        <a href="<?php echo '/download_database.php'; ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i>
                            Download Database
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Timezone Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Timezone Settings</h2>
                    <p>Configure your preferred timezone for date and time display</p>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Current Timezone</h3>
                        <p>All dates and times will be displayed in this timezone</p>
                    </div>
                    <div class="setting-action">
                        <form method="POST" class="timezone-form">
                            <input type="hidden" name="action" value="update_timezone">
                            <select name="timezone" class="timezone-select">
                                <?php
                                $timezones = [
                                    'UTC' => 'UTC (Coordinated Universal Time)',
                                    'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                                    'Asia/Makassar' => 'Asia/Makassar (WITA)',
                                    'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                                    'America/New_York' => 'America/New_York (EST)',
                                    'America/Los_Angeles' => 'America/Los_Angeles (PST)',
                                    'Europe/London' => 'Europe/London (GMT)',
                                    'Europe/Paris' => 'Europe/Paris (CET)',
                                    'Asia/Tokyo' => 'Asia/Tokyo (JST)',
                                    'Asia/Shanghai' => 'Asia/Shanghai (CST)',
                                    'Australia/Sydney' => 'Australia/Sydney (AEDT)'
                                ];
                                
                                foreach ($timezones as $tz => $label) {
                                    $selected = ($tz === $currentTimezone) ? 'selected' : '';
                                    echo "<option value=\"$tz\" $selected>$label</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-save"></i>
                                Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Database Information -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-info-circle"></i> Database Information</h2>
                    <p>Statistics and information about your memo database</p>
                </div>
                
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <div class="info-content">
                            <h3><?php echo $memoCount; ?></h3>
                            <p>Total Memos</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-hdd"></i>
                        </div>
                        <div class="info-content">
                            <h3><?php echo $dbSizeFormatted; ?></h3>
                            <p>Database Size</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="info-content">
                            <h3><?php echo date('M j, Y', strtotime($userCreated)); ?></h3>
                            <p>Account Created</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="info-content">
                            <h3>SQLite3</h3>
                            <p>Database Type</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Application Information -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-laptop-code"></i> Application Information</h2>
                    <p>Version and technical details</p>
                </div>
                
                <div class="app-info">
                    <div class="info-row">
                        <span class="info-label">Application Name:</span>
                        <span class="info-value">Memo Notepad</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Version:</span>
                        <span class="info-value">1.2.0</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?php echo PHP_VERSION; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Keyboard Shortcuts -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h2>
                    <p>Useful keyboard shortcuts to improve your productivity</p>
                </div>
                
                <div class="shortcuts-grid">
                    <div class="shortcut-item">
                        <kbd>Ctrl</kbd> + <kbd>S</kbd>
                        <span>Save current memo</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Ctrl</kbd> + <kbd>N</kbd>
                        <span>Create new memo</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Esc</kbd>
                        <span>Cancel edit mode</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Ctrl</kbd> + <kbd>/</kbd>
                        <span>Focus search</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        function copyKey() {
            const keyElement = document.getElementById('current-key');
            const textArea = document.createElement('textarea');
            textArea.value = keyElement.textContent;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            // Show feedback
            const btn = document.querySelector('.btn-copy');
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            
            setTimeout(() => {
                btn.innerHTML = originalIcon;
            }, 2000);
        }
        
        // Setup dropdown functionality
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });
                
                dropdown.classList.toggle('active');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        });
        
        // Add event listener for regenerate button
        document.getElementById('regenerate-btn').addEventListener('click', function() {
            showRegenerateModal();
        });
        
        // Custom modal for regenerate key confirmation
        function showRegenerateModal() {
            // Remove any existing modal first
            const existingModal = document.querySelector('.modal-overlay');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card">
                    <div class="modal-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Regenerate Security Key</h3>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to generate a new security key?</p>
                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> This will invalidate your current key and you will need to save the new one securely.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button onclick="confirmRegenerate()" class="btn btn-warning">
                            <i class="fas fa-sync-alt"></i>
                            Yes, Regenerate
                        </button>
                        <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
            
            // Add styles for modal
            if (!document.getElementById('modal-styles')) {
                const style = document.createElement('style');
                style.id = 'modal-styles';
                style.textContent = `
                    .modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.7);
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                        z-index: 4000;
                        animation: fadeIn 0.3s ease;
                    }
                    
                    .modal-card {
                        background: var(--bg-secondary);
                        border: 1px solid var(--border);
                        border-radius: 12px;
                        max-width: 500px;
                        width: 90%;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                        animation: slideIn 0.3s ease;
                    }
                    
                    .modal-header {
                        padding: 20px 24px 0;
                        border-bottom: 1px solid var(--border);
                        margin-bottom: 20px;
                    }
                    
                    .modal-header h3 {
                        margin: 0;
                        color: var(--warning);
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }
                    
                    .modal-body {
                        padding: 0 24px 20px;
                    }
                    
                    .modal-body p {
                        margin: 0 0 16px 0;
                        color: var(--text-primary);
                    }
                    
                    .warning-box {
                        background: rgba(253, 203, 110, 0.1);
                        border: 1px solid rgba(253, 203, 110, 0.3);
                        border-radius: 8px;
                        padding: 12px 16px;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        color: var(--warning);
                    }
                    
                    .modal-footer {
                        padding: 0 24px 24px;
                        display: flex;
                        gap: 12px;
                        justify-content: flex-end;
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    
                    @keyframes slideIn {
                        from { transform: translateY(-50px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }
        }
        
        function confirmRegenerate() {
            document.getElementById('regenerate-form').submit();
        }
        
        function closeModal() {
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.remove();
            }
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
