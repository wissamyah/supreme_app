<?php
// Database connection using .env variables

// Parse .env file
function parseEnvFile($envFile) {
    $vars = [];
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($name, $value) = explode('=', $line, 2);
                $vars[$name] = $value;
            }
        }
    }
    return $vars;
}

// Get database credentials from .env
$env = parseEnvFile('.env');

// Set DB connection parameters
$db_host = $env['DB_HOST'] ?? 'localhost';
$db_name = $env['DB_NAME'] ?? 'xazqmkee_supreme_app';
$db_user = $env['DB_USER'] ?? 'xazqmkee_supreme';
$db_pass = $env['DB_PASS'] ?? 'Anasousou1990!';

// Connect to PostgreSQL
$connection_string = "host=$db_host dbname=$db_name user=$db_user password=$db_pass";
$db_conn = pg_connect($connection_string);

if (!$db_conn) {
    die("Database connection failed: " . pg_last_error());
}
?>