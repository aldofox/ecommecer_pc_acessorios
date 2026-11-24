<?php
require_once __DIR__ . '/../includes/db.php';

session_start();

// Só funciona para usuários logados
if (!isset($_SESSION['user_id'])) {
    // Redireciona para login preservando a página anterior
    $ref = $_SERVER['HTTP_REFERER'] ?? '/index.php';
    header('Location: /login.php?redirect=' . urlencode($ref));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['product_id'])) {
    header('Location: /index.php');
    exit;
}

$userId    = (int)$_SESSION['user_id'];
$productId = (int)$_POST['product_id'];
$pdo       = db();

// Verifica se já é favorito
$stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = :uid AND product_id = :pid");
$stmt->execute([':uid' => $userId, ':pid' => $productId]);
$exists = $stmt->fetchColumn();

if ($exists) {
    // Remove dos favoritos (toggle)
    $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid")
        ->execute([':uid' => $userId, ':pid' => $productId]);
    $action = 'removed';
} else {
    // Adiciona aos favoritos
    $pdo->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (:uid, :pid)")
        ->execute([':uid' => $userId, ':pid' => $productId]);
    $action = 'added';
}

// Redireciona de volta para o produto
$ref = $_SERVER['HTTP_REFERER'] ?? '/index.php';
header('Location: ' . $ref);
exit;
