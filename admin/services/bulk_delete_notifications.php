<?php
require_once(__DIR__ . "/../../config/config.php");
require_once(__DIR__ . "/../auth_admin.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['notif_ids']) && is_array($_POST['notif_ids']) && count($_POST['notif_ids']) > 0) {
        $ids = array_map('intval', $_POST['notif_ids']);
        $ids_string = implode(',', $ids);
        
        $sql = "DELETE FROM notifications WHERE id IN ($ids_string)";
        if (mysqli_query($conn, $sql)) {
            header("Location: ../pages/notifications.php?status=success&msg=" . urlencode("Sắc lệnh thành công: Đã xóa " . count($ids) . " thông báo."));
            exit;
        } else {
            header("Location: ../pages/notifications.php?status=error&msg=" . urlencode("Lỗi hệ thống khi xóa thông báo hàng loạt."));
            exit;
        }
    } else {
        header("Location: ../pages/notifications.php?status=error&msg=" . urlencode("Thao tác thất bại: Bạn chưa chọn thông báo nào để xóa."));
        exit;
    }
} else {
    header("Location: ../pages/notifications.php");
    exit;
}
