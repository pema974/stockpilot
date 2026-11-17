
<?php
session_start();

require_once __DIR__ . '/config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $username === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters using letters, numbers, or underscores.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, username, email, password, role)
                 VALUES (:full_name, :username, :email, :password, :role)'
            );

            $stmt->execute([
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email !== '' ? $email : null,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'staff'
            ]);

            $success = 'Registration successful! You can now log in.';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'That username or email is already registered.';
            } else {
                error_log('Registration failed: ' . $e->getMessage());
                $error = 'Registration could not be completed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | StockPilot</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="login-container">
        <h1>StockPilot</h1>
        <p>Create your staff account.</p>

        <?php if ($error !== ''): ?>
            <p class="error-message">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
            <p><a href="login.php">Go to login</a></p>
        <?php else: ?>
            <form method="POST" action="register.php">
                <label for="full_name">Full name *</label>
                <input type="text" id="full_name" name="full_name"
                       maxlength="100" required autocomplete="name">

                <label for="username">Username *</label>
                <input type="text" id="username" name="username"
                       minlength="3" maxlength="50" required
                       pattern="[a-zA-Z0-9_]+"
                       autocomplete="username">

                <label for="email">Email (optional)</label>
                <input type="email" id="email" name="email"
                       maxlength="150" autocomplete="email">

                <label for="password">Password *</label>
                <input type="password" id="password" name="password"
                       minlength="10" required autocomplete="new-password">

                <label for="confirm_password">Confirm password *</label>
                <input type="password" id="confirm_password"
                       name="confirm_password" minlength="10"
                       required autocomplete="new-password">

                <button type="submit">Register</button>
            </form>

            <p><a href="login.php">Already have an account? Log in</a></p>
        <?php endif; ?>
    </main>
</body>
</html>
