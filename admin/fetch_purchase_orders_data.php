<?php
include_once '../session.php';
include_once '../function.php';

// Initialize session
Session::init();
Session::requireRole('admin');

$function = new Functions();

// Fetch all purchase orders
$orders = $function->getAllOrders();

// Log the raw orders for debugging
error_log("fetch_purchase_orders_data.php: Raw orders: " . json_encode($orders));

// Prepare data for JSON response
$ordersData = [];
foreach ($orders as $order) {
    $status = strtolower(trim($order['status'])); // Normalize: lowercase and trim spaces
    error_log("fetch_purchase_orders_data.php: Processing order {$order['po_number']}, status: {$status}");
    $ordersData[] = [
        'po_number' => $order['po_number'],
        'item_name' => $order['item_name'],
        'quantity' => $order['quantity'],
        'total_cost' => number_format($order['total_cost'], 2),
        'supplier_name' => $order['supplier_name'],
        'order_date' => date('F j, Y, g:i A', strtotime($order['order_date'])),
        'status' => $status // Use normalized status
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['orders' => $ordersData]);
exit;
?>