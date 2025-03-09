<?php
// Users CRUD API
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check authentication
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user has admin or moderator role
if (!isAdminOrModerator()) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

header('Content-Type: application/json');

// Validate CSRF token for all POST, PUT, DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (
        (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) &&
        (!isset($input['csrf_token']) || !validateCSRFToken($input['csrf_token']))
    ) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                getUsers();
                break;
            case 'get':
                getUser();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'POST':
        switch ($action) {
            case 'add':
                addUser();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'PUT':
        switch ($action) {
            case 'update':
                updateUser();
                break;
            case 'change_password':
                changePassword();
                break;
            case 'toggle_block':
                toggleBlockStatus();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'DELETE':
        switch ($action) {
            case 'delete':
                deleteUser();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function getUsers() {
    global $db_conn;
    
    // Only admin can see all users
    // Moderators can only see operators
    $where_clause = '';
    $params = [];
    
    if (!isAdmin() && isAdminOrModerator()) {
        $where_clause = "WHERE role = 'operator'";
    }
    
    $search = sanitizeInput($_GET['search'] ?? '');
    if (!empty($search)) {
        $params[] = "%$search%";
        $where_clause = !empty($where_clause) ? 
            "$where_clause AND (username ILIKE $1 OR email ILIKE $1)" : 
            "WHERE username ILIKE $1 OR email ILIKE $1";
    }
    
    $query = "
        SELECT id, username, email, role, last_session, is_blocked, created_at
        FROM users
        $where_clause
        ORDER BY username
    ";
    
    $result = empty($params) ? 
        pg_query($db_conn, $query) : 
        pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $users = [];
    while ($row = pg_fetch_assoc($result)) {
        // Format last session date if it exists
        if (!is_null($row['last_session'])) {
            $row['formatted_last_session'] = formatDate($row['last_session']);
        } else {
            $row['formatted_last_session'] = 'Never';
        }
        
        // Format creation date
        $row['formatted_created_at'] = formatDate($row['created_at']);
        
        // Convert is_blocked to boolean
        $row['is_blocked'] = $row['is_blocked'] === 't' || $row['is_blocked'] === true;
        
        $users[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $users]);
}

function getUser() {
    global $db_conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Moderators can only view operators
    $where_clause = "id = $1";
    if (!isAdmin() && isAdminOrModerator()) {
        $where_clause .= " AND role = 'operator'";
    }
    
    $query = "
        SELECT id, username, email, role, last_session, is_blocked, created_at
        FROM users
        WHERE $where_clause
    ";
    
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $user = pg_fetch_assoc($result);
    
    // Format last session date if it exists
    if (!is_null($user['last_session'])) {
        $user['formatted_last_session'] = formatDate($user['last_session']);
    } else {
        $user['formatted_last_session'] = 'Never';
    }
    
    // Format creation date
    $user['formatted_created_at'] = formatDate($user['created_at']);
    
    // Convert is_blocked to boolean
    $user['is_blocked'] = $user['is_blocked'] === 't' || $user['is_blocked'] === true;
    
    echo json_encode(['success' => true, 'data' => $user]);
}

function addUser() {
    global $db_conn;
    
    // Only admin can add admin or moderator users
    // Moderators can only add operators
    
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? '');
    
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'All fields are required'
        ]);
        return;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Validate role
    $valid_roles = ['admin', 'moderator', 'operator'];
    if (!in_array($role, $valid_roles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        return;
    }
    
    // If user is moderator, they can only add operators
    if (!isAdmin() && ($role === 'admin' || $role === 'moderator')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    // Check if username or email already exists
    $check_query = "SELECT COUNT(*) FROM users WHERE username = $1 OR email = $2";
    $check_result = pg_query_params($db_conn, $check_query, [$username, $email]);
    $count = pg_fetch_result($check_result, 0, 0);
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Username or email already exists'
        ]);
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $query = "
        INSERT INTO users (username, email, password, role) 
        VALUES ($1, $2, $3, $4) 
        RETURNING id, username, email, role, last_session, is_blocked, created_at
    ";
    
    $result = pg_query_params($db_conn, $query, [$username, $email, $hashed_password, $role]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $user = pg_fetch_assoc($result);
    
    // Format last session date if it exists
    if (!is_null($user['last_session'])) {
        $user['formatted_last_session'] = formatDate($user['last_session']);
    } else {
        $user['formatted_last_session'] = 'Never';
    }
    
    // Format creation date
    $user['formatted_created_at'] = formatDate($user['created_at']);
    
    // Convert is_blocked to boolean
    $user['is_blocked'] = $user['is_blocked'] === 't' || $user['is_blocked'] === true;
    
    // Log the action
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Added user', 
        'users', 
        $user['id'], 
        "Username: $username, Email: $email, Role: $role"
    );
    
    echo json_encode([
        'success' => true, 
        'message' => 'User added successfully', 
        'data' => $user
    ]);
}

function updateUser() {
    global $db_conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $username = sanitizeInput($input['username'] ?? '');
    $email = sanitizeInput($input['email'] ?? '');
    $role = sanitizeInput($input['role'] ?? '');
    
    if ($id <= 0 || empty($username) || empty($email) || empty($role)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Validate role
    $valid_roles = ['admin', 'moderator', 'operator'];
    if (!in_array($role, $valid_roles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        return;
    }
    
    // Get current user data
    $current_query = "SELECT username, email, role FROM users WHERE id = $1";
    $current_result = pg_query_params($db_conn, $current_query, [$id]);
    
    if (!$current_result || pg_num_rows($current_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $current_user = pg_fetch_assoc($current_result);
    
    // Moderators can only update operators
    if (!isAdmin() && $current_user['role'] !== 'operator') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    // Moderators cannot change a user's role to admin or moderator
    if (!isAdmin() && ($role === 'admin' || $role === 'moderator')) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Permission denied: Cannot change role to admin or moderator'
        ]);
        return;
    }
    
    // Check if username or email already exists (excluding current user)
    $check_query = "SELECT COUNT(*) FROM users WHERE (username = $1 OR email = $2) AND id != $3";
    $check_result = pg_query_params($db_conn, $check_query, [$username, $email, $id]);
    $count = pg_fetch_result($check_result, 0, 0);
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Username or email already exists'
        ]);
        return;
    }
    
    // Update user
    $query = "
        UPDATE users 
        SET username = $1, email = $2, role = $3 
        WHERE id = $4 
        RETURNING id, username, email, role, last_session, is_blocked, created_at
    ";
    
    $result = pg_query_params($db_conn, $query, [$username, $email, $role, $id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $user = pg_fetch_assoc($result);
    
    // Format last session date if it exists
    if (!is_null($user['last_session'])) {
        $user['formatted_last_session'] = formatDate($user['last_session']);
    } else {
        $user['formatted_last_session'] = 'Never';
    }
    
    // Format creation date
    $user['formatted_created_at'] = formatDate($user['created_at']);
    
    // Convert is_blocked to boolean
    $user['is_blocked'] = $user['is_blocked'] === 't' || $user['is_blocked'] === true;
    
    // Log the action
    $changes = [];
    if ($current_user['username'] !== $username) $changes[] = "Username: {$current_user['username']} → $username";
    if ($current_user['email'] !== $email) $changes[] = "Email: {$current_user['email']} → $email";
    if ($current_user['role'] !== $role) $changes[] = "Role: {$current_user['role']} → $role";
    
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Updated user', 
        'users', 
        $id, 
        implode(", ", $changes)
    );
    
    echo json_encode([
        'success' => true, 
        'message' => 'User updated successfully', 
        'data' => $user
    ]);
}

function changePassword() {
    global $db_conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $password = $input['password'] ?? '';
    
    if ($id <= 0 || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Get current user data to check permissions
    $current_query = "SELECT username, role FROM users WHERE id = $1";
    $current_result = pg_query_params($db_conn, $current_query, [$id]);
    
    if (!$current_result || pg_num_rows($current_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $current_user = pg_fetch_assoc($current_result);
    
    // Moderators can only change operator passwords
    if (!isAdmin() && $current_user['role'] !== 'operator') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    // Hash new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password
    $query = "UPDATE users SET password = $1 WHERE id = $2";
    $result = pg_query_params($db_conn, $query, [$hashed_password, $id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Log the action
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Changed user password', 
        'users', 
        $id, 
        "Username: {$current_user['username']}"
    );
    
    echo json_encode([
        'success' => true, 
        'message' => 'Password changed successfully'
    ]);
}

function toggleBlockStatus() {
    global $db_conn;
    
    // Only admin can block/unblock users
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Check if user exists and get current status
    $current_query = "SELECT username, is_blocked FROM users WHERE id = $1";
    $current_result = pg_query_params($db_conn, $current_query, [$id]);
    
    if (!$current_result || pg_num_rows($current_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $current_user = pg_fetch_assoc($current_result);
    $current_status = $current_user['is_blocked'] === 't' || $current_user['is_blocked'] === true;
    
    // Prevent blocking the current user (self)
    if ($id === intval($_SESSION['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot block yourself']);
        return;
    }
    
    // Toggle block status
    $new_status = !$current_status;
    
    $query = "
        UPDATE users 
        SET is_blocked = $1 
        WHERE id = $2 
        RETURNING id, username, email, role, last_session, is_blocked, created_at
    ";
    
    $result = pg_query_params($db_conn, $query, [$new_status, $id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $user = pg_fetch_assoc($result);
    
    // Format last session date if it exists
    if (!is_null($user['last_session'])) {
        $user['formatted_last_session'] = formatDate($user['last_session']);
    } else {
        $user['formatted_last_session'] = 'Never';
    }
    
    // Format creation date
    $user['formatted_created_at'] = formatDate($user['created_at']);
    
    // Convert is_blocked to boolean
    $user['is_blocked'] = $user['is_blocked'] === 't' || $user['is_blocked'] === true;
    
    // Log the action
    $action = $new_status ? 'Blocked user' : 'Unblocked user';
    
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        $action, 
        'users', 
        $id, 
        "Username: {$current_user['username']}"
    );
    
    echo json_encode([
        'success' => true, 
        'message' => ($new_status ? 'User blocked' : 'User unblocked') . ' successfully', 
        'data' => $user
    ]);
}

function deleteUser() {
    global $db_conn;
    
    // Only admin can delete users
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Prevent deleting the current user (self)
    if ($id === intval($_SESSION['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        return;
    }
    
    // Get user data for logging
    $user_query = "SELECT username FROM users WHERE id = $1";
    $user_result = pg_query_params($db_conn, $user_query, [$id]);
    
    if (!$user_result || pg_num_rows($user_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $username = pg_fetch_result($user_result, 0, 0);
    
    // Check if user has performed any actions (audit logs)
    $audit_query = "SELECT COUNT(*) FROM audit_logs WHERE user_id = $1";
    $audit_result = pg_query_params($db_conn, $audit_query, [$id]);
    $audit_count = pg_fetch_result($audit_result, 0, 0);
    
    if ($audit_count > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete user with activity history. Consider blocking instead.'
        ]);
        return;
    }
    
    // Delete user
    $query = "DELETE FROM users WHERE id = $1";
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Log the action
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Deleted user', 
        'users', 
        $id, 
        "Username: $username"
    );
    
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
}
?>