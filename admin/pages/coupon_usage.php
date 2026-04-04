<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

$coupon_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($coupon_id <= 0) die("Mã giảm giá không hợp lệ");

$res_coupon = mysqli_query($conn, "SELECT code, type, value FROM coupons WHERE id = $coupon_id");
$coupon = mysqli_fetch_assoc($res_coupon);
if (!$coupon) die("Không tìm thấy mã giảm giá.");

// Xử lý chỉnh sửa lượt dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_usage') {
    $uid = (int)$_POST['user_id'];
    $new_count = max(0, (int)$_POST['used_count']);
    mysqli_query($conn, "UPDATE coupon_user_usage SET used_count = $new_count WHERE coupon_id = $coupon_id AND user_id = $uid");
    $res_sum = mysqli_query($conn, "SELECT SUM(used_count) as total_used FROM coupon_user_usage WHERE coupon_id = $coupon_id");
    $sum_row = mysqli_fetch_assoc($res_sum);
    $total_used = (int)($sum_row['total_used'] ?? 0);
    mysqli_query($conn, "UPDATE coupons SET used_count = $total_used WHERE id = $coupon_id");
    header("Location: coupon_usage.php?id=$coupon_id&success=1");
    exit;
}

// Lấy dữ liệu
$users_usage_rows = [];
$r1 = mysqli_query($conn, "
    SELECT cu.user_id, cu.used_count, u.username
    FROM coupon_user_usage cu
    LEFT JOIN users u ON u.id = cu.user_id
    WHERE cu.coupon_id = $coupon_id
    ORDER BY cu.used_count DESC
");
if ($r1) while ($row = mysqli_fetch_assoc($r1)) $users_usage_rows[] = $row;

$history_rows = [];
$r2 = mysqli_query($conn, "
    SELECT h.used_at, h.order_id, u.username
    FROM coupon_usage_history h
    LEFT JOIN users u ON u.id = h.user_id
    WHERE h.coupon_id = $coupon_id
    ORDER BY h.used_at DESC
");
if ($r2) while ($row = mysqli_fetch_assoc($r2)) $history_rows[] = $row;

$page_title = 'Kiểm Soát Lượt Dùng';
$page_subtitle = 'Mã: ' . htmlspecialchars($coupon['code']);
$active_page = 'coupons';

ob_start();
?>
<style>
.usage-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.usage-table { width: 100%; border-collapse: collapse; }
.usage-table th { padding: 12px 15px; text-align: left; border-bottom: 2px solid var(--border); font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
.usage-table td { padding: 12px 15px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
.usage-table tr:last-child td { border-bottom: none; }
.usage-table tr:hover td { background: rgba(0,0,0,0.02); }
.empty-state { padding: 30px; text-align: center; color: var(--muted); font-size: 14px; }
.count-input { width: 65px; padding: 6px 8px; border-radius: 6px; border: 1px solid var(--border); background: #f8f9fa; color: var(--text); font-size: 14px; text-align: center; }
.save-btn { padding: 5px 12px; font-size: 12px; font-weight: 700; background: var(--sidebar); color: #fff; border: none; border-radius: 6px; cursor: pointer; margin-left: 6px; }
.save-btn:hover { opacity: 0.85; }
.inline-form { display: inline-flex; align-items: center; }
.order-link { color: var(--accent); font-weight: 700; text-decoration: none; }
.order-link:hover { text-decoration: underline; }
.box-header { font-size: 16px; font-weight: 800; color: var(--sidebar); font-family: var(--font-heading); margin: 0 0 6px; padding-bottom: 14px; border-bottom: 1px solid var(--border); }
.box-sub { font-size: 13px; color: var(--muted); margin: 0 0 16px; }
</style>
<?php
$extra_css = ob_get_clean();
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div style="margin-bottom: 20px;">
    <a href="manage_coupons.php" class="btn-outline" style="text-decoration: none;">🔙 Quay Lại Danh Sách</a>
</div>

<?php if(isset($_GET['success'])): ?>
<div style="background:#dcfce7; color:#16a34a; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; border:1px solid #86efac;">
    ✅ Cập nhật số lượt dùng thành công!
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

    <!-- KHUNG 1: KHÁCH HÀNG -->
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:20px; padding:24px; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
        <h3 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--sidebar); padding-bottom:14px; border-bottom:1px solid #eee;">👥 Khách Hàng Đã Sử Dụng</h3>
        <p style="font-size:13px; color:var(--muted); margin:0 0 16px;">Admin có thể chủ động sửa lại số lượt khách đã xài ở đây.</p>

        <?php if (count($users_usage_rows) === 0): ?>
            <div style="padding:30px; text-align:center; color:var(--muted);">Chưa có khách hàng nào sử dụng mã này.</div>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:10px; text-align:left; border-bottom:2px solid #eee; font-size:13px; color:var(--muted); font-weight:700;">ID</th>
                    <th style="padding:10px; text-align:left; border-bottom:2px solid #eee; font-size:13px; color:var(--muted); font-weight:700;">Tài khoản</th>
                    <th style="padding:10px; text-align:left; border-bottom:2px solid #eee; font-size:13px; color:var(--muted); font-weight:700;">Lượt xài / Sửa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users_usage_rows as $u): ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px; font-size:14px;">#<?= (int)$u['user_id'] ?></td>
                    <td style="padding:10px; font-size:14px;"><strong><?= htmlspecialchars($u['username'] ?? '(Đã xóa)') ?></strong></td>
                    <td style="padding:10px;">
                        <form method="POST" style="margin:0; display:inline-flex; align-items:center; gap:8px;">
                            <input type="hidden" name="action" value="edit_usage">
                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                            <input type="number" name="used_count" value="<?= (int)$u['used_count'] ?>" min="0" style="width:60px; padding:6px; border-radius:6px; border:1px solid #ddd; font-size:14px; text-align:center;">
                            <button type="submit" style="padding:6px 12px; font-size:12px; font-weight:700; background:var(--sidebar); color:#fff; border:none; border-radius:6px; cursor:pointer;">Lưu</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- KHUNG 2: NHẬT KÝ -->
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:20px; padding:24px; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
        <h3 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--sidebar); padding-bottom:14px; border-bottom:1px solid #eee;">📋 Nhật Ký Chi Tiết</h3>
        <p style="font-size:13px; color:var(--muted); margin:0 0 16px;">Dữ liệu lưu vết tự động — chỉ xem, không chỉnh sửa.</p>

        <?php if (count($history_rows) === 0): ?>
            <div style="padding:30px; text-align:center; color:var(--muted);">Chưa có nhật ký ghi nhận.</div>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:10px; text-align:left; border-bottom:2px solid #eee; font-size:13px; color:var(--muted); font-weight:700;">Thời gian</th>
                    <th style="padding:10px; text-align:left; border-bottom:2px solid #eee; font-size:13px; color:var(--muted); font-weight:700;">Khách hàng</th>
                    <th style="padding:10px; text-align:left; border-bottom:2px solid #eee; font-size:13px; color:var(--muted); font-weight:700;">Đơn hàng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history_rows as $h): ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px; font-size:13px; color:var(--muted);"><?= date('d/m/Y H:i', strtotime($h['used_at'])) ?></td>
                    <td style="padding:10px; font-size:14px;"><strong><?= htmlspecialchars($h['username'] ?? '(Đã xóa)') ?></strong></td>
                    <td style="padding:10px;">
                        <?php if($h['order_id']): ?>
                            <a href="edit_status_product_user.php?id=<?= (int)$h['order_id'] ?>" style="color:var(--accent); font-weight:700; text-decoration:none;">#<?= (int)$h['order_id'] ?></a>
                        <?php else: ?>
                            <span style="color:var(--muted)">---</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>
