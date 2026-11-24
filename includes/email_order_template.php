<?php
/**
 * Gera o HTML do e-mail de confirmação de pedido.
 *
 * @param array $order   Dados do pedido (id, total, subtotal, shipping, discount, payment_method, shipping_address, created_at)
 * @param array $items   Itens do pedido (product_name, quantity, unit_price, total_line, image_url)
 * @param array $user    Dados do usuário (name, email)
 * @param string $siteUrl URL base do site (ex: http://pc.local)
 * @return string  HTML completo do e-mail
 */
function buildOrderEmailHtml(array $order, array $items, array $user, string $siteUrl = ''): string
{
    $siteUrl   = rtrim($siteUrl, '/');
    $userName  = htmlspecialchars($order['user_name'] ?? $user['name'] ?? 'Cliente');
    $orderId   = (int)$order['id'];
    $orderDate = date('d/m/Y \à\s H:i', strtotime($order['created_at']));
    $subtotal  = number_format((float)$order['subtotal'], 2, ',', '.');
    $shipping  = (float)$order['shipping'];
    $discount  = (float)($order['discount'] ?? 0);
    $total     = number_format((float)$order['total'], 2, ',', '.');

    $paymentLabels = [
        'credit_card' => '💳 Cartão de Crédito',
        'pix'         => '⚡ Pix',
        'boleto'      => '📄 Boleto Bancário',
        'debit_card'  => '💳 Cartão de Débito',
    ];
    $paymentLabel = $paymentLabels[$order['payment_method']] ?? htmlspecialchars($order['payment_method']);

    $statusLabels = [
        'pending'   => ['Pedido Recebido',    '#f59e0b', '⏳'],
        'paid'      => ['Pagamento Aprovado', '#22c55e', '✅'],
        'shipped'   => ['Enviado',            '#3b82f6', '🚚'],
        'delivered' => ['Entregue',           '#22c55e', '📦'],
        'canceled'  => ['Cancelado',          '#ef4444', '❌'],
    ];
    [$statusLabel, $statusColor, $statusIcon] = $statusLabels[$order['status']] ?? ['Pendente', '#f59e0b', '⏳'];

    // ── Itens do pedido ───────────────────────────────────────────────────
    $itemsHtml = '';
    foreach ($items as $item) {
        $productName = htmlspecialchars($item['product_name']);
        $qty         = (int)$item['quantity'];
        $unitPrice   = number_format((float)$item['unit_price'], 2, ',', '.');
        $lineTotal   = number_format((float)$item['total_line'], 2, ',', '.');
        $imageUrl    = !empty($item['image_url'])
                       ? $siteUrl . '/' . ltrim($item['image_url'], '/')
                       : $siteUrl . '/images/placeholder.jpg';

        $itemsHtml .= <<<HTML
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #2e2c45;vertical-align:middle">
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="70" style="vertical-align:middle;padding-right:14px">
                  <img src="{$imageUrl}" alt="{$productName}"
                       width="60" height="60"
                       style="border-radius:8px;object-fit:contain;background:#13121f;display:block">
                </td>
                <td style="vertical-align:middle">
                  <span style="font-weight:700;color:#f0eeff;font-size:14px;display:block">{$productName}</span>
                  <span style="color:#a09cc0;font-size:12px">Qtd: {$qty} × R$ {$unitPrice}</span>
                </td>
                <td width="100" style="text-align:right;vertical-align:middle">
                  <span style="font-weight:800;color:#00e5ff;font-size:15px">R$ {$lineTotal}</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
HTML;
    }

    // ── Totais ────────────────────────────────────────────────────────────
    $shippingRow = $shipping == 0
        ? '<tr><td style="color:#a09cc0;font-size:13px;padding:4px 0">Frete</td><td style="text-align:right;color:#22c55e;font-weight:700;font-size:13px">Grátis</td></tr>'
        : '<tr><td style="color:#a09cc0;font-size:13px;padding:4px 0">Frete</td><td style="text-align:right;color:#f0eeff;font-size:13px;padding:4px 0">R$ ' . number_format($shipping, 2, ',', '.') . '</td></tr>';

    $discountRow = '';
    if ($discount > 0) {
        $discountRow = '<tr><td style="color:#22c55e;font-size:13px;padding:4px 0">Desconto</td><td style="text-align:right;color:#22c55e;font-size:13px;padding:4px 0">− R$ ' . number_format($discount, 2, ',', '.') . '</td></tr>';
    }

    // ── Endereço de entrega ───────────────────────────────────────────────
    $addressHtml = nl2br(htmlspecialchars($order['shipping_address'] ?? ''));

    // ── HTML completo ─────────────────────────────────────────────────────
    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pedido #{$orderId} — Loja Gamer</title>
</head>
<body style="margin:0;padding:0;background:#0d0d14;font-family:'Segoe UI',Arial,sans-serif;color:#f0eeff">

  <!-- Wrapper -->
  <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#0d0d14;padding:32px 16px">
    <tr>
      <td align="center">
        <table cellpadding="0" cellspacing="0" border="0" width="600"
               style="background:#13121f;border-radius:16px;overflow:hidden;border:1px solid #252338;max-width:600px">

          <!-- ── CABEÇALHO ─────────────────────────────────── -->
          <tr>
            <td style="background:linear-gradient(135deg,#1a1535,#0e1a2e);padding:36px 40px;text-align:center;border-bottom:1px solid #252338">
              <div style="font-size:28px;font-weight:900;letter-spacing:1px;color:#f0eeff">
                ⚡ Loja<span style="color:#6c63ff">Gamer</span>
              </div>
              <div style="color:#a09cc0;font-size:13px;margin-top:6px">Seu setup dos sonhos começa aqui</div>
            </td>
          </tr>

          <!-- ── SAUDAÇÃO ──────────────────────────────────── -->
          <tr>
            <td style="padding:32px 40px 24px;text-align:center;border-bottom:1px solid #252338">
              <div style="font-size:32px;margin-bottom:8px">🎉</div>
              <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#f0eeff">
                Pedido Confirmado!
              </h1>
              <p style="margin:0;color:#a09cc0;font-size:15px">
                Olá, <strong style="color:#f0eeff">{$userName}</strong>! Recebemos seu pedido com sucesso.
              </p>
            </td>
          </tr>

          <!-- ── INFO DO PEDIDO ────────────────────────────── -->
          <tr>
            <td style="padding:24px 40px;border-bottom:1px solid #252338">
              <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td width="50%" style="vertical-align:top">
                    <div style="background:#1a1828;border:1px solid #252338;border-radius:10px;padding:16px">
                      <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#5c5880;margin-bottom:6px">Número do Pedido</div>
                      <div style="font-size:20px;font-weight:900;color:#6c63ff">#{$orderId}</div>
                      <div style="font-size:11px;color:#5c5880;margin-top:4px">{$orderDate}</div>
                    </div>
                  </td>
                  <td width="4%"></td>
                  <td width="46%" style="vertical-align:top">
                    <div style="background:#1a1828;border:1px solid #252338;border-radius:10px;padding:16px">
                      <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#5c5880;margin-bottom:6px">Status</div>
                      <div style="font-size:16px;font-weight:700;color:{$statusColor}">{$statusIcon} {$statusLabel}</div>
                      <div style="font-size:11px;color:#5c5880;margin-top:4px">Você receberá atualizações por e-mail</div>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ── PRODUTOS ──────────────────────────────────── -->
          <tr>
            <td style="padding:24px 40px;border-bottom:1px solid #252338">
              <h2 style="margin:0 0 16px;font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#5c5880">
                🛍️ Itens do Pedido
              </h2>
              <table cellpadding="0" cellspacing="0" border="0" width="100%">
                {$itemsHtml}
              </table>
            </td>
          </tr>

          <!-- ── TOTAIS ────────────────────────────────────── -->
          <tr>
            <td style="padding:24px 40px;border-bottom:1px solid #252338">
              <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="color:#a09cc0;font-size:13px;padding:4px 0">Subtotal</td>
                  <td style="text-align:right;color:#f0eeff;font-size:13px;padding:4px 0">R$ {$subtotal}</td>
                </tr>
                {$shippingRow}
                {$discountRow}
                <tr>
                  <td colspan="2" style="padding:8px 0 4px;border-top:1px solid #252338"></td>
                </tr>
                <tr>
                  <td style="font-size:16px;font-weight:800;color:#f0eeff">Total Pago</td>
                  <td style="text-align:right;font-size:22px;font-weight:900;color:#00e5ff">R$ {$total}</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ── PAGAMENTO ─────────────────────────────────── -->
          <tr>
            <td style="padding:20px 40px;border-bottom:1px solid #252338">
              <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td width="50%" style="vertical-align:top">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#5c5880;margin-bottom:6px">Forma de Pagamento</div>
                    <div style="font-size:14px;font-weight:700;color:#f0eeff">{$paymentLabel}</div>
                  </td>
                  <td width="4%"></td>
                  <td width="46%" style="vertical-align:top">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#5c5880;margin-bottom:6px">Entrega</div>
                    <div style="font-size:12px;color:#a09cc0;line-height:1.6">{$addressHtml}</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ── CTA ──────────────────────────────────────── -->
          <tr>
            <td style="padding:28px 40px;text-align:center;border-bottom:1px solid #252338">
              <a href="{$siteUrl}/minha_conta.php?tab=pedido&id={$orderId}"
                 style="display:inline-block;background:#6c63ff;color:#fff;font-size:14px;font-weight:700;
                        text-decoration:none;padding:13px 32px;border-radius:8px;letter-spacing:.3px">
                Acompanhar Pedido →
              </a>
              <p style="margin:16px 0 0;font-size:12px;color:#5c5880">
                Dúvidas? Entre em contato: <a href="{$siteUrl}/contact.php" style="color:#6c63ff;text-decoration:none">contato@lojagamer.com</a>
              </p>
            </td>
          </tr>

          <!-- ── BENEFÍCIOS ────────────────────────────────── -->
          <tr>
            <td style="padding:20px 40px;border-bottom:1px solid #252338">
              <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td width="33%" style="text-align:center;padding:0 8px">
                    <div style="font-size:20px">🔒</div>
                    <div style="font-size:11px;color:#5c5880;margin-top:4px">Compra Segura</div>
                  </td>
                  <td width="33%" style="text-align:center;padding:0 8px">
                    <div style="font-size:20px">🚚</div>
                    <div style="font-size:11px;color:#5c5880;margin-top:4px">Entrega Rápida</div>
                  </td>
                  <td width="33%" style="text-align:center;padding:0 8px">
                    <div style="font-size:20px">↩️</div>
                    <div style="font-size:11px;color:#5c5880;margin-top:4px">7 dias p/ devolver</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- ── RODAPÉ ────────────────────────────────────── -->
          <tr>
            <td style="padding:20px 40px;text-align:center;background:#0a0912;border-radius:0 0 16px 16px">
              <div style="font-size:16px;font-weight:800;color:#f0eeff;margin-bottom:8px">
                ⚡ Loja<span style="color:#6c63ff">Gamer</span>
              </div>
              <div style="font-size:11px;color:#2e2c45;line-height:1.8">
                Você recebeu este e-mail porque realizou uma compra em nossa loja.<br>
                © <?= date('Y') ?> Loja Gamer. Todos os direitos reservados.
              </div>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}
