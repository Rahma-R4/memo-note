<?php
require_once __DIR__ . '/auth.php';

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    header('Location: /');
    exit;
}

// Check if this is the first user
$firstUserKey = $auth->createFirstUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secretKey = $_POST['secret_key'] ?? '';
    
    if (empty($secretKey)) {
        $error = 'Secret key is required';
    } elseif (strlen($secretKey) !== 32) {
        $error = 'Secret key must be exactly 32 characters';
    } elseif (!ctype_xdigit($secretKey)) {
        $error = 'Secret key must contain only hexadecimal characters';
    } else {
        if ($auth->login($secretKey)) {
            $_SESSION['login_success'] = true;
            header('Location: /');
            exit;
        } else {
            $error = 'Invalid secret key';
        }
    }
}

if ($firstUserKey) {
    $success = 'Welcome! Your secret key has been generated. Please save it securely.';
}

// Check for logout success message
$logoutSuccess = false;
if (isset($_SESSION['logout_success'])) {
    $logoutSuccess = true;
    unset($_SESSION['logout_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Memo Notepad</title>
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-sticky-note"></i>
                <h1>Memo Notepad</h1>
                <p>Secure note-taking application</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <div class="secret-key-display">
                    <label>Your Secret Key:</label>
                    <div class="key-container">
                        <input type="text" value="<?php echo htmlspecialchars($firstUserKey); ?>" readonly id="secretKeyDisplay">
                        <button type="button" onclick="copySecretKey()" class="copy-btn">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <small>Save this key securely. You'll need it to access your memos.</small>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="secret_key">
                        <i class="fas fa-key"></i>
                        Secret Key
                    </label>
                    <input 
                        type="text" 
                        id="secret_key" 
                        name="secret_key" 
                        placeholder="Enter your 32-character secret key"
                        maxlength="32"
                        value="<?php echo $firstUserKey ? htmlspecialchars($firstUserKey) : ''; ?>"
                        required
                    >
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            <div class="login-footer">
                <p><i class="fas fa-info-circle"></i> Your secret key is a 32-character hexadecimal string</p>
            </div>
        </div>
    </div>
    
    <script>
        function copySecretKey() {
            const input = document.getElementById('secretKeyDisplay');
            input.select();
            document.execCommand('copy');
            
            const btn = document.querySelector('.copy-btn');
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            
            setTimeout(() => {
                btn.innerHTML = originalIcon;
            }, 2000);
        }
        
        // Show logout success toast
        <?php if ($logoutSuccess): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                showToast('You have been successfully logged out.', 'success');
            }, 500);
        });
        <?php endif; ?>
        
        // Toast notification function for login page
        function showToast(message, type = 'info') {
            // Create toast container if not exists
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 3000;';
                document.body.appendChild(container);
            }
            
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.style.cssText = `
                background: var(--bg-secondary);
                border: 1px solid var(--border);
                border-radius: 8px;
                padding: 16px 20px;
                margin-bottom: 10px;
                min-width: 300px;
                box-shadow: 0 5px 20px var(--shadow);
                transform: translateX(100%);
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 12px;
                color: var(--text-primary);
            `;
            
            if (type === 'success') {
                toast.style.borderColor = 'var(--success)';
            } else if (type === 'error') {
                toast.style.borderColor = 'var(--danger)';
            }
            
            const icon = getToastIcon(type);
            toast.innerHTML = `
                <i class="${icon}" style="font-size: 1.2rem; color: ${getIconColor(type)};"></i>
                <span>${message}</span>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.style.transform = 'translateX(0)', 100);
            
            // Remove toast after 4 seconds
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 4000);
        }
        
        function getToastIcon(type) {
            switch (type) {
                case 'success': return 'fas fa-check-circle';
                case 'error': return 'fas fa-exclamation-circle';
                case 'warning': return 'fas fa-exclamation-triangle';
                default: return 'fas fa-info-circle';
            }
        }
        
        function getIconColor(type) {
            switch (type) {
                case 'success': return 'var(--success)';
                case 'error': return 'var(--danger)';
                case 'warning': return 'var(--warning)';
                default: return 'var(--accent-primary)';
            }
        }
    </script>
</body>
</html>
