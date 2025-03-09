<?php
// Inventory CRUD API
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
            case 'categories':
                getCategories();
                break;
            case 'products':
                getProducts();
                break;
            case 'product':
                getProduct();
                break;
            case 'stock':
                getStock();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'POST':
        switch ($action) {
            case 'category':
                addCategory();
                break;
            case 'product':
                addProduct();
                break;
            case 'production':
                addProduction();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'PUT':
        switch ($action) {
            case 'category':
                updateCategory();
                break;
            case 'product':
                updateProduct();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    case 'DELETE':
        switch ($action) {
            case 'category':
                deleteCategory();
                break;
            case 'product':
                deleteProduct();
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

// Category functions
function getCategories() {
    global $db_conn;
    
    $query = "SELECT * FROM categories ORDER BY name";
    $result = pg_query($db_conn, $query);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $categories = [];
    while ($row = pg_fetch_assoc($result)) {
        $categories[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $categories]);
}

function addCategory() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        return;
    }
    
    // Check if category already exists
    $check_query = "SELECT COUNT(*) FROM categories WHERE name = $1";
    $check_result = pg_query_params($db_conn, $check_query, [$name]);
    $count = pg_fetch_result($check_result, 0, 0);
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category name already exists']);
        return;
    }
    
    $query = "INSERT INTO categories (name) VALUES ($1) RETURNING id, name, created_at";
    $result = pg_query_params($db_conn, $query, [$name]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $category = pg_fetch_assoc($result);
    
    // Log the action
    logAudit($db_conn, $_SESSION['user_id'], 'Added category', 'categories', $category['id'], "Name: $name");
    
    echo json_encode(['success' => true, 'message' => 'Category added successfully', 'data' => $category]);
}

function updateCategory() {
    global $db_conn;
    
    if (!hasPermission('edit')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $name = sanitizeInput($input['name'] ?? '');
    
    if ($id <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Check if category already exists with this name (excluding current category)
    $check_query = "SELECT COUNT(*) FROM categories WHERE name = $1 AND id != $2";
    $check_result = pg_query_params($db_conn, $check_query, [$name, $id]);
    $count = pg_fetch_result($check_result, 0, 0);
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category name already exists']);
        return;
    }
    
    $query = "UPDATE categories SET name = $1 WHERE id = $2 RETURNING id, name, created_at";
    $result = pg_query_params($db_conn, $query, [$name, $id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    if (pg_affected_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        return;
    }
    
    $category = pg_fetch_assoc($result);
    
    // Log the action
    logAudit($db_conn, $_SESSION['user_id'], 'Updated category', 'categories', $id, "Name: $name");
    
    echo json_encode(['success' => true, 'message' => 'Category updated successfully', 'data' => $category]);
}

function deleteCategory() {
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
        echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
        return;
    }
    
    // Check if category is in use
    $check_query = "SELECT COUNT(*) FROM products WHERE category_id = $1";
    $check_result = pg_query_params($db_conn, $check_query, [$id]);
    $count = pg_fetch_result($check_result, 0, 0);
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete category that is in use']);
        return;
    }
    
    // Get category name for logging
    $name_query = "SELECT name FROM categories WHERE id = $1";
    $name_result = pg_query_params($db_conn, $name_query, [$id]);
    $category_name = pg_fetch_result($name_result, 0, 0);
    
    $query = "DELETE FROM categories WHERE id = $1";
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    if (pg_affected_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        return;
    }
    
    // Log the action
    logAudit($db_conn, $_SESSION['user_id'], 'Deleted category', 'categories', $id, "Name: $category_name");
    
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
}

// Product functions
function getProducts() {
    global $db_conn;
    
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build the query
    $where_clauses = [];
    $params = [];
    $param_index = 1;
    
    if ($category_id) {
        $where_clauses[] = "p.category_id = $" . $param_index;
        $params[] = $category_id;
        $param_index++;
    }
    
    if (!empty($search)) {
        $where_clauses[] = "p.name ILIKE $" . $param_index;
        $params[] = "%$search%";
        $param_index++;
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Count total records
    $count_query = "
        SELECT COUNT(*) 
        FROM products p
        $where_clause
    ";
    
    $count_result = pg_query_params($db_conn, $count_query, $params);
    $total_records = pg_fetch_result($count_result, 0, 0);
    
    // Get products
    $query = "
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
        ORDER BY p.name
        LIMIT $per_page OFFSET $offset
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $products = [];
    while ($row = pg_fetch_assoc($result)) {
        // Calculate available stock
        $row['available_stock'] = $row['physical_stock'] - $row['booked_stock'];
        $products[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $products,
        'pagination' => [
            'total' => intval($total_records),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total_records / $per_page)
        ]
    ]);
}

function getProduct() {
    global $db_conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        return;
    }
    
    $query = "
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = $1
    ";
    
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    $product = pg_fetch_assoc($result);
    
    // Calculate available stock
    $product['available_stock'] = $product['physical_stock'] - $product['booked_stock'];
    
    echo json_encode(['success' => true, 'data' => $product]);
}

function getStock() {
    global $db_conn;
    
    $query = "
        SELECT p.id, p.name, c.name as category, p.physical_stock, p.booked_stock, 
               (p.physical_stock - p.booked_stock) as available_stock
        FROM products p
        JOIN categories c ON p.category_id = c.id
        ORDER BY c.name, p.name
    ";
    
    $result = pg_query($db_conn, $query);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $stock = [];
    while ($row = pg_fetch_assoc($result)) {
        $stock[] = $row;
    }
    
    // Get total stock counts
    $totals_query = "
        SELECT 
            SUM(physical_stock) as total_physical,
            SUM(booked_stock) as total_booked,
            SUM(physical_stock - booked_stock) as total_available
        FROM products
    ";
    
    $totals_result = pg_query($db_conn, $totals_query);
    $totals = pg_fetch_assoc($totals_result);
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'stock' => $stock,
            'totals' => $totals
        ]
    ]);
}

function addProduct() {
    global $db_conn;
    
    if (!hasPermission('add')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $physical_stock = intval($_POST['physical_stock'] ?? 0);
    
    if (empty($name) || $category_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name and category are required']);
        return;
    }
    
    // Check if product name already exists
    $check_query = "SELECT COUNT(*) FROM products WHERE name = $1";
    $check_result = pg_query_params($db_conn, $check_query, [$name]);
    $count = pg_fetch_result($check_result, 0, 0);
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product name already exists']);
        return;
    }
    
    // Check if category exists
    $cat_check_query = "SELECT COUNT(*) FROM categories WHERE id = $1";
    $cat_check_result = pg_query_params($db_conn, $cat_check_query, [$category_id]);
    $cat_count = pg_fetch_result($cat_check_result, 0, 0);
    
    if ($cat_count === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
        return;
    }
    
    $query = "
        INSERT INTO products (name, category_id, physical_stock, booked_stock) 
        VALUES ($1, $2, $3, 0) 
        RETURNING id, name, category_id, physical_stock, booked_stock, created_at
    ";
    
    $result = pg_query_params($db_conn, $query, [$name, $category_id, $physical_stock]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $product = pg_fetch_assoc($result);
    
    // Log the action
    logAudit(
        $db_conn, 
        $_SESSION['user_id'], 
        'Added product', 
        'products', 
        $product['id'], 
        "Name: $name, Category: $category_id, Physical Stock: $physical_stock"
    );
    
    echo json_encode(['success' => true, 'message' => 'Product added successfully', 'data' => $product]);
}

function updateProduct() {
    global $db_conn;
    
    if (!hasPermission('edit')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $name = sanitizeInput($input['name'] ?? '');
    $category_id = intval($input['category_id'] ?? 0);
    $physical_stock = intval($input['physical_stock'] ?? 0);
    
    if ($id <= 0 || empty($name) || $category_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Check if product name already exists (excluding current product)
    $check_query = "SELECT COUNT(*) FROM products WHERE name = $1 AND id != $2";
    $check_result = pg_query_params($db_conn, $check_query, [$name, $id]);
    $count = pg_fetch_result($check_result, 0, 0);
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product name already exists']);
        return;
    }
    
    // Check if category exists
    $cat_check_query = "SELECT COUNT(*) FROM categories WHERE id = $1";
    $cat_check_result = pg_query_params($db_conn, $cat_check_query, [$category_id]);
    $cat_count = pg_fetch_result($cat_check_result, 0, 0);
    
    if ($cat_count === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
        return;
    }
    
    // Get current product data for logging
    $current_query = "SELECT name, category_id, physical_stock FROM products WHERE id = $1";
    $current_result = pg_query_params($db_conn, $current_query, [$id]);
    $current = pg_fetch_assoc($current_result);
    
    $query = "
        UPDATE products 
        SET name = $1, category_id = $2, physical_stock = $3 
        WHERE id = $4 
        RETURNING id, name, category_id, physical_stock, booked_stock, created_at
    ";
    
    $result = pg_query_params($db_conn, $query, [$name, $category_id, $physical_stock, $id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    if (pg_affected_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    $product = pg_fetch_assoc($result);
    
    // Log the action
    $change_log = "Changed:";
    if ($current['name'] !== $name) $change_log .= " Name from '{$current['name']}' to '$name',";
    if ($current['category_id'] !== $category_id) $change_log .= " Category ID from {$current['category_id']} to $category_id,";
    if ($current['physical_stock'] !== $physical_stock) $change_log .= " Physical Stock from {$current['physical_stock']} to $physical_stock,";
    $change_log = rtrim($change_log, ',');
    
    logAudit($db_conn, $_SESSION['user_id'], 'Updated product', 'products', $id, $change_log);
    
    echo json_encode(['success' => true, 'message' => 'Product updated successfully', 'data' => $product]);
}

function deleteProduct() {
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
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        return;
    }
    
    // Check if product is in use in sales or loadings
    $check_query = "
        SELECT 
            (SELECT COUNT(*) FROM sale_items WHERE product_id = $1) +
            (SELECT COUNT(*) FROM loading_items WHERE product_id = $1) +
            (SELECT COUNT(*) FROM production WHERE product_id = $1) as total_usage
    ";
    
    $check_result = pg_query_params($db_conn, $check_query, [$id]);
    $total_usage = pg_fetch_result($check_result, 0, 0);
    
    if ($total_usage > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete product that is in use']);
        return;
    }
    
    // Get product name for logging
    $name_query = "SELECT name FROM products WHERE id = $1";
    $name_result = pg_query_params($db_conn, $name_query, [$id]);
    $product_name = pg_fetch_result($name_result, 0, 0);
    
    $query = "DELETE FROM products WHERE id = $1";
    $result = pg_query_params($db_conn, $query, [$id]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    if (pg_affected_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    // Log the action
    logAudit($db_conn, $_SESSION['user_id'], 'Deleted product', 'products', $id, "Name: $product_name");
    
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
}
?>