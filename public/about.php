<?php
$pageTitle = 'Sobre Nós — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="static-page">
    <div class="static-container">
        <h1><i class="fa-solid fa-store"></i> Sobre a Loja Gamer</h1>
        <p>A <strong>Loja Gamer</strong> é especializada em hardware, periféricos e tudo que um gamer precisa para montar o setup dos sonhos.</p>
        <p>Fundada por entusiastas de tecnologia, nossa missão é oferecer os melhores produtos com preços competitivos e entrega rápida.</p>
        <h2>Por que nos escolher?</h2>
        <ul>
            <li><i class="fa-solid fa-check"></i> Produtos originais com garantia</li>
            <li><i class="fa-solid fa-check"></i> Entrega para todo o Brasil</li>
            <li><i class="fa-solid fa-check"></i> Suporte técnico especializado</li>
            <li><i class="fa-solid fa-check"></i> Parcelamento em até 12x</li>
        </ul>
    </div>
</main>
<style>
.static-page { max-width: 800px; margin: 40px auto; padding: 0 20px 80px; }
.static-page h1 { font-size: 1.8rem; color: #e5e7eb; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.static-page h1 i { color: #00ffcc; }
.static-page h2 { font-size: 1.2rem; color: #e5e7eb; margin: 24px 0 12px; }
.static-page p  { color: #9ca3af; line-height: 1.8; margin-bottom: 14px; }
.static-page ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
.static-page li { color: #9ca3af; display: flex; align-items: center; gap: 10px; }
.static-page li i { color: #00ffcc; }
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
