<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

$slug = trim($_GET['slug'] ?? '');
$pdo  = db();

$stmt = $pdo->prepare("
    SELECT bp.*, u.name AS author_name
    FROM blog_posts bp
    JOIN users u ON u.id = bp.author_id
    WHERE bp.slug = :slug AND bp.is_published = 1
");
$stmt->execute([':slug' => $slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Post não encontrado — Loja Gamer';
    require_once __DIR__ . '/../includes/header.php';
    echo '<main style="text-align:center;padding:80px 20px"><h2 style="color:var(--text-primary)">Post não encontrado 😢</h2><a href="/blog.php" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Voltar ao Blog</a></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Posts relacionados (mesma categoria, exceto o atual)
$stmtRel = $pdo->prepare("
    SELECT id, slug, title, summary, category, created_at
    FROM blog_posts
    WHERE is_published = 1 AND category = :cat AND slug != :slug
    ORDER BY created_at DESC
    LIMIT 3
");
$stmtRel->execute([':cat' => $post['category'], ':slug' => $slug]);
$related = $stmtRel->fetchAll();

$pageTitle  = htmlspecialchars($post['title']) . ' — Blog Loja Gamer';
$extraCss   = ['css/blog.css'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="post-page">
  <div class="post-inner">

    <!-- Breadcrumb -->
    <nav class="product-breadcrumb">
      <a href="/index.php"><i class="fa-solid fa-house"></i> Home</a>
      <span class="sep">/</span>
      <a href="/blog.php">Blog</a>
      <span class="sep">/</span>
      <a href="/blog.php?cat=<?= urlencode($post['category']) ?>"><?= htmlspecialchars($post['category']) ?></a>
      <span class="sep">/</span>
      <span><?= htmlspecialchars($post['title']) ?></span>
    </nav>

    <!-- Cabeçalho do post -->
    <header class="post-header">
      <span class="blog-cat-tag"><?= htmlspecialchars($post['category']) ?></span>
      <h1><?= htmlspecialchars($post['title']) ?></h1>
      <p class="post-summary"><?= htmlspecialchars($post['summary']) ?></p>
      <div class="post-meta">
        <div class="post-author">
          <div class="post-avatar"><?= strtoupper(mb_substr($post['author_name'], 0, 1)) ?></div>
          <div>
            <strong><?= htmlspecialchars($post['author_name']) ?></strong>
            <span><?= date('d \d\e F \d\e Y', strtotime($post['created_at'])) ?></span>
          </div>
        </div>
        <div class="post-share">
          <span>Compartilhar:</span>
          <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode('http://pc.local/post.php?slug='.$slug) ?>"
             target="_blank" rel="noopener" title="Twitter">
            <i class="fa-brands fa-x-twitter"></i>
          </a>
          <a href="https://wa.me/?text=<?= urlencode($post['title'].' - http://pc.local/post.php?slug='.$slug) ?>"
             target="_blank" rel="noopener" title="WhatsApp">
            <i class="fa-brands fa-whatsapp"></i>
          </a>
        </div>
      </div>
    </header>

    <!-- Imagem de capa -->
    <?php if ($post['cover_url']): ?>
    <div class="post-cover">
      <img src="<?= BASE_URL ?>/<?= htmlspecialchars($post['cover_url']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
    </div>
    <?php endif; ?>

    <!-- Conteúdo -->
    <article class="post-content">
      <?= $post['content'] ?>
    </article>

    <!-- Posts relacionados -->
    <?php if (!empty($related)): ?>
    <section class="post-related">
      <h3><i class="fa-solid fa-layer-group"></i> Artigos relacionados</h3>
      <div class="post-related-grid">
        <?php foreach ($related as $r): ?>
        <a href="/post.php?slug=<?= htmlspecialchars($r['slug']) ?>" class="related-post-card">
          <div class="rpc-cat"><?= htmlspecialchars($r['category']) ?></div>
          <h4><?= htmlspecialchars($r['title']) ?></h4>
          <p><?= htmlspecialchars($r['summary']) ?></p>
          <span class="rpc-date"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <div class="post-back">
      <a href="/blog.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Voltar ao Blog
      </a>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
