<?php
// Shared PHP functions (e.g., security, utilities)

// Security: Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Security: Validate CSRF token
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Security: Sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Format date to DD/MM/YYYY
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y');
}

// Format to Naira currency
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

// Create audit log entry
function logAudit($db_conn, $user_id, $action, $table_name = null, $record_id = null, $details = null) {
    $query = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details) 
              VALUES ($1, $2, $3, $4, $5)";
    return pg_query_params($db_conn, $query, [$user_id, $action, $table_name, $record_id, $details]);
}

// Generate pagination controls
function getPaginationControls($total_items, $items_per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total_items / $items_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 sm:px-6">';
    $html .= '<div class="flex-1 flex justify-between sm:hidden">';
    
    // Previous button (mobile)
    if ($current_page > 1) {
        $prev_url = sprintf($url_pattern, $current_page - 1);
        $html .= '<a href="' . $prev_url . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Previous</a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-700 cursor-not-allowed">Previous</span>';
    }
    
    // Next button (mobile)
    if ($current_page < $total_pages) {
        $next_url = sprintf($url_pattern, $current_page + 1);
        $html .= '<a href="' . $next_url . '" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Next</a>';
    } else {
        $html .= '<span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-700 cursor-not-allowed">Next</span>';
    }
    
    $html .= '</div>';
    
    // Desktop pagination
    $html .= '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
    $html .= '<div><p class="text-sm text-gray-700 dark:text-gray-300">Showing <span class="font-medium">' . min(($current_page - 1) * $items_per_page + 1, $total_items) . '</span> to <span class="font-medium">' . min($current_page * $items_per_page, $total_items) . '</span> of <span class="font-medium">' . $total_items . '</span> results</p></div>';
    
    $html .= '<div><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_url = sprintf($url_pattern, $current_page - 1);
        $html .= '<a href="' . $prev_url . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">';
        $html .= '<span class="sr-only">Previous</span>';
        $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
        $html .= '</a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 cursor-not-allowed">';
        $html .= '<span class="sr-only">Previous</span>';
        $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
        $html .= '</span>';
    }
    
    // Page buttons
    $start_page = max(1, min($current_page - 2, $total_pages - 4));
    $end_page = min($total_pages, max(5, $current_page + 2));
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $html .= '<span aria-current="page" class="z-10 bg-indigo-50 dark:bg-indigo-900 border-indigo-500 dark:border-indigo-500 text-indigo-600 dark:text-indigo-200 relative inline-flex items-center px-4 py-2 border text-sm font-medium">' . $i . '</span>';
        } else {
            $page_url = sprintf($url_pattern, $i);
            $html .= '<a href="' . $page_url . '" class="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_url = sprintf($url_pattern, $current_page + 1);
        $html .= '<a href="' . $next_url . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">';
        $html .= '<span class="sr-only">Next</span>';
        $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>';
        $html .= '</a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 cursor-not-allowed">';
        $html .= '<span class="sr-only">Next</span>';
        $html .= '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>';
        $html .= '</span>';
    }
    
    $html .= '</nav></div></div></div>';
    
    return $html;
}
?>