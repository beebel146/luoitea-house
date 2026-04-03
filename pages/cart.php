<?php
require_once(__DIR__ . "/../config/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$cart_items = [];
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $first_ids = array_map(function($val){
        $parts = explode('_', $val);
        return intval($parts[0]);
    }, array_keys($_SESSION['cart']));
    $first_ids = array_unique($first_ids);
    $ids = implode(',', $first_ids);

    $sql = "SELECT id, name, price_s, price_m, price_l, image, stock FROM products WHERE id IN ($ids)";
    $result = mysqli_query($conn, $sql);
    
    $products_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products_data[$row['id']] = $row;
    }

    foreach($_SESSION['cart'] as $id_size => $qty) {
        $parts = explode('_', $id_size);
        $product_id = intval($parts[0]);
        $size = $parts[1] ?? 'M';
        
        if(isset($products_data[$product_id])) {
            $p = $products_data[$product_id];
            $price_key = 'price_' . strtolower($size);
            
            $cart_items[] = [
                'id_size' => $id_size,
                'id' => $product_id,
                'name' => $p['name'],
                'size' => $size,
                'price' => $p[$price_key],
                'price_s' => $p['price_s'],
                'price_m' => $p['price_m'],
                'price_l' => $p['price_l'],
                'image' => $p['image'],
                'stock' => $p['stock'],
                'quantity' => $qty
            ];
        }
    }
}

$page_css = "cart.css";
include("../includes/header.php");
?>

<div class="cart-page">
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <span class="empty-cart-icon">🛍️</span>
            <h3>Giỏ hàng đang buồn!</h3>
            <p>Hương vị trà tuyệt hảo đang chờ bạn. Hãy thêm vài món vào giỏ nhé.</p>
            <a href="<?= BASE_URL ?>index.php" class="btn-go-shop">Khám phá thực đơn ngay</a>
        </div>
    <?php else: ?>
        <style>
        .size-dropdown {
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #e1e1e1;
            background-color: #fff;
            color: #333;
            font-family: 'Mulish', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .size-dropdown:hover {
            border-color: #bbb;
            background-color: #fafafa;
        }
        .size-dropdown:focus {
            border-color: var(--brand, #333);
            box-shadow: 0 0 0 3px rgba(0,0,0, 0.05);
        }
        </style>
        <h2 class="cart-title">
            Giỏ hàng của tôi
            <span class="cart-count-badge"><?= count($cart_items) ?> món</span>
        </h2>

        <div class="cart-layout">
            <!-- LEFT: Product list -->
            <div class="cart-left">
                
                <div class="cart-batch-header">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="check-all" onclick="toggleCheckAll(this)">
                        <span>Chọn tất cả (<span id="total-count-selected">0</span>)</span>
                        <!-- Hidden footer check to satisfy JS dependencies if required -->
                        <input type="checkbox" id="check-all-footer" onclick="toggleCheckAll(this)" hidden>
                    </label>
                    <button class="btn-delete-batch" onclick="removeSelectedItems()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        Xóa lựa chọn
                    </button>
                </div>

                <div id="cart-item-list">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-box item-row" id="item-row-<?= $item['id_size'] ?>">
                        <div class="col-check">
                            <input type="checkbox" class="item-check" value="<?= $item['id_size'] ?>" onclick="calculateTotal()">
                        </div>
                        
                        <div class="col-product">
                            <img src="<?= BASE_URL ?>images/<?= $item['image'] ?>" class="product-img" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>images/default.jpg';">
                            <div class="product-info">
                                <a href="<?= BASE_URL ?>pages/product_detail.php?id=<?= $item['id'] ?>" class="product-name">
                                    <?= htmlspecialchars($item['name']) ?>
                                </a>
                                <div style="margin-top: 5px;">
                                    <select class="size-dropdown" onchange="changeSize('<?= $item['id_size'] ?>', this.value)">
                                        <option value="S" <?= $item['size'] == 'S' ? 'selected' : '' ?>>Nhỏ (S) - <?= number_format($item['price_s'], 0, ',', '.') ?>đ</option>
                                        <option value="M" <?= $item['size'] == 'M' ? 'selected' : '' ?>>Vừa (M) - <?= number_format($item['price_m'], 0, ',', '.') ?>đ</option>
                                        <option value="L" <?= $item['size'] == 'L' ? 'selected' : '' ?>>Lớn (L) - <?= number_format($item['price_l'], 0, ',', '.') ?>đ</option>
                                    </select>
                                </div>
                                <div class="product-unit-price col-price" id="price-<?= $item['id_size'] ?>" data-price="<?= $item['price'] ?>" style="margin-top: 5px;">
                                    <?= number_format($item['price'], 0, ',', '.') ?>đ
                                </div>
                            </div>
                        </div>

                        <div class="col-qty">
                            <div class="qty-control">
                                <button class="qty-btn" onclick="updateQty('<?= $item['id_size'] ?>', -1)">−</button>
                                <input type="text" class="qty-input" id="qty-<?= $item['id_size'] ?>" value="<?= $item['quantity'] ?>" data-stock="<?= $item['stock'] ?>" readonly>
                                <button class="qty-btn" onclick="updateQty('<?= $item['id_size'] ?>', 1)">+</button>
                            </div>
                        </div>

                        <div class="col-subtotal" id="subtotal-<?= $item['id_size'] ?>">
                            <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                        </div>

                        <div class="col-action">
                            <button class="btn-delete" title="Xóa" onclick="removeItem('<?= $item['id_size'] ?>')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order note -->
                <div class="cart-note">
                    <div class="cart-note-label">
                        <span>📝 Ghi chú cho quán</span>
                        <span class="cart-note-counter">
                            <span id="note-char-count">0</span>/300
                        </span>
                    </div>
                    <textarea id="order-note" maxlength="300"
                              placeholder="Ví dụ: Lấy ít đường, không đá, cắt lát chanh thêm..."
                              rows="3"
                              oninput="updateNoteCounter(this)"></textarea>
                </div>
            </div><!-- /cart-left -->

            <!-- RIGHT: Summary panel -->
            <div class="cart-right">
                <div class="cart-summary">
                    <div class="summary-header">
                        Hóa đơn tạm tính
                    </div>
                    <div class="summary-body">
                        <!-- Discount code -->
                        <div class="discount-area">
                            <input type="text" placeholder="Nhập mã ưu đãi..." id="discount-code">
                            <button onclick="applyDiscount()">Áp dụng</button>
                        </div>

                        <!-- Summary rows -->
                        <div class="summary-row">
                            <span class="label">Sản phẩm đã chọn</span>
                            <span class="value"><span id="total-items">0</span> món</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Tổng cộng</span>
                            <span class="value" id="subtotal-display">0đ</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Khấu trừ</span>
                            <span class="value" id="discount-display" style="color: var(--brand);">—</span>
                        </div>

                        <!-- Total -->
                        <div class="summary-total">
                            <span class="total-label">Cần thanh toán</span>
                            <span class="total-price" id="total-price-display">0đ</span>
                        </div>

                        <!-- Checkout -->
                        <button class="btn-checkout" onclick="goToCheckout()">
                            Thiết lập Đặt hàng
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                        </button>
                        <a href="<?= BASE_URL ?>index.php" class="btn-continue">← Tiếp tục nhâm nhi đồ uống</a>
                    </div>
                </div>
            </div><!-- /cart-right -->
        </div><!-- /cart-layout -->
    <?php endif; ?>
</div>

<script>
    window.BASE_URL = '<?= rtrim(BASE_URL, '/'); ?>/';
</script>
<script src="<?= BASE_URL ?>js/cart.js"></script>
<script src="<?= BASE_URL ?>js/form_checkout.js"></script>

<?php include(__DIR__ . "/../includes/footer.php"); ?>
