<?php
// Login page logic

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: /dashboard');
    exit;
}

// Check if there's a timeout message
$timeout_message = '';
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $timeout_message = 'Your session has expired. Please log in again.';
}

// Load the login template and replace placeholders
$template = file_get_contents('../templates/login.html');

// Replace CSRF token
$template = str_replace('{{csrf_token}}', generateCSRFToken(), $template);

// Replace timeout message if exists
if (!empty($timeout_message)) {
    $error_div = '<div id="error-message" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
        <span id="error-text" class="block sm:inline">' . $timeout_message . '</span>
    </div>';
    $template = str_replace('<!-- Error message -->', $error_div, $template);
}

echo $template;
?>