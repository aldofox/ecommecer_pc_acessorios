<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/OrderModel.php';

// Exige login
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=/minha_conta.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$pdo    = db();
$tab    = $_GET['tab'] ?? 'pedidos';
$msg    = '';

// ── Atualizar dados pessoais ──────────────────────────────────────────
if ($tab === 'dados' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $errors = [];

    if (strlen($name) < 3)
        $errors[] = 'Nome deve ter ao menos 3 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'E-mail inválido.';

    // Verifica e-mail duplicado (exceto o próprio)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
    $stmt->execute([':email' => $email, ':id' => $userId]);
    if ($stmt->fetch()) $errors[] = 'Este e-mail já está em uso por outra conta.';

    if (empty($errors)) {
        // Atualiza senha só se preenchida
        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) < 6) {
                $errors[] = 'A nova senha deve ter ao menos 6 caracteres.';
            } elseif ($_POST['new_password'] !== ($_POST['confirm_password'] ?? '')) {
                $errors[] = 'As senhas não coincidem.';
            } else {
                $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET name=:n, email=:e, password_hash=:h WHERE id=:id")
                    ->execute([':n'=>$name,':e'=>$email,':h'=>$hash,':id'=>$userId]);
            }
        } else {
            $pdo->prepare("UPDATE users SET name=:n, email=:e WHERE id=:id")
                ->execute([':n'=>$name,':e'=>$email,':id'=>$userId]);
        }

        if (empty($errors)) {
            $_SESSION['user_name'] = $name;
            $msg = 'Dados atualizados com sucesso!';
        }
    }
}

// ── Busca dados do usuário ────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

// ── Busca pedidos do usuário ──────────────────────────────────────────
$orders = OrderModel::getByUser($userId);

// ── Pedido individual ─────────────────────────────────────────────────
$orderDetail = null;
if ($tab === 'pedido' && isset($_GET['id'])) {
    $orderDetail = OrderModel::getById((int)$_GET['id']);
    // Garante que o pedido pertence ao usuário
    if ($orderDetail && (int)$orderDetail['user_id'] !== $userId) {
        $orderDetail = null;
    }
}

$statusLabels = [
    'pending'   => ['label' => 'Pendente',   'color' => '#f59e0b'],
    'paid'      => ['label' => 'Pago',        'color' => '#3b82f6'],
    'shipped'   => ['label' => 'Enviado',     'color' => '#8b5cf6'],
    'delivered' => ['label' => 'Entregue',    'color' => '#22c55e'],
    'canceled'  => ['label' => 'Cancelado',   'color' => '#ef4444'],
];

$pageTitle = 'Minha Conta — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="account-page">
    <div class="account-layout">

        <!-- ── Sidebar da conta ───────────────── -->
        <aside class="account-sidebar">
            <div class="account-avatar">
                <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
            </div>
            <div class="account-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="account-email"><?= htmlspecialchars($user['email']) ?></div>
            <div class="account-since">
                Membro desde <?= date('M/Y', strtotime($user['created_at'])) ?>
            </div>

            <nav class="account-nav">
                <a href="/minha_conta.php?tab=pedidos"
                   class="<?= $tab === 'pedidos' || $tab === 'pedido' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bag-shopping"></i> Meus Pedidos
                    <?php if (!empty($orders)): ?>
                        <span class="nav-count"><?= count($orders) ?></span>
                    <?php endif; ?>
                </a>
                <a href="/minha_conta.php?tab=dados"
                   class="<?= $tab === 'dados' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-pen"></i> Meus Dados
                </a>
                <a href="/minha_conta.php?tab=favoritos"
                   class="<?= $tab === 'favoritos' ? 'active' : '' ?>">
                    <i class="fa-solid fa-heart"></i> Favoritos
                </a>
                <a href="/logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </a>
            </nav>
        </aside>

        <!-- ── Conteúdo ───────────────────────── -->
        <div class="account-content">

            <?php if ($msg): ?>
                <div class="account-alert account-alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <!-- ═══ ABA: PEDIDO INDIVIDUAL ══════ -->
            <?php if ($tab === 'pedido' && $orderDetail): ?>

                <div class="account-header">
                    <h1><i class="fa-solid fa-receipt"></i> Pedido #<?= $orderDetail['id'] ?></h1>
                    <a href="/minha_conta.php?tab=pedidos" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>

                <?php $st = $statusLabels[$orderDetail['status']] ?? ['label'=>$orderDetail['status'],'color'=>'#fff']; ?>

                <div class="order-detail-cards">
                    <div class="order-info-card">
                        <h3>Informações</h3>
                        <table class="order-info-table">
                            <tr><th>Pedido</th>    <td>#<?= $orderDetail['id'] ?></td></tr>
                            <tr><th>Data</th>       <td><?= date('d/m/Y H:i', strtotime($orderDetail['created_at'])) ?></td></tr>
                            <tr><th>Pagamento</th>  <td><?= htmlspecialchars($orderDetail['payment_method']) ?></td></tr>
                            <tr><th>Status</th>
                                <td><span class="status-pill" style="background:<?= $st['color'] ?>22;color:<?= $st['color'] ?>;border-color:<?= $st['color'] ?>44">
                                    <?= $st['label'] ?>
                                </span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="order-info-card">
                        <h3>Endereço de Entrega</h3>
                        <p style="color:#a09cc0;font-size:.88rem;line-height:1.7">
                            <?= nl2br(htmlspecialchars($orderDetail['shipping_address'])) ?>
                        </p>
                    </div>
                    <div class="order-info-card">
                        <h3>Valores</h3>
                        <table class="order-info-table">
                            <tr><th>Subtotal</th><td>R$ <?= number_format($orderDetail['subtotal'],2,',','.') ?></td></tr>
                            <tr><th>Frete</th>   <td><?= $orderDetail['shipping'] == 0 ? '<span style="color:#22c55e">Grátis</span>' : 'R$ '.number_format($orderDetail['shipping'],2,',','.') ?></td></tr>
                            <tr style="font-weight:700"><th>Total</th><td style="color:#00e5ff">R$ <?= number_format($orderDetail['total'],2,',','.') ?></td></tr>
                        </table>
                    </div>
                </div>

                <div class="order-items-card">
                    <h3>Itens do Pedido</h3>
                    <div class="order-items-list">
                        <?php foreach ($orderDetail['items'] as $item): ?>
                        <div class="order-item-row">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['image_url'] ?? '') ?>"
                                 alt="<?= htmlspecialchars($item['product_name']) ?>">
                            <div class="order-item-info">
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                <span>Qtd: <?= $item['quantity'] ?> × R$ <?= number_format($item['unit_price'],2,',','.') ?></span>
                            </div>
                            <div class="order-item-total">
                                R$ <?= number_format($item['total_line'],2,',','.') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <!-- ═══ ABA: MEUS PEDIDOS ════════════ -->
            <?php elseif ($tab === 'pedidos' || $tab === 'pedido'): ?>

                <div class="account-header">
                    <h1><i class="fa-solid fa-bag-shopping"></i> Meus Pedidos</h1>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="account-empty">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <p>Você ainda não fez nenhum pedido.</p>
                        <a href="/index.php" class="btn btn-primary">Explorar Produtos</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $o):
                            $st = $statusLabels[$o['status']] ?? ['label'=>$o['status'],'color'=>'#fff'];
                        ?>
                        <a href="/minha_conta.php?tab=pedido&id=<?= $o['id'] ?>" class="order-card">
                            <div class="order-card-left">
                                <div class="order-card-id">#<?= $o['id'] ?></div>
                                <div class="order-card-date">
                                    <?= date('d/m/Y', strtotime($o['created_at'])) ?>
                                </div>
                            </div>
                            <div class="order-card-mid">
                                <span class="status-pill" style="background:<?= $st['color'] ?>22;color:<?= $st['color'] ?>;border-color:<?= $st['color'] ?>44">
                                    <?= $st['label'] ?>
                                </span>
                                <span class="order-card-pay"><?= htmlspecialchars($o['payment_method']) ?></span>
                            </div>
                            <div class="order-card-total">
                                R$ <?= number_format($o['total'],2,',','.') ?>
                            </div>
                            <i class="fa-solid fa-chevron-right order-card-arrow"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- ═══ ABA: MEUS DADOS ═════════════ -->
            <?php elseif ($tab === 'dados'): ?>

                <div class="account-header">
                    <h1><i class="fa-solid fa-user-pen"></i> Meus Dados</h1>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="account-alert account-alert-error">
                        <?php foreach ($errors as $e): ?>
                            <p><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/minha_conta.php?tab=dados" class="account-form">
                    <div class="form-section">
                        <h3>Informações Pessoais</h3>
                        <div class="field-group">
                            <label>Nome Completo</label>
                            <input type="text" name="name" required minlength="3"
                                   value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>">
                        </div>
                        <div class="field-group">
                            <label>E-mail</label>
                            <input type="email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Alterar Senha <small>(deixe em branco para não alterar)</small></h3>
                        <div class="field-group">
                            <label>Nova Senha</label>
                            <input type="password" name="new_password" minlength="6"
                                   placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="field-group">
                            <label>Confirmar Nova Senha</label>
                            <input type="password" name="confirm_password" minlength="6"
                                   placeholder="Repita a nova senha">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
                    </button>
                </form>

            <!-- ═══ ABA: FAVORITOS ══════════════ -->
            <?php elseif ($tab === 'favoritos'): ?>

                <div class="account-header">
                    <h1><i class="fa-solid fa-heart"></i> Meus Favoritos</h1>
                </div>

                <?php
                $stmtF = $pdo->prepare("
                    SELECT p.id, p.slug, p.name, p.image_url, p.unit_price, p.compare_price,
                           COALESCE(AVG(r.rating),0) AS rating_avg, COUNT(r.id) AS rating_count
                    FROM favorites f
                    JOIN products p ON p.id = f.product_id
                    LEFT JOIN reviews r ON r.product_id = p.id
                    WHERE f.user_id = :uid AND p.is_active = 1
                    GROUP BY p.id
                    ORDER BY f.created_at DESC
                ");
                $stmtF->execute([':uid' => $userId]);
                $favorites = $stmtF->fetchAll();
                ?>

                <?php if (empty($favorites)): ?>
                    <div class="account-empty">
                        <i class="fa-regular fa-heart"></i>
                        <p>Você ainda não tem produtos favoritos.</p>
                        <a href="/index.php" class="btn btn-primary">Explorar Produtos</a>
                    </div>
                <?php else: ?>
                    <div class="favorites-grid">
                        <?php foreach ($favorites as $fav):
                            $fStars   = min(5, round((float)$fav['rating_avg']));
                            $fHasDisc = !empty($fav['compare_price']) && $fav['compare_price'] > $fav['unit_price'];
                        ?>
                        <div class="fav-card">
                            <a href="/product.php?slug=<?= htmlspecialchars($fav['slug']) ?>" class="fav-img">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($fav['image_url']) ?>"
                                     alt="<?= htmlspecialchars($fav['name']) ?>" loading="lazy">
                            </a>
                            <div class="fav-body">
                                <a href="/product.php?slug=<?= htmlspecialchars($fav['slug']) ?>" class="fav-name">
                                    <?= htmlspecialchars($fav['name']) ?>
                                </a>
                                <div class="fav-stars">
                                    <?php for ($i=1;$i<=5;$i++): ?>
                                        <i class="fa-<?= $i<=$fStars?'solid':'regular' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($fHasDisc): ?>
                                    <div class="fav-compare">R$ <?= number_format($fav['compare_price'],2,',','.') ?></div>
                                <?php endif; ?>
                                <div class="fav-price">R$ <?= number_format($fav['unit_price'],2,',','.') ?></div>
                                <div class="fav-actions">
                                    <form action="/cart.php?action=add" method="post">
                                        <input type="hidden" name="product_id" value="<?= $fav['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-cart-plus"></i> Adicionar
                                        </button>
                                    </form>
                                    <form action="/favorite.php" method="post">
                                        <input type="hidden" name="product_id" value="<?= $fav['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Remover dos favoritos">
                                            <i class="fa-solid fa-heart-crack"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div><!-- /account-content -->
    </div><!-- /account-layout -->
</main>

<style>
/* ── Layout ────────────────────────────────── */
.account-page {
    max-width: 1100px;
    margin: 28px auto;
    padding: 0 20px 60px;
}
.account-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 24px;
    align-items: start;
}
@media(max-width:768px){
    .account-layout { grid-template-columns: 1fr; }
}

/* ── Sidebar ───────────────────────────────── */
.account-sidebar {
    background: #13121f;
    border: 1px solid #252338;
    border-radius: 16px;
    padding: 24px;
    position: sticky;
    top: 90px;
    text-align: center;
}
.account-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6c63ff, #00e5ff);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
    margin: 0 auto 12px;
}
.account-name  { font-size: 1rem; font-weight: 700; color: #f0eeff; }
.account-email { font-size: .78rem; color: #5c5880; margin: 3px 0 8px; }
.account-since { font-size: .72rem; color: #2e2c45; margin-bottom: 20px; }
.account-nav { display: flex; flex-direction: column; gap: 4px; text-align: left; }
.account-nav a {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 12px;
    border-radius: 8px;
    color: #5c5880;
    text-decoration: none;
    font-size: .88rem;
    font-weight: 500;
    transition: background .2s, color .2s;
}
.account-nav a i { font-size: .85rem; width: 16px; }
.account-nav a:hover { background: rgba(108,99,255,.08); color: #a09cc0; }
.account-nav a.active { background: rgba(108,99,255,.12); color: #6c63ff; font-weight: 700; }
.account-nav .logout-link:hover { background: rgba(239,68,68,.08); color: #ef4444; }
.nav-count {
    margin-left: auto;
    background: #6c63ff;
    color: #fff;
    font-size: .68rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 99px;
}

/* ── Conteúdo ──────────────────────────────── */
.account-content { min-width: 0; }
.account-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #252338;
}
.account-header h1 {
    font-size: 1.3rem;
    color: #f0eeff;
    display: flex;
    align-items: center;
    gap: 10px;
}
.account-header h1 i { color: #6c63ff; }

/* Alertas */
.account-alert {
    border-radius: 8px;
    padding: 12px 16px;
    font-size: .88rem;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.account-alert-success { background: rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.25); color: #22c55e; }
.account-alert-error   { background: rgba(239,68,68,.1);  border:1px solid rgba(239,68,68,.25);  color: #f87171; flex-direction:column; align-items:flex-start; }

/* Status pill */
.status-pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: .75rem;
    font-weight: 700;
    border: 1px solid;
}

/* ── Lista de pedidos ──────────────────────── */
.orders-list { display: flex; flex-direction: column; gap: 8px; }
.order-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #13121f;
    border: 1px solid #252338;
    border-radius: 10px;
    padding: 14px 18px;
    text-decoration: none;
    color: inherit;
    transition: border-color .2s, transform .1s;
}
.order-card:hover { border-color: #6c63ff; transform: translateX(2px); }
.order-card-left { min-width: 70px; }
.order-card-id   { font-size: .95rem; font-weight: 700; color: #f0eeff; }
.order-card-date { font-size: .75rem; color: #5c5880; }
.order-card-mid  { flex: 1; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.order-card-pay  { font-size: .75rem; color: #5c5880; }
.order-card-total { font-size: 1rem; font-weight: 700; color: #00e5ff; white-space: nowrap; }
.order-card-arrow { color: #2e2c45; font-size: .8rem; }

/* ── Detalhe do pedido ─────────────────────── */
.order-detail-cards {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}
@media(max-width:680px){ .order-detail-cards { grid-template-columns:1fr; } }
.order-info-card {
    background: #13121f;
    border: 1px solid #252338;
    border-radius: 12px;
    padding: 18px;
}
.order-info-card h3 {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #5c5880;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #252338;
}
.order-info-table { width:100%; border-collapse:collapse; }
.order-info-table th { text-align:left; padding:6px 0; color:#5c5880; font-size:.82rem; width:45%; }
.order-info-table td { color:#f0eeff; font-size:.85rem; padding:6px 0; }

.order-items-card {
    background: #13121f;
    border: 1px solid #252338;
    border-radius: 12px;
    padding: 18px;
}
.order-items-card h3 {
    font-size:.78rem; text-transform:uppercase; letter-spacing:.5px;
    color:#5c5880; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid #252338;
}
.order-items-list { display:flex; flex-direction:column; gap:12px; }
.order-item-row {
    display: flex;
    align-items: center;
    gap: 14px;
}
.order-item-row img {
    width: 56px; height: 56px;
    object-fit: contain;
    background: #0f0e1a;
    border-radius: 8px;
    padding: 4px;
    flex-shrink: 0;
}
.order-item-info { flex:1; display:flex; flex-direction:column; gap:3px; }
.order-item-info strong { font-size:.88rem; color:#f0eeff; }
.order-item-info span   { font-size:.78rem; color:#5c5880; }
.order-item-total { font-size:.95rem; font-weight:700; color:#00e5ff; white-space:nowrap; }

/* ── Formulário de dados ───────────────────── */
.account-form { max-width: 520px; display: flex; flex-direction: column; gap: 24px; }
.form-section h3 {
    font-size: .82rem; text-transform: uppercase; letter-spacing: .5px;
    color: #5c5880; margin-bottom: 14px;
}
.form-section h3 small { font-size: .72rem; text-transform: none; letter-spacing: 0; }
.field-group { margin-bottom: 14px; }
.field-group label { display:block; font-size:.82rem; color:#a09cc0; margin-bottom:5px; }
.field-group input {
    width: 100%; padding: 10px 14px;
    background: rgba(255,255,255,.04); border:1px solid #252338;
    border-radius:8px; color:#f0eeff; font-size:.9rem; outline:none;
    transition: border-color .2s;
}
.field-group input:focus { border-color: #6c63ff; }

/* ── Favoritos ─────────────────────────────── */
.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
}
.fav-card {
    background: #13121f;
    border: 1px solid #252338;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: border-color .2s;
}
.fav-card:hover { border-color: #6c63ff; }
.fav-img {
    aspect-ratio: 1/1;
    background: #0f0e1a;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
}
.fav-img img { width:100%; height:100%; object-fit:contain; }
.fav-body { padding: 12px; display: flex; flex-direction: column; gap: 5px; }
.fav-name {
    font-size: .85rem; font-weight:600; color:#f0eeff; text-decoration:none;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.fav-name:hover { color: #6c63ff; }
.fav-stars i { font-size: .72rem; color: #f59e0b; }
.fav-stars .fa-regular.fa-star { color: #2e2c45; }
.fav-compare { font-size:.72rem; color:#5c5880; text-decoration:line-through; }
.fav-price   { font-size: 1rem; font-weight:700; color:#00e5ff; }
.fav-actions { display:flex; gap:6px; margin-top:4px; }
.fav-actions form { flex:1; }
.fav-actions .btn { width:100%; justify-content:center; padding:7px; font-size:.78rem; }

/* ── Estado vazio ──────────────────────────── */
.account-empty {
    text-align: center;
    padding: 60px 20px;
    color: #5c5880;
}
.account-empty i { font-size: 3rem; display: block; margin-bottom: 12px; color: #2e2c45; }
.account-empty p { margin-bottom: 20px; color: #a09cc0; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
