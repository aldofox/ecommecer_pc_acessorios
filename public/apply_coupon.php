<?php
/**
 * apply_coupon.php — valida e aplica cupom via AJAX
 * Retorna JSON: { ok, msg, discount, discount_label, new_total }
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/CartModel.php';

header('Content-Type: application/json');

$code = strtoupper(trim($_POST['code'] ?? ''));

// Remoção de cupom
if (isset($_POST['remove'])) {
    unset($_SESSION['coupon']);
    echo json_encode(['ok'=>true,'msg'=>'Cupom removido.']);
    exit;
}

if (!$code) {
    echo json_encode(['ok'=>false,'msg'=>'Informe um código de cupom.']);
    exit;
}

$pdo  = db();
$stmt = $pdo->prepare("
    SELECT * FROM coupons
    WHERE code = :code AND is_active = 1
");
$stmt->execute([':code' => $code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['ok'=>false,'msg'=>'Cupom inválido ou expirado.']);
    exit;
}

// Verifica validade
if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    echo json_encode(['ok'=>false,'msg'=>'Este cupom expirou.']);
    exit;
}

// Verifica limite de usos
if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
    echo json_encode(['ok'=>false,'msg'=>'Este cupom atingiu o limite de usos.']);
    exit;
}

// Calcula subtotal do carrinho
$cart     = CartModel::getOrCreateCart($_SESSION['user_id'] ?? null, session_id());
$items    = CartModel::getItems((int)$cart['id']);
$subtotal = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
$shipping = $subtotal > 2000 ? 0.0 : 29.90;

if ($subtotal < (float)$coupon['min_order']) {
    echo json_encode([
        'ok'  => false,
        'msg' => 'Pedido mínimo para este cupom: R$ ' . number_format($coupon['min_order'], 2, ',', '.')
    ]);
    exit;
}

// Calcula desconto
if ($coupon['type'] === 'percent') {
    $discount = round($subtotal * ($coupon['value'] / 100), 2);
    $label    = number_format($coupon['value'], 0) . '% de desconto';
} else {
    $discount = min((float)$coupon['value'], $subtotal);
    $label    = 'R$ ' . number_format($discount, 2, ',', '.') . ' de desconto';
}

$newTotal = max(0, $subtotal + $shipping - $discount);

// Salva cupom na sessão
$_SESSION['coupon'] = [
    'id'       => $coupon['id'],
    'code'     => $coupon['code'],
    'type'     => $coupon['type'],
    'value'    => $coupon['value'],
    'discount' => $discount,
    'label'    => $label,
];

echo json_encode([
    'ok'            => true,
    'msg'           => '✅ Cupom <strong>' . htmlspecialchars($code) . '</strong> aplicado! ' . $label,
    'discount'      => number_format($discount, 2, ',', '.'),
    'discount_label'=> $label,
    'new_total'     => number_format($newTotal, 2, ',', '.'),
]);
