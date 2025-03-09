<?php
// Initial admin setup script
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if admin user already exists
$checkQuery = "SELECT COUNT(*) FROM users WHERE username = 'Wissam'";
$result = pg_query($db_conn, $checkQuery);
$count = pg_fetch_result($result, 0, 0);

if ($count > 0) {
    echo "Admin user already exists!";
} else {
    // Create admin user
    $username = 'Wissam';
    $email = 'wissam.yahfoufi@gmail.com';
    $password = 'admin1';
    $role = 'admin';
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin user
    $query = "INSERT INTO users (username, email, password, role) 
              VALUES ($1, $2, $3, $4)";
    $result = pg_query_params($db_conn, $query, [$username, $email, $hashed_password, $role]);
    
    if ($result) {
        echo "Admin user created successfully!";
    } else {
        echo "Error creating admin user: " . pg_last_error($db_conn);
    }
}
?>