<?php
require_once(__DIR__ . "/../config/config.php");

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];
$msg = "";
$msg_type = "";

// Lấy dữ liệu user hiện tại
$stmt_user = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $uid);
mysqli_stmt_execute($stmt_user);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Cập nhật Avatar (Độc lập qua JS form submit)
    if (isset($_POST['update_avatar'])) {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']; // ADDED GIF
            $file = $_FILES['avatar'];
            
            // MAX SIZE: 5MB (5 * 1024 * 1024)
            if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newname = 'u' . $uid . '_avatar_' . time() . '.' . $ext;
                $target_path = __DIR__ . '/../uploads/' . $newname;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Xóa ảnh cũ
                    if (!empty($user['avatar']) && file_exists(__DIR__ . '/../uploads/' . $user['avatar'])) {
                        unlink(__DIR__ . '/../uploads/' . $user['avatar']);
                    }
                    $sql = "UPDATE users SET avatar = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $newname, $uid);
                    mysqli_stmt_execute($stmt);
                    
                    $msg = "Cập nhật ảnh đại diện thành công!";
                    $msg_type = "success";
                    
                    // Nạp lại thông tin mới
                    mysqli_stmt_execute($stmt_user);
                    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
                }
            } else {
                $msg = "Ảnh không hợp lệ hoặc dung lượng quá lớn (Tối đa 5MB, hỗ trợ thay đổi ảnh tĩnh/GIF)!";
                $msg_type = "error";
            }
        }
    }

    // 2. Cập nhật Thông tin hồ sơ
    if (isset($_POST['update_profile'])) {
        $display_name = mysqli_real_escape_string($conn, $_POST['display_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $phones = isset($_POST['phones']) ? array_filter($_POST['phones']) : [];
        $addresses = isset($_POST['addresses']) ? array_filter($_POST['addresses']) : [];
        
        $phone_json = json_encode($phones, JSON_UNESCAPED_UNICODE);
        $address_json = json_encode($addresses, JSON_UNESCAPED_UNICODE);

        if ($msg_type !== "error") {
            $sql = "UPDATE users SET display_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssi", $display_name, $email, $phone_json, $address_json, $uid);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = "Cập nhật thông tin thành công!";
                    $msg_type = "success";
                    mysqli_stmt_execute($stmt_user);
                    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
                } else {
                    $msg = "Lỗi thực thi: " . mysqli_stmt_error($stmt);
                    $msg_type = "error";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    // 3. Đổi mật khẩu
    if (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (!password_verify($old_pass, $user['password'])) {
            $msg = "Mật khẩu hiện tại không chính xác!";
            $msg_type = "error";
        } elseif ($new_pass !== $confirm_pass) {
            $msg = "Mật khẩu xác nhận không khớp!";
            $msg_type = "error";
        } elseif (strlen($new_pass) < 6) {
            $msg = "Mật khẩu mới phải từ 6 ký tự!";
            $msg_type = "error";
        } else {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt_up = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_up, "si", $hashed_pass, $uid);
            mysqli_stmt_execute($stmt_up);
            $msg = "Đổi mật khẩu thành công!";
            $msg_type = "success";
        }
    }
}

$page_css = "profile.css";
include(__DIR__ . "/../includes/header.php");

// Giải mã JSON
$user_phones = json_decode($user['phone'] ?? '[]', true);
if (!is_array($user_phones)) $user_phones = $user['phone'] ? [$user['phone']] : [];

$user_addresses = json_decode($user['address'] ?? '[]', true);
if (!is_array($user_addresses)) $user_addresses = $user['address'] ? [$user['address']] : [];

// Lịch sử ĐH
$sql_orders = "SELECT o.id, o.total, o.status, o.created_at, 
               GROUP_CONCAT(p.name SEPARATOR ', ') as items
               FROM orders o
               JOIN order_items oi ON o.id = oi.order_id
               JOIN products p ON oi.product_id = p.id
               WHERE o.user_id = ?
               GROUP BY o.id
               ORDER BY o.created_at DESC";
$stmt_orders = mysqli_prepare($conn, $sql_orders);
mysqli_stmt_bind_param($stmt_orders, "i", $uid);
mysqli_stmt_execute($stmt_orders);
$orders_res = mysqli_stmt_get_result($stmt_orders);
?>

<div class="profile-page">
    <div class="dashboard-layout">
        
        <!-- SIDEBAR -->
        <aside class="dashboard-sidebar">
            <div class="avatar-wrapper">
                <img src="<?= ($user['avatar'] ?? '') ? BASE_URL.'uploads/'.$user['avatar'] : BASE_URL.'images/user.jpg' ?>" alt="Avatar" class="avatar-image">
                <form id="avatarForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_avatar" value="1">
                    <label for="avatarFile" class="avatar-overlay">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        <span>Đổi ảnh</span>
                    </label>
                    <input type="file" name="avatar" id="avatarFile" accept="image/jpeg, image/png, image/webp, image/gif" hidden onchange="this.form.submit()">
                </form>
            </div>
            
            <div class="user-info">
                <h2 class="user-display-name"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></h2>
                <p class="user-username">@<?= htmlspecialchars($user['username']) ?></p>
            </div>

            <nav class="dashboard-nav">
                <button class="nav-item active" onclick="openSection(event, 'infoSection')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Thông tin cá nhân
                </button>
                <button class="nav-item" onclick="openSection(event, 'historySection')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Lịch sử đơn hàng
                </button>
                <button class="nav-item" onclick="openSection(event, 'securitySection')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Bảo mật
                </button>
                <a href="<?= BASE_URL ?>pages/logout.php" class="nav-item logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Đăng xuất
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="dashboard-content">
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>">
                    <?php if($msg_type === 'success') echo '✅'; else echo '⚠️'; ?>
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <!-- Thu gọn thiết kế lại cho Tab 1: Info -->
            <section id="infoSection" class="content-section active">
                <h1 class="section-title">Hồ sơ cá nhân</h1>
                <p class="section-subtitle">Cập nhật và quản lý các thông tin cá nhân của bạn.</p>
                
                <form action="" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tên đăng nhập</label>
                            <input type="text" class="input-field input-readonly" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Tên hiển thị</label>
                            <input type="text" name="display_name" class="input-field" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" placeholder="Tên hiển thị">
                        </div>
                        <div class="form-group full">
                            <label>Email liên hệ</label>
                            <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Nhập địa chỉ email của bạn">
                        </div>
                        
                        <!-- Block SĐT đa dạng -->
                        <div class="form-group full">
                            <label>Số điện thoại</label>
                            <div id="phone-container" class="dynamic-list">
                                <?php if(empty($user_phones)): ?>
                                    <div class="item-row">
                                        <input type="text" name="phones[]" class="input-field" placeholder="Ví dụ: 0987123456">
                                        <span class="btn-remove" onclick="removeItem(this)">×</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($user_phones as $phone): ?>
                                        <div class="item-row">
                                            <input type="text" name="phones[]" class="input-field" value="<?= htmlspecialchars($phone) ?>">
                                            <span class="btn-remove" onclick="removeItem(this)">×</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-outline-modern" style="margin-top: 15px;" onclick="addItem('phone-container', 'phones[]')">+ Thêm liên hệ</button>
                        </div>

                        <!-- Block Địa chỉ đa dạng -->
                        <div class="form-group full">
                            <label>Địa chỉ nhận hàng</label>
                            <div id="address-container" class="dynamic-list">
                                <?php if(empty($user_addresses)): ?>
                                    <div class="item-row">
                                        <textarea name="addresses[]" rows="2" class="input-field" placeholder="Nhập địa chỉ chi tiết (nhà/đường/quận)"></textarea>
                                        <span class="btn-remove" onclick="removeItem(this)">×</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($user_addresses as $address): ?>
                                        <div class="item-row">
                                            <textarea name="addresses[]" rows="2" class="input-field"><?= htmlspecialchars($address) ?></textarea>
                                            <span class="btn-remove" onclick="removeItem(this)">×</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-outline-modern" style="margin-top: 15px;" onclick="addItem('address-container', 'addresses[]', true)">+ Thêm địa chỉ mới</button>
                        </div>
                    </div>
                    
                    <div style="margin-top: 40px; text-align: right;">
                        <button type="submit" name="update_profile" class="btn-modern">
                            Lưu cấu hình
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Tab 2: Lịch sử Mua hàng dạng Cards -->
            <section id="historySection" class="content-section">
                <h1 class="section-title">Lịch sử giao dịch</h1>
                <p class="section-subtitle">Xem lại các đơn hàng bạn đã mua sắm tại LườiTea House.</p>
                
                <div class="order-list">
                    <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($orders_res)): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <div class="order-id">Đơn hàng #<?= $order['id'] ?></div>
                                        <div class="order-date">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                            <?= date('d/m/Y - H:i', strtotime($order['created_at'])) ?>
                                        </div>
                                    </div>
                                    <a href="<?= BASE_URL ?>pages/order_detail.php?id=<?= $order['id'] ?>" class="btn-outline-modern" style="padding: 8px 16px;">Chi tiết đơn</a>
                                </div>
                                <div class="order-body">
                                    <div class="order-items">
                                        <strong>Sản phẩm:</strong><br>
                                        <?= htmlspecialchars($order['items'] ?? '') ?>
                                    </div>
                                    <div class="order-total-status">
                                        <div class="order-total"><?= number_format($order['total'], 0, ',', '.') ?>đ</div>
                                        <span class="status-pill <?= $order['status'] ?>">
                                            <?php
                                            switch($order['status']) {
                                                case 'pending': echo 'Đợi Nhà Làm'; break;
                                                case 'pending_payment': echo 'Cho Thanh Toán'; break;
                                                case 'processing': echo 'Người Dưng Pha Chế'; break;
                                                case 'shipping': echo 'Tiến Tới Khách Hàng'; break;
                                                case 'shipped': echo 'Tiến Tới Khách Hàng'; break;
                                                case 'completed': echo 'Chuyến Hàng Êm Ấm'; break;
                                                case 'cancelled': echo 'Hủy Non...'; break;
                                                default: echo htmlspecialchars($order['status']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-data">
                            <div style="font-size: 50px; margin-bottom: 20px;">🛍️</div>
                            Bạn chưa có lịch sử mua hàng.<br>Hãy lướt qua menu thưởng thức trà nhé!
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Tab 3: Security -->
            <section id="securitySection" class="content-section">
                <h1 class="section-title">Bảo mật tài khoản</h1>
                <p class="section-subtitle">Đổi mật khẩu định kỳ giúp bảo vệ tài khoản tốt hơn.</p>

                <form action="" method="POST" style="max-width: 500px;">
                    <div class="form-group">
                        <label>Mật khẩu hiện tại</label>
                        <div class="password-wrapper">
                            <input type="password" name="old_password" id="old_password" class="input-field" required placeholder="Nhập để xác thực">
                            <span class="toggle-password" data-target="old_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" id="new_password" class="input-field" required placeholder="Tối thiểu 6 ký tự">
                            <span class="toggle-password" data-target="new_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Xác nhận lại mật khẩu mới</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" class="input-field" required placeholder="Nhập lại chính xác">
                            <span class="toggle-password" data-target="confirm_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-modern" style="margin-top: 15px; width: 100%; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Khóa mật khẩu mới
                    </button>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
// Logic Vanilla JS tabs
function openSection(evt, sectionId) {
    var sections = document.querySelectorAll(".content-section");
    sections.forEach(sec => sec.classList.remove("active"));
    
    var navs = document.querySelectorAll(".dashboard-nav .nav-item");
    navs.forEach(nav => {
        if (!nav.classList.contains("logout")) nav.classList.remove("active");
    });
    
    document.getElementById(sectionId).classList.add("active");
    evt.currentTarget.classList.add("active");
}

function addItem(containerId, name, isTextarea = false) {
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'item-row';
    
    const input = document.createElement(isTextarea ? 'textarea' : 'input');
    input.name = name;
    input.className = 'input-field';
    if (isTextarea) {
        input.rows = 2;
        input.placeholder = "Nhập địa chỉ chi tiết mới";
    } else {
        input.type = 'text';
        input.placeholder = "Nhập liên hệ điện thoại mới";
    }
    
    const removeBtn = document.createElement('span');
    removeBtn.className = 'btn-remove';
    removeBtn.innerHTML = '×';
    removeBtn.onclick = function() { removeItem(this); };
    
    div.appendChild(input);
    div.appendChild(removeBtn);
    container.appendChild(div);
}

function removeItem(btn) {
    const row = btn.parentElement;
    const container = row.parentElement;
    if (container.children.length > 1) {
        row.remove();
    } else {
        const input = row.querySelector('.input-field');
        input.value = '';
    }
}

// Logic cho nút Ẩn/Hiện Mật khẩu
document.querySelectorAll(".toggle-password").forEach(icon => {
    icon.addEventListener("click", function () {
        const targetId = this.getAttribute("data-target");
        const input = document.getElementById(targetId);
        const isPassword = input.type === "password";
        
        input.type = isPassword ? "text" : "password";
        
        if (isPassword) {
            this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        } else {
            this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        }
    });
});
</script>

<?php include(__DIR__ . "/../includes/footer.php"); ?>
