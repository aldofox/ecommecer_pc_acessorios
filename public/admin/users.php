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

// ── CREATE ────────────────────────────────────────────────────────────────────
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin)
                           VALUES (:name, :email, :hash, :admin)");
    $stmt->execute([
        ':name'  => trim($_POST['name']),
        ':email' => trim($_POST['email']),
        ':hash'  => $hash,
        ':admin' => isset($_POST['is_admin']) ? 1 : 0,
    ]);
    header('Location: ' . BASE_URL . '/admin/users.php?msg=created');
    exit;
}

// ── EDIT (GET) ────────────────────────────────────────────────────────────────
$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT id, name, email, is_admin FROM users WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $editUser = $stmt->fetch();
    if (!$editUser) {
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }
}

// ── UPDATE (POST) ─────────────────────────────────────────────────────────────
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];

    // Só atualiza senha se foi preenchida
    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name=:name, email=:email, password_hash=:hash, is_admin=:admin WHERE id=:id");
        $stmt->execute([
            ':name'  => trim($_POST['name']),
            ':email' => trim($_POST['email']),
            ':hash'  => $hash,
            ':admin' => isset($_POST['is_admin']) ? 1 : 0,
            ':id'    => $userId,
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name=:name, email=:email, is_admin=:admin WHERE id=:id");
        $stmt->execute([
            ':name'  => trim($_POST['name']),
            ':email' => trim($_POST['email']),
            ':admin' => isset($_POST['is_admin']) ? 1 : 0,
            ':id'    => $userId,
        ]);
    }
    header('Location: ' . BASE_URL . '/admin/users.php?msg=updated');
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Impede auto-exclusão
    if ((int)$_POST['id'] !== (int)$_SESSION['admin_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => (int)$_POST['id']]);
    }
    header('Location: ' . BASE_URL . '/admin/users.php?msg=deleted');
    exit;
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$users = $pdo->query("SELECT id, name, email, is_admin, created_at FROM users ORDER BY created_at DESC")->fetchAll();

$msg = match ($_GET['msg'] ?? '') {
    'created' => 'Usuário criado com sucesso!',
    'updated' => 'Usuário atualizado!',
    'deleted' => 'Usuário excluído.',
    default   => ''
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuários — Admin Loja Gamer</title>
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
                <li><a href="<?= BASE_URL ?>/admin/banners.php"><i class="fa-solid fa-image"></i> Banners</a></li>
                <li><a href="<?= BASE_URL ?>/admin/users.php" class="active"><i class="fa-solid fa-users"></i> Usuários</a></li>
                <li><a href="<?= BASE_URL ?>/admin/orders.php"><i class="fa-solid fa-bag-shopping"></i> Pedidos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="content-admin">

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($action === 'create'): ?>
        <!-- ── CRIAR ──────────────────────────────────── -->
        <h2><i class="fa-solid fa-user-plus"></i> Criar Usuário</h2>
        <form method="post" action="<?= BASE_URL ?>/admin/users.php?action=create" class="admin-form">
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>E-mail:</label>
                <input type="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group form-check">
                <label><input type="checkbox" name="is_admin" value="1"> Administrador</label>
            </div>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Usuário
                </button>
            </div>
        </form>

        <?php elseif ($action === 'edit' && $editUser): ?>
        <!-- ── EDITAR ─────────────────────────────────── -->
        <h2><i class="fa-solid fa-user-pen"></i> Editar Usuário #<?= $editUser['id'] ?></h2>
        <form method="post" action="<?= BASE_URL ?>/admin/users.php?action=update" class="admin-form">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($editUser['name']) ?>">
            </div>
            <div class="form-group">
                <label>E-mail:</label>
                <input type="email" name="email" required
                       value="<?= htmlspecialchars($editUser['email']) ?>">
            </div>
            <div class="form-group">
                <label>Nova Senha: <small>(deixe em branco para não alterar)</small></label>
                <input type="password" name="password" minlength="6">
            </div>
            <div class="form-group form-check">
                <label>
                    <input type="checkbox" name="is_admin" value="1"
                           <?= $editUser['is_admin'] ? 'checked' : '' ?>> Administrador
                </label>
            </div>
            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
                </button>
            </div>
        </form>

        <?php else: ?>
        <!-- ── LISTAGEM ───────────────────────────────── -->
        <div class="page-header">
            <h2><i class="fa-solid fa-users"></i> Usuários (<?= count($users) ?>)</h2>
            <a href="<?= BASE_URL ?>/admin/users.php?action=create" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Novo Usuário
            </a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th>Cadastrado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php if ($u['is_admin']): ?>
                            <span class="badge badge-danger">Admin</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Cliente</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                    <td class="actions-cell">
                        <a href="<?= BASE_URL ?>/admin/users.php?action=edit&id=<?= $u['id'] ?>"
                           class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
                        <form method="post" action="<?= BASE_URL ?>/admin/users.php?action=delete"
                              style="display:inline"
                              onsubmit="return confirm('Excluir usuário «<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>»?')">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fa-solid fa-trash"></i> Excluir
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </main>
</div>
</body>
</html>
