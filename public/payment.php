<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/CartModel.php';
require_once __DIR__ . '/../models/OrderModel.php';

// Guards
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=/payment.php');
    exit;
}
if (empty($_SESSION['shipping'])) {
    header('Location: /checkout.php');
    exit;
}

// Calcula totais (usados na exibição e no POST)
$userId   = (int)$_SESSION['user_id'];
$cart     = CartModel::getOrCreateCart($userId, session_id());
$cartId   = (int)$cart['id'];
$items    = CartModel::getItems($cartId);
$subtotal = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
$shipping = $subtotal > 2000 ? 0.0 : 29.90;
$discount = (float)($_SESSION['coupon']['discount'] ?? 0);
$couponId = (int)($_SESSION['coupon']['id'] ?? 0);
$total    = max(0, $subtotal + $shipping - $discount);

$errors = [];
$method = $_POST['payment_method'] ?? $_GET['method'] ?? 'credit_card';

// ── Processamento do POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'] ?? 'credit_card';

    // Validações específicas por método
    if ($method === 'credit_card') {
        $cardNumber = preg_replace('/\D/', '', $_POST['card'] ?? '');
        $cardName   = trim($_POST['card_name'] ?? '');
        $expiry     = trim($_POST['expiry']    ?? '');
        $cvv        = trim($_POST['cvv']       ?? '');

        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19)
            $errors[] = 'Número do cartão inválido.';
        if (strlen($cardName) < 3)
            $errors[] = 'Nome no cartão inválido.';
        if (!preg_match('/^\d{2}\/\d{2}$/', $expiry))
            $errors[] = 'Validade inválida — use MM/AA.';
        if (!preg_match('/^\d{3,4}$/', $cvv))
            $errors[] = 'CVV inválido.';

    } elseif ($method === 'pix') {
        // Pix não precisa de campos extras — confirmar apenas
        if (empty($_POST['pix_confirm'])) {
            $errors[] = 'Confirme o pagamento via Pix para continuar.';
        }

    } elseif ($method === 'boleto') {
        // Boleto — apenas confirma
        if (empty($_POST['boleto_confirm'])) {
            $errors[] = 'Confirme a geração do boleto para continuar.';
        }
    }

    if (empty($items)) {
        $errors[] = 'Seu carrinho está vazio.';
    }

    if (empty($errors)) {
        try {
            $s = $_SESSION['shipping'];
            $shippingAddress = sprintf('%s, %s - %s / CEP: %s | Tel: %s',
                htmlspecialchars($s['address']),
                htmlspecialchars($s['city']),
                htmlspecialchars($s['state']),
                htmlspecialchars($s['zip']),
                htmlspecialchars($s['phone'])
            );

            $orderId = OrderModel::createOrder(
                $userId,
                $cartId,
                $method,
                $shippingAddress,
                $shipping,
                $discount,
                $couponId
            );

            $_SESSION['last_order_id']      = $orderId;
            $_SESSION['last_payment_method'] = $method;
            unset($_SESSION['shipping'], $_SESSION['coupon']);

            header('Location: /done.php');
            exit;
        } catch (\Throwable $e) {
            $errors[] = 'Erro ao processar pedido: ' . $e->getMessage();
        }
    }
}

// ── HTML ───────────────────────────────────────────────────────────────────
$pageTitle = 'Pagamento — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/checkout_steps.php';
renderCheckoutSteps('payment');
?>

<main class="payment-page">
    <h1><i class="fa-solid fa-credit-card"></i> Pagamento</h1>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $err): ?>
                <p><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="payment-layout">

        <!-- ── Resumo do Pedido ───────────────────── -->
        <div class="payment-order-summary">
            <h3><i class="fa-solid fa-receipt"></i> Resumo</h3>

            <?php foreach ($items as $it): ?>
            <div class="pay-item">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($it['image_url']) ?>"
                     alt="<?= htmlspecialchars($it['name']) ?>">
                <div class="pay-item-info">
                    <span><?= htmlspecialchars($it['name']) ?></span>
                    <small>Qtd: <?= $it['quantity'] ?></small>
                </div>
                <span class="pay-item-price">R$ <?= number_format($it['unit_price'] * $it['quantity'], 2, ',', '.') ?></span>
            </div>
            <?php endforeach; ?>

            <div class="pay-totals">
                <div class="pay-total-row">
                    <span>Subtotal</span>
                    <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                </div>
                <div class="pay-total-row">
                    <span>Frete</span>
                    <span><?= $shipping == 0 ? '<span class="pay-free">Grátis</span>' : 'R$ ' . number_format($shipping, 2, ',', '.') ?></span>
                </div>
                <?php if ($discount > 0): ?>
                <div class="pay-total-row pay-discount-row">
                    <span>Desconto (<?= htmlspecialchars($_SESSION['coupon']['code'] ?? '') ?>)</span>
                    <span>− R$ <?= number_format($discount, 2, ',', '.') ?></span>
                </div>
                <?php endif; ?>
                <div class="pay-total-row pay-grand-total">
                    <span>Total</span>
                    <span>R$ <?= number_format($total, 2, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- ── Formulário de Pagamento ───────────── -->
        <div class="payment-form-wrap">
            <form action="/payment.php" method="post" id="paymentForm">

                <!-- Seletor de método -->
                <div class="payment-methods">
                    <button type="button"
                            class="method-btn <?= $method === 'credit_card' ? 'active' : '' ?>"
                            onclick="selectMethod('credit_card', this)">
                        <i class="fa-regular fa-credit-card"></i>
                        <span>Cartão de Crédito</span>
                    </button>
                    <button type="button"
                            class="method-btn <?= $method === 'pix' ? 'active' : '' ?>"
                            onclick="selectMethod('pix', this)">
                        <i class="fa-brands fa-pix"></i>
                        <span>Pix</span>
                        <em>5% OFF</em>
                    </button>
                    <button type="button"
                            class="method-btn <?= $method === 'boleto' ? 'active' : '' ?>"
                            onclick="selectMethod('boleto', this)">
                        <i class="fa-solid fa-barcode"></i>
                        <span>Boleto</span>
                    </button>
                </div>

                <input type="hidden" name="payment_method" id="paymentMethodInput" value="<?= htmlspecialchars($method) ?>">

                <!-- ── Cartão de Crédito ──────────── -->
                <div class="method-panel" id="panel-credit_card"
                     style="<?= $method !== 'credit_card' ? 'display:none' : '' ?>">

                    <div class="form-field">
                        <label>Número do Cartão</label>
                        <input type="text" id="cardNumber" name="card"
                               placeholder="0000 0000 0000 0000" maxlength="19"
                               value="<?= htmlspecialchars($_POST['card'] ?? '') ?>"
                               autocomplete="cc-number">
                    </div>

                    <div class="form-field">
                        <label>Nome no Cartão</label>
                        <input type="text" name="card_name"
                               placeholder="Como aparece no cartão"
                               value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>"
                               autocomplete="cc-name">
                    </div>

                    <div class="form-field-row">
                        <div class="form-field">
                            <label>Validade</label>
                            <input type="text" id="expiry" name="expiry"
                                   placeholder="MM/AA" maxlength="5"
                                   value="<?= htmlspecialchars($_POST['expiry'] ?? '') ?>"
                                   autocomplete="cc-exp">
                        </div>
                        <div class="form-field">
                            <label>CVV</label>
                            <input type="text" name="cvv"
                                   placeholder="123" maxlength="4"
                                   value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>"
                                   autocomplete="cc-csc">
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Parcelas</label>
                        <select name="installments">
                            <?php
                            $installmentOptions = [1,2,3,6,12];
                            foreach ($installmentOptions as $n):
                                $val = number_format($total / $n, 2, ',', '.');
                            ?>
                            <option value="<?= $n ?>">
                                <?= $n ?>x de R$ <?= $val ?><?= $n === 1 ? ' (à vista)' : ' sem juros' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- ── Pix ───────────────────────── -->
                <div class="method-panel" id="panel-pix"
                     style="<?= $method !== 'pix' ? 'display:none' : '' ?>">

                    <div class="pix-panel">
                        <div class="pix-qr">
                            <!-- QR Code simulado via CSS -->
                            <div class="pix-qr-placeholder">
                                <i class="fa-brands fa-pix"></i>
                                <span>QR Code Pix</span>
                                <small>Simulação — integre com sua API de pagamento</small>
                            </div>
                        </div>
                        <div class="pix-info">
                            <div class="pix-key-box">
                                <span class="pix-key-label">Chave Pix (CNPJ)</span>
                                <div class="pix-key-value">
                                    <code>00.000.000/0001-00</code>
                                    <button type="button" onclick="copyPix()" class="pix-copy-btn">
                                        <i class="fa-solid fa-copy"></i> Copiar
                                    </button>
                                </div>
                            </div>
                            <div class="pix-total">
                                Valor a pagar: <strong>R$ <?= number_format($total * 0.95, 2, ',', '.') ?></strong>
                                <span class="pix-discount-tag">5% OFF aplicado</span>
                            </div>
                            <p class="pix-instructions">
                                <i class="fa-solid fa-circle-info"></i>
                                Abra o app do seu banco, escolha pagar via Pix e escaneie o QR Code ou cole a chave acima.
                            </p>
                            <label class="pix-confirm-label">
                                <input type="checkbox" name="pix_confirm" value="1"
                                       <?= !empty($_POST['pix_confirm']) ? 'checked' : '' ?>>
                                Já realizei o pagamento via Pix e confirmo o valor de
                                <strong>R$ <?= number_format($total * 0.95, 2, ',', '.') ?></strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ── Boleto ─────────────────────── -->
                <div class="method-panel" id="panel-boleto"
                     style="<?= $method !== 'boleto' ? 'display:none' : '' ?>">

                    <div class="boleto-panel">
                        <div class="boleto-icon">
                            <i class="fa-solid fa-barcode"></i>
                        </div>
                        <div class="boleto-info">
                            <h4>Boleto Bancário</h4>
                            <ul>
                                <li><i class="fa-solid fa-clock"></i> Vencimento em <strong>3 dias úteis</strong></li>
                                <li><i class="fa-solid fa-calendar-check"></i> Confirmação em até <strong>2 dias úteis</strong> após pagamento</li>
                                <li><i class="fa-solid fa-circle-info"></i> O boleto será gerado após a confirmação do pedido</li>
                            </ul>
                            <div class="boleto-total">
                                Valor: <strong>R$ <?= number_format($total, 2, ',', '.') ?></strong>
                            </div>
                            <label class="pix-confirm-label">
                                <input type="checkbox" name="boleto_confirm" value="1"
                                       <?= !empty($_POST['boleto_confirm']) ? 'checked' : '' ?>>
                                Entendo o prazo e confirmo a geração do boleto
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botão de finalizar -->
                <button type="submit" class="btn btn-primary btn-lg pay-submit-btn" id="submitBtn">
                    <i class="fa-solid fa-lock"></i>
                    <span id="submitLabel">Finalizar Pagamento</span>
                </button>

                <p class="pay-security-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    Pagamento 100% seguro — seus dados estão protegidos
                </p>

            </form>
        </div>

    </div><!-- /payment-layout -->
</main>

<style>
/* ── Layout ──────────────────────────────────── */
.payment-page {
    max-width: 960px;
    margin: 0 auto;
    padding: 20px 20px 60px;
}
.payment-page h1 {
    font-size: 1.4rem;
    color: var(--text-primary, #f0eeff);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.payment-page h1 i { color: var(--accent, #6c63ff); }

.payment-layout {
    display: grid;
    grid-template-columns: 1fr 1.4fr;
    gap: 24px;
    align-items: start;
}
@media(max-width: 768px) { .payment-layout { grid-template-columns: 1fr; } }

/* ── Resumo ──────────────────────────────────── */
.payment-order-summary {
    background: var(--bg-card, #1a1828);
    border: 1px solid var(--border, #252338);
    border-radius: 14px;
    padding: 20px;
    position: sticky;
    top: 90px;
}
.payment-order-summary h3 {
    font-size: .88rem;
    font-weight: 700;
    color: var(--text-secondary, #a09cc0);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border, #252338);
    display: flex;
    align-items: center;
    gap: 7px;
}
.payment-order-summary h3 i { color: var(--accent, #6c63ff); }

.pay-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border, #252338);
}
.pay-item:last-of-type { border-bottom: none; }
.pay-item img {
    width: 44px;
    height: 44px;
    object-fit: contain;
    border-radius: 6px;
    background: var(--bg-surface, #13121f);
    padding: 4px;
    flex-shrink: 0;
}
.pay-item-info { flex: 1; min-width: 0; }
.pay-item-info span { font-size: .8rem; color: var(--text-primary, #f0eeff); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pay-item-info small { font-size: .72rem; color: var(--text-muted, #5c5880); }
.pay-item-price { font-size: .85rem; font-weight: 700; color: var(--text-primary, #f0eeff); white-space: nowrap; }

.pay-totals { margin-top: 12px; display: flex; flex-direction: column; gap: 6px; }
.pay-total-row { display: flex; justify-content: space-between; font-size: .82rem; color: var(--text-secondary, #a09cc0); }
.pay-free { color: #22c55e; font-weight: 600; }
.pay-discount-row { color: #22c55e; }
.pay-grand-total {
    font-size: 1rem;
    font-weight: 800;
    color: var(--text-primary, #f0eeff);
    padding-top: 8px;
    margin-top: 4px;
    border-top: 1px solid var(--border, #252338);
}
.pay-grand-total span:last-child { color: var(--neon, #00e5ff); }

/* ── Formulário ──────────────────────────────── */
.payment-form-wrap {
    background: var(--bg-card, #1a1828);
    border: 1px solid var(--border, #252338);
    border-radius: 14px;
    padding: 24px;
}

/* ── Seletor de método ───────────────────────── */
.payment-methods {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 24px;
}
@media(max-width: 420px) { .payment-methods { grid-template-columns: 1fr; } }

.method-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 14px 8px;
    background: var(--bg-surface, #13121f);
    border: 2px solid var(--border, #252338);
    border-radius: 10px;
    color: var(--text-secondary, #a09cc0);
    cursor: pointer;
    font-family: inherit;
    font-size: .78rem;
    font-weight: 600;
    position: relative;
    transition: border-color .2s, color .2s, background .2s;
}
.method-btn i    { font-size: 1.3rem; }
.method-btn em   { font-size: .65rem; font-style: normal; background: #22c55e; color: #fff; padding: 1px 6px; border-radius: 99px; position: absolute; top: -6px; right: -4px; }
.method-btn:hover { border-color: var(--accent, #6c63ff); color: var(--text-primary, #f0eeff); }
.method-btn.active {
    border-color: var(--accent, #6c63ff);
    background: rgba(108,99,255,.1);
    color: var(--accent, #6c63ff);
}

/* ── Campos de formulário ────────────────────── */
.form-field { margin-bottom: 14px; }
.form-field label { display: block; font-size: .82rem; color: var(--text-secondary, #a09cc0); margin-bottom: 5px; font-weight: 500; }
.form-field input,
.form-field select {
    width: 100%;
    padding: 10px 14px;
    background: var(--bg-surface, rgba(255,255,255,.04));
    border: 1px solid var(--border-light, #2e2c45);
    border-radius: 8px;
    color: var(--text-primary, #f0eeff);
    font-size: .9rem;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    font-family: inherit;
}
.form-field select { padding-right: 36px; }
.form-field input:focus,
.form-field select:focus { border-color: var(--accent, #6c63ff); box-shadow: 0 0 0 3px rgba(108,99,255,.12); }
.form-field-row { display: flex; gap: 12px; }
.form-field-row .form-field { flex: 1; }

/* ── Pix ─────────────────────────────────────── */
.pix-panel { display: flex; gap: 20px; flex-wrap: wrap; }
.pix-qr-placeholder {
    width: 140px;
    height: 140px;
    background: var(--bg-surface, #13121f);
    border: 2px dashed var(--border-light, #2e2c45);
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-shrink: 0;
}
.pix-qr-placeholder i { font-size: 2.5rem; color: #22c55e; }
.pix-qr-placeholder span { font-size: .75rem; color: var(--text-secondary, #a09cc0); font-weight: 700; }
.pix-qr-placeholder small { font-size: .62rem; color: var(--text-muted, #5c5880); text-align: center; padding: 0 6px; }

.pix-info { flex: 1; min-width: 180px; display: flex; flex-direction: column; gap: 10px; }
.pix-key-box { background: var(--bg-surface, #13121f); border: 1px solid var(--border, #252338); border-radius: 8px; padding: 10px 12px; }
.pix-key-label { font-size: .7rem; color: var(--text-muted, #5c5880); text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 4px; }
.pix-key-value { display: flex; align-items: center; gap: 8px; }
.pix-key-value code { font-family: monospace; font-size: .82rem; color: var(--text-primary, #f0eeff); flex: 1; }
.pix-copy-btn { background: rgba(108,99,255,.12); border: 1px solid rgba(108,99,255,.2); color: var(--accent, #6c63ff); border-radius: 6px; padding: 4px 10px; font-size: .72rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background .2s; }
.pix-copy-btn:hover { background: rgba(108,99,255,.2); }

.pix-total { font-size: .88rem; color: var(--text-secondary, #a09cc0); }
.pix-total strong { color: #22c55e; font-size: 1.1rem; }
.pix-discount-tag { display: inline-block; background: rgba(34,197,94,.12); color: #22c55e; font-size: .68rem; font-weight: 700; padding: 1px 7px; border-radius: 99px; margin-left: 6px; }
.pix-instructions { font-size: .78rem; color: var(--text-muted, #5c5880); display: flex; align-items: flex-start; gap: 6px; line-height: 1.5; }
.pix-instructions i { color: var(--accent, #6c63ff); margin-top: 1px; flex-shrink: 0; }

.pix-confirm-label { display: flex; align-items: flex-start; gap: 8px; font-size: .8rem; color: var(--text-secondary, #a09cc0); cursor: pointer; line-height: 1.5; }
.pix-confirm-label input { accent-color: var(--accent, #6c63ff); margin-top: 2px; flex-shrink: 0; }
.pix-confirm-label strong { color: var(--text-primary, #f0eeff); }

/* ── Boleto ──────────────────────────────────── */
.boleto-panel { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; }
.boleto-icon { font-size: 3rem; color: var(--text-muted, #5c5880); flex-shrink: 0; padding-top: 4px; }
.boleto-info { flex: 1; min-width: 180px; }
.boleto-info h4 { font-size: .95rem; color: var(--text-primary, #f0eeff); margin-bottom: 10px; }
.boleto-info ul { list-style: none; display: flex; flex-direction: column; gap: 7px; margin-bottom: 12px; }
.boleto-info ul li { font-size: .8rem; color: var(--text-secondary, #a09cc0); display: flex; align-items: center; gap: 8px; }
.boleto-info ul li i { color: var(--accent, #6c63ff); font-size: .8rem; width: 14px; }
.boleto-total { font-size: .88rem; color: var(--text-secondary, #a09cc0); margin-bottom: 10px; }
.boleto-total strong { color: var(--text-primary, #f0eeff); font-size: 1.05rem; }

/* ── Botão e segurança ───────────────────────── */
.pay-submit-btn { width: 100%; margin-top: 20px; padding: 14px; font-size: .95rem; justify-content: center; }
.pay-security-note { text-align: center; font-size: .75rem; color: var(--text-muted, #5c5880); margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 6px; }
.pay-security-note i { color: #22c55e; }
</style>

<script>
const paymentTotal = <?= $total ?>;
const pixTotal     = <?= round($total * 0.95, 2) ?>;

function selectMethod(method, btn) {
    // Ativa botão
    document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Troca hidden input
    document.getElementById('paymentMethodInput').value = method;

    // Exibe painel correto
    document.querySelectorAll('.method-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panel-' + method).style.display = 'block';

    // Atualiza label do botão
    const labels = {
        credit_card: '<i class="fa-solid fa-lock"></i> Pagar com Cartão — R$ ' + paymentTotal.toLocaleString('pt-BR',{minimumFractionDigits:2}),
        pix:         '<i class="fa-brands fa-pix"></i> Confirmar Pagamento Pix — R$ ' + pixTotal.toLocaleString('pt-BR',{minimumFractionDigits:2}),
        boleto:      '<i class="fa-solid fa-barcode"></i> Gerar Boleto — R$ ' + paymentTotal.toLocaleString('pt-BR',{minimumFractionDigits:2}),
    };
    document.getElementById('submitBtn').innerHTML = labels[method] || labels.credit_card;

    // Gerencia required
    const cardFields = ['card','card_name','expiry','cvv'];
    cardFields.forEach(id => {
        const el = document.querySelector(`[name="${id}"]`);
        if (el) el.required = method === 'credit_card';
    });
}

// Formatação do número do cartão
document.getElementById('cardNumber')?.addEventListener('input', function () {
    let v = this.value.replace(/\D/g,'').substring(0,16);
    this.value = v.replace(/(.{4})/g,'$1 ').trim();
});

// Formatação da validade
document.getElementById('expiry')?.addEventListener('input', function () {
    let v = this.value.replace(/\D/g,'').substring(0,4);
    if (v.length > 2) v = v.slice(0,2) + '/' + v.slice(2);
    this.value = v;
});

// Copiar chave Pix
function copyPix() {
    navigator.clipboard.writeText('00.000.000/0001-00').then(() => {
        const btn = document.querySelector('.pix-copy-btn');
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copiado!';
        btn.style.color = '#22c55e';
        setTimeout(() => {
            btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copiar';
            btn.style.color = '';
        }, 2000);
    });
}

// Inicializa o label do botão com o método padrão
selectMethod('<?= htmlspecialchars($method) ?>', document.querySelector('.method-btn.active'));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
