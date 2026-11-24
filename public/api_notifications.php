<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/NotificationModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count'=>0,'items'=>[]]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Marcar como lida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    NotificationModel::markRead($userId, $id ?: null);
    echo json_encode(['ok'=>true]);
    exit;
}

$notifications = NotificationModel::getUnread($userId, 10);
$unread        = NotificationModel::countUnread($userId);

$icons = [
    'order'   => ['icon'=>'fa-bag-shopping',   'color'=>'#6c63ff'],
    'paid'    => ['icon'=>'fa-circle-check',    'color'=>'#22c55e'],
    'shipped' => ['icon'=>'fa-truck-fast',      'color'=>'#3b82f6'],
    'delivered'=> ['icon'=>'fa-box-open',        'color'=>'#22c55e'],
    'warning' => ['icon'=>'fa-triangle-exclamation','color'=>'#f59e0b'],
    'info'    => ['icon'=>'fa-circle-info',     'color'=>'#6c63ff'],
];

$items = array_map(function($n) use ($icons) {
    $ic = $icons[$n['type']] ?? $icons['info'];
    return [
        'id'      => $n['id'],
        'title'   => $n['title'],
        'message' => $n['message'],
        'url'     => $n['url'] ?? '',
        'is_read' => (bool)$n['is_read'],
        'icon'    => $ic['icon'],
        'color'   => $ic['color'],
        'time'    => date('d/m H:i', strtotime($n['created_at'])),
    ];
}, $notifications);

echo json_encode(['count'=>$unread,'items'=>$items]);
