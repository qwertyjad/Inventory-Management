<?php
include_once 'session.php';
include_once 'function.php';

// Initialize session
Session::init();

// Check if user has the 'user' role
Session::requireRole('user');

// Generate CSRF token if it doesn't exist
if (!Session::get('csrf_token')) {
    Session::set('csrf_token', bin2hex(random_bytes(32)));
}

$function = new Functions();
$guest_user_id = Session::get('guest_user_id');
$guest_user_name = Session::get('guest_user_name');

// Fetch all items from the items table
$items = $function->getAllItems();

// Handle item request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn-add-request'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        Session::set("msg", "<div class='alert alert-danger toast-message error'>CSRF token validation failed!</div>");
        header('Location: request-items.php');
        exit;
    }

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
    error_log("Submitting request: guest_user_id=$guest_user_id, item_id=$item_id, item_name=$item_name, quantity=$quantity");
    if ($function->addItemRequest($guest_user_id, $item_id, $item_name, $quantity)) {
        Session::set("msg", "<div class='alert alert-success toast-message success'>Request submitted successfully!</div>");
        // Regenerate CSRF token after form submission
        Session::set('csrf_token', bin2hex(random_bytes(32)));
        header('Location: user-dashboard.php');
        exit;
    } else {
        Session::set("msg", "<div class='alert alert-danger toast-message error'>Failed to submit request!</div>");
        header('Location: request-items.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Items</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/favicon.svg" />
    <link href="assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
</head>
<body class="bg-light min-vh-100">
    <div style="padding: 50px; font-family: 'Arial', sans-serif; background-color: #f5f7fa; min-height: 100vh;">
        <!-- Toast Notification -->
        <?php if ($msg = Session::get('msg')): ?>
            <div id="toast-container">
                <?php echo $msg; ?>
            </div>
            <?php Session::set('msg', null); ?>
        <?php endif; ?>

        <!-- Header Section -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0;">Request Items</h2>
            <a href="user-dashboard.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white;">Back to Dashboard</a>
        </div>

        <!-- Items Table -->
        <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                            <th style="padding: 12px 16px;">Item Name</th>
                            <th style="padding: 12px 16px;">Available Quantity</th>
                            <th style="padding: 12px 16px;">Unit</th>
                            <th style="padding: 12px 16px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="4" style="padding: 12px 16px; text-align: center; color: #718096;">No items available to request.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['quantity'] <= 0) continue; // Skip items with no quantity ?>
                                <tr style="border-top: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= htmlspecialchars($item['name']); ?></td>
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= $item['quantity']; ?></td>
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= htmlspecialchars($item['unit']); ?></td>
                                    <td style="padding: 12px 16px;">
                                        <button onclick="openRequestModal(<?= $item['id']; ?>, '<?= htmlspecialchars($item['name']); ?>', <?= $item['quantity']; ?>)"
                                                style="padding: 6px 12px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white; cursor: pointer;">
                                            Request
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Request Modal -->
        <div id="requestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="background-color: white; width: 400px; margin: 100px auto; padding: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 20px 0;">Request Item</h3>
                <form method="post" onsubmit="return validateRequest()">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token')); ?>">
                    <input type="hidden" id="modal_item_id" name="item_id">
                    <input type="hidden" id="modal_item_name" name="item_name">
                    <div style="margin-bottom: 20px;">
                        <label for="request_quantity" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Quantity</label>
                        <input type="number" id="request_quantity" name="quantity" min="1" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                        <input type="hidden" id="max_quantity" value="0">
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="closeRequestModal()" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #718096; cursor: pointer;">Cancel</button>
                        <button type="submit" name="btn-add-request" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white; cursor: pointer;">Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openRequestModal(itemId, itemName, maxQuantity) {
            document.getElementById('modal_item_id').value = itemId;
            document.getElementById('modal_item_name').value = itemName;
            document.getElementById('max_quantity').value = maxQuantity;
            document.getElementById('request_quantity').value = 1;
            document.getElementById('requestModal').style.display = 'block';
        }

        function closeRequestModal() {
            document.getElementById('requestModal').style.display = 'none';
        }

        function validateRequest() {
            const quantity = parseInt(document.getElementById('request_quantity').value) || 0;
            const maxQuantity = parseInt(document.getElementById('max_quantity').value) || 0;

            if (quantity <= 0) {
                alert('Quantity must be greater than 0.');
                return false;
            }

            if (quantity > maxQuantity) {
                alert(`Requested quantity cannot exceed available quantity (${maxQuantity}).`);
                return false;
            }

            return true;
        }

        // Show and hide toast notification
        document.addEventListener('DOMContentLoaded', function() {
            const toastContainer = document.getElementById('toast-container');
            if (toastContainer) {
                const toasts = toastContainer.querySelectorAll('.toast-message');
                toasts.forEach(toast => {
                    setTimeout(() => {
                        toast.style.opacity = '1';
                    }, 100);
                    setTimeout(() => {
                        toast.style.opacity = '0';
                    }, 3000);
                    setTimeout(() => {
                        toast.remove();
                    }, 3500);
                });
            }
        });
    </script>
</body>
</html>