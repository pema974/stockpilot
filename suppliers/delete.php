
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('Invalid supplier ID.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request method.');
}

$stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = :id');
$stmt->execute([':id' => $id]);

header('Location: index.php?deleted=1');
exit;
