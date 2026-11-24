<?php
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$pdo    = db();
$action = $_GET['action'] ?? 'list';

/* ── Helper: slug ─────────────────────────────────── */
function makeSlug(string $str): string {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[áàâãä]/u','a',$str);
    $str = preg_replace('/[éèêë]/u', 'e',$str);
    $str = preg_replace('/[íìîï]/u', 'i',$str);
    $str = preg_replace('/[óòôõö]/u','o',$str);
    $str = preg_replace('/[úùûü]/u', 'u',$str);
    $str = preg_replace('/[ç]/u',    'c',$str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    return trim(preg_replace('/[\s-]+/', '-', $str), '-');
}

/* ── CREATE ───────────────────────────────────────── */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = makeSlug($_POST['slug'] ?: $_POST['title']);
    $pdo->prepare("INSERT INTO blog_posts
        (author_id, title, slug, summary, content, cover_url, category, is_published)
        VALUES (:aid, :title, :slug, :summary, :content, :cover, :cat, :pub)")
    ->execute([
        ':aid'     => (int)$_SESSION['admin_id'],
        ':title'   => trim($_POST['title']),
        ':slug'    => $slug,
        ':summary' => trim($_POST['summary']),
        ':content' => $_POST['content'],
        ':cover'   => trim($_POST['cover_url'] ?? ''),
        ':cat'     => trim($_POST['category'] ?? 'Geral'),
        ':pub'     => isset($_POST['is_published']) ? 1 : 0,
    ]);
    header('Location: ' . BASE_URL . '/admin/blog.php?msg=created');
    exit;
}

/* ── EDIT (GET) ───────────────────────────────────── */
$editPost = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $editPost = $stmt->fetch();
    if (!$editPost) { header('Location: ' . BASE_URL . '/admin/blog.php'); exit; }
}

/* ── UPDATE (POST) ────────────────────────────────── */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = makeSlug($_POST['slug'] ?: $_POST['title']);
    $pdo->prepare("UPDATE blog_posts SET
        title=:title, slug=:slug, summary=:summary, content=:content,
        cover_url=:cover, category=:cat, is_published=:pub
        WHERE id=:id")
    ->execute([
        ':title'   => trim($_POST['title']),
        ':slug'    => $slug,
        ':summary' => trim($_POST['summary']),
        ':content' => $_POST['content'],
        ':cover'   => trim($_POST['cover_url'] ?? ''),
        ':cat'     => trim($_POST['category'] ?? 'Geral'),
        ':pub'     => isset($_POST['is_published']) ? 1 : 0,
        ':id'      => (int)$_POST['post_id'],
    ]);
    header('Location: ' . BASE_URL . '/admin/blog.php?msg=updated');
    exit;
}

/* ── DELETE ───────────────────────────────────────── */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pdo->prepare("DELETE FROM blog_posts WHERE id = :id")
        ->execute([':id' => (int)$_POST['id']]);
    header('Location: ' . BASE_URL . '/admin/blog.php?msg=deleted');
    exit;
}

/* ── LIST ─────────────────────────────────────────── */
$posts = $pdo->query("
    SELECT bp.*, u.name AS author_name
    FROM blog_posts bp
    JOIN users u ON u.id = bp.author_id
    ORDER BY bp.created_at DESC
")->fetchAll();

$categories = ['Tutoriais','Reviews','Guias','Notícias','Geral'];
$msg = match ($_GET['msg'] ?? '') {
    'created' => 'Post criado!', 'updated' => 'Post atualizado!',
    'deleted' => 'Post excluído.', default => ''
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog — Admin Loja Gamer</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="container-admin">
    <aside class="sidebar-admin">
        <div class="sidebar-logo">Loja Gamer</div>
        <nav><ul>
            <li><a href="<?= BASE_URL ?>/admin/index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/admin/products.php"><i class="fa-solid fa-box"></i> Produtos</a></li>
            <li><a href="<?= BASE_URL ?>/admin/banners.php"><i class="fa-solid fa-image"></i> Banners</a></li>
            <li><a href="<?= BASE_URL ?>/admin/users.php"><i class="fa-solid fa-users"></i> Usuários</a></li>
            <li><a href="<?= BASE_URL ?>/admin/orders.php"><i class="fa-solid fa-bag-shopping"></i> Pedidos</a></li>
            <li><a href="<?= BASE_URL ?>/admin/blog.php" class="active"><i class="fa-solid fa-newspaper"></i> Blog</a></li>
            <li><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
        </ul></nav>
    </aside>

    <main class="content-admin">
        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($action === 'create'): ?>
        <!-- CRIAR -->
        <h2><i class="fa-solid fa-plus"></i> Novo Post</h2>
        <form method="post" action="<?= BASE_URL ?>/admin/blog.php?action=create" class="admin-form">
            <?php include __DIR__ . '/../../includes/blog_form_fields.php'; ?>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/blog.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Publicar</button>
            </div>
        </form>

        <?php elseif ($action === 'edit' && $editPost): ?>
        <!-- EDITAR -->
        <h2><i class="fa-solid fa-pen-to-square"></i> Editar Post #<?= $editPost['id'] ?></h2>
        <form method="post" action="<?= BASE_URL ?>/admin/blog.php?action=update" class="admin-form">
            <input type="hidden" name="post_id" value="<?= $editPost['id'] ?>">
            <?php include __DIR__ . '/../../includes/blog_form_fields.php'; ?>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/blog.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            </div>
        </form>

        <?php else: ?>
        <!-- LISTAGEM -->
        <div class="page-header">
            <h2><i class="fa-solid fa-newspaper"></i> Blog (<?= count($posts) ?> posts)</h2>
            <a href="<?= BASE_URL ?>/admin/blog.php?action=create" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Novo Post
            </a>
        </div>

        <?php if (empty($posts)): ?>
            <p class="empty-state">Nenhum post cadastrado ainda.</p>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>#</th><th>Título</th><th>Categoria</th><th>Autor</th><th>Status</th><th>Data</th><th>Ações</th></tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td>
                    <strong><?= htmlspecialchars($p['title']) ?></strong>
                    <br><small style="color:#5c5880"><?= htmlspecialchars($p['slug']) ?></small>
                </td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td><?= htmlspecialchars($p['author_name']) ?></td>
                <td>
                    <?php if ($p['is_published']): ?>
                        <span class="badge badge-success">Publicado</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Rascunho</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                <td class="actions-cell">
                    <a href="/post.php?slug=<?= $p['slug'] ?>" target="_blank" class="btn btn-sm btn-secondary" title="Ver no site">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/blog.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-pen"></i> Editar
                    </a>
                    <form method="post" action="<?= BASE_URL ?>/admin/blog.php?action=delete" style="display:inline"
                          onsubmit="return confirm('Excluir «<?= htmlspecialchars($p['title'], ENT_QUOTES) ?>»?')">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
