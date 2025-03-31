<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include 'header.php';
$function = new Functions();

// Fetch data for dashboard widgets
$items = $function->getAllItems();
$totalItems = count($items);
$inventoryValue = array_sum(array_column($items, 'total_cost'));
$lowStockItems = array_filter($items, function($item) {
    return $item['quantity'] <= $item['min_stock_level'];
});
$lowStockCount = count($lowStockItems);
$stockHealth = $totalItems > 0 ? (1 - $lowStockCount / $totalItems) * 100 : 100;

// Calculate total unique units for Inventory Status
$uniqueUnits = array_unique(array_column($items, 'unit'));
$totalUnits = count($uniqueUnits);

// Aggregate items by unit for the chart
$unitValues = [];
foreach ($items as $item) {
    $unit = isset($item['unit']) && $item['unit'] !== '' ? $item['unit'] : 'Unknown';
    if (!isset($unitValues[$unit])) {
        $unitValues[$unit] = 0;
    }
    $unitValues[$unit] += $item['total_cost'];
}
arsort($unitValues);

$totalValue = array_sum($unitValues);
$labels = array_keys($unitValues);
$values = array_values($unitValues);
$percentages = [];
foreach ($unitValues as $value) {
    $percentage = $totalValue > 0 ? ($value / $totalValue) * 100 : 0;
    $percentages[] = round($percentage);
}
?>

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
        <div style="flex: 1; min-width: 200px; background-color: #E0FFFF; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid lightgrey; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
            <div style="position: absolute; top: 10px; left: 10px;">
                <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Total Items</h6>
            <h3 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;"><?=$totalItems;?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Items in inventory</p>
        </div>
        <div style="flex: 1; min-width: 200px; background-color: #f7fafc; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid lightgrey; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
            <div style="position: absolute; top: 10px; left: 10px;">
                <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Inventory Value</h6>
            <h3 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;">₱<?=number_format($inventoryValue, 2);?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Total value of inventory</p>
        </div>
        <div style="flex: 1; min-width: 200px; background-color: <?=$lowStockCount > 0 ? '#FFFFE0' : 'white';?>; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid lightgrey; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
            <div style="position: absolute; top: 10px; left: 10px;">
                <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Low Stock Alerts</h6>
            <h3 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;"><?=$lowStockCount;?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Items below minimum level</p>
        </div>
    </div>

    <!-- Low Stock Alerts and Inventory Status -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <!-- Low Stock Alerts -->
        <div style="flex: 1; min-width: 300px; background-color: #f5f5f5; border: 1px solid lightgrey; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h6 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0;">Low Stock Alerts</h6>
                    <span style="background-color: #e53e3e; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;"><?=$lowStockCount;?> Critical</span>
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
                        <tbody>
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

        <!-- Inventory Status -->
        <div style="flex: 1; min-width: 300px;">
            <div style="background-color: #f5f5f5; border: 1px solid lightgrey; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;">
                <h6 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0 0 15px 0;">Inventory Status</h6>
                
                <!-- Updated Inventory Status Summary (Total Units and Stock Health) -->
                <div style="display: flex; justify-content: space-around; text-align: center; margin-bottom: 20px; gap: 20px;">
                    <div style="flex: 1; background-color: #f7fafc; border-radius: 8px; padding: 15px; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid #e2e8f0; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                        <div style="position: absolute; top: 10px; left: 10px;">
                            <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <h6 style="font-size: 14px; color: #718096; margin: 0 0 5px 0;">Total Units</h6>
                        <p style="font-size: 16px; font-weight: 600; color: #2d3748; margin: 0;"><?=$totalUnits;?></p>
                    </div>
                    <div style="flex: 1; background-color: #f7fafc; border-radius: 8px; padding: 15px; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid #e2e8f0; position: relative;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                        <div style="position: absolute; top: 10px; left: 10px;">
                            <svg style="width: 20px; height: 20px; color: #718096;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h6 style="font-size: 14px; color: #718096; margin: 0 0 5px 0;">Stock Health</h6>
                        <p style="font-size: 16px; font-weight: 600; color: #38a169; margin: 0 0 5px 0;"><?=(int)$stockHealth;?>%</p>
                        <div style="width: 100%; height: 5px; background-color: #e2e8f0; border-radius: 5px;">
                            <div style="width: <?=$stockHealth;?>%; height: 8px; background-color: #38a169; border-radius: 5px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Donut Chart for Inventory Value Distribution (Grouped by Unit) -->
                <div style="margin-top: 20px; display: flex; align-items: center;">
                    <div style="flex: 1; max-height: 200px; overflow-y: auto;">
                        <h6 style="font-size: 14px; color: #1a202c; margin: 0 0 10px 0;">Inventory Value Distribution by Unit</h6>
                        <?php
                        $colors = [
                            '#1e3a8a', '#38bdf8', '#d1d5db', '#2563eb', '#60a5fa', 
                            '#93c5fd', '#1d4ed8', '#3b82f6', '#bfdbfe', '#1e40af'
                        ];
                        $i = 0;
                        foreach ($unitValues as $unit => $value) {
                            $colorIndex = $i % count($colors);
                            echo '<div style="display: flex; align-items: center; margin-bottom: 5px;">';
                            echo '<div style="width: 12px; height: 12px; border-radius: 50%; background-color: ' . $colors[$colorIndex] . '; margin-right: 8px;"></div>';
                            echo '<span style="font-size: 14px; color: #2d3748;">' . htmlspecialchars($unit) . '</span>';
                            echo '<span style="font-size: 14px; font-weight: 600; color: #2d3748; margin-left: auto;">' . $percentages[$i] . '%</span>';
                            echo '</div>';
                            $i++;
                        }
                        ?>
                    </div>
                    <div style="flex: 1; position: relative; width: 150px; height: 150px; display: flex; justify-content: center; align-items: center;">
                        <div id="inventoryDonutChart" style="width: 150px; height: 150px;"></div>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ensure the chart container is centered */
#inventoryDonutChart {
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Remove any default margins or padding that might offset the chart */
#inventoryDonutChart > * {
    margin: 0 !important;
    padding: 0 !important;
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
// Initialize ApexCharts for the Inventory Value Distribution Donut Chart
document.addEventListener('DOMContentLoaded', function() {
    var options = {
        chart: {
            type: 'donut',
            width: 150,
            height: 150,
            offsetX: 0,
            offsetY: 0
        },
        series: <?=json_encode($values);?>,
        labels: <?=json_encode($labels);?>,
        colors: [
            '#1e3a8a', '#38bdf8', '#d1d5db', '#2563eb', '#60a5fa', 
            '#93c5fd', '#1d4ed8', '#3b82f6', '#bfdbfe', '#1e40af'
        ],
        dataLabels: {
            enabled: false
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: false
                    }
                }
            }
        },
        legend: {
            show: false
        },
        tooltip: {
            enabled: false
        }
    };

    var chart = new ApexCharts(document.querySelector("#inventoryDonutChart"), options);
    chart.render();
});
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

<?php
include 'footer.php';
?>