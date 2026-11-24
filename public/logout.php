<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/CartModel.php';

// Se o usuário estava logado, remove dados de login
if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id'], $_SESSION['user_name']);
}

// Limpa todas as variáveis da sessão
$_SESSION = [];

// Destroi a sessão atual
session_destroy();

// Inicia uma nova sessão para o visitante
session_start();

// Agora o visitante terá um novo session_id e poderá criar um novo carrinho
CartModel::getOrCreateCart(null, session_id());

// Redireciona para a página inicial
header("Location: /index.php");
exit;
