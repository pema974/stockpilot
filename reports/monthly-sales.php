<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get selected month or use current month
$selectedMonth = $_GET['month'] ?? date('Y-m');

if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$startDate = $selectedMonth . '-01';
$endDate = date('Y-m-d', strtotime($startDate . ' +1 month'));

// Get monthly sales summary
$summaryStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_invoices,
        COALESCE(SUM(total), 0) AS total_revenue,
        COALESCE(SUM(discount), 0) AS total_discount
     FROM sales
     WHERE created_at >= :start_date
       AND created_at < :end_date
       AND sale_status = 'completed'"
);

$summaryStmt->execute([
    'start_date' => $startDate,
    'end_date' => $endDate
]);

$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

// Get monthly transactions
$salesStmt = $pdo->prepare(
    "SELECT
        id,
        invoice_number,
        customer_name,
        total,
        payment_method,
        created_at
     FROM sales
     WHERE created_at >= :start_date
       AND created_at < :end_date
       AND sale_status = 'completed'
     ORDER BY id DESC"
);

$salesStmt->execute([
    'start_date' => $startDate,
    'end_date' => $endDate
]);

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
    <title>Monthly Sales Report | StockPilot</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php
$basePath = '../';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">

    <h2>Monthly Sales Report</h2>
    <p>Review your sales performance by month.</p>

    <section class="dashboard-card">
        <form method="GET">
            <label for="month">Select Month</label>

            <input
                type="month"
                id="month"
                name="month"
                value="<?= h($selectedMonth) ?>"
                required
            >

            <button type="submit">View Report</button>
        </form>
    </section>

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

        <h3>Transactions for <?= h(date('F Y', strtotime($startDate))) ?></h3>

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
                            <td colspan="5">No completed sales for this month.</td>
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
