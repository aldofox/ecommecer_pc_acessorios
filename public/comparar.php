<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

$pdo = db();

// IDs dos produtos a comparar (até 3, via query string ?ids=1,2,3)
$idsRaw  = trim($_GET['ids'] ?? '');
$ids     = array_filter(array_map('intval', explode(',', $idsRaw)));
$ids     = array_slice(array_unique($ids), 0, 3);

$products = [];
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name,
               COALESCE(AVG(r.rating),0) AS rating_avg,
               COUNT(r.id)               AS rating_count
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE p.id IN ($placeholders) AND p.is_active = 1
        GROUP BY p.id
    ");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    // Mantém ordem dos ids
    $indexed = array_column($rows, null, 'id');
    foreach ($ids as $id) {
        if (isset($indexed[$id])) $products[] = $indexed[$id];
    }
}

// Todos os produtos para o seletor
$allProducts = $pdo->query("
    SELECT p.id, p.name, c.name AS cat
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1
    ORDER BY c.name, p.name
")->fetchAll();

$pageTitle = 'Comparar Produtos — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/comparar.css">

<main class="comparar-page">
  <div class="container">

    <div class="comparar-header">
      <h1><i class="fa-solid fa-code-compare"></i> Comparar Produtos</h1>
      <p>Selecione até 3 produtos para comparar especificações, preço e avaliações lado a lado.</p>
    </div>

    <!-- Seletor de produtos -->
    <div class="comparar-selector">
      <?php for ($slot = 0; $slot < 3; $slot++): ?>
      <div class="selector-slot" id="slot-<?= $slot ?>">
        <div class="slot-card <?= isset($products[$slot]) ? 'filled' : '' ?>">
          <?php if (isset($products[$slot])): $p = $products[$slot]; ?>
            <button class="slot-remove" onclick="removeProduct(<?= $p['id'] ?>)" title="Remover">
              <i class="fa-solid fa-xmark"></i>
            </button>
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
                 alt="<?= htmlspecialchars($p['name']) ?>">
            <span><?= htmlspecialchars($p['name']) ?></span>
          <?php else: ?>
            <div class="slot-empty">
              <i class="fa-solid fa-plus"></i>
              <span>Adicionar produto</span>
            </div>
          <?php endif; ?>
        </div>

        <select class="slot-select" onchange="addProduct(this.value)">
          <option value="">— Escolha um produto —</option>
          <?php
          $currentCat = '';
          foreach ($allProducts as $ap):
            $alreadyIn = in_array($ap['id'], $ids);
            if ($ap['cat'] !== $currentCat) {
              if ($currentCat) echo '</optgroup>';
              echo '<optgroup label="' . htmlspecialchars($ap['cat']) . '">';
              $currentCat = $ap['cat'];
            }
          ?>
            <option value="<?= $ap['id'] ?>" <?= $alreadyIn ? 'disabled' : '' ?>>
              <?= htmlspecialchars($ap['name']) ?>
            </option>
          <?php endforeach; if ($currentCat) echo '</optgroup>'; ?>
        </select>
      </div>
      <?php endfor; ?>
    </div>

    <!-- Tabela de comparação -->
    <?php if (count($products) >= 2): ?>
    <div class="comparar-table-wrap">
      <table class="comparar-table">
        <thead>
          <tr>
            <th class="comparar-label-col">Especificação</th>
            <?php foreach ($products as $p): ?>
            <th>
              <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>">
                <span><?= htmlspecialchars($p['name']) ?></span>
              </a>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>

          <!-- Preço -->
          <tr class="row-highlight">
            <td class="comparar-label"><i class="fa-solid fa-tag"></i> Preço</td>
            <?php
            $prices = array_column($products, 'unit_price');
            $minPrice = min($prices);
            foreach ($products as $p):
              $best = $p['unit_price'] == $minPrice;
            ?>
            <td class="<?= $best ? 'best-value' : '' ?>">
              <?php if (!empty($p['compare_price']) && $p['compare_price'] > $p['unit_price']): ?>
                <small class="compare-old">R$ <?= number_format($p['compare_price'],2,',','.') ?></small>
              <?php endif; ?>
              <strong>R$ <?= number_format($p['unit_price'], 2, ',', '.') ?></strong>
              <?php if ($best): ?><span class="best-tag">Melhor preço</span><?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>

          <!-- Avaliação -->
          <tr>
            <td class="comparar-label"><i class="fa-solid fa-star"></i> Avaliação</td>
            <?php
            $ratings = array_column($products, 'rating_avg');
            $maxRating = max($ratings);
            foreach ($products as $p):
              $stars = min(5, round((float)$p['rating_avg']));
              $best  = $p['rating_avg'] == $maxRating && $maxRating > 0;
            ?>
            <td class="<?= $best ? 'best-value' : '' ?>">
              <div class="cmp-stars">
                <?php for ($i=1;$i<=5;$i++): ?>
                  <i class="fa-<?= $i<=$stars?'solid':'regular' ?> fa-star"></i>
                <?php endfor; ?>
              </div>
              <span><?= number_format($p['rating_avg'],1) ?>/5 (<?= (int)$p['rating_count'] ?>)</span>
            </td>
            <?php endforeach; ?>
          </tr>

          <!-- Categoria -->
          <tr>
            <td class="comparar-label"><i class="fa-solid fa-folder"></i> Categoria</td>
            <?php foreach ($products as $p): ?>
            <td><?= htmlspecialchars($p['category_name']) ?></td>
            <?php endforeach; ?>
          </tr>

          <!-- Estoque -->
          <tr>
            <td class="comparar-label"><i class="fa-solid fa-boxes-stacked"></i> Estoque</td>
            <?php foreach ($products as $p): ?>
            <td>
              <?php if ($p['stock_qty'] > 0): ?>
                <span class="cmp-ok"><i class="fa-solid fa-circle-check"></i> <?= $p['stock_qty'] ?> unidades</span>
              <?php else: ?>
                <span class="cmp-out"><i class="fa-solid fa-circle-xmark"></i> Esgotado</span>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>

          <!-- Especificações técnicas -->
          <?php
          // Coleta todas as specs de todos os produtos
          $allSpecKeys = [];
          $specsMap    = [];
          foreach ($products as $p) {
              $specs = !empty($p['specs']) ? json_decode($p['specs'], true) : [];
              $specsMap[$p['id']] = array_column($specs ?? [], 'value', 'label');
              foreach (array_keys($specsMap[$p['id']]) as $k) {
                  $allSpecKeys[$k] = true;
              }
          }
          ?>
          <?php if (!empty($allSpecKeys)): ?>
          <tr class="specs-header-row">
            <td colspan="<?= count($products) + 1 ?>">
              <strong><i class="fa-solid fa-list-check"></i> Especificações Técnicas</strong>
            </td>
          </tr>
          <?php foreach (array_keys($allSpecKeys) as $specLabel): ?>
          <tr>
            <td class="comparar-label"><?= htmlspecialchars($specLabel) ?></td>
            <?php foreach ($products as $p): ?>
            <td><?= htmlspecialchars($specsMap[$p['id']][$specLabel] ?? '—') ?></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>

          <!-- Ação -->
          <tr class="row-action">
            <td class="comparar-label"></td>
            <?php foreach ($products as $p): ?>
            <td>
              <?php if ($p['stock_qty'] > 0): ?>
                <form action="/cart.php?action=add" method="post">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="quantity"   value="1">
                  <button type="submit" class="btn btn-primary btn-sm" style="width:100%">
                    <i class="fa-solid fa-cart-plus"></i> Adicionar
                  </button>
                </form>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" style="width:100%" disabled>Esgotado</button>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>

        </tbody>
      </table>
    </div>
    <?php elseif (count($products) === 1): ?>
      <div class="comparar-hint">
        <i class="fa-solid fa-arrow-up"></i>
        Adicione mais um produto acima para iniciar a comparação.
      </div>
    <?php else: ?>
      <div class="comparar-hint">
        <i class="fa-solid fa-code-compare"></i>
        Selecione pelo menos 2 produtos nos campos acima para comparar.
      </div>
    <?php endif; ?>

  </div>
</main>

<script>
const currentIds = <?= json_encode($ids) ?>;

function addProduct(id) {
    id = parseInt(id);
    if (!id || currentIds.includes(id)) return;
    const newIds = [...currentIds, id].slice(0, 3);
    window.location.href = '/comparar.php?ids=' + newIds.join(',');
}

function removeProduct(id) {
    const newIds = currentIds.filter(i => i !== id);
    window.location.href = newIds.length ? '/comparar.php?ids=' + newIds.join(',') : '/comparar.php';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
