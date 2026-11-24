<?php
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/models/OrderModel.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$pdo       = db();
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// ── Estatísticas ──────────────────────────────────────────────
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$totalOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue       = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'canceled'")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$lowStock      = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty <= 5 AND is_active = 1")->fetchColumn();

// Últimos 5 pedidos
$recentOrders = $pdo->query("
    SELECT o.id, o.total, o.status, o.created_at, u.name AS user_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetchAll();

// Produtos com estoque baixo
$lowStockProducts = $pdo->query("
    SELECT id, name, stock_qty FROM products
    WHERE stock_qty <= 5 AND is_active = 1
    ORDER BY stock_qty ASC
    LIMIT 5
")->fetchAll();

$statusLabels = [
    'pending'   => ['label' => 'Pendente',    'class' => 'badge-warning'],
    'paid'      => ['label' => 'Pago',         'class' => 'badge-info'],
    'shipped'   => ['label' => 'Enviado',       'class' => 'badge-primary'],
    'delivered' => ['label' => 'Entregue',      'class' => 'badge-success'],
    'canceled'  => ['label' => 'Cancelado',     'class' => 'badge-danger'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — Admin Loja Gamer</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="container-admin">

    <!-- Sidebar -->
    <aside class="sidebar-admin">
        <div class="sidebar-logo">Loja Gamer</div>
        <nav>
            <ul>
                <li><a href="<?= BASE_URL ?>/admin/index.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/admin/products.php"><i class="fa-solid fa-box"></i> Produtos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/banners.php"><i class="fa-solid fa-image"></i> Banners</a></li>
                <li><a href="<?= BASE_URL ?>/admin/users.php"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <li><a href="<?= BASE_URL ?>/admin/orders.php"><i class="fa-solid fa-bag-shopping"></i> Pedidos
                    <?php if ($pendingOrders > 0): ?>
                        <span class="sidebar-badge"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="<?= BASE_URL ?>/admin/blog.php"><i class="fa-solid fa-newspaper"></i> Blog</a></li>
                <li><a href="<?= BASE_URL ?>/admin/relatorios.php"><i class="fa-solid fa-chart-line"></i> Relatórios</a></li>
                <li><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Conteúdo -->
    <main class="content-admin">
        <div class="page-header">
            <h2><i class="fa-solid fa-gauge"></i> Dashboard</h2>
            <span class="welcome">Olá, <?= htmlspecialchars($adminName) ?>!</span>
        </div>

        <!-- Cards de estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fa-solid fa-bag-shopping"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $totalOrders ?></div>
                    <div class="stat-label">Total de Pedidos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-yellow"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $pendingOrders ?></div>
                    <div class="stat-label">Pedidos Pendentes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fa-solid fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <div class="stat-value">R$ <?= number_format($revenue, 2, ',', '.') ?></div>
                    <div class="stat-label">Receita Total</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple"><i class="fa-solid fa-box"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $totalProducts ?></div>
                    <div class="stat-label">Produtos Ativos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-indigo"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-label">Clientes</div>
                </div>
            </div>
            <div class="stat-card <?= $lowStock > 0 ? 'stat-alert' : '' ?>">
                <div class="stat-icon bg-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $lowStock ?></div>
                    <div class="stat-label">Estoque Crítico (≤5)</div>
                </div>
            </div>
        </div>

        <div class="dashboard-cols">

            <!-- Últimos pedidos -->
            <section class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Últimos Pedidos</h3>
                    <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-sm btn-secondary">Ver todos</a>
                </div>
                <?php if (empty($recentOrders)): ?>
                    <p class="empty-state">Nenhum pedido ainda.</p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Cliente</th><th>Total</th><th>Status</th><th>Data</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o):
                            $st = $statusLabels[$o['status']] ?? ['label' => $o['status'], 'class' => ''];
                        ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/admin/orders.php?action=view&id=<?= $o['id'] ?>">#<?= $o['id'] ?></a></td>
                            <td><?= htmlspecialchars($o['user_name'] ?? 'Visitante') ?></td>
                            <td>R$ <?= number_format($o['total'], 2, ',', '.') ?></td>
                            <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </section>

            <!-- Estoque crítico -->
            <section class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-triangle-exclamation"></i> Estoque Crítico</h3>
                    <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-sm btn-secondary">Produtos</a>
                </div>
                <?php if (empty($lowStockProducts)): ?>
                    <p class="empty-state">Todos os produtos com estoque adequado ✅</p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Produto</th><th>Estoque</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockProducts as $lp): ?>
                        <tr class="<?= $lp['stock_qty'] == 0 ? 'row-danger' : 'row-warning' ?>">
                            <td><?= htmlspecialchars($lp['name']) ?></td>
                            <td><?= $lp['stock_qty'] == 0 ? '<span class="badge badge-danger">Esgotado</span>' : $lp['stock_qty'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/products.php?action=edit&id=<?= $lp['id'] ?>"
                                   class="btn btn-sm btn-secondary">Editar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </section>

        </div>
    </main>
</div>
</body>
</html>
