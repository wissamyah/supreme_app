<?php
// Authentication API (login/logout)
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Validate CSRF token for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleLogin() {
    global $db_conn;
    
    $username_email = sanitizeInput($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username_email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username/email and password are required']);
        return;
    }
    
    // Check if input is an email or username
    $is_email = filter_var($username_email, FILTER_VALIDATE_EMAIL);
    
    if ($is_email) {
        $query = "SELECT * FROM users WHERE email = $1 AND is_blocked = FALSE";
    } else {
        $query = "SELECT * FROM users WHERE username = $1 AND is_blocked = FALSE";
    }
    
    $result = pg_query_params($db_conn, $query, [$username_email]);
    
    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        return;
    }
    
    $user = pg_fetch_assoc($result);
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        return;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    
    // Update last session timestamp
    $update_query = "UPDATE users SET last_session = CURRENT_TIMESTAMP WHERE id = $1";
    pg_query_params($db_conn, $update_query, [$user['id']]);
    
    // Log the login
    logAudit($db_conn, $user['id'], 'User logged in');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful', 
        'redirect' => '/dashboard'
    ]);
}

function handleLogout() {
    // Log the logout if user is logged in
    if (isset($_SESSION['user_id'])) {
        logAudit($GLOBALS['db_conn'], $_SESSION['user_id'], 'User logged out');
    }
    
    // Clear session
    session_unset();
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Logout successful', 
        'redirect' => '/'
    ]);
}
?>