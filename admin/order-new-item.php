<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include 'header.php';
$function = new Functions();

// Fetch all purchase orders (initial load)
$orders = $function->getAllOrders();
error_log("order-new.item.php: Raw orders: " . json_encode($orders));
?>

<div style="padding: 20px; font-family: 'Arial', sans-serif; background-color: #f5f7fa; min-height: 100vh;">
    <!-- Toast Notification -->
    <?php
    $msg = Session::get("msg");
    if (isset($msg)) {
        echo "<div id='toast-container' style='position: fixed; top: 20px; right: 20px; z-index: 1000;'>";
        echo $msg;
        echo "</div>";
        Session::set("msg", NULL);
    }
    ?>

    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative; z-index: 10;">
        <div>
            <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0;">Purchase Orders</h2>
            <p style="font-size: 14px; color: #718096; margin: 5px 0 0 0;">Manage your purchase orders for materials and equipment.</p>
        </div>
        <a href="add-order.php" id="add-order-btn" style="background-color: #2d3748; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; z-index: 20;">
            <span style="margin-right: 5px;">+</span> Add Order
        </a>
    </div>

    <!-- Table Section -->
    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                        <th style="padding: 12px 16px;">Code</th>
                        <th style="padding: 12px 16px;">Item Name</th>
                        <th style="padding: 12px 16px;">Quantity</th>
                        <th style="padding: 12px 16px;">Total Cost (₱)</th>
                        <th style="padding: 12px 16px;">Supplier</th>
                        <th style="padding: 12px 16px;">Order Date</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="orders-table">
                    <?php
                    if ($orders) {
                        foreach ($orders as $order):
                            $statusLower = strtolower(trim($order['status'])); // Normalize: lowercase and trim
                            error_log("order-new-item.php: Processing order {$order['po_number']}, status: {$statusLower}");
                            $statusColor = $statusLower === 'ordered' ? '#f6e05e' : 
                                          ($statusLower === 'shipped' ? '#63b3ed' : 
                                          ($statusLower === 'delivered' ? '#38a169' : 
                                          ($statusLower === 'canceled' ? '#e53e3e' : '#718096')));
                    ?>
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                            <td style="padding: 12px 16px;"><?=$order['po_number'];?></td>
                            <td style="padding: 12px 16px;"><?=$order['item_name'];?></td>
                            <td style="padding: 12px 16px;"><?=$order['quantity'];?></td>
                            <td style="padding: 12px 16px;"><strong>₱ <?=number_format($order['total_cost'], 2);?></strong></td>
                            <td style="padding: 12px 16px;"><?=$order['supplier_name'];?></td>
                            <td style="padding: 12px 16px;"><?=date('F j, Y, g:i A', strtotime($order['order_date']));?></td>
                            <td style="padding: 12px 16px;">
                                <span style="background-color: <?=$statusColor;?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    <?=ucfirst($order['status']);?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($statusLower !== 'canceled' && $statusLower !== 'delivered'): ?>
                                    <a href="edit-order.php?po_number=<?=$order['po_number'];?>" style="margin-right: 10px; text-decoration: none;">
                                        <svg style="width: 20px; height: 20px;" fill="none" stroke="#2d3748" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button onclick="openCancelModal('<?=$order['po_number'];?>')" style="background: none; border: none; cursor: pointer;">
                                        <svg style="width: 20px; height: 20px;" fill="none" stroke="#e53e3e" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6h12z"></path>
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <span style="color: #718096; font-size: 14px;">No actions available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    } else {
                        echo "<tr><td colspan='8' style='padding: 20px; text-align: center; color: #718096; font-size: 14px;'>No purchase orders found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;">
        <div style="background-color: white; border-radius: 8px; padding: 20px; width: 400px; max-width: 90%;">
            <h3 style="font-size: 18px; font-weight: 600; color: #1a202c; margin: 0 0 10px 0;">Cancel Order</h3>
            <p style="font-size: 14px; color: #4a5568; margin: 0 0 20px 0;">Are you sure you want to cancel this order? This action cannot be undone.</p>
            <form id="cancelForm" method="post" action="navigate.php">
                <input type="hidden" name="po_number" id="cancelPoNumber">
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeCancelModal()" style="padding: 8px 16px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #718096; cursor: pointer;">No, Keep Order</button>
                    <button type="submit" name="btn-cancel-order" style="padding: 8px 16px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #e53e3e; color: white; cursor: pointer;">Yes, Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Toast Notification Styles */
#toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
}

#toast-container .toast-message {
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 14px;
    color: white;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
    margin-bottom: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

#toast-container .toast-message.success {
    background-color: #38a169;
}

#toast-container .toast-message.error {
    background-color: #e53e3e;
}

/* Ensure the Add Order button is clickable */
#add-order-btn {
    position: relative;
    z-index: 20;
}
</style>

<script>
// Show and hide toast notification
document.addEventListener('DOMContentLoaded', function() {
    const toastContainer = document.getElementById('toast-container');
    if (toastContainer) {
        const toasts = toastContainer.querySelectorAll('.toast-message');
        toasts.forEach(toast => {
            setTimeout(() => { toast.style.opacity = '1'; }, 100);
            setTimeout(() => { toast.style.opacity = '0'; }, 3000);
            setTimeout(() => { toast.remove(); }, 3500);
        });
    }

    // Start real-time updates
    fetchPurchaseOrdersData();
    setInterval(fetchPurchaseOrdersData, 5000); // Poll every 5 seconds
});

// Modal functions
function openCancelModal(poNumber) {
    document.getElementById('cancelPoNumber').value = poNumber;
    document.getElementById('cancelModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
    document.getElementById('cancelPoNumber').value = '';
}

// Fetch and update purchase orders in real-time
function fetchPurchaseOrdersData() {
    fetch('fetch_purchase_orders_data.php')
        .then(response => response.json())
        .then(data => {
            const ordersTable = document.getElementById('orders-table');
            ordersTable.innerHTML = '';

            if (data.orders.length === 0) {
                ordersTable.innerHTML = `
                    <tr>
                        <td colspan="8" style="padding: 20px; text-align: center; color: #718096; font-size: 14px;">No purchase orders found.</td>
                    </tr>
                `;
            } else {
                data.orders.forEach(order => {
                    const statusLower = order.status.toLowerCase().trim(); // Normalize: lowercase and trim
                    console.log(`Processing order ${order.po_number}, status: ${statusLower}`); // Debug in browser console
                    const statusColor = statusLower === 'ordered' ? '#f6e05e' : 
                                       (statusLower === 'shipped' ? '#63b3ed' : 
                                       (statusLower === 'delivered' ? '#38a169' : 
                                       (statusLower === 'canceled' ? '#e53e3e' : '#718096')));

                    const actions = (statusLower !== 'canceled' && statusLower !== 'delivered') ? `
                        <a href="edit-order.php?po_number=${order.po_number}" style="margin-right: 10px; text-decoration: none;">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="#2d3748" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <button onclick="openCancelModal('${order.po_number}')" style="background: none; border: none; cursor: pointer;">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="#e53e3e" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6h12z"></path>
                            </svg>
                        </button>
                    ` : `<span style="color: #718096; font-size: 14px;">No actions available</span>`;

                    ordersTable.innerHTML += `
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                            <td style="padding: 12px 16px;">${order.po_number}</td>
                            <td style="padding: 12px 16px;">${order.item_name}</td>
                            <td style="padding: 12px 16px;">${order.quantity}</td>
                            <td style="padding: 12px 16px;"><strong>₱ ${order.total_cost}</strong></td>
                            <td style="padding: 12px 16px;">${order.supplier_name}</td>
                            <td style="padding: 12px 16px;">${order.order_date}</td>
                            <td style="padding: 12px 16px;">
                                <span style="background-color: ${statusColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">${actions}</td>
                        </tr>
                    `;
                });
            }
        })
        .catch(error => console.error('Error fetching purchase orders:', error));
}
</script>