<?php
// Dashboard page logic
require_once 'includes/header.php';

// Load the dashboard template and replace placeholders
$template = file_get_contents('templates/dashboard.html');

// Replace username
$template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);

echo $template;

require_once 'includes/footer.php';
?>