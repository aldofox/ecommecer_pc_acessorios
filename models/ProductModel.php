<?php
require_once __DIR__ . '/../includes/db.php';
    
class ProductModel {
    public static function getFeaturedBanners(): array {
        $sql = "SELECT b.*, p.slug FROM banners b
                JOIN products p ON p.id = b.product_id
                WHERE b.is_active = 1
                ORDER BY b.sort_order ASC";
        return db()->query($sql)->fetchAll();
    }

    public static function getCarouselProducts(int $limit = 6, bool $featured = false): array {
        $featuredClause = $featured ? 'AND p.featured = 1' : '';
        $sql = "SELECT p.id, p.name, p.slug, p.image_url, p.unit_price, p.compare_price,
                        p.short_description, p.featured,
                        COALESCE(AVG(r.rating),0) AS rating_avg, COUNT(r.id) AS rating_count
                FROM products p
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE p.is_active = 1 $featuredClause
                GROUP BY p.id
                ORDER BY p.featured DESC, p.created_at DESC
                LIMIT :limit";
        $stmt = db()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getBySlug(string $slug): ?array {
        $sql = "SELECT p.*, p.compare_price, c.name AS category_name,
                        COALESCE(AVG(r.rating),0) AS rating_avg, COUNT(r.id) AS rating_count
                FROM products p
                JOIN categories c ON c.id = p.category_id
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE p.slug = :slug
                GROUP BY p.id";
        $stmt = db()->prepare($sql);
        $stmt->execute([':slug'=> $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    public static function getByCategory(int $categoryId, ?int $excludeProductId = null, int $limit = 6): array
{
    $pdo = db();
    $limit = (int)$limit; // garante inteiro

    $sql = "
        SELECT p.id, p.slug, p.name, p.image_url, p.unit_price, p.compare_price, p.category_id,
               COALESCE(AVG(r.rating),0) AS rating_avg,
               COUNT(r.id) AS rating_count
        FROM products p
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE p.category_id = :categoryId
    ";

    if ($excludeProductId) {
        $sql .= " AND p.id <> :excludeId";
    }

    $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':categoryId', $categoryId, PDO::PARAM_INT);

    if ($excludeProductId) {
        $stmt->bindValue(':excludeId', $excludeProductId, PDO::PARAM_INT);
    }

    $stmt->execute();
    $related = $stmt->fetchAll();

    // Fallback
    if (count($related) < $limit) {
        $extraLimit = $limit - count($related);
        $extraLimit = (int)$extraLimit;

        $extraSql = "
            SELECT id, slug, name, image_url, unit_price, category_id, 0 AS rating_avg, 0 AS rating_count
            FROM products
            WHERE id <> :excludeId
            ORDER BY created_at DESC
            LIMIT $extraLimit
        ";

        $extra = $pdo->prepare($extraSql);
        $extra->execute([
            ':excludeId' => $excludeProductId ?? 0
        ]);

        $related = array_merge($related, $extra->fetchAll());
    }

    return $related;
    }
}