<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

$pdo     = db();
$orderBy = $_GET['order']    ?? 'discount';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 16;
$offset  = ($page - 1) * $perPage;

$orderMap = [
    'discount'   => '((p.compare_price - p.unit_price) / p.compare_price) DESC',
    'price_asc'  => 'p.unit_price ASC',
    'price_desc' => 'p.unit_price DESC',
    'rating'     => 'rating_avg DESC',
];
$orderSql = $orderMap[$orderBy] ?? $orderMap['discount'];

$total = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE compare_price IS NOT NULL AND compare_price > unit_price AND is_active = 1")->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT p.id, p.slug, p.name, p.image_url, p.unit_price, p.compare_price,
           p.stock_qty, p.short_description,
           ROUND((1 - p.unit_price / p.compare_price) * 100) AS discount_pct,
           (p.compare_price - p.unit_price)                  AS saving,
           COALESCE(AVG(r.rating), 0) AS rating_avg,
           COUNT(r.id)                AS rating_count
    FROM products p
    LEFT JOIN reviews r ON r.product_id = p.id
    WHERE p.compare_price IS NOT NULL AND p.compare_price > p.unit_price AND p.is_active = 1
    GROUP BY p.id
    ORDER BY $orderSql
    LIMIT :lim OFFSET :off
");
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$maxSaving  = $pdo->query("SELECT MAX(compare_price - unit_price) FROM products WHERE compare_price > unit_price AND is_active=1")->fetchColumn();
$maxDisc    = $pdo->query("SELECT MAX(ROUND((1-unit_price/compare_price)*100)) FROM products WHERE compare_price > unit_price AND is_active=1")->fetchColumn();

$pageTitle = 'Ofertas e Promoções — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/ofertas.css">

<main class="ofertas-page">

  <!-- Hero de ofertas -->
  <div class="ofertas-hero">
    <div class="ofertas-hero-content">
      <span class="hero-tag"><i class="fa-solid fa-bolt"></i> Promoções imperdíveis</span>
      <h1>Ofertas da Semana</h1>
      <p>Economize até <strong>R$ <?= number_format($maxSaving, 2, ',', '.') ?></strong> e até <strong><?= $maxDisc ?>% de desconto</strong> nos melhores produtos.</p>
    </div>
    <div class="ofertas-hero-stats">
      <div class="hero-stat">
        <span class="stat-n"><?= $total ?></span>
        <span class="stat-l">Produtos em oferta</span>
      </div>
      <div class="hero-stat">
        <span class="stat-n"><?= $maxDisc ?>%</span>
        <span class="stat-l">Desconto máximo</span>
      </div>
      <div class="hero-stat">
        <span class="stat-n">R$ <?= number_format($maxSaving, 0, ',', '.') ?></span>
        <span class="stat-l">Maior economia</span>
      </div>
    </div>
  </div>

  <!-- Filtro de ordenação -->
  <div class="ofertas-toolbar">
    <span class="toolbar-count"><?= $total ?> oferta<?= $total !== 1 ? 's' : '' ?></span>
    <div class="toolbar-order">
      <span>Ordenar:</span>
      <?php
      $orders = ['discount'=>'Maior desconto','price_asc'=>'Menor preço','price_desc'=>'Maior preço','rating'=>'Avaliação'];
      foreach ($orders as $k => $v):
      ?>
        <a href="/ofertas.php?order=<?= $k ?>&page=1"
           class="order-pill <?= $orderBy === $k ? 'active' : '' ?>"><?= $v ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Grade de produtos -->
  <div class="ofertas-grid">
    <?php foreach ($products as $p):
      $stars   = min(5, round((float)$p['rating_avg']));
      $inStock = (int)$p['stock_qty'] > 0;
    ?>
    <article class="oferta-card <?= !$inStock ? 'esgotado' : '' ?>">
      <!-- Badge de desconto -->
      <div class="oferta-discount-badge">-<?= $p['discount_pct'] ?>%</div>

      <!-- Imagem -->
      <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="oferta-img">
        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
             alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
        <?php if (!$inStock): ?>
          <div class="oferta-esgotado-overlay">Esgotado</div>
        <?php endif; ?>
      </a>

      <div class="oferta-body">
        <h3 class="oferta-name">
          <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>">
            <?= htmlspecialchars($p['name']) ?>
          </a>
        </h3>

        <!-- Estrelas -->
        <div class="oferta-stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
          <?php endfor; ?>
          <span>(<?= (int)$p['rating_count'] ?>)</span>
        </div>

        <!-- Preços -->
        <div class="oferta-pricing">
          <div class="oferta-from">
            De <span>R$ <?= number_format($p['compare_price'], 2, ',', '.') ?></span>
          </div>
          <div class="oferta-price">R$ <?= number_format($p['unit_price'], 2, ',', '.') ?></div>
          <div class="oferta-saving">
            <i class="fa-solid fa-piggy-bank"></i>
            Economize R$ <?= number_format($p['saving'], 2, ',', '.') ?>
          </div>
        </div>

        <!-- Ação -->
        <?php if ($inStock): ?>
          <form action="/cart.php?action=add" method="post">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="quantity"   value="1">
            <button type="submit" class="oferta-btn">
              <i class="fa-solid fa-cart-plus"></i> Aproveitar oferta
            </button>
          </form>
        <?php else: ?>
          <button class="oferta-btn oferta-btn-dis" disabled>Esgotado</button>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <!-- Paginação -->
  <?php if ($totalPages > 1): ?>
  <nav class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="/ofertas.php?order=<?= $orderBy ?>&page=<?= $i ?>"
         class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
