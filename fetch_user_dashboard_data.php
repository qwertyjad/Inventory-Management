<?php
include_once 'session.php';
include_once 'function.php';

// Initialize session
Session::init();
Session::requireRole('user');

$function = new Functions();
$guest_user_id = Session::get('guest_user_id');

// Fetch user's requests
$requests = $function->getItemRequestsByUser($guest_user_id);

// Prepare data for JSON response
$requestsData = [];
foreach ($requests as $request) {
    $item = $function->getItem($request['item_id']);
    $availableQuantity = $item ? $item['quantity'] : 0;
    $requestsData[] = [
        'id' => $request['id'],
        'item_name' => htmlspecialchars($request['item_name']),
        'quantity' => $request['quantity'],
        'request_date' => date('F j, Y, g:i A', strtotime($request['request_date'])),
        'status' => ucfirst($request['status']), // Ensure consistency with UI
        'available_quantity' => $availableQuantity
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['requests' => $requestsData]);
exit;
?>