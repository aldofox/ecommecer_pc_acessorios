<?php
require_once __DIR__ . '/../models/ProductModel.php';

$q        = trim($_GET['q'] ?? '');
$orderBy  = $_GET['order']     ?? 'relevance';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : 99999;
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$products    = [];
$totalFound  = 0;
$totalPages  = 1;

if ($q !== '') {
    $pdo = db();
    $like = '%' . $q . '%';

    $orderMap = [
        'relevance'  => 'p.featured DESC, p.created_at DESC',
        'price_asc'  => 'p.unit_price ASC',
        'price_desc' => 'p.unit_price DESC',
        'rating'     => 'rating_avg DESC',
    ];
    $orderSql = $orderMap[$orderBy] ?? $orderMap['relevance'];

    // Total
    $stmtCount = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id)
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1
          AND (p.name LIKE :q OR p.short_description LIKE :q2 OR p.description LIKE :q3 OR c.name LIKE :q4)
          AND p.unit_price BETWEEN :min AND :max
    ");
    $stmtCount->execute([':q'=>$like,':q2'=>$like,':q3'=>$like,':q4'=>$like,':min'=>$minPrice,':max'=>$maxPrice]);
    $totalFound = (int)$stmtCount->fetchColumn();
    $totalPages = max(1, (int)ceil($totalFound / $perPage));

    // Resultados
    $stmt = $pdo->prepare("
        SELECT p.id, p.slug, p.name, p.image_url, p.unit_price,
               p.compare_price, p.short_description, p.stock_qty, p.featured,
               c.name AS category_name, c.slug AS category_slug,
               COALESCE(AVG(r.rating), 0) AS rating_avg,
               COUNT(r.id)               AS rating_count
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE p.is_active = 1
          AND (p.name LIKE :q OR p.short_description LIKE :q2 OR p.description LIKE :q3 OR c.name LIKE :q4)
          AND p.unit_price BETWEEN :min AND :max
        GROUP BY p.id
        ORDER BY $orderSql
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':q',   $like);
    $stmt->bindValue(':q2',  $like);
    $stmt->bindValue(':q3',  $like);
    $stmt->bindValue(':q4',  $like);
    $stmt->bindValue(':min', $minPrice);
    $stmt->bindValue(':max', $maxPrice);
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
}

$pageTitle = $q !== '' ? 'Busca: ' . htmlspecialchars($q) . ' — Loja Gamer' : 'Busca — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/category.css">

<main class="category-page">

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/index.php"><i class="fa-solid fa-house"></i> Home</a>
    <span class="sep">/</span>
    <span>Busca<?= $q !== '' ? ': "' . htmlspecialchars($q) . '"' : '' ?></span>
  </nav>

  <!-- Formulário de busca destacado -->
  <div class="search-hero">
    <form method="get" action="/search.php" class="search-form-big">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
             placeholder="O que você está procurando?" autofocus>
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    </form>
  </div>

  <?php if ($q === ''): ?>
    <!-- Estado inicial -->
    <div class="search-empty-state">
      <i class="fa-solid fa-magnifying-glass"></i>
      <p>Digite o nome do produto, marca ou categoria para buscar.</p>
      <div class="search-suggestions">
        <span>Sugestões:</span>
        <a href="/search.php?q=rtx">RTX 4070</a>
        <a href="/search.php?q=teclado">Teclado</a>
        <a href="/search.php?q=monitor">Monitor</a>
        <a href="/search.php?q=ryzen">Ryzen</a>
        <a href="/search.php?q=mouse">Mouse</a>
      </div>
    </div>

  <?php elseif ($totalFound === 0): ?>
    <!-- Sem resultados -->
    <div class="search-empty-state">
      <i class="fa-solid fa-box-open"></i>
      <p>Nenhum resultado para <strong>"<?= htmlspecialchars($q) ?>"</strong>.</p>
      <div class="search-suggestions">
        <span>Tente:</span>
        <a href="/search.php?q=rtx">RTX 4070</a>
        <a href="/search.php?q=teclado">Teclado</a>
        <a href="/search.php?q=monitor">Monitor</a>
      </div>
    </div>

  <?php else: ?>
  <!-- Resultados -->
  <div class="category-layout">

    <!-- Filtros -->
    <aside class="filters">
      <h3><i class="fa-solid fa-sliders"></i> Filtros</h3>
      <form method="get" action="/search.php">
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">

        <div class="filter-group">
          <label>Preço mínimo (R$)</label>
          <input type="number" name="min_price" min="0" step="10"
                 value="<?= $minPrice > 0 ? $minPrice : '' ?>" placeholder="0">
        </div>
        <div class="filter-group">
          <label>Preço máximo (R$)</label>
          <input type="number" name="max_price" min="0" step="10"
                 value="<?= $maxPrice < 99999 ? $maxPrice : '' ?>" placeholder="Sem limite">
        </div>
        <div class="filter-group">
          <label>Ordenar por</label>
          <select name="order">
            <option value="relevance"  <?= $orderBy==='relevance'  ? 'selected':'' ?>>Mais relevantes</option>
            <option value="price_asc"  <?= $orderBy==='price_asc'  ? 'selected':'' ?>>Menor preço</option>
            <option value="price_desc" <?= $orderBy==='price_desc' ? 'selected':'' ?>>Maior preço</option>
            <option value="rating"     <?= $orderBy==='rating'     ? 'selected':'' ?>>Mais avaliados</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">
          <i class="fa-solid fa-magnifying-glass"></i> Filtrar
        </button>
        <a href="/search.php?q=<?= urlencode($q) ?>" class="btn btn-secondary btn-block" style="margin-top:8px">
          Limpar filtros
        </a>
      </form>
    </aside>

    <!-- Grade de produtos -->
    <section class="products-area">
      <div class="category-header">
        <h1>Resultados para "<?= htmlspecialchars($q) ?>"</h1>
        <span class="result-count"><?= $totalFound ?> produto<?= $totalFound !== 1 ? 's' : '' ?> encontrado<?= $totalFound !== 1 ? 's' : '' ?></span>
      </div>

      <div class="products-grid">
        <?php foreach ($products as $p):
          $stars   = min(5, round((float)$p['rating_avg']));
          $inStock = (int)$p['stock_qty'] > 0;
          $hasDiscount = !empty($p['compare_price']) && $p['compare_price'] > $p['unit_price'];
          $discountPct = $hasDiscount ? round((1 - $p['unit_price'] / $p['compare_price']) * 100) : 0;
        ?>
          <article class="product-card <?= !$inStock ? 'out-of-stock' : '' ?>">
            <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="card-image">
              <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
                   alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
              <?php if (!$inStock): ?>
                <span class="badge-sold-out">Esgotado</span>
              <?php elseif ($hasDiscount): ?>
                <span class="badge-discount">-<?= $discountPct ?>%</span>
              <?php endif; ?>
            </a>
            <div class="card-body">
              <div style="font-size:.72rem;color:#6c63ff;font-weight:600;margin-bottom:3px">
                <?= htmlspecialchars($p['category_name']) ?>
              </div>
              <h3 class="card-title">
                <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>">
                  <?= htmlspecialchars($p['name']) ?>
                </a>
              </h3>
              <p class="card-desc"><?= htmlspecialchars($p['short_description']) ?></p>

              <div class="card-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                <?php endfor; ?>
                <span>(<?= (int)$p['rating_count'] ?>)</span>
              </div>

              <?php if ($hasDiscount): ?>
                <div style="font-size:.78rem;color:#5c5880;text-decoration:line-through;margin-bottom:2px">
                  R$ <?= number_format($p['compare_price'], 2, ',', '.') ?>
                </div>
              <?php endif; ?>
              <div class="card-price">R$ <?= number_format($p['unit_price'], 2, ',', '.') ?></div>

              <?php if ($inStock): ?>
                <form action="/cart.php?action=add" method="post">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-cart-plus"></i> Adicionar ao Carrinho
                  </button>
                </form>
              <?php else: ?>
                <button class="btn btn-secondary btn-block" disabled>Esgotado</button>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- Paginação -->
      <?php if ($totalPages > 1): ?>
        <nav class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++):
            $params = array_merge($_GET, ['page' => $i]);
          ?>
            <a href="/search.php?<?= http_build_query($params) ?>"
               class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    </section>
  </div>
  <?php endif; ?>

</main>

<style>
/* ── Busca hero ──────────────────────────────── */
.search-hero {
  margin: 20px 0 24px;
}
.search-form-big {
  display: flex;
  max-width: 680px;
  margin: 0 auto;
  gap: 0;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #2e2c45;
  box-shadow: 0 0 0 3px rgba(108,99,255,.1);
}
.search-form-big input {
  flex: 1;
  padding: 13px 18px;
  background: #1a1828;
  border: none;
  color: #f0eeff;
  font-size: .95rem;
  outline: none;
}
.search-form-big input::placeholder { color: #5c5880; }
.search-form-big button {
  padding: 13px 24px;
  background: #6c63ff;
  color: #fff;
  border: none;
  font-size: .9rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 7px;
  transition: background .2s;
  white-space: nowrap;
}
.search-form-big button:hover { background: #5a52e0; }

/* ── Estado vazio ────────────────────────────── */
.search-empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #5c5880;
}
.search-empty-state i {
  font-size: 3.5rem;
  color: #2e2c45;
  display: block;
  margin-bottom: 14px;
}
.search-empty-state p {
  font-size: 1rem;
  color: #a09cc0;
  margin-bottom: 20px;
}
.search-empty-state p strong { color: #f0eeff; }
.search-suggestions {
  display: flex;
  gap: 8px;
  justify-content: center;
  flex-wrap: wrap;
  align-items: center;
}
.search-suggestions span {
  font-size: .82rem;
  color: #5c5880;
}
.search-suggestions a {
  display: inline-block;
  padding: 5px 14px;
  background: #1a1828;
  border: 1px solid #2e2c45;
  border-radius: 99px;
  color: #a09cc0;
  font-size: .82rem;
  text-decoration: none;
  transition: border-color .2s, color .2s;
}
.search-suggestions a:hover {
  border-color: #6c63ff;
  color: #6c63ff;
}
/* badge de desconto na busca */
.badge-discount {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #ef4444;
  color: #fff;
  font-size: .68rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 99px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
