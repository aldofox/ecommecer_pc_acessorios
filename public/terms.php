<?php
$pageTitle = 'Termos de Uso — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="static-page terms-page">
    <h1><i class="fa-solid fa-file-contract"></i> Termos de Uso</h1>
    <p class="terms-updated">Última atualização: <?= date('d/m/Y') ?></p>

    <section>
        <h2>1. Aceitação dos Termos</h2>
        <p>Ao utilizar a Loja Gamer, você concorda com estes termos. Se não concordar, não utilize nossos serviços.</p>
    </section>

    <section>
        <h2>2. Produtos e Preços</h2>
        <p>Os preços estão sujeitos a alteração sem aviso prévio. Nos reservamos o direito de cancelar pedidos em caso de erros de preço evidentes.</p>
    </section>

    <section>
        <h2>3. Pagamento</h2>
        <p>Aceitamos Pix, boleto, cartão de crédito e débito. O pedido é processado após a confirmação do pagamento.</p>
    </section>

    <section>
        <h2>4. Entrega</h2>
        <p>Entregamos para todo o Brasil via Correios ou transportadora. O prazo começa após a confirmação do pagamento.</p>
    </section>

    <section>
        <h2>5. Política de Devolução</h2>
        <p>Seguimos o Código de Defesa do Consumidor (Lei 8.078/90). Você tem direito a arrependimento de compra em até 7 dias corridos após o recebimento.</p>
    </section>

    <section>
        <h2>6. Privacidade</h2>
        <p>Seus dados pessoais são tratados conforme a LGPD. Não compartilhamos dados com terceiros sem seu consentimento.</p>
    </section>

    <section>
        <h2>7. Contato</h2>
        <p>Dúvidas? Entre em contato: <a href="/contact.php">Fale Conosco</a>.</p>
    </section>
</main>
<style>
.static-page { max-width: 720px; margin: 40px auto; padding: 0 20px 80px; }
.static-page h1 { font-size: 1.8rem; color: #e5e7eb; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
.static-page h1 i { color: #00ffcc; }
.terms-updated { color: #6b7280; font-size: 0.82rem; margin-bottom: 28px; }
.static-page section { margin-bottom: 24px; }
.static-page h2 { font-size: 1.1rem; color: #c4c0d8; margin-bottom: 8px; }
.static-page p { color: #9ca3af; line-height: 1.8; font-size: 0.9rem; }
.static-page a { color: #00ffcc; text-decoration: none; }
.static-page a:hover { text-decoration: underline; }
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
