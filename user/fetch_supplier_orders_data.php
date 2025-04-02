<?php
include_once '../session.php';
include_once '../function.php';

// Initialize session
Session::init();
Session::requireRole('staff');

$function = new Functions();

// Fetch user information
$user_id = Session::get('user_id');
$user = $user_id ? $function->GetUserInfo($user_id) : null;

// Fetch purchase orders assigned to this supplier
$supplier_id = $user['id'];
$orders = $function->getOrdersBySupplier($supplier_id);

// Log the raw orders for debugging
error_log("fetch_supplier_orders_data.php: Raw orders: " . json_encode($orders));

// Prepare data for JSON response
$ordersData = [];
foreach ($orders as $order) {
    $status = strtolower(trim($order['status'])); // Normalize status
    error_log("fetch_supplier_orders_data.php: Processing order {$order['po_number']}, status: {$status}");
    $ordersData[] = [
        'po_number' => $order['po_number'],
        'item_name' => $order['item_name'],
        'quantity' => $order['quantity'],
        'total_cost' => number_format($order['total_cost'], 2),
        'order_date' => date('F j, Y, g:i A', strtotime($order['order_date'])),
        'status' => $status // Use normalized status
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['orders' => $ordersData]);
exit;
?>