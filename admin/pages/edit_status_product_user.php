<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

// check login
if (!isset($_SESSION['user_id'])) {
    die("Chưa đăng nhập");
}

// lấy id
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID không hợp lệ");

// =======================
// LẤY ORDER + USER
// =======================
$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT o.*, u.username 
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = $id
"));

if (!$order) die("Không tìm thấy đơn hàng");

// =======================
//  LẤY ORDER ITEMS
// =======================
$res_items = mysqli_query($conn, "
    SELECT oi.size, oi.qty, oi.price, p.name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $id
");

$items = [];
while ($row = mysqli_fetch_assoc($res_items)) {
    $items[] = $row;
}

// =======================
// UPDATE STATUS
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $status = $_POST['status'] ?? '';

    $allowed = ['pending','processing','shipping','completed','cancelled'];

    if (!in_array($status, $allowed)) {
        die("Trạng thái không hợp lệ");
    }

    $updates = ["status = '$status'"];

    // Automatically manage payment status for COD orders
    $currentPaymentStatus = $order['payment_status'] ?? 'unpaid';
    $currentPaymentMethod = $order['payment_method'] ?? '';
    
    if ($currentPaymentMethod === 'cod') {
        if ($status === 'completed' && $currentPaymentStatus !== 'paid') {
            $updates[] = "payment_status = 'paid'";
            $updates[] = "paid_at = NOW()";
        } elseif ($status !== 'completed' && $currentPaymentStatus === 'paid') {
            // Revert back to unpaid if they moved away from completed
            $updates[] = "payment_status = 'unpaid'";
            $updates[] = "paid_at = NULL";
        }
    }

    $updateStr = implode(', ', $updates);

    mysqli_query($conn, "
        UPDATE orders 
        SET $updateStr
        WHERE id = $id
    ");

    // Redirect to the same page to show updated info, or to orders.php
    header("Location: edit_status_product_user.php?id=$id");
    exit;
}

// label
$statusLabels = [
    'pending'    => 'Chờ xác nhận',
    'processing' => 'Đang xử lý',
    'shipping'   => 'Đang giao hàng',
    'completed'  => 'Đã hoàn thành',
    'cancelled'  => 'Đã hủy',
];
?>

<?php
$page_title = 'Thiết Lập Đơn Hàng';
$page_subtitle = 'Mã vận đơn #' . $order['id'];
$active_page = 'orders';
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div class="invoice-card">
    <div class="invoice-header">
        <div class="invoice-customer">
            <h3>Tên Khách Hàng/Người nhận: <?= htmlspecialchars(!empty($order['name']) ? $order['name'] : $order['username']) ?></h3>
            <p>📞 Phone: <?= htmlspecialchars($order['phone'] ?? '') ?></p>
            <p>📍 Location: <?= htmlspecialchars($order['address'] ?? '') ?></p>
        </div>
        <div>
            <span class="badge <?= $order['status'] ?> invoice-badge">
                <?= isset($statusLabels[$order['status']]) ? $statusLabels[$order['status']] : $order['status'] ?>
            </span>
        </div>
    </div>

    <!-- CHI TIẾT ĐƠN HÀNG -->
    <div class="invoice-details" style="margin-top: 20px; margin-bottom: 20px; padding: 15px; background: rgba(0,0,0,0.15); border-radius: 8px; border: 1px solid var(--border);">
        <h4 style="margin: 0 0 15px; font-family: var(--font-heading); font-size: 16px; color: var(--sidebar); border-bottom: 1px solid var(--border); padding-bottom: 10px;">Chi tiết đơn hàng / Giao dịch</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; color: var(--text);">
            <div>
                <p style="margin-bottom: 8px;"><strong>Ngày đặt hàng:</strong> <span style="color: var(--muted);"><?= !empty($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : '---' ?></span></p>
                <p style="margin-bottom: 8px;"><strong>Hình thức:</strong> 
                    <span style="color: var(--sidebar); font-weight: 600;">
                    <?php
                        $method = $order['payment_method'] ?? '';
                        if ($method === 'cod') echo 'Thanh toán khi nhận hàng (COD)';
                        elseif ($method === 'bank_transfer' || $method === 'bank') echo 'Chuyển khoản ngân hàng';
                        else echo htmlspecialchars($method);
                    ?>
                    </span>
                </p>
            </div>
            <div>
                <p style="margin-bottom: 8px;"><strong>Trạng thái thanh toán:</strong> 
                    <?php
                    $pStatus = $order['payment_status'] ?? 'unpaid';
                    if ($pStatus === 'paid') {
                        echo '<span style="color: #4cd137; font-weight: 700;">Đã thanh toán ' . (!empty($order['paid_at']) ? '('.date('d/m H:i', strtotime($order['paid_at'])).')' : '') . '</span>';
                    } else {
                        echo '<span style="color: #e84118; font-weight: 700;">Chưa thanh toán</span>';
                    }
                    ?>
                </p>
                <p style="margin-bottom: 8px;"><strong>Mã áp dụng giảm giá:</strong> 
                    <span style="color: var(--muted);">
                        <?= !empty($order['coupon_id']) ? 'Mã ID: ' . (int)$order['coupon_id'] . ' (-' . number_format((float)($order['discount_amount'] ?? 0)) . 'đ)' : 'Không có' ?>
                    </span>
                </p>
            </div>
            <div style="grid-column: 1 / -1; padding-top: 10px; border-top: 1px dashed var(--border);">
                <p style="margin: 0;"><strong>Ghi chú của khách hàng:</strong> <br/>
                <span style="color: var(--muted); display: inline-block; margin-top: 5px;">
                    <?= !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : '<em>Không có ghi chú</em>' ?>
                </span>
                </p>
            </div>
        </div>
    </div>

    <div class="invoice-items">
        <h4 style="margin: 0 0 15px; font-family: var(--font-heading); font-size: 18px; color: var(--sidebar);">Danh sách Món nước:</h4>
        <?php foreach ($items as $item): ?>
            <div class="invoice-item">
                <div class="invoice-item-info">
                    <strong><?= htmlspecialchars($item['name']) ?> (Size <?= htmlspecialchars($item['size'] ?? 'M') ?>)</strong>
                    <span>Số lượng: <?= $item['qty'] ?></span>
                </div>
                <div style="font-weight: 800; color: var(--sidebar); font-size: 17px;">
                    <?= number_format($item['price']) ?>đ
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="invoice-summary">
        Tổng hóa đơn chốt: <span><?= number_format($order['total']) ?>đ</span>
    </div>

    <!-- UPDATE STATUS FORM -->
    <div class="invoice-actions">
        <form method="POST" style="display: flex; gap: 20px; align-items: center; width: 100%;">
            <label style="font-weight: 800; color: var(--sidebar); flex-shrink: 0;">Trạng thái đơn (Live):</label>
            <select name="status" style="flex: 1;">
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn-save">💾 Đóng Dấu Cập Nhật</button>
        </form>
    </div>
</div>

<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>