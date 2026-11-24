<?php
require_once __DIR__ . '/../../config/config.php'; // garante BASE_URL
session_start();
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;