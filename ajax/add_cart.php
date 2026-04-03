<?php

require_once("../config/config.php");
require_once(__DIR__ . "/../admin/services/notification_helper.php");
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([
        "status"=>"error",
        "message"=>"Vui lòng đăng nhập để thêm vào giỏ hàng"
    ]);
    exit;
}
$user_id = $_SESSION['user_id'];
$id = intval($_POST['id'] ?? 0);
$size = $_POST['size'] ?? 'M'; // Default size

// Xác thực size
if(!in_array($size, ['S', 'M', 'L'])){
    echo json_encode([
        "status"=>"error",
        "message"=>"Size không hợp lệ"
    ]);
    exit;
}

$sql = "SELECT id, name, stock FROM products WHERE id=$id";
$result = mysqli_query($conn,$sql);
$product = mysqli_fetch_assoc($result);

if(!$product){
    echo json_encode([
        "status"=>"error",
        "message"=>"Sản phẩm không tồn tại"
    ]);
    exit;
}

$stock = intval($product['stock']);

if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

$cart_key = $id . '_' . $size;
$qty_in_cart = $_SESSION['cart'][$cart_key] ?? 0;

// Tính tổng số lượng CỦA SẢN PHẨM NÀY trong giỏ để kiểm tra tồn kho.
$total_qty_this_product = 0;
foreach($_SESSION['cart'] as $k => $q){
    $k_parts = explode('_', $k);
    if($k_parts[0] == $id) {
        $total_qty_this_product += $q;
    }
}

// 1. Kiểm tra nếu sản phẩm đã hết sạch trong kho
if($stock <= 0){
    sendAdminNotification(
        $conn,
        '⚠️ Sản phẩm hết hàng',
        "Sản phẩm \"{$product['name']}\" (ID: $id) đã hết hàng khi người dùng ID: $user_id cố gắng thêm vào giỏ.",
        'admin/pages/edit_product.php?id=' . $id,
        $id
    );

    echo json_encode([
        "status"=>"error",
        "message"=>"Sản phẩm hiện đang hết hàng"
    ]);
    exit;
}
// 2. Cảnh báo cho admin nếu tồn kho thấp (nhưng vẫn cho phép khách thêm giỏ)
if($stock <= 3 && $stock > 0){
    sendAdminNotification(
        $conn,
        '⚠️ Sắp hết hàng',
        "Sản phẩm \"{$product['name']}\" (ID: $id) chỉ còn $stock ly trong kho, hãy chú ý chuẩn bị nhé!",
        'admin/pages/edit_product.php?id=' . $id,
        $id
    );
}
if($total_qty_this_product + 1 > $stock){

    sendAdminNotification(
        $conn,
        '⚠️ Thiếu hàng',
        "User ID: $user_id đang cố mua vượt tồn kho sản phẩm \"{$product['name']}\"",
        'admin/pages/edit_product.php?id=' . $id,
        $id
    );

    echo json_encode([
        "status"=>"error",
        "message"=>"Sản phẩm này chỉ còn $stock sản phẩm, bạn đã thêm tối đa số lượng cho phép"
    ]);
    exit;
}

// 3. Thêm vào giỏ hàng
$_SESSION['cart'][$cart_key] = $qty_in_cart + 1;

$count = array_sum($_SESSION['cart']);

echo json_encode([
    "status"=>"success",
    "message"=>"Đã thêm vào giỏ hàng",
    "cart_count"=>$count
]);
exit;