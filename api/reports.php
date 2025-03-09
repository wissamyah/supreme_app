<?php
// Reports generation API
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

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        header('Content-Type: application/json');
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
            case 'production':
                getProductionReport();
                break;
            case 'sales':
                getSalesReport();
                break;
            case 'loadings':
                getLoadingsReport();
                break;
            case 'product_movement':
                getProductMovementReport();
                break;
            default:
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
    default:
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function getProductionReport() {
    global $db_conn;
    
    $start_date = sanitizeInput($_GET['start_date'] ?? '');
    $end_date = sanitizeInput($_GET['end_date'] ?? '');
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
    $format = sanitizeInput($_GET['format'] ?? 'json');
    
    // Build query conditions
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
    
    if ($category_id) {
        $where_clauses[] = "c.id = $" . $param_index;
        $params[] = $category_id;
        $param_index++;
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Get summary grouped by product and date
    $query = "
        SELECT 
            p.id as product_id,
            p.name as product_name,
            c.name as category_name,
            to_char(pd.production_date, 'DD/MM/YYYY') as formatted_date,
            pd.production_date,
            SUM(pd.quantity) as quantity
        FROM production pd
        JOIN products p ON pd.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        $where_clause
        GROUP BY p.id, p.name, c.name, pd.production_date
        ORDER BY pd.production_date, c.name, p.name
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Process results
    $data = [];
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    // Get summary by product
    $summary_query = "
        SELECT 
            p.id as product_id,
            p.name as product_name,
            c.name as category_name,
            SUM(pd.quantity) as total_quantity
        FROM production pd
        JOIN products p ON pd.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        $where_clause
        GROUP BY p.id, p.name, c.name
        ORDER BY c.name, p.name
    ";
    
    $summary_result = pg_query_params($db_conn, $summary_query, $params);
    
    if (!$summary_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $summary = [];
    while ($row = pg_fetch_assoc($summary_result)) {
        $summary[] = $row;
    }
    
    // Log the report generation
    $log_details = "Production Report";
    if (!empty($start_date)) $log_details .= ", From: $start_date";
    if (!empty($end_date)) $log_details .= ", To: $end_date";
    if ($category_id) $log_details .= ", Category ID: $category_id";
    
    logAudit($db_conn, $_SESSION['user_id'], 'Generated report', null, null, $log_details);
    
    // Return data in requested format
    if ($format === 'csv') {
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="production_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV header
        fputcsv($output, ['Product Name', 'Category', 'Date', 'Quantity']);
        
        // CSV data
        foreach ($data as $row) {
            fputcsv($output, [
                $row['product_name'],
                $row['category_name'],
                $row['formatted_date'],
                $row['quantity']
            ]);
        }
        
        // Summary section
        fputcsv($output, []);
        fputcsv($output, ['Product Name', 'Category', 'Total Quantity']);
        
        foreach ($summary as $row) {
            fputcsv($output, [
                $row['product_name'],
                $row['category_name'],
                $row['total_quantity']
            ]);
        }
        
        fclose($output);
        exit;
        
    } else {
        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => [
                'details' => $data,
                'summary' => $summary,
                'filters' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'category_id' => $category_id
                ]
            ]
        ]);
    }
}

function getSalesReport() {
    global $db_conn;
    
    $start_date = sanitizeInput($_GET['start_date'] ?? '');
    $end_date = sanitizeInput($_GET['end_date'] ?? '');
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
    $loading_status = sanitizeInput($_GET['loading_status'] ?? '');
    $format = sanitizeInput($_GET['format'] ?? 'json');
    
    // Build query conditions
    $where_clauses = [];
    $params = [];
    $param_index = 1;
    
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
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Get sales data
    $query = "
        SELECT 
            s.id as sale_id,
            s.sale_date,
            to_char(s.sale_date, 'DD/MM/YYYY') as formatted_date,
            s.total_amount,
            s.loading_status,
            c.id as customer_id,
            c.name as customer_name,
            c.state as customer_state
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        $where_clause
        ORDER BY s.sale_date DESC, s.id DESC
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Process results
    $sales = [];
    $total_amount = 0;
    
    while ($row = pg_fetch_assoc($result)) {
        $row['formatted_amount'] = formatCurrency($row['total_amount']);
        $total_amount += floatval($row['total_amount']);
        
        // Get sale items
        $items_query = "
            SELECT 
                si.quantity,
                si.rate,
                p.name as product_name,
                c.name as category_name
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE si.sale_id = $1
            ORDER BY c.name, p.name
        ";
        
        $items_result = pg_query_params($db_conn, $items_query, [$row['sale_id']]);
        
        if (!$items_result) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            return;
        }
        
        $items = [];
        while ($item = pg_fetch_assoc($items_result)) {
            $item['subtotal'] = floatval($item['quantity']) * floatval($item['rate']);
            $item['formatted_rate'] = formatCurrency($item['rate']);
            $item['formatted_subtotal'] = formatCurrency($item['subtotal']);
            $items[] = $item;
        }
        
        $row['items'] = $items;
        $sales[] = $row;
    }
    
    // Get summary by customer
    $customer_summary_query = "
        SELECT 
            c.id as customer_id,
            c.name as customer_name,
            c.state as customer_state,
            COUNT(s.id) as sale_count,
            SUM(s.total_amount) as total_amount
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        $where_clause
        GROUP BY c.id, c.name, c.state
        ORDER BY total_amount DESC
    ";
    
    $customer_summary_result = pg_query_params($db_conn, $customer_summary_query, $params);
    
    if (!$customer_summary_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $customer_summary = [];
    while ($row = pg_fetch_assoc($customer_summary_result)) {
        $row['formatted_amount'] = formatCurrency($row['total_amount']);
        $customer_summary[] = $row;
    }
    
    // Get summary by product
    $product_summary_query = "
        SELECT 
            p.id as product_id,
            p.name as product_name,
            c.name as category_name,
            SUM(si.quantity) as total_quantity,
            SUM(si.quantity * si.rate) as total_amount
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN sales s ON si.sale_id = s.id
        $where_clause
        GROUP BY p.id, p.name, c.name
        ORDER BY c.name, p.name
    ";
    
    $product_summary_result = pg_query_params($db_conn, $product_summary_query, $params);
    
    if (!$product_summary_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $product_summary = [];
    while ($row = pg_fetch_assoc($product_summary_result)) {
        $row['formatted_amount'] = formatCurrency($row['total_amount']);
        $product_summary[] = $row;
    }
    
    // Get summary by loading status
    $status_summary_query = "
        SELECT 
            loading_status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
        FROM sales s
        $where_clause
        GROUP BY loading_status
        ORDER BY loading_status
    ";
    
    $status_summary_result = pg_query_params($db_conn, $status_summary_query, $params);
    
    if (!$status_summary_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $status_summary = [];
    while ($row = pg_fetch_assoc($status_summary_result)) {
        $row['formatted_amount'] = formatCurrency($row['total_amount']);
        $status_summary[] = $row;
    }
    
    // Log the report generation
    $log_details = "Sales Report";
    if (!empty($start_date)) $log_details .= ", From: $start_date";
    if (!empty($end_date)) $log_details .= ", To: $end_date";
    if ($customer_id) $log_details .= ", Customer ID: $customer_id";
    if (!empty($loading_status)) $log_details .= ", Status: $loading_status";
    
    logAudit($db_conn, $_SESSION['user_id'], 'Generated report', null, null, $log_details);
    
    // Return data in requested format
    if ($format === 'csv') {
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV header for sales
        fputcsv($output, ['Sale ID', 'Date', 'Customer', 'State', 'Amount', 'Loading Status']);
        
        // CSV data for sales
        foreach ($sales as $sale) {
            fputcsv($output, [
                $sale['sale_id'],
                $sale['formatted_date'],
                $sale['customer_name'],
                $sale['customer_state'],
                $sale['formatted_amount'],
                $sale['loading_status']
            ]);
            
            // Items for this sale
            fputcsv($output, ['', 'Product', 'Category', 'Quantity', 'Rate', 'Subtotal']);
            
            foreach ($sale['items'] as $item) {
                fputcsv($output, [
                    '',
                    $item['product_name'],
                    $item['category_name'],
                    $item['quantity'],
                    $item['formatted_rate'],
                    $item['formatted_subtotal']
                ]);
            }
            
            // Empty line between sales
            fputcsv($output, []);
        }
        
        // Summary by customer
        fputcsv($output, []);
        fputcsv($output, ['Summary by Customer']);
        fputcsv($output, ['Customer', 'State', 'Sale Count', 'Total Amount']);
        
        foreach ($customer_summary as $row) {
            fputcsv($output, [
                $row['customer_name'],
                $row['customer_state'],
                $row['sale_count'],
                $row['formatted_amount']
            ]);
        }
        
        // Summary by product
        fputcsv($output, []);
        fputcsv($output, ['Summary by Product']);
        fputcsv($output, ['Product', 'Category', 'Total Quantity', 'Total Amount']);
        
        foreach ($product_summary as $row) {
            fputcsv($output, [
                $row['product_name'],
                $row['category_name'],
                $row['total_quantity'],
                $row['formatted_amount']
            ]);
        }
        
        // Summary by loading status
        fputcsv($output, []);
        fputcsv($output, ['Summary by Loading Status']);
        fputcsv($output, ['Status', 'Count', 'Total Amount']);
        
        foreach ($status_summary as $row) {
            fputcsv($output, [
                $row['loading_status'],
                $row['count'],
                $row['formatted_amount']
            ]);
        }
        
        // Overall total
        fputcsv($output, []);
        fputcsv($output, ['Total Sales Amount', formatCurrency($total_amount)]);
        
        fclose($output);
        exit;
        
    } else {
        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => [
                'sales' => $sales,
                'total_sales_amount' => $total_amount,
                'formatted_total_amount' => formatCurrency($total_amount),
                'customer_summary' => $customer_summary,
                'product_summary' => $product_summary,
                'status_summary' => $status_summary,
                'filters' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'customer_id' => $customer_id,
                    'loading_status' => $loading_status
                ]
            ]
        ]);
    }
}

function getLoadingsReport() {
    global $db_conn;
    
    $start_date = sanitizeInput($_GET['start_date'] ?? '');
    $end_date = sanitizeInput($_GET['end_date'] ?? '');
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
    $format = sanitizeInput($_GET['format'] ?? 'json');
    
    // Build query conditions
    $where_clauses = [];
    $params = [];
    $param_index = 1;
    
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
    
    if ($customer_id) {
        $where_clauses[] = "l.customer_id = $" . $param_index;
        $params[] = $customer_id;
        $param_index++;
    }
    
    $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Get loadings data
    $query = "
        SELECT 
            l.id as loading_id,
            l.loading_date,
            to_char(l.loading_date, 'DD/MM/YYYY') as formatted_date,
            l.truck_number,
            l.waybill,
            l.driver_name,
            l.driver_phone,
            c.id as customer_id,
            c.name as customer_name,
            c.state as customer_state
        FROM loadings l
        JOIN customers c ON l.customer_id = c.id
        $where_clause
        ORDER BY l.loading_date DESC, l.id DESC
    ";
    
    $result = pg_query_params($db_conn, $query, $params);
    
    if (!$result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Process results
    $loadings = [];
    
    while ($row = pg_fetch_assoc($result)) {
        // Get loading items
        $items_query = "
            SELECT 
                li.quantity,
                p.name as product_name,
                c.name as category_name
            FROM loading_items li
            JOIN products p ON li.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE li.loading_id = $1
            ORDER BY c.name, p.name
        ";
        
        $items_result = pg_query_params($db_conn, $items_query, [$row['loading_id']]);
        
        if (!$items_result) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            return;
        }
        
        $items = [];
        while ($item = pg_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        $row['items'] = $items;
        $loadings[] = $row;
    }
    
    // Get summary by customer
    $customer_summary_query = "
        SELECT 
            c.id as customer_id,
            c.name as customer_name,
            c.state as customer_state,
            COUNT(l.id) as loading_count
        FROM loadings l
        JOIN customers c ON l.customer_id = c.id
        $where_clause
        GROUP BY c.id, c.name, c.state
        ORDER BY loading_count DESC
    ";
    
    $customer_summary_result = pg_query_params($db_conn, $customer_summary_query, $params);
    
    if (!$customer_summary_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $customer_summary = [];
    while ($row = pg_fetch_assoc($customer_summary_result)) {
        $customer_summary[] = $row;
    }
    
    // Get summary by product
    $product_summary_query = "
        SELECT 
            p.id as product_id,
            p.name as product_name,
            c.name as category_name,
            SUM(li.quantity) as total_quantity
        FROM loading_items li
        JOIN products p ON li.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN loadings l ON li.loading_id = l.id
        $where_clause
        GROUP BY p.id, p.name, c.name
        ORDER BY c.name, p.name
    ";
    
    $product_summary_result = pg_query_params($db_conn, $product_summary_query, $params);
    
    if (!$product_summary_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $product_summary = [];
    while ($row = pg_fetch_assoc($product_summary_result)) {
        $product_summary[] = $row;
    }
    
    // Log the report generation
    $log_details = "Loadings Report";
    if (!empty($start_date)) $log_details .= ", From: $start_date";
    if (!empty($end_date)) $log_details .= ", To: $end_date";
    if ($customer_id) $log_details .= ", Customer ID: $customer_id";
    
    logAudit($db_conn, $_SESSION['user_id'], 'Generated report', null, null, $log_details);
    
    // Return data in requested format
    if ($format === 'csv') {
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="loadings_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV header for loadings
        fputcsv($output, ['Loading ID', 'Date', 'Customer', 'State', 'Truck Number', 'Waybill', 'Driver Name', 'Driver Phone']);
        
        // CSV data for loadings
        foreach ($loadings as $loading) {
            fputcsv($output, [
                $loading['loading_id'],
                $loading['formatted_date'],
                $loading['customer_name'],
                $loading['customer_state'],
                $loading['truck_number'],
                $loading['waybill'],
                $loading['driver_name'],
                $loading['driver_phone']
            ]);
            
            // Items for this loading
            fputcsv($output, ['', 'Product', 'Category', 'Quantity']);
            
            foreach ($loading['items'] as $item) {
                fputcsv($output, [
                    '',
                    $item['product_name'],
                    $item['category_name'],
                    $item['quantity']
                ]);
            }
            
            // Empty line between loadings
            fputcsv($output, []);
        }
        
        // Summary by customer
        fputcsv($output, []);
        fputcsv($output, ['Summary by Customer']);
        fputcsv($output, ['Customer', 'State', 'Loading Count']);
        
        foreach ($customer_summary as $row) {
            fputcsv($output, [
                $row['customer_name'],
                $row['customer_state'],
                $row['loading_count']
            ]);
        }
        
        // Summary by product
        fputcsv($output, []);
        fputcsv($output, ['Summary by Product']);
        fputcsv($output, ['Product', 'Category', 'Total Quantity']);
        
        foreach ($product_summary as $row) {
            fputcsv($output, [
                $row['product_name'],
                $row['category_name'],
                $row['total_quantity']
            ]);
        }
        
        fclose($output);
        exit;
        
    } else {
        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => [
                'loadings' => $loadings,
                'customer_summary' => $customer_summary,
                'product_summary' => $product_summary,
                'filters' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'customer_id' => $customer_id
                ]
            ]
        ]);
    }
}

function getProductMovementReport() {
    global $db_conn;
    
    $start_date = sanitizeInput($_GET['start_date'] ?? '');
    $end_date = sanitizeInput($_GET['end_date'] ?? '');
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
    $format = sanitizeInput($_GET['format'] ?? 'json');
    
    // Build product filter
    $product_filter = '';
    $params = [];
    $param_index = 1;
    
    if ($product_id) {
        $product_filter = "WHERE p.id = $" . $param_index;
        $params[] = $product_id;
        $param_index++;
    } elseif ($category_id) {
        $product_filter = "WHERE p.category_id = $" . $param_index;
        $params[] = $category_id;
        $param_index++;
    }
    
    // Date filters will be applied in the UNION queries
    
    // Get all products that match the filter
    $products_query = "
        SELECT p.id, p.name, c.name as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        $product_filter
        ORDER BY c.name, p.name
    ";
    
    $products_result = pg_query_params($db_conn, $products_query, $params);
    
    if (!$products_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    $products = [];
    while ($row = pg_fetch_assoc($products_result)) {
        $products[$row['id']] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'category' => $row['category_name'],
            'movements' => []
        ];
    }
    
    if (empty($products)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => [
                'products' => [],
                'filters' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'product_id' => $product_id,
                    'category_id' => $category_id
                ]
            ]
        ]);
        return;
    }
    
    // Build the product IDs list for the IN clause
    $product_ids = array_keys($products);
    $product_ids_list = implode(',', $product_ids);
    
    // Date filter for movements
    $date_filter = [];
    $date_params = [];
    $date_param_index = 1;
    
    if (!empty($start_date)) {
        $date_filter[] = "date >= $" . $date_param_index;
        $date_params[] = $start_date;
        $date_param_index++;
    }
    
    if (!empty($end_date)) {
        $date_filter[] = "date <= $" . $date_param_index;
        $date_params[] = $end_date;
        $date_param_index++;
    }
    
    $date_where = !empty($date_filter) ? "AND " . implode(" AND ", $date_filter) : "";
    
    // Get all movements for these products
    $movements_query = "
        SELECT 
            product_id,
            date,
            to_char(date, 'DD/MM/YYYY') as formatted_date,
            'Production' as type,
            quantity as movement,
            NULL as customer_name,
            NULL as document_id
        FROM (
            SELECT 
                product_id,
                production_date as date,
                quantity
            FROM production
            WHERE product_id IN ($product_ids_list)
            $date_where
        ) as prod
        
        UNION ALL
        
        SELECT 
            product_id,
            date,
            to_char(date, 'DD/MM/YYYY') as formatted_date,
            'Sale (Booked)' as type,
            0 - quantity as movement,
            customer_name,
            sale_id as document_id
        FROM (
            SELECT 
                si.product_id,
                s.sale_date as date,
                si.quantity,
                c.name as customer_name,
                s.id as sale_id
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN customers c ON s.customer_id = c.id
            WHERE si.product_id IN ($product_ids_list)
            $date_where
        ) as sales
        
        UNION ALL
        
        SELECT 
            product_id,
            date,
            to_char(date, 'DD/MM/YYYY') as formatted_date,
            'Loading' as type,
            0 - quantity as movement,
            customer_name,
            loading_id as document_id
        FROM (
            SELECT 
                li.product_id,
                l.loading_date as date,
                li.quantity,
                c.name as customer_name,
                l.id as loading_id
            FROM loading_items li
            JOIN loadings l ON li.loading_id = l.id
            JOIN customers c ON l.customer_id = c.id
            WHERE li.product_id IN ($product_ids_list)
            $date_where
        ) as loadings
        
        ORDER BY date, type
    ";
    
    $movements_result = pg_query_params($db_conn, $movements_query, $date_params);
    
    if (!$movements_result) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }
    
    // Process movements
    while ($row = pg_fetch_assoc($movements_result)) {
        $products[$row['product_id']]['movements'][] = $row;
    }
    
    // Calculate running balance for each product
    foreach ($products as &$product) {
        $running_balance = 0;
        
        foreach ($product['movements'] as &$movement) {
            $running_balance += floatval($movement['movement']);
            $movement['balance'] = $running_balance;
        }
        
        // Calculate summary
        $production = 0;
        $sales = 0;
        $loadings = 0;
        
        foreach ($product['movements'] as $movement) {
            if ($movement['type'] === 'Production') {
                $production += floatval($movement['movement']);
            } elseif ($movement['type'] === 'Sale (Booked)') {
                $sales += abs(floatval($movement['movement']));
            } elseif ($movement['type'] === 'Loading') {
                $loadings += abs(floatval($movement['movement']));
            }
        }
        
        $product['summary'] = [
            'production' => $production,
            'sales' => $sales,
            'loadings' => $loadings,
            'net_change' => $production - $loadings
        ];
    }
    
    // Log the report generation
    $log_details = "Product Movement Report";
    if (!empty($start_date)) $log_details .= ", From: $start_date";
    if (!empty($end_date)) $log_details .= ", To: $end_date";
    if ($product_id) $log_details .= ", Product ID: $product_id";
    if ($category_id) $log_details .= ", Category ID: $category_id";
    
    logAudit($db_conn, $_SESSION['user_id'], 'Generated report', null, null, $log_details);
    
    // Return data in requested format
    if ($format === 'csv') {
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="product_movement_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        foreach ($products as $product) {
            // Product header
            fputcsv($output, ['Product: ' . $product['name'], 'Category: ' . $product['category']]);
            fputcsv($output, ['Date', 'Type', 'Quantity', 'Balance', 'Customer/Document']);
            
            // Movements
            foreach ($product['movements'] as $movement) {
                $document_info = '';
                if (!is_null($movement['customer_name'])) {
                    $document_info = $movement['customer_name'] . ' (ID: ' . $movement['document_id'] . ')';
                }
                
                fputcsv($output, [
                    $movement['formatted_date'],
                    $movement['type'],
                    $movement['movement'],
                    $movement['balance'],
                    $document_info
                ]);
            }
            
            // Summary
            fputcsv($output, []);
            fputcsv($output, ['Summary']);
            fputcsv($output, ['Total Production', $product['summary']['production']]);
            fputcsv($output, ['Total Sales (Booked)', $product['summary']['sales']]);
            fputcsv($output, ['Total Loadings', $product['summary']['loadings']]);
            fputcsv($output, ['Net Change', $product['summary']['net_change']]);
            
            // Space between products
            fputcsv($output, []);
            fputcsv($output, []);
        }
        
        fclose($output);
        exit;
        
    } else {
        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => [
                'products' => array_values($products),
                'filters' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'product_id' => $product_id,
                    'category_id' => $category_id
                ]
            ]
        ]);
    }
}
?>