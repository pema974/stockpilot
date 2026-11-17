
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | StockPilot</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
    <div class="sidebar-brand">
        <h1>StockPilot<span>.</span></h1>
        <small>INVENTORY MANAGEMENT</small>
    </div>

    <nav class="sidebar-nav">
        <p class="nav-label">MAIN MENU</p>

        <a class="active" href="index.php">Dashboard</a>
        <a href="products/index.php">Products</a>
        <a href="categories/index.php">Categories</a>
        <a href="inventory/index.php">Inventory</a>
        <a href="sales/index.php">Sales</a>
        <a href="suppliers/index.php">Suppliers</a>
        <a href="reports/index.php">Reports</a>

        <p class="nav-label">ACCOUNT</p>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

    <main class="dashboard-container">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?>!</h2>
        <p>You are logged in to StockPilot.</p>

        <section class="dashboard-cards">
            <article class="dashboard-card">
                <h3>Products</h3>
                <p>Manage your products and prices.</p>
                <a href="products/index.php">Open Products</a>
            </article>

            <article class="dashboard-card">
                <h3>Categories</h3>
                <p>Organize products by category.</p>
                <a href="categories/index.php">Open Categories</a>
            </article>

            <article class="dashboard-card">
                <h3>Inventory</h3>
                <p>Track stock movements and quantities.</p>
                <a href="inventory/index.php">Open Inventory</a>
            </article>

            <article class="dashboard-card">
                <h3>Sales</h3>
                <p>Manage sales and invoices.</p>
                <a href="sales/index.php">Open Sales</a>
            </article>

            <article class="dashboard-card">
                <h3>Suppliers</h3>
                <p>Maintain your supplier information.</p>
                <a href="suppliers/index.php">Open Suppliers</a>
            </article>

            <article class="dashboard-card">
                <h3>Reports</h3>
                <p>Review inventory and sales reports.</p>
                <a href="reports/index.php">Open Reports</a>
            </article>
        </section>
    </main>
</body>
</html>

