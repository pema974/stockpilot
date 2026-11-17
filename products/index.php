<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

$categoryStmt = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name ASC'
);
$supplierStmt = $pdo->query(
    'SELECT id, name FROM suppliers ORDER BY name ASC'
);
$suppliers = $supplierStmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim($_POST['product_name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $supplierId = $_POST['supplier_id'] ?? '';
    $costPrice = $_POST['cost_price'] ?? '';
    $sellingPrice = $_POST['selling_price'] ?? '';
    $quantity = $_POST['quantity'] ?? '0';
    $lowStock = $_POST['low_stock_threshold'] ?? '5';



    if ($productName === '' || $sku === '') {
        $error = 'Product name and SKU are required.';
    } elseif (
        !is_numeric($costPrice) || !is_numeric($sellingPrice)
        || !is_numeric($quantity) || !is_numeric($lowStock)
        || (float)$costPrice < 0 || (float)$sellingPrice < 0
        || (float)$quantity < 0 || (float)$lowStock < 0
        || (float)$quantity != (int)$quantity
        || (float)$lowStock != (int)$lowStock
    ) {
        $error = 'Enter valid prices and non-negative whole-number quantities.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO products
(product_name, sku, category_id, supplier_id, cost_price, selling_price, quantity, low_stock_threshold)
VALUES (:name, :sku, :category_id, :supplier_id, :cost, :selling, :quantity, :low_stock)'
            );

            $stmt->execute([
                'name' => $productName,
                'sku' => $sku,
                'category_id' => $categoryId === '' ? null : (int)$categoryId,
                'supplier_id' => $supplierId === '' ? null : (int)$supplierId,
                'cost' => $costPrice,
                'selling' => $sellingPrice,
                'quantity' => (int)$quantity,
                'low_stock' => (int)$lowStock
            ]);

            $success = 'Product added successfully!';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'That SKU already exists. Please use a unique SKU.';
            } else {
                error_log('Product creation failed: ' . $e->getMessage());
                $error = 'Could not add the product. Please try again.';
            }
        }
    }
}

$stmt = $pdo->query(
    'SELECT
        p.id,
        p.product_name,
        p.sku,
        p.category_id,
        p.supplier_id,
        p.cost_price,
        p.selling_price,
        p.quantity,
        p.low_stock_threshold,
        c.name AS category_name,
        s.name AS supplier_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN suppliers s ON p.supplier_id = s.id
     ORDER BY p.id DESC'
);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<?php
$pageTitle = 'Products';
$basePath = '../';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">

    <h2>Product Management</h2>
    <p>Add products, manage product details, and monitor your stock.</p>

    <?php if ($error !== ''): ?>
        <p class="error-message"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p><?= h($success) ?></p>
    <?php endif; ?>

    <section class="dashboard-card">
        <h3>Add New Product</h3>

        <form method="POST" action="index.php">

            <label for="product_name">Product name *</label>
            <input id="product_name" name="product_name"
                   maxlength="150" required>

            <label for="sku">SKU (unique product code) *</label>
            <input id="sku" name="sku" maxlength="100" required>

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= h($category['id']) ?>">
                        <?= h($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">-- Select Supplier --</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= h($supplier['id']) ?>">
                        <?= h($supplier['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="cost_price">Cost price</label>
            <input id="cost_price" name="cost_price"
                   type="number" min="0" step="0.01" value="0" required>

            <label for="selling_price">Selling price</label>
            <input id="selling_price" name="selling_price"
                   type="number" min="0" step="0.01" value="0" required>

            <label for="quantity">Initial quantity</label>
            <input id="quantity" name="quantity"
                   type="number" min="0" step="1" value="0" required>

            <label for="low_stock_threshold">Low-stock alert quantity</label>
            <input id="low_stock_threshold" name="low_stock_threshold"
                   type="number" min="0" step="1" value="5" required>

            <button type="submit">Add Product</button>
        </form>
    </section>

    <section style="margin-top: 28px; padding: 25px;">
        <h3>Products List</h3>
        <p>All products currently registered in StockPilot.</p>

        <div style="overflow-x: auto; margin-top: 18px;">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Cost</th>
                        <th>Selling Price</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$products): ?>
                        <tr>
                            <td colspan="8">No products added yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= h($product['product_name']) ?></td>
                                <td><?= h($product['sku']) ?></td>
                                <td><?= h($product['category_name'] ?? 'No category') ?></td>
                                <td><?= h($product['supplier_name'] ?? 'No supplier') ?></td>
                                <td><?= h($product['cost_price']) ?></td>
                                <td><?= h($product['selling_price']) ?></td>
                                <td>
                                    <?= h($product['quantity']) ?>
                                    <?php if ((int)$product['quantity'] <= (int)$product['low_stock_threshold']): ?>
                                        <span style="color: #dc2626; font-weight: 700;">
                                            Low stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?= (int)$product['id'] ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
