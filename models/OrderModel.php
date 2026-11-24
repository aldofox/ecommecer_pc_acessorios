<?php
require_once dirname(__DIR__) . '/includes/db.php';

class OrderModel {

    /**
     * Cria um pedido a partir do carrinho atual.
     * Copia os itens do carrinho → order_items, calcula totais e limpa o carrinho.
     *
     * @return int ID do pedido criado
     */
    public static function createOrder(
        int    $userId,
        int    $cartId,
        string $paymentMethod,
        string $shippingAddress,
        float  $shipping  = 0.00,
        float  $discount  = 0.00,
        int    $couponId  = 0
    ): int {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "SELECT SUM(quantity * unit_price) FROM cart_items WHERE cart_id = :cartId"
            );
            $stmt->execute([':cartId' => $cartId]);
            $subtotal = (float)($stmt->fetchColumn() ?? 0.0);
            $total    = max(0, $subtotal + $shipping - $discount);

            $stmt = $pdo->prepare("
                INSERT INTO orders
                    (user_id, status, subtotal, shipping, discount, total, payment_method, shipping_address, created_at)
                VALUES
                    (:userId, 'pending', :subtotal, :shipping, :discount, :total, :paymentMethod, :shippingAddress, NOW())
            ");
            $stmt->execute([
                ':userId'          => $userId,
                ':subtotal'        => $subtotal,
                ':shipping'        => $shipping,
                ':discount'        => $discount,
                ':total'           => $total,
                ':paymentMethod'   => $paymentMethod,
                ':shippingAddress' => $shippingAddress,
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // Registra uso do cupom
            if ($couponId > 0 && $discount > 0) {
                $pdo->prepare("INSERT INTO order_coupons (order_id, coupon_id, discount) VALUES (:o,:c,:d)")
                    ->execute([':o'=>$orderId,':c'=>$couponId,':d'=>$discount]);
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id")
                    ->execute([':id'=>$couponId]);
            }

            // Copia os itens do carrinho para order_items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_line)
                SELECT :orderId, ci.product_id, ci.quantity, ci.unit_price, (ci.quantity * ci.unit_price)
                FROM cart_items ci
                WHERE ci.cart_id = :cartId
            ");
            $stmt->execute([
                ':orderId' => $orderId,
                ':cartId'  => $cartId,
            ]);

            // Limpa os itens do carrinho
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = :cartId");
            $stmt->execute([':cartId' => $cartId]);

            // Marca o carrinho como fechado (coluna status existe no schema corrigido)
            $stmt = $pdo->prepare("UPDATE carts SET status = 'closed' WHERE id = :cartId");
            $stmt->execute([':cartId' => $cartId]);

            $pdo->commit();
            
            // Dispara notificação ao usuário
            try {
                require_once __DIR__ . '/../models/NotificationModel.php';
                NotificationModel::onOrderCreated($userId, $orderId, $total);
            } catch (\Throwable $ignored) {}

            return $orderId;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Retorna todos os pedidos com dados do usuário, mais recentes primeiro.
     */
    public static function getAll(int $limit = 50, int $offset = 0): array {
        $sql = "SELECT o.*, u.name AS user_name, u.email AS user_email
                FROM orders o
                LEFT JOIN users u ON u.id = o.user_id
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = db()->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna um pedido pelo ID com seus itens.
     */
    public static function getById(int $orderId): ?array {
        $pdo  = db();
        $stmt = $pdo->prepare("
            SELECT o.*, u.name AS user_name, u.email AS user_email
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            WHERE o.id = :id
        ");
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();

        if (!$order) return null;

        $stmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, p.image_url
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = :oid
        ");
        $stmt->execute([':oid' => $orderId]);
        $order['items'] = $stmt->fetchAll();

        return $order;
    }

    /**
     * Retorna os pedidos de um usuário específico.
     */
    public static function getByUser(int $userId): array {
        $stmt = db()->prepare("
            SELECT * FROM orders
            WHERE user_id = :uid
            ORDER BY created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Atualiza o status de um pedido.
     * Statuses válidos: pending, paid, shipped, delivered, canceled
     */
    public static function updateStatus(int $orderId, string $status): void {
        $allowed = ['pending', 'paid', 'shipped', 'delivered', 'canceled'];
        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Status inválido: $status");
        }
        db()->prepare("UPDATE orders SET status = :status WHERE id = :id")
           ->execute([':status' => $status, ':id' => $orderId]);

        // Notifica o dono do pedido
        try {
            $pdo  = db();
            $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = :id");
            $stmt->execute([':id' => $orderId]);
            $userId = (int)$stmt->fetchColumn();
            if ($userId > 0) {
                require_once __DIR__ . '/NotificationModel.php';
                NotificationModel::onStatusChange($userId, $orderId, $status);
            }
        } catch (\Throwable $ignored) {}
    }

    /**
     * Estatísticas rápidas para o dashboard admin.
     */
    public static function getStats(): array {
        $pdo = db();
        $stats = [];

        $stats['total']     = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $stats['pending']   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
        $stats['paid']      = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn();
        $stats['shipped']   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
        $stats['revenue']   = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'canceled'")->fetchColumn();

        return $stats;
    }
}
