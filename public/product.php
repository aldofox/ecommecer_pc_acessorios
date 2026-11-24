<?php
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../includes/db.php';

$slug    = $_GET['slug'] ?? '';
$product = ProductModel::getBySlug($slug);

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produto não encontrado — Loja Gamer';
    require_once __DIR__ . '/../includes/header.php';
    echo '<main style="text-align:center;padding:80px 20px"><h2 style="color:#f0eeff">Produto não encontrado 😫</h2><a href="/index.php" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Voltar para a Home</a></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Prepara todos os dados ANTES de abrir o HTML ──────────────────────
$stars       = min(5, round((float)$product['rating_avg']));
$inStock     = (int)$product['stock_qty'] > 0;
$stockLow    = (int)$product['stock_qty'] <= 5 && $inStock;
$installment = number_format($product['unit_price'] / 12, 2, ',', '.');
$pixPrice    = number_format($product['unit_price'] * 0.95, 2, ',', '.');
$hasDiscount = !empty($product['compare_price']) && $product['compare_price'] > $product['unit_price'];
$discPct     = $hasDiscount ? round((1 - $product['unit_price'] / $product['compare_price']) * 100) : 0;
$specs       = !empty($product['specs']) ? json_decode($product['specs'], true) : [];

// Reviews do produto
$pdo   = db();
$stmtR = $pdo->prepare("
    SELECT r.*, u.name AS user_name
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.product_id = :pid
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmtR->execute([':pid' => $product['id']]);
$reviews = $stmtR->fetchAll();

// Imagens adicionais do produto
$stmtI = $pdo->prepare("
    SELECT image_url, alt_text FROM product_images
    WHERE product_id = :pid
    ORDER BY sort_order ASC
");
$stmtI->execute([':pid' => $product['id']]);
$extraImages = $stmtI->fetchAll();

// ── Abre o HTML ───────────────────────────────────────────────────────
$pageTitle = htmlspecialchars($product['name']) . ' — Loja Gamer';
$extraCss  = ['css/products_detial.css'];
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="product-breadcrumb">
    <a href="/index.php"><i class="fa-solid fa-house"></i> Home</a>
    <span class="sep">/</span>
    <a href="/category.php?slug=<?= htmlspecialchars($product['slug'] ?? '') ?>">
        <?= htmlspecialchars($product['category_name']) ?>
    </a>
    <span class="sep">/</span>
    <span><?= htmlspecialchars($product['name']) ?></span>
</nav>

<main class="product_detail">

    <!-- ── Galeria ─────────────────────────────── -->
    <div class="gallery">
        <div class="gallery-main" id="galleryMain">
            <?php if ($hasDiscount): ?>
                <div class="gallery-discount-badge">-<?= $discPct ?>% OFF</div>
            <?php endif; ?>
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image_url'] ?? 'images/placeholder.jpg') ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 id="mainProductImg">
        </div>

        <?php
        // Monta array completo de imagens (principal + extras)
        $allImages = array_merge(
            [['image_url' => $product['image_url'], 'alt_text' => $product['name']]],
            $extraImages
        );
        ?>
        <?php if (count($allImages) > 1): ?>
        <div class="gallery-thumbs">
            <?php foreach ($allImages as $idx => $img): ?>
            <button class="thumb-btn <?= $idx === 0 ? 'active' : '' ?>"
                    onclick="switchImage(this, '<?= BASE_URL ?>/<?= htmlspecialchars($img['image_url']) ?>', '<?= htmlspecialchars($img['alt_text'] ?? $product['name'], ENT_QUOTES) ?>')"
                    title="<?= htmlspecialchars($img['alt_text'] ?? '', ENT_QUOTES) ?>">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($img['image_url']) ?>"
                     alt="Miniatura <?= $idx + 1 ?>" loading="lazy">
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Informações ────────────────────────── -->
    <div class="info">

        <div class="info-category">
            <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($product['category_name']) ?>
        </div>

        <h1><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Avaliação resumida -->
        <div class="info-rating">
            <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                <?php endfor; ?>
            </div>
            <span class="rating-score"><?= number_format($product['rating_avg'], 1) ?></span>
            <span class="rating-count">
                (<?= (int)$product['rating_count'] ?> avaliação<?= $product['rating_count'] != 1 ? 'ões' : '' ?>)
            </span>
        </div>

        <hr class="info-divider">

        <!-- Bloco de preço -->
        <div class="info-price-block">
            <div class="price-label">Preço</div>
            <?php if ($hasDiscount): ?>
                <div class="price-compare">
                    De <span>R$ <?= number_format($product['compare_price'], 2, ',', '.') ?></span>
                </div>
            <?php endif; ?>
            <div class="price-main">R$ <?= number_format($product['unit_price'], 2, ',', '.') ?></div>
            <div class="price-installments">
                ou <strong>12x de R$ <?= $installment ?></strong> sem juros no cartão
            </div>
            <div class="price-pix">
                <i class="fa-brands fa-pix"></i>
                Pague com Pix por <strong>R$ <?= $pixPrice ?></strong>
                <span class="pix-badge">5% OFF</span>
            </div>
        </div>

        <!-- Estoque -->
        <div class="info-stock">
            <?php if (!$inStock): ?>
                <i class="fa-solid fa-circle-xmark stock-out"></i>
                <span class="stock-out">Produto esgotado</span>
            <?php elseif ($stockLow): ?>
                <i class="fa-solid fa-triangle-exclamation stock-low"></i>
                <span class="stock-low">Últimas <?= $product['stock_qty'] ?> unidades!</span>
            <?php else: ?>
                <i class="fa-solid fa-circle-check stock-ok"></i>
                <span class="stock-ok">Em estoque (<?= $product['stock_qty'] ?> unidades)</span>
            <?php endif; ?>
        </div>

        <!-- Formulário compra -->
        <?php if ($inStock): ?>
        <div class="buy-row">
            <form action="/cart.php?action=add" method="post" class="buy-form">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <div class="qty-wrapper">
                    <button type="button" onclick="changeQtyProduct(-1)">−</button>
                    <input type="number" name="quantity" id="productQty"
                           value="1" min="1" max="<?= $product['stock_qty'] ?>" class="qty-input">
                    <button type="button" onclick="changeQtyProduct(1)">+</button>
                </div>
                <button type="submit" class="btn-add-cart">
                    <i class="fa-solid fa-cart-plus"></i> Adicionar ao Carrinho
                </button>
            </form>
            <form action="/favorite.php" method="post" class="favorite-form">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button type="submit" title="Adicionar aos favoritos">
                    <i class="fa-regular fa-heart"></i>
                </button>
            </form>
        </div>
        <?php else: ?>
            <button class="btn-add-cart" style="opacity:.4;cursor:not-allowed" disabled>
                <i class="fa-solid fa-ban"></i> Produto Esgotado
            </button>
        <?php endif; ?>

        <!-- Benefícios -->
        <div class="info-benefits">
            <div class="benefit-item">
                <i class="fa-solid fa-truck-fast"></i>
                Frete grátis acima de R$ 2.000
            </div>
            <div class="benefit-item">
                <i class="fa-solid fa-shield-halved"></i>
                Garantia do fabricante
            </div>
            <div class="benefit-item">
                <i class="fa-solid fa-rotate-left"></i>
                7 dias para devolver
            </div>
            <div class="benefit-item">
                <i class="fa-solid fa-lock"></i>
                Pagamento seguro
            </div>
        </div>

        <!-- Descrição -->
        <div class="info-description">
            <h3>Sobre o produto</h3>
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        </div>

    </div><!-- /info -->
</main>

<!-- ── Abas: Especificações + Avaliações ─────── -->
<section class="product-tabs-section">
    <div class="product-tabs-inner">

        <div class="tabs-nav" role="tablist">
            <?php if (!empty($specs)): ?>
            <button class="tab-btn active" onclick="switchTab(this,'specs')" role="tab" aria-selected="true">
                <i class="fa-solid fa-list-check"></i> Especificações
            </button>
            <?php endif; ?>
            <button class="tab-btn <?= empty($specs) ? 'active' : '' ?>"
                    onclick="switchTab(this,'reviews')" role="tab"
                    aria-selected="<?= empty($specs) ? 'true' : 'false' ?>">
                <i class="fa-regular fa-star"></i>
                Avaliações (<?= (int)$product['rating_count'] ?>)
            </button>
        </div>

        <!-- Aba: Especificações -->
        <?php if (!empty($specs)): ?>
        <div class="tab-panel active" id="tab-specs">
            <table class="specs-table">
                <tbody>
                    <?php foreach ($specs as $i => $spec): ?>
                    <tr class="<?= $i % 2 === 0 ? 'row-even' : 'row-odd' ?>">
                        <th><?= htmlspecialchars($spec['label']) ?></th>
                        <td><?= htmlspecialchars($spec['value']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Aba: Avaliações -->
        <div class="tab-panel <?= empty($specs) ? 'active' : '' ?>" id="tab-reviews">

            <?php if (empty($reviews)): ?>
                <div class="reviews-empty">
                    <i class="fa-regular fa-star"></i>
                    <p>Nenhuma avaliação ainda. Seja o primeiro!</p>
                </div>
            <?php else: ?>

                <!-- Resumo de estrelas -->
                <div class="reviews-summary">
                    <div class="reviews-avg">
                        <span class="avg-number"><?= number_format($product['rating_avg'], 1) ?></span>
                        <div class="stars stars-lg">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa-<?= $i <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="avg-total"><?= (int)$product['rating_count'] ?> avaliações</span>
                    </div>

                    <!-- Barras de distribuição -->
                    <div class="reviews-bars">
                        <?php
                        $dist = array_fill(1, 5, 0);
                        foreach ($reviews as $rv) { $dist[(int)$rv['rating']]++; }
                        for ($s = 5; $s >= 1; $s--):
                            $pct = count($reviews) > 0 ? round($dist[$s] / count($reviews) * 100) : 0;
                        ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= $s ?> <i class="fa-solid fa-star"></i></span>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="bar-count"><?= $dist[$s] ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Lista de reviews -->
                <div class="reviews-list">
                    <?php foreach ($reviews as $rv):
                        $rvStars = (int)$rv['rating'];
                        $initials = strtoupper(mb_substr($rv['user_name'], 0, 1));
                        $date = date('d/m/Y', strtotime($rv['created_at']));
                    ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-avatar"><?= $initials ?></div>
                            <div class="review-meta">
                                <span class="review-author"><?= htmlspecialchars($rv['user_name']) ?></span>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-<?= $i <= $rvStars ? 'solid' : 'regular' ?> fa-star"></i>
                                    <?php endfor; ?>
                                    <span class="review-date"><?= $date ?></span>
                                </div>
                            </div>
                        </div>
                        <p class="review-body"><?= nl2br(htmlspecialchars($rv['comment'] ?? '')) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            <!-- Formulário para deixar review -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form-wrap">
                <h4>Deixe sua avaliação</h4>
                <form action="/review.php" method="post" class="review-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="star-picker" id="starPicker">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-regular fa-star" data-val="<?= $i ?>"></i>
                        <?php endfor; ?>
                        <input type="hidden" name="rating" id="ratingInput" value="0">
                    </div>
                    <textarea name="comment" rows="3" placeholder="Conte sua experiência com o produto..."></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Enviar Avaliação
                    </button>
                </form>
            </div>
            <?php else: ?>
            <p class="review-login-hint">
                <i class="fa-solid fa-circle-info"></i>
                <a href="/login.php?redirect=<?= urlencode('/product.php?slug=' . $slug) ?>">Entre na sua conta</a>
                para deixar uma avaliação.
            </p>
            <?php endif; ?>

        </div><!-- /tab-reviews -->

    </div>
</section>

<!-- ── Produtos relacionados ──────────────────── -->
<section class="related-products">
    <div class="section-header">
        <h2><i class="fa-solid fa-layer-group"></i> Produtos Relacionados</h2>
        <a href="/category.php?slug=<?= htmlspecialchars($product['slug'] ?? '') ?>">
            Ver categoria <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
    <div class="swiper mySwiperRelated">
        <div class="swiper-wrapper">
            <?php
            $related = ProductModel::getByCategory($product['category_id'], $product['id'], 8);
            foreach ($related as $rp):
                $rStars      = min(5, round((float)$rp['rating_avg']));
                $rHasDisc    = !empty($rp['compare_price']) && $rp['compare_price'] > $rp['unit_price'];
                $rDiscPct    = $rHasDisc ? round((1 - $rp['unit_price'] / $rp['compare_price']) * 100) : 0;
            ?>
            <div class="swiper-slide">
                <article class="product-card-swiper">
                    <a href="/product.php?slug=<?= htmlspecialchars($rp['slug']) ?>" class="card-img-wrap">
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($rp['image_url']) ?>"
                             alt="<?= htmlspecialchars($rp['name']) ?>" loading="lazy">
                        <?php if ($rHasDisc): ?>
                            <span class="card-badge-tag" style="background:#ef4444">-<?= $rDiscPct ?>%</span>
                        <?php endif; ?>
                    </a>
                    <div class="card-body">
                        <h3 class="card-name"><?= htmlspecialchars($rp['name']) ?></h3>
                        <div class="card-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa-<?= $i <= $rStars ? 'solid' : 'regular' ?> fa-star"></i>
                            <?php endfor; ?>
                            <span>(<?= (int)$rp['rating_count'] ?>)</span>
                        </div>
                        <div class="card-spacer"></div>
                        <?php if ($rHasDisc): ?>
                            <div class="card-compare-price">R$ <?= number_format($rp['compare_price'], 2, ',', '.') ?></div>
                        <?php endif; ?>
                        <div class="card-price">R$ <?= number_format($rp['unit_price'], 2, ',', '.') ?></div>
                        <a href="/product.php?slug=<?= htmlspecialchars($rp['slug']) ?>" class="card-btn">
                            <i class="fa-solid fa-eye"></i> Ver produto
                        </a>
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

<script>
// Registra produto nos "vistos recentemente" (localStorage)
(function(){
  const key  = 'loja_recentes';
  const item = {
    slug:  '<?= addslashes($product['slug']) ?>',
    name:  '<?= addslashes(htmlspecialchars($product['name'])) ?>',
    img:   '<?= addslashes($product['image_url']) ?>',
    price: '<?= number_format($product['unit_price'], 2, ',', '.') ?>'
  };
  try {
    let arr = JSON.parse(localStorage.getItem(key) || '[]');
    arr = arr.filter(p => p.slug !== item.slug);
    arr.unshift(item);
    arr = arr.slice(0, 8);
    localStorage.setItem(key, JSON.stringify(arr));
  } catch(e){}
})();

// Troca imagem principal na galeria
function switchImage(btn, src, alt) {
    const img = document.getElementById('mainProductImg');
    img.style.opacity = '0';
    setTimeout(() => { img.src = src; img.alt = alt; img.style.opacity = '1'; }, 150);
    document.querySelectorAll('.thumb-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function changeQtyProduct(delta) {
    const max   = parseInt(input.max) || 99;
    input.value = Math.min(max, Math.max(1, parseInt(input.value) + delta));
}

// Troca de aba
function switchTab(btn, tabId) {
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
    });
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    btn.setAttribute('aria-selected', 'true');
    document.getElementById('tab-' + tabId).classList.add('active');
}

// Star picker interativo
const picker = document.getElementById('starPicker');
if (picker) {
    const stars = picker.querySelectorAll('i');
    const input = document.getElementById('ratingInput');

    stars.forEach(star => {
        star.addEventListener('mouseover', function () {
            const val = +this.dataset.val;
            stars.forEach((s, idx) => {
                s.className = idx < val ? 'fa-solid fa-star' : 'fa-regular fa-star';
            });
        });
        star.addEventListener('click', function () {
            input.value = this.dataset.val;
        });
    });

    picker.addEventListener('mouseleave', function () {
        const val = +input.value;
        stars.forEach((s, idx) => {
            s.className = idx < val ? 'fa-solid fa-star' : 'fa-regular fa-star';
        });
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
