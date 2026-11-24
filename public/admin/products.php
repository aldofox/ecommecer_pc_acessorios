<?php
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$pdo    = db();
$action = $_GET['action'] ?? 'list';

/* ── Helper: salva imagem principal ─────────────────────────────────── */
function saveProductImage(): ?string {
    if (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return null;
    $filename = uniqid('prod_') . '.' . $ext;
    $destDir  = BASE_PATH . '/public/images/products/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $filename);
    return 'images/products/' . $filename;
}

/* ── Helper: salva imagens adicionais ───────────────────────────────── */
function saveExtraImages(int $productId, PDO $pdo): void {
    if (empty($_FILES['extra_images']['name'][0])) return;
    $files   = $_FILES['extra_images'];
    $allowed = ['jpg','jpeg','png','webp','gif'];
    $destDir = BASE_PATH . '/public/images/products/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    // Pega a maior ordem existente
    $maxOrder = (int)$pdo->prepare("SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id=:pid")
                          ->execute([':pid'=>$productId]) ? $pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id=$productId")->fetchColumn() : 0;

    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK || !$name) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $filename = uniqid('extra_') . '.' . $ext;
        move_uploaded_file($files['tmp_name'][$i], $destDir . $filename);
        $maxOrder++;
        $pdo->prepare("INSERT INTO product_images (product_id, image_url, sort_order, alt_text) VALUES (:pid,:url,:ord,:alt)")
            ->execute([':pid'=>$productId, ':url'=>'images/products/'.$filename, ':ord'=>$maxOrder, ':alt'=>htmlspecialchars($name)]);
    }
}

/* ── Helper: monta JSON de specs a partir do POST ───────────────────── */
function buildSpecsJson(): ?string {
    $labels = $_POST['spec_label'] ?? [];
    $values = $_POST['spec_value'] ?? [];
    $specs  = [];
    foreach ($labels as $i => $label) {
        $label = trim($label);
        $value = trim($values[$i] ?? '');
        if ($label !== '' && $value !== '') {
            $specs[] = ['label' => $label, 'value' => $value];
        }
    }
    return !empty($specs) ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null;
}

/* ── CREATE ─────────────────────────────────────────────────────────── */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imgPath      = saveProductImage();
    $specsJson    = buildSpecsJson();
    $comparePrice = trim($_POST['compare_price'] ?? '') !== '' ? (float)$_POST['compare_price'] : null;

    $stmt = $pdo->prepare("INSERT INTO products
        (category_id, name, slug, short_description, description, specs,
         image_url, unit_price, compare_price, stock_qty, is_active, featured)
        VALUES (:cid,:name,:slug,:short,:desc,:specs,:img,:price,:cprice,:stock,:active,:feat)");
    $stmt->execute([
        ':cid'    => (int)$_POST['category_id'],
        ':name'   => trim($_POST['name']),
        ':slug'   => trim($_POST['slug']),
        ':short'  => trim($_POST['short_description']),
        ':desc'   => trim($_POST['description']),
        ':specs'  => $specsJson,
        ':img'    => $imgPath ?? '',
        ':price'  => (float)$_POST['unit_price'],
        ':cprice' => $comparePrice,
        ':stock'  => (int)$_POST['stock_qty'],
        ':active' => isset($_POST['is_active']) ? 1 : 0,
        ':feat'   => isset($_POST['featured'])  ? 1 : 0,
    ]);
    $newId = (int)$pdo->lastInsertId();
    saveExtraImages($newId, $pdo);

    header('Location: ' . BASE_URL . '/admin/products.php?msg=created');
    exit;
}

/* ── EDIT (GET) ─────────────────────────────────────────────────────── */
$editProduct  = null;
$editImages   = [];
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $editProduct = $stmt->fetch();
    if (!$editProduct) { header('Location: ' . BASE_URL . '/admin/products.php'); exit; }

    $stmtI = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :pid ORDER BY sort_order");
    $stmtI->execute([':pid' => $editProduct['id']]);
    $editImages = $stmtI->fetchAll();
}

/* ── UPDATE (POST) ──────────────────────────────────────────────────── */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId    = (int)$_POST['product_id'];
    $newImg       = saveProductImage();
    $imgPath      = $newImg ?? trim($_POST['current_image'] ?? '');
    $specsJson    = buildSpecsJson();
    $comparePrice = trim($_POST['compare_price'] ?? '') !== '' ? (float)$_POST['compare_price'] : null;

    $stmt = $pdo->prepare("UPDATE products SET
        category_id       = :cid,
        name              = :name,
        slug              = :slug,
        short_description = :short,
        description       = :desc,
        specs             = :specs,
        image_url         = :img,
        unit_price        = :price,
        compare_price     = :cprice,
        stock_qty         = :stock,
        is_active         = :active,
        featured          = :feat
        WHERE id          = :id");
    $stmt->execute([
        ':cid'    => (int)$_POST['category_id'],
        ':name'   => trim($_POST['name']),
        ':slug'   => trim($_POST['slug']),
        ':short'  => trim($_POST['short_description']),
        ':desc'   => trim($_POST['description']),
        ':specs'  => $specsJson,
        ':img'    => $imgPath,
        ':price'  => (float)$_POST['unit_price'],
        ':cprice' => $comparePrice,
        ':stock'  => (int)$_POST['stock_qty'],
        ':active' => isset($_POST['is_active']) ? 1 : 0,
        ':feat'   => isset($_POST['featured'])  ? 1 : 0,
        ':id'     => $productId,
    ]);

    // Imagens extras novas
    saveExtraImages($productId, $pdo);

    header('Location: ' . BASE_URL . '/admin/products.php?msg=updated');
    exit;
}

/* ── DELETE IMAGEM EXTRA ────────────────────────────────────────────── */
if ($action === 'delete_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imgId  = (int)$_POST['image_id'];
    $prodId = (int)$_POST['product_id'];
    $stmt   = $pdo->prepare("SELECT image_url FROM product_images WHERE id = :id AND product_id = :pid");
    $stmt->execute([':id'=>$imgId,':pid'=>$prodId]);
    $row = $stmt->fetch();
    if ($row) {
        $file = BASE_PATH . '/public/' . $row['image_url'];
        if (file_exists($file)) @unlink($file);
        $pdo->prepare("DELETE FROM product_images WHERE id = :id")->execute([':id'=>$imgId]);
    }
    header('Location: ' . BASE_URL . '/admin/products.php?action=edit&id=' . $prodId . '&msg=img_deleted');
    exit;
}

/* ── DELETE PRODUTO ─────────────────────────────────────────────────── */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pdo->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => (int)$_POST['id']]);
    header('Location: ' . BASE_URL . '/admin/products.php?msg=deleted');
    exit;
}

/* ── LIST ───────────────────────────────────────────────────────────── */
$products = $pdo->query("
    SELECT p.id, p.image_url, p.name, p.slug, c.name AS category,
           p.unit_price, p.compare_price, p.stock_qty, p.is_active, p.featured,
           IF(p.specs IS NOT NULL AND p.specs != 'null', 1, 0) AS has_specs
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY p.created_at DESC
")->fetchAll();

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

$msg = match ($_GET['msg'] ?? '') {
    'created'     => 'Produto criado com sucesso!',
    'updated'     => 'Produto atualizado!',
    'deleted'     => 'Produto excluído.',
    'img_deleted' => 'Imagem removida.',
    default       => ''
};

/* ── Specs do produto em edição (decodificadas para preencher o form) ── */
$editSpecs = [];
if ($editProduct && !empty($editProduct['specs'])) {
    $decoded = json_decode($editProduct['specs'], true);
    if (is_array($decoded)) $editSpecs = $decoded;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produtos — Admin Loja Gamer</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
    /* ── Specs editor ───────────────────────────────── */
    .specs-editor { display:flex; flex-direction:column; gap:8px; }

    .spec-row {
        display:grid;
        grid-template-columns:1fr 1fr auto;
        gap:8px;
        align-items:center;
        background:rgba(255,255,255,.03);
        border:1px solid var(--border);
        border-radius:8px;
        padding:8px 10px;
    }

    .spec-row input {
        padding:8px 10px;
        background:rgba(255,255,255,.05);
        border:1px solid var(--border);
        border-radius:6px;
        color:var(--text-main);
        font-size:.85rem;
        outline:none;
        width:100%;
        font-family:inherit;
    }
    .spec-row input:focus { border-color:var(--accent); }

    .spec-row input::placeholder { color:var(--text-muted); }

    .spec-remove-btn {
        background:none;
        border:none;
        color:var(--text-muted);
        cursor:pointer;
        font-size:.9rem;
        padding:4px 6px;
        border-radius:4px;
        transition:color .2s, background .2s;
        white-space:nowrap;
    }
    .spec-remove-btn:hover { color:var(--danger); background:rgba(239,68,68,.1); }

    .spec-add-btn {
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:7px 14px;
        background:rgba(108,99,255,.1);
        border:1px dashed var(--accent);
        border-radius:8px;
        color:var(--accent);
        font-size:.82rem;
        font-weight:600;
        cursor:pointer;
        transition:background .2s;
        margin-top:4px;
        width:fit-content;
        font-family:inherit;
    }
    .spec-add-btn:hover { background:rgba(108,99,255,.2); }

    .specs-header {
        display:grid;
        grid-template-columns:1fr 1fr auto;
        gap:8px;
        padding:0 10px;
        font-size:.72rem;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.5px;
        color:var(--text-muted);
    }

    /* ── Imagens extras ─────────────────────────────── */
    .extra-images-grid {
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        margin-bottom:12px;
    }

    .extra-img-item {
        position:relative;
        border:1px solid var(--border);
        border-radius:8px;
        overflow:hidden;
        width:90px;
        height:90px;
        background:rgba(255,255,255,.04);
    }

    .extra-img-item img {
        width:100%;
        height:100%;
        object-fit:contain;
        padding:4px;
    }

    .extra-img-delete {
        position:absolute;
        top:3px; right:3px;
        background:rgba(239,68,68,.85);
        border:none;
        color:#fff;
        border-radius:50%;
        width:20px; height:20px;
        font-size:.65rem;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        transition:background .2s;
    }
    .extra-img-delete:hover { background:#ef4444; }

    .section-divider {
        border:none;
        border-top:1px solid var(--border);
        margin:24px 0 16px;
    }
    .section-title {
        font-size:.78rem;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.6px;
        color:var(--text-muted);
        margin-bottom:14px;
        display:flex;
        align-items:center;
        gap:8px;
    }
    .section-title i { color:var(--accent); }
    </style>
</head>
<body>
<div class="container-admin">

    <aside class="sidebar-admin">
        <div class="sidebar-logo">Loja Gamer</div>
        <nav><ul>
            <li><a href="<?= BASE_URL ?>/admin/index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/admin/products.php" class="active"><i class="fa-solid fa-box"></i> Produtos</a></li>
            <li><a href="<?= BASE_URL ?>/admin/banners.php"><i class="fa-solid fa-image"></i> Banners</a></li>
            <li><a href="<?= BASE_URL ?>/admin/users.php"><i class="fa-solid fa-users"></i> Usuários</a></li>
            <li><a href="<?= BASE_URL ?>/admin/orders.php"><i class="fa-solid fa-bag-shopping"></i> Pedidos</a></li>
            <li><a href="<?= BASE_URL ?>/admin/blog.php"><i class="fa-solid fa-newspaper"></i> Blog</a></li>
            <li><a href="<?= BASE_URL ?>/admin/relatorios.php"><i class="fa-solid fa-chart-line"></i> Relatórios</a></li>
            <li><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
        </ul></nav>
    </aside>

    <main class="content-admin">

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($action === 'create' || ($action === 'edit' && $editProduct)):
            $isEdit  = ($action === 'edit');
            $f       = $editProduct ?? [];
            $formUrl = $isEdit
                ? BASE_URL . '/admin/products.php?action=update'
                : BASE_URL . '/admin/products.php?action=create';
        ?>
        <!-- ══════════════════════════════════════════════
             FORMULÁRIO (CRIAR e EDITAR — mesmo template)
             ══════════════════════════════════════════════ -->
        <div class="page-header">
            <h2>
                <i class="fa-solid fa-<?= $isEdit ? 'pen-to-square' : 'plus' ?>"></i>
                <?= $isEdit ? 'Editar Produto #' . $f['id'] : 'Criar Produto' ?>
            </h2>
            <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form method="post" action="<?= $formUrl ?>" enctype="multipart/form-data" class="admin-form" style="max-width:900px">
            <?php if ($isEdit): ?>
                <input type="hidden" name="product_id"    value="<?= $f['id'] ?>">
                <input type="hidden" name="current_image" value="<?= htmlspecialchars($f['image_url'] ?? '') ?>">
            <?php endif; ?>

            <!-- ── Seção: Informações básicas ───────── -->
            <div class="section-title"><i class="fa-solid fa-circle-info"></i> Informações Básicas</div>

            <div class="form-row">
                <div class="form-group flex-2">
                    <label>Nome do Produto: <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" required maxlength="200"
                           value="<?= htmlspecialchars($f['name'] ?? $_POST['name'] ?? '') ?>"
                           <?= !$isEdit ? 'oninput="generateSlug(this.value)"' : '' ?>>
                </div>
                <div class="form-group flex-1">
                    <label>Categoria: <span style="color:var(--danger)">*</span></label>
                    <select name="category_id" required>
                        <option value="">— selecione —</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($c['id'] == ($f['category_id'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Slug (URL amigável): <span style="color:var(--danger)">*</span></label>
                <input type="text" name="slug" id="slugField" required maxlength="220"
                       value="<?= htmlspecialchars($f['slug'] ?? $_POST['slug'] ?? '') ?>">
                <small>Gerado automaticamente. Só letras minúsculas, números e hífens. Ex: rtx-4070-super</small>
            </div>

            <div class="form-group">
                <label>Descrição Curta: <span style="color:var(--danger)">*</span></label>
                <input type="text" name="short_description" required maxlength="255"
                       placeholder="Aparece nos cards de produto (máx. 255 caracteres)"
                       value="<?= htmlspecialchars($f['short_description'] ?? $_POST['short_description'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Descrição Completa: <span style="color:var(--danger)">*</span></label>
                <textarea name="description" required rows="5"
                          placeholder="Descrição detalhada exibida na página do produto..."><?= htmlspecialchars($f['description'] ?? $_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- ── Seção: Preços e Estoque ───────────── -->
            <hr class="section-divider">
            <div class="section-title"><i class="fa-solid fa-tag"></i> Preços e Estoque</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Preço de Venda (R$): <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="unit_price" step="0.01" min="0" required
                           placeholder="0,00"
                           value="<?= $f['unit_price'] ?? $_POST['unit_price'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>
                        Preço Original (R$):
                        <small style="font-weight:400;color:var(--text-muted)"> — preço "de" para exibir desconto</small>
                    </label>
                    <input type="number" name="compare_price" step="0.01" min="0"
                           placeholder="Deixe vazio se não houver desconto"
                           value="<?= ($f['compare_price'] ?? '') ?: '' ?>">
                </div>
                <div class="form-group">
                    <label>Estoque: <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="stock_qty" min="0" required
                           value="<?= $f['stock_qty'] ?? $_POST['stock_qty'] ?? 0 ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-check">
                    <label>
                        <input type="checkbox" name="is_active" value="1"
                               <?= ($f['is_active'] ?? true) ? 'checked' : '' ?>>
                        Produto Ativo (visível na loja)
                    </label>
                </div>
                <div class="form-group form-check">
                    <label>
                        <input type="checkbox" name="featured" value="1"
                               <?= !empty($f['featured']) ? 'checked' : '' ?>>
                        Destaque (aparece no carrossel e banners)
                    </label>
                </div>
            </div>

            <!-- ── Seção: Imagens ────────────────────── -->
            <hr class="section-divider">
            <div class="section-title"><i class="fa-solid fa-images"></i> Imagens</div>

            <div class="form-row">
                <div class="form-group flex-1">
                    <label>Imagem Principal: <?= !$isEdit ? '<span style="color:var(--danger)">*</span>' : '' ?></label>
                    <input type="file" name="image" accept="image/*" <?= !$isEdit ? 'required' : '' ?>>
                    <small>JPG, PNG, WebP — recomendado 800×800px</small>
                    <?php if (!empty($f['image_url'])): ?>
                        <div class="current-image" style="margin-top:8px">
                            <small>Atual:</small>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($f['image_url']) ?>"
                                 alt="imagem atual"
                                 style="max-height:80px;border-radius:6px;margin-top:4px;display:block">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group flex-1">
                    <label>Imagens Adicionais (galeria):</label>
                    <input type="file" name="extra_images[]" accept="image/*" multiple>
                    <small>Selecione várias de uma vez. Aparecem como thumbnails na página do produto.</small>

                    <?php if (!empty($editImages)): ?>
                    <div class="extra-images-grid" style="margin-top:10px">
                        <?php foreach ($editImages as $ei): ?>
                        <div class="extra-img-item">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($ei['image_url']) ?>"
                                 alt="<?= htmlspecialchars($ei['alt_text'] ?? '') ?>">
                            <form method="post"
                                  action="<?= BASE_URL ?>/admin/products.php?action=delete_image"
                                  onsubmit="return confirm('Remover esta imagem?')">
                                <input type="hidden" name="image_id"   value="<?= $ei['id'] ?>">
                                <input type="hidden" name="product_id" value="<?= $f['id'] ?>">
                                <button type="submit" class="extra-img-delete" title="Remover">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Seção: Especificações Técnicas ───── -->
            <hr class="section-divider">
            <div class="section-title"><i class="fa-solid fa-list-check"></i> Especificações Técnicas</div>
            <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
                Adicione as especificações do produto (ex: Memória → 12 GB GDDR6X). Aparecem em tabela na página do produto.
            </p>

            <div id="specsEditor" class="specs-editor">

                <!-- Cabeçalho das colunas -->
                <div class="specs-header">
                    <span>Especificação</span>
                    <span>Valor</span>
                    <span></span>
                </div>

                <?php
                // Se é edição, preenche com specs salvas
                // Se é criação ou houve erro de validação, tenta do POST
                $specsToRender = $editSpecs;
                if (empty($specsToRender) && !empty($_POST['spec_label'])) {
                    foreach ($_POST['spec_label'] as $i => $lbl) {
                        $specsToRender[] = ['label' => $lbl, 'value' => $_POST['spec_value'][$i] ?? ''];
                    }
                }
                // Garante ao menos 1 linha vazia
                if (empty($specsToRender)) {
                    $specsToRender = [['label'=>'','value'=>'']];
                }
                foreach ($specsToRender as $spec): ?>
                <div class="spec-row">
                    <input type="text" name="spec_label[]"
                           placeholder="Ex: Memória, Barramento, Clock..."
                           value="<?= htmlspecialchars($spec['label']) ?>">
                    <input type="text" name="spec_value[]"
                           placeholder="Ex: 12 GB GDDR6X, 192-bit, 2475 MHz..."
                           value="<?= htmlspecialchars($spec['value']) ?>">
                    <button type="button" class="spec-remove-btn" onclick="removeSpec(this)" title="Remover linha">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="spec-add-btn" onclick="addSpec()">
                <i class="fa-solid fa-plus"></i> Adicionar Especificação
            </button>

            <!-- ── Ações ─────────────────────────────── -->
            <div class="form-actions" style="margin-top:28px">
                <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <?= $isEdit ? 'Salvar Alterações' : 'Criar Produto' ?>
                </button>
            </div>
        </form>

        <?php else: ?>
        <!-- ══════════════════════════════════════════════
             LISTAGEM
             ══════════════════════════════════════════════ -->
        <div class="page-header">
            <h2><i class="fa-solid fa-box"></i> Produtos (<?= count($products) ?>)</h2>
            <a href="<?= BASE_URL ?>/admin/products.php?action=create" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Novo Produto
            </a>
        </div>

        <?php if (empty($products)): ?>
            <p class="empty-state">Nenhum produto cadastrado.</p>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Imagem</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Specs</th>
                    <th>Ativo</th>
                    <th>Destaque</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr class="<?= $p['stock_qty'] == 0 ? 'row-danger' : ($p['stock_qty'] <= 5 ? 'row-warning' : '') ?>">
                    <td><?= $p['id'] ?></td>
                    <td>
                        <?php if ($p['image_url']): ?>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image_url']) ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 style="max-height:50px;border-radius:4px">
                        <?php else: ?>
                            <span class="no-image">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($p['name']) ?>
                        <?php if ($p['compare_price']): ?>
                            <br><small style="color:#22c55e;font-size:.7rem">
                                <?= round((1 - $p['unit_price']/$p['compare_price'])*100) ?>% OFF
                            </small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['category']) ?></td>
                    <td>
                        R$ <?= number_format($p['unit_price'], 2, ',', '.') ?>
                        <?php if ($p['compare_price']): ?>
                            <br><small style="color:var(--text-muted);text-decoration:line-through;font-size:.75rem">
                                R$ <?= number_format($p['compare_price'], 2, ',', '.') ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['stock_qty'] == 0): ?>
                            <span class="badge badge-danger">Esgotado</span>
                        <?php elseif ($p['stock_qty'] <= 5): ?>
                            <span class="badge badge-warning"><?= $p['stock_qty'] ?> un.</span>
                        <?php else: ?>
                            <?= $p['stock_qty'] ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $p['has_specs']
                            ? '<span class="badge badge-success"><i class="fa-solid fa-check"></i></span>'
                            : '<span class="badge badge-muted">—</span>' ?>
                    </td>
                    <td><?= $p['is_active'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-muted">Não</span>' ?></td>
                    <td><?= $p['featured']  ? '<span class="badge badge-info">Sim</span>' : '—' ?></td>
                    <td class="actions-cell">
                        <a href="<?= BASE_URL ?>/admin/products.php?action=edit&id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                        <form method="post" action="<?= BASE_URL ?>/admin/products.php?action=delete"
                              style="display:inline"
                              onsubmit="return confirm('Excluir «<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>»? Esta ação não pode ser desfeita.')">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<script>
/* ── Slug automático ─────────────────────── */
function generateSlug(name) {
    const field = document.getElementById('slugField');
    if (!field || field.dataset.manual === 'true') return;
    field.value = name.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/[^a-z0-9\s-]/g,'')
        .trim().replace(/\s+/g,'-');
}
document.getElementById('slugField')?.addEventListener('input', function(){ this.dataset.manual = 'true'; });

/* ── Editor de Specs ─────────────────────── */
function addSpec() {
    const editor = document.getElementById('specsEditor');
    const row = document.createElement('div');
    row.className = 'spec-row';
    row.innerHTML = `
        <input type="text" name="spec_label[]" placeholder="Ex: Memória, Barramento, Clock...">
        <input type="text" name="spec_value[]" placeholder="Ex: 12 GB GDDR6X, 192-bit...">
        <button type="button" class="spec-remove-btn" onclick="removeSpec(this)" title="Remover">
            <i class="fa-solid fa-trash"></i>
        </button>`;
    editor.appendChild(row);
    row.querySelector('input').focus();
}

function removeSpec(btn) {
    const rows = document.querySelectorAll('#specsEditor .spec-row');
    if (rows.length <= 1) {
        // Se só tem 1 linha, limpa os campos em vez de remover
        btn.closest('.spec-row').querySelectorAll('input').forEach(i => i.value = '');
        return;
    }
    btn.closest('.spec-row').remove();
}

/* ── Atalho: Enter no input de specs vai pro próximo ── */
document.getElementById('specsEditor')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
        e.preventDefault();
        const allInputs = [...this.querySelectorAll('input')];
        const idx = allInputs.indexOf(e.target);
        if (idx === allInputs.length - 1) {
            // Último campo → adiciona nova linha
            addSpec();
        } else {
            allInputs[idx + 1].focus();
        }
    }
});
</script>
</body>
</html>
