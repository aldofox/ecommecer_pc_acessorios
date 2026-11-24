<?php
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/models/OrderModel.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$msg    = '';

// ── Atualizar status ──────────────────────────────────────────────────────────
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId   = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status'] ?? '');
    try {
        OrderModel::updateStatus($orderId, $newStatus);
        header('Location: ' . BASE_URL . '/admin/orders.php?action=view&id=' . $orderId . '&msg=updated');
        exit;
    } catch (\Throwable $e) {
        $msg = 'Erro ao atualizar status: ' . $e->getMessage();
    }
}

// ── Ver detalhes de um pedido ─────────────────────────────────────────────────
$order = null;
if ($action === 'view' && isset($_GET['id'])) {
    $order = OrderModel::getById((int)$_GET['id']);
    if (!$order) {
        header('Location: ' . BASE_URL . '/admin/orders.php');
        exit;
    }
    if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
        $msg = 'Status atualizado!';
    }
}

// ── Filtro de status na listagem ──────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$pdo          = db();

if ($filterStatus && $action === 'list') {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS user_name
        FROM orders o LEFT JOIN users u ON u.id = o.user_id
        WHERE o.status = :s
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([':s' => $filterStatus]);
    $orders = $stmt->fetchAll();
} else {
    $orders = OrderModel::getAll(100);
}

$statusLabels = [
    'pending'   => ['label' => 'Pendente',  'class' => 'badge-warning'],
    'paid'      => ['label' => 'Pago',       'class' => 'badge-info'],
    'shipped'   => ['label' => 'Enviado',    'class' => 'badge-primary'],
    'delivered' => ['label' => 'Entregue',   'class' => 'badge-success'],
    'canceled'  => ['label' => 'Cancelado',  'class' => 'badge-danger'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedidos — Admin Loja Gamer</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="container-admin">

    <aside class="sidebar-admin">
        <div class="sidebar-logo">Loja Gamer</div>
        <nav>
            <ul>
                <li><a href="<?= BASE_URL ?>/admin/index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/admin/products.php"><i class="fa-solid fa-box"></i> Produtos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/banners.php"><i class="fa-solid fa-image"></i> Banners</a></li>
                <li><a href="<?= BASE_URL ?>/admin/users.php"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <li><a href="<?= BASE_URL ?>/admin/orders.php" class="active"><i class="fa-solid fa-bag-shopping"></i> Pedidos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="content-admin">

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($action === 'view' && $order): ?>
        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- Detalhes do Pedido                                      -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div class="page-header">
            <h2><i class="fa-solid fa-receipt"></i> Pedido #<?= $order['id'] ?></h2>
            <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="order-detail-grid">

            <!-- Informações gerais -->
            <section class="order-info-card">
                <h3>Informações</h3>
                <table class="info-table">
                    <tr><th>Pedido</th><td>#<?= $order['id'] ?></td></tr>
                    <tr><th>Cliente</th><td><?= htmlspecialchars($order['user_name'] ?? 'Visitante') ?></td></tr>
                    <tr><th>E-mail</th><td><?= htmlspecialchars($order['user_email'] ?? '—') ?></td></tr>
                    <tr><th>Data</th><td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td></tr>
                    <tr><th>Pagamento</th><td><?= htmlspecialchars($order['payment_method']) ?></td></tr>
                    <tr><th>Status</th>
                        <td>
                            <?php $st = $statusLabels[$order['status']] ?? ['label'=>$order['status'],'class'=>'']; ?>
                            <span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                        </td>
                    </tr>
                </table>
            </section>

            <!-- Endereço de entrega -->
            <section class="order-info-card">
                <h3>Endereço de Entrega</h3>
                <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            </section>

            <!-- Alterar status -->
            <section class="order-info-card">
                <h3>Alterar Status</h3>
                <form method="post" action="<?= BASE_URL ?>/admin/orders.php?action=update_status">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <select name="status" class="input-select">
                        <?php foreach ($statusLabels as $sKey => $sData): ?>
                            <option value="<?= $sKey ?>" <?= $order['status'] === $sKey ? 'selected' : '' ?>>
                                <?= $sData['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary" style="margin-top:10px">
                        <i class="fa-solid fa-floppy-disk"></i> Salvar
                    </button>
                </form>
            </section>

            <!-- Totais -->
            <section class="order-info-card">
                <h3>Valores</h3>
                <table class="info-table">
                    <tr><th>Subtotal</th><td>R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></td></tr>
                    <tr><th>Frete</th>   <td>R$ <?= number_format($order['shipping'], 2, ',', '.') ?></td></tr>
                    <tr class="row-total"><th>Total</th><td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td></tr>
                </table>
            </section>
        </div>

        <!-- Itens do pedido -->
        <section class="dashboard-card" style="margin-top:24px">
            <div class="card-header"><h3>Itens do Pedido</h3></div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Imagem</th>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Preço Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['image_url'] ?? '') ?>"
                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                 style="max-height:50px; border-radius:4px;">
                        </td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($item['total_line'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <?php else: ?>
        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- Listagem de Pedidos                                     -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div class="page-header">
            <h2><i class="fa-solid fa-bag-shopping"></i> Pedidos</h2>
        </div>

        <!-- Filtro por status -->
        <div class="filter-bar">
            <a href="<?= BASE_URL ?>/admin/orders.php"
               class="btn btn-sm <?= $filterStatus === '' ? 'btn-primary' : 'btn-secondary' ?>">Todos</a>
            <?php foreach ($statusLabels as $sKey => $sData): ?>
                <a href="<?= BASE_URL ?>/admin/orders.php?status=<?= $sKey ?>"
                   class="btn btn-sm <?= $filterStatus === $sKey ? 'btn-primary' : 'btn-secondary' ?>">
                    <?= $sData['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($orders)): ?>
            <p class="empty-state">Nenhum pedido encontrado.</p>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o):
                    $st = $statusLabels[$o['status']] ?? ['label' => $o['status'], 'class' => ''];
                ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td><?= htmlspecialchars($o['user_name'] ?? 'Visitante') ?></td>
                    <td>R$ <?= number_format($o['total'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($o['payment_method']) ?></td>
                    <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/orders.php?action=view&id=<?= $o['id'] ?>"
                           class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; ?>

    </main>
</div>
</body>
</html>
