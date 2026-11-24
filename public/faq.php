<?php
$pageTitle = 'FAQ — Loja Gamer';
require_once __DIR__ . '/../includes/header.php';

$faqs = [
    ['q' => 'Como rastrear meu pedido?',            'a' => 'Após a confirmação do pagamento, você recebe o código de rastreio por e-mail em até 2 dias úteis.'],
    ['q' => 'Qual o prazo de entrega?',              'a' => 'Varia de acordo com a região. Em capitais, geralmente de 3 a 7 dias úteis. Para o interior, de 7 a 15 dias úteis.'],
    ['q' => 'Posso devolver um produto?',            'a' => 'Sim. De acordo com o CDC, você tem até 7 dias após o recebimento para desistir da compra sem custo.'],
    ['q' => 'Os produtos têm garantia?',             'a' => 'Todos os produtos são originais e têm garantia do fabricante — geralmente 12 meses para periféricos e 36 meses para hardware.'],
    ['q' => 'Quais formas de pagamento aceitas?',    'a' => 'Aceitamos Pix, boleto bancário, cartão de crédito (até 12x) e cartão de débito.'],
    ['q' => 'Posso parcelar no cartão?',             'a' => 'Sim, parcelamos em até 12x sem juros para compras acima de R$ 300,00.'],
    ['q' => 'Como entrar em contato com o suporte?', 'a' => 'Via e-mail em contato@lojagamer.com ou WhatsApp (11) 99999-9999, de Seg–Sex das 9h às 18h.'],
];
?>
<main class="static-page faq-page">
    <h1><i class="fa-solid fa-circle-question"></i> Perguntas Frequentes</h1>

    <div class="faq-list">
        <?php foreach ($faqs as $i => $item): ?>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false" onclick="toggleFaq(this)">
                    <span><?= htmlspecialchars($item['q']) ?></span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p><?= htmlspecialchars($item['a']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<style>
.static-page { max-width: 720px; margin: 40px auto; padding: 0 20px 80px; }
.static-page h1 { font-size: 1.8rem; color: #e5e7eb; margin-bottom: 28px; display: flex; align-items: center; gap: 10px; }
.static-page h1 i { color: #00ffcc; }
.faq-list { display: flex; flex-direction: column; gap: 8px; }
.faq-item { background: #1a1828; border: 1px solid #2a2740; border-radius: 10px; overflow: hidden; }
.faq-question { width: 100%; background: none; border: none; padding: 16px 20px; color: #e5e7eb; font-size: 0.95rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 12px; text-align: left; transition: color 0.2s; }
.faq-question:hover { color: #00ffcc; }
.faq-question i { color: #9ca3af; font-size: 0.8rem; transition: transform 0.3s; flex-shrink: 0; }
.faq-question[aria-expanded="true"] i { transform: rotate(180deg); color: #00ffcc; }
.faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
.faq-answer p { padding: 0 20px 16px; color: #9ca3af; font-size: 0.9rem; line-height: 1.7; }
</style>
<script>
function toggleFaq(btn) {
    const answer  = btn.nextElementSibling;
    const isOpen  = btn.getAttribute('aria-expanded') === 'true';
    // Fecha todos
    document.querySelectorAll('.faq-question').forEach(q => {
        q.setAttribute('aria-expanded', 'false');
        q.nextElementSibling.style.maxHeight = null;
    });
    // Abre o clicado se estava fechado
    if (!isOpen) {
        btn.setAttribute('aria-expanded', 'true');
        answer.style.maxHeight = answer.scrollHeight + 'px';
    }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
