<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

$orders = mysqli_query($conn, "
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
");



$statusLabels = [
    'pending'    => 'Chờ xác nhận',
    'pending_payment' => 'Chờ thanh toán',
    'processing' => 'Đang xử lý',
    'shipping'   => 'Đang giao hàng',
    'completed'  => 'Đã hoàn thành',
    'cancelled'  => 'Đã hủy',
];
?>

<?php
$page_title = 'Kiểm Soát Đơn Hàng';
$page_subtitle = 'Theo dõi ly trà giao tay khách';
$active_page = 'orders';
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div class="box">
<div class="data-list">
    <div class="data-header">
        <div class="col-id">Mã Đơn</div>
        <div class="col-info" style="flex:2.5;">Khách Hàng</div>
        <div class="col-price" style="flex:1;">Tổng Tiền</div>
        <div class="col-date" style="flex:1.5;">Ngày Tạo</div>
        <div class="col-status" style="flex:1.5;">Trạng Thái</div>
        <div class="col-action" style="flex:1.5;">Hành Động</div>
    </div>

    <?php while ($o = mysqli_fetch_assoc($orders)) {
        $status = trim((string)($o['status'] ?? ''));
        if ($status === '' || !isset($statusLabels[$status])) {
            $status = 'pending';
        }
    ?>
    <div class="data-item">
        <div class="col-id">#<?= (int)$o['id'] ?></div>
        
        <div class="col-info" style="flex:2.5;">
            <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($o['username']) ?>" class="data-avatar user-avatar" alt="Avatar">
            <div class="col-info-text">
                <strong style="color: var(--sidebar);"><?= htmlspecialchars($o['username']) ?></strong>
                <span>Khách hàng</span>
            </div>
        </div>
        
        <div class="col-price" style="flex:1;">$<?= htmlspecialchars($o['total']) ?></div>
        
        <div class="col-date" style="flex:1.5; font-size: 13px; font-weight: 700; color: var(--muted);"><?= htmlspecialchars($o['created_at']) ?></div>
        
        <div class="col-status" style="flex:1.5;">
            <span class="badge <?= htmlspecialchars($status) ?>">
                <?= htmlspecialchars($statusLabels[$status]) ?>
            </span>
        </div>
        
        <div class="col-action" style="flex:1.5;">
            <a href="edit_status_product_user.php?id=<?= (int)$o['id'] ?>" class="btn-outline" style="border-radius: 10px; font-size: 13px; font-weight: 800; border-width: 2px;">Thiết lập</a>
            <button class="btn-delete-icon" onclick="deleteOrder(<?= (int)$o['id'] ?>)" title="Xóa đơn hàng">🗑</button>
        </div>
    </div>
    <?php } ?>
</div>
    </div>

<script src="../js/admin.js"></script>
<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>