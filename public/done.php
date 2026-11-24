<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

// Redireciona se não houver pedido registrado
if (!isset($_SESSION['last_order_id'])) {
    header('Location: /index.php');
    exit;
}

$orderId  = (int)$_SESSION['last_order_id'];
$userName = $_SESSION['user_name'] ?? 'cliente';

// Limpa da sessão após ler
unset($_SESSION['last_order_id']);

$pageTitle = 'Pedido Confirmado — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/checkout_steps.php';
renderCheckoutSteps('done');
?>

<main class="done-page">
    <div class="done-icon">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    <h1>Pedido Confirmado!</h1>
    <p>Obrigado pela sua compra, <strong><?= htmlspecialchars($userName) ?></strong>!</p>
    <p>Seu pedido <strong>#<?= $orderId ?></strong> foi recebido e está sendo processado.</p>
    <p class="done-info">
        <i class="fa-solid fa-envelope"></i>
        Você receberá atualizações sobre a entrega em breve.
    </p>
    <div class="done-actions">
        <a href="/minha_conta.php" class="btn btn-secondary">
            <i class="fa-solid fa-bag-shopping"></i> Ver Meus Pedidos
        </a>
        <a href="/index.php" class="btn btn-primary">
            <i class="fa-solid fa-house"></i> Continuar Comprando
        </a>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
