<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

http_response_code(500);
$pageTitle = 'Erro interno — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="error-page">
  <div class="error-inner">
    <div class="error-code" style="-webkit-text-fill-color:unset;background:none;color:#ef4444">500</div>
    <div class="error-emoji">⚙️</div>
    <h1>Algo deu errado</h1>
    <p>Ocorreu um erro interno. Nossa equipe já foi notificada e estamos trabalhando para resolver.</p>
    <p style="font-size:.82rem">Se o problema persistir, <a href="/contact.php" style="color:var(--accent,#6c63ff)">entre em contato</a>.</p>

    <div class="error-links">
      <a href="/index.php" class="btn btn-primary">
        <i class="fa-solid fa-house"></i> Voltar para a Home
      </a>
      <a href="javascript:history.back()" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Página anterior
      </a>
    </div>
  </div>
</main>

<style>
.error-page { display:flex;align-items:center;justify-content:center;min-height:60vh;padding:40px 20px;text-align:center; }
.error-inner { max-width:480px;display:flex;flex-direction:column;align-items:center;gap:16px; }
.error-code { font-size:7rem;font-weight:900;line-height:1; }
.error-emoji { font-size:2.5rem;margin-top:-10px; }
.error-inner h1 { font-size:1.5rem;color:var(--text-primary,#f0eeff); }
.error-inner p  { color:var(--text-secondary,#a09cc0);font-size:.92rem;line-height:1.6; }
.error-links { display:flex;gap:10px;flex-wrap:wrap;justify-content:center; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
