<?php
// Settings API
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
            case 'get':
                getSettings();
                break;
            case 'audit_logs':
                getAuditLogs();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'POST':
        switch ($action) {
            case 'update':
                updateSettings();
                break;
            case 'backup':
                backupDatabase();
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

function getSettings() {
    global $db_conn;
    
    // Create settings table if it doesn't exist
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(50) PRIMARY KEY,
            value VARCHAR(255) NOT NULL,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    pg_query($db_conn, $create_table_query);
    
    // Insert default settings if they don't exist
    $default_settings = [
        [
            'session_timeout', 
            '30', 
            'Session timeout in minutes'
        ],
        [
            'rows_per_page', 
            '25', 
            'Number of rows to display per page in tables'
        ]
    ];
    
    foreach ($default_settings as $setting) {
        $check_query = "SELECT COUNT(*) FROM settings WHERE name = $1";
        $check_result = pg_query_params($db_conn, $check_query, [$setting[0]]);
        $count = pg_fetch_result($check_result, 0, 0);
        
        if ($count == 0) {
            $insert_query = "
                INSERT INTO settings (name, value, description) 
                VALUES ($1, $2, $3)
            ";
            
            pg_query_params($db_conn, $insert_query, $setting);
        }
    }
    
    // Get all settings
    $query = "SELECT name, value, description FROM settings ORDER BY name";
    $result = pg_query($db_conn, $query);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $settings = [];
    while ($row = pg_fetch_assoc($result)) {
        $settings[$row['name']] = [
            'value' => $row['value'],
            'description' => $row['description']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $settings]);
}

function updateSettings() {
    global $db_conn;
    
    // Only admin or moderator can update settings
    if (!isAdminOrModerator()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $settings = $input['settings'] ?? [];
    
    if (empty($settings)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No settings provided']);
        return;
    }
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        foreach ($settings as $name => $value) {
            // Sanitize name and value
            $name = sanitizeInput($name);
            $value = sanitizeInput($value);
            
            // Validate session_timeout (must be a positive integer)
            if ($name === 'session_timeout' && (!is_numeric($value) || intval($value) <= 0)) {
                throw new Exception('Session timeout must be a positive number');
            }
            
            // Validate rows_per_page (must be a positive integer)
            if ($name === 'rows_per_page' && (!is_numeric($value) || intval($value) <= 0)) {
                throw new Exception('Rows per page must be a positive number');
            }
            
            // Update setting
            $query = "
                UPDATE settings 
                SET value = $1, updated_at = CURRENT_TIMESTAMP 
                WHERE name = $2
            ";
            
            $result = pg_query_params($db_conn, $query, [$value, $name]);
            
            if (!$result) {
                throw new Exception('Failed to update setting: ' . $name);
            }
            
            // Log the action
            logAudit(
                $db_conn, 
                $_SESSION['user_id'], 
                'Updated setting', 
                'settings', 
                null, 
                "Name: $name, Value: $value"
            );
        }
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getAuditLogs() {
    global $db_conn;
    
    // Only admin or moderator can view audit logs
    if (!isAdminOrModerator()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $action = sanitizeInput($_GET['log_action'] ?? '');
    $table_name = sanitizeInput($_GET['table_name'] ?? '');
    $start_date = sanitizeInput($_GET['start_date'] ?? '');
    $end_date = sanitizeInput($_GET['end_date'] ?? '');
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build the query
    $where_clauses = [];
    $params = [];
    $param_index = 1;
    
    if ($user_id) {
        $where_clauses[] = "al.user_id = $" . $param_index;
        $params[] = $user_id;
        $param_index++;
    }
    
    if (!empty($action)) {
        $where_clauses[] = "al.action ILIKE $" . $param_index;
        $params[] = "%$action%";
        $param_index++;
    }
    
    if (!empty($table_name)) {
        $where_clauses[] = "al.table_name = $" . $param_index;
        $params[] = $table_name;
        $param_index++;
    }
    
    if (!empty($start_date)) {
        $where_clauses[] = "al.created_at >= $" . $param_index;
        $params[] = $start_date;
        $param_index++;
    }
    
    if (!empty($end_date)) {
        $where_clauses[] = "al.created_at <= $" . $param_index . "::date + interval '1 day'";
        $params[] = $end_date;
        $param_index++;
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Count total records
    $count_query = "
        SELECT COUNT(*) 
        FROM audit_logs al
        $where_clause
    ";
    
    $count_result = pg_query_params($db_conn, $count_query, $params);
    $total_records = pg_fetch_result($count_result, 0, 0);
    
    // Get audit logs
    $query = "
        SELECT al.*, u.username
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $where_clause
        ORDER BY al.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $logs = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_date'] = formatDate(substr($row['created_at'], 0, 19));
        $logs[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $logs,
        'pagination' => [
            'total' => intval($total_records),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total_records / $per_page)
        ]
    ]);
}

function backupDatabase() {
    global $db_conn;
    
    // Only admin can backup the database
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    // Get database connection details from .env
    $env = parseEnvFile('../.env');
    
    $db_host = $env['DB_HOST'] ?? 'localhost';
    $db_name = $env['DB_NAME'] ?? 'xazqmkee_supreme_app';
    $db_user = $env['DB_USER'] ?? 'xazqmkee_supreme';
    $db_pass = $env['DB_PASS'] ?? '';
    
    // Tables to backup
    $tables = [
        'users', 'categories', 'products', 'production', 'customers', 
        'transactions', 'sales', 'sale_items', 'loadings', 'loading_items',
        'audit_logs', 'settings'
    ];
    
    $output = "-- Database backup for {$db_name}\n";
    $output .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $output .= "-- Table: {$table}\n";
        
        // Get create table statement
        $create_query = "
            SELECT 
                'CREATE TABLE ' || table_name || ' (' ||
                string_agg(
                    column_name || ' ' || data_type || 
                    CASE 
                        WHEN character_maximum_length IS NOT NULL THEN '(' || character_maximum_length || ')'
                        ELSE ''
                    END ||
                    CASE 
                        WHEN is_nullable = 'NO' THEN ' NOT NULL'
                        ELSE ''
                    END ||
                    CASE 
                        WHEN column_default IS NOT NULL THEN ' DEFAULT ' || column_default
                        ELSE ''
                    END,
                    ', '
                ) || ');' AS create_statement
            FROM 
                information_schema.columns
            WHERE 
                table_name = $1
            GROUP BY 
                table_name
        ";
        
        $create_result = pg_query_params($db_conn, $create_query, [$table]);
        
        if ($create_result && pg_num_rows($create_result) > 0) {
            $output .= pg_fetch_result($create_result, 0, 0) . "\n\n";
        }
        
        // Get data
        $data_query = "SELECT * FROM {$table}";
        $data_result = pg_query($db_conn, $data_query);
        
        if ($data_result && pg_num_rows($data_result) > 0) {
            $output .= "-- Data for table: {$table}\n";
            
            while ($row = pg_fetch_assoc($data_result)) {
                $columns = array_keys($row);
                $values = [];
                
                foreach ($row as $value) {
                    if (is_null($value)) {
                        $values[] = 'NULL';
                    } elseif (is_numeric($value)) {
                        $values[] = $value;
                    } else {
                        $values[] = "'" . pg_escape_string($value) . "'";
                    }
                }
                
                $output .= "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            
            $output .= "\n";
        }
    }
    
    // Log the action
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Generated database backup', 
        null, 
        null, 
        "Database: {$db_name}"
    );
    
    // Return the SQL as downloadable content
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="supreme_rice_mills_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    
    echo $output;
    exit;
}
?>