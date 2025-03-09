<?php
// Loading CRUD API
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
                getLoadings();
                break;
            case 'get':
                getLoading();
                break;
            case 'recent':
                getRecentLoadings();
                break;
            case 'customer':
                getCustomerLoadings();
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
                addLoading();
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
                updateLoading();
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
                deleteLoading();
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

function getLoadings() {
    global $db_conn;
    
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
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
        $where_clauses[] = "l.customer_id = $" . $param_index;
        $params[] = $customer_id;
        $param_index++;
    }
    
    if (!empty($start_date)) {
        $where_clauses[] = "l.loading_date >= $" . $param_index;
        $params[] = $start_date;
        $param_index++;
    }
    
    if (!empty($end_date)) {
        $where_clauses[] = "l.loading_date <= $" . $param_index;
        $params[] = $end_date;
        $param_index++;
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Count total records
    $count_query = "
        SELECT COUNT(*) 
        FROM loadings l
        $where_clause
    ";
    
    $count_result = pg_query_params($db_conn, $count_query, $params);
    $total_records = pg_fetch_result($count_result, 0, 0);
    
    // Get loadings
    $query = "
        SELECT l.*, c.name as customer_name,
               (SELECT COUNT(*) FROM loading_items WHERE loading_id = l.id) as item_count
        FROM loadings l
        JOIN customers c ON l.customer_id = c.id
        $where_clause
        ORDER BY l.loading_date DESC, l.id DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $loadings = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_date'] = formatDate($row['loading_date']);
        $loadings[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $loadings,
        'pagination' => [
            'total' => intval($total_records),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total_records / $per_page)
        ]
    ]);
}

function getLoading() {
    global $db_conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid loading ID']);
        return;
    }
    
    // Get loading details
    $query = "
        SELECT l.*, c.name as customer_name, c.phone as customer_phone, c.state as customer_state
        FROM loadings l
        JOIN customers c ON l.customer_id = c.id
        WHERE l.id = $1
    ";
    
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Loading not found']);
        return;
    }
    
    $loading = pg_fetch_assoc($result);
    $loading['formatted_date'] = formatDate($loading['loading_date']);
    
    // Get loading items
    $items_query = "
        SELECT li.*, p.name as product_name, c.name as category_name
        FROM loading_items li
        JOIN products p ON li.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE li.loading_id = $1
        ORDER BY li.id
    ";
    
    $items_result = pg_query_params($db_conn, $items_query, [$id]);
    
    if (!$items_result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $items = [];
    while ($row = pg_fetch_assoc($items_result)) {
        $items[] = $row;
    }
    
    $loading['items'] = $items;
    
    echo json_encode(['success' => true, 'data' => $loading]);
}

function getRecentLoadings() {
    global $db_conn;
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    
    $query = "
        SELECT l.*, c.name as customer_name,
               (SELECT COUNT(*) FROM loading_items WHERE loading_id = l.id) as item_count
        FROM loadings l
        JOIN customers c ON l.customer_id = c.id
        ORDER BY l.loading_date DESC, l.id DESC
        LIMIT $1
    ";
    
    $result = pg_query_params($db_conn, $query, [$limit]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $loadings = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_date'] = formatDate($row['loading_date']);
        $loadings[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $loadings]);
}

function getCustomerLoadings() {
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
        SELECT l.*,
               (SELECT COUNT(*) FROM loading_items WHERE loading_id = l.id) as item_count
        FROM loadings l
        WHERE l.customer_id = $1
        ORDER BY l.loading_date DESC, l.id DESC
        LIMIT $2
    ";
    
    $result = pg_query_params($db_conn, $query, [$customer_id, $limit]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $loadings = [];
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_date'] = formatDate($row['loading_date']);
        $loadings[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'loadings' => $loadings
        ]
    ]);
}

function addLoading() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    // Parse JSON data from POST request
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $loading_date = sanitizeInput($_POST['loading_date'] ?? date('Y-m-d'));
    $truck_number = sanitizeInput($_POST['truck_number'] ?? '');
    $waybill = sanitizeInput($_POST['waybill'] ?? '');
    $driver_name = sanitizeInput($_POST['driver_name'] ?? '');
    $driver_phone = sanitizeInput($_POST['driver_phone'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    if ($customer_id <= 0 || empty($truck_number) || empty($items)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Customer ID, truck number, and at least one item are required'
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
        // Create loading
        $query = "
            INSERT INTO loadings (customer_id, loading_date, truck_number, waybill, driver_name, driver_phone) 
            VALUES ($1, $2, $3, $4, $5, $6) 
            RETURNING id
        ";
        
        $result = pg_query_params(
            $db_conn, 
            $query, 
            [$customer_id, $loading_date, $truck_number, $waybill, $driver_name, $driver_phone]
        );
        
        if (!$result) {
            throw new Exception('Failed to create loading');
        }
        
        $loading_id = pg_fetch_result($result, 0, 0);
        
        // Process items, validate stock, and update
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            
            if ($product_id <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product data');
            }
            
            // Get product details
            $product_query = "SELECT name, physical_stock, booked_stock FROM products WHERE id = $1";
            $product_result = pg_query_params($db_conn, $product_query, [$product_id]);
            
            if (!$product_result || pg_num_rows($product_result) === 0) {
                throw new Exception('Product not found: ID ' . $product_id);
            }
            
            $product = pg_fetch_assoc($product_result);
            
            // Check if there's enough physical stock
            if ($product['physical_stock'] < $quantity) {
                throw new Exception("Not enough physical stock for {$product['name']}");
            }
            
            // Insert loading item
            $item_query = "
                INSERT INTO loading_items (loading_id, product_id, quantity) 
                VALUES ($1, $2, $3)
            ";
            
            $item_result = pg_query_params($db_conn, $item_query, [$loading_id, $product_id, $quantity]);
            
            if (!$item_result) {
                throw new Exception('Failed to add loading item');
            }
            
            // Update product stock
            $update_query = "
                UPDATE products 
                SET physical_stock = physical_stock - $1,
                    booked_stock = GREATEST(0, booked_stock - $1)
                WHERE id = $2
            ";
            
            $update_result = pg_query_params($db_conn, $update_query, [$quantity, $product_id]);
            
            if (!$update_result) {
                throw new Exception('Failed to update product stock');
            }
        }
        
        // Update sales loading status
        // Check which sales are affected by this loading
        $sales_query = "
            SELECT s.id, s.loading_status, 
                   (SELECT SUM(si.quantity) FROM sale_items si WHERE si.sale_id = s.id) as total_sale_quantity,
                   (
                       SELECT COALESCE(SUM(li.quantity), 0)
                       FROM loading_items li
                       JOIN loadings l ON li.loading_id = l.id
                       WHERE l.customer_id = s.customer_id
                   ) as total_loaded_quantity
            FROM sales s
            WHERE s.customer_id = $1 AND s.loading_status != 'Fully Loaded'
            ORDER BY s.sale_date
        ";
        
        $sales_result = pg_query_params($db_conn, $sales_query, [$customer_id]);
        
        while ($sale = pg_fetch_assoc($sales_result)) {
            $status = $sale['total_loaded_quantity'] >= $sale['total_sale_quantity'] ? 
                      'Fully Loaded' : 'Partially Loaded';
            
            if ($status !== $sale['loading_status']) {
                $update_sale_query = "
                    UPDATE sales 
                    SET loading_status = $1 
                    WHERE id = $2
                ";
                
                pg_query_params($db_conn, $update_sale_query, [$status, $sale['id']]);
            }
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Created loading', 
            'loadings', 
            $loading_id, 
            "Customer: $customer_name, Date: $loading_date, Truck: $truck_number, Items: " . count($items)
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Loading created successfully', 
            'data' => [
                'id' => $loading_id,
                'customer_id' => $customer_id,
                'customer_name' => $customer_name,
                'loading_date' => $loading_date,
                'formatted_date' => formatDate($loading_date),
                'truck_number' => $truck_number,
                'waybill' => $waybill,
                'driver_name' => $driver_name,
                'driver_phone' => $driver_phone,
                'item_count' => count($items)
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateLoading() {
    global $db_conn;
    
    if (!hasPermission('edit')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $loading_date = sanitizeInput($input['loading_date'] ?? '');
    $truck_number = sanitizeInput($input['truck_number'] ?? '');
    $waybill = sanitizeInput($input['waybill'] ?? '');
    $driver_name = sanitizeInput($input['driver_name'] ?? '');
    $driver_phone = sanitizeInput($input['driver_phone'] ?? '');
    
    if ($id <= 0 || empty($loading_date) || empty($truck_number)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Check if loading exists and get current data
    $check_query = "
        SELECT l.*, c.name as customer_name 
        FROM loadings l
        JOIN customers c ON l.customer_id = c.id
        WHERE l.id = $1
    ";
    
    $check_result = pg_query_params($db_conn, $check_query, [$id]);
    
    if (!$check_result || pg_num_rows($check_result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Loading not found']);
        return;
    }
    
    $current_loading = pg_fetch_assoc($check_result);
    
    // Update loading
    $query = "
        UPDATE loadings 
        SET loading_date = $1, truck_number = $2, waybill = $3, driver_name = $4, driver_phone = $5 
        WHERE id = $6 
        RETURNING *
    ";
    
    $result = pg_query_params(
        $db_conn, 
        $query, 
        [$loading_date, $truck_number, $waybill, $driver_name, $driver_phone, $id]
    );
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $loading = pg_fetch_assoc($result);
    
    // Log the action
    $changes = [];
    if ($current_loading['loading_date'] !== $loading_date) $changes[] = "Date changed";
    if ($current_loading['truck_number'] !== $truck_number) $changes[] = "Truck number changed";
    if ($current_loading['waybill'] !== $waybill) $changes[] = "Waybill changed";
    if ($current_loading['driver_name'] !== $driver_name) $changes[] = "Driver name changed";
    if ($current_loading['driver_phone'] !== $driver_phone) $changes[] = "Driver phone changed";
    
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Updated loading', 
        'loadings', 
        $id, 
        "Customer: {$current_loading['customer_name']}, Changes: " . implode(", ", $changes)
    );
    
    echo json_encode([
        'success' => true, 
        'message' => 'Loading updated successfully',
        'data' => [
            'id' => $loading['id'],
            'customer_id' => $loading['customer_id'],
            'customer_name' => $current_loading['customer_name'],
            'loading_date' => $loading['loading_date'],
            'formatted_date' => formatDate($loading['loading_date']),
            'truck_number' => $loading['truck_number'],
            'waybill' => $loading['waybill'],
            'driver_name' => $loading['driver_name'],
            'driver_phone' => $loading['driver_phone']
        ]
    ]);
}

function deleteLoading() {
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
        echo json_encode(['success' => false, 'message' => 'Invalid loading ID']);
        return;
    }
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Check if loading exists and get data for logging and reverting
        $loading_query = "
            SELECT l.*, c.name as customer_name 
            FROM loadings l
            JOIN customers c ON l.customer_id = c.id
            WHERE l.id = $1
        ";
        
        $loading_result = pg_query_params($db_conn, $loading_query, [$id]);
        
        if (!$loading_result || pg_num_rows($loading_result) === 0) {
            throw new Exception('Loading not found');
        }
        
        $loading = pg_fetch_assoc($loading_result);
        
        // Get loading items
        $items_query = "
            SELECT li.*, p.name as product_name
            FROM loading_items li
            JOIN products p ON li.product_id = p.id
            WHERE li.loading_id = $1
        ";
        
        $items_result = pg_query_params($db_conn, $items_query, [$id]);
        
        if (!$items_result) {
            throw new Exception('Failed to retrieve loading items');
        }
        
        $items = [];
        while ($row = pg_fetch_assoc($items_result)) {
            $items[] = $row;
            
            // Restore product physical and booked stock
            $update_query = "
                UPDATE products 
                SET physical_stock = physical_stock + $1,
                    booked_stock = booked_stock + $1
                WHERE id = $2
            ";
            
            $update_result = pg_query_params(
                $db_conn, 
                $update_query, 
                [$row['quantity'], $row['product_id']]
            );
            
            if (!$update_result) {
                throw new Exception('Failed to update product stock');
            }
        }
        
        // Delete loading items
        $delete_items_query = "DELETE FROM loading_items WHERE loading_id = $1";
        $delete_items_result = pg_query_params($db_conn, $delete_items_query, [$id]);
        
        if (!$delete_items_result) {
            throw new Exception('Failed to delete loading items');
        }
        
        // Finally, delete the loading
        $delete_query = "DELETE FROM loadings WHERE id = $1";
        $delete_result = pg_query_params($db_conn, $delete_query, [$id]);
        
        if (!$delete_result) {
            throw new Exception('Failed to delete loading');
        }
        
        // Update affected sales loading status
        $sales_query = "
            UPDATE sales
            SET loading_status = 
                CASE
                    WHEN (
                        SELECT COALESCE(SUM(li.quantity), 0)
                        FROM loading_items li
                        JOIN loadings l ON li.loading_id = l.id
                        WHERE l.customer_id = sales.customer_id
                    ) = 0 THEN 'Pending'
                    WHEN (
                        SELECT COALESCE(SUM(li.quantity), 0)
                        FROM loading_items li
                        JOIN loadings l ON li.loading_id = l.id
                        WHERE l.customer_id = sales.customer_id
                    ) < (
                        SELECT COALESCE(SUM(si.quantity), 0)
                        FROM sale_items si
                        WHERE si.sale_id = sales.id
                    ) THEN 'Partially Loaded'
                    ELSE 'Fully Loaded'
                END
            WHERE customer_id = $1
        ";
        
        $sales_result = pg_query_params($db_conn, $sales_query, [$loading['customer_id']]);
        
        if (!$sales_result) {
            throw new Exception('Failed to update sales loading status');
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Deleted loading', 
            'loadings', 
            $id, 
            "Customer: {$loading['customer_name']}, Date: {$loading['loading_date']}, " .
            "Truck: {$loading['truck_number']}, Items: " . count($items)
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode(['success' => true, 'message' => 'Loading deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>