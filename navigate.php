<?php
include_once 'session.php';
include_once 'function.php';

// Initialize session
Session::init();

// Check if user has the 'user' role
Session::requireRole('user');

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
    Session::set("msg", "<div class='alert alert-danger toast-message error'>CSRF token validation failed!</div>");
    header('Location: request-items.php');
    exit;
}

$function = new Functions();
$guest_user_id = Session::get('guest_user_id');

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'request_item') {
        $item_id = (int)$_POST['item_id'];
        $item_name = trim($_POST['item_name']);
        $quantity = (int)$_POST['quantity'];

        // Validate the requested quantity against the available quantity
        $item = $function->getItem($item_id);
        if (!$item || $quantity <= 0) {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Invalid item or quantity!</div>");
            header('Location: request-items.php');
            exit;
        }

        if ($quantity > $item['quantity']) {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Requested quantity exceeds available quantity ({$item['quantity']})!</div>");
            header('Location: request-items.php');
            exit;
        }

        // Add the item request
        if ($function->addItemRequest($guest_user_id, $item_id, $item_name, $quantity)) {
            Session::set("msg", "<div class='alert alert-success toast-message success'>Request submitted successfully!</div>");
            header('Location: user-dashboard.php');
            exit;
        } else {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Failed to submit request!</div>");
            header('Location: request-items.php');
            exit;
        }
    }

    if ($action === 'update_request') {
        $request_id = (int)$_POST['request_id'];
        $item_name = trim($_POST['item_name']);
        $quantity = (int)$_POST['quantity'];
    
        error_log("navigate.php: Attempting to update request_id=$request_id with item_name=$item_name, quantity=$quantity");
    
        if ($quantity <= 0) {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Quantity must be greater than 0!</div>");
            header('Location: user-dashboard.php');
            exit;
        }
    
        $request = $function->getItemRequest($request_id);
        if (!$request || $request['status'] !== 'pending') {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Request not found or not in pending status!</div>");
            header('Location: user-dashboard.php');
            exit;
        }
    
        $item = $function->getItem($request['item_id']);
        if (!$item || $quantity > $item['quantity']) {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Requested quantity ($quantity) exceeds available quantity ({$item['quantity']})!</div>");
            header('Location: user-dashboard.php');
            exit;
        }
    
        if ($function->updateItemRequest($request_id, $item_name, $quantity)) {
            Session::set("msg", "<div class='alert alert-success toast-message success'>Request updated successfully!</div>");
        } else {
            $error = "Failed to update request! Check logs for details.";
            Session::set("msg", "<div class='alert alert-danger toast-message error'>$error</div>");
        }
        header('Location: user-dashboard.php');
        exit;
    }

    if ($action === 'delete_request') {
        $request_id = (int)$_POST['request_id'];

        if ($function->deleteItemRequest($request_id)) {
            Session::set("msg", "<div class='alert alert-success toast-message success'>Request deleted successfully!</div>");
        } else {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Failed to delete request!</div>");
        }
        header('Location: user-dashboard.php');
        exit;
    }

    if ($action === 'cancel_request') {
        $request_id = (int)$_POST['request_id'];

        if ($function->cancelItemRequest($request_id)) {
            Session::set("msg", "<div class='alert alert-success toast-message success'>Request canceled successfully!</div>");
        } else {
            Session::set("msg", "<div class='alert alert-danger toast-message error'>Failed to cancel request!</div>");
        }
        header('Location: user-dashboard.php');
        exit;
    }
}

// If no valid action is specified, redirect back
header('Location: request-items.php');
exit;
?>