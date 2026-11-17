<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$saleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$saleId || $saleId < 1) {
    exit('Invalid invoice ID.');
}

$stmt = $pdo->prepare(
    'SELECT sales.*, users.full_name
     FROM sales
     LEFT JOIN users ON sales.user_id = users.id
     WHERE sales.id = :id'
);

$stmt->execute(['id' => $saleId]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    exit('Invoice not found.');
}

$itemStmt = $pdo->prepare(
    'SELECT sale_items.*, products.product_name, products.sku
     FROM sale_items
     INNER JOIN products ON sale_items.product_id = products.id
     WHERE sale_items.sale_id = :sale_id'
);

$itemStmt->execute(['sale_id' => $saleId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Invoice ' . $sale['invoice_number'];
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .invoice-container {
        max-width: 950px;
        margin: 30px auto;
    }

    .invoice-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 15px;
    }

    .invoice-actions a {
        color: #4f46e5;
        text-decoration: none;
        font-weight: 600;
    }

    .invoice-sheet {
        background: #fff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 30px;
    }

    .invoice-brand h1 {
        margin: 0;
        font-size: 30px;
        color: #172554;
    }

    .invoice-brand p,
    .invoice-number p {
        color: #64748b;
        margin-top: 6px;
    }

    .invoice-number {
        text-align: right;
    }

    .invoice-number h2 {
        margin: 0;
        color: #4f46e5;
        font-size: 21px;
    }

    .invoice-divider {
        border: 0;
        border-top: 1px solid #e2e8f0;
        margin: 25px 0;
    }

    .invoice-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }

    .invoice-details h3,
    .invoice-products-title {
        color: #64748b;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    .invoice-details p {
        margin: 6px 0;
        color: #1e293b;
    }

    .invoice-table-wrapper {
        overflow-x: auto;
    }

    .invoice-table {
        width: 100%;
        border-collapse: collapse;
    }

    .invoice-table th {
        background: #f1f5f9;
        color: #475569;
        text-align: left;
        padding: 13px;
        font-size: 13px;
    }

    .invoice-table td {
        padding: 14px 13px;
        border-bottom: 1px solid #e2e8f0;
    }

    .invoice-total {
        max-width: 320px;
        margin: 25px 0 0 auto;
    }

    .invoice-total-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        color: #475569;
    }

    .invoice-grand-total {
        border-top: 2px solid #e2e8f0;
        margin-top: 10px;
        padding-top: 15px;
        font-size: 21px;
        font-weight: 700;
        color: #172554;
    }

    .invoice-thankyou {
        text-align: center;
        color: #64748b;
        margin-top: 35px;
    }

    @media (max-width: 600px) {
        .invoice-sheet {
            padding: 20px;
        }

        .invoice-header {
            flex-direction: column;
        }

        .invoice-number {
            text-align: left;
        }

        .invoice-details {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        body {
            background: #fff !important;
        }

        .sidebar,
        .invoice-actions {
            display: none !important;
        }

        .dashboard-container {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .invoice-container {
            max-width: 100%;
            margin: 0;
        }

        .invoice-sheet {
            box-shadow: none;
            padding: 15px;
        }
    }
</style>

<main class="dashboard-container">
    <div class="invoice-container">

        <div class="invoice-actions">
            <a href="history.php">← Back to Sales History</a>
            <button type="button" onclick="window.print()">Print Invoice</button>
        </div>

        <section class="invoice-sheet">

            <div class="invoice-header">
                <div class="invoice-brand">
                    <h1>StockPilot<span style="color:#4f46e5;">.</span></h1>
                    <p>INVENTORY MANAGEMENT</p>
                    <strong>SALES INVOICE</strong>
                </div>

                <div class="invoice-number">
                    <h2><?= h($sale['invoice_number']) ?></h2>
                    <p><?= h($sale['created_at']) ?></p>
                </div>
            </div>

            <hr class="invoice-divider">

            <div class="invoice-details">
                <div>
                    <h3>Bill To</h3>
                    <p><strong><?= h($sale['customer_name'] ?: 'Walk-in Customer') ?></strong></p>
                </div>

                <div>
                    <h3>Payment Information</h3>
                    <p>Method: <?= h(ucwords(str_replace('_', ' ', $sale['payment_method']))) ?></p>
                    <p>Status: <?= h(ucfirst($sale['sale_status'])) ?></p>
                    <p>Sold by: <?= h($sale['full_name'] ?? 'Staff') ?></p>
                </div>
            </div>

            <h3 class="invoice-products-title">Purchased Products</h3>

            <div class="invoice-table-wrapper">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= h($item['product_name']) ?></td>
                                <td><?= h($item['sku']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td>Rs. <?= number_format((float)$item['unit_price'], 2) ?></td>
                                <td>Rs. <?= number_format((float)$item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$items): ?>
                            <tr>
                                <td colspan="5">No products found for this invoice.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="invoice-total">
                <div class="invoice-total-row">
                    <span>Subtotal</span>
                    <span>Rs. <?= number_format((float)$sale['subtotal'], 2) ?></span>
                </div>

                <div class="invoice-total-row">
                    <span>Discount</span>
                    <span>Rs. <?= number_format((float)$sale['discount'], 2) ?></span>
                </div>

                <div class="invoice-total-row invoice-grand-total">
                    <span>Total</span>
                    <span>Rs. <?= number_format((float)$sale['total'], 2) ?></span>
                </div>
            </div>

            <hr class="invoice-divider">

            <p class="invoice-thankyou">Thank you for your business!</p>

        </section>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
