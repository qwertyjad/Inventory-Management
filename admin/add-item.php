<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include 'header.php';
$function = new Functions();

// Fetch unique items from purchase_orders (only delivered)
$orderedItems = $function->getUniqueOrderedItems();

// Prepare item data for JavaScript (to use in auto-fill)
$itemsData = [];
foreach ($orderedItems as $item) {
    $supplier = $function->GetUserInfo($item['supplier_id']);
    $totalInItems = $function->getTotalQuantityInItems($item['item_name']);
    $remainingQuantity = $item['total_ordered_quantity'] - $totalInItems;

    $itemsData[$item['item_name']] = [
        'unit_cost' => (float)$item['unit_cost'],
        'supplier' => $supplier ? $supplier['full_name'] : 'Unknown',
        'total_quantity' => (int)$item['total_ordered_quantity'],
        'remaining_quantity' => max(0, (int)$remainingQuantity) // Ensure non-negative
    ];
}
// Debug: Log the itemsData array
error_log("itemsData: " . json_encode($itemsData));
?>

<div style="padding: 20px; font-family: 'Arial', sans-serif; background-color: #f5f7fa; min-height: 100vh;">
    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;">
        <h5 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 20px 0;">Add New Inventory Item</h5>
        <?php if (empty($orderedItems)): ?>
            <div style="color: #e53e3e; margin-bottom: 20px;">
                No delivered items available. Please ensure purchase orders are marked as delivered.
            </div>
            <a href="order-new-item.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white;">Add Order</a>
        <?php else: ?>
            <form method="post" action="navigate.php" onsubmit="return validateQuantity()">
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 1; min-width: 300px;">
                        <label for="name" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">
                            Item Name
                            
                        </label>
                        <select id="name" name="name" required onchange="updateItemDetails()" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none; background-color: white;">
                            <option value="">Select Item</option>
                            <?php foreach ($orderedItems as $item): ?>
                                <?php
                                $totalInItems = $function->getTotalQuantityInItems($item['item_name']);
                                $remainingQuantity = $item['total_ordered_quantity'] - $totalInItems;
                                if ($remainingQuantity <= 0) continue; // Skip items with no remaining quantity
                                ?>
                                <option value="<?= htmlspecialchars($item['item_name']); ?>">
                                    <?= htmlspecialchars($item['item_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <label for="unit" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Unit</label>
                        <select id="unit" name="unit" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none; background-color: white;">
                            <option value="sacks">Sacks</option>
                            <option value="pieces">Pieces</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="liters">Liters</option>
                            <option value="meters">Meters</option>
                            <option value="boxes">Boxes</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                    <div style="flex: 1; min-width: 300px;">
                        <label for="quantity" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Quantity
                        <strong><span id="item-quantity-info" style="font-size: 12px; color:rgb(255, 0, 0);">( Select an item to see total and remaining quantity )</span></strong>
                        </label>
                        <input type="number" id="quantity" name="quantity" min="1" value="0" required oninput="calculateTotalCost()" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                        <input type="hidden" id="maxQuantity" value="0">
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <label for="min_stock_level" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Minimum Stock Level</label>
                        <input type="number" id="min_stock_level" name="min_stock_level" min="0" value="5" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                    <div style="flex: 1; min-width: 300px;">
                        <label for="cost" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Cost per Item (₱)</label>
                        <input type="number" id="cost" name="cost" step="0.01" min="0" value="0.00" readonly style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none; background-color: #f7fafc;">
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <label for="total_cost" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Total Cost (₱)</label>
                        <input type="text" id="total_cost" name="total_cost" readonly value="0.00" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; background-color: #f7fafc;">
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                    <div style="flex: 1; min-width: 300px;">
                        <label for="location" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g., Warehouse A" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <label for="supplier" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Supplier</label>
                        <input type="text" id="supplier" name="supplier" readonly style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none; background-color: #f7fafc;">
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <label for="description" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Item details..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <a href="items.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #718096;">Cancel</a>
                    <button type="submit" name="btn-add-item" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white; cursor: pointer;">Add Item</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Pass PHP items data to JavaScript
const itemsData = <?php echo json_encode($itemsData, JSON_PRETTY_PRINT); ?>;

// Debug: Log itemsData to console
console.log('itemsData:', itemsData);

function updateItemDetails() {
    const itemName = document.getElementById('name').value;
    const costInput = document.getElementById('cost');
    const supplierInput = document.getElementById('supplier');
    const maxQuantityInput = document.getElementById('maxQuantity');
    const quantityInput = document.getElementById('quantity');
    const itemQuantityInfo = document.getElementById('item-quantity-info');

    console.log('Selected itemName:', itemName); // Debug log

    if (itemName && itemsData[itemName]) {
        console.log('Item data found:', itemsData[itemName]); // Debug log

        // Auto-fill cost and supplier
        costInput.value = itemsData[itemName].unit_cost.toFixed(2);
        supplierInput.value = itemsData[itemName].supplier || 'Unknown';
        
        // Set max quantity
        const totalQuantity = itemsData[itemName].total_quantity;
        const remainingQuantity = itemsData[itemName].remaining_quantity;
        maxQuantityInput.value = remainingQuantity;

        // Update the label with total and remaining quantity
        itemQuantityInfo.textContent = `(Remaining: ${remainingQuantity})`;
        
        // Reset quantity to 0 and recalculate total cost
        quantityInput.value = 0;
        calculateTotalCost();
    } else {
        console.log('Item data not found for:', itemName); // Debug log

        // Reset fields if no item is selected
        costInput.value = '0.00';
        supplierInput.value = '';
        maxQuantityInput.value = '0';
        itemQuantityInfo.textContent = '(Select an item to see total and remaining quantity)';
        quantityInput.value = 0;
        calculateTotalCost();
    }
}

function calculateTotalCost() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const cost = parseFloat(document.getElementById('cost').value) || 0;
    const totalCost = (quantity * cost).toFixed(2);
    document.getElementById('total_cost').value = totalCost;
}

function validateQuantity() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const maxQuantity = parseInt(document.getElementById('maxQuantity').value) || 0;

    if (quantity <= 0) {
        alert('Quantity must be greater than 0.');
        return false;
    }

    if (quantity > maxQuantity) {
        alert(`Quantity cannot exceed the remaining quantity (${maxQuantity}).`);
        return false;
    }

    return true;
}

// Trigger updateItemDetails on page load if an item is already selected
document.addEventListener('DOMContentLoaded', function() {
    const itemNameSelect = document.getElementById('name');
    if (itemNameSelect.value) {
        updateItemDetails();
    }
});
</script>