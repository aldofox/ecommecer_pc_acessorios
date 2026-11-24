<?php
// Inicia sessão ANTES de qualquer verificação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/CartModel.php';

// Exige login
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=/checkout.php');
    exit;
}

// Valida se carrinho tem itens
$cart  = CartModel::getOrCreateCart((int)$_SESSION['user_id'], session_id());
$items = CartModel::getItems((int)$cart['id']);
if (empty($items)) {
    header('Location: /cart.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $address  = trim($_POST['address']  ?? '');
    $city     = trim($_POST['city']     ?? '');
    $state    = trim($_POST['state']    ?? '');
    $zip      = preg_replace('/\D/', '', $_POST['zip'] ?? '');
    $phone    = trim($_POST['phone']    ?? '');

    if (strlen($fullname) < 3)   $errors[] = 'Nome completo inválido.';
    if (strlen($address)  < 5)   $errors[] = 'Endereço inválido.';
    if (strlen($city)     < 2)   $errors[] = 'Cidade inválida.';
    if (strlen($state)    < 2)   $errors[] = 'Estado inválido.';
    if (strlen($zip)      !== 8) $errors[] = 'CEP inválido (8 dígitos).';
    if (strlen($phone)    < 10)  $errors[] = 'Telefone inválido.';

    if (empty($errors)) {
        $_SESSION['shipping'] = [
            'fullname' => $fullname,
            'address'  => $address,
            'city'     => $city,
            'state'    => $state,
            'zip'      => $zip,
            'phone'    => $phone,
        ];
        header('Location: /payment.php');
        exit;
    }
}

// Abre o HTML somente aqui — depois de todos os redirects/lógica
$pageTitle = 'Dados de Entrega — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/checkout_steps.php';
renderCheckoutSteps('checkout');
?>

<main class="checkout-page">
    <h1><i class="fa-solid fa-location-dot"></i> Dados de Entrega</h1>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $e): ?>
                <p><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="/checkout.php" method="POST" class="checkout-form">
        <label for="fullname">Nome Completo</label>
        <input type="text" id="fullname" name="fullname" required autocomplete="name"
               value="<?= htmlspecialchars($_POST['fullname'] ?? $_SESSION['user_name'] ?? '') ?>">

        <label for="address">Endereço (Rua, número, complemento)</label>
        <input type="text" id="address" name="address" required autocomplete="street-address"
               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">

        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <div style="flex:2;min-width:140px">
                <label for="city">Cidade</label>
                <input type="text" id="city" name="city" required autocomplete="address-level2"
                       value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
            </div>
            <div style="flex:1;min-width:80px">
                <label for="state">UF</label>
                <input type="text" id="state" name="state" required maxlength="2"
                       placeholder="SP" style="text-transform:uppercase"
                       value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
            </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:130px">
                <label for="zip">CEP</label>
                <input type="text" id="zip" name="zip" required placeholder="00000-000" maxlength="9"
                       value="<?= htmlspecialchars($_POST['zip'] ?? '') ?>">
            </div>
            <div style="flex:2;min-width:160px">
                <label for="phone">Telefone / WhatsApp</label>
                <input type="text" id="phone" name="phone" required placeholder="(11) 99999-9999"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="margin-top:6px">
            <i class="fa-solid fa-arrow-right"></i> Continuar para Pagamento
        </button>
    </form>
</main>

<script>
document.getElementById('zip').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').substring(0, 8);
    if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
    this.value = v;
});
document.getElementById('phone').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').substring(0, 11);
    if (v.length > 10)      v = '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7);
    else if (v.length > 6)  v = '(' + v.slice(0,2) + ') ' + v.slice(2,6) + '-' + v.slice(6);
    this.value = v;
});
document.getElementById('state').addEventListener('input', function () {
    this.value = this.value.toUpperCase();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
