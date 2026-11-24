<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

http_response_code(404);
$pageTitle = 'Página não encontrada — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="error-page">
  <div class="error-inner">
    <div class="error-code">404</div>
    <div class="error-emoji">🔍</div>
    <h1>Página não encontrada</h1>
    <p>Parece que a página que você está procurando foi removida, renomeada ou nunca existiu.</p>

    <!-- Busca integrada -->
    <form action="/search.php" method="get" class="error-search">
      <input type="text" name="q" placeholder="O que você está procurando?" autofocus>
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    <!-- Links úteis -->
    <div class="error-links">
      <a href="/index.php" class="btn btn-primary">
        <i class="fa-solid fa-house"></i> Ir para a Home
      </a>
      <a href="/ofertas.php" class="btn btn-secondary">
        <i class="fa-solid fa-fire"></i> Ver Ofertas
      </a>
      <a href="/blog.php" class="btn btn-secondary">
        <i class="fa-solid fa-newspaper"></i> Blog
      </a>
    </div>

    <!-- Categorias populares -->
    <div class="error-cats">
      <span>Categorias populares:</span>
      <a href="/category.php?slug=placas-de-video">Placas de Vídeo</a>
      <a href="/category.php?slug=monitores">Monitores</a>
      <a href="/category.php?slug=perifericos">Periféricos</a>
      <a href="/category.php?slug=processadores">Processadores</a>
    </div>
  </div>
</main>

<style>
.error-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  padding: 40px 20px;
  text-align: center;
}
.error-inner {
  max-width: 520px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}
.error-code {
  font-size: 7rem;
  font-weight: 900;
  line-height: 1;
  background: linear-gradient(135deg, var(--accent, #6c63ff), var(--neon, #00e5ff));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.error-emoji { font-size: 2.5rem; margin-top: -10px; }
.error-inner h1 { font-size: 1.5rem; color: var(--text-primary, #f0eeff); }
.error-inner p  { color: var(--text-secondary, #a09cc0); font-size: .92rem; line-height: 1.6; }

.error-search {
  display: flex;
  width: 100%;
  max-width: 380px;
  border: 1px solid var(--border-light, #2e2c45);
  border-radius: 10px;
  overflow: hidden;
}
.error-search input {
  flex: 1;
  padding: 11px 16px;
  background: var(--bg-card, #1a1828);
  border: none;
  color: var(--text-primary, #f0eeff);
  font-size: .9rem;
  outline: none;
}
.error-search button {
  padding: 11px 16px;
  background: var(--accent, #6c63ff);
  border: none;
  color: #fff;
  cursor: pointer;
  font-size: .95rem;
  transition: background .2s;
}
.error-search button:hover { background: var(--accent-hover, #5a52e0); }

.error-links {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}
.error-cats {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  font-size: .8rem;
  color: var(--text-muted, #5c5880);
  margin-top: 4px;
}
.error-cats a {
  color: var(--accent, #6c63ff);
  text-decoration: none;
  font-weight: 600;
  transition: opacity .2s;
}
.error-cats a:hover { opacity: .75; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
