<?php
$basePath = $basePath ?? '';
$currentSection = basename(dirname($_SERVER['SCRIPT_NAME']));

if ($currentSection === 'stockpilot') {
    $currentSection = 'dashboard';
}
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>StockPilot<span>.</span></h1>
        <small>INVENTORY MANAGEMENT</small>
    </div>

    <nav class="sidebar-nav">
        <p class="nav-label">MAIN MENU</p>

        <a class="<?= $currentSection === 'dashboard' ? 'active' : '' ?>"
           href="<?= $basePath ?>index.php">Dashboard</a>

        <a class="<?= $currentSection === 'products' ? 'active' : '' ?>"
           href="<?= $basePath ?>products/index.php">Products</a>

        <a class="<?= $currentSection === 'categories' ? 'active' : '' ?>"
           href="<?= $basePath ?>categories/index.php">Categories</a>

        <a class="<?= $currentSection === 'inventory' ? 'active' : '' ?>"
           href="<?= $basePath ?>inventory/index.php">Inventory</a>

        <a class="<?= $currentSection === 'sales' && basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>"
           href="<?= $basePath ?>sales/index.php">Sales</a>

        <a class="<?= $currentSection === 'sales' && basename($_SERVER['SCRIPT_NAME']) === 'history.php' ? 'active' : '' ?>"
           href="<?= $basePath ?>sales/history.php">Sales History</a>

        <a class="<?= $currentSection === 'suppliers' ? 'active' : '' ?>"
           href="<?= $basePath ?>suppliers/index.php">Suppliers</a>

        <a class="<?= $currentSection === 'reports' ? 'active' : '' ?>"
           href="<?= $basePath ?>reports/index.php">Reports</a>

        <p class="nav-label">ACCOUNT</p>
        <a href="<?= $basePath ?>logout.php">Logout</a>
    </nav>
</aside>
