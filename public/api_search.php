<?php
/**
 * api_search.php — autocomplete de busca via AJAX
 * GET ?q=termo  →  JSON com até 8 sugestões
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=60');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo  = db();
$like = '%' . $q . '%';

// Produtos
$stmt = $pdo->prepare("
    SELECT p.name, p.slug, p.image_url, p.unit_price, c.name AS category
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1
      AND (p.name LIKE :q OR p.short_description LIKE :q2)
    ORDER BY p.featured DESC, p.name ASC
    LIMIT 6
");
$stmt->execute([':q'=>$like,':q2'=>$like]);
$products = $stmt->fetchAll();

// Categorias
$stmt = $pdo->prepare("
    SELECT name, slug FROM categories
    WHERE name LIKE :q
    LIMIT 3
");
$stmt->execute([':q'=>$like]);
$categories = $stmt->fetchAll();

$results = [];

foreach ($categories as $cat) {
    $results[] = [
        'type'  => 'category',
        'label' => $cat['name'],
        'url'   => '/category.php?slug=' . urlencode($cat['slug']),
        'icon'  => 'folder',
    ];
}

foreach ($products as $p) {
    $results[] = [
        'type'  => 'product',
        'label' => $p['name'],
        'sub'   => $p['category'],
        'price' => 'R$ ' . number_format($p['unit_price'], 2, ',', '.'),
        'img'   => $p['image_url'],
        'url'   => '/product.php?slug=' . urlencode($p['slug']),
    ];
}

echo json_encode($results);
