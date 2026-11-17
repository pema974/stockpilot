
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('Invalid supplier ID.');
}

$stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = :id');
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    exit('Supplier not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $error = 'Supplier name is required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $update = $pdo->prepare(
            'UPDATE suppliers
             SET name = :name,
                 phone = :phone,
                 email = :email,
                 address = :address
             WHERE id = :id'
        );

        $update->execute([
            ':name' => $name,
            ':phone' => $phone ?: null,
            ':email' => $email ?: null,
            ':address' => $address ?: null,
            ':id' => $id
        ]);

        header('Location: index.php?updated=1');
        exit;
    }
}
?>

<?php
$pageTitle = 'Edit Supplier';
$basePath = '../';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-container">
    <h2>Edit Supplier</h2>
    <p>Update supplier contact and business information.</p>

    <?php if ($error): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <section class="dashboard-card">
        <form method="POST">
            <div class="form-group">
                <label for="name">Supplier Name *</label>
                <input type="text" id="name" name="name"
                    value="<?= e($supplier['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone"
                    value="<?= e($supplier['phone']) ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                    value="<?= e($supplier['email']) ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?= e($supplier['address']) ?></textarea>
            </div>

            <button type="submit">Update Supplier</button>
            <a href="index.php">Cancel</a>
        </form>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
