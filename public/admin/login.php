<?php
require_once __DIR__ . '/../../config/config.php'; 
require_once BASE_PATH . '/includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM users WHERE email = :e AND is_admin = 1");
    $stmt->execute([':e' => $email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['admin_id'] = $u['id'];
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }

    $error = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login Admin</title>
  <link rel="stylesheet" href="/css/login.css">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <h1 class="login-title">Painel Administrativo</h1>
      <p class="login-subtitle">Faça login para continuar</p>

      <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= BASE_URL ?>/admin/login.php" class="login-form">
        <div class="form-group">
          <label for="email">Email:</label>
          <input id="email" type="email" name="email" placeholder="Digite seu email" required>
        </div>

        <div class="form-group">
          <label for="password">Senha:</label>
          <input id="password" type="password" name="password" placeholder="Digite sua senha" required>
        </div>

        <button type="submit" class="btn btn-primary">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>

