<?php
require_once("../config/config.php");
header('Content-Type: application/json');

$id = $_POST['id'] ?? 0;

if (isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]); // Xóa khỏi Session
}

$count = empty($_SESSION['cart']) ? 0 : array_sum($_SESSION['cart']);

echo json_encode([
    "status" => "success",
    "cart_count" => $count
]);