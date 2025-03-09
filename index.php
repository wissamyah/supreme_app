<?php
// Main entry point for all requests
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Get the requested URL path
$url = isset($_GET['url']) ? $_GET['url'] : '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Route the request
$page = !empty($url[0]) ? $url[0] : 'login';

// Check if user is logged in, except for login page
if ($page !== 'login' && !isLoggedIn()) {
    header('Location: /');
    exit;
}

// Include the appropriate page file
$page_file = 'pages/' . $page . '.php';

if (file_exists($page_file)) {
    include_once $page_file;
} else {
    // 404 Page Not Found
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The page you requested could not be found.</p>";
    echo "<p><a href='/dashboard'>Return to Dashboard</a></p>";
}
?>