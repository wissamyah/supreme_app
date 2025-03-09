<?php
// Customers CRUD API
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
            case 'list':
                getCustomers();
                break;
            case 'get':
                getCustomer();
                break;
            case 'top':
                getTopCustomers();
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
                addCustomer();
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
                updateCustomer();
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
                deleteCustomer();
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

function getCustomers() {
    global $db_conn;
    
    $search = sanitizeInput($_GET['search'] ?? '');
    $state = sanitizeInput($_GET['state'] ?? '');
    $has_balance = isset($_GET['has_balance']) ? filter_var($_GET['has_balance'], FILTER_VALIDATE_BOOLEAN) : null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build the query
    $where_clauses = [];
    $params = [];
    $param_index = 1;
    
    if (!empty($search)) {
        $where_clauses[] = "(name ILIKE $" . $param_index . " OR phone ILIKE $" . $param_index . ")";
        $params[] = "%$search%";
        $param_index++;
    }
    
    if (!empty($state)) {
        $where_clauses[] = "state = $" . $param_index;
        $params[] = $state;
        $param_index++;
    }
    
    if ($has_balance !== null) {
        if ($has_balance) {
            $where_clauses[] = "balance > 0";
        } else {
            $where_clauses[] = "balance <= 0";
        }
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Count total records
    $count_query = "SELECT COUNT(*) FROM customers $where_clause";
    $count_result = pg_query_params($db_conn, $count_query, $params);
    $total_records = pg_fetch_result($count_result, 0, 0);
    
    // Get customers
    $query = "
        SELECT *
        FROM customers 
        $where_clause
        ORDER BY name
        LIMIT $per_page OFFSET $offset
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $customers = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_balance'] = formatCurrency($row['balance']);
        $customers[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $customers,
        'pagination' => [
            'total' => intval($total_records),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total_records / $per_page)
        ]
    ]);
}

function getCustomer() {
    global $db_conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
        return;
    }
    
    $query = "SELECT * FROM customers WHERE id = $1";
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $customer = pg_fetch_assoc($result);
    $customer['formatted_balance'] = formatCurrency($customer['balance']);
    
    echo json_encode(['success' => true, 'data' => $customer]);
}

function getTopCustomers() {
    global $db_conn;
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    
    $query = "
        SELECT *
        FROM customers 
        WHERE balance > 0
        ORDER BY balance DESC
        LIMIT $1
    ";
    
    $result = pg_query_params($db_conn, $query, [$limit]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $customers = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_balance'] = formatCurrency($row['balance']);
        $customers[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $customers]);
}

function addCustomer() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $balance = isset($_POST['balance']) ? floatval($_POST['balance']) : 0;
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Customer name is required']);
        return;
    }
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Insert customer
        $query = "
            INSERT INTO customers (name, phone, state, balance) 
            VALUES ($1, $2, $3, $4) 
            RETURNING *
        ";
        
        $result = pg_query_params($db_conn, $query, [$name, $phone, $state, $balance]);
        
        if (!$result) {
            throw new Exception('Failed to add customer');
        }
        
        $customer = pg_fetch_assoc($result);
        
        // If initial balance is not zero, add to transaction ledger
        if ($balance != 0) {
            $type = $balance > 0 ? 'sale' : 'payment';
            $description = $balance > 0 ? 'Initial balance' : 'Initial credit';
            $amount = abs($balance);
            
            $trans_query = "
                INSERT INTO transactions (customer_id, type, amount, description, transaction_date) 
                VALUES ($1, $2, $3, $4, CURRENT_DATE) 
                RETURNING id
            ";
            
            $trans_result = pg_query_params(
                $db_conn, 
                $trans_query, 
                [$customer['id'], $type, $amount, $description]
            );
            
            if (!$trans_result) {
                throw new Exception('Failed to add initial transaction');
            }
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Added customer', 
            'customers', 
            $customer['id'], 
            "Name: $name, Phone: $phone, State: $state, Initial Balance: " . formatCurrency($balance)
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        $customer['formatted_balance'] = formatCurrency($customer['balance']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Customer added successfully', 
            'data' => $customer
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateCustomer() {
    global $db_conn;
    
    if (!hasPermission('edit')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $name = sanitizeInput($input['name'] ?? '');
    $phone = sanitizeInput($input['phone'] ?? '');
    $state = sanitizeInput($input['state'] ?? '');
    
    if ($id <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Get current customer data for logging
    $current_query = "SELECT name, phone, state FROM customers WHERE id = $1";
    $current_result = pg_query_params($db_conn, $current_query, [$id]);
    
    if (!$current_result || pg_num_rows($current_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $current = pg_fetch_assoc($current_result);
    
    $query = "
        UPDATE customers 
        SET name = $1, phone = $2, state = $3 
        WHERE id = $4 
        RETURNING *
    ";
    
    $result = pg_query_params($db_conn, $query, [$name, $phone, $state, $id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $customer = pg_fetch_assoc($result);
    $customer['formatted_balance'] = formatCurrency($customer['balance']);
    
    // Log the action
    $change_log = "Changed:";
    if ($current['name'] !== $name) $change_log .= " Name from '{$current['name']}' to '$name',";
    if ($current['phone'] !== $phone) $change_log .= " Phone from '{$current['phone']}' to '$phone',";
    if ($current['state'] !== $state) $change_log .= " State from '{$current['state']}' to '$state',";
    $change_log = rtrim($change_log, ',');
    
    logAudit($db_conn, $_SESSION['user_id'], 'Updated customer', 'customers', $id, $change_log);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Customer updated successfully', 
        'data' => $customer
    ]);
}

function deleteCustomer() {
    global $db_conn;
    
    if (!hasPermission('delete_own_records') && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
        return;
    }
    
    // Check if customer has transactions
    $check_query = "
        SELECT 
            (SELECT COUNT(*) FROM sales WHERE customer_id = $1) +
            (SELECT COUNT(*) FROM loadings WHERE customer_id = $1) +
            (SELECT COUNT(*) FROM transactions WHERE customer_id = $1) as total_usage
    ";
    
    $check_result = pg_query_params($db_conn, $check_query, [$id]);
    $total_usage = pg_fetch_result($check_result, 0, 0);
    
    if ($total_usage > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete customer with associated transactions']);
        return;
    }
    
    // Get customer data for logging
    $name_query = "SELECT name FROM customers WHERE id = $1";
    $name_result = pg_query_params($db_conn, $name_query, [$id]);
    
    if (!$name_result || pg_num_rows($name_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $customer_name = pg_fetch_result($name_result, 0, 0);
    
    $query = "DELETE FROM customers WHERE id = $1";
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Log the action
    logAudit($db_conn, $_SESSION['user_id'], 'Deleted customer', 'customers', $id, "Name: $customer_name");
    
    echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
}
?>