<?php
// Session management logic

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get session timeout from settings or use default
function getSessionTimeout() {
    global $db_conn;
    
    // Default timeout is 30 minutes
    $timeout = 30;
    
    // Try to get timeout from settings
    if (isset($db_conn)) {
        $query = "SELECT value FROM settings WHERE name = 'session_timeout'";
        $result = pg_query($db_conn, $query);
        
        if ($result && pg_num_rows($result) > 0) {
            $timeout = intval(pg_fetch_result($result, 0, 0));
        }
    }
    
    return $timeout * 60; // Convert to seconds
}

// Check if session has expired
function checkSessionTimeout() {
    $timeout = getSessionTimeout();
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session has expired
        session_unset();
        session_destroy();
        header('Location: /?timeout=1');
        exit;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Check if user is logged in
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        checkSessionTimeout();
        return true;
    }
    return false;
}

// Check if current user has a specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Check if current user has admin or moderator role
function isAdminOrModerator() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'moderator');
}

// Check if current user has admin role
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Check permission for specific action
function hasPermission($action) {
    // Define permissions based on roles
    $permissions = [
        'admin' => ['all'],
        'moderator' => ['view_all', 'edit', 'add', 'delete_own_records'],
        'operator' => ['view_assigned', 'edit_own_records', 'add']
    ];
    
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $role = $_SESSION['user_role'];
    
    // Admin has all permissions
    if ($role === 'admin') {
        return true;
    }
    
    // Check if the role has the specific action permission
    return isset($permissions[$role]) && (in_array($action, $permissions[$role]) || in_array('all', $permissions[$role]));
}
?>