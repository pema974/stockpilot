<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

// Get product ID from URL
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    header('Location: index.php');
    exit;
}

// Load categories
$categoryStmt = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name ASC'
);
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Load selected product
$stmt = $pdo->prepare(
    'SELECT * FROM products WHERE id = :id'
);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productName = trim($_POST['product_name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $costPrice = $_POST['cost_price'] ?? '';
    $sellingPrice = $_POST['selling_price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $lowStock = $_POST['low_stock_threshold'] ?? '';

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
                'UPDATE products SET
                    product_name = :name,
                    sku = :sku,
                    category_id = :category_id,
                    cost_price = :cost,
                    selling_price = :selling,
                    quantity = :quantity,
                    low_stock_threshold = :low_stock
                 WHERE id = :id'
            );

            $stmt->execute([
                'name' => $productName,
                'sku' => $sku,
                'category_id' => $categoryId === '' ? null : (int)$categoryId,
                'cost' => $costPrice,
                'selling' => $sellingPrice,
                'quantity' => (int)$quantity,
                'low_stock' => (int)$lowStock,
                'id' => $productId
            ]);

            header('Location: index.php?updated=1');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'That SKU already exists. Please use a unique SKU.';
            } else {
                error_log('Product update failed: ' . $e->getMessage());
                $error = 'Could not update product. Please try again.';
            }
        }
    }
}
?>

<?php
$pageTitle = 'Edit Product';
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">
    <h2>Edit Product</h2>
    <p>Update the product information below.</p>

    <section class="dashboard-card">
        <?php if ($error): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="product_name">Product Name</label>
                <input type="text" id="product_name" name="product_name"
                    value="<?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="sku">SKU</label>
                <input type="text" id="sku" name="sku"
                    value="<?= htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['id'] ?>"
                            <?= (string)$product['category_id'] === (string)$category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cost_price">Cost Price</label>
                <input type="number" id="cost_price" step="0.01" min="0" name="cost_price"
                    value="<?= htmlspecialchars((string)$product['cost_price'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="selling_price">Selling Price</label>
                <input type="number" id="selling_price" step="0.01" min="0" name="selling_price"
                    value="<?= htmlspecialchars((string)$product['selling_price'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity"
                    value="<?= (int)$product['quantity'] ?>" min="0" required>
            </div>

            <div class="form-group">
                <label for="low_stock_threshold">Low-stock Threshold</label>
                <input type="number" id="low_stock_threshold" name="low_stock_threshold"
                    value="<?= (int)$product['low_stock_threshold'] ?>" min="0" required>
            </div>

            <button type="submit">Update Product</button>
            <a href="index.php">Cancel</a>
        </form>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
