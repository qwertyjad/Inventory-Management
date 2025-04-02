<?php
include '../session.php';
Session::init();

// Ensure only admins can access this file
Session::requireRole('admin');

include '../function.php';
$function = new Functions();

// Handle Approve Request
if (isset($_GET['action']) && $_GET['action'] === 'approve_request' && isset($_GET['request_id'])) {
    $request_id = (int)$_GET['request_id'];
    if ($function->approveRequest($request_id)) {
        Session::set("msg", "<div class='toast-message success'>Request approved successfully!</div>");
    } else {
        Session::set("msg", "<div class='toast-message error'>Failed to approve request!</div>");
    }
    header('Location: admin-handle-requests.php');
    exit;
}

// Handle Reject Request
if (isset($_GET['action']) && $_GET['action'] === 'reject_request' && isset($_GET['request_id'])) {
    $request_id = (int)$_GET['request_id'];
    if ($function->rejectRequest($request_id)) {
        Session::set("msg", "<div class='toast-message success'>Request rejected successfully!</div>");
    } else {
        Session::set("msg", "<div class='toast-message error'>Failed to reject request!</div>");
    }
    header('Location: admin-handle-requests.php');
    exit;
}
include 'header.php';
// Fetch all requests
$requests = $function->getAllItemRequests();
error_log("admin-handle-requests.php: Fetched " . count($requests) . " requests");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Handle Requests</title>
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
<body>
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
            <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0;">Handle Item Requests</h2>
            
        </div>

        <!-- Table Section -->
        <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                            <th style="padding: 12px 16px;">Guest User</th>
                            <th style="padding: 12px 16px;">Item Name</th>
                            <th style="padding: 12px 16px;">Quantity</th>
                            <th style="padding: 12px 16px;">Request Date</th>
                            <th style="padding: 12px 16px;">Status</th>
                            <th style="padding: 12px 16px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="6" style="padding: 12px 16px; text-align: center; color: #718096;">No requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <tr style="border-top: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= htmlspecialchars($request['guest_user_name']); ?></td>
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= htmlspecialchars($request['item_name']); ?></td>
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= $request['quantity']; ?></td>
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= date('Y-m-d H:i:s', strtotime($request['request_date'])); ?></td>
                                    <td style="padding: 12px 16px;">
                                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; 
                                            <?= $request['status'] === 'approved' ? 'background-color: #c6f6d5; color: #38a169;' : 
                                               ($request['status'] === 'pending' ? 'background-color: #fefcbf; color: #d69e2e;' : 
                                               ($request['status'] === 'rejected' ? 'background-color: #fed7d7; color: #e53e3e;' : 
                                               'background-color: #ffedd5; color: #f97316;')); ?>">
                                            <?= ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <a href="admin-handle-requests.php?action=approve_request&request_id=<?= $request['id']; ?>" 
                                               style="padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #38a169; color: white; margin-right: 5px;"
                                               onclick="return confirm('Are you sure you want to approve this request?');">Approve</a>
                                            <a href="admin-handle-requests.php?action=reject_request&request_id=<?= $request['id']; ?>" 
                                               style="padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #e53e3e; color: white;"
                                               onclick="return confirm('Are you sure you want to reject this request?');">Reject</a>
                                        <?php else: ?>
                                            <span style="color: #718096; font-size: 14px;">No actions available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
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
        });
    </script>
</body>
</html>