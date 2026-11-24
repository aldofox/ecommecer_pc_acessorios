<?php 
require_once dirname(__DIR__) . '/includes/db.php';

class CartModel {
    /**
     * Busca ou cria um carrinho para o usuário ou sessão.
     * Se o usuário logar e já existir carrinho da sessão, vincula ao user_id.
     */
    public static function getOrCreateCart(?int $userId, string $sessionId): array
    {
        $pdo = db();

        if ($userId) {
            // 1. Busca carrinho pelo user_id
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE user_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $cart = $stmt->fetch();

            // 2. Se não encontrou, busca pelo session_id
            if (!$cart) {
                $stmt = $pdo->prepare("SELECT * FROM carts WHERE session_id = :sid LIMIT 1");
                $stmt->execute([':sid' => $sessionId]);
                $cart = $stmt->fetch();

                // Se encontrou pelo session_id, vincula ao usuário
                if ($cart) {
                    $stmt = $pdo->prepare("UPDATE carts SET user_id = :uid WHERE id = :id");
                    $stmt->execute([':uid' => $userId, ':id' => $cart['id']]);
                    $cart['user_id'] = $userId;
                }
            }
        } else {
            // Visitante → busca pelo session_id
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE session_id = :sid LIMIT 1");
            $stmt->execute([':sid' => $sessionId]);
            $cart = $stmt->fetch();
        }

        // 3. Se não encontrou nada, cria um novo carrinho
        if (!$cart) {
            $stmt = $pdo->prepare("
                INSERT INTO carts (user_id, session_id, created_at) 
                VALUES (:uid, :sid, NOW())
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':sid' => $sessionId
            ]);

            $cartId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE id = :id");
            $stmt->execute([':id' => $cartId]);
            $cart = $stmt->fetch();
        }

        return $cart;
    }

    /**
     * Mescla carrinho da sessão com carrinho do usuário ao fazer login.
     */
    public static function mergeCartOnLogin(int $userId, string $sessionId): void
    {
        $pdo = db();

        // Carrinho da sessão
        $stmt = $pdo->prepare("SELECT * FROM carts WHERE session_id = :sid LIMIT 1");
        $stmt->execute([':sid' => $sessionId]);
        $sessionCart = $stmt->fetch();

        // Carrinho do usuário
        $stmt = $pdo->prepare("SELECT * FROM carts WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $userCart = $stmt->fetch();

        if ($sessionCart && $userCart && $sessionCart['id'] != $userCart['id']) {
            // Mescla itens
            $items = self::getItems((int)$sessionCart['id']);
            foreach ($items as $it) {
                self::addItem((int)$userCart['id'], (int)$it['product_id'], (int)$it['quantity']);
            }

            // Remove carrinho da sessão
            $stmt = $pdo->prepare("DELETE FROM carts WHERE id = :id");
            $stmt->execute([':id' => $sessionCart['id']]);
        } elseif ($sessionCart && !$userCart) {
            // Se só existe carrinho da sessão, vincula ao usuário
            $stmt = $pdo->prepare("UPDATE carts SET user_id = :uid WHERE id = :id");
            $stmt->execute([':uid' => $userId, ':id' => $sessionCart['id']]);
        }
    }

    public static function addItem(int $cartId, int $productId, int $qty): void {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT unit_price, stock_qty FROM products WHERE id = :pid AND is_active = 1");
        $stmt->execute([':pid' => $productId]);
        $p = $stmt->fetch();

        if (!$p || $p['stock_qty'] < $qty) {
            throw new RuntimeException('Produto indisponível ou quantidade acima do estoque.');
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = :cid AND product_id = :pid");
            $stmt->execute([':cid' => $cartId, ':pid' => $productId]);
            $item = $stmt->fetch();

            if ($item) {
                $newQty = (int)$item['quantity'] + $qty;
                if ($newQty > (int)$p['stock_qty']) throw new RuntimeException('Estoque insuficiente.');
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = :q, unit_price = :up WHERE id = :id");
                $stmt->execute([':q' => $newQty, ':up' => $p['unit_price'], ':id' => $item['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, unit_price) VALUES (:cid, :pid, :q, :up)");
                $stmt->execute([':cid'=> $cartId, ':pid' =>$productId, ':q' => $qty, ':up' => $p['unit_price']]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getItems(int $cartId): array {
        $sql = "SELECT ci.*, p.name, p.image_url, p.slug
                FROM cart_items ci
                JOIN products p ON p.id = ci.product_id
                WHERE ci.cart_id = :cid";
        $stmt = db()->prepare($sql);
        $stmt->execute([':cid' => $cartId]);
        return $stmt->fetchAll();
    }

    public static function updateQty(int $cartItemId, int $qty): void {
        $stmt = db()->prepare("UPDATE cart_items SET quantity = :q WHERE id = :id");
        $stmt->execute([':q' => $qty, ':id' => $cartItemId]);
    }

    public static function removeItem(int $cartItemId): void {
        $stmt = db()->prepare("DELETE FROM cart_items WHERE id = :id");
        $stmt->execute([':id' => $cartItemId]);
    }

    public static function clearCart(int $cartId): void {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = :cid");
    $stmt->execute([':cid' => $cartId]);
}

}
