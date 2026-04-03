<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

$where = "WHERE 1";

// search
if(!empty($_GET['keyword'])){
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $where .= " AND p.name LIKE '%$keyword%'";
}

// filter category
if(!empty($_GET['category'])){
    $cat = (int)$_GET['category'];
    $where .= " AND p.category_id = $cat";
}

$products = mysqli_query($conn,"
SELECT p.*, c.name as category 
FROM products p 
LEFT JOIN categories c ON c.id = p.category_id
$where
ORDER BY p.id DESC
");
?>
<?php
$page_title = 'Kho Trà Sữa';
$page_subtitle = 'Quản lý, đóng gói và thêm mới món uống';
$active_page = 'products';
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div style="margin-bottom: 20px;">
    <a href="add_product.php" class="btn-primary" style="text-decoration: none;">➕ Thêm Trà Sữa Mới</a>
</div>

<div class="box">
<form method="GET" class="filter-box">
    <input type="text" name="keyword" placeholder="Tìm sản phẩm...">

    <select name="category">
        <option value="">-- Danh mục --</option>
        <?php 
        $cats = mysqli_query($conn,"SELECT * FROM categories");
        while($c = mysqli_fetch_assoc($cats)){ ?>
            <option value="<?= $c['id'] ?>">
                <?= $c['name'] ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">Lọc</button>
</form>

<div class="data-list">
    <div class="data-header">
        <div class="col-id">Mã</div>
        <div class="col-info" style="flex:3;">Sản Phẩm</div>
        <div class="col-price" style="flex:1.5;">Giá Bán</div>
        <div class="col-cat">Phân Loại</div>
        <div class="col-status">Tồn Kho</div>
        <div class="col-action">Thiết Lập</div>
    </div>

    <?php while($p = mysqli_fetch_assoc($products)){ ?>
    <div class="data-item">
        <div class="col-id">#<?= $p['id'] ?></div>
        
        <div class="col-info" style="flex:3;">
            <img src="<?= BASE_URL ?>images/<?= $p['image'] ?>" class="data-avatar" onerror="this.src='https://placehold.co/100?text=No+Image'">
            <div class="col-info-text">
                <strong><?= htmlspecialchars($p['name']) ?></strong>
            </div>
        </div>
        
        <div class="col-price" style="flex:1.5;"><?= number_format($p['price_s'], 0, ',', '.') ?>đ - <?= number_format($p['price_l'], 0, ',', '.') ?>đ</div>
        
        <div class="col-cat"><?= $p['category'] ?></div>
        
        <div class="col-status">
            <?php if($p['stock'] > 0){ ?>
                <span class="badge completed">Còn hàng (<?= $p['stock'] ?>)</span>
            <?php } else { ?>
                <span class="badge cancelled">Hết hàng</span>
            <?php } ?>
        </div>
        
        <div class="col-action">
            <a class="btn-edit-icon" href="edit_product.php?id=<?= $p['id'] ?>" title="Chỉnh sửa">✏️</a>
            <a class="btn-delete-icon" href="../delete_product.php?id=<?= $p['id'] ?>" onclick="return confirm('Xóa siêu phẩm này khỏi kho?')" title="Xóa">🗑</a>
        </div>
    </div>
    <?php } ?>
</div>
</div>
<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>