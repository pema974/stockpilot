<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $message = 'Category name is required.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name, description)
                 VALUES (:name, :description)'
            );

            $stmt->execute([
                ':name' => $name,
                ':description' => $description
            ]);

            header('Location: index.php?added=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $message = 'This category already exists.';
            } else {
                error_log('Category creation failed: ' . $e->getMessage());
                $message = 'Could not add category. Please try again.';
            }

            $messageType = 'error';
        }
    }
}

if (isset($_GET['added'])) {
    $message = 'Category added successfully!';
}

$stmt = $pdo->query(
    'SELECT id, name, description, created_at
     FROM categories
     ORDER BY id DESC'
);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<?php
$pageTitle = 'Categories';
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">
    <h2>Category Management</h2>
    <p>Organize your products into categories.</p>

    <section class="dashboard-card">
        <h3>Add New Category</h3>

        <?php if ($message !== ''): ?>
            <p class="<?= escapeHtml($messageType) ?>">
                <?= escapeHtml($message) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="name">Category Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                ></textarea>
            </div>

            <button type="submit">Add Category</button>
        </form>
    </section>

    <section class="dashboard-card">
        <h3>All Categories (<?= count($categories) ?>)</h3>

        <?php if (empty($categories)): ?>
            <p>No categories yet. Add your first category above.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= escapeHtml($category['id']) ?></td>
                                <td><?= escapeHtml($category['name']) ?></td>
                                <td><?= escapeHtml($category['description']) ?></td>
                                <td><?= escapeHtml($category['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
