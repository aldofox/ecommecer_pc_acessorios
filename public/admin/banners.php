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
$errors = [];

// ── CREATE ───────────────────────────────────────────────────────────────────
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $imgPath = null;
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Tipo de imagem não permitido.';
        } else {
            $filename = uniqid('banner_') . '.' . $ext;
            $destDir  = BASE_PATH . '/public/images/banners/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $filename);
            $imgPath = 'images/banners/' . $filename;
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO banners
            (product_id, title, subtitle, image_url, sort_order, is_active)
            VALUES (:pid, :title, :subtitle, :img, :sort, :active)");
        $stmt->execute([
            ':pid'      => (int)$_POST['product_id'],
            ':title'    => trim($_POST['title']),
            ':subtitle' => trim($_POST['subtitle'] ?? ''),
            ':img'      => $imgPath,
            ':sort'     => (int)($_POST['sort_order'] ?? 0),
            ':active'   => isset($_POST['is_active']) ? 1 : 0,
        ]);
        header('Location: ' . BASE_URL . '/admin/banners.php?msg=created');
        exit;
    }
}

// ── EDIT (GET — carrega dados) ────────────────────────────────────────────────
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $editBanner = $stmt->fetch();
    if (!$editBanner) {
        header('Location: ' . BASE_URL . '/admin/banners.php');
        exit;
    }
}

// ── UPDATE (POST — salva edição) ──────────────────────────────────────────────
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $bannerId = (int)$_POST['banner_id'];
    $imgPath  = trim($_POST['current_image'] ?? '');

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = uniqid('banner_') . '.' . $ext;
            $destDir  = BASE_PATH . '/public/images/banners/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $filename);
            $imgPath = 'images/banners/' . $filename;
        }
    }

    $stmt = $pdo->prepare("UPDATE banners SET
        product_id = :pid,
        title      = :title,
        subtitle   = :subtitle,
        image_url  = :img,
        sort_order = :sort,
        is_active  = :active
        WHERE id   = :id");
    $stmt->execute([
        ':pid'      => (int)$_POST['product_id'],
        ':title'    => trim($_POST['title']),
        ':subtitle' => trim($_POST['subtitle'] ?? ''),
        ':img'      => $imgPath,
        ':sort'     => (int)($_POST['sort_order'] ?? 0),
        ':active'   => isset($_POST['is_active']) ? 1 : 0,
        ':id'       => $bannerId,
    ]);
    header('Location: ' . BASE_URL . '/admin/banners.php?msg=updated');
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM banners WHERE id = :id");
    $stmt->execute([':id' => (int)$_POST['id']]);
    header('Location: ' . BASE_URL . '/admin/banners.php?msg=deleted');
    exit;
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$banners  = $pdo->query("SELECT b.*, p.name AS product_name
                          FROM banners b
                          JOIN products p ON p.id = b.product_id
                          ORDER BY b.sort_order ASC")->fetchAll();

$products = $pdo->query("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();

$msg = match ($_GET['msg'] ?? '') {
    'created' => 'Banner criado com sucesso!',
    'updated' => 'Banner atualizado!',
    'deleted' => 'Banner excluído.',
    default   => ''
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin — Banners</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="container-admin">
    <aside class="sidebar-admin">
        <div class="sidebar-logo">Loja Gamer</div>
        <nav>
            <ul>
                <li><a href="<?= BASE_URL ?>/admin/index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/admin/products.php"><i class="fa-solid fa-box"></i> Produtos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/banners.php" class="active"><i class="fa-solid fa-image"></i> Banners</a></li>
                <li><a href="<?= BASE_URL ?>/admin/users.php"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <li><a href="<?= BASE_URL ?>/admin/orders.php"><i class="fa-solid fa-bag-shopping"></i> Pedidos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="content-admin">

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <p><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'create' || (!empty($errors) && $action === 'create')): ?>
        <!-- ── FORMULÁRIO CRIAR ────────────────────────── -->
        <h2><i class="fa-solid fa-plus"></i> Criar Banner</h2>
        <form method="post" action="<?= BASE_URL ?>/admin/banners.php?action=create" enctype="multipart/form-data" class="admin-form">

            <div class="form-group">
                <label>Produto vinculado:</label>
                <select name="product_id" required>
                    <option value="">— selecione —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Título:</label>
                <input type="text" name="title" required maxlength="160"
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Subtítulo:</label>
                <input type="text" name="subtitle" maxlength="220"
                       value="<?= htmlspecialchars($_POST['subtitle'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Imagem do banner:</label>
                <input type="file" name="image" accept="image/*" required>
                <small>Formatos: JPG, PNG, WebP, GIF</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ordem de exibição:</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
                <div class="form-group form-check">
                    <label>
                        <input type="checkbox" name="is_active" value="1" checked>
                        Ativo
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/banners.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Banner
                </button>
            </div>
        </form>

        <?php elseif ($action === 'edit' && !empty($editBanner)): ?>
        <!-- ── FORMULÁRIO EDITAR ───────────────────────── -->
        <h2><i class="fa-solid fa-pen-to-square"></i> Editar Banner #<?= $editBanner['id'] ?></h2>
        <form method="post" action="<?= BASE_URL ?>/admin/banners.php?action=update" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="banner_id" value="<?= $editBanner['id'] ?>">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editBanner['image_url'] ?? '') ?>">

            <div class="form-group">
                <label>Produto vinculado:</label>
                <select name="product_id" required>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $editBanner['product_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Título:</label>
                <input type="text" name="title" required maxlength="160"
                       value="<?= htmlspecialchars($editBanner['title']) ?>">
            </div>

            <div class="form-group">
                <label>Subtítulo:</label>
                <input type="text" name="subtitle" maxlength="220"
                       value="<?= htmlspecialchars($editBanner['subtitle'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Nova imagem (opcional):</label>
                <input type="file" name="image" accept="image/*">
                <?php if (!empty($editBanner['image_url'])): ?>
                    <div class="current-image">
                        <small>Atual:</small>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($editBanner['image_url']) ?>" alt="banner atual" style="max-height:80px;">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ordem:</label>
                    <input type="number" name="sort_order" value="<?= $editBanner['sort_order'] ?>" min="0">
                </div>
                <div class="form-group form-check">
                    <label>
                        <input type="checkbox" name="is_active" value="1"
                               <?= $editBanner['is_active'] ? 'checked' : '' ?>>
                        Ativo
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/banners.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
                </button>
            </div>
        </form>

        <?php else: ?>
        <!-- ── LISTAGEM ────────────────────────────────── -->
        <div class="page-header">
            <h2><i class="fa-solid fa-image"></i> Banners</h2>
            <a href="<?= BASE_URL ?>/admin/banners.php?action=create" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Novo Banner
            </a>
        </div>

        <?php if (empty($banners)): ?>
            <p class="empty-state">Nenhum banner cadastrado.</p>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Imagem</th>
                    <th>Produto</th>
                    <th>Título</th>
                    <th>Subtítulo</th>
                    <th>Ordem</th>
                    <th>Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($banners as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td>
                        <?php if ($b['image_url']): ?>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($b['image_url']) ?>"
                                 alt="<?= htmlspecialchars($b['title']) ?>"
                                 style="max-height:50px; border-radius:4px;">
                        <?php else: ?>
                            <span class="no-image">Sem imagem</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($b['product_name']) ?></td>
                    <td><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['subtitle'] ?? '') ?></td>
                    <td><?= $b['sort_order'] ?></td>
                    <td>
                        <span class="badge <?= $b['is_active'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $b['is_active'] ? 'Sim' : 'Não' ?>
                        </span>
                    </td>
                    <td class="actions-cell">
                        <a href="<?= BASE_URL ?>/admin/banners.php?action=edit&id=<?= $b['id'] ?>"
                           class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                        <form method="post" action="<?= BASE_URL ?>/admin/banners.php?action=delete"
                              style="display:inline"
                              onsubmit="return confirm('Excluir banner #<?= $b['id'] ?>?')">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fa-solid fa-trash"></i> Excluir
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
