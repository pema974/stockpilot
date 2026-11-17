
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Reports';
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="dashboard-container">

    <h2>Reports Dashboard</h2>
    <p>Monitor your business performance and sales activities.</p>

    <div class="dashboard-cards">

        <div class="dashboard-card">
            <h3>Daily Sales Report</h3>
            <p>Check today's invoices, total revenue, discounts, and transactions.</p>
            <a href="daily-sales.php">View Daily Report →</a>
        </div>

        <div class="dashboard-card">
            <h3>Monthly Sales Report</h3>
            <p>Analyze monthly revenue, invoices, discounts, and sales transactions.</p>
            <a href="monthly-sales.php">View Monthly Report →</a>
        </div>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
PHP