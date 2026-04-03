<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base_admin_url = rtrim(BASE_URL, '/') . '/admin/';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'Admin Dashboard' ?> - LườiTea House</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Mulish:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_admin_url ?>css/admin.css">
    <link rel="stylesheet" href="<?= $base_admin_url ?>css/admin_cards.css">
    <link rel="stylesheet" href="<?= $base_admin_url ?>css/admin_dash.css">
    
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
    
    <?php if (isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
</head>
<body>
<div class="layout">
    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <h2>LườiTea Admin</h2>
        <a class="<?= ($active_page ?? '') == 'dashboard' ? 'active' : '' ?>" href="<?= $base_admin_url ?>dashboard.php">📊 Dashboard</a>
        <a class="<?= ($active_page ?? '') == 'products' ? 'active' : '' ?>" href="<?= $base_admin_url ?>pages/manage_products.php">📦 Sản phẩm</a>
        <a class="<?= ($active_page ?? '') == 'coupons' ? 'active' : '' ?>" href="<?= $base_admin_url ?>pages/manage_coupons.php">🎫 Mã giảm giá</a>
        <a class="<?= ($active_page ?? '') == 'orders' ? 'active' : '' ?>" href="<?= $base_admin_url ?>pages/orders.php">🧾 Đơn hàng</a>
        <a class="<?= ($active_page ?? '') == 'users' ? 'active' : '' ?>" href="<?= $base_admin_url ?>pages/users.php">👤 Người dùng</a>
        <a class="<?= ($active_page ?? '') == 'notifications' ? 'active' : '' ?>" href="<?= $base_admin_url ?>pages/notifications.php">🔔 Thông báo</a>
    </aside>

    <!-- ===== MAIN ===== -->
    <main class="main">
        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <h1><?= $page_title ?? 'Dashboard' ?></h1>
                <?php if (isset($page_subtitle)): ?>
                    <p class="subtext"><?= $page_subtitle ?></p>
                <?php endif; ?>
            </div>
            <a class="btn-primary" href="<?= BASE_URL ?>index.php" target="_blank">🏠 Thoát Admin</a>
        </div>
