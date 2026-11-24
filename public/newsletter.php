<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método inválido.']);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok'=>false,'msg'=>'E-mail inválido.']);
    exit;
}

$pdo = db();
try {
    $pdo->prepare("INSERT IGNORE INTO newsletter (email) VALUES (:e)")
        ->execute([':e' => $email]);
    echo json_encode(['ok'=>true,'msg'=>'Cadastrado com sucesso! 🎉']);
} catch (\Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>'Erro ao cadastrar. Tente novamente.']);
}
