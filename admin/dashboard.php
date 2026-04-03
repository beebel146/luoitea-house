<?php
require_once("../config/config.php");
require_once("auth_admin.php");

function get_stat($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) return 0;
    $row = mysqli_fetch_row($result);
    return $row ? (float)$row[0] : 0;
}

$from    = $_GET['from_date'] ?? date("Y-m-01");
$to      = $_GET['to_date'] ?? date("Y-m-d");
$period  = $_GET['period'] ?? 'month';
$chartType = $_GET['chartType'] ?? 'bar';

$totalUsers    = get_stat($conn, "SELECT COUNT(*) FROM users");
$totalProducts = get_stat($conn, "SELECT COUNT(*) FROM products");
$totalOrders   = get_stat($conn, "SELECT COUNT(*) FROM orders");
$revenue       = get_stat($conn, "SELECT SUM(total) FROM orders WHERE status = 'completed' AND DATE(created_at) BETWEEN '$from' AND '$to'");
$revenue_display = number_format((float)$revenue, 0, ',', '.') . "đ";

$currentMonthStart = date("Y-m-01");
$nextMonthStart    = date("Y-m-01", strtotime("+1 month"));
$prevMonthStart    = date("Y-m-01", strtotime("-1 month"));

$currentMonthRevenue = get_stat($conn, "
    SELECT SUM(total) 
    FROM orders
    WHERE status = 'completed' AND DATE(created_at) >= '$currentMonthStart' 
      AND DATE(created_at) < '$nextMonthStart'
");

$lastMonthRevenue = get_stat($conn, "
    SELECT SUM(total) 
    FROM orders
    WHERE status = 'completed' AND DATE(created_at) >= '$prevMonthStart' 
      AND DATE(created_at) < '$currentMonthStart'
");

$growth = ($lastMonthRevenue > 0)
    ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
    : 0;

$topProducts = mysqli_query($conn, "
    SELECT p.name, COUNT(oi.product_id) as total
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    GROUP BY oi.product_id
    ORDER BY total DESC
    LIMIT 5
");

$newUsers = mysqli_query($conn, "
    SELECT username, email, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");

$recentOrders = mysqli_query($conn, "
    SELECT o.id, o.user_id, u.username, o.total, o.status, o.created_at
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$statusLabels = [
    'pending'    => 'pending',
    'processing' => 'processing',
    'shipping'   => 'shipping',
    'completed'  => 'completed',
    'cancelled'  => 'cancelled',
];
?>
<?php
$page_title = 'Trụ Sở Báo Cáo';
$page_subtitle = 'Tổng quan thông số cửa hàng';
$active_page = 'dashboard';
$extra_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="js/admin_chart.js" defer></script>';
require_once(__DIR__ . "/includes/admin_header.php");
?>

        <!-- STATS -->
        <section class="dash-stats">
            <div class="dash-card">
                <div class="dash-card-info">
                    <h3>Khách Hàng</h3>
                    <p><?= number_format($totalUsers) ?></p>
                </div>
                <div class="dash-card-icon icon-users">👥</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-info">
                    <h3>Sản Phẩm</h3>
                    <p><?= number_format($totalProducts) ?></p>
                </div>
                <div class="dash-card-icon icon-products">🧋</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-info">
                    <h3>Đơn Hàng</h3>
                    <p><?= number_format($totalOrders) ?></p>
                </div>
                <div class="dash-card-icon icon-orders">📦</div>
            </div>

            <div class="dash-card highlight">
                <div class="dash-card-info">
                    <h3>Doanh Thu Theo Kỳ</h3>
                    <p><?= $revenue_display ?></p>
                </div>
                <div class="dash-card-icon">💰</div>
            </div>
        </section>

        <!-- CHART -->
        <section class="chart-box" >
            <div class="box-head">
                <h2>📈 Doanh thu</h2>
                <p class="subtext">Lọc theo khoảng thời gian</p>
            </div>

            <form class="chart-toolbar-inline" id="filterForm">
                <div class="field">
                    <label>Từ ngày</label>
                    <input type="date" name="from_date" value="<?= htmlspecialchars($from) ?>">
                </div>

                <div class="field">
                    <label>Đến ngày</label>
                    <input type="date" name="to_date" value="<?= htmlspecialchars($to) ?>">
                </div>

                <div class="field">
                    <label>Nhóm theo</label>
                    <select name="period" id="periodSelect">
                        <option value="day" <?= $period == 'day' ? 'selected' : '' ?>>Theo ngày</option>
                        <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Theo tháng</option>
                    </select>
                </div>

                <div class="field">
                    <label>Kiểu chart</label>
                    <select name="chartType" id="chartType">
                        <option value="bar" <?= $chartType == 'bar' ? 'selected' : '' ?>>Biểu đồ cột (Bar)</option>
                        <option value="line" <?= $chartType == 'line' ? 'selected' : '' ?>>Biểu đồ đường (Line)</option>
                    </select>
                </div>

                <button class="btn-primary" type="submit">Xác nhận Lọc</button>
            </form>

            <div class="chart-wrap">
                <canvas id="revenueChart"></canvas>
            </div>
        </section>

        <!-- ACTION -->
        <section class="box action">
            <div>
                <h2>🔔 Gửi thông báo</h2>
                <p class="subtext">Gửi thông báo nhanh cho khách hàng</p>
            </div>
            <a class="btn-primary" href="pages/notifications.php" style="color: white;">Gửi ngay →</a>
        </section>

        <!-- GRID -->
        <section class="grid-2">

            <div class="box">
                <h2 style="font-family: var(--font-heading); margin-bottom: 5px;">🔥 Top Sản Phẩm Bán Chạy</h2>
                <div class="dash-list">
                    <?php while ($p = mysqli_fetch_assoc($topProducts)) { ?>
                        <div class="dash-list-item">
                            <div class="dash-list-item-left">
                                <div class="dash-item-icon">🍵</div>
                                <div class="dash-item-info">
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <span>Lượt Order</span>
                                </div>
                            </div>
                            <div class="dash-item-val"><?= $p['total'] ?> ly</div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="box">
                <h2 style="font-family: var(--font-heading); margin-bottom: 5px;">🌟 Người Dùng Mới Ghi Danh</h2>
                <div class="dash-list">
                    <?php while ($u = mysqli_fetch_assoc($newUsers)) { ?>
                        <div class="dash-list-item">
                            <div class="dash-list-item-left">
                                <img src="https://api.dicebear.com/7.x/notionists/svg?seed=<?= urlencode($u['username']) ?>" class="dash-item-avatar" alt="Avatar">
                                <div class="dash-item-info">
                                    <strong><?= htmlspecialchars($u['username']) ?></strong>
                                    <span><?= htmlspecialchars($u['email']) ?></span>
                                </div>
                            </div>
                            <div class="dash-item-val" style="color: var(--sidebar-2); font-size: 13px;">Member Mới</div>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </section>

        <!-- ORDERS -->
        <section class="box">
            <h2 style="font-family: var(--font-heading); margin-bottom: 5px;">🧾 Đơn Hàng Gần Đây Nhất</h2>
            
            <div class="data-list" style="margin-top: 20px;">
                <div class="data-header">
                    <div class="col-id">Mã Đơn</div>
                    <div class="col-info" style="flex:2.5;">Khách Hàng</div>
                    <div class="col-price" style="flex:1;">Tổng Tiền</div>
                    <div class="col-date" style="flex:1.5;">Ngày Tạo Đơn</div>
                    <div class="col-status" style="flex:1;">Trạng Thái</div>
                </div>

                <?php while ($o = mysqli_fetch_assoc($recentOrders)) {
                    $status = trim((string)($o['status'] ?? ''));
                    if ($status === '' || !isset($statusLabels[$status])) {
                        $status = 'pending';
                    }
                ?>
                <div class="data-item" style="padding: 12px 20px;">
                    <div class="col-id">#<?= (int)$o['id'] ?></div>
                    
                    <div class="col-info" style="flex:2.5;">
                        <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($o['username']) ?>" class="data-avatar user-avatar" style="width:40px;height:40px;" alt="Avatar">
                        <div class="col-info-text">
                            <strong style="color: var(--sidebar); font-size: 15px; margin-bottom:0;"><?= htmlspecialchars($o['username']) ?></strong>
                        </div>
                    </div>
                    
                    <div class="col-price" style="flex:1;"><?= number_format($o['total']) ?>đ</div>
                    
                    <div class="col-date" style="flex:1.5; font-size: 13px; font-weight: 700; color: var(--muted);"><?= htmlspecialchars($o['created_at']) ?></div>
                    
                    <div class="col-status" style="flex:1;">
                        <span class="badge <?= htmlspecialchars($status) ?>">
                            <?= htmlspecialchars($statusLabels[$status] ?? $status) ?>
                        </span>
                    </div>
                </div>
                <?php } ?>
            </div>
        </section>

<?php require_once(__DIR__ . "/includes/admin_footer.php"); ?>