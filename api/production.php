<?php
// Production CRUD API
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
                getProduction();
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
                addProduction();
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
                deleteProduction();
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

function getProduction() {
    global $db_conn;
    
    $start_date = sanitizeInput($_GET['start_date'] ?? '');
    $end_date = sanitizeInput($_GET['end_date'] ?? '');
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build the query
    $where_clauses = [];
    $params = [];
    $param_index = 1;
    
    if (!empty($start_date)) {
        $where_clauses[] = "pd.production_date >= $" . $param_index;
        $params[] = $start_date;
        $param_index++;
    }
    
    if (!empty($end_date)) {
        $where_clauses[] = "pd.production_date <= $" . $param_index;
        $params[] = $end_date;
        $param_index++;
    }
    
    if ($product_id) {
        $where_clauses[] = "pd.product_id = $" . $param_index;
        $params[] = $product_id;
        $param_index++;
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Count total records
    $count_query = "
        SELECT COUNT(*) 
        FROM production pd
        $where_clause
    ";
    
    $count_result = pg_query_params($db_conn, $count_query, $params);
    $total_records = pg_fetch_result($count_result, 0, 0);
    
    // Get production data
    $query = "
        SELECT pd.*, p.name as product_name, c.name as category_name
        FROM production pd
        JOIN products p ON pd.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        $where_clause
        ORDER BY pd.production_date DESC, pd.id DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $production = [];
    while ($row = pg_fetch_assoc($result)) {
        // Format date for display
        $row['formatted_date'] = formatDate($row['production_date']);
        $production[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $production,
        'pagination' => [
            'total' => intval($total_records),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total_records / $per_page)
        ]
    ]);
}

function addProduction() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Get form data
        $production_date = sanitizeInput($_POST['production_date'] ?? '');
        $items = json_decode($_POST['items'] ?? '[]', true);
        
        if (empty($production_date) || empty($items)) {
            throw new Exception('Production date and items are required');
        }
        
        $added_items = [];
        
        foreach ($items as $item) {
            $product_id = intval($item['product_id'] ?? 0);
            $quantity = intval($item['quantity'] ?? 0);
            
            if ($product_id <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product ID or quantity');
            }
            
            // Insert production record
            $query = "
                INSERT INTO production (product_id, quantity, production_date) 
                VALUES ($1, $2, $3) 
                RETURNING id
            ";
            
            $result = pg_query_params($db_conn, $query, [$product_id, $quantity, $production_date]);
            
            if (!$result) {
                throw new Exception('Failed to add production record');
            }
            
            $production_id = pg_fetch_result($result, 0, 0);
            
            // Update product physical stock
            $update_query = "
                UPDATE products 
                SET physical_stock = physical_stock + $1 
                WHERE id = $2
                RETURNING name, physical_stock
            ";
            
            $update_result = pg_query_params($db_conn, $update_query, [$quantity, $product_id]);
            
            if (!$update_result) {
                throw new Exception('Failed to update product stock');
            }
            
            $product = pg_fetch_assoc($update_result);
            
            // Log the action
            logAudit(
                $db_conn, 
                $_SESSION['user_id'], 
                'Added production', 
                'production', 
                $production_id, 
                "Product: {$product['name']}, Quantity: $quantity, Date: $production_date"
            );
            
            $added_items[] = [
                'id' => $production_id,
                'product_id' => $product_id,
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'production_date' => $production_date,
                'formatted_date' => formatDate($production_date)
            ];
        }
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode(['success' => true, 'message' => 'Production added successfully', 'data' => $added_items]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteProduction() {
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
        echo json_encode(['success' => false, 'message' => 'Invalid production ID']);
        return;
    }
    
    // Start a transaction
    pg_query($db_conn, "BEGIN");
    
    try {
        // Get production details for logging and stock update
        $details_query = "
            SELECT pd.*, p.name as product_name 
            FROM production pd
            JOIN products p ON pd.product_id = p.id
            WHERE pd.id = $1
        ";
        
        $details_result = pg_query_params($db_conn, $details_query, [$id]);
        
        if (!$details_result || pg_num_rows($details_result) === 0) {
            throw new Exception('Production record not found');
        }
        
        $production = pg_fetch_assoc($details_result);
        
        // Update product physical stock
        $update_query = "
            UPDATE products 
            SET physical_stock = physical_stock - $1 
            WHERE id = $2 AND physical_stock >= $1
            RETURNING physical_stock
        ";
        
        $update_result = pg_query_params(
            $db_conn, 
            $update_query, 
            [$production['quantity'], $production['product_id']]
        );
        
        if (!$update_result || pg_num_rows($update_result) === 0) {
            throw new Exception('Cannot delete production: insufficient stock');
        }
        
        // Delete the production record
        $query = "DELETE FROM production WHERE id = $1";
        $result = pg_query_params($db_conn, $query, [$id]);
        
        if (!$result) {
            throw new Exception('Failed to delete production record');
        }
        
        // Log the action
        logAudit(
            $db_conn, 
            $_SESSION['user_id'], 
            'Deleted production', 
            'production', 
            $id, 
            "Product: {$production['product_name']}, Quantity: {$production['quantity']}, Date: {$production['production_date']}"
        );
        
        // Commit the transaction
        pg_query($db_conn, "COMMIT");
        
        echo json_encode(['success' => true, 'message' => 'Production record deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction
        pg_query($db_conn, "ROLLBACK");
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>