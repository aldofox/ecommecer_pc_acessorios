<?php
// ── Lógica de ação ANTES de qualquer output ──────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/CartModel.php';

$action    = $_GET['action'] ?? 'view';
$userId    = $_SESSION['user_id'] ?? null;
$sessionId = session_id();
$cart      = CartModel::getOrCreateCart($userId, $sessionId);
$error     = '';

try {
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        CartModel::addItem((int)$cart['id'], $pid, $qty);
        header('Location: /cart.php');
        exit;
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $qty = max(1, (int)$_POST['quantity']);
        CartModel::updateQty((int)$_POST['item_id'], $qty);
        header('Location: /cart.php');
        exit;
    }

    if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        CartModel::removeItem((int)$_POST['item_id']);
        header('Location: /cart.php');
        exit;
    }
} catch (\Throwable $e) {
    $error = $e->getMessage();
}

// ── Calcula totais ───────────────────────────────────────────
$items    = CartModel::getItems((int)$cart['id']);
$subtotal = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
$shipping = $subtotal > 2000 ? 0 : ($subtotal > 0 ? 29.90 : 0);
$discount = (float)(($_SESSION['coupon']['discount'] ?? 0));
$total    = max(0, $subtotal + $shipping - $discount);

// ── Abre HTML ────────────────────────────────────────────────
$pageTitle = 'Carrinho — Loja Gamer';
$extraCss  = ['cart.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/checkout_steps.php';
renderCheckoutSteps('cart');
?>

<main class="cart-page">
    <h1><i class="fa-solid fa-cart-shopping"></i> Carrinho</h1>

    <?php if ($error): ?>
        <div class="error-box">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="cart-empty">
            <i class="fa-solid fa-cart-xmark"></i>
            <p>Seu carrinho está vazio.</p>
            <a href="/index.php" class="btn btn-primary">Ver Produtos</a>
        </div>
    <?php else: ?>

    <div class="cart-layout">

        <!-- ── Lista de itens ─────────────────────── -->
        <div class="cart-items">
            <?php foreach ($items as $it): ?>
            <div class="cart-item">
                <a href="/product.php?slug=<?= htmlspecialchars($it['slug'] ?? '') ?>" class="cart-item-img">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($it['image_url']) ?>"
                         alt="<?= htmlspecialchars($it['name']) ?>"
                         loading="lazy">
                </a>
                <div class="cart-item-info">
                    <strong class="cart-item-name"><?= htmlspecialchars($it['name']) ?></strong>
                    <div class="cart-item-price">R$ <?= number_format($it['unit_price'], 2, ',', '.') ?></div>

                    <div class="cart-item-actions">
                        <form action="/cart.php?action=update" method="post" class="qty-form">
                            <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                            <button type="button" class="qty-btn" onclick="changeQty(this, -1)">−</button>
                            <input type="number" name="quantity"
                                   value="<?= (int)$it['quantity'] ?>" min="1" max="99"
                                   class="qty-input" onchange="this.form.submit()">
                            <button type="button" class="qty-btn" onclick="changeQty(this, 1)">+</button>
                        </form>
                        <form action="/cart.php?action=remove" method="post">
                            <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                            <button type="submit" class="btn-remove" title="Remover item"
                                    onclick="return confirm('Remover <?= htmlspecialchars($it['name'], ENT_QUOTES) ?> do carrinho?')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="cart-item-subtotal">
                    R$ <?= number_format($it['unit_price'] * $it['quantity'], 2, ',', '.') ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Resumo ─────────────────────────────── -->
        <aside class="cart-summary">
            <h2>Resumo do Pedido</h2>

            <div class="summary-row">
                <span>Subtotal (<?= count($items) ?> <?= count($items) === 1 ? 'item' : 'itens' ?>)</span>
                <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
            </div>

            <div class="summary-row">
                <span>Frete</span>
                <span class="<?= $shipping == 0 ? 'free-shipping' : '' ?>">
                    <?= $shipping == 0 ? '<i class="fa-solid fa-check"></i> Grátis' : 'R$ ' . number_format($shipping, 2, ',', '.') ?>
                </span>
            </div>

            <?php if ($shipping > 0): ?>
                <p class="shipping-hint">
                    <i class="fa-solid fa-truck"></i>
                    Frete grátis em pedidos acima de R$ 2.000,00
                </p>
            <?php endif; ?>

            <!-- Cupom de desconto -->
            <?php $couponSession = $_SESSION['coupon'] ?? null; ?>
            <?php if ($couponSession): ?>
                <div class="coupon-applied">
                    <div class="coupon-applied-info">
                        <i class="fa-solid fa-tag"></i>
                        <span>
                            <strong><?= htmlspecialchars($couponSession['code']) ?></strong>
                            — <?= htmlspecialchars($couponSession['label']) ?>
                        </span>
                    </div>
                    <button onclick="removeCoupon()" class="coupon-remove" title="Remover cupom">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="summary-row" style="color:#22c55e">
                    <span>Desconto</span>
                    <span>− R$ <?= number_format($couponSession['discount'], 2, ',', '.') ?></span>
                </div>
            <?php else: ?>
                <div class="coupon-box">
                    <input type="text" id="couponInput" placeholder="Código do cupom"
                           maxlength="30" style="text-transform:uppercase">
                    <button onclick="applyCoupon()" class="coupon-btn">
                        <i class="fa-solid fa-tag"></i> Aplicar
                    </button>
                </div>
                <div id="couponMsg" class="coupon-msg" style="display:none"></div>
            <?php endif; ?>

            <div class="summary-total">
                <span>Total</span>
                <span>R$ <?= number_format($total, 2, ',', '.') ?></span>
            </div>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <p class="login-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    <a href="/login.php?redirect=/checkout.php">Entre na sua conta</a> para finalizar a compra.
                </p>
            <?php endif; ?>

            <a href="/checkout.php" class="btn btn-primary btn-block cart-checkout-btn"
               <?= !isset($_SESSION['user_id']) ? 'onclick="return confirm(\'Você precisa estar logado. Ir para o login?\')"' : '' ?>>
                <i class="fa-solid fa-lock"></i> Finalizar Compra
            </a>

            <a href="/index.php" class="btn btn-secondary btn-block" style="margin-top:10px">
                <i class="fa-solid fa-arrow-left"></i> Continuar Comprando
            </a>
        </aside>

    </div><!-- /cart-layout -->
    <?php endif; ?>
</main>

<script>
function changeQty(btn, delta) {
    const form  = btn.closest('.qty-form');
    const input = form.querySelector('.qty-input');
    const val   = parseInt(input.value) + delta;
    if (val >= 1) { input.value = val; form.submit(); }
}

function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim().toUpperCase();
    const msg  = document.getElementById('couponMsg');
    if (!code) return;
    msg.style.display = 'flex';
    msg.className = 'coupon-msg coupon-loading';
    msg.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Validando...';

    fetch('/apply_coupon.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'code=' + encodeURIComponent(code)
    })
    .then(r => r.json())
    .then(data => {
        msg.className = 'coupon-msg ' + (data.ok ? 'coupon-ok' : 'coupon-err');
        msg.innerHTML = data.msg;
        if (data.ok) setTimeout(() => location.reload(), 800);
    });
}

function removeCoupon() {
    fetch('/apply_coupon.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'remove=1'
    }).then(() => location.reload());
}

document.getElementById('couponInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
