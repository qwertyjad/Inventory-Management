<?php
header('Content-Type: application/json');
include '../../session.php';
include '../../function.php';

// Fetch items
$items = $function->getAllItems();
echo json_encode($items);
exit;