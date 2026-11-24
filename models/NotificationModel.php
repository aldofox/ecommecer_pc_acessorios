<?php
require_once __DIR__ . '/../includes/db.php';

class NotificationModel {

    public static function create(int $userId, string $type, string $title, string $message, string $url = ''): void {
        db()->prepare("INSERT INTO notifications (user_id, type, title, message, url) VALUES (:u,:t,:ti,:m,:url)")
           ->execute([':u'=>$userId,':t'=>$type,':ti'=>$title,':m'=>$message,':url'=>$url]);
    }

    public static function getUnread(int $userId, int $limit = 10): array {
        $stmt = db()->prepare("
            SELECT * FROM notifications
            WHERE user_id = :u
            ORDER BY created_at DESC
            LIMIT :l
        ");
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':l', $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countUnread(int $userId): int {
        $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0");
        $stmt->execute([':u' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function markRead(int $userId, ?int $notifId = null): void {
        if ($notifId) {
            db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :u")
               ->execute([':id'=>$notifId,':u'=>$userId]);
        } else {
            db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :u")
               ->execute([':u'=>$userId]);
        }
    }

    /** Dispara notificação automática ao criar pedido */
    public static function onOrderCreated(int $userId, int $orderId, float $total): void {
        self::create($userId, 'order',
            'Pedido #' . $orderId . ' recebido!',
            'Seu pedido de R$ ' . number_format($total,2,',','.') . ' foi confirmado e está sendo processado.',
            '/minha_conta.php?tab=pedido&id=' . $orderId
        );
    }

    /** Dispara notificação quando status do pedido muda */
    public static function onStatusChange(int $userId, int $orderId, string $status): void {
        $msgs = [
            'paid'      => ['Pagamento aprovado! ✅', 'O pagamento do pedido #'.$orderId.' foi confirmado.'],
            'shipped'   => ['Pedido enviado! 🚚', 'Seu pedido #'.$orderId.' saiu para entrega.'],
            'delivered' => ['Pedido entregue! 📦', 'Seu pedido #'.$orderId.' foi entregue. Aproveite!'],
            'canceled'  => ['Pedido cancelado', 'O pedido #'.$orderId.' foi cancelado.'],
        ];
        if (!isset($msgs[$status])) return;
        self::create($userId, $status === 'canceled' ? 'warning' : 'order',
            $msgs[$status][0],
            $msgs[$status][1],
            '/minha_conta.php?tab=pedido&id=' . $orderId
        );
    }
}
