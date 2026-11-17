<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query(
    'SELECT
        id,
        invoice_number,
        customer_name,
        subtotal,
        discount,
        total,
        payment_method,
        sale_status,
        created_at
     FROM sales
     ORDER BY id DESC'
);

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<?php
$pageTitle = 'Sales History';
$basePath = '../';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">

    <h2>Sales History</h2>
    <p>View all completed and cancelled sales.</p>

    <section class="dashboard-card">

        <h3>All Sales (<?= count($sales) ?>)</h3>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Customer</th>
                        <th>Subtotal</th>
                        <th>Discount</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$sales): ?>
                        <tr>
                            <td colspan="8">No sales recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td>
                                    <a href="invoice.php?id=<?= (int)$sale['id'] ?>">
                                        <?= h($sale['invoice_number']) ?>
                                    </a>
                                </td>
                                <td><?= h($sale['customer_name'] ?: 'Walk-in Customer') ?></td>
                                <td>Rs. <?= number_format((float)$sale['subtotal'], 2) ?></td>
                                <td>Rs. <?= number_format((float)$sale['discount'], 2) ?></td>
                                <td><strong>Rs. <?= number_format((float)$sale['total'], 2) ?></strong></td>
                                <td><?= h(ucwords(str_replace('_', ' ', $sale['payment_method']))) ?></td>
                                <td><?= h(ucfirst($sale['sale_status'])) ?></td>
                                <td><?= h($sale['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
