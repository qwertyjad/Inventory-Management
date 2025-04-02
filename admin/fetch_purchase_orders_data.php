<?php
include_once '../session.php';
include_once '../function.php';

// Initialize session
Session::init();
Session::requireRole('admin');

$function = new Functions();

// Fetch all purchase orders
$orders = $function->getAllOrders();

// Prepare data for JSON response
$ordersData = [];
foreach ($orders as $order) {
    $ordersData[] = [
        'po_number' => $order['po_number'],
        'item_name' => $order['item_name'],
        'quantity' => $order['quantity'],
        'total_cost' => number_format($order['total_cost'], 2),
        'supplier_name' => $order['supplier_name'],
        'order_date' => date('F j, Y, g:i A', strtotime($order['order_date'])),
        'status' => strtolower($order['status']) // Normalize to lowercase for consistency
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['orders' => $ordersData]);
exit;
?>