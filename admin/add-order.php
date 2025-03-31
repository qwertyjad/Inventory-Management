<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include 'header.php';
$function = new Functions();

// Fetch staff users for the supplier dropdown
$staffUsers = $function->getStaffUsers();

// Fetch item details if item_id is provided
$itemName = '';
$unitCost = '';
$supplierId = '';
if (isset($_GET['item_id']) && !empty($_GET['item_id'])) {
    $itemId = (int)$_GET['item_id'];
    $item = $function->getItemById($itemId);
    if ($item) {
        $itemName = htmlspecialchars($item['name']);
        $unitCost = htmlspecialchars($item['cost']);
        // Fetch the supplier name from the items table
        $supplierName = htmlspecialchars($item['supplier']);
        // Find the supplier_id by matching the supplier name with full_name in the users table
        foreach ($staffUsers as $user) {
            if ($user['full_name'] === $supplierName) {
                $supplierId = $user['id'];
                break;
            }
        }
        if (!$supplierId) {
            error_log("Supplier not found for item: $itemName, supplier name: $supplierName");
        }
    } else {
        // If item not found, redirect with an error message
        Session::set("msg", "<div class='toast-message error'>Item not found!</div>");
        header('Location: index.php');
        exit;
    }
}
?>

<div style="padding: 20px; font-family: 'Arial', sans-serif; background-color: #f5f7fa; min-height: 100vh;">
    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;">
        <h5 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 20px 0;">Add New Purchase Order</h5>
        <form method="post" action="navigate.php" onsubmit="return validateForm()">
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="item_name" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Item Name</label>
                    <input type="text" id="item_name" name="item_name" value="<?= $itemName; ?>" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="quantity" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="unit_cost" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Unit Cost (₱)</label>
                    <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" value="<?= $unitCost; ?>" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="supplier_id" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Supplier</label>
                    <select id="supplier_id" name="supplier_id" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none; background-color: white;">
                        <option value="">Select Supplier</option>
                        <?php foreach ($staffUsers as $user): ?>
                            <option value="<?= $user['id']; ?>" <?= $user['id'] == $supplierId ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <a href="order-new-item.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #718096;">Cancel</a>
                <button type="submit" name="btn-add-order" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white; cursor: pointer;">Add Order</button>
            </div>
        </form>
    </div>
</div>

<script>
function validateForm() {
    const itemName = document.getElementById('item_name').value.trim();
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
    const supplierId = document.getElementById('supplier_id').value;

    if (!itemName) {
        alert('Item Name cannot be empty.');
        return false;
    }

    if (quantity <= 0) {
        alert('Quantity must be greater than 0.');
        return false;
    }

    if (unitCost <= 0) {
        alert('Unit Cost must be greater than 0.');
        return false;
    }

    if (!supplierId) {
        alert('Please select a supplier.');
        return false;
    }

    return true;
}
</script>