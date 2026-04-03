<?php
require_once(__DIR__ . "/../config/config.php");

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user_id'])) throw new Exception("Chưa đăng nhập");

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $note = trim($data['note'] ?? '');
    $payment_method = trim($data['payment_method'] ?? 'bank_transfer');
    $coupon_id = isset($data['coupon_id']) ? (int)$data['coupon_id'] : null;

    $user_id = (int)$_SESSION['user_id'];
    $checkout_ids = $_SESSION['checkout_items'] ?? [];

    if (empty($checkout_ids)) throw new Exception("Không có sản phẩm để đặt hàng");

    // Tính tổng tiền
    $first_ids = array_map(function($val){
        $parts = explode('_', $val);
        return intval($parts[0]);
    }, $checkout_ids);
    $first_ids = array_unique($first_ids);
    $ids = implode(',', $first_ids);
    $res_p = mysqli_query($conn, "SELECT id, name, price_s, price_m, price_l FROM products WHERE id IN ($ids)");
    $products_data = [];
    while ($row = mysqli_fetch_assoc($res_p)) {
        $products_data[$row['id']] = $row;
    }

    $subtotal = 0;
    $items_detail = [];
    foreach ($checkout_ids as $id_size) {
        $qty = (int)($_SESSION['cart'][$id_size] ?? 0);
        if ($qty > 0) {
            $parts = explode('_', $id_size);
            $product_id = intval($parts[0]);
            $size = $parts[1] ?? 'M';
            $price_key = 'price_' . strtolower($size);
            $the_price = (int)($products_data[$product_id][$price_key] ?? 0);

            $subtotal += $the_price * $qty;
            $items_detail[] = [
                'id' => $product_id,
                'name' => $products_data[$product_id]['name'],
                'size' => $size,
                'id_size' => $id_size,
                'quantity' => $qty,
                'price' => $the_price
            ];
        }
    }

    $discount_amount = 0;
    if ($coupon_id) {
        $res_c = mysqli_query($conn, "SELECT * FROM coupons WHERE id = $coupon_id AND is_active = 1");
        $coupon = mysqli_fetch_assoc($res_c);
        if ($coupon && $subtotal >= $coupon['min_order_value']) {
            $discount_amount = ($coupon['type'] === 'fixed') ? $coupon['value'] : ($subtotal * $coupon['value'] / 100);
        }
    }

    $total = $subtotal - $discount_amount;

    // TẤT CẢ PHƯƠNG THỨC: Lưu vào bảng tạm payment_waiting
    $reference = "REF" . time() . rand(10, 99);
    $order_data = [
        'user_id' => $user_id,
        'coupon_id' => $coupon_id,
        'total' => $total,
        'discount_amount' => $discount_amount,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'note' => $note,
        'payment_method' => $payment_method,
        'items' => $items_detail
    ];

    $json_data = json_encode($order_data, JSON_UNESCAPED_UNICODE);
    $stmt = mysqli_prepare($conn, "INSERT INTO payment_waiting (reference, order_data) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ss", $reference, $json_data);
    mysqli_stmt_execute($stmt);

    echo json_encode(["success" => true, "reference" => $reference]);

} catch (Exception $e) {
    if (isset($conn) && $conn) mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}