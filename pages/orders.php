<?php
require_once(__DIR__ . "/../config/config.php");

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Xây dựng câu truy vấn lấy đơn hàng
$sql_orders = "SELECT * FROM orders WHERE user_id = ?";
if ($status_filter !== 'all') {
    $sql_orders .= " AND status = ?";
}
$sql_orders .= " ORDER BY created_at DESC";

$stmt_orders = mysqli_prepare($conn, $sql_orders);
if ($status_filter !== 'all') {
    mysqli_stmt_bind_param($stmt_orders, "is", $uid, $status_filter);
} else {
    mysqli_stmt_bind_param($stmt_orders, "i", $uid);
}
mysqli_stmt_execute($stmt_orders);
$orders_res = mysqli_stmt_get_result($stmt_orders);

$orders = [];
while ($row = mysqli_fetch_assoc($orders_res)) {
    $order_id = $row['id'];
    $sql_items = "SELECT oi.size, oi.qty, oi.price, p.name, p.image 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
    $stmt_items = mysqli_prepare($conn, $sql_items);
    mysqli_stmt_bind_param($stmt_items, "i", $order_id);
    mysqli_stmt_execute($stmt_items);
    $items_res = mysqli_stmt_get_result($stmt_items);
    
    $items = [];
    while ($item = mysqli_fetch_assoc($items_res)) {
        $items[] = $item;
    }
    
    $row['items'] = $items;
    $orders[] = $row;
}

$page_css = "orders.css";
include(__DIR__ . "/../includes/header.php");
?>

<main class="orders-page">
    <div class="orders-container">
        <header class="orders-header">
            <h1 class="orders-title">Lịch Sử Đặt Hàng</h1>
            
            <div class="status-filter">
                <a href="?status=all" class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>">Tất cả</a>
                <a href="?status=pending" class="filter-btn <?= $status_filter === 'pending' ? 'active' : '' ?>">Chờ Duyệt</a>
                <a href="?status=processing" class="filter-btn <?= $status_filter === 'processing' ? 'active' : '' ?>">Đang Pha Chế</a>
                <a href="?status=shipping" class="filter-btn <?= $status_filter === 'shipping' ? 'active' : '' ?>">Đang Giao</a>
                <a href="?status=completed" class="filter-btn <?= $status_filter === 'completed' ? 'active' : '' ?>">Thành Công</a>
                <a href="?status=cancelled" class="filter-btn <?= $status_filter === 'cancelled' ? 'active' : '' ?>">Đã Hủy</a>
            </div>
        </header>

        <div class="orders-list">
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <div class="empty-icon">🛍️</div>
                    <p class="empty-text">Thật tiếc, bạn chưa đăng ký món nước nào để theo dõi.</p>
                    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary">Khám Phá Menu</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $index => $order): ?>
                    <div class="order-card" style="animation-delay: <?= ($index % 10) * 0.1 ?>s">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="info-item">
                                    <span class="info-label">Mã đơn hàng</span>
                                    <span class="info-value">#<?= $order['id'] ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Khởi tạo</span>
                                    <span class="info-value"><?= date('H:i - d/m/Y', strtotime($order['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="order-status">
                                <span class="badge status-<?= $order['status'] ?>">
                                    <?php
                                    switch($order['status']) {
                                        case 'pending': echo 'Đợi Nhà Làm'; break;
                                        case 'pending_payment': echo 'Cho Thanh Toán'; break;
                                        case 'processing': echo 'Người Dưng Pha Chế'; break;
                                        case 'shipping': echo 'Tiến Tới Khách Hàng'; break;
                                        case 'shipped': echo 'Tiến Tới Khách Hàng'; break;
                                        case 'completed': echo 'Chuyến Hàng Êm Ấm'; break;
                                        case 'cancelled': echo 'Hủy Non...'; break;
                                        default: echo $order['status'];
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-items-list">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <img src="<?= BASE_URL ?>images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-img" onerror="this.src='<?= BASE_URL ?>images/no-image.png'">
                                        <div class="item-details">
                                            <h4 class="item-name"><?= htmlspecialchars($item['name']) ?> (Size <?= htmlspecialchars($item['size'] ?? 'M') ?>)</h4>
                                            <p class="item-meta">(Số lượng: <?= $item['qty'] ?>)</p>
                                        </div>
                                        <div class="item-price">
                                            <?= number_format($item['price'], 0, ',', '.') ?>đ
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                <span class="order-total-label">Thành Tiền:</span>
                                <span class="order-total-value"><?= number_format($order['total'], 0, ',', '.') ?>đ</span>
                            </div>
                            <div class="order-actions">
                                <!-- Nút hủy đơn: Chỉ hiện khi trạng thái là pending -->
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button class="btn-sm btn-outline danger" onclick="cancelOrder(<?= $order['id'] ?>)">Yêu cầu Hủy</button>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'completed' || $order['status'] === 'cancelled'): ?>
                                    <button class="btn-sm btn-outline" onclick="reorder(<?= $order['id'] ?>)">Đặt lại Món Cũ</button>
                                <?php endif; ?>
                                
                                <a href="<?= BASE_URL ?>pages/order_detail.php?id=<?= $order['id'] ?>" class="btn-sm btn-primary">Hóa Đơn Cụ Thể</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function cancelOrder(orderId) {
    if (confirm("LườiTea rất tiếc! Bạn có chắc chắn muốn hủy Đơn hàng #" + orderId + " không?")) {
        fetch("<?= BASE_URL ?>api/cancel_order.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Đã nhận yêu cầu RÚT LUI thành công!");
                location.reload();
            } else {
                alert(data.message || "Tài trễ, hủy đơn hàng không được lúc này.");
            }
        })
        .catch(() => alert("Lỗi kết nối từ server."));
    }
}

function reorder(orderId) {
    alert("Tính năng tự động lên xe Món Số #" + orderId + " đang được LườiTea phát triển!");
}
</script>

<?php include(__DIR__ . "/../includes/footer.php"); ?>
