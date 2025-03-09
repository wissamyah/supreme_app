<?php
// Sales CRUD API
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
                getSales();
                break;
            case 'get':
                getSale();
                break;
            case 'recent':
                getRecentSales();
                break;
            case 'customer':
                getCustomerSales();
                break;
            case 'monthly':
                getMonthlySales();
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
                addSale();
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
                updateSale();
                break;
            case 'loading_status':
                updateLoadingStatus();
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
                deleteSale();
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

function getSales() {
    global $db_conn;
    
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
    $loading_status = sanitizeInput($_GET['loading_status'] ?? '');
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
    
    if ($customer_id) {
        $where_clauses[] = "s.customer_id = $" . $param_index;
        $params[] = $customer_id;
        $param_index++;
    }
    
    if (!empty($loading_status)) {
        $where_clauses[] = "s.loading_status = $" . $param_index;
        $params[] = $loading_status;
        $param_index++;
    }
    
    if (!empty($start_date)) {
        $where_clauses[] = "s.sale_date >= $" . $param_index;
        $params[] = $start_date;
        $param_index++;
    }
    
    if (!empty($end_date)) {
        $where_clauses[] = "s.sale_date <= $" . $param_index;
        $params[] = $end_date;
        $param_index++;
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Count total records
    $count_query = "
        SELECT COUNT(*) 
        FROM sales s
        $where_clause
    ";
    
    $count_result = pg_query_params($db_conn, $count_query, $params);
    $total_records = pg_fetch_result($count_result, 0, 0);
    
    // Get sales
    $query = "
        SELECT s.*, c.name as customer_name
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        $where_clause
        ORDER BY s.sale_date DESC, s.id DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $sales = [];
    while ($row = pg_fetch_assoc($result)) {
        // Format data for display
        $row['formatted_date'] = formatDate($row['sale_date']);
        $row['formatted_amount'] = formatCurrency($row['total_amount']);
        $sales[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $sales,
        'pagination' => [
            'total' => intval($total_records),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total_records / $per_page)
        ]
    ]);
}

function getSale() {
    global $db_conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid sale ID']);
        return;
    }
    
    // Get sale details
    $query = "
        SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.state as customer_state
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        WHERE s.id = $1
    ";
    
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        return;
    }
    
    $sale = pg_fetch_assoc($result);
    $sale['formatted_date'] = formatDate($sale['sale_date']);
    $sale['formatted_amount'] = formatCurrency($sale['total_amount']);
    
    // Get sale items
    $items_query = "
        SELECT si.*, p.name as product_name, c.name as category_name
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE si.sale_id = $1
        ORDER BY si.id
    ";
    
    $items_result = pg_query_params($db_conn, $items_query, [$id]);
    
    if (!$items_result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $items = [];
    while ($row = pg_fetch_assoc($items_result)) {
        $row['subtotal'] = floatval($row['quantity']) * floatval($row['rate']);
        $row['formatted_rate'] = formatCurrency($row['rate']);
        $row['formatted_subtotal'] = formatCurrency($row['subtotal']);
        $items[] = $row;
    }
    
    $sale['items'] = $items;
    
    echo json_encode(['success' => true, 'data' => $sale]);
}

function getRecentSales() {
    global $db_conn;
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    
    $query = "
        SELECT s.*, c.name as customer_name
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        ORDER BY s.sale_date DESC, s.id DESC
        LIMIT $1
    ";
    
    $result = pg_query_params($db_conn, $query, [$limit]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $sales = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_date'] = formatDate($row['sale_date']);
        $row['formatted_amount'] = formatCurrency($row['total_amount']);
        $sales[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $sales]);
}

function getCustomerSales() {
    global $db_conn;
    
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
    
    if ($customer_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
        return;
    }
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Verify customer exists
    $customer_query = "SELECT name FROM customers WHERE id = $1";
    $customer_result = pg_query_params($db_conn, $customer_query, [$customer_id]);
    
    if (!$customer_result || pg_num_rows($customer_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $customer_name = pg_fetch_result($customer_result, 0, 0);
    
    $query = "
        SELECT s.*
        FROM sales s
        WHERE s.customer_id = $1
        ORDER BY s.sale_date DESC, s.id DESC
        LIMIT $2
    ";
    
    $result = pg_query_params($db_conn, $query, [$customer_id, $limit]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $sales = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_date'] = formatDate($row['sale_date']);
        $row['formatted_amount'] = formatCurrency($row['total_amount']);
        $sales[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'sales' => $sales
        ]
    ]);
}

function getMonthlySales() {
    global $db_conn;
    
    $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
    
    $query = "
        SELECT 
            to_char(sale_date, 'YYYY-MM') as month,
            SUM(total_amount) as total
        FROM sales
        WHERE sale_date >= (CURRENT_DATE - INTERVAL '$1 months')
        GROUP BY to_char(sale_date, 'YYYY-MM')
        ORDER BY month
    ";
    
    $result = pg_query_params($db_conn, $query, [$months]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $monthly_sales = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_total'] = formatCurrency($row['total']);
        $monthly_sales[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $monthly_sales]);
}

function addSale() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    // Parse JSON data from POST request
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $sale_date = sanitizeInput($_POST['sale_date'] ?? date('Y-m-d'));
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    if ($customer_id <= 0 || empty($items)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Customer ID and at least one item are required'
        ]);
        return;
    }
    
    // Verify customer exists
    $customer_query = "SELECT name FROM customers WHERE id = $1";
    $customer_result = pg_query_params($db_conn, $customer_query, [$customer_id]);
    
    if (!$customer_result || pg_num_rows($customer_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    $customer_name = pg_fetch_result($customer_result, 0, 0);
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Calculate total amount
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += floatval($item['quantity']) * floatval($item['rate']);
        }
        
        // Create sale
        $query = "
            INSERT INTO sales (customer_id, sale_date, total_amount, loading_status) 
            VALUES ($1, $2, $3, 'Pending') 
            RETURNING id
        ";
        
        $result = pg_query_params($db_conn, $query, [$customer_id, $sale_date, $total_amount]);
        
        if (!$result) {
            throw new Exception('Failed to create sale');
        }
        
        $sale_id = pg_fetch_result($result, 0, 0);
        
        // Add sale items and update product booked stock
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            $rate = floatval($item['rate']);
            
            if ($product_id <= 0 || $quantity <= 0 || $rate <= 0) {
                throw new Exception('Invalid product data');
            }
            
            // Get product details
            $product_query = "SELECT name, physical_stock, booked_stock FROM products WHERE id = $1";
            $product_result = pg_query_params($db_conn, $product_query, [$product_id]);
            
            if (!$product_result || pg_num_rows($product_result) === 0) {
                throw new Exception('Product not found: ID ' . $product_id);
            }
            
            $product = pg_fetch_assoc($product_result);
            
            // Check if there's enough available stock
            $available_stock = $product['physical_stock'] - $product['booked_stock'];
            if ($available_stock < $quantity) {
                throw new Exception("Not enough available stock for {$product['name']}");
            }
            
            // Insert sale item
            $item_query = "
                INSERT INTO sale_items (sale_id, product_id, quantity, rate) 
                VALUES ($1, $2, $3, $4)
            ";
            
            $item_result = pg_query_params($db_conn, $item_query, [$sale_id, $product_id, $quantity, $rate]);
            
            if (!$item_result) {
                throw new Exception('Failed to add sale item');
            }
            
            // Update product booked stock
            $update_query = "
                UPDATE products 
                SET booked_stock = booked_stock + $1 
                WHERE id = $2
            ";
            
            $update_result = pg_query_params($db_conn, $update_query, [$quantity, $product_id]);
            
            if (!$update_result) {
                throw new Exception('Failed to update product booked stock');
            }
        }
        
        // Add transaction to customer ledger
        $transaction_query = "
            INSERT INTO transactions (customer_id, type, amount, description, transaction_date, sale_id) 
            VALUES ($1, 'sale', $2, 'Sale #$3', $4, $5)
        ";
        
        $transaction_result = pg_query_params(
            $db_conn, 
            $transaction_query, 
            [$customer_id, $total_amount, $sale_id, $sale_date, $sale_id]
        );
        
        if (!$transaction_result) {
            throw new Exception('Failed to add transaction to ledger');
        }
        
        // Update customer balance
        $balance_query = "
            UPDATE customers 
            SET balance = balance + $1 
            WHERE id = $2
        ";
        
        $balance_result = pg_query_params($db_conn, $balance_query, [$total_amount, $customer_id]);
        
        if (!$balance_result) {
            throw new Exception('Failed to update customer balance');
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Created sale', 
            'sales', 
            $sale_id, 
            "Customer: $customer_name, Amount: " . formatCurrency($total_amount) . 
            ", Date: $sale_date, Items: " . count($items)
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sale created successfully', 
            'data' => [
                'id' => $sale_id,
                'customer_id' => $customer_id,
                'customer_name' => $customer_name,
                'sale_date' => $sale_date,
                'formatted_date' => formatDate($sale_date),
                'total_amount' => $total_amount,
                'formatted_amount' => formatCurrency($total_amount),
                'loading_status' => 'Pending'
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateSale() {
    global $db_conn;
    
    if (!hasPermission('edit')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $sale_date = sanitizeInput($input['sale_date'] ?? '');
    
    if ($id <= 0 || empty($sale_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Check if sale exists and get current data
    $check_query = "
        SELECT s.*, c.name as customer_name 
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        WHERE s.id = $1
    ";
    
    $check_result = pg_query_params($db_conn, $check_query, [$id]);
    
    if (!$check_result || pg_num_rows($check_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        return;
    }
    
    $current_sale = pg_fetch_assoc($check_result);
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Update sale date
        $query = "
            UPDATE sales 
            SET sale_date = $1 
            WHERE id = $2 
            RETURNING *
        ";
        
        $result = pg_query_params($db_conn, $query, [$sale_date, $id]);
        
        if (!$result) {
            throw new Exception('Failed to update sale');
        }
        
        $sale = pg_fetch_assoc($result);
        
        // Update transaction date
        $trans_query = "
            UPDATE transactions 
            SET transaction_date = $1 
            WHERE sale_id = $2
        ";
        
        $trans_result = pg_query_params($db_conn, $trans_query, [$sale_date, $id]);
        
        if (!$trans_result) {
            throw new Exception('Failed to update transaction date');
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Updated sale', 
            'sales', 
            $id, 
            "Customer: {$current_sale['customer_name']}, Changed date from: {$current_sale['sale_date']} to: $sale_date"
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sale updated successfully',
            'data' => [
                'id' => $sale['id'],
                'customer_id' => $sale['customer_id'],
                'customer_name' => $current_sale['customer_name'],
                'sale_date' => $sale['sale_date'],
                'formatted_date' => formatDate($sale['sale_date']),
                'total_amount' => $sale['total_amount'],
                'formatted_amount' => formatCurrency($sale['total_amount']),
                'loading_status' => $sale['loading_status']
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateLoadingStatus() {
    global $db_conn;
    
    if (!hasPermission('edit')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $status = sanitizeInput($input['status'] ?? '');
    
    if ($id <= 0 || empty($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Validate status
    $valid_statuses = ['Pending', 'Partially Loaded', 'Fully Loaded'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid loading status']);
        return;
    }
    
    // Check if sale exists and get current data
    $check_query = "
        SELECT s.*, c.name as customer_name 
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        WHERE s.id = $1
    ";
    
    $check_result = pg_query_params($db_conn, $check_query, [$id]);
    
    if (!$check_result || pg_num_rows($check_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        return;
    }
    
    $current_sale = pg_fetch_assoc($check_result);
    
    // Update loading status
    $query = "
        UPDATE sales 
        SET loading_status = $1 
        WHERE id = $2 
        RETURNING *
    ";
    
    $result = pg_query_params($db_conn, $query, [$status, $id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $sale = pg_fetch_assoc($result);
    
    // Log the action
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Updated sale loading status', 
        'sales', 
        $id, 
        "Customer: {$current_sale['customer_name']}, Changed status from: {$current_sale['loading_status']} to: $status"
    );
    
    echo json_encode([
        'success' => true, 
        'message' => 'Loading status updated successfully',
        'data' => [
            'id' => $sale['id'],
            'customer_id' => $sale['customer_id'],
            'customer_name' => $current_sale['customer_name'],
            'sale_date' => $sale['sale_date'],
            'formatted_date' => formatDate($sale['sale_date']),
            'total_amount' => $sale['total_amount'],
            'formatted_amount' => formatCurrency($sale['total_amount']),
            'loading_status' => $sale['loading_status']
        ]
    ]);
}

function deleteSale() {
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
        echo json_encode(['success' => false, 'message' => 'Invalid sale ID']);
        return;
    }
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Check if sale exists and get data for logging and reverting
        $sale_query = "
            SELECT s.*, c.name as customer_name 
            FROM sales s
            JOIN customers c ON s.customer_id = c.id
            WHERE s.id = $1
        ";
        
        $sale_result = pg_query_params($db_conn, $sale_query, [$id]);
        
        if (!$sale_result || pg_num_rows($sale_result) === 0) {
            throw new Exception('Sale not found');
        }
        
        $sale = pg_fetch_assoc($sale_result);
        
        // Check if sale has been loaded
        if ($sale['loading_status'] !== 'Pending') {
            throw new Exception('Cannot delete sale that has been loaded');
        }
        
        // Get sale items
        $items_query = "
            SELECT si.*, p.name as product_name
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = $1
        ";
        
        $items_result = pg_query_params($db_conn, $items_query, [$id]);
        
        if (!$items_result) {
            throw new Exception('Failed to retrieve sale items');
        }
        
        $items = [];
        while ($row = pg_fetch_assoc($items_result)) {
            $items[] = $row;
            
            // Update product booked stock
            $update_query = "
                UPDATE products 
                SET booked_stock = booked_stock - $1 
                WHERE id = $2
            ";
            
            $update_result = pg_query_params(
                $db_conn, 
                $update_query, 
                [$row['quantity'], $row['product_id']]
            );
            
            if (!$update_result) {
                throw new Exception('Failed to update product booked stock');
            }
        }
        
        // Delete transaction related to this sale
        $trans_query = "DELETE FROM transactions WHERE sale_id = $1";
        $trans_result = pg_query_params($db_conn, $trans_query, [$id]);
        
        if (!$trans_result) {
            throw new Exception('Failed to delete transaction');
        }
        
        // Update customer balance
        $balance_query = "
            UPDATE customers 
            SET balance = balance - $1 
            WHERE id = $2
        ";
        
        $balance_result = pg_query_params(
            $db_conn, 
            $balance_query, 
            [$sale['total_amount'], $sale['customer_id']]
        );
        
        if (!$balance_result) {
            throw new Exception('Failed to update customer balance');
        }
        
        // Delete sale items
        $delete_items_query = "DELETE FROM sale_items WHERE sale_id = $1";
        $delete_items_result = pg_query_params($db_conn, $delete_items_query, [$id]);
        
        if (!$delete_items_result) {
            throw new Exception('Failed to delete sale items');
        }
        
        // Finally, delete the sale
        $delete_query = "DELETE FROM sales WHERE id = $1";
        $delete_result = pg_query_params($db_conn, $delete_query, [$id]);
        
        if (!$delete_result) {
            throw new Exception('Failed to delete sale');
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Deleted sale', 
            'sales', 
            $id, 
            "Customer: {$sale['customer_name']}, Amount: " . formatCurrency($sale['total_amount']) . 
            ", Date: {$sale['sale_date']}, Items: " . count($items)
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode(['success' => true, 'message' => 'Sale deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>