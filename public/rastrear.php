<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/OrderModel.php';

$order  = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)trim($_POST['order_id'] ?? 0);
    $email   = strtolower(trim($_POST['email'] ?? ''));

    if ($orderId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um número de pedido válido e o e-mail cadastrado.';
    } else {
        $pdo  = db();
        $stmt = $pdo->prepare("
            SELECT o.*, u.email AS user_email, u.name AS user_name
            FROM orders o
            JOIN users u ON u.id = o.user_id
            WHERE o.id = :id AND LOWER(u.email) = :email
        ");
        $stmt->execute([':id' => $orderId, ':email' => $email]);
        $row = $stmt->fetch();

        if ($row) {
            $order = OrderModel::getById($orderId);
            $order['user_email'] = $row['user_email'];
            $order['user_name']  = $row['user_name'];
        } else {
            $errors[] = 'Pedido não encontrado. Verifique o número e o e-mail informados.';
        }
    }
}

// Se logado, mostra pedidos do próprio usuário
$myOrders = [];
if (isset($_SESSION['user_id'])) {
    $myOrders = OrderModel::getByUser((int)$_SESSION['user_id']);
}

$timeline = [
    'pending'   => ['label'=>'Pedido Recebido',    'icon'=>'fa-file-invoice',    'desc'=>'Seu pedido foi registrado e está aguardando confirmação de pagamento.'],
    'paid'      => ['label'=>'Pagamento Confirmado','icon'=>'fa-circle-check',    'desc'=>'O pagamento foi aprovado. Estamos preparando seu pedido para envio.'],
    'shipped'   => ['label'=>'Pedido Enviado',      'icon'=>'fa-truck-fast',      'desc'=>'Seu pedido saiu para entrega. Acompanhe pelos Correios com o código enviado por e-mail.'],
    'delivered' => ['label'=>'Pedido Entregue',     'icon'=>'fa-box-open',        'desc'=>'Produto entregue com sucesso. Aproveite! 🎉'],
    'canceled'  => ['label'=>'Pedido Cancelado',    'icon'=>'fa-circle-xmark',    'desc'=>'Este pedido foi cancelado. Em caso de dúvidas entre em contato com o suporte.'],
];

$statusOrder = ['pending','paid','shipped','delivered'];

$pageTitle = 'Rastrear Pedido — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/rastrear.css">

<main class="rastrear-page">
  <div class="rastrear-inner">

    <h1><i class="fa-solid fa-truck-fast"></i> Rastrear Pedido</h1>
    <p class="rastrear-sub">Informe o número do pedido e o e-mail cadastrado para acompanhar sua entrega.</p>

    <!-- Formulário -->
    <form method="post" action="/rastrear.php" class="rastrear-form">
      <?php if (!empty($errors)): ?>
        <div class="rastrear-error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <?= htmlspecialchars($errors[0]) ?>
        </div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-field">
          <label>Número do Pedido</label>
          <input type="number" name="order_id" placeholder="Ex: 42" min="1" required
                 value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>">
        </div>
        <div class="form-field">
          <label>E-mail da compra</label>
          <input type="email" name="email" placeholder="seu@email.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-magnifying-glass"></i> Rastrear
      </button>
    </form>

    <!-- Resultado do rastreamento -->
    <?php if ($order): ?>
    <div class="rastrear-result">
      <div class="rastrear-result-header">
        <div>
          <h2>Pedido #<?= $order['id'] ?></h2>
          <span class="rastrear-date">Realizado em <?= date('d/m/Y \à\s H:i', strtotime($order['created_at'])) ?></span>
        </div>
        <div class="rastrear-total">
          R$ <?= number_format($order['total'], 2, ',', '.') ?>
        </div>
      </div>

      <!-- Timeline -->
      <?php
      $currentStatus = $order['status'];
      $isCanceled    = $currentStatus === 'canceled';
      $currentIdx    = array_search($currentStatus, $statusOrder);
      ?>
      <div class="timeline">
        <?php if ($isCanceled): ?>
          <div class="timeline-step canceled">
            <div class="step-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            <div class="step-body">
              <strong>Pedido Cancelado</strong>
              <p><?= $timeline['canceled']['desc'] ?></p>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($statusOrder as $idx => $sKey):
            $info    = $timeline[$sKey];
            $isDone  = $idx <= $currentIdx;
            $isNow   = $idx === $currentIdx;
          ?>
          <div class="timeline-step <?= $isDone ? 'done' : '' ?> <?= $isNow ? 'current' : '' ?>">
            <div class="step-icon"><i class="fa-solid <?= $info['icon'] ?>"></i></div>
            <div class="step-body">
              <strong><?= $info['label'] ?></strong>
              <?php if ($isDone): ?>
                <p><?= $info['desc'] ?></p>
              <?php else: ?>
                <p class="step-pending">Aguardando...</p>
              <?php endif; ?>
            </div>
            <?php if ($idx < count($statusOrder) - 1): ?>
              <div class="step-connector <?= $idx < $currentIdx ? 'done' : '' ?>"></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Itens do pedido -->
      <div class="rastrear-items">
        <h3>Itens do Pedido</h3>
        <?php foreach ($order['items'] as $item): ?>
        <div class="rastrear-item">
          <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['image_url'] ?? '') ?>"
               alt="<?= htmlspecialchars($item['product_name']) ?>">
          <div class="rastrear-item-info">
            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
            <span>Qtd: <?= $item['quantity'] ?> × R$ <?= number_format($item['unit_price'],2,',','.') ?></span>
          </div>
          <span class="rastrear-item-total">R$ <?= number_format($item['total_line'],2,',','.') ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Endereço -->
      <div class="rastrear-address">
        <i class="fa-solid fa-location-dot"></i>
        <div>
          <strong>Endereço de entrega</strong>
          <p><?= htmlspecialchars($order['shipping_address']) ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Pedidos do usuário logado -->
    <?php if (!empty($myOrders) && !$order): ?>
    <div class="my-orders-section">
      <h3><i class="fa-solid fa-bag-shopping"></i> Meus pedidos recentes</h3>
      <div class="my-orders-list">
        <?php
        $statusColors = ['pending'=>'#f59e0b','paid'=>'#3b82f6','shipped'=>'#8b5cf6','delivered'=>'#22c55e','canceled'=>'#ef4444'];
        foreach (array_slice($myOrders, 0, 5) as $o):
          $sc = $statusColors[$o['status']] ?? '#fff';
          $sl = $timeline[$o['status']]['label'] ?? $o['status'];
        ?>
        <div class="my-order-row">
          <div>
            <span class="my-order-id">#<?= $o['id'] ?></span>
            <span class="my-order-date"><?= date('d/m/Y', strtotime($o['created_at'])) ?></span>
          </div>
          <span class="status-pill" style="background:<?= $sc ?>22;color:<?= $sc ?>;border:1px solid <?= $sc ?>44">
            <?= $sl ?>
          </span>
          <span class="my-order-total">R$ <?= number_format($o['total'],2,',','.') ?></span>
          <a href="/minha_conta.php?tab=pedido&id=<?= $o['id'] ?>" class="btn btn-secondary btn-sm">
            Ver detalhes
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
