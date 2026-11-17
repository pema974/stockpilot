<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Today's date
$today = date('Y-m-d');

// Get today's sales summary
$summaryStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_invoices,
        COALESCE(SUM(total), 0) AS total_revenue,
        COALESCE(SUM(discount), 0) AS total_discount
     FROM sales
     WHERE DATE(created_at) = :today
     AND sale_status = 'completed'"
);

$summaryStmt->execute(['today' => $today]);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

// Get today's transactions
$salesStmt = $pdo->prepare(
    "SELECT
        id,
        invoice_number,
        customer_name,
        total,
        payment_method,
        created_at
     FROM sales
     WHERE DATE(created_at) = :today
     AND sale_status = 'completed'
     ORDER BY id DESC"
);

$salesStmt->execute(['today' => $today]);
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report | StockPilot</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php
$basePath = '../';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">

    <h2>Daily Sales Report</h2>
    <p>Sales summary for <?= h(date('F j, Y')) ?></p>

    <section class="dashboard-grid">

        <div class="dashboard-card">
            <h3>Total Invoices</h3>
            <h2><?= (int)$summary['total_invoices'] ?></h2>
        </div>

        <div class="dashboard-card">
            <h3>Total Revenue</h3>
            <h2>Rs. <?= number_format((float)$summary['total_revenue'], 2) ?></h2>
        </div>

        <div class="dashboard-card">
            <h3>Total Discount</h3>
            <h2>Rs. <?= number_format((float)$summary['total_discount'], 2) ?></h2>
        </div>

    </section>

    <section class="dashboard-card">

        <h3>Today's Transactions</h3>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!$sales): ?>
                        <tr>
                            <td colspan="5">No completed sales today.</td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td>
                                    <a href="../sales/invoice.php?id=<?= (int)$sale['id'] ?>">
                                        <?= h($sale['invoice_number']) ?>
                                    </a>
                                </td>

                                <td>
                                    <?= h($sale['customer_name'] ?: 'Walk-in Customer') ?>
                                </td>

                                <td>
                                    Rs. <?= number_format((float)$sale['total'], 2) ?>
                                </td>

                                <td>
                                    <?= h(ucwords(str_replace('_', ' ', $sale['payment_method']))) ?>
                                </td>

                                <td>
                                    <?= h($sale['created_at']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

</main>

</body>
</html>
