<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

// Lấy categories
$categories = mysqli_query($conn, "SELECT id, name FROM categories");

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price_s = (int)$_POST['price_s'];
    $price_m = (int)$_POST['price_m'];
    $price_l = (int)$_POST['price_l'];
    $stock = (int)$_POST['stock'];
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 1;
    $image = 'default.jpg'; // Ảnh mặc định

    // Cập nhật câu lệnh thêm product
    mysqli_query($conn,"
        INSERT INTO products(name, price_s, price_m, price_l, stock, category_id, image)
        VALUES('$name', '$price_s', '$price_m', '$price_l', '$stock', '$category_id', '$image')
    ");

    header("Location: manage_products.php");
    exit;
}

$page_title = 'Tạo Sản Phẩm Mới';
$page_subtitle = 'Mở rộng thực đơn với thức uống mới';
$active_page = 'products';

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

.product-preview {
    display: flex;
    align-items: center;
    gap: 20px;
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}
.product-preview-icon {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    font-size: 24px;
}
.product-preview-info {
    flex: 1;
}
.product-preview-info h3 {
    margin: 0 0 5px;
    font-size: 18px;
    color: var(--sidebar);
}
.product-preview-info p {
    margin: 0;
    color: #666;
    font-size: 13px;
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
</style>
<?php
$extra_css = ob_get_clean();

require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div class="form-card">
    <div class="form-header">
        <h2>✨ Thêm Sản Phẩm Khởi Tạo</h2>
        <a href="manage_products.php" class="btn-back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            Hủy và Quay lại
        </a>
    </div>

    <div class="product-preview">
        <div class="product-preview-icon">☕</div>
        <div class="product-preview-info">
            <h3 id="preview-name">Món Nước Chờ Bạn...</h3>
            <p>Menu Hệ thống Quản Lý Cửa Hàng</p>
        </div>
    </div>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group full">
                <label>Tên Sản Phẩm (Tên Món)</label>
                <input type="text" name="name" id="name-input" required placeholder="Ví dụ: Lục Trà Sữa Thạch Băng Tuyết" autocomplete="off">
            </div>

            <div class="form-group full" style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label>Giá Nhỏ (Size S)</label>
                    <input type="number" name="price_s" required>
                </div>
                <div style="flex: 1;">
                    <label>Giá Vừa (Size M)</label>
                    <input type="number" name="price_m" required>
                </div>
                <div style="flex: 1;">
                    <label>Giá Lớn (Size L)</label>
                    <input type="number" name="price_l" required>
                </div>
            </div>

            <div class="form-group">
                <label>Số Lượng Kho (Tồn Dư)</label>
                <input type="number" name="stock" required placeholder="Ví dụ: 999">
            </div>

            <div class="form-group full">
                <label>Nhóm Danh Mục</label>
                <select name="category_id" required>
                    <?php if($categories): ?>
                        <?php while($c = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= $c['id'] ?>">📁 <?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="btn-submit-wrap">
            <button type="submit" class="btn-submit">🚀 Thêm Món Vào Kho</button>
        </div>
    </form>
</div>

<script>
    const nameInput = document.getElementById('name-input');
    const previewName = document.getElementById('preview-name');
    
    nameInput.addEventListener('input', function() {
        if(this.value.trim() === '') {
            previewName.textContent = 'Món Nước Chờ Bạn...';
            previewName.style.color = '#ccc';
        } else {
            previewName.textContent = this.value;
            previewName.style.color = 'var(--sidebar)';
        }
    });
</script>

<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>