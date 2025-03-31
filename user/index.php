<?php
include_once '../session.php';
Session::init();
Session::requireRole('staff'); // Use the requireRole method for consistency

include_once 'header.php';
include_once '../function.php';
$function = new Functions();

// Fetch user information
$user_id = Session::get('user_id');
$user = $user_id ? $function->GetUserInfo($user_id) : null;

// Fetch purchase orders assigned to this supplier
$supplier_id = $user['id'];
$orders = $function->getOrdersBySupplier($supplier_id);
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
            <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0;">Supplier Dashboard</h2>
            <p style="font-size: 14px; color: #718096; margin: 5px 0 0 0;">Manage purchase orders assigned to you.</p>
        </div>
    </div>

    <!-- Table Section -->
    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                        <th style="padding: 12px 16px;">PO Number</th>
                        <th style="padding: 12px 16px;">Item Name</th>
                        <th style="padding: 12px 16px;">Quantity</th>
                        <th style="padding: 12px 16px;">Total Cost (₱)</th>
                        <th style="padding: 12px 16px;">Order Date</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($orders) {
                        foreach ($orders as $order):
                            $statusColor = $order['status'] === 'ordered' ? '#f6e05e' : ($order['status'] === 'shipped' ? '#63b3ed' : '#38a169');
                    ?>
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                            <td style="padding: 12px 16px;"><?=$order['po_number'];?></td>
                            <td style="padding: 12px 16px;"><?=$order['item_name'];?></td>
                            <td style="padding: 12px 16px;"><?=$order['quantity'];?></td>
                            <td style="padding: 12px 16px;">₱<?=number_format($order['total_cost'], 2);?></td>
                            <td style="padding: 12px 16px;"><?=$order['order_date'];?></td>
                            <td style="padding: 12px 16px;">
                                <span style="background-color: <?=$statusColor;?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    <?=$order['status'];?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <form method="post" action="navigate.php" style="display: inline;">
                                    <input type="hidden" name="po_number" value="<?=$order['po_number'];?>">
                                    <select name="status" onchange="this.form.submit()" style="padding: 5px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                                        <option value="ordered" <?=$order['status'] === 'ordered' ? 'selected' : '';?>>Ordered</option>
                                        <option value="shipped" <?=$order['status'] === 'shipped' ? 'selected' : '';?>>Shipped</option>
                                        <option value="delivered" <?=$order['status'] === 'delivered' ? 'selected' : '';?>>Delivered</option>
                                    </select>
                                    <input type="hidden" name="btn-update-status">
                                </form>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    } else {
                        echo "<tr><td colspan='7' style='padding: 20px; text-align: center; color: #718096; font-size: 14px;'>No purchase orders assigned to you.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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
</style>

<script>
// Show and hide toast notification
document.addEventListener('DOMContentLoaded', function() {
    const toastContainer = document.getElementById('toast-container');
    if (toastContainer) {
        const toasts = toastContainer.querySelectorAll('.toast-message');
        toasts.forEach(toast => {
            // Show toast
            setTimeout(() => {
                toast.style.opacity = '1';
            }, 100);

            // Hide toast after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
            }, 3000);

            // Remove toast from DOM after fading out to prevent overlap
            setTimeout(() => {
                toast.remove();
            }, 3500);
        });
    }
});
</script>

<?php 
include_once 'footer.php';
?>