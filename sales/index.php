<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customerName = trim($_POST['customer_name'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    $allowedPayments = ['cash', 'card', 'bank_transfer', 'other'];

    if (
        !$productId ||
        !$quantity ||
        $quantity < 1 ||
        !in_array($paymentMethod, $allowedPayments, true)
    ) {
        $error = 'Please enter valid sale information.';
    } else {

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT id, product_name, selling_price, quantity
                 FROM products
                 WHERE id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$productId]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception('Product not found.');
            }

            $currentStock = (int)$product['quantity'];

            if ($quantity > $currentStock) {
                throw new Exception('Not enough stock available.');
            }

            $unitPrice = (float)$product['selling_price'];
            $subtotal = $unitPrice * $quantity;

            // Calculate discount
            $discountPercent = filter_input(
                INPUT_POST,
                'discount_percent',
                FILTER_VALIDATE_FLOAT
            );

            if (
                $discountPercent === false ||
                $discountPercent === null ||
                !is_finite($discountPercent) ||
                $discountPercent < 0 ||
                $discountPercent > 15
            ) {
                throw new Exception('Discount must be between 0% and 15%.');
            }

            $discount = round(
                $subtotal * ($discountPercent / 100),
                2
            );

            $total = round($subtotal - $discount, 2);

            $invoiceNumber = 'INV-' . date('YmdHis') . '-' . random_int(100, 999);

            $saleStmt = $pdo->prepare(
                'INSERT INTO sales
                (invoice_number, user_id, customer_name, subtotal,
                 discount, total, payment_method, sale_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $saleStmt->execute([
                $invoiceNumber,
                $_SESSION['user_id'],
                $customerName ?: null,
                $subtotal,
                $discount,
                $total,
                $paymentMethod,
                'completed'
            ]);

            $saleId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO sale_items
                (sale_id, product_id, quantity, unit_price, line_total)
                VALUES (?, ?, ?, ?, ?)'
            );

            $itemStmt->execute([
                $saleId,
                $productId,
                $quantity,
                $unitPrice,
                $subtotal
            ]);

            $newStock = $currentStock - $quantity;

            $updateStmt = $pdo->prepare(
                'UPDATE products SET quantity = ? WHERE id = ?'
            );
            $updateStmt->execute([$newStock, $productId]);

            $movementStmt = $pdo->prepare(
                'INSERT INTO inventory_movements
                (product_id, user_id, movement_type, quantity, note)
                VALUES (?, ?, ?, ?, ?)'
            );

            $movementStmt->execute([
                $productId,
                $_SESSION['user_id'],
                'stock_out',
                $quantity,
                'Sale invoice: ' . $invoiceNumber
            ]);

            $pdo->commit();

            $success = 'Sale created successfully! Invoice: ' . $invoiceNumber;
        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Sale creation failed: ' . $e->getMessage());

            $error = $e instanceof Exception
                ? $e->getMessage()
                : 'Could not create sale. Please try again.';
        }
    }
}

$products = $pdo->query(
    'SELECT id, product_name, sku, selling_price, quantity
     FROM products
     ORDER BY product_name ASC'
)->fetchAll(PDO::FETCH_ASSOC);

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<?php
$pageTitle = 'Sales';
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">
    <h2>Sales Management</h2>
    <p>Create customer sales and manage transactions.</p>

    <?php if ($error): ?>
        <p class="error-message"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <section class="dashboard-card">
        <h3>Create New Sale</h3>

        <form method="POST" action="">
            <div class="form-group">
                <label for="customer_name">Customer Name</label>
                <input
                    type="text"
                    name="customer_name"
                    id="customer_name"
                    maxlength="150"
                    placeholder="Enter customer name (optional)">
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method *</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="discount_percent">Discount (%)</label>

                <input
                    type="number"
                    name="discount_percent"
                    id="discount_percent"
                    min="0"
                    max="15"
                    step="0.01"
                    value="0"
                    placeholder="Enter discount percentage">

                <small>Maximum discount allowed: 15%</small>
            </div>

            <h3>Product Details</h3>

            <div class="form-group">
                <label for="product_id">Select Product *</label>
                <select name="product_id" id="product_id" required>
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int)$product['id'] ?>">
                            <?= h($product['product_name']) ?>
                            (<?= h($product['sku']) ?>)
                            - Rs. <?= h($product['selling_price']) ?>
                            - Stock: <?= (int)$product['quantity'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input
                    type="number"
                    name="quantity"
                    id="quantity"
                    min="1"
                    step="1"
                    required>
            </div>

            <button type="submit">Create Sale</button>
        </form>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>