<?php
require_once(__DIR__ . "/../../config/config.php");
require_once(__DIR__ . "/../auth_admin.php");

// Lấy danh sách user để admin chọn (nếu muốn gửi riêng)
$sql_users = "SELECT id, username FROM users WHERE role != 'admin'";

$admins = mysqli_query($conn, "
    SELECT id FROM users WHERE role = 'admin'
");
$res_users = mysqli_query($conn, $sql_users);
?>
<?php
$page_title = 'Trung Tâm Thông Báo';
$page_subtitle = 'Phát sóng tin tức, chương trình khuyến mãi';
$active_page = 'notifications';
$extra_css = '
<style>
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 800; margin-bottom: 8px; color: var(--text); }
    .form-group input, .form-group select, .form-group textarea { 
        width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 12px; box-sizing: border-box; font-size: 14px; 
    }
    .btn-submit { 
        background: linear-gradient(90deg, var(--accent), var(--accent-2)); color: white; border: none; padding: 12px 25px; border-radius: 12px; 
        cursor: pointer; font-weight: 700; transition: transform 0.2s; 
    }
    .btn-submit:hover { transform: translateY(-2px); }
    
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 12px; font-weight: 600; }
    .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

    .btn-delete { color: var(--danger); text-decoration: none; font-weight: 700; font-size: 14px; }
    .btn-delete:hover { text-decoration: underline; }
    .badge-all { background: #fef9c3; color: #a16207; padding: 4px 10px; border-radius: 30px; font-size: 12px; font-weight: 800; border: 1px solid #fde047; }
</style>
';
require_once(__DIR__ . "/../includes/admin_header.php");
?>

<?php if (isset($_GET['status'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_GET['status']) ?>">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

<!-- FORM GỬI THÔNG BÁO -->
<div class="box">
            <h3>Gửi thông báo mới</h3>
            <form action="../services/notification_service.php" method="POST" style="margin-top: 15px;">
                <div class="form-group">
                    <label>Người nhận:</label>
                    <select name="user_id">
                        <option value="0">Tất cả người dùng (Public)</option>
                        <?php while($u = mysqli_fetch_assoc($res_users)): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (ID: <?= $u['id'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tiêu đề:</label>
                    <input type="text" name="title" placeholder="Ví dụ: Khuyến mãi cuối tuần cực sốc!" required>
                </div>

                <div class="form-group">
                    <label>Nội dung thông báo:</label>
                    <textarea name="message" rows="3" placeholder="Nhập nội dung chi tiết thông báo..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Đường dẫn khi nhấn vào (Link):</label>
                    <input type="text" name="link" placeholder="Ví dụ: pages/category.php?id=1">
                </div>

                <button type="submit" class="btn-submit">Gửi thông báo ngay</button>
            </form>
        <form id="bulkDeleteForm" action="../services/bulk_delete_notifications.php" method="POST">
            <div class="bulk-action-bar">
                <label>
                    <input type="checkbox" id="checkAll" class="custom-checkbox">
                    <span style="font-size: 14px; text-transform: uppercase;">Chọn tất cả bản ghi</span>
                </label>
                <button type="submit" class="btn-danger" id="btnBulkDelete" onclick="return confirm('Bạn có chắc chắn muốn thiêu rụi tất cả các thông báo đã chọn?')">
                    🗑 Xóa Nhóm Đã Chọn
                </button>
            </div>
            
            <div class="data-list">
                <div class="data-header">
                    <div class="col-checkbox"></div>
                    <div class="col-info" style="flex:1;">Người nhận</div>
                    <div class="col-info" style="flex:3;">Nội dung phát sóng</div>
                    <div class="col-date">Ngày gửi</div>
                    <div class="col-action">Thao tác lẻ</div>
                </div>
                
                <?php
                $sql_list = "SELECT n.*, u.username FROM notifications n 
                             LEFT JOIN users u ON n.user_id = u.id 
                             ORDER BY n.created_at DESC";
                $res_list = mysqli_query($conn, $sql_list);
                if ($res_list && mysqli_num_rows($res_list) > 0):
                    while ($row = mysqli_fetch_assoc($res_list)):
                ?>
                <div class="data-item">
                    <div class="col-checkbox">
                        <input type="checkbox" name="notif_ids[]" value="<?= $row['id'] ?>" class="custom-checkbox notif-check">
                    </div>
                    
                    <div class="col-info" style="flex:1;">
                        <?= $row['user_id'] == null ? '<span class="badge-all">TẤT CẢ KHÁCH</span>' : '<span style="font-weight:800; color:var(--accent);">👤 ' . htmlspecialchars($row['username']).'</span>' ?>
                    </div>
                    
                    <div class="col-info" style="flex:3;">
                        <div class="col-info-text">
                            <strong style="font-family: var(--font-heading); color: var(--sidebar);"><?= htmlspecialchars($row['title']) ?></strong>
                            <span style="font-size: 14px; line-height: 1.5; color: var(--muted);"><?= htmlspecialchars($row['message']) ?></span>
                        </div>
                    </div>
                    
                    <div class="col-date" style="font-weight: 700;">
                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                    </div>
                    
                    <div class="col-action">
                        <a href="../services/delete_notification.php?id=<?= $row['id'] ?>" class="btn-delete-icon" onclick="return confirm('Xóa cá thể này?')" title="Xóa thông báo">🗑</a>
                    </div>
                </div>
                <?php 
                    endwhile; 
                else:
                ?>
                    <div class="data-item" style="justify-content: center; padding: 40px; color: var(--muted); font-weight: 700;">
                        Chưa có thông báo nào được phát sóng ra cộng đồng.
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <script>
    document.getElementById('checkAll').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('.notif-check');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
    </script>
<?php require_once(__DIR__ . "/../includes/admin_footer.php"); ?>