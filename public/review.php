<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$rating    = (int)($_POST['rating']     ?? 0);
$comment   = trim($_POST['comment']     ?? '');
$userId    = (int)$_SESSION['user_id'];

// Valida
if ($productId <= 0 || $rating < 1 || $rating > 5) {
    header('Location: /index.php');
    exit;
}

$pdo = db();

// Busca slug para redirecionar de volta
$stmt = $pdo->prepare("SELECT slug FROM products WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /index.php');
    exit;
}

// Verifica se já avaliou
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE product_id = :pid AND user_id = :uid");
$stmt->execute([':pid' => $productId, ':uid' => $userId]);

if ($stmt->fetch()) {
    // Atualiza a avaliação existente
    $pdo->prepare("UPDATE reviews SET rating = :r, comment = :c, created_at = NOW()
                   WHERE product_id = :pid AND user_id = :uid")
        ->execute([':r' => $rating, ':c' => $comment, ':pid' => $productId, ':uid' => $userId]);
} else {
    // Insere nova avaliação
    $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, created_at)
                   VALUES (:pid, :uid, :r, :c, NOW())")
        ->execute([':pid' => $productId, ':uid' => $userId, ':r' => $rating, ':c' => $comment]);
}

header('Location: /product.php?slug=' . urlencode($product['slug']) . '#tab-reviews');
exit;
