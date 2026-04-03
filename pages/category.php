<?php
require_once(__DIR__ . "/../config/config.php");

$page_css = "product.css";
include(__DIR__ . "/../includes/header.php");

$category_id = intval($_GET['id'] ?? 0);
$search = $_GET['search'] ?? '';
$min = isset($_GET['min']) && $_GET['min'] !== '' ? intval($_GET['min']) : 0;
$max = isset($_GET['max']) && $_GET['max'] !== '' ? intval($_GET['max']) : 1000000;
$sort = $_GET['sort'] ?? 'newest';

if ($min > $max) {
    $tmp = $min; $min = $max; $max = $tmp;
}

$search_esc = mysqli_real_escape_string($conn, $search);

$sql = "SELECT id, name, price_s, price_m, price_l, image, description, stock
        FROM products
        WHERE category_id = {$category_id}
        AND price_m BETWEEN {$min} AND {$max}";

if ($search_esc !== '') {
    $sql .= " AND name LIKE '%{$search_esc}%'";
}

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price_m ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price_m DESC";
        break;
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$result = mysqli_query($conn, $sql);

$category_name = "Sản phẩm";
$res_cat = mysqli_query($conn, "SELECT name FROM categories WHERE id = $category_id");
if ($cat_row = mysqli_fetch_assoc($res_cat)) {
    $category_name = $cat_row['name'];
}

$is_filtering = !empty($search) || $min > 0 || $max < 1000000 || $sort !== 'newest';
?>

<section class="products product-page">
  <div class="container">

    <div class="products-header">
        <h2 class="products-title">
            <?php if (!empty($search)): ?>
                Kết quả cho "<?= htmlspecialchars($search) ?>" trong <?= htmlspecialchars($category_name) ?>
            <?php else: ?>
                <?= htmlspecialchars($category_name) ?>
            <?php endif; ?>
        </h2>
        <p class="products-subtitle">Thưởng thức hương vị đặc trưng của chúng tôi</p>
    </div>

    <!-- Filter form -->
    <div class="filter-section">
      <form method="GET" class="filter-form">
        <input type="hidden" name="id" value="<?= htmlspecialchars($category_id) ?>">

        <div class="search-wrapper">
          <span class="search-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </span>
          <input
            type="text"
            name="search"
            placeholder="Tìm trong danh mục này..."
            value="<?= htmlspecialchars($search) ?>"
          >
        </div>

        <div class="price-filter">
          <label>Giá:</label>
          <input type="number" name="min" value="<?= $min > 0 ? htmlspecialchars($min) : '' ?>" min="0" placeholder="Từ">
          <span class="separator">-</span>
          <input type="number" name="max" value="<?= $max < 1000000 ? htmlspecialchars($max) : '' ?>" min="0" placeholder="Đến">
        </div>

        <div class="sort-filter">
            <label>Sắp xếp:</label>
            <select name="sort" onchange="this.form.submit()">
                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none; margin-left:-25px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </div>

        <div style="display: flex; gap: 10px; margin-left: auto; align-items:center;">
            <?php if ($is_filtering): ?>
                <a href="<?= BASE_URL ?>pages/category.php?id=<?= $category_id ?>" class="btn-clear">
                    Xóa bộ lọc
                </a>
            <?php endif; ?>
            <button type="submit" class="btn-submit">Lọc sản phẩm</button>
        </div>
      </form>
    </div>

    <!-- Grid -->
    <div class="product-grid">
      <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <article class="product-card" aria-labelledby="prod-<?= $row['id'] ?>">
            <div class="product-thumb-wrap">
              <a class="product-thumb" href="<?= BASE_URL ?>pages/product_detail.php?id=<?= $row['id'] ?>" aria-label="<?= htmlspecialchars($row['name']) ?>">
                <?php if (!empty($row['image'])): ?>
                  <img
                    src="<?= BASE_URL ?>images/<?= htmlspecialchars($row['image']) ?>"
                    alt="<?= htmlspecialchars($row['name']) ?>"
                    onerror="this.onerror=null; this.outerHTML='<div class=\'no-image-brand\'>LườiTea<br><span>House</span></div>';"
                  >
                <?php else: ?>
                  <div class="no-image-brand">LườiTea<br><span>House</span></div>
                <?php endif; ?>
              </a>
              
              <div class="card-badges">
                <?php if (isset($row['stock']) && $row['stock'] > 0): ?>
                  <span class="status-badge status-in">Còn hàng</span>
                <?php else: ?>
                  <span class="status-badge status-out">Hết hàng</span>
                <?php endif; ?>
              </div>

              <div class="hover-actions">
                <button 
                  class="btn-hover add-cart" 
                  onclick="addCart(<?= (int)$row['id'] ?>)" 
                  <?= (isset($row['stock']) && $row['stock'] <= 0) ? 'disabled' : '' ?>
                >
                  Thêm vào giỏ hàng
                </button>
                <a class="btn-hover" href="<?= BASE_URL ?>pages/product_detail.php?id=<?= $row['id'] ?>" aria-label="Xem chi tiết">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </a>
              </div>
            </div>

            <div class="product-info">
              <a id="prod-<?= $row['id'] ?>" class="product-name" href="<?= BASE_URL ?>pages/product_detail.php?id=<?= $row['id'] ?>">
                <?= htmlspecialchars($row['name']) ?>
              </a>
              <p class="product-desc"><?= htmlspecialchars($row['description'] ?? '') ?></p>
              
              <div class="product-meta">
                <div class="price-sale"><?= number_format($row['price_s'], 0, ',', '.') ?>đ - <?= number_format($row['price_l'], 0, ',', '.') ?>đ</div>
                <button 
                  class="btn-mobile-cart" 
                  aria-label="Thêm vào giỏ" 
                  onclick="addCart(<?= (int)$row['id'] ?>)" 
                  <?= (isset($row['stock']) && $row['stock'] <= 0) ? 'disabled' : '' ?>
                >
                   <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                </button>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <span class="empty-icon">🌱</span>
          <h3 class="empty-title">Không tìm thấy sản phẩm</h3>
          <p class="empty-desc">Không có đồ uống nào phù hợp với bộ lọc hiện tại của bạn.</p>
          <a href="<?= BASE_URL ?>pages/category.php?id=<?= $category_id ?>" class="btn-primary-large">Xem tất cả sản phẩm trong mục này</a>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>
 
<?php include(__DIR__ . "/../includes/footer.php"); ?>
