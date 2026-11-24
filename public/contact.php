<?php
$pageTitle = 'Contato — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aqui entraria o envio de e-mail (PHPMailer)
    $sent = true;
}
?>
<main class="static-page">
    <div class="static-container">
        <h1><i class="fa-solid fa-envelope"></i> Fale Conosco</h1>

        <?php if ($sent): ?>
            <div class="alert-success-static">
                <i class="fa-solid fa-circle-check"></i> Mensagem enviada! Retornaremos em até 48h.
            </div>
        <?php else: ?>
            <form method="post" class="contact-form">
                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>E-mail:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Assunto:</label>
                    <input type="text" name="subject" required>
                </div>
                <div class="form-group">
                    <label>Mensagem:</label>
                    <textarea name="message" rows="6" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Mensagem
                </button>
            </form>
        <?php endif; ?>

        <div class="contact-info">
            <h2>Outras formas de contato</h2>
            <p><i class="fa-solid fa-envelope"></i> contato@lojagamer.com</p>
            <p><i class="fa-brands fa-whatsapp"></i> (11) 99999-9999</p>
            <p><i class="fa-solid fa-clock"></i> Seg–Sex 9h–18h</p>
        </div>
    </div>
</main>
<style>
.static-page { max-width: 640px; margin: 40px auto; padding: 0 20px 80px; }
.static-page h1 { font-size: 1.8rem; color: #e5e7eb; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
.static-page h1 i { color: #00ffcc; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 0.85rem; color: #9ca3af; margin-bottom: 6px; }
.form-group input, .form-group textarea {
  width: 100%; padding: 10px 14px;
  background: rgba(255,255,255,0.05); border: 1px solid #2a2740;
  border-radius: 8px; color: #e5e7eb; font-size: 0.9rem; outline: none;
}
.form-group textarea { resize: vertical; min-height: 120px; }
.form-group input:focus, .form-group textarea:focus { border-color: #00ffcc; }
.alert-success-static { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; border-radius: 8px; padding: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.contact-info { margin-top: 36px; padding-top: 24px; border-top: 1px solid #2a2740; }
.contact-info h2 { font-size: 1.1rem; color: #e5e7eb; margin-bottom: 14px; }
.contact-info p { color: #9ca3af; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
.contact-info i { color: #00ffcc; }
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
