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
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $message = 'Supplier name is required.';
        $messageType = 'error';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO suppliers (name, phone, email, address)
             VALUES (:name, :phone, :email, :address)'
        );

        $stmt->execute([
            ':name' => $name,
            ':phone' => $phone ?: null,
            ':email' => $email ?: null,
            ':address' => $address ?: null
        ]);

        header('Location: index.php?added=1');
        exit;
    }
}

if (isset($_GET['added'])) {
    $message = 'Supplier added successfully!';
}

$suppliers = $pdo->query(
    'SELECT * FROM suppliers ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<?php
$pageTitle = 'Suppliers';
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">
    <h2>Supplier Management</h2>
    <p>Manage your product suppliers and contact information.</p>

    <?php if ($message): ?>
        <p class="<?= $messageType === 'error' ? 'error' : 'success' ?>">
            <?= e($message) ?>
        </p>
    <?php endif; ?>

    <section class="dashboard-card">
        <h3>Add New Supplier</h3>

        <form method="POST">
            <div class="form-group">
                <label for="name">Supplier Name *</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"></textarea>
            </div>

            <button type="submit">Add Supplier</button>
        </form>
    </section>

    <section class="dashboard-card">
        <h3>Supplier List (<?= count($suppliers) ?>)</h3>

        <?php if (empty($suppliers)): ?>
            <p>No suppliers found. Add your first supplier above.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?= e($supplier['id']) ?></td>
                                <td><?= e($supplier['name']) ?></td>
                                <td><?= e($supplier['phone']) ?></td>
                                <td><?= e($supplier['email']) ?></td>
                                <td><?= e($supplier['address']) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= (int)$supplier['id'] ?>">Edit</a>

                                    <form method="POST"
                                        action="delete.php?id=<?= (int)$supplier['id'] ?>"
                                        onsubmit="return confirm('Are you sure you want to delete this supplier?');"
                                        style="display:inline;">

                                        <button type="submit" class="delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
