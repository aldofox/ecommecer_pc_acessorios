<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

$pdo      = db();
$category = trim($_GET['cat'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

$where = "WHERE bp.is_published = 1";
$params = [];
if ($category) {
    $where  .= " AND bp.category = :cat";
    $params[':cat'] = $category;
}

$total      = (int)$pdo->prepare("SELECT COUNT(*) FROM blog_posts bp $where")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM blog_posts bp $where")->execute($params) : 0;
$stmtCount  = $pdo->prepare("SELECT COUNT(*) FROM blog_posts bp $where");
$stmtCount->execute($params);
$total      = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT bp.*, u.name AS author_name
    FROM blog_posts bp
    JOIN users u ON u.id = bp.author_id
    $where
    ORDER BY bp.created_at DESC
    LIMIT :lim OFFSET :off
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Categorias disponíveis
$categories = $pdo->query("SELECT DISTINCT category FROM blog_posts WHERE is_published=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Post em destaque (primeiro da listagem)
$featured = !empty($posts) ? $posts[0] : null;
$rest     = array_slice($posts, 1);

$pageTitle = 'Blog — Dicas, Guias e Reviews — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/blog.css">

<main class="blog-page">
  <div class="container">

    <!-- Hero do blog -->
    <div class="blog-hero">
      <h1><i class="fa-solid fa-newspaper"></i> Blog Loja Gamer</h1>
      <p>Dicas, guias, reviews e novidades do mundo gamer e hardware.</p>

      <!-- Filtro por categoria -->
      <div class="blog-cats">
        <a href="/blog.php" class="cat-pill <?= $category==='' ? 'active':'' ?>">Todos</a>
        <?php foreach ($categories as $cat): ?>
          <a href="/blog.php?cat=<?= urlencode($cat) ?>"
             class="cat-pill <?= $category===$cat ? 'active':'' ?>"><?= htmlspecialchars($cat) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($featured && !$category): ?>
    <!-- Post em destaque -->
    <a href="/post.php?slug=<?= htmlspecialchars($featured['slug']) ?>" class="blog-featured">
      <div class="blog-featured-img">
        <?php if ($featured['cover_url']): ?>
          <img src="<?= BASE_URL ?>/<?= htmlspecialchars($featured['cover_url']) ?>" alt="<?= htmlspecialchars($featured['title']) ?>">
        <?php else: ?>
          <div class="blog-featured-placeholder"><i class="fa-solid fa-newspaper"></i></div>
        <?php endif; ?>
        <span class="blog-cat-tag"><?= htmlspecialchars($featured['category']) ?></span>
      </div>
      <div class="blog-featured-body">
        <span class="blog-featured-label"><i class="fa-solid fa-star"></i> Destaque</span>
        <h2><?= htmlspecialchars($featured['title']) ?></h2>
        <p><?= htmlspecialchars($featured['summary']) ?></p>
        <div class="blog-meta">
          <span><i class="fa-solid fa-user"></i> <?= htmlspecialchars($featured['author_name']) ?></span>
          <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($featured['created_at'])) ?></span>
        </div>
        <span class="blog-read-more">Ler artigo <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <?php endif; ?>

    <!-- Grid de posts -->
    <?php $gridPosts = $category ? $posts : $rest; ?>
    <?php if (!empty($gridPosts)): ?>
    <div class="blog-grid">
      <?php foreach ($gridPosts as $post): ?>
      <a href="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>" class="blog-card">
        <div class="blog-card-img">
          <?php if ($post['cover_url']): ?>
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($post['cover_url']) ?>"
                 alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
          <?php else: ?>
            <div class="blog-card-placeholder"><i class="fa-solid fa-newspaper"></i></div>
          <?php endif; ?>
          <span class="blog-cat-tag"><?= htmlspecialchars($post['category']) ?></span>
        </div>
        <div class="blog-card-body">
          <h3><?= htmlspecialchars($post['title']) ?></h3>
          <p><?= htmlspecialchars($post['summary']) ?></p>
          <div class="blog-meta">
            <span><i class="fa-solid fa-user"></i> <?= htmlspecialchars($post['author_name']) ?></span>
            <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
      <div class="blog-empty">
        <i class="fa-solid fa-newspaper"></i>
        <p>Nenhum artigo encontrado<?= $category ? " em \"$category\"" : '' ?>.</p>
        <a href="/blog.php" class="btn btn-secondary">Ver todos</a>
      </div>
    <?php endif; ?>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++):
        $params2 = array_filter(['cat'=>$category,'page'=>$i]); ?>
        <a href="/blog.php?<?= http_build_query($params2) ?>"
           class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
