<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

$coupons = mysqli_query($conn, "SELECT * FROM coupons ORDER BY id DESC");
?>
<?php
$page_title = 'Mã Giảm Giá';
$page_subtitle = 'Tạo voucher kích cầu thả ga';
$active_page = 'coupons';
$extra_css = '
<style>
    .badge-active { background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 800; display: inline-block; text-transform: uppercase;}
    .badge-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 800; display: inline-block; text-transform: uppercase;}
</style>';
require_once(__DIR__ . "/../includes/admin_header.php");
?>
<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

$coupons = mysqli_query($conn, "SELECT * FROM coupons ORDER BY id DESC");
?>
<?php
$page_title = 'Mã Giảm Giá';
$page_subtitle = 'Tạo voucher kích cầu thả ga';
$active_page = 'coupons';
$extra_css = '
<style>
    .badge-active { background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 800; display: inline-block; text-transform: uppercase;}
    .badge-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 800; display: inline-block; text-transform: uppercase;}
</style>';
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div style="margin-bottom: 20px;">
    <a href="add_coupon.php" class="btn-primary" style="text-decoration: none;">➕ Cấp Mã Mới</a>
</div>

<div class="box">
<div class="data-list">
            <div class="data-header">
                <div class="col-id">ID</div>
                <div class="col-info" style="flex:2;">Mã Khuyến Mãi</div>
                <div class="col-cat">Phân Loại</div>
                <div class="col-price" style="flex:1.5;">Giá Trị Giảm</div>
                <div class="col-status">Điều Kiện / Lượt</div>
                <div class="col-date" style="flex:1.2;">Trạng Thái</div>
                <div class="col-action">Thao Tác</div>
            </div>
            
            <?php while($c = mysqli_fetch_assoc($coupons)): ?>
            <div class="data-item">
                <div class="col-id">#<?= $c['id'] ?></div>
                
                <div class="col-info" style="flex:2;">
                    <div class="col-info-text">
                        <strong style="font-size: 18px; color: var(--accent); letter-spacing: 1px; font-family: var(--font-heading);"><?= htmlspecialchars($c['code']) ?></strong>
                    </div>
                </div>
                
                <div class="col-cat"><?= $c['type'] === 'fixed' ? 'Giảm Trực Tiếp' : 'Giảm Theo %' ?></div>
                
                <div class="col-price" style="flex:1.5;"><?= $c['type'] === 'fixed' ? number_format($c['value'], 0, ',', '.') . 'đ' : (int)$c['value'] . '%' ?></div>
                
                <div class="col-status" style="display: flex; flex-direction: column; gap: 4px;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--sidebar);">Đơn từ: <?= number_format($c['min_order_value'], 0, ',', '.') ?>đ</span>
                    <span style="font-size: 12px; color: var(--muted); font-weight: 600;">Dùng: <?= $c['used_count'] ?> / <?= $c['usage_limit'] ?? '∞' ?></span>
                </div>
                
                <div class="col-date" style="flex:1.2;">
                    <span class="<?= $c['is_active'] ? 'badge completed' : 'badge cancelled' ?>">
                        <?= $c['is_active'] ? 'Hoạt động' : 'Tạm khóa' ?>
                    </span>
                </div>
                
                <div class="col-action actions">
                    <a class="btn-edit-icon" href="edit_coupon.php?id=<?= $c['id'] ?>" title="Sửa mã">✏️</a>
                    <a class="btn-delete-icon" href="../delete_coupon.php?id=<?= $c['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa mã này?')" title="Xóa mã">🗑</a>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if (mysqli_num_rows($coupons) == 0): ?>
                <div style="text-align: center; padding: 40px; color: var(--muted); font-weight: 700;">Chưa có mã giảm giá nào được tung ra.</div>
            <?php endif; ?>
        </div>
    </div>
<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>
