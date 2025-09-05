<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/Database.php';

// Disable any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: /login');
    exit;
}

$database = new Database();
$dbPath = $database->getDatabasePath();

if (file_exists($dbPath)) {
    $filename = 'memo_notepad_backup_' . date('Y-m-d_H-i-s') . '.db';
    
    // Set headers for file download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($dbPath));
    
    // Clear the output buffer before sending file
    flush();
    
    // Read file in chunks to handle large files
    $handle = fopen($dbPath, 'rb');
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
    exit;
} else {
    header('HTTP/1.0 404 Not Found');
    echo 'Database file not found: ' . htmlspecialchars($dbPath);
    exit;
}
?>
