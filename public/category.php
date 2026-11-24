<?php
require_once __DIR__ . '/../models/ProductModel.php';

// Valida slug
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: /index.php');
    exit;
}

$pdo = db();

// Busca categoria pelo slug
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = :slug");
$stmt->execute([':slug' => $slug]);
$categoria = $stmt->fetch();

if (!$categoria) {
    http_response_code(404);
    $pageTitle = 'Categoria não encontrada — Loja Gamer';
    require_once __DIR__ . '/../includes/header.php';
    echo '<main style="padding:60px;text-align:center"><h2>Categoria não encontrada 😢</h2><a href="/index.php" class="btn btn-primary">Voltar</a></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Parâmetros de filtro e ordenação
$orderBy  = $_GET['order'] ?? 'recent';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 99999;
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$orderMap = [
    'recent'    => 'p.created_at DESC',
    'price_asc' => 'p.unit_price ASC',
    'price_desc'=> 'p.unit_price DESC',
    'rating'    => 'rating_avg DESC',
];
$orderSql = $orderMap[$orderBy] ?? $orderMap['recent'];

// Total para paginação
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) FROM products p
    WHERE p.category_id = :cid
      AND p.is_active = 1
      AND p.unit_price BETWEEN :min AND :max
");
$stmtCount->execute([':cid' => $categoria['id'], ':min' => $minPrice, ':max' => $maxPrice]);
$totalProducts = (int)$stmtCount->fetchColumn();
$totalPages    = max(1, (int)ceil($totalProducts / $perPage));

// Busca produtos com avaliação média
$sql = "
    SELECT p.id, p.slug, p.name, p.image_url, p.unit_price, p.short_description, p.stock_qty,
           COALESCE(AVG(r.rating), 0) AS rating_avg,
           COUNT(r.id) AS rating_count
    FROM products p
    LEFT JOIN reviews r ON r.product_id = p.id
    WHERE p.category_id = :cid
      AND p.is_active = 1
      AND p.unit_price BETWEEN :min AND :max
    GROUP BY p.id
    ORDER BY $orderSql
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':cid',    $categoria['id'], PDO::PARAM_INT);
$stmt->bindValue(':min',    $minPrice);
$stmt->bindValue(':max',    $maxPrice);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll();

$pageTitle = htmlspecialchars($categoria['name']) . ' — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/category.css">
<main class="category-page">

    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Navegação">
        <a href="/index.php"><i class="fa-solid fa-house"></i> Home</a>
        <span class="sep">/</span>
        <span><?= htmlspecialchars($categoria['name']) ?></span>
    </nav>

    <div class="category-layout">

        <!-- ── Filtros ───────────────────────────────── -->
        <aside class="filters">
            <h3><i class="fa-solid fa-sliders"></i> Filtros</h3>
            <form method="get" action="/category.php" id="filterForm">
                <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">

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
                        <option value="recent"     <?= $orderBy==='recent'     ? 'selected':'' ?>>Mais recentes</option>
                        <option value="price_asc"  <?= $orderBy==='price_asc'  ? 'selected':'' ?>>Menor preço</option>
                        <option value="price_desc" <?= $orderBy==='price_desc' ? 'selected':'' ?>>Maior preço</option>
                        <option value="rating"     <?= $orderBy==='rating'     ? 'selected':'' ?>>Mais avaliados</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-magnifying-glass"></i> Aplicar
                </button>
                <a href="/category.php?slug=<?= htmlspecialchars($slug) ?>"
                   class="btn btn-secondary btn-block">Limpar filtros</a>
            </form>
        </aside>

        <!-- ── Grade de Produtos ─────────────────────── -->
        <section class="products-area">
            <div class="category-header">
                <h1><?= htmlspecialchars($categoria['name']) ?></h1>
                <span class="result-count"><?= $totalProducts ?> produto<?= $totalProducts !== 1 ? 's' : '' ?> encontrado<?= $totalProducts !== 1 ? 's' : '' ?></span>
            </div>

            <?php if (empty($produtos)): ?>
                <div class="empty-category">
                    <i class="fa-solid fa-box-open"></i>
                    <p>Nenhum produto encontrado com esses filtros.</p>
                    <a href="/category.php?slug=<?= htmlspecialchars($slug) ?>" class="btn btn-secondary">Ver todos</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($produtos as $p):
                        $stars     = round((float)$p['rating_avg']);
                        $inStock   = (int)$p['stock_qty'] > 0;
                    ?>
                        <article class="product-card <?= !$inStock ? 'out-of-stock' : '' ?>">
                            <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="card-image">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
                                     alt="<?= htmlspecialchars($p['name']) ?>"
                                     loading="lazy">
                                <?php if (!$inStock): ?>
                                    <span class="badge-sold-out">Esgotado</span>
                                <?php endif; ?>
                            </a>
                            <div class="card-body">
                                <h3 class="card-title">
                                    <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </a>
                                </h3>
                                <p class="card-desc"><?= htmlspecialchars($p['short_description']) ?></p>

                                <!-- Estrelas -->
                                <div class="card-rating" title="<?= number_format($p['rating_avg'],1) ?>/5">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                                    <?php endfor; ?>
                                    <span>(<?= (int)$p['rating_count'] ?>)</span>
                                </div>

                                <div class="card-price">
                                    R$ <?= number_format($p['unit_price'], 2, ',', '.') ?>
                                </div>

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

                <!-- ── Paginação ── -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Páginas">
                        <?php for ($i = 1; $i <= $totalPages; $i++):
                            $params = array_merge($_GET, ['page' => $i]);
                        ?>
                            <a href="/category.php?<?= http_build_query($params) ?>"
                               class="page-btn <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
