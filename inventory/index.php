
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
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $movementType = $_POST['movement_type'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $note = trim($_POST['note'] ?? '');

    if (
        !$productId || !$quantity || $quantity < 1 ||
        !in_array($movementType, ['stock_in', 'stock_out', 'adjustment'], true)
    ) {
        $error = 'Please enter valid inventory information.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT id, quantity FROM products WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception('Product not found.');
            }

            $currentQuantity = (int)$product['quantity'];

            if ($movementType === 'stock_in') {
                $newQuantity = $currentQuantity + $quantity;
            } elseif ($movementType === 'stock_out') {
                if ($quantity > $currentQuantity) {
                    throw new Exception('Not enough stock available.');
                }

                $newQuantity = $currentQuantity - $quantity;
            } else {
                $newQuantity = $quantity;
            }

            $update = $pdo->prepare(
                'UPDATE products SET quantity = ? WHERE id = ?'
            );
            $update->execute([$newQuantity, $productId]);

            $movement = $pdo->prepare(
                'INSERT INTO inventory_movements
                (product_id, user_id, movement_type, quantity, note)
                VALUES (?, ?, ?, ?, ?)'
            );

            $movement->execute([
                $productId,
                $_SESSION['user_id'],
                $movementType,
                $quantity,
                $note ?: null
            ]);

            $pdo->commit();

            $success = 'Inventory updated successfully!';

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Inventory update failed: ' . $e->getMessage());
            $error = $e instanceof Exception
                ? $e->getMessage()
                : 'Could not update inventory. Please try again.';
        }
    }
}

$products = $pdo->query(
    'SELECT id, product_name, sku, quantity
     FROM products ORDER BY product_name'
)->fetchAll(PDO::FETCH_ASSOC);

$history = $pdo->query(
    'SELECT
        m.id,
        p.product_name,
        p.sku,
        m.movement_type,
        m.quantity,
        m.note,
        m.created_at
     FROM inventory_movements m
     JOIN products p ON m.product_id = p.id
     ORDER BY m.id DESC
     LIMIT 20'
)->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<?php
$pageTitle = 'Inventory';
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">
    <h2>Inventory Management</h2>
    <p>Manage stock movements and monitor your inventory.</p>

    <?php if ($error): ?>
        <p class="error-message"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <section class="dashboard-card">
        <h3>Record Stock Movement</h3>

        <form method="POST">
            <div class="form-group">
                <label for="product_id">Product *</label>
                <select name="product_id" id="product_id" required>
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int)$product['id'] ?>">
                            <?= h($product['product_name']) ?>
                            (<?= h($product['sku']) ?>)
                            - Stock: <?= (int)$product['quantity'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="movement_type">Movement Type *</label>
                <select name="movement_type" id="movement_type" required>
                    <option value="">-- Select Movement --</option>
                    <option value="stock_in">Stock In</option>
                    <option value="stock_out">Stock Out</option>
                    <option value="adjustment">Stock Adjustment</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input type="number" name="quantity" id="quantity"
                    min="1" step="1" required>
            </div>

            <div class="form-group">
                <label for="note">Note</label>
                <input type="text" name="note" id="note" maxlength="255"
                    placeholder="Enter a reason or note (optional)">
            </div>

            <button type="submit">Update Inventory</button>
        </form>
    </section>

    <section class="dashboard-card">
        <h3>Recent Inventory Movements</h3>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Movement</th>
                        <th>Quantity</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$history): ?>
                        <tr>
                            <td colspan="6">No inventory movements yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td><?= h($item['product_name']) ?></td>
                                <td><?= h($item['sku']) ?></td>
                                <td><?= h($item['movement_type']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td><?= h($item['note']) ?></td>
                                <td><?= h($item['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
