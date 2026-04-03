<?php
require_once("../../config/config.php");
require_once("../auth_admin.php");

$users = mysqli_query($conn,"SELECT * FROM users ORDER BY id DESC");
?>

<?php
$page_title = 'Người Dùng Tín Nhiệm';
$page_subtitle = 'Bảo mật thông tin khách hàng';
$active_page = 'users';
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<div class="box">
<div class="data-list">
    <div class="data-header">
        <div class="col-id">Mã Nhận Diện</div>
        <div class="col-info" style="flex:3;">Khách Hàng</div>
        <div class="col-date" style="flex:2;">Email Liên Hệ</div>
        <div class="col-action" style="flex:0.5;">Trục Xuất</div>
    </div>

    <?php while($u = mysqli_fetch_assoc($users)){ 
        $isAdmin = (isset($u['role']) && $u['role'] === 'admin');
    ?>
    <div class="data-item" <?= $isAdmin ? 'style="border: 2px solid #ff5a5f; background: #fffafb;"' : '' ?>>
        <div class="col-id">#<?= $u['id'] ?></div>
        
        <div class="col-info" style="flex:3;">
            <img src="https://api.dicebear.com/7.x/notionists/svg?seed=<?= urlencode($u['username']) ?>" class="data-avatar user-avatar" alt="Avatar" <?= $isAdmin ? 'style="border-color: #ff5a5f;"' : '' ?>>
            <div class="col-info-text">
                <strong style="color: <?= $isAdmin ? '#ff5a5f' : 'var(--accent)' ?>;"><?= htmlspecialchars($u['username']) ?> <?= $isAdmin ? '👑' : '' ?></strong>
                <span style="color: <?= $isAdmin ? '#ff5a5f' : 'var(--muted)' ?>; font-weight: <?= $isAdmin ? '800' : '600' ?>;">
                    <?= $isAdmin ? 'Tài Khoản Admin' : 'Khách hàng thành viên' ?>
                </span>
            </div>
        </div>
        
        <div class="col-date" style="flex:2; font-weight: <?= $isAdmin ? '700' : '500' ?>;">
            <?= htmlspecialchars($u['email']) ?>
        </div>
        
        <div class="col-action" style="flex:0.5;">
            <a href="../delete_user.php?id=<?= $u['id'] ?>" class="btn-delete-icon" onclick="return confirm('Bạn có thực sự muốn trục xuất người dùng này?')">❌</a>
        </div>
    </div>
    <?php } ?>
</div>
</div>
<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>