<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include '../function.php';
include 'header.php';

$function = new Functions();

// Fetch data for dashboard widgets (initial load)
$items = $function->getAllItems();
$totalItems = count($items);
$inventoryValue = array_sum(array_column($items, 'total_cost'));
$lowStockItems = array_filter($items, function($item) {
    return $item['quantity'] <= $item['min_stock_level'];
});
$lowStockCount = count($lowStockItems);
$stockHealth = $totalItems > 0 ? (1 - $lowStockCount / $totalItems) * 100 : 100;

// Calculate total unique units
$uniqueUnits = array_unique(array_column($items, 'unit'));
$totalUnits = count($uniqueUnits);

// Fetch approved requests
$approvedRequests = $function->getApprovedItemRequests(10);

// Fetch pending requests
$pendingRequests = $function->getPendingItemRequests();
$notifications = [];
foreach ($pendingRequests as $request) {
    $guestName = $request['guest_name'] ?? 'Unknown Guest';
    $itemName = $request['item_name'] ?? 'Unknown Item'; // From items table
    $notifications[] = [
        'message' => "{$guestName}: {$itemName} (Qty: {$request['quantity']})",
        'type' => 'pending',
        'timestamp' => date('F j, Y, g:i A', strtotime($request['request_date']))
    ];
}

error_log("index.php: Loaded with totalItems=$totalItems, inventoryValue=$inventoryValue, lowStockCount=$lowStockCount, pendingRequests=" . count($pendingRequests) . ", approvedRequests=" . count($approvedRequests));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/favicon.svg" />
    <link href="assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div style="padding: 30px; font-family: 'Arial', sans-serif; min-height: 100vh;">
    <?php
    $msg = Session::get("msg");
    if (isset($msg)) {
        echo "<div id='toast-container' style='position: fixed; top: 20px; right: 20px; z-index: 1000;'>";
        echo $msg;
        echo "</div>";
        Session::set("msg", NULL);
    }
    ?>
    <!-- Dashboard Widgets -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        <div id="total-items-widget" style="flex: 1; min-width: 200px; background-color: #E0FFFF; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid lightgrey; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
            <div style="position: absolute; top: 10px; left: 10px;">
                <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Total Items</h6>
            <h3 id="total-items" style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;"><?=$totalItems;?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0 0 5px 0;">Items in inventory</p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 10px;">
                <div>
                    <p style="font-size: 12px; color: #718096; margin: 0;">Total Units: <strong id="total-units"><?=$totalUnits;?></strong></p>
                </div>
                <div>
                    <p style="font-size: 12px; color: #718096; margin: 0;">Stock Health: <strong id="stock-health"><?=(int)$stockHealth;?>%</strong></p>
                </div>
            </div>
        </div>
        <div id="inventory-value-widget" style="flex: 1; min-width: 200px; background-color: #f7fafc; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid lightgrey; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
            <div style="position: absolute; top: 10px; left: 10px;">
                <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Inventory Value</h6>
            <h3 id="inventory-value" style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;">₱<?=number_format($inventoryValue, 2);?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Total value of inventory</p>
        </div>
        <div id="low-stock-widget" style="flex: 1; min-width: 200px; background-color: <?=$lowStockCount > 0 ? '#FFFFE0' : 'white';?>; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid lightgrey; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
            <div style="position: absolute; top: 10px; left: 10px;">
                <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Low Stock Alerts</h6>
            <h3 id="low-stock-count" style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;"><?=$lowStockCount;?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Items below minimum level</p>
        </div>
    </div>

    <!-- Main Content Area -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <!-- Left Column: Low Stock Alerts and Request History -->
        <div style="flex: 2; min-width: 300px;">
            <!-- Low Stock Alerts -->
            <div style="background-color: #f5f5f5; border: 1px solid lightgrey; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <div style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h6 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0;">Low Stock Alerts</h6>
                        <span id="low-stock-badge" style="background-color: #e53e3e; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;"><?=$lowStockCount;?> Critical</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                                    <th style="padding: 12px 16px;">Item</th>
                                    <th style="padding: 12px 16px;">Current Stock</th>
                                    <th style="padding: 12px 16px;">Min. Stock</th>
                                    <th style="padding: 12px 16px;">Status</th>
                                    <th style="padding: 12px 16px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="low-stock-table">
                                <?php
                                if ($lowStockItems) {
                                    foreach ($lowStockItems as $item):
                                ?>
                                    <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                                        <td style="padding: 12px 16px;"><?=$item['name'];?></td>
                                        <td style="padding: 12px 16px;"><?=$item['quantity'];?></td>
                                        <td style="padding: 12px 16px;"><?=$item['min_stock_level'];?></td>
                                        <td style="padding: 12px 16px;">
                                            <span style="background-color: #e53e3e; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">Low Stock</span>
                                        </td>
                                        <td style="padding: 12px 16px;">
                                            <a href="add-order.php?item_id=<?=$item['id'];?>" style="text-decoration: none; color: #718096; transition: color 0.2s;" onmouseover="this.style.color='#2563eb';" onmouseout="this.style.color='#718096';">
                                                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                    endforeach;
                                } else {
                                    echo "<tr><td colspan='5' style='padding: 20px; text-align: center; color: #718096; font-size: 14px;'>No low stock items.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Approved Request History -->
            <div style="background-color: #f5f5f5; border: 1px solid lightgrey; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h6 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0;">Approved Request History</h6>
                        <span id="approved-requests-badge" style="background-color: #38a169; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;"><?=count($approvedRequests);?> Approved</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                                    <th style="padding: 12px 16px;">Guest Name</th>
                                    <th style="padding: 12px 16px;">Item Name</th> <!-- Updated header -->
                                    <th style="padding: 12px 16px;">Quantity</th>
                                    <th style="padding: 12px 16px;">Status</th>
                                    <th style="padding: 12px 16px;">Date</th>
                                </tr>
                            </thead>
                            <tbody id="approved-requests-table">
                                <?php
                                if ($approvedRequests) {
                                    foreach ($approvedRequests as $request):
                                        $guestName = $request['guest_name'] ?? 'Unknown Guest';
                                        $itemName = $request['item_name'] ?? 'Unknown Item';
                                ?>
                                    <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                                        <td style="padding: 12px 16px;"><?=htmlspecialchars($guestName);?></td>
                                        <td style="padding: 12px 16px;"><?=htmlspecialchars($itemName);?></td>
                                        <td style="padding: 12px 16px;"><?=$request['quantity'];?></td>
                                        <td style="padding: 12px 16px;">
                                            <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; background-color: #c6f6d5; color: #38a169;">
                                                Approved
                                            </span>
                                        </td>
                                        <td style="padding: 12px 16px;"><?=date('F j, Y, g:i A', strtotime($request['request_date']));?></td>
                                    </tr>
                                <?php
                                    endforeach;
                                } else {
                                    echo "<tr><td colspan='5' style='padding: 20px; text-align: center; color: #718096; font-size: 14px;'>No approved requests.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Pending Requests Only -->
        <div style="flex: 1; min-width: 300px;">
            <!-- Pending Requests -->
            <div style="background-color: #f5f5f5; border: 1px solid lightgrey; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h6 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0;">Notifications</h6>
                        <span id="notifications-badge" style="background-color: #d69e2e; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;"><?=count($notifications);?> Pending</span>
                    </div>
                    <div id="notifications-list" style="max-height: 400px; overflow-y: auto;">
                        <?php
                        if ($notifications) {
                            foreach ($notifications as $notification) :
                        ?>
                            <div style="display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #edf2f7;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background-color: #d69e2e; margin-right: 10px;"></div>
                                <div style="flex: 1;">
                                    <p style="font-size: 14px; color: #2d3748; margin: 0;"><?=$notification['message'];?></p>
                                    <p style="font-size: 12px; color: #718096; margin: 0;"><?=$notification['timestamp'];?></p>
                                </div>
                            </div>
                        <?php
                            endforeach;
                        } else {
                            echo "<p style='padding: 20px; text-align: center; color: #718096; font-size: 14px;'>No pending requests.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
// Toast handling
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
    fetchDashboardData();
    setInterval(fetchDashboardData, 5000); // Poll every 5 seconds
});

function fetchDashboardData() {
    fetch('fetch_dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            // Update widgets
            document.getElementById('total-items').textContent = data.totalItems;
            document.getElementById('inventory-value').textContent = '₱' + data.inventoryValue;
            document.getElementById('low-stock-count').textContent = data.lowStockCount;
            document.getElementById('total-units').textContent = data.totalUnits;
            document.getElementById('stock-health').textContent = data.stockHealth + '%';
            document.getElementById('low-stock-widget').style.backgroundColor = data.lowStockCount > 0 ? '#FFFFE0' : 'white';

            // Update low stock table
            const lowStockTable = document.getElementById('low-stock-table');
            lowStockTable.innerHTML = '';
            if (data.lowStockItems.length > 0) {
                data.lowStockItems.forEach(item => {
                    lowStockTable.innerHTML += `
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                            <td style="padding: 12px 16px;">${item.name}</td>
                            <td style="padding: 12px 16px;">${item.quantity}</td>
                            <td style="padding: 12px 16px;">${item.min_stock_level}</td>
                            <td style="padding: 12px 16px;">
                                <span style="background-color: #e53e3e; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">Low Stock</span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <a href="add-order.php?item_id=${item.id}" style="text-decoration: none; color: #718096; transition: color 0.2s;" onmouseover="this.style.color='#2563eb';" onmouseout="this.style.color='#718096';">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    `;
                });
            } else {
                lowStockTable.innerHTML = `<tr><td colspan="5" style="padding: 20px; text-align: center; color: #718096; font-size: 14px;">No low stock items.</td></tr>`;
            }
            document.getElementById('low-stock-badge').textContent = `${data.lowStockCount} Critical`;

            // Update approved requests table
            const approvedRequestsTable = document.getElementById('approved-requests-table');
            approvedRequestsTable.innerHTML = '';
            if (data.approvedRequests.length > 0) {
                data.approvedRequests.forEach(request => {
                    approvedRequestsTable.innerHTML += `
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                            <td style="padding: 12px 16px;">${request.guest_name}</td>
                            <td style="padding: 12px 16px;">${request.item_name}</td>
                            <td style="padding: 12px 16px;">${request.quantity}</td>
                            <td style="padding: 12px 16px;">
                                <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; background-color: #c6f6d5; color: #38a169;">Approved</span>
                            </td>
                            <td style="padding: 12px 16px;">${request.request_date}</td>
                        </tr>
                    `;
                });
            } else {
                approvedRequestsTable.innerHTML = `<tr><td colspan="5" style="padding: 20px; text-align: center; color: #718096; font-size: 14px;">No approved requests.</td></tr>`;
            }
            document.getElementById('approved-requests-badge').textContent = `${data.approvedRequests.length} Approved`;

            // Update notifications
            const notificationsList = document.getElementById('notifications-list');
            notificationsList.innerHTML = '';
            if (data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    notificationsList.innerHTML += `
                        <div style="display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #edf2f7;">
                            <div style="width: 10px; height: 10px; border-radius: 50%; background-color: #d69e2e; margin-right: 10px;"></div>
                            <div style="flex: 1;">
                                <p style="font-size: 14px; color: #2d3748; margin: 0;">${notification.message}</p>
                                <p style="font-size: 12px; color: #718096; margin: 0;">${notification.timestamp}</p>
                            </div>
                        </div>
                    `;
                });
            } else {
                notificationsList.innerHTML = `<p style="padding: 20px; text-align: center; color: #718096; font-size: 14px;">No pending requests.</p>`;
            }
            document.getElementById('notifications-badge').textContent = `${data.notifications.length} Pending`;
        })
        .catch(error => console.error('Error fetching dashboard data:', error));
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>