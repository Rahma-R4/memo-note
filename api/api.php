<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../models/Memo.php';

// Set timezone if user has preference
if (isset($_SESSION['user_timezone'])) {
    date_default_timezone_set($_SESSION['user_timezone']);
}

$memo = new Memo();

function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function sendError($message, $status = 400) {
    sendResponse(['error' => $message], $status);
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

// Authentication check for all API endpoints
if (!$auth->isLoggedIn()) {
    sendError('Authentication required', 401);
}

$userId = $auth->getCurrentUserId();

switch ($method) {
    case 'GET':
        if ($path === 'memos') {
            $memos = $memo->getUserMemos($userId);
            sendResponse(['memos' => $memos]);
        } elseif (preg_match('/^memo\/(\d+)$/', $path, $matches)) {
            $memoId = $matches[1];
            $memoData = $memo->getMemo($memoId, $userId);
            if ($memoData) {
                sendResponse(['memo' => $memoData]);
            } else {
                sendError('Memo not found', 404);
            }
        } elseif ($path === 'search') {
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                sendError('Search query required');
            }
            $results = $memo->searchMemos($userId, $query);
            sendResponse(['memos' => $results]);
        } else {
            sendError('Invalid endpoint', 404);
        }
        break;
        
    case 'POST':
        if ($path === 'memo') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['title']) || !isset($input['content'])) {
                sendError('Title and content are required');
            }
            
            $memoId = $memo->createMemo($userId, $input['title'], $input['content']);
            if ($memoId) {
                sendResponse(['id' => $memoId, 'message' => 'Memo created successfully'], 201);
            } else {
                sendError('Failed to create memo', 500);
            }
        } else {
            sendError('Invalid endpoint', 404);
        }
        break;
        
    case 'PUT':
        if (preg_match('/^memo\/(\d+)$/', $path, $matches)) {
            $memoId = $matches[1];
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['title']) || !isset($input['content'])) {
                sendError('Title and content are required');
            }
            
            $success = $memo->updateMemo($memoId, $userId, $input['title'], $input['content']);
            if ($success) {
                sendResponse(['message' => 'Memo updated successfully']);
            } else {
                sendError('Failed to update memo or memo not found', 404);
            }
        } else {
            sendError('Invalid endpoint', 404);
        }
        break;
        
    case 'DELETE':
        if (preg_match('/^memo\/(\d+)$/', $path, $matches)) {
            $memoId = $matches[1];
            $success = $memo->deleteMemo($memoId, $userId);
            if ($success) {
                sendResponse(['message' => 'Memo deleted successfully']);
            } else {
                sendError('Failed to delete memo or memo not found', 404);
            }
        } else {
            sendError('Invalid endpoint', 404);
        }
        break;
        
    default:
        sendError('Method not allowed', 405);
}
