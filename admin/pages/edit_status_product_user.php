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

    mysqli_query($conn, "
        UPDATE orders 
        SET status = '$status'
        WHERE id = $id
    ");

    header("Location: orders.php");
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
            <h3>Tên Khách Hàng: <?= htmlspecialchars($order['username']) ?></h3>
            <p>📞 Phone: <?= htmlspecialchars($order['phone']) ?></p>
            <p>📍 Location: <?= htmlspecialchars($order['address']) ?></p>
        </div>
        <div>
            <span class="badge <?= $order['status'] ?> invoice-badge">
                <?= $statusLabels[$order['status']] ?? $order['status'] ?>
            </span>
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