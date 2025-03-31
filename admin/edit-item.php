<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include '../function.php';
$function = new Functions();

// Validate item_id and fetch item (before any output)
if (!isset($_GET['item_id'])) {
    header('Location: items.php');
    exit;
}

$item_id = (int)$_GET['item_id'];
$item = $function->getItem($item_id);

if (!$item) {
    header('Location: items.php');
    exit;
}

// Fetch unique items from purchase_orders (only delivered)
$orderedItems = $function->getUniqueOrderedItems();

// Prepare item data for JavaScript (to use in auto-fill)
$itemsData = [];
foreach ($orderedItems as $orderedItem) {
    $supplier = $function->GetUserInfo($orderedItem['supplier_id']);
    $totalInItems = $function->getTotalQuantityInItems($orderedItem['item_name']);
    $remainingQuantity = $orderedItem['total_ordered_quantity'] - $totalInItems;

    $itemsData[$orderedItem['item_name']] = [
        'unit_cost' => (float)$orderedItem['unit_cost'],
        'supplier' => $supplier ? $supplier['full_name'] : 'Unknown',
        'total_quantity' => (int)$orderedItem['total_ordered_quantity'],
        'remaining_quantity' => max(0, (int)$remainingQuantity) // Ensure non-negative
    ];
}
// Debug: Log the itemsData array
error_log("itemsData: " . json_encode($itemsData));

// Get the remaining quantity for the current item
$remainingQuantity = isset($itemsData[$item['name']]) ? $itemsData[$item['name']]['remaining_quantity'] : 0;
$totalQuantity = isset($itemsData[$item['name']]) ? $itemsData[$item['name']]['total_quantity'] : 0;
$unitCostFromPO = isset($itemsData[$item['name']]) ? $itemsData[$item['name']]['unit_cost'] : $item['cost'];
$supplierFromPO = isset($itemsData[$item['name']]) ? $itemsData[$item['name']]['supplier'] : $item['supplier'];

include 'header.php';
?>

<div style="padding: 20px; font-family: 'Arial', sans-serif; background-color: #f5f7fa; min-height: 100vh;">
    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;">
        <h5 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 20px 0;">Edit Item: <?=$item['name'];?> (<?=$item['unit'];?>)</h5>
        <form method="post" action="navigate.php" onsubmit="return validateForm()">
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="name" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Item Name</label>
                    <input type="text" id="name" name="name" value="<?=$item['name'];?>" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="unit" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Unit</label>
                    <select id="unit" name="unit" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none; background-color: white;">
                        <option value="sacks" <?=$item['unit'] === 'sacks' ? 'selected' : '';?>>Sacks</option>
                        <option value="pieces" <?=$item['unit'] === 'pieces' ? 'selected' : '';?>>Pieces</option>
                        <option value="kg" <?=$item['unit'] === 'kg' ? 'selected' : '';?>>Kilograms (kg)</option>
                        <option value="liters" <?=$item['unit'] === 'liters' ? 'selected' : '';?>>Liters</option>
                        <option value="meters" <?=$item['unit'] === 'meters' ? 'selected' : '';?>>Meters</option>
                        <option value="boxes" <?=$item['unit'] === 'boxes' ? 'selected' : '';?>>Boxes</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="quantity" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">
                        Quantity 
                        <?php if ($totalQuantity > 0): ?>
                            <strong><span style="font-size: 12px; color:rgb(255, 0, 0);"> ( Remaining: <?=$remainingQuantity;?> )</span></strong>
                        <?php else: ?>
                            <span style="font-size: 12px; color: #718096;">(No delivered orders found)</span>
                        <?php endif; ?>
                    </label>
                    <input type="number" id="quantity" name="quantity" min="0" value="<?=$item['quantity'];?>" required oninput="calculateTotalCost()" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                    <input type="hidden" id="maxQuantity" value="<?= $remainingQuantity; ?>">
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="min_stock_level" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Minimum Stock Level</label>
                    <input type="number" id="min_stock_level" name="min_stock_level" min="0" value="<?=$item['min_stock_level'];?>" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="cost" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Cost per Item (₱)</label>
                    <input type="number" id="cost" name="cost" step="0.01" min="0" value="<?= $unitCostFromPO; ?>" required oninput="calculateTotalCost()" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="total_cost" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Total Cost (₱)</label>
                    <input type="text" id="total_cost" name="total_cost" readonly value="<?= number_format($item['quantity'] * $unitCostFromPO, 2); ?>" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; background-color: #f7fafc;">
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <label for="location" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Location</label>
                    <input type="text" id="location" name="location" value="<?=$item['location'];?>" placeholder="e.g., Warehouse A" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px;  background-color: #f7fafc;" >
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <label for="supplier" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Supplier</label>
                    <input type="text" id="supplier" name="supplier" value="<?= $supplierFromPO; ?>" placeholder="e.g., ABC Supplies" readonly style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                </div>
            </div>
            <div style="margin-top: 20px;">
                <label for="description" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Item details..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;"><?=$item['description'];?></textarea>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <a href="items.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color:rgb(15, 15, 16); font-weight: bolder; border: 1px solid rgb(2, 2, 2);">Cancel</a>
                <button type="submit" name="btn-update-item" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white; cursor: pointer; font-weight: bold;">Update Item</button>
                <input type="hidden" name="item_id" value="<?= $item_id; ?>">
            </div>
        </form>
    </div>
</div>

<script>
function calculateTotalCost() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const cost = parseFloat(document.getElementById('cost').value) || 0;
    const totalCost = (quantity * cost).toFixed(2);
    document.getElementById('total_cost').value = totalCost;
}

function validateForm() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const maxQuantity = parseInt(document.getElementById('maxQuantity').value) || 0;
    const cost = parseFloat(document.getElementById('cost').value) || 0;

    if (quantity < 0) {
        alert('Quantity cannot be negative.');
        return false;
    }

    // Allow quantity to exceed remaining quantity during edit, as this might be intentional
    // But warn the user if the quantity exceeds the remaining quantity
    if (maxQuantity > 0 && quantity > maxQuantity) {
        if (!confirm(`The quantity (${quantity}) exceeds the remaining delivered quantity (${maxQuantity}). Are you sure you want to proceed?`)) {
            return false;
        }
    }

    if (cost <= 0) {
        alert('Cost per item must be greater than 0.');
        return false;
    }

    return true;
}
</script>