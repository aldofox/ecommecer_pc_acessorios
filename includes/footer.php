<footer class="site-footer">

  <div class="footer-trust">
    <div class="container">
      <div class="trust-items">
        <div class="trust-item">
          <i class="fa-solid fa-truck-fast"></i>
          <div>
            <strong>Entrega Rápida</strong>
            <span>Para todo o Brasil</span>
          </div>
        </div>
        <div class="trust-item">
          <i class="fa-solid fa-shield-halved"></i>
          <div>
            <strong>Compra Segura</strong>
            <span>Dados criptografados</span>
          </div>
        </div>
        <div class="trust-item">
          <i class="fa-solid fa-rotate-left"></i>
          <div>
            <strong>7 dias para devolver</strong>
            <span>Sem perguntas</span>
          </div>
        </div>
        <div class="trust-item">
          <i class="fa-solid fa-headset"></i>
          <div>
            <strong>Suporte Especializado</strong>
            <span>Seg–Sex 9h–18h</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-main">
    <div class="container">
      <div class="footer-grid">

        <div class="footer-brand-col">
          <a href="/index.php" class="footer-logo">
            <div class="footer-logo-icon"><i class="fa-solid fa-bolt"></i></div>
            <span>Loja<strong>Gamer</strong></span>
          </a>
          <p>Especialistas em hardware, periféricos e eletrônicos. Seu setup dos sonhos começa aqui.</p>
          <div class="footer-social">
            <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
            <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
          </div>
        </div>

        <div class="footer-col">
          <h4>Categorias</h4>
          <ul>
            <li><a href="/category.php?slug=perifericos"><i class="fa-solid fa-keyboard"></i> Periféricos</a></li>
            <li><a href="/category.php?slug=placas-de-video"><i class="fa-solid fa-microchip"></i> Placas de Vídeo</a></li>
            <li><a href="/category.php?slug=monitores"><i class="fa-solid fa-desktop"></i> Monitores</a></li>
            <li><a href="/category.php?slug=gabinetes"><i class="fa-solid fa-computer"></i> Gabinetes</a></li>
            <li><a href="/category.php?slug=processadores"><i class="fa-solid fa-cpu"></i> Processadores</a></li>
            <li><a href="/category.php?slug=headsets"><i class="fa-solid fa-headphones"></i> Headsets</a></li>
            <li><a href="/ofertas.php"><i class="fa-solid fa-fire" style="color:#ef4444"></i> Ofertas</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Ferramentas</h4>
          <ul>
            <li><a href="/setup.php"><i class="fa-solid fa-screwdriver-wrench"></i> Monte seu Setup</a></li>
            <li><a href="/comparar.php"><i class="fa-solid fa-code-compare"></i> Comparar Produtos</a></li>
            <li><a href="/rastrear.php"><i class="fa-solid fa-truck-fast"></i> Rastrear Pedido</a></li>
            <li><a href="/blog.php"><i class="fa-solid fa-newspaper"></i> Blog & Guias</a></li>
          </ul>
          <h4 style="margin-top:16px">Minha Conta</h4>
          <ul>
            <li><a href="/login.php">Entrar</a></li>
            <li><a href="/register.php">Criar conta</a></li>
            <li><a href="/minha_conta.php">Meus pedidos</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Institucional</h4>
          <ul>
            <li><a href="/about.php">Sobre nós</a></li>
            <li><a href="/contact.php">Contato</a></li>
            <li><a href="/faq.php">FAQ</a></li>
            <li><a href="/terms.php">Termos de uso</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Pagamentos</h4>
          <div class="payment-icons">
            <span title="Pix"><i class="fa-brands fa-pix"></i> Pix</span>
            <span title="Cartão"><i class="fa-regular fa-credit-card"></i> Cartão</span>
            <span title="Boleto"><i class="fa-solid fa-barcode"></i> Boleto</span>
          </div>
          <div class="footer-safe">
            <i class="fa-solid fa-lock"></i>
            Site 100% seguro — SSL/TLS
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <span>&copy; <?= date('Y') ?> Loja Gamer. Todos os direitos reservados.</span>
      <span>CNPJ: 00.000.000/0001-00</span>
    </div>
  </div>

</footer>

<style>
/* ── Footer ─────────────────────────────────── */
.site-footer {
  background: #0a0912;
  border-top: 1px solid #252338;
  margin-top: 60px;
}

/* Barra de confiança */
.footer-trust {
  background: #13121f;
  border-bottom: 1px solid #252338;
  padding: 24px 0;
}

.trust-items {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

@media (max-width: 768px) {
  .trust-items { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 480px) {
  .trust-items { grid-template-columns: 1fr; }
}

.trust-item {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #a09cc0;
}

.trust-item > i {
  font-size: 1.5rem;
  color: #6c63ff;
  flex-shrink: 0;
}

.trust-item strong {
  display: block;
  font-size: 0.85rem;
  color: #f0eeff;
  font-weight: 700;
}

.trust-item span {
  font-size: 0.78rem;
  color: #5c5880;
}

/* Footer principal */
.footer-main {
  padding: 48px 0 36px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1.2fr 1.2fr 1fr;
  gap: 40px;
}

@media (max-width: 900px) {
  .footer-grid { grid-template-columns: 1fr 1fr; gap: 28px; }
}

@media (max-width: 480px) {
  .footer-grid { grid-template-columns: 1fr; }
}

/* Logo */
.footer-logo {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  margin-bottom: 12px;
}

.footer-logo-icon {
  width: 30px;
  height: 30px;
  background: #6c63ff;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.footer-logo span {
  font-size: 1.15rem;
  color: #f0eeff;
  letter-spacing: 0.5px;
}

.footer-logo strong { color: #6c63ff; }

.footer-brand-col > p {
  font-size: 0.82rem;
  color: #5c5880;
  line-height: 1.7;
  max-width: 280px;
  margin-bottom: 16px;
}

.footer-social {
  display: flex;
  gap: 8px;
}

.footer-social a {
  width: 32px;
  height: 32px;
  background: #1a1828;
  border: 1px solid #252338;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #5c5880;
  font-size: 0.85rem;
  text-decoration: none;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.footer-social a:hover {
  background: rgba(108,99,255,0.15);
  color: #6c63ff;
  border-color: rgba(108,99,255,0.3);
}

/* Colunas de links */
.footer-col h4 {
  font-size: 0.78rem;
  font-weight: 700;
  color: #a09cc0;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 12px;
}

.footer-col ul {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.footer-col ul li a {
  color: #5c5880;
  text-decoration: none;
  font-size: 0.82rem;
  display: flex;
  align-items: center;
  gap: 7px;
  transition: color 0.2s;
}

.footer-col ul li a i {
  font-size: 0.75rem;
  width: 14px;
  color: #252338;
  transition: color 0.2s;
}

.footer-col ul li a:hover {
  color: #6c63ff;
}

.footer-col ul li a:hover i {
  color: #6c63ff;
}

/* Pagamentos */
.payment-icons {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.payment-icons span {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  color: #5c5880;
}

.payment-icons i { color: #a09cc0; }

.footer-safe {
  font-size: 0.78rem;
  color: #22c55e;
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(34,197,94,0.08);
  border: 1px solid rgba(34,197,94,0.15);
  border-radius: 6px;
  padding: 8px 12px;
}

/* Rodapé inferior */
.footer-bottom {
  border-top: 1px solid #252338;
  padding: 16px 0;
}

.footer-bottom .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 0.78rem;
  color: #2e2c45;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="<?= BASE_URL ?>/js/script.js"></script>
