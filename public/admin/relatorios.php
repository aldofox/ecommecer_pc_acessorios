<?php
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$pdo    = db();
$period = (int)($_GET['period'] ?? 30); // dias
$period = in_array($period, [7,30,90,365]) ? $period : 30;

// ── Vendas por dia ──────────────────────────────────────────
$salesByDay = $pdo->prepare("
    SELECT DATE(created_at) AS day,
           COUNT(*)          AS orders,
           SUM(total)        AS revenue
    FROM orders
    WHERE status != 'canceled'
      AND created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$salesByDay->bindValue(':p', $period, PDO::PARAM_INT);
$salesByDay->execute();
$salesData = $salesByDay->fetchAll();

// ── Produtos mais vendidos ──────────────────────────────────
$topProducts = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) AS qty, SUM(oi.total_line) AS revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'canceled'
      AND o.created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
    GROUP BY oi.product_id
    ORDER BY qty DESC
    LIMIT 8
");
$topProducts->bindValue(':p', $period, PDO::PARAM_INT);
$topProducts->execute();
$topProds = $topProducts->fetchAll();

// ── Pedidos por status ──────────────────────────────────────
$byStatus = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM orders GROUP BY status
")->fetchAll();

// ── Receita por categoria ───────────────────────────────────
$revByCat = $pdo->prepare("
    SELECT c.name, SUM(oi.total_line) AS revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN categories c ON c.id = p.category_id
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'canceled'
      AND o.created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
    GROUP BY c.id
    ORDER BY revenue DESC
");
$revByCat->bindValue(':p', $period, PDO::PARAM_INT);
$revByCat->execute();
$catRevenue = $revByCat->fetchAll();

// ── KPIs do período ─────────────────────────────────────────
$kpi = $pdo->prepare("
    SELECT COUNT(*) AS orders,
           COALESCE(SUM(total),0) AS revenue,
           COALESCE(AVG(total),0) AS avg_ticket,
           COUNT(DISTINCT user_id) AS unique_customers
    FROM orders
    WHERE status != 'canceled'
      AND created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
");
$kpi->bindValue(':p', $period, PDO::PARAM_INT);
$kpi->execute();
$kpis = $kpi->fetch();

// ── Exportar CSV ────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $pdo->prepare("
        SELECT o.id, u.name AS cliente, u.email, o.total, o.status,
               o.payment_method, DATE(o.created_at) AS data
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
        ORDER BY o.created_at DESC
    ");
    $stmt->bindValue(':p', $period, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pedidos_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['#', 'Cliente', 'E-mail', 'Total (R$)', 'Status', 'Pagamento', 'Data'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'], $r['cliente'], $r['email'],
            number_format($r['total'],2,',','.'), $r['status'],
            $r['payment_method'], $r['data']], ';');
    }
    fclose($out);
    exit;
}

// ── JSON para gráficos ──────────────────────────────────────
$chartDays    = array_column($salesData, 'day');
$chartRevenue = array_map(fn($r) => round((float)$r['revenue'], 2), $salesData);
$chartOrders  = array_column($salesData, 'orders');
$statusColors = ['pending'=>'#f59e0b','paid'=>'#3b82f6','shipped'=>'#8b5cf6','delivered'=>'#22c55e','canceled'=>'#ef4444'];

$sidebar = [
    ['url'=>'index.php',    'icon'=>'fa-gauge',        'label'=>'Dashboard'],
    ['url'=>'products.php', 'icon'=>'fa-box',          'label'=>'Produtos'],
    ['url'=>'banners.php',  'icon'=>'fa-image',        'label'=>'Banners'],
    ['url'=>'users.php',    'icon'=>'fa-users',        'label'=>'Usuários'],
    ['url'=>'orders.php',   'icon'=>'fa-bag-shopping', 'label'=>'Pedidos'],
    ['url'=>'blog.php',     'icon'=>'fa-newspaper',    'label'=>'Blog'],
    ['url'=>'relatorios.php','icon'=>'fa-chart-line',  'label'=>'Relatórios', 'active'=>true],
    ['url'=>'logout.php',   'icon'=>'fa-right-from-bracket','label'=>'Sair'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatórios — Admin Loja Gamer</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="container-admin">

    <aside class="sidebar-admin">
        <div class="sidebar-logo">Loja Gamer</div>
        <nav><ul>
            <?php foreach ($sidebar as $s): ?>
            <li>
                <a href="<?= BASE_URL ?>/admin/<?= $s['url'] ?>" <?= !empty($s['active']) ? 'class="active"' : '' ?>>
                    <i class="fa-solid <?= $s['icon'] ?>"></i> <?= $s['label'] ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul></nav>
    </aside>

    <main class="content-admin">

        <!-- Header com filtro de período -->
        <div class="page-header">
            <h2><i class="fa-solid fa-chart-line"></i> Relatórios</h2>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <?php foreach ([7=>'7 dias',30=>'30 dias',90=>'90 dias',365=>'1 ano'] as $d => $l): ?>
                    <a href="?period=<?= $d ?>"
                       class="btn btn-sm <?= $period===$d ? 'btn-primary' : 'btn-secondary' ?>">
                        <?= $l ?>
                    </a>
                <?php endforeach; ?>
                <a href="?period=<?= $period ?>&export=csv" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-file-csv"></i> Exportar CSV
                </a>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="stats-grid" style="margin-bottom:24px">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fa-solid fa-bag-shopping"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($kpis['orders']) ?></div>
                    <div class="stat-label">Pedidos no período</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fa-solid fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <div class="stat-value">R$ <?= number_format($kpis['revenue'],2,',','.') ?></div>
                    <div class="stat-label">Receita no período</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple"><i class="fa-solid fa-receipt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">R$ <?= number_format($kpis['avg_ticket'],2,',','.') ?></div>
                    <div class="stat-label">Ticket médio</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-indigo"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($kpis['unique_customers']) ?></div>
                    <div class="stat-label">Clientes únicos</div>
                </div>
            </div>
        </div>

        <!-- Gráfico de linha — Receita diária -->
        <div class="dashboard-cols" style="margin-bottom:24px">
            <section class="dashboard-card" style="grid-column:1/-1">
                <div class="card-header">
                    <h3><i class="fa-solid fa-chart-line"></i> Receita Diária</h3>
                </div>
                <div style="padding:20px;height:280px">
                    <canvas id="chartRevenue"></canvas>
                </div>
            </section>
        </div>

        <div class="dashboard-cols">

            <!-- Produtos mais vendidos -->
            <section class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-trophy"></i> Mais Vendidos</h3>
                </div>
                <div style="padding:16px 20px">
                <?php if (empty($topProds)): ?>
                    <p class="empty-state">Sem dados no período.</p>
                <?php else: ?>
                    <?php
                    $maxQty = max(array_column($topProds,'qty'));
                    foreach ($topProds as $tp):
                        $pct = $maxQty > 0 ? round($tp['qty'] / $maxQty * 100) : 0;
                    ?>
                    <div style="margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:4px">
                            <span style="color:#f0eeff;font-weight:600"><?= htmlspecialchars(mb_strimwidth($tp['name'],0,32,'…')) ?></span>
                            <span style="color:#5c5880"><?= $tp['qty'] ?> un.</span>
                        </div>
                        <div style="background:#1e1c2f;border-radius:99px;height:6px;overflow:hidden">
                            <div style="height:100%;background:linear-gradient(90deg,#6c63ff,#00e5ff);border-radius:99px;width:<?= $pct ?>%;transition:width .5s ease"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </section>

            <!-- Gráfico de donut — Pedidos por status -->
            <section class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-chart-pie"></i> Pedidos por Status</h3>
                </div>
                <div style="padding:20px;height:240px;display:flex;align-items:center;justify-content:center">
                    <?php if (empty($byStatus)): ?>
                        <p class="empty-state">Sem pedidos.</p>
                    <?php else: ?>
                        <canvas id="chartStatus"></canvas>
                    <?php endif; ?>
                </div>
            </section>

        </div>

        <!-- Receita por categoria -->
        <?php if (!empty($catRevenue)): ?>
        <section class="dashboard-card" style="margin-top:24px">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-bar"></i> Receita por Categoria</h3>
            </div>
            <div style="padding:20px;height:240px">
                <canvas id="chartCategories"></canvas>
            </div>
        </section>
        <?php endif; ?>

    </main>
</div>

<script>
Chart.defaults.color = '#a09cc0';
Chart.defaults.borderColor = '#252338';
Chart.defaults.font.family = "'Segoe UI', sans-serif";
Chart.defaults.font.size = 11;

const days    = <?= json_encode($chartDays) ?>;
const revenue = <?= json_encode($chartRevenue) ?>;
const orders  = <?= json_encode($chartOrders) ?>;

// ── Gráfico de receita diária ──────────────────────────────
if (days.length && document.getElementById('chartRevenue')) {
    new Chart(document.getElementById('chartRevenue'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [
                {
                    label: 'Receita (R$)',
                    data: revenue,
                    borderColor: '#6c63ff',
                    backgroundColor: 'rgba(108,99,255,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    yAxisID: 'y',
                },
                {
                    label: 'Pedidos',
                    data: orders,
                    borderColor: '#00e5ff',
                    backgroundColor: 'transparent',
                    borderDash: [5,3],
                    tension: 0.4,
                    pointRadius: 3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.datasetIndex === 0
                            ? ' R$ ' + ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2})
                            : ' ' + ctx.raw + ' pedidos'
                    }
                }
            },
            scales: {
                y:  { position:'left',  ticks: { callback: v => 'R$' + v.toLocaleString('pt-BR') } },
                y1: { position:'right', grid: { drawOnChartArea:false }, ticks: { stepSize:1 } },
            }
        }
    });
}

// ── Gráfico de donut — status ──────────────────────────────
<?php if (!empty($byStatus)): ?>
const statusLabels = <?= json_encode(array_column($byStatus,'status')) ?>;
const statusTotals = <?= json_encode(array_map(fn($r)=>(int)$r['total'],$byStatus)) ?>;
const statusColors = <?= json_encode(array_map(fn($r)=>$statusColors[$r['status']]??'#6c63ff',$byStatus)) ?>;

if (document.getElementById('chartStatus')) {
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusTotals,
                backgroundColor: statusColors,
                borderColor: '#13121f',
                borderWidth: 3,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position:'right' } }
        }
    });
}
<?php endif; ?>

// ── Gráfico de barras — categorias ────────────────────────
<?php if (!empty($catRevenue)): ?>
const catNames   = <?= json_encode(array_column($catRevenue,'name')) ?>;
const catRevs    = <?= json_encode(array_map(fn($r)=>round((float)$r['revenue'],2),$catRevenue)) ?>;

if (document.getElementById('chartCategories')) {
    new Chart(document.getElementById('chartCategories'), {
        type: 'bar',
        data: {
            labels: catNames,
            datasets: [{
                label: 'Receita (R$)',
                data: catRevs,
                backgroundColor: 'rgba(108,99,255,0.7)',
                borderColor: '#6c63ff',
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display:false } },
            scales: {
                y: { ticks: { callback: v => 'R$'+v.toLocaleString('pt-BR') } }
            }
        }
    });
}
<?php endif; ?>
</script>
</body>
</html>
