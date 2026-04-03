<?php
require_once(__DIR__ . "/../config/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$uid = (int)$_SESSION['user_id'];

// Lấy thông tin user từ DB
$stmt = mysqli_prepare($conn, "SELECT display_name, phone, address FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Decode phone & address (JSON array từ profile.php)
$user_phones = json_decode($user['phone'] ?? '[]', true);
if (!is_array($user_phones)) {
    $user_phones = ($user['phone'] ?? '') !== '' ? [$user['phone']] : [];
}
$user_phones = array_values(array_filter($user_phones));

$user_addresses = json_decode($user['address'] ?? '[]', true);
if (!is_array($user_addresses)) {
    $user_addresses = ($user['address'] ?? '') !== '' ? [$user['address']] : [];
}
$user_addresses = array_values(array_filter($user_addresses));

$display_name  = htmlspecialchars($user['display_name'] ?? '');
$first_phone   = htmlspecialchars($user_phones[0] ?? '');
$first_address = htmlspecialchars($user_addresses[0] ?? '');

// Lấy sản phẩm checkout
$checkout_ids = $_SESSION['checkout_items'] ?? [];
$cart_items   = [];

if (!empty($checkout_ids) && isset($_SESSION['cart'])) {
    $first_ids = array_map(function($val){
        $parts = explode('_', $val);
        return intval($parts[0]);
    }, $checkout_ids);
    $first_ids = array_unique($first_ids);
    $ids = implode(',', $first_ids);

    $res = mysqli_query($conn, "SELECT id, name, price_s, price_m, price_l, image FROM products WHERE id IN ($ids)");
    $products_data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $products_data[$row['id']] = $row;
    }

    foreach ($checkout_ids as $id_size) {
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
                'image' => $p['image'],
                'quantity' => $_SESSION['cart'][$id_size] ?? 0
            ];
        }
    }
}

// Lấy coupon từ session (đã áp dụng ở trang cart)
$session_coupon_id = $_SESSION['coupon_id'] ?? null;
$session_discount = $_SESSION['discount_amount'] ?? 0;

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$total = $subtotal - $session_discount;

$page_css = "checkout.css";
include("../includes/header.php");
?>

<div class="checkout-page">
    <h1 class="checkout-title">Xác nhận thanh toán</h1>
    <p class="checkout-subtitle">Bạn chỉ còn một thao tác nữa để thưởng thức hương vị trà tuyệt hảo.</p>

    <?php if (empty($cart_items)): ?>
      <div class="empty-cart-msg">
        <span class="empty-cart-icon">🛒</span>
        <h3>Không có hóa đơn thanh toán</h3>
        <p>Vui lòng lựa chọn đồ uống trước khi tiến hành thanh toán.</p>
        <a href="<?= BASE_URL ?>index.php" class="btn-back">Khám phá thực đơn</a>
      </div>
    <?php else: ?>

    <div class="checkout-layout">

        <!-- CỘT TRÁI: Form giao hàng -->
        <div class="checkout-left">
            
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="section-title">
                        <h3>Thông tin người nhận</h3>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- Họ tên -->
                    <div class="form-group full">
                        <label for="name">Họ và tên</label>
                        <input type="text" id="name" class="input-field" placeholder="Ví dụ: Nguyễn Văn A" value="<?= $display_name ?>" required>
                    </div>

                    <!-- Số điện thoại -->
                    <div class="form-group full">
                        <label>Số điện thoại liên lạc</label>
                        <div class="field-dropdown-wrap" id="phone-wrap">
                            <div class="field-display" <?= count($user_phones) > 0 ? 'onclick="toggleDropdown(\'phone-wrap\')"' : '' ?>>
                                <input type="tel" id="phone" class="input-field" placeholder="Ví dụ: 0987123456"
                                       value="<?= $first_phone ?>" required <?= count($user_phones) > 0 ? 'readonly' : '' ?>>
                                <?php if (count($user_phones) > 0): ?>
                                    <span class="field-arrow">▾</span>
                                <?php endif; ?>
                            </div>

                            <?php if (count($user_phones) >= 1): ?>
                            <div class="field-dropdown" id="phone-dropdown">
                                <?php foreach ($user_phones as $ph): ?>
                                    <div class="dropdown-item" onclick="selectDropdown('phone-wrap', 'phone', '<?= $ph ?>', this)">
                                        <span class="dropdown-check">✓</span>
                                        <span><?= htmlspecialchars($ph) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="dropdown-item dropdown-custom-trigger" onclick="toggleCustomInput('phone-wrap', 'phone', this)">
                                    <span class="dropdown-plus">＋</span>
                                    <span>Sử dụng số khác</span>
                                </div>
                                <div class="dropdown-custom-input" id="phone-custom-wrap" style="display:none;">
                                    <input type="tel" id="phone-custom" placeholder="Nhập số điện thoại mới" oninput="applyCustomValue('phone', 'phone-custom')">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Địa chỉ -->
                    <div class="form-group full">
                        <label>Địa chỉ giao hàng</label>
                        <div class="field-dropdown-wrap" id="address-wrap">
                            <div class="field-display" <?= count($user_addresses) > 0 ? 'onclick="toggleDropdown(\'address-wrap\')"' : '' ?>>
                                <input type="text" id="address" class="input-field" placeholder="Nhập địa chỉ nhà, đường, phương..."
                                       value="<?= $first_address ?>" required <?= count($user_addresses) > 0 ? 'readonly' : '' ?>>
                                <?php if (count($user_addresses) > 0): ?>
                                    <span class="field-arrow">▾</span>
                                <?php endif; ?>
                            </div>

                            <?php if (count($user_addresses) >= 1): ?>
                            <div class="field-dropdown" id="address-dropdown">
                                <?php foreach ($user_addresses as $addr): ?>
                                    <div class="dropdown-item" onclick="selectDropdown('address-wrap', 'address', '<?= htmlspecialchars($addr, ENT_QUOTES) ?>', this)">
                                        <span class="dropdown-check">✓</span>
                                        <span><?= htmlspecialchars($addr) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="dropdown-item dropdown-custom-trigger" onclick="toggleCustomInput('address-wrap', 'address', this)">
                                    <span class="dropdown-plus">＋</span>
                                    <span>Giao đến địa chỉ mới</span>
                                </div>
                                <div class="dropdown-custom-input" id="address-custom-wrap" style="display:none;">
                                    <input type="text" id="address-custom" placeholder="Nhập địa chỉ mới" oninput="applyCustomValue('address', 'address-custom')">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <div class="form-group full">
                        <label for="note">Ghi chú vận chuyển (Tuỳ chọn)</label>
                        <textarea id="note" class="input-field" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến..."></textarea>
                    </div>

                    <!-- Phương thức thanh toán -->
                    <div class="form-group full" style="margin-top: -10px;">
                        <label for="payment_method">Hình thức thanh toán</label>
                        <div class="select-wrap">
                            <select id="payment_method">
                                <option value="cod">Thanh toán khi nhận hàng (COD)</option>
                                <option value="bank_transfer">Chuyển khoản / Quét mã QR</option>
                                <option value="fake_paypal">Cổng thanh toán điện tử (PayPal)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- CỘT PHẢI: Sticky Order Summary -->
        <div class="checkout-right">
            <div class="summary-card">
                <h3 class="summary-title">Tóm tắt Hóa đơn</h3>
                
                <div class="summary-item-list">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <div class="item-img-wrap">
                                <img src="<?= BASE_URL ?>images/<?= htmlspecialchars($item['image'] ?? '') ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='<?= BASE_URL ?>images/no-image.png'">
                                <span class="item-qty-badge"><?= $item['quantity'] ?></span>
                            </div>
                            <div class="item-meta">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?> (Size <?= htmlspecialchars($item['size']) ?>)</div>
                            </div>
                            <div class="item-subtotal">
                                <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-totals" id="checkout-summary">
                    <div class="summary-row">
                        <span>Giá trị đơn hàng</span>
                        <span class="value"><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                    </div>
                    <?php if ($session_discount > 0): ?>
                    <div class="summary-row discount">
                        <span>Ưu đãi áp dụng</span>
                        <span class="value">-<?= number_format($session_discount, 0, ',', '.') ?>đ</span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Phí vận chuyển</span>
                        <span class="value" style="color:var(--brand)">Miễn phí</span>
                    </div>
                </div>

                <div class="summary-final">
                    <span class="label">Tổng cộng</span>
                    <span class="value"><?= number_format($total, 0, ',', '.') ?>đ</span>
                </div>

                <button class="btn-order" onclick="placeOrder()">
                    Chốt đơn Giao hàng
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>

    </div>

    <?php endif; ?>
</div>

<script>
// Các biến từ PHP sang JS
const couponId = <?= json_encode($session_coupon_id) ?>;

function toggleDropdown(wrapId) {
  const wrap = document.getElementById(wrapId);
  const isOpen = wrap.classList.contains('open');
  document.querySelectorAll('.field-dropdown-wrap.open').forEach(w => w.classList.remove('open'));
  if (!isOpen) wrap.classList.add('open');
}

function selectDropdown(wrapId, inputId, value, el) {
  const input = document.getElementById(inputId);
  input.value = value;
  input.readOnly = true;
  const wrap = document.getElementById(wrapId);
  wrap.querySelectorAll('.dropdown-item').forEach(item => item.classList.remove('active'));
  el.classList.add('active');
  const customWrap = document.getElementById(inputId + '-custom-wrap');
  if (customWrap) customWrap.style.display = 'none';
  wrap.classList.remove('open');
}

function toggleCustomInput(wrapId, inputId, el) {
  const customWrap = document.getElementById(inputId + '-custom-wrap');
  const isHidden = customWrap.style.display === 'none';
  customWrap.style.display = isHidden ? 'block' : 'none';
  if (isHidden) {
    const customInput = document.getElementById(inputId + '-custom');
    customInput.value = '';
    customInput.focus();
    const wrap = document.getElementById(wrapId);
    wrap.querySelectorAll('.dropdown-item').forEach(item => item.classList.remove('active'));
    el.classList.add('active');
    document.getElementById(inputId).value = '';
    document.getElementById(inputId).readOnly = false;
  }
}

function applyCustomValue(inputId, customInputId) {
  const val = document.getElementById(customInputId).value;
  const mainInput = document.getElementById(inputId);
  mainInput.value = val;
  mainInput.readOnly = false;
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.field-dropdown-wrap')) {
    document.querySelectorAll('.field-dropdown-wrap.open').forEach(w => w.classList.remove('open'));
  }
});

function placeOrder() {
  const name = document.getElementById("name").value.trim();
  const phone = document.getElementById("phone").value.trim();
  const address = document.getElementById("address").value.trim();
  const note = document.getElementById("note").value.trim();
  const payment_method = document.getElementById("payment_method").value;

  if (!name || !phone || !address) {
    alert("Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ giao hàng!");
    return;
  }

  fetch("<?= BASE_URL ?>api/create_order.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ 
        name, phone, address, note, payment_method,
        coupon_id: couponId
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Đơn hàng đã được thiết lập thành công!");
      window.location.href = "<?= BASE_URL ?>pages/payment.php?ref=" + data.reference;
    } else {
      alert(data.message || "Không thể tạo đơn hàng lúc này.");
    }
  })
  .catch(() => alert("Đã xảy ra lỗi hệ thống, vui lòng thử lại sau."));
}
</script>

<?php include("../includes/footer.php"); ?>
