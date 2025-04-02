<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include '../function.php';

$function = new Functions();

// Fetch data for dashboard widgets
$items = $function->getAllItems();
$totalItems = count($items);
$inventoryValue = array_sum(array_column($items, 'total_cost'));
$lowStockItems = array_filter($items, function($item) {
    return $item['quantity'] <= $item['min_stock_level'];
});
$lowStockCount = count($lowStockItems);
$stockHealth = $totalItems > 0 ? (1 - $lowStockCount / $totalItems) * 100 : 100;

// Calculate total unique units
$uniqueUnits = array_unique(array_column($items, 'unit'));
$totalUnits = count($uniqueUnits);

// Fetch approved requests
$approvedRequests = $function->getApprovedItemRequests(10);

// Fetch pending requests
$pendingRequests = $function->getPendingItemRequests();
$notifications = [];
foreach ($pendingRequests as $request) {
    $guestName = $request['guest_name'] ?? 'Unknown Guest';
    $itemName = $request['item_name'] ?? 'Unknown Item'; // From items table
    $notifications[] = [
        'message' => "<b>{$guestName}</b> : {$itemName} (Qty: {$request['quantity']})",
        'type' => 'pending',
        'timestamp' => date('F j, Y, g:i A', strtotime($request['request_date']))
    ];
}

// Prepare low stock items data
$lowStockItemsData = [];
foreach ($lowStockItems as $item) {
    $lowStockItemsData[] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'min_stock_level' => $item['min_stock_level']
    ];
}

// Prepare approved requests data
$approvedRequestsData = [];
foreach ($approvedRequests as $request) {
    $guestName = $request['guest_name'] ?? 'Unknown Guest';
    $itemName = $request['item_name'] ?? 'Unknown Item'; // From items table
    $approvedRequestsData[] = [
        'guest_name' => htmlspecialchars($guestName),
        'item_name' => htmlspecialchars($itemName), // Add item name
        'quantity' => $request['quantity'],
        'request_date' => date('F j, Y, g:i A', strtotime($request['request_date']))
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'totalItems' => $totalItems,
    'inventoryValue' => number_format($inventoryValue, 2),
    'lowStockCount' => $lowStockCount,
    'stockHealth' => (int)$stockHealth,
    'totalUnits' => $totalUnits,
    'lowStockItems' => $lowStockItemsData,
    'approvedRequests' => $approvedRequestsData,
    'notifications' => $notifications
]);
exit;
?>