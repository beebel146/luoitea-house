<?php
require_once("../config/config.php");
header('Content-Type: application/json');

$id_size = $_POST['id'] ?? '';
$qty = intval($_POST['quantity'] ?? 1);

// Lấy id từ key
$k_parts = explode('_', $id_size);
$id = intval($k_parts[0]);

if (isset($_SESSION['cart'][$id_size])) {
    if ($qty > 0) {
        // Kiểm tra tổng tồn kho hiện tại trong giỏ (nếu có nhiều size)
        $total_other_qty = 0;
        foreach($_SESSION['cart'] as $k => $q) {
            $parts = explode('_', $k);
            if($parts[0] == $id && $k != $id_size) {
                $total_other_qty += $q;
            }
        }

        // Kiểm tra tồn kho trước khi cập nhật
        $sql = "SELECT stock FROM products WHERE id = $id";
        $result = mysqli_query($conn, $sql);
        $product = mysqli_fetch_assoc($result);

        if ($product && ($qty + $total_other_qty) > $product['stock']) {
            echo json_encode([
                "status" => "error",
                "message" => "Sản phẩm này chỉ còn " . $product['stock'] . " sản phẩm.",
                "max_stock" => $product['stock']
            ]);
            exit;
        }

        $_SESSION['cart'][$id_size] = $qty; // Cập nhật lại số lượng mới
    } else {
        unset($_SESSION['cart'][$id_size]);
    }
}

// Đếm lại tổng số lượng trong giỏ để cập nhật Header
$count = empty($_SESSION['cart']) ? 0 : array_sum($_SESSION['cart']);

echo json_encode([
    "status" => "success",
    "cart_count" => $count
]);