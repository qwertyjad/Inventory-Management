<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include 'header.php';
$function = new Functions();

// Fetch items with optional search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$items = $function->getAllItems();

// Apply search (only on name since category is removed)
if ($search) {
    $items = array_filter($items, function($item) use ($search) {
        return stripos($item['name'], $search) !== false;
    });
}

// Apply filter
if ($filter === 'low_stock') {
    $items = array_filter($items, function($item) {
        return $item['quantity'] <= $item['min_stock_level'];
    });
}
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0;">Inventory Management</h2>
            <p style="font-size: 14px; color: #718096; margin: 5px 0 0 0;">Manage your construction materials, tools, and equipment.</p>
        </div>
        <a href="add-item.php" style="background-color: #2d3748; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500;">
            <span style="margin-right: 5px;">+</span> Add Item
        </a>
    </div>

    <!-- Search and Filter Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 10px;">
        <div style="display: flex; align-items: center; background-color: white; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 300px;">
            <input type="text" id="searchInput" placeholder="Search inventory..." style="border: none; padding: 10px; flex: 1; font-size: 14px; outline: none; border-radius: 5px 0 0 5px;">
            <button onclick="searchItems()" style="background-color: #e2e8f0; border: none; padding: 10px; border-radius: 0 5px 5px 0; cursor: pointer;">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="#718096" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="?filter=all" style="padding: 8px 16px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: <?php echo $filter === 'all' ? '#2d3748' : '#edf2f7'; ?>; color: <?php echo $filter === 'all' ? 'white' : '#718096'; ?>;">
                All Items
            </a>
            <a href="?filter=low_stock" style="padding: 8px 16px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: <?php echo $filter === 'low_stock' ? '#2d3748' : '#edf2f7'; ?>; color: <?php echo $filter === 'low_stock' ? 'white' : '#718096'; ?>;">
                Low Stock
            </a>
            <a href="#" style="padding: 8px 16px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #a0aec0; cursor: not-allowed;">
                Categories
            </a>
            <a href="#" style="padding: 8px 16px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; background-color: #edf2f7; color: #a0aec0; cursor: not-allowed;">
                Locations
            </a>
        </div>
    </div>

    <!-- Table Section -->
    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;" id="itemsTable">
                <thead>
                    <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                        <th style="padding: 12px 16px;">Name</th>
                        <th style="padding: 12px 16px;">Quantity</th>
                        <th style="padding: 12px 16px;">Unit</th>
                        <th style="padding: 12px 16px;">Min Stock</th>
                        <th style="padding: 12px 16px;">Cost (₱)</th>
                        <th style="padding: 12px 16px;">Last Updated</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody">
                    <?php
                    if ($items) {
                        foreach ($items as $item):
                            $status = $item['quantity'] <= $item['min_stock_level'] ? 'Low Stock' : 'In Stock';
                            $statusColor = $item['quantity'] <= $item['min_stock_level'] ? '#e53e3e' : '#38a169';
                    ?>
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;" data-id="<?=$item['id'];?>">
                            <td style="padding: 12px 16px;"><?=$item['name'];?></td>
                            <td style="padding: 12px 16px;"><?=$item['quantity'];?></td>
                            <td style="padding: 12px 16px;"><?=$item['unit'];?></td>
                            <td style="padding: 12px 16px;"><?=$item['min_stock_level'];?></td>
                            <td style="padding: 12px 16px;">₱<?=number_format($item['cost'], 2);?></td>
                            <td style="padding: 12px 16px;"><?=date('Y-m-d H:i:s', strtotime($item['last_updated']));?>Z</td>
                            <td style="padding: 12px 16px;">
                                <span style="background-color: <?=$statusColor;?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    <?=$status;?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="display: flex; gap: 10px;">
                                    <a href="edit-item.php?item_id=<?=$item['id'];?>" title="Edit" style="text-decoration: none;">
                                        <svg style="width: 20px; height: 20px;" fill="none" stroke="#2d3748" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button onclick="showDeleteModal(<?=$item['id'];?>, '<?=$item['name'];?>')" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                        <svg style="width: 20px; height: 20px;" fill="none" stroke="#e53e3e" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a2 2 0 00-2 2h8a2 2 0 00-2-2m-4 0h4m-5 4v12m5-12v12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    } else {
                        echo "<tr><td colspan='8' style='padding: 20px; text-align: center; color: #718096; font-size: 14px;'>No items found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%;">
            <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #2d3748;">Confirm Delete</h3>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #718096;">Are you sure you want to delete "<span id="deleteItemName"></span>"? This action cannot be undone.</p>
            <form id="deleteForm" method="post" action="navigate.php" style="margin: 0;">
                <input type="hidden" name="id" id="deleteItemId">
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="hideDeleteModal()" style="background-color: #edf2f7; color: #718096; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">Cancel</button>
                    <button type="submit" name="btn-delete-item" style="background-color: #e53e3e; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">Delete</button>
                </div>
            </form>
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
function searchItems() {
    const searchValue = document.getElementById('searchInput').value;
    window.location.href = `?search=${encodeURIComponent(searchValue)}&filter=<?=$filter;?>`;
}

function showDeleteModal(id, name) {
    document.getElementById('deleteModal').style.display = 'block';
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemName').textContent = name;
}

function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function fetchItems() {
    fetch('api/fetch-items.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('itemsTableBody');
            let html = '';
            data.forEach(item => {
                const status = item.quantity <= item.min_stock_level ? 'Low Stock' : 'In Stock';
                const statusColor = item.quantity <= item.min_stock_level ? '#e53e3e' : '#38a169';
                html += `
                    <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;" data-id="${item.id}">
                        <td style="padding: 12px 16px;">${item.name}</td>
                        <td style="padding: 12px 16px;">${item.quantity}</td>
                        <td style="padding: 12px 16px;">${item.unit}</td>
                        <td style="padding: 12px 16px;">${item.min_stock_level}</td>
                        <td style="padding: 12px 16px;">₱${parseFloat(item.cost).toFixed(2)}</td>
                        <td style="padding: 12px 16px;">${new Date(item.last_updated).toISOString().replace('T', ' ').slice(0, 19)}Z</td>
                        <td style="padding: 12px 16px;">
                            <span style="background-color: ${statusColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                ${status}
                            </span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <div style="display: flex; gap: 10px;">
                                <a href="edit-item.php?item_id=${item.id}" title="Edit" style="text-decoration: none;">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="#2d3748" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <button onclick="showDeleteModal(${item.id}, '${item.name}')" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="#e53e3e" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a2 2 0 00-2 2h8a2 2 0 00-2-2m-4 0h4m-5 4v12m5-12v12"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        })
        .catch(error => console.error('Error fetching items:', error));
}

// Poll for updates every 10 seconds
setInterval(fetchItems, 10000);

// Initial fetch
fetchItems();

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

document.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        hideDeleteModal();
    }
});
</script>