<?php
include_once '../session.php';
Session::init();
include_once '../function.php';
$function = new Functions();

// Fetch data for dashboard widgets
$items = $function->getAllItems();
$totalItems = count($items);
$inventoryValue = array_sum(array_column($items, 'total_cost')); // Use total_cost (quantity * cost)
$lowStockItems = array_filter($items, function($item) {
    return $item['quantity'] <= $item['min_stock_level'];
});
$lowStockCount = count($lowStockItems);
$stockHealth = $totalItems > 0 ? (1 - $lowStockCount / $totalItems) * 100 : 100;

// Calculate total unique units for Inventory Status
$uniqueUnits = array_unique(array_column($items, 'unit'));
$totalUnits = count($uniqueUnits);

// Determine the current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modernize Free</title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
</head>


<body>
  <!-- Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <aside class="left-sidebar">
      <!-- Sidebar scroll-->
      <div>
        <div class="brand-logo d-flex align-items-center justify-content-between py-4 px-3">
          <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" 
                 stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                 class="lucide lucide-package me-2">
              <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
              <path d="M12 22V12"></path>
              <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"></path>
              <path d="m7.5 4.27 9 5.15"></path>
            </svg>
            <h2 class="fw-bolder text-dark mb-0">ContructInv</h2>
          </div>
          <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
            <i class="ti ti-x fs-8"></i>
          </div>
        </div>

        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar">
          <ul id="sidebarnav">
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">Home</span>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link <?= $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php" aria-expanded="false">
                <span>
                  <i class="ti ti-layout-dashboard"></i>
                </span>
                <span class="hide-menu">Dashboard</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link <?= $currentPage == 'items.php' ? 'active' : ''; ?>" href="items.php" aria-expanded="false">
                <span>
                  <i class="ti ti-package"></i>
                </span>
                <span class="hide-menu">Items</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link <?= $currentPage == 'order-new-item.php' ? 'active' : ''; ?>" href="order-new-item.php" aria-expanded="false">
                <span>
                  <i class="ti ti-shopping-cart"></i>
                </span>
                <span class="hide-menu">Purcahse Order</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link <?= $currentPage == 'reports.php' ? 'active' : ''; ?>" href="reports.php" aria-expanded="false">
                <span>
                  <i class="ti ti-report"></i>
                </span>
                <span class="hide-menu">Reports</span>
              </a>
            </li>
            <hr>
            <li class="sidebar-item">
              <a class="sidebar-link" href="../auth/logout.php" aria-expanded="false">
                <span>
                  <i class="ti ti-login"></i>
                </span>
                <span class="hide-menu">Logout</span>
              </a>
            </li>
          </ul>
        </nav>
        <!-- End Sidebar navigation -->
      </div>
      <!-- End Sidebar scroll-->
    </aside>
    <!-- Sidebar End -->
    <!-- Main wrapper -->
    <div class="body-wrapper">
      <!-- Header Start -->
   
</body>
</html>
<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
  <script src="../assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../assets/js/dashboard.js"></script>