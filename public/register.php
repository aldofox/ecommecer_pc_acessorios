<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/CartModel.php';

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (strlen($name) < 3)         $error = 'Nome deve ter ao menos 3 caracteres.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'E-mail inválido.';
    elseif (strlen($password) < 6) $error = 'Senha deve ter ao menos 6 caracteres.';
    elseif ($password !== $confirm) $error = 'As senhas não coincidem.';
    else {
        $pdo  = db();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $error = 'Já existe uma conta com esse e-mail.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin, created_at)
                                   VALUES (:name, :email, :hash, 0, NOW())");
            $stmt->execute([':name' => $name, ':email' => $email, ':hash' => $hash]);

            $userId = (int)$pdo->lastInsertId();
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $name;

            CartModel::mergeCartOnLogin($userId, session_id());

            header('Location: /index.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar Conta — Loja Gamer</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-card">
        <a href="/index.php" class="auth-logo">Loja Gamer</a>
        <h1>Criar sua conta</h1>
        <p class="auth-sub">Rápido e gratuito</p>

        <?php if ($error): ?>
            <div class="auth-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="field-group">
                <label for="name">Nome Completo</label>
                <div class="field-icon">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="name" name="name"
                           placeholder="Seu nome completo" required autocomplete="name"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
            </div>

            <div class="field-group">
                <label for="email">E-mail</label>
                <div class="field-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email"
                           placeholder="seu@email.com" required autocomplete="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="field-group">
                <label for="password">Senha</label>
                <div class="field-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>
            </div>

            <div class="field-group">
                <label for="confirm">Confirmar Senha</label>
                <div class="field-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="confirm" name="confirm"
                           placeholder="Repita a senha" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-auth">
                <i class="fa-solid fa-user-plus"></i> Criar Conta
            </button>
        </form>

        <p class="auth-switch">
            Já tem conta? <a href="/login.php">Entrar</a>
        </p>
    </div>
</div>

<style>
.auth-body {
    background: linear-gradient(135deg, #0f0e17, #1a1828);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.auth-wrapper { width: 100%; max-width: 420px; }
.auth-card {
    background: #1a1828;
    border: 1px solid #2a2740;
    border-radius: 16px;
    padding: 40px;
    text-align: center;
}
.auth-logo {
    display: inline-block;
    font-size: 1.6rem;
    font-weight: 700;
    color: #00ffcc;
    text-decoration: none;
    letter-spacing: 1px;
    margin-bottom: 20px;
}
.auth-card h1 { font-size: 1.4rem; color: #e5e7eb; margin-bottom: 6px; }
.auth-sub { color: #6b7280; font-size: 0.9rem; margin-bottom: 24px; }
.auth-error {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    color: #f87171;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.88rem;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    text-align: left;
}
.auth-form { display: flex; flex-direction: column; gap: 14px; }
.field-group { text-align: left; }
.field-group label { display: block; font-size: 0.82rem; color: #9ca3af; margin-bottom: 6px; }
.field-icon { position: relative; }
.field-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #4b5563; font-size: 0.9rem; }
.field-icon input {
    width: 100%; padding: 10px 14px 10px 36px;
    background: rgba(255,255,255,0.05); border: 1px solid #2a2740;
    border-radius: 8px; color: #e5e7eb; font-size: 0.9rem; outline: none;
    transition: border-color 0.2s;
}
.field-icon input:focus { border-color: #00ffcc; }
.btn-auth { width: 100%; padding: 12px; margin-top: 6px; justify-content: center; }
.auth-switch { margin-top: 20px; font-size: 0.88rem; color: #6b7280; }
.auth-switch a { color: #00ffcc; text-decoration: none; font-weight: 600; }
.auth-switch a:hover { text-decoration: underline; }
</style>
</body>
</html>
