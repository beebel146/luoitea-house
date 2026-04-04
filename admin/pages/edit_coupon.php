<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

$id = (int)$_GET['id'];
$coupon = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM coupons WHERE id = $id"));

if (!$coupon) {
    header("Location: manage_coupons.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $value = (float)$_POST['value'];
    $min_order_value = (float)$_POST['min_order_value'];
    $usage_limit = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : "NULL";
    $is_active = (int)$_POST['is_active'];

    $sql = "UPDATE coupons SET 
            code = '$code', 
            type = '$type', 
            value = $value, 
            min_order_value = $min_order_value, 
            usage_limit = $usage_limit, 
            is_active = $is_active 
            WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: manage_coupons.php");
        exit;
    } else {
        $error = "Lỗi: " . mysqli_error($conn);
    }
}

$page_title = 'Cập Nhật Mã Khuyến Mãi';
$page_subtitle = 'Điều chỉnh thông tin và quyền hạn sử dụng voucher';
$active_page = 'coupons';

ob_start();
?>
<style>
.form-card {
    background: #fff;
    border-radius: 20px;
    padding: 35px 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.04);
    max-width: 800px;
    margin: 0 auto;
}
.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f2f5;
}
.form-header h2 {
    font-size: 22px;
    color: var(--sidebar);
    margin: 0;
}
.btn-back-link {
    color: #666;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.3s ease;
}
.btn-back-link:hover {
    color: var(--sidebar);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}
.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}
.form-group input, .form-group select {
    width: 100%;
    padding: 14px 18px;
    border: 1px solid #e1e5ea;
    border-radius: 12px;
    font-family: 'Mulish', sans-serif;
    font-size: 15px;
    color: #333;
    transition: all 0.3s ease;
    background: #fdfdfd;
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--sidebar);
    box-shadow: 0 0 0 4px rgba(22, 72, 99, 0.1);
    outline: none;
    background: #fff;
}

.code-preview {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    margin-bottom: 25px;
}
.code-preview span {
    font-weight: 800;
    font-size: 24px;
    color: var(--sidebar);
    letter-spacing: 2px;
}

.btn-submit-wrap {
    margin-top: 35px;
    display: flex;
    justify-content: flex-end;
}
.btn-submit {
    background: var(--sidebar);
    color: #fff;
    border: none;
    padding: 16px 35px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    font-family: 'Mulish', sans-serif;
    cursor: pointer;
    transition: transform 0.2s, background 0.3s;
    box-shadow: 0 8px 15px rgba(22, 72, 99, 0.2);
}
.btn-submit:hover {
    background: #0f3547;
    transform: translateY(-2px);
}
.error-msg {
    color: #dc3545;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    font-weight: 600;
}
</style>
<?php
$extra_css = ob_get_clean();
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div class="form-card">
    <div class="form-header">
        <h2>✏️ Cập nhật mã "<?= htmlspecialchars($coupon['code']) ?>"</h2>
        <a href="manage_coupons.php" class="btn-back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            Quay Về Danh Sách
        </a>
    </div>

    <?php if(isset($error)): ?>
        <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="code-preview">
            <span id="preview-text"><?= htmlspecialchars($coupon['code']) ?></span>
        </div>

        <div class="form-grid">
            <div class="form-group full">
                <label>Mã Voucher (Code)</label>
                <input type="text" name="code" id="code-input" value="<?= htmlspecialchars($coupon['code']) ?>" required style="text-transform: uppercase;" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Loại Giảm Giá</label>
                <select name="type" required>
                    <option value="fixed" <?= $coupon['type'] === 'fixed' ? 'selected' : '' ?>>Tiền mặt (VNĐ)</option>
                    <option value="percentage" <?= $coupon['type'] === 'percentage' ? 'selected' : '' ?>>Phần trăm (%)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Mức Giảm</label>
                <input type="number" name="value" step="0.01" value="<?= $coupon['value'] ?>" required>
            </div>

            <div class="form-group">
                <label>Đơn tối thiểu (VNĐ)</label>
                <input type="number" name="min_order_value" value="<?= $coupon['min_order_value'] ?>" required>
            </div>

            <div class="form-group">
                <label>Giới hạn số lượt dùng</label>
                <input type="number" name="usage_limit" value="<?= $coupon['usage_limit'] ?>" placeholder="Để trống nếu Vô Cực">
            </div>

            <div class="form-group full">
                <label>Trạng Thái Hiện Tại</label>
                <select name="is_active">
                    <option value="1" <?= $coupon['is_active'] == 1 ? 'selected' : '' ?>>🟢 Đang Hoạt Động</option>
                    <option value="0" <?= $coupon['is_active'] == 0 ? 'selected' : '' ?>>🔴 Tạm Khóa</option>
                </select>
            </div>
        </div>

        <div class="btn-submit-wrap">
            <button type="submit" class="btn-submit">💾 Lưu Thay Đổi</button>
        </div>
    </form>
</div>

<script>
    const codeInput = document.getElementById('code-input');
    const previewText = document.getElementById('preview-text');
    
    codeInput.addEventListener('input', function() {
        if(this.value.trim() === '') {
            previewText.textContent = '...';
            previewText.style.color = '#ccc';
        } else {
            previewText.textContent = this.value.toUpperCase();
            previewText.style.color = 'var(--sidebar)';
        }
    });
</script>

<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>
