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
error_log("user-dashboard.php: Fetching requests for guest_user_id=$guest_user_id");

// Fetch user's requests (initial load)
$requests = $function->getItemRequestsByUser($guest_user_id);
error_log("user-dashboard.php: Fetched " . count($requests) . " requests: " . json_encode($requests));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/favicon.svg" />
    <link href="assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Button Icon Styles */
        .action-btn {
            padding: 6px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
            background-color: transparent;
            transition: background-color 0.2s;
        }

        .action-btn:hover {
            background-color: #e2e8f0;
        }

        .action-btn svg {
            width: 20px;
            height: 20px;
        }

        .update-btn svg { fill: #2d3748; }
        .delete-btn svg { fill: #e53e3e; }
        .cancel-btn svg { fill: #d69e2e; }
    </style>
</head>
<body>
    <div style="padding: 50px; font-family: 'Arial', sans-serif; background-color: #f5f7fa; min-height: 100vh;">
        <!-- Toast Notification -->
        <?php if ($msg = Session::get('msg')): ?>
            <div id="toast-container">
                <?php echo $msg; ?>
            </div>
            <?php Session::set('msg', null); // Clear the message after displaying ?>
        <?php endif; ?>

        <!-- Header Section -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative; z-index: 10;">
            <div>
                <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0;">User Dashboard</h2>
            </div>
            <div>
                <a href="request-items.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white;">Request New Item</a>
                <a href="logout.php" style="padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: rgb(113, 17, 17); color: white;">Back</a>
            </div>
        </div>

        <!-- Table Section -->
        <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                            <th style="padding: 12px 16px;">Item Name</th>
                            <th style="padding: 12px 16px;">Quantity</th>
                            <th style="padding: 12px 16px;">Request Date</th>
                            <th style="padding: 12px 16px;">Status</th>
                            <th style="padding: 12px 16px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requests-table">
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="5" style="padding: 12px 16px; text-align: center; color: #718096;">No requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                $item = $function->getItem($request['item_id']);
                                $availableQuantity = $item ? $item['quantity'] : 0;
                                ?>
                                <tr style="border-top: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= htmlspecialchars($request['item_name']); ?></td>
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= $request['quantity']; ?></td>
                                    <td style="padding: 12px 16px; color: #2d3748;"><?= date('F j, Y, g:i A', strtotime($request['request_date'])); ?></td>
                                    <td style="padding: 12px 16px;">
                                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; 
                                            <?= $request['status'] === 'approved' ? 'background-color: #c6f6d5; color: #38a169;' : 
                                               ($request['status'] === 'pending' ? 'background-color: #fefcbf; color: #d69e2e;' : 
                                               ($request['status'] === 'canceled' ? 'background-color: #ffedd5; color: #f97316;' : 
                                               ($request['status'] === 'rejected' ? 'background-color: #fed7d7; color: #e53e3e;' : 
                                               'background-color: #e2e8f0; color: #4a5568;'))); ?>">
                                            <?= ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button class="action-btn update-btn" onclick="openUpdateModal(<?= $request['id']; ?>, '<?= htmlspecialchars($request['item_name']); ?>', <?= $request['quantity']; ?>, <?= $availableQuantity; ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                                </svg>
                                            </button>
                                            <form method="post" action="navigate.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                                <input type="hidden" name="action" value="delete_request">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token')); ?>">
                                                <input type="hidden" name="request_id" value="<?= $request['id']; ?>">
                                                <button type="submit" class="action-btn delete-btn">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            <form method="post" action="navigate.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token')); ?>">
                                                <input type="hidden" name="request_id" value="<?= $request['id']; ?>">
                                                <button type="submit" class="action-btn cancel-btn">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                        <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                                    </svg>
                                                </button>
                                            </form>
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

        <!-- Update Request Modal -->
        <div id="updateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="background-color: white; width: 400px; margin: 100px auto; padding: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 20px 0;">Update Request</h3>
                <form method="post" action="navigate.php" onsubmit="return validateUpdate()">
                    <input type="hidden" name="action" value="update_request">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token')); ?>">
                    <input type="hidden" id="update_request_id" name="request_id">
                    <input type="hidden" id="update_item_name" name="item_name">
                    <div style="margin-bottom: 20px;">
                        <label for="update_quantity" style="display: block; font-size: 14px; font-weight: 500; color: #4a5568; margin-bottom: 5px;">Quantity</label>
                        <input type="number" id="update_quantity" name="quantity" min="1" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 14px; outline: none;">
                        <input type="hidden" id="update_max_quantity" value="0">
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="closeUpdateModal()" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #718096; cursor: pointer;">Cancel</button>
                        <button type="submit" style="padding: 10px 20px; border-radius: 5px; border: none; font-size: 14px; font-weight: 500; background-color: #2d3748; color: white; cursor: pointer;">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openUpdateModal(requestId, itemName, currentQuantity, maxQuantity) {
            document.getElementById('update_request_id').value = requestId;
            document.getElementById('update_item_name').value = itemName;
            document.getElementById('update_quantity').value = currentQuantity;
            document.getElementById('update_max_quantity').value = maxQuantity;
            document.getElementById('updateModal').style.display = 'block';
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        function validateUpdate() {
            const quantity = parseInt(document.getElementById('update_quantity').value) || 0;
            const maxQuantity = parseInt(document.getElementById('update_max_quantity').value) || 0;

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
                    setTimeout(() => { toast.style.opacity = '1'; }, 100);
                    setTimeout(() => { toast.style.opacity = '0'; }, 3000);
                    setTimeout(() => { toast.remove(); }, 3500);
                });
            }

            // Start real-time updates
            fetchUserDashboardData();
            setInterval(fetchUserDashboardData, 5000); // Poll every 5 seconds
        });

        function fetchUserDashboardData() {
            fetch('fetch_user_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    const requestsTable = document.getElementById('requests-table');
                    requestsTable.innerHTML = '';

                    if (data.requests.length === 0) {
                        requestsTable.innerHTML = `
                            <tr>
                                <td colspan="5" style="padding: 12px 16px; text-align: center; color: #718096;">No requests found.</td>
                            </tr>
                        `;
                    } else {
                        data.requests.forEach(request => {
                            const statusStyle = request.status === 'Approved' ? 'background-color: #c6f6d5; color: #38a169;' :
                                               (request.status === 'Pending' ? 'background-color: #fefcbf; color: #d69e2e;' :
                                               (request.status === 'Canceled' ? 'background-color: #ffedd5; color: #f97316;' :
                                               (request.status === 'Rejected' ? 'background-color: #fed7d7; color: #e53e3e;' :
                                               'background-color: #e2e8f0; color: #4a5568;')));

                            let actions = request.status === 'Pending' ? `
                                <button class="action-btn update-btn" onclick="openUpdateModal(${request.id}, '${request.item_name}', ${request.quantity}, ${request.available_quantity})">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                </button>
                                <form method="post" action="navigate.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                    <input type="hidden" name="action" value="delete_request">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token')); ?>">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    <button type="submit" class="action-btn delete-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </form>
                                <form method="post" action="navigate.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                    <input type="hidden" name="action" value="cancel_request">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token')); ?>">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    <button type="submit" class="action-btn cancel-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                        </svg>
                                    </button>
                                </form>
                            ` : `<span style="color: #718096; font-size: 14px;">No actions available</span>`;

                            requestsTable.innerHTML += `
                                <tr style="border-top: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #2d3748;">${request.item_name}</td>
                                    <td style="padding: 12px 16px; color: #2d3748;">${request.quantity}</td>
                                    <td style="padding: 12px 16px; color: #2d3748;">${request.request_date}</td>
                                    <td style="padding: 12px 16px;">
                                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; ${statusStyle}">
                                            ${request.status}
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px;">${actions}</td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => console.error('Error fetching user dashboard data:', error));
        }
    </script>
</body>
</html>