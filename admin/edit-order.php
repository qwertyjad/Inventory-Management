<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include '../function.php';
$function = new Functions();

// Validate po_number and fetch order
if (!isset($_GET['po_number'])) {
    header('Location: order-new-item.php');
    exit;
}

$po_number = $_GET['po_number'];
$order = $function->getOrder($po_number);

if (!$order) {
    header('Location: order-new-item.php');
    exit;
}

// Fetch staff users for the supplier dropdown
$staffUsers = $function->getStaffUsers();

// Include header after all redirects
include 'header.php';
?>

<div style="padding: 20px; font-family: 'Arial', sans-serif; background-color: #f5f7fa; min-height: 100vh;">
    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;">
        <h5 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 20px 0;">Edit Purchase Order: #<?=$order['po_number'];?></h5>
        <form method="post" action="navigate.php">
            <input type="hidden" name="po_number" value="<?=$order['po_number'];?>">
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="item_name" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Item Name</label>
                    <input type="text" id="item_name" name="item_name" value="<?=$order['item_name'];?>" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="quantity" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="<?=$order['quantity'];?>" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="unit_cost" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Unit Cost (₱)</label>
                    <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" value="<?=$order['unit_cost'];?>" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="supplier_id" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Supplier</label>
                    <select id="supplier_id" name="supplier_id" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none; background-color: white;">
                        <option value="">Select Supplier</option>
                        <?php foreach ($staffUsers as $user): ?>
                            <option value="<?=$user['id'];?>" <?=$order['supplier_id'] == $user['id'] ? 'selected' : '';?>><?=$user['full_name'];?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <a href="order-new-item.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #718096;">Cancel</a>
                <button type="submit" name="btn-update-order" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white; cursor: pointer;">Update Order</button>
            </div>
        </form>
    </div>
</div>