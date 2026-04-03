<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $value = (float)$_POST['value'];
    $min_order_value = (float)$_POST['min_order_value'];
    $usage_limit = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : "NULL";
    $is_active = (int)$_POST['is_active'];

    $sql = "INSERT INTO coupons (code, type, value, min_order_value, usage_limit, is_active) 
            VALUES ('$code', '$type', $value, $min_order_value, $usage_limit, $is_active)";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: manage_coupons.php");
        exit;
    } else {
        $error = "Lỗi: " . mysqli_error($conn);
    }
}

$page_title = 'Tạo Mã Giảm Giá';
$page_subtitle = 'Thiết lập chương trình khuyến mãi cho khách hàng';
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
        <h2>✨ Khởi tạo Khuyến Mãi</h2>
        <a href="manage_coupons.php" class="btn-back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            Hủy và Quay lại
        </a>
    </div>

    <?php if(isset($error)): ?>
        <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="code-preview">
            <span id="preview-text">NHẬP MÃ Ở DƯỚI</span>
        </div>

        <div class="form-grid">
            <div class="form-group full">
                <label>Mã Voucher (Code)</label>
                <input type="text" name="code" id="code-input" required style="text-transform: uppercase;" placeholder="Ví dụ: SUMMER30" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Loại Giảm Giá</label>
                <select name="type" required>
                    <option value="fixed">Tiền mặt (VNĐ)</option>
                    <option value="percentage">Phần trăm (%)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Mức Giảm</label>
                <input type="number" name="value" step="0.01" required placeholder="Ví dụ: 20000 hoặc 15">
            </div>

            <div class="form-group">
                <label>Đơn tối thiểu (VNĐ)</label>
                <input type="number" name="min_order_value" value="0" required placeholder="Ví dụ: 50000">
            </div>

            <div class="form-group">
                <label>Giới hạn số lượt dùng</label>
                <input type="number" name="usage_limit" placeholder="Để trống nếu Vô Cực">
            </div>

            <div class="form-group full">
                <label>Trạng Thái Hiệu Lực</label>
                <select name="is_active">
                    <option value="1">🟢 Kích Hoạt Ngay</option>
                    <option value="0">🔴 Lưu Nháp (Khóa)</option>
                </select>
            </div>
        </div>

        <div class="btn-submit-wrap">
            <button type="submit" class="btn-submit">🚀 Phát Hành Mã Này</button>
        </div>
    </form>
</div>

<script>
    const codeInput = document.getElementById('code-input');
    const previewText = document.getElementById('preview-text');
    
    codeInput.addEventListener('input', function() {
        if(this.value.trim() === '') {
            previewText.textContent = 'NHẬP MÃ TẠI ĐÂY';
            previewText.style.color = '#ccc';
        } else {
            previewText.textContent = this.value.toUpperCase();
            previewText.style.color = 'var(--sidebar)';
        }
    });
</script>

<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>
