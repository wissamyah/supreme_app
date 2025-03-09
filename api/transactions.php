<?php
// Ledger/transaction API
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
            case 'ledger':
                getLedger();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'POST':
        switch ($action) {
            case 'payment':
                addPayment();
                break;
            case 'credit_note':
                addCreditNote();
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
                deleteTransaction();
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

function getLedger() {
    global $db_conn;
    
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
    $start_date = sanitizeInput($_GET['start_date'] ?? '');
    $end_date = sanitizeInput($_GET['end_date'] ?? '');
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    
    if ($customer_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
        return;
    }
    
    // Verify customer exists
    $customer_query = "SELECT name, balance FROM customers WHERE id = $1";
    $customer_result = pg_query_params($db_conn, $customer_query, [$customer_id]);
    
    if (!$customer_result || pg_num_rows($customer_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $customer = pg_fetch_assoc($customer_result);
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build the query
    $where_clauses = ["customer_id = $1"];
    $params = [$customer_id];
    $param_index = 2;
    
    if (!empty($start_date)) {
        $where_clauses[] = "transaction_date >= $" . $param_index;
        $params[] = $start_date;
        $param_index++;
    }
    
    if (!empty($end_date)) {
        $where_clauses[] = "transaction_date <= $" . $param_index;
        $params[] = $end_date;
        $param_index++;
    }
    
    $where_clause = implode(" AND ", $where_clauses);
    
    // Count total records
    $count_query = "SELECT COUNT(*) FROM transactions WHERE $where_clause";
    $count_result = pg_query_params($db_conn, $count_query, $params);
    $total_records = pg_fetch_result($count_result, 0, 0);
    
    // Get transactions
    $query = "
        SELECT t.*, 
               s.sale_date,
               CASE 
                   WHEN t.type = 'sale' THEN 'Sale' 
                   WHEN t.type = 'payment' THEN 'Payment' 
                   WHEN t.type = 'credit_note' THEN 'Credit Note' 
               END as type_name
        FROM transactions t
        LEFT JOIN sales s ON t.sale_id = s.id
        WHERE $where_clause
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Prepare transactions with running balance
    $transactions = [];
    
    // Get current customer balance
    $current_balance = floatval($customer['balance']);
    
    // Calculate starting balance for the current page
    if ($page > 1) {
        // Get total of transactions after the ones we're viewing
        $prev_query = "
            SELECT 
                SUM(CASE WHEN type = 'sale' THEN amount ELSE -amount END) as total
            FROM transactions 
            WHERE $where_clause
            AND id > (
                SELECT id FROM transactions 
                WHERE $where_clause
                ORDER BY transaction_date DESC, id DESC
                LIMIT 1 OFFSET " . ($offset - 1) . "
            )
        ";
        
        $prev_result = pg_query_params($db_conn, $prev_query, $params);
        $prev_total = pg_fetch_result($prev_result, 0, 0);
        
        // Starting balance = current balance - sum of transactions after our page
        $running_balance = $current_balance - floatval($prev_total);
    } else {
        $running_balance = $current_balance;
    }
    
    // Process transactions in reverse order to calculate running balance
    $temp_transactions = [];
    while ($row = pg_fetch_assoc($result)) {
        $temp_transactions[] = $row;
    }
    
    // Reverse the array to calculate balances from oldest to newest
    $temp_transactions = array_reverse($temp_transactions);
    
    foreach ($temp_transactions as $row) {
        if ($row['type'] === 'sale') {
            $running_balance -= floatval($row['amount']);
        } else {
            $running_balance += floatval($row['amount']);
        }
        
        $row['running_balance'] = $running_balance;
        $row['formatted_amount'] = formatCurrency($row['amount']);
        $row['formatted_balance'] = formatCurrency($running_balance);
        $row['formatted_date'] = formatDate($row['transaction_date']);
        
        // Add to the beginning (to reverse back to newest-first)
        array_unshift($transactions, $row);
    }
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'customer' => [
                'id' => $customer_id,
                'name' => $customer['name'],
                'balance' => $customer['balance'],
                'formatted_balance' => formatCurrency($customer['balance'])
            ],
            'transactions' => $transactions
        ],
        'pagination' => [
            'total' => intval($total_records),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total_records / $per_page)
        ]
    ]);
}

function addPayment() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $transaction_date = sanitizeInput($_POST['transaction_date'] ?? date('Y-m-d'));
    
    if ($customer_id <= 0 || $amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Customer ID and positive amount are required'
        ]);
        return;
    }
    
    // Verify customer exists
    $customer_query = "SELECT name, balance FROM customers WHERE id = $1";
    $customer_result = pg_query_params($db_conn, $customer_query, [$customer_id]);
    
    if (!$customer_result || pg_num_rows($customer_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $customer = pg_fetch_assoc($customer_result);
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Add payment transaction
        $query = "
            INSERT INTO transactions (customer_id, type, amount, description, transaction_date) 
            VALUES ($1, 'payment', $2, $3, $4) 
            RETURNING id
        ";
        
        $result = pg_query_params(
            $db_conn, 
            $query, 
            [$customer_id, $amount, $description, $transaction_date]
        );
        
        if (!$result) {
            throw new Exception('Failed to add payment transaction');
        }
        
        $transaction_id = pg_fetch_result($result, 0, 0);
        
        // Update customer balance
        $update_query = "
            UPDATE customers 
            SET balance = balance - $1 
            WHERE id = $2 
            RETURNING balance
        ";
        
        $update_result = pg_query_params($db_conn, $update_query, [$amount, $customer_id]);
        
        if (!$update_result) {
            throw new Exception('Failed to update customer balance');
        }
        
        $new_balance = pg_fetch_result($update_result, 0, 0);
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Added payment', 
            'transactions', 
            $transaction_id, 
            "Customer: {$customer['name']}, Amount: " . formatCurrency($amount) . 
            ", Date: $transaction_date, Description: $description"
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payment added successfully', 
            'data' => [
                'transaction_id' => $transaction_id,
                'customer_id' => $customer_id,
                'customer_name' => $customer['name'],
                'amount' => $amount,
                'formatted_amount' => formatCurrency($amount),
                'transaction_date' => $transaction_date,
                'formatted_date' => formatDate($transaction_date),
                'description' => $description,
                'new_balance' => $new_balance,
                'formatted_balance' => formatCurrency($new_balance)
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addCreditNote() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $transaction_date = sanitizeInput($_POST['transaction_date'] ?? date('Y-m-d'));
    
    if ($customer_id <= 0 || $amount <= 0 || empty($description)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Customer ID, positive amount, and description are required'
        ]);
        return;
    }
    
    // Verify customer exists
    $customer_query = "SELECT name, balance FROM customers WHERE id = $1";
    $customer_result = pg_query_params($db_conn, $customer_query, [$customer_id]);
    
    if (!$customer_result || pg_num_rows($customer_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $customer = pg_fetch_assoc($customer_result);
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Add credit note transaction
        $query = "
            INSERT INTO transactions (customer_id, type, amount, description, transaction_date) 
            VALUES ($1, 'credit_note', $2, $3, $4) 
            RETURNING id
        ";
        
        $result = pg_query_params(
            $db_conn, 
            $query, 
            [$customer_id, $amount, $description, $transaction_date]
        );
        
        if (!$result) {
            throw new Exception('Failed to add credit note transaction');
        }
        
        $transaction_id = pg_fetch_result($result, 0, 0);
        
        // Update customer balance
        $update_query = "
            UPDATE customers 
            SET balance = balance - $1 
            WHERE id = $2 
            RETURNING balance
        ";
        
        $update_result = pg_query_params($db_conn, $update_query, [$amount, $customer_id]);
        
        if (!$update_result) {
            throw new Exception('Failed to update customer balance');
        }
        
        $new_balance = pg_fetch_result($update_result, 0, 0);
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Added credit note', 
            'transactions', 
            $transaction_id, 
            "Customer: {$customer['name']}, Amount: " . formatCurrency($amount) . 
            ", Date: $transaction_date, Description: $description"
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Credit note added successfully', 
            'data' => [
                'transaction_id' => $transaction_id,
                'customer_id' => $customer_id,
                'customer_name' => $customer['name'],
                'amount' => $amount,
                'formatted_amount' => formatCurrency($amount),
                'transaction_date' => $transaction_date,
                'formatted_date' => formatDate($transaction_date),
                'description' => $description,
                'new_balance' => $new_balance,
                'formatted_balance' => formatCurrency($new_balance)
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteTransaction() {
    global $db_conn;
    
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
        return;
    }
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Get transaction details
        $details_query = "
            SELECT t.*, c.name as customer_name 
            FROM transactions t
            JOIN customers c ON t.customer_id = c.id
            WHERE t.id = $1
        ";
        
        $details_result = pg_query_params($db_conn, $details_query, [$id]);
        
        if (!$details_result || pg_num_rows($details_result) === 0) {
            throw new Exception('Transaction not found');
        }
        
        $transaction = pg_fetch_assoc($details_result);
        
        // Check if transaction is associated with a sale
        if (!is_null($transaction['sale_id'])) {
            throw new Exception('Cannot delete transaction associated with a sale');
        }
        
        // Update customer balance
        $update_query = "
            UPDATE customers 
            SET balance = balance " . 
            ($transaction['type'] === 'sale' ? "-" : "+") . " $1 
            WHERE id = $2 
            RETURNING balance
        ";
        
        $update_result = pg_query_params(
            $db_conn, 
            $update_query, 
            [$transaction['amount'], $transaction['customer_id']]
        );
        
        if (!$update_result) {
            throw new Exception('Failed to update customer balance');
        }
        
        // Delete the transaction
        $query = "DELETE FROM transactions WHERE id = $1";
        $result = pg_query_params($db_conn, $query, [$id]);
        
        if (!$result) {
            throw new Exception('Failed to delete transaction');
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Deleted transaction', 
            'transactions', 
            $id, 
            "Customer: {$transaction['customer_name']}, Type: {$transaction['type']}, " .
            "Amount: " . formatCurrency($transaction['amount']) . 
            ", Date: {$transaction['transaction_date']}"
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>