<?php
include_once '../session.php';
Session::init();
include_once '../function.php';
$function = new Functions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Item
    if (isset($_POST['btn-add-item'])) {
        $name = $_POST['name'];
        $quantity = (int)$_POST['quantity'];
        $unit = $_POST['unit'];
        $min_stock_level = (int)$_POST['min_stock_level'];
        $location = $_POST['location'] ?: null;
        $supplier = $_POST['supplier'] ?: null;
        $cost = (float)$_POST['cost'];
        $description = $_POST['description'] ?: null;
        $total_cost = $quantity * $cost; // Calculate total_cost

        if ($function->addItem($name, $unit, $quantity, $min_stock_level, $cost, $total_cost, $location, $supplier, $description)) {
            Session::set("msg", "<div class='toast-message success'>Item added successfully!</div>");
        } else {
            Session::set("msg", "<div class='toast-message error'>Failed to add item!</div>");
        }
        header('Location: items.php');
        exit;
    }

    // Handle Update Item
    if (isset($_POST['btn-update-item'])) {
        $id = (int)$_POST['id'];
        $name = $_POST['name'];
        $quantity = (int)$_POST['quantity'];
        $unit = $_POST['unit'];
        $min_stock_level = (int)$_POST['min_stock_level'];
        $location = $_POST['location'] ?: null;
        $supplier = $_POST['supplier'] ?: null;
        $cost = (float)$_POST['cost'];
        $description = $_POST['description'] ?: null;
        $total_cost = $quantity * $cost; // Calculate total_cost

        if ($function->updateItem($id, $name, $unit, $quantity, $min_stock_level, $cost, $total_cost, $location, $supplier, $description)) {
            Session::set("msg", "<div class='toast-message success'>Item updated successfully!</div>");
        } else {
            Session::set("msg", "<div class='toast-message error'>Failed to update item!</div>");
        }
        header('Location: items.php');
        exit;
    }

    // Handle Delete Item
    if (isset($_POST['btn-delete-item'])) {
        $id = (int)$_POST['id'];
        if ($function->deleteItem($id)) {
            Session::set("msg", "<div class='toast-message success'>Item deleted successfully!</div>");
        } else {
            Session::set("msg", "<div class='toast-message error'>Failed to delete item!</div>");
        }
        header('Location: items.php');
        exit;
    }

    // Handle Add Order
    if (isset($_POST['btn-add-order'])) {
        $item_name = $_POST['item_name'];
        $quantity = (int)$_POST['quantity'];
        $unit_cost = (float)$_POST['unit_cost'];
        $supplier_id = (int)$_POST['supplier_id'];
        $total_cost = $quantity * $unit_cost; // Calculate total_cost

        if ($function->addOrder($item_name, $quantity, $unit_cost, $total_cost, $supplier_id)) {
            Session::set("msg", "<div class='toast-message success'>Order added successfully!</div>");
        } else {
            Session::set("msg", "<div class='toast-message error'>Failed to add order!</div>");
        }
        header('Location: order-new-item.php');
        exit;
    }

    // Handle Update Order
    if (isset($_POST['btn-update-order'])) {
        $po_number = $_POST['po_number'];
        $item_name = $_POST['item_name'];
        $quantity = (int)$_POST['quantity'];
        $unit_cost = (float)$_POST['unit_cost'];
        $supplier_id = (int)$_POST['supplier_id'];
        $total_cost = $quantity * $unit_cost; // Calculate total_cost

        if ($function->updateOrder($po_number, $item_name, $quantity, $unit_cost, $total_cost, $supplier_id)) {
            Session::set("msg", "<div class='toast-message success'>Order updated successfully!</div>");
        } else {
            Session::set("msg", "<div class='toast-message error'>Failed to update order!</div>");
        }
        header('Location: order-new-item.php');
        exit;
    }

    // Handle Cancel Order
    if (isset($_POST['btn-cancel-order'])) {
        $po_number = $_POST['po_number'];
        if ($function->cancelOrder($po_number)) {
            Session::set("msg", "<div class='toast-message success'>Order cancelled successfully!</div>");
        } else {
            Session::set("msg", "<div class='toast-message error'>Failed to cancel order!</div>");
        }
        header('Location: order-new-item.php');
        exit;
    }
}

exit;
?>