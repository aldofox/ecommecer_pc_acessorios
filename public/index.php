<?php
require_once __DIR__ . '/../models/ProductModel.php';

$pageTitle = 'Loja Gamer — Eletrônicos, Hardware e Periféricos';
require_once __DIR__ . '/../includes/header.php';

$banners    = ProductModel::getFeaturedBanners();
$carouselA  = ProductModel::getCarouselProducts(10, featured: true);
$carouselB  = ProductModel::getCarouselProducts(10, featured: false);
?>

<main class="home-main">
<div class="container">

  <!-- ── Chips de categorias ─────────────────────── -->
  <div class="category-chips" role="navigation" aria-label="Categorias">
    <a href="/category.php?slug=perifericos"    class="category-chip"><i class="fa-solid fa-keyboard"></i>Periféricos</a>
    <a href="/category.php?slug=placas-de-video" class="category-chip"><i class="fa-solid fa-video"></i></i>Placas de Vídeo</a>
    <a href="/category.php?slug=monitores"      class="category-chip"><i class="fa-solid fa-desktop"></i>Monitores</a>
    <a href="/category.php?slug=gabinetes"      class="category-chip"><i class="fa-solid fa-computer"></i>Gabinetes</a>
    <a href="/category.php?slug=processadores"  class="category-chip"><i class="fa-solid fa-microchip"></i>Processadores</a>
    <a href="/category.php?slug=headsets"       class="category-chip"><i class="fa-solid fa-headphones"></i>Headsets</a>
  </div>

  <!-- ── Banner Hero — Carrossel Swiper ────────── -->
  <?php if (!empty($banners)): ?>
  <div class="banner-hero-swiper-wrap">
    <div class="swiper mySwiperBannerHero">
      <div class="swiper-wrapper">
        <?php foreach ($banners as $b): ?>
        <div class="swiper-slide">
          <a href="/product.php?slug=<?= htmlspecialchars($b['slug']) ?>"
             class="banner-hero"
             aria-label="<?= htmlspecialchars($b['title']) ?>">
            <div class="banner-hero-body">
              <span class="banner-hero-tag"><i class="fa-solid fa-bolt"></i> Destaque</span>
              <h2><?= htmlspecialchars($b['title']) ?></h2>
              <p><?= htmlspecialchars($b['subtitle'] ?? '') ?></p>
              <div class="btn btn-lg btn-primary" style="display:inline-flex;margin-top:4px">
                <i class="fa-solid fa-cart-plus"></i> Ver oferta
              </div>
            </div>
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($b['image_url']) ?>"
                 alt="<?= htmlspecialchars($b['title']) ?>"
                 class="banner-hero-img" loading="lazy">
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Controles -->
      <div class="swiper-button-next banner-hero-next"></div>
      <div class="swiper-button-prev banner-hero-prev"></div>
      <div class="swiper-pagination banner-hero-pagination"></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Banner Strip — Carrossel Swiper ─────── -->
  <?php if (count($banners) >= 2): ?>
  <div class="banner-strip-swiper-wrap">
    <div class="swiper mySwiperBannerStrip">
      <div class="swiper-wrapper">
        <?php foreach ($banners as $bs): ?>
        <div class="swiper-slide">
          <a href="/product.php?slug=<?= htmlspecialchars($bs['slug']) ?>" class="banner-strip-item">
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($bs['image_url']) ?>"
                 alt="<?= htmlspecialchars($bs['title']) ?>" loading="lazy">
            <div class="banner-strip-body">
              <h3><?= htmlspecialchars($bs['title']) ?></h3>
              <p><?= htmlspecialchars($bs['subtitle'] ?? '') ?></p>
              <span class="btn btn-sm btn-secondary">
                Ver produto <i class="fa-solid fa-arrow-right"></i>
              </span>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-button-next strip-next"></div>
      <div class="swiper-button-prev strip-prev"></div>
      <div class="swiper-pagination strip-pagination"></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Carrossel A: Destaques ─────────────────── -->
  <section class="carousel-section">
    <div class="section-header">
      <h2><i class="fa-solid fa-star"></i> Produtos em Destaque</h2>
      <a href="/category.php?slug=perifericos">Ver todos <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="swiper mySwiperA">
      <div class="swiper-wrapper">
        <?php foreach ($carouselA as $p):
          $stars      = min(5, round((float)$p['rating_avg']));
          $hasDiscount = !empty($p['compare_price']) && $p['compare_price'] > $p['unit_price'];
          $discPct     = $hasDiscount ? round((1 - $p['unit_price'] / $p['compare_price']) * 100) : 0;
        ?>
          <div class="swiper-slide">
            <article class="product-card-swiper">
              <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="card-img-wrap">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                <?php if ($p['featured'] ?? false): ?>
                  <span class="card-badge-tag">Destaque</span>
                <?php elseif ($hasDiscount): ?>
                  <span class="card-badge-tag" style="background:#ef4444">-<?= $discPct ?>%</span>
                <?php endif; ?>
              </a>
              <div class="card-body">
                <h3 class="card-name"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="card-short-desc"><?= htmlspecialchars($p['short_description']) ?></p>

                <div class="card-stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                  <?php endfor; ?>
                  <span>(<?= (int)$p['rating_count'] ?>)</span>
                </div>

                <div class="card-spacer"></div>

                <?php if ($hasDiscount): ?>
                  <div class="card-compare-price">R$ <?= number_format($p['compare_price'], 2, ',', '.') ?></div>
                <?php endif; ?>
                <div class="card-price">
                  R$ <?= number_format($p['unit_price'], 2, ',', '.') ?>
                  <small>ou em até 12x no cartão</small>
                </div>

                <form action="/cart.php?action=add" method="post">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="quantity"   value="1">
                  <button type="submit" class="card-btn">
                    <i class="fa-solid fa-cart-plus"></i> Adicionar
                  </button>
                </form>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
      <div class="swiper-pagination"></div>
    </div>
  </section>

  <!-- ── Carrossel B: Novidades ─────────────────── -->
  <?php if (!empty($carouselB)): ?>
  <section class="carousel-section">
    <div class="section-header">
      <h2><i class="fa-solid fa-clock-rotate-left"></i> Chegaram Agora</h2>
      <a href="/index.php">Explorar <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="swiper mySwiperB">
      <div class="swiper-wrapper">
        <?php foreach ($carouselB as $p):
          $stars       = min(5, round((float)$p['rating_avg']));
          $hasDiscount = !empty($p['compare_price']) && $p['compare_price'] > $p['unit_price'];
          $discPct     = $hasDiscount ? round((1 - $p['unit_price'] / $p['compare_price']) * 100) : 0;
        ?>
          <div class="swiper-slide">
            <article class="product-card-swiper">
              <a href="/product.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="card-img-wrap">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                <?php if ($hasDiscount): ?>
                  <span class="card-badge-tag" style="background:#ef4444">-<?= $discPct ?>%</span>
                <?php endif; ?>
              </a>
              <div class="card-body">
                <h3 class="card-name"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="card-short-desc"><?= htmlspecialchars($p['short_description']) ?></p>

                <div class="card-stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                  <?php endfor; ?>
                  <span>(<?= (int)$p['rating_count'] ?>)</span>
                </div>

                <div class="card-spacer"></div>

                <?php if ($hasDiscount): ?>
                  <div class="card-compare-price">R$ <?= number_format($p['compare_price'], 2, ',', '.') ?></div>
                <?php endif; ?>
                <div class="card-price">
                  R$ <?= number_format($p['unit_price'], 2, ',', '.') ?>
                  <small>ou em até 12x no cartão</small>
                </div>

                <form action="/cart.php?action=add" method="post">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="quantity"   value="1">
                  <button type="submit" class="card-btn">
                    <i class="fa-solid fa-cart-plus"></i> Adicionar
                  </button>
                </form>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
      <div class="swiper-pagination"></div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Vistos Recentemente (JS/localStorage) ────── -->
  <section class="carousel-section" id="section-recentes" style="display:none">
    <div class="section-header">
      <h2><i class="fa-solid fa-eye"></i> Vistos Recentemente</h2>
      <button onclick="clearRecentes()" class="btn-clear-recentes">Limpar</button>
    </div>
    <div id="recentes-grid" class="recentes-grid"></div>
  </section>

  <!-- ── Newsletter ───────────────────────────────── -->
  <section class="newsletter-section">
    <div class="newsletter-inner">
      <div class="newsletter-text">
        <i class="fa-solid fa-envelope-open-text"></i>
        <div>
          <h3>Fique por dentro das ofertas</h3>
          <p>Receba as melhores promoções e novidades direto no seu e-mail.</p>
        </div>
      </div>
      <form class="newsletter-form" id="newsletterForm" onsubmit="subscribeNewsletter(event)">
        <input type="email" name="email" placeholder="seu@email.com" required>
        <button type="submit">
          <i class="fa-solid fa-paper-plane"></i> Inscrever
        </button>
      </form>
      <div id="newsletter-msg" class="newsletter-msg" style="display:none"></div>
    </div>
  </section>

  <!-- ── Banner de Seções ──────────────────────────── -->
  <section class="features-banner">
    <a href="/ofertas.php" class="feature-card feature-ofertas">
      <i class="fa-solid fa-fire"></i>
      <div>
        <strong>Ofertas da Semana</strong>
        <span>Até 31% de desconto</span>
      </div>
    </a>
    <a href="/setup.php" class="feature-card feature-setup">
      <i class="fa-solid fa-screwdriver-wrench"></i>
      <div>
        <strong>Monte seu Setup</strong>
        <span>Configure e compre de uma vez</span>
      </div>
    </a>
    <a href="/comparar.php" class="feature-card feature-comparar">
      <i class="fa-solid fa-code-compare"></i>
      <div>
        <strong>Comparar Produtos</strong>
        <span>Specs lado a lado</span>
      </div>
    </a>
    <a href="/blog.php" class="feature-card feature-blog">
      <i class="fa-solid fa-newspaper"></i>
      <div>
        <strong>Blog & Guias</strong>
        <span>Dicas para seu setup</span>
      </div>
    </a>
  </section>

</div><!-- /container -->
</main>

<script>
/* ── Vistos Recentemente ──────────────────────── */
const BASE = '<?= BASE_URL ?>';

function getRecentes() {
  try { return JSON.parse(localStorage.getItem('loja_recentes') || '[]'); } catch(e) { return []; }
}

function renderRecentes() {
  const items = getRecentes();
  const section = document.getElementById('section-recentes');
  const grid    = document.getElementById('recentes-grid');
  if (!items.length) { section.style.display='none'; return; }
  section.style.display='block';
  grid.innerHTML = items.map(p => `
    <a href="/product.php?slug=${p.slug}" class="recente-card">
      <div class="recente-img">
        <img src="${BASE}/${p.img}" alt="${p.name}" loading="lazy">
      </div>
      <div class="recente-body">
        <span class="recente-name">${p.name}</span>
        <span class="recente-price">R$ ${p.price}</span>
      </div>
    </a>
  `).join('');
}

function clearRecentes() {
  localStorage.removeItem('loja_recentes');
  renderRecentes();
}

renderRecentes();

/* ── Newsletter ───────────────────────────────── */
function subscribeNewsletter(e) {
  e.preventDefault();
  const form  = document.getElementById('newsletterForm');
  const email = form.querySelector('input[name=email]').value;
  const msg   = document.getElementById('newsletter-msg');

  fetch('/newsletter.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'email='+encodeURIComponent(email)
  })
  .then(r => r.json())
  .then(data => {
    msg.style.display = 'flex';
    msg.className = 'newsletter-msg ' + (data.ok ? 'nl-ok' : 'nl-err');
    msg.innerHTML = (data.ok ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-triangle-exclamation"></i>') + ' ' + data.msg;
    if (data.ok) form.style.display = 'none';
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
