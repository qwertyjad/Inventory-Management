<?php
include '../session.php';
Session::init();
Session::requireRole('admin');

include 'header.php';
$function = new Functions();

// Fetch data for the report (same as dashboard)
$items = $function->getAllItems();
$totalItems = count($items);
$inventoryValue = array_sum(array_column($items, 'total_cost'));
$lowStockItems = array_filter($items, function($item) {
    return $item['quantity'] <= $item['min_stock_level'];
});
$lowStockCount = count($lowStockItems);

// Aggregate items by unit for the report
$unitValues = [];
foreach ($items as $item) {
    $unit = isset($item['unit']) && $item['unit'] !== '' ? $item['unit'] : 'Unknown';
    if (!isset($unitValues[$unit])) {
        $unitValues[$unit] = 0;
    }
    $unitValues[$unit] += $item['total_cost'];
}
arsort($unitValues);
?>

<div style="padding: 30px; font-family: 'Arial', sans-serif; min-height: 100vh;">
    <h2 style="font-size: 24px; font-weight: 600; color: #1a202c; margin-bottom: 20px;">Inventory Report</h2>

    <!-- Summary Cards -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        <div style="flex: 1; min-width: 200px; background-color: #E0FFFF; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; border: 1px solid lightgrey;">
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Total Items</h6>
            <h3 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;"><?=$totalItems;?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Items in inventory</p>
        </div>
        <div style="flex: 1; min-width: 200px; background-color: #f7fafc; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; border: 1px solid lightgrey;">
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Inventory Value</h6>
            <h3 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;">₱<?=number_format($inventoryValue, 2);?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Total value of inventory</p>
        </div>
        <div style="flex: 1; min-width: 200px; background-color: <?=$lowStockCount > 0 ? '#FFFFE0' : 'white';?>; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; text-align: center; border: 1px solid lightgrey;">
            <h6 style="font-size: 14px; color: #718096; margin: 0 0 10px 0;">Low Stock Alerts</h6>
            <h3 style="font-size: 24px; font-weight: 600; color: #1a202c; margin: 0 0 5px 0;"><?=$lowStockCount;?></h3>
            <p style="font-size: 12px; color: #718096; margin: 0;">Items below minimum level</p>
        </div>
    </div>

    <!-- Report Table -->
    <div style="background-color: #f5f5f5; border: 1px solid lightgrey; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h6 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0;">Inventory Details</h6>
            <button id="exportExcelBtn" style="background-color: #38a169; color: white; padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#2f855a';" onmouseout="this.style.backgroundColor='#38a169';">
                Export to Excel
            </button>
        </div>
        <div style="overflow-x: auto;">
            <table id="inventoryTable" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                        <th style="padding: 12px 16px;">Item Name</th>
                        <th style="padding: 12px 16px;">Quantity</th>
                        <th style="padding: 12px 16px;">Unit</th>
                        <th style="padding: 12px 16px;">Cost per Unit</th>
                        <th style="padding: 12px 16px;">Total Cost</th>
                        <th style="padding: 12px 16px;">Min. Stock Level</th>
                        <th style="padding: 12px 16px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                            <td style="padding: 12px 16px;"><?=$item['name'];?></td>
                            <td style="padding: 12px 16px;"><?=$item['quantity'];?></td>
                            <td style="padding: 12px 16px;"><?=isset($item['unit']) && $item['unit'] !== '' ? $item['unit'] : 'Unknown';?></td>
                            <td style="padding: 12px 16px;">₱<?=number_format($item['cost'], 2);?></td>
                            <td style="padding: 12px 16px;">₱<?=number_format($item['total_cost'], 2);?></td>
                            <td style="padding: 12px 16px;"><?=$item['min_stock_level'];?></td>
                            <td style="padding: 12px 16px;">
                                <span style="background-color: <?=$item['quantity'] <= $item['min_stock_level'] ? '#e53e3e' : '#38a169';?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                    <?=$item['quantity'] <= $item['min_stock_level'] ? 'Low Stock' : 'In Stock';?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Unit Distribution Summary -->
    <div style="background-color: #f5f5f5; border: 1px solid lightgrey; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;">
        <h6 style="font-size: 16px; font-weight: 600; color: #1a202c; margin: 0 0 15px 0;">Unit Distribution Summary</h6>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f7fafc; color: #4a5568; font-size: 14px; font-weight: 600; text-align: left;">
                        <th style="padding: 12px 16px;">Unit</th>
                        <th style="padding: 12px 16px;">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unitValues as $unit => $value): ?>
                        <tr style="border-top: 1px solid #edf2f7; font-size: 14px; color: #2d3748;">
                            <td style="padding: 12px 16px;"><?=$unit;?></td>
                            <td style="padding: 12px 16px;">₱<?=number_format($value, 2);?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include write-excel-file library -->
<script src="https://unpkg.com/write-excel-file@2.3.2/dist/write-excel-file.min.js"></script>

<script>
// Prepare data for Excel export
document.getElementById('exportExcelBtn').addEventListener('click', async () => {
    const data = [
        [
            { value: 'Item Name', type: 'string', fontWeight: 'bold' },
            { value: 'Quantity', type: 'string', fontWeight: 'bold' },
            { value: 'Unit', type: 'string', fontWeight: 'bold' },
            { value: 'Cost per Unit', type: 'string', fontWeight: 'bold' },
            { value: 'Total Cost', type: 'string', fontWeight: 'bold' },
            { value: 'Min. Stock Level', type: 'string', fontWeight: 'bold' },
            { value: 'Status', type: 'string', fontWeight: 'bold' }
        ]
    ];

    <?php foreach ($items as $item): ?>
        data.push([
            { value: '<?=$item['name'];?>', type: 'string' },
            { value: <?=$item['quantity'];?>, type: 'number' },
            { value: '<?=isset($item['unit']) && $item['unit'] !== '' ? $item['unit'] : 'Unknown';?>', type: 'string' },
            { value: <?=$item['cost'];?>, type: 'number', format: '₱#,##0.00' },
            { value: <?=$item['total_cost'];?>, type: 'number', format: '₱#,##0.00' },
            { value: <?=$item['min_stock_level'];?>, type: 'number' },
            { value: '<?=$item['quantity'] <= $item['min_stock_level'] ? 'Low Stock' : 'In Stock';?>', type: 'string' }
        ]);
    <?php endforeach; ?>

    data.push([{}]);

    data.push([
        { value: 'Unit Distribution Summary', type: 'string', fontWeight: 'bold', span: 7 }
    ]);
    data.push([
        { value: 'Unit', type: 'string', fontWeight: 'bold' },
        { value: 'Total Value', type: 'string', fontWeight: 'bold' }
    ]);

    <?php foreach ($unitValues as $unit => $value): ?>
        data.push([
            { value: '<?=$unit;?>', type: 'string' },
            { value: <?=$value;?>, type: 'number', format: '₱#,##0.00' }
        ]);
    <?php endforeach; ?>

    await writeExcelFile(data, {
        fileName: 'Inventory_Report_<?=date('Ymd_His');?>.xlsx'
    });
});
</script>

<?php
include 'footer.php';
?>