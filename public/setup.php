<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/CartModel.php';

$pdo = db();

// Slots do setup com ícone e categoria
$slots = [
    'processador'   => ['label'=>'Processador',     'icon'=>'fa-cpu',           'cat_slug'=>'processadores'],
    'placa_video'   => ['label'=>'Placa de Vídeo',   'icon'=>'fa-microchip',     'cat_slug'=>'placas-de-video'],
    'monitor'       => ['label'=>'Monitor',          'icon'=>'fa-desktop',       'cat_slug'=>'monitores'],
    'gabinete'      => ['label'=>'Gabinete',         'icon'=>'fa-computer',      'cat_slug'=>'gabinetes'],
    'teclado'       => ['label'=>'Teclado',          'icon'=>'fa-keyboard',      'cat_slug'=>'perifericos'],
    'mouse'         => ['label'=>'Mouse',            'icon'=>'fa-computer-mouse','cat_slug'=>'perifericos'],
    'headset'       => ['label'=>'Headset',          'icon'=>'fa-headphones',    'cat_slug'=>'headsets'],
];

// Busca produtos por categoria slug
$productsBySlot = [];
foreach ($slots as $slotKey => $slotData) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.image_url, p.unit_price, p.compare_price, p.stock_qty,
               COALESCE(AVG(r.rating),0) AS rating_avg
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE c.slug = :slug AND p.is_active = 1
        GROUP BY p.id
        ORDER BY p.unit_price ASC
    ");
    $stmt->execute([':slug' => $slotData['cat_slug']]);
    $productsBySlot[$slotKey] = $stmt->fetchAll();
}

// Adicionar tudo ao carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_all'])) {
    $cart    = CartModel::getOrCreateCart($_SESSION['user_id'] ?? null, session_id());
    $cartId  = (int)$cart['id'];
    $added   = 0;
    $errors  = [];

    foreach ($slots as $slotKey => $_) {
        $pid = (int)($_POST["slot_$slotKey"] ?? 0);
        if ($pid > 0) {
            try {
                CartModel::addItem($cartId, $pid, 1);
                $added++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if ($added > 0) {
        header('Location: /cart.php');
        exit;
    }
}

// Calcular total da seleção atual (via POST persistência)
$selectedIds = [];
foreach ($slots as $slotKey => $_) {
    $pid = (int)($_POST["slot_$slotKey"] ?? $_GET["slot_$slotKey"] ?? 0);
    if ($pid > 0) $selectedIds[$slotKey] = $pid;
}

$pageTitle = 'Monte seu Setup — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/setup.css">

<main class="setup-page">
  <div class="container">

    <div class="setup-header">
      <h1><i class="fa-solid fa-screwdriver-wrench"></i> Monte seu Setup</h1>
      <p>Escolha um componente para cada categoria e adicione tudo ao carrinho de uma vez.</p>
    </div>

    <form method="post" action="/setup.php" id="setupForm">

      <div class="setup-layout">

        <!-- Slots de componentes -->
        <div class="setup-slots">
          <?php foreach ($slots as $slotKey => $slotData):
            $products   = $productsBySlot[$slotKey];
            $selectedId = $selectedIds[$slotKey] ?? 0;
            // Encontra produto selecionado
            $selected   = null;
            foreach ($products as $sp) {
                if ($sp['id'] == $selectedId) { $selected = $sp; break; }
            }
          ?>
          <div class="setup-slot <?= $selected ? 'has-product' : '' ?>" id="slot-<?= $slotKey ?>">
            <div class="slot-icon">
              <i class="fa-solid <?= $slotData['icon'] ?>"></i>
            </div>
            <div class="slot-body">
              <div class="slot-label"><?= $slotData['label'] ?></div>

              <?php if ($selected): ?>
              <div class="slot-selected">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($selected['image_url']) ?>"
                     alt="<?= htmlspecialchars($selected['name']) ?>">
                <div class="slot-selected-info">
                  <span class="slot-selected-name"><?= htmlspecialchars($selected['name']) ?></span>
                  <span class="slot-selected-price">R$ <?= number_format($selected['unit_price'],2,',','.') ?></span>
                  <?php if ($selected['stock_qty'] <= 0): ?>
                    <span class="slot-out">Esgotado</span>
                  <?php endif; ?>
                </div>
              </div>
              <?php else: ?>
              <div class="slot-empty-state">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Nenhum selecionado</span>
              </div>
              <?php endif; ?>

              <select name="slot_<?= $slotKey ?>" class="slot-select"
                      onchange="updateSetup()"
                      <?= empty($products) ? 'disabled' : '' ?>>
                <option value="">— Selecionar <?= $slotData['label'] ?> —</option>
                <?php foreach ($products as $sp):
                  $stars = min(5, round((float)$sp['rating_avg']));
                  $disc  = !empty($sp['compare_price']) && $sp['compare_price'] > $sp['unit_price'];
                ?>
                <option value="<?= $sp['id'] ?>"
                        data-price="<?= $sp['unit_price'] ?>"
                        data-name="<?= htmlspecialchars($sp['name'], ENT_QUOTES) ?>"
                        data-img="<?= BASE_URL ?>/<?= htmlspecialchars($sp['image_url']) ?>"
                        data-stock="<?= $sp['stock_qty'] ?>"
                        <?= $sp['id'] == $selectedId ? 'selected' : '' ?>>
                  <?= htmlspecialchars($sp['name']) ?> — R$ <?= number_format($sp['unit_price'],2,',','.') ?>
                  <?= $disc ? ' (-'.round((1-$sp['unit_price']/$sp['compare_price'])*100).'%)' : '' ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Painel do total -->
        <aside class="setup-summary">
          <h3><i class="fa-solid fa-calculator"></i> Resumo do Setup</h3>

          <div class="summary-slots" id="summarySlots">
            <?php foreach ($slots as $slotKey => $slotData):
              $sel = null;
              foreach ($productsBySlot[$slotKey] as $sp) {
                  if ($sp['id'] == ($selectedIds[$slotKey] ?? 0)) { $sel = $sp; break; }
              }
            ?>
            <div class="summary-slot-row" id="sum-<?= $slotKey ?>">
              <span class="sum-label">
                <i class="fa-solid <?= $slotData['icon'] ?>"></i>
                <?= $slotData['label'] ?>
              </span>
              <span class="sum-price" id="sum-price-<?= $slotKey ?>">
                <?= $sel ? 'R$ '.number_format($sel['unit_price'],2,',','.') : '—' ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="summary-divider"></div>

          <div class="summary-total-row">
            <span>Total Estimado</span>
            <span id="setupTotal">
              <?php
              $total = 0;
              foreach ($slots as $slotKey => $_) {
                  $sel = null;
                  foreach ($productsBySlot[$slotKey] as $sp) {
                      if ($sp['id'] == ($selectedIds[$slotKey] ?? 0)) { $sel = $sp; break; }
                  }
                  if ($sel) $total += $sel['unit_price'];
              }
              echo $total > 0 ? 'R$ '.number_format($total,2,',','.') : 'R$ 0,00';
              ?>
            </span>
          </div>

          <div class="summary-count" id="setupCount">
            <?php
            $cnt = count($selectedIds);
            echo $cnt > 0 ? "$cnt de ".count($slots)." componentes selecionados" : "Nenhum componente selecionado";
            ?>
          </div>

          <button type="submit" name="add_all" value="1"
                  class="btn btn-primary btn-lg btn-block"
                  id="btnAddAll"
                  <?= empty($selectedIds) ? 'disabled' : '' ?>>
            <i class="fa-solid fa-cart-plus"></i>
            Adicionar Setup ao Carrinho
          </button>

          <a href="/index.php" class="btn btn-secondary btn-block" style="margin-top:10px">
            Cancelar
          </a>

          <div class="setup-tip">
            <i class="fa-solid fa-circle-info"></i>
            Você pode editar as quantidades no carrinho antes de finalizar.
          </div>
        </aside>

      </div>
    </form>
  </div>
</main>

<script>
const prices  = {};
const names   = {};
const slotKeys = <?= json_encode(array_keys($slots)) ?>;

// Inicializa com seleções atuais
document.querySelectorAll('.slot-select').forEach(sel => {
    const key = sel.name.replace('slot_', '');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        prices[key] = parseFloat(opt.dataset.price || 0);
        names[key]  = opt.dataset.name || '';
    }
});

function updateSetup() {
    let total = 0;
    let count = 0;

    document.querySelectorAll('.slot-select').forEach(sel => {
        const key = sel.name.replace('slot_', '');
        const opt = sel.options[sel.selectedIndex];
        const priceEl = document.getElementById('sum-price-' + key);

        if (opt && opt.value) {
            const price = parseFloat(opt.dataset.price || 0);
            prices[key] = price;
            names[key]  = opt.dataset.name;
            total += price;
            count++;
            if (priceEl) priceEl.textContent = 'R$ ' + price.toLocaleString('pt-BR',{minimumFractionDigits:2});

            // Atualiza visual do slot
            const slotDiv = document.getElementById('slot-' + key);
            if (slotDiv) {
                slotDiv.classList.add('has-product');
                const emptyState = slotDiv.querySelector('.slot-empty-state');
                const selectedDiv = slotDiv.querySelector('.slot-selected');

                if (emptyState) emptyState.style.display = 'none';
                if (!selectedDiv) {
                    const img = opt.dataset.img;
                    const html = `<div class="slot-selected">
                        <img src="${img}" alt="${opt.dataset.name}">
                        <div class="slot-selected-info">
                            <span class="slot-selected-name">${opt.dataset.name}</span>
                            <span class="slot-selected-price">R$ ${price.toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
                            ${opt.dataset.stock == 0 ? '<span class="slot-out">Esgotado</span>' : ''}
                        </div>
                    </div>`;
                    slotDiv.querySelector('.slot-body').insertAdjacentHTML('afterbegin', html);
                } else {
                    selectedDiv.querySelector('img').src = opt.dataset.img;
                    selectedDiv.querySelector('.slot-selected-name').textContent = opt.dataset.name;
                    selectedDiv.querySelector('.slot-selected-price').textContent =
                        'R$ ' + price.toLocaleString('pt-BR',{minimumFractionDigits:2});
                }
            }
        } else {
            delete prices[key];
            if (priceEl) priceEl.textContent = '—';
            const slotDiv = document.getElementById('slot-' + key);
            if (slotDiv) {
                slotDiv.classList.remove('has-product');
                const selectedDiv = slotDiv.querySelector('.slot-selected');
                if (selectedDiv) selectedDiv.remove();
                const emptyState = slotDiv.querySelector('.slot-empty-state');
                if (emptyState) emptyState.style.display = 'flex';
            }
        }
    });

    document.getElementById('setupTotal').textContent =
        total > 0 ? 'R$ ' + total.toLocaleString('pt-BR',{minimumFractionDigits:2}) : 'R$ 0,00';

    document.getElementById('setupCount').textContent =
        count > 0 ? `${count} de ${slotKeys.length} componentes selecionados` : 'Nenhum componente selecionado';

    document.getElementById('btnAddAll').disabled = count === 0;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
