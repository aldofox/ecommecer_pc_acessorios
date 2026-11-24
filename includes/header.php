<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/CartModel.php';

$userName  = $_SESSION['user_name'] ?? null;
$pageTitle = $pageTitle ?? 'Loja Gamer — Eletrônicos e Periféricos';

// Badge do carrinho
try {
    $cart      = CartModel::getOrCreateCart($_SESSION['user_id'] ?? null, session_id());
    $cartItems = CartModel::getItems((int)$cart['id']);
    $cartCount = array_sum(array_column($cartItems, 'quantity'));
} catch (\Throwable $e) {
    $cartCount = 0;
}

// Detectar categoria ativa
$currentSlug = $_GET['slug'] ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="base-url" content="<?= BASE_URL ?>">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/header.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/theme-light.css">
  <?php if (!empty($extraCss)): foreach ((array)$extraCss as $css): ?>
  <link rel="stylesheet" href="<?= BASE_URL ?>/<?= htmlspecialchars($css) ?>">
  <?php endforeach; endif; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<!-- Script inline: aplica tema ANTES do paint para evitar flash -->
<script>
(function(){
  var t = localStorage.getItem('loja_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>

<!-- Topbar informativa -->
<div class="header-topbar">
  <i class="fa-solid fa-truck-fast"></i>
  Frete grátis em compras acima de R$ 2.000 &nbsp;|&nbsp;
  <i class="fa-brands fa-pix"></i>
  Pix com 5% de desconto &nbsp;|&nbsp;
  <i class="fa-solid fa-shield-halved"></i>
  Compra 100% segura
</div>

<header class="site-header">
  <div class="header-inner">

    <!-- Logo -->
    <div class="logo">
      <a href="/index.php">
        <div class="logo-icon"><i class="fa-solid fa-bolt"></i></div>
        <span class="logo-text">Loja<span>Gamer</span></span>
      </a>
    </div>

    <!-- Busca -->
    <div class="header-search">
      <form action="/search.php" method="get">
        <input type="text" name="q" placeholder="Buscar produtos, marcas e categorias..." autocomplete="off">
        <button type="submit" class="header-search-btn" aria-label="Buscar">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </form>
    </div>

    <!-- Ações -->
    <div class="header-actions">

      <?php if ($userName): ?>
        <a href="/minha_conta.php" class="header-icon-btn active" title="Minha conta">
          <i class="fa-solid fa-user"></i>
          <span><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>
        </a>
        <div class="header-divider"></div>
        <!-- Sino de notificações -->
        <button class="header-icon-btn notif-btn" id="notifBtn" title="Notificações" aria-label="Notificações">
          <i class="fa-solid fa-bell"></i>
          <span class="cart-badge notif-badge" id="notifBadge" style="display:none">0</span>
          <span>Avisos</span>
        </button>
        <!-- Dropdown de notificações -->
        <div class="notif-dropdown" id="notifDropdown" style="display:none"></div>
        <div class="header-divider"></div>
        <a href="/cart.php" class="header-icon-btn" title="Carrinho">
          <i class="fa-solid fa-cart-shopping"></i>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount ?></span>
          <?php endif; ?>
          <span>Carrinho</span>
        </a>
        <div class="header-divider"></div>
        <a href="/logout.php" class="header-icon-btn" title="Sair">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>Sair</span>
        </a>
      <?php else: ?>
        <a href="/login.php" class="header-icon-btn" title="Entrar">
          <i class="fa-solid fa-user"></i>
          <span>Entrar</span>
        </a>
        <div class="header-divider"></div>
        <a href="/cart.php" class="header-icon-btn" title="Carrinho">
          <i class="fa-solid fa-cart-shopping"></i>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount ?></span>
          <?php endif; ?>
          <span>Carrinho</span>
        </a>
        <div class="header-divider"></div>
        <a href="/admin/index.php" class="header-icon-btn" title="Admin">
          <i class="fa-solid fa-shield-halved"></i>
          <span>Admin</span>
        </a>
      <?php endif; ?>

      <!-- Botão tema claro/escuro -->
      <div class="header-divider"></div>
      <button class="header-icon-btn theme-toggle" id="themeToggle"
              title="Alternar tema" aria-label="Alternar modo claro/escuro">
        <i class="fa-solid fa-moon" id="themeIcon"></i>
        <span id="themeLabel">Tema</span>
      </button>

    </div>

    <!-- Hamburguer mobile -->
    <button class="menu-toggle" id="menuToggle" aria-label="Menu" aria-expanded="false">
      <i class="fa-solid fa-bars"></i>
    </button>

  </div>
</header>

<!-- Nav de categorias + novas seções -->
<nav class="header-nav" aria-label="Navegação">
  <div class="header-nav-inner" id="mainMenu">
    <a href="/category.php?slug=perifericos" <?= $currentSlug==='perifericos' ? 'class="active"':''; ?>>
      <i class="fa-solid fa-keyboard"></i> Periféricos
    </a>
    <a href="/category.php?slug=placas-de-video" <?= $currentSlug==='placas-de-video' ? 'class="active"':''; ?>>
      <i class="fa-solid fa-video"></i> Placas de Vídeo
    </a>
    <a href="/category.php?slug=monitores" <?= $currentSlug==='monitores' ? 'class="active"':''; ?>>
      <i class="fa-solid fa-desktop"></i> Monitores
    </a>
    <a href="/category.php?slug=gabinetes" <?= $currentSlug==='gabinetes' ? 'class="active"':''; ?>>
      <i class="fa-solid fa-computer"></i> Gabinetes
    </a>
    <a href="/category.php?slug=processadores" <?= $currentSlug==='processadores' ? 'class="active"':''; ?>>
      <i class="fa-solid fa-microchip"></i> Processadores
    </a>
    <a href="/category.php?slug=headsets" <?= $currentSlug==='headsets' ? 'class="active"':''; ?>>
      <i class="fa-solid fa-headphones"></i> Headsets
    </a>
    <div class="nav-sep"></div>
    <a href="/ofertas.php" style="color:#ef4444">
      <i class="fa-solid fa-fire"></i> Ofertas
    </a>
    <a href="/setup.php">
      <i class="fa-solid fa-screwdriver-wrench"></i> Monte seu Setup
    </a>
    <a href="/blog.php">
      <i class="fa-solid fa-newspaper"></i> Blog
    </a>
  </div>
</nav>
