<?php
require_once(__DIR__ . "/../config/config.php");

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];

    $sql = "SELECT id, name, image, description, stock, price_s, price_m, price_l FROM products WHERE id=$id";
    $result = mysqli_query($conn,$sql);
    $product = mysqli_fetch_assoc($result);

    if(!$product){
        echo "<script>alert('Sản phẩm không tồn tại'); window.location.href='../index.php';</script>";
        exit();
    }
}else{
    header("Location: ../index.php");
    exit();
}

$currentUser = null;
if(isset($_SESSION['user_id'])){
    $uid = (int)$_SESSION['user_id'];
    $res = mysqli_query($conn, "SELECT username FROM users WHERE id=$uid");
    $currentUser = mysqli_fetch_assoc($res);
}

if(isset($_POST['submit_review'])){
    $product_id = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    if($currentUser){
        $name = $currentUser['username'];
    } else {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
    }

    if($product_id && $name && $rating){
        $sql = "INSERT INTO reviews(product_id, name, rating, comment) 
                VALUES($product_id, '$name', $rating, '$comment')";
        mysqli_query($conn, $sql);

        header("Location: product_detail.php?id=".$product_id);
        exit();
    }
}

$page_css = "product-detail.css";
include(__DIR__ . "/../includes/header.php");
?>

<div class="product-detail-page">

    <!-- Hero Section -->
    <div class="product-hero">
        <div class="product-hero-image">
            <?php if (!empty($product['image'])): ?>
                <img src="<?= BASE_URL ?>images/<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     onerror="this.onerror=null; this.outerHTML='<div class=\'no-image-brand\'>LườiTea<br><span>House</span></div>';">
            <?php else: ?>
                <div class="no-image-brand">LườiTea<br><span>House</span></div>
            <?php endif; ?>
        </div>

        <div class="product-hero-info">
            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price">
                <span id="display-price-amount"><?= number_format($product['price_m'], 0, ",", ".") ?>đ</span>
            </div>

            <div class="product-status-wrap">
                <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                    <span class="status-badge status-in">✓ Còn hàng (<?= $product['stock'] ?>)</span>
                <?php else: ?>
                    <span class="status-badge status-out">✕ Hết hàng</span>
                <?php endif; ?>
            </div>

            <div class="product-description">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>

            <div class="size-selector">
                <style>
                    .size-selector { margin-bottom: 20px; }
                    .size-selector h4 { margin-bottom: 10px; font-weight: 600; color: #333; }
                    .size-opts { display: flex; gap: 10px; }
                    .size-opt { 
                        border: 1px solid #ccc; background: white; padding: 10px 20px; 
                        border-radius: 8px; cursor: pointer; transition: all 0.2s;
                        font-weight: 600;
                    }
                    .size-opt:hover { border-color: var(--brand); color: var(--brand); }
                    .size-opt.active { background: var(--brand); color: white; border-color: var(--brand); }
                </style>
                <h4>Chọn size</h4>
                <div class="size-opts">
                    <button class="size-opt" data-size="S" data-price="<?= $product['price_s'] ?>">Nhỏ (S)</button>
                    <button class="size-opt active" data-size="M" data-price="<?= $product['price_m'] ?>">Vừa (M)</button>
                    <button class="size-opt" data-size="L" data-price="<?= $product['price_l'] ?>">Lớn (L)</button>
                </div>
                <script>
                    let currentSelectedSize = 'M';
                    document.querySelectorAll('.size-opt').forEach(btn => {
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
                            this.classList.add('active');
                            currentSelectedSize = this.getAttribute('data-size');
                            let price = parseInt(this.getAttribute('data-price'));
                            document.getElementById('display-price-amount').textContent = parseInt(price).toLocaleString('vi-VN') + 'đ';
                        });
                    });
                </script>
            </div>

            <div class="product-actions">
                <button class="btn-large btn-add-cart" 
                        onclick="addCart(<?= $product['id'] ?>, currentSelectedSize)" 
                        <?= (isset($product['stock']) && $product['stock'] <= 0) ? 'disabled' : '' ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    Thêm vào giỏ hàng
                </button>
                <button class="btn-large btn-buy-now" 
                        onclick="addCart(<?= $product['id'] ?>, currentSelectedSize); setTimeout(()=>window.location.href='checkout.php', 300);" 
                        <?= (isset($product['stock']) && $product['stock'] <= 0) ? 'disabled' : '' ?>>
                    Mua ngay
                </button>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="product-reviews">
        <div class="reviews-header">
            <h2>Đánh giá từ khách hàng</h2>
            <p style="color: var(--muted);">Bạn nghĩ gì về sản phẩm này? Chia sẻ cùng LườiTea nhé!</p>
        </div>

        <div class="reviews-grid">
            <!-- Review Form -->
            <div class="review-form-wrap">
                <h3>Viết đánh giá</h3>
                <form method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <?php if($currentUser): ?>
                        <input type="text" class="input-field" value="<?= htmlspecialchars($currentUser['username']) ?>" disabled>
                    <?php else: ?>
                        <input type="text" name="name" class="input-field" placeholder="Tên của bạn *" required>
                    <?php endif; ?>

                    <select name="rating" class="input-field" required>
                        <option value="">Chọn số sao đánh giá *</option>
                        <option value="5">⭐⭐⭐⭐⭐ Tuyệt vời</option>
                        <option value="4">⭐⭐⭐⭐ Rất tốt</option>
                        <option value="3">⭐⭐⭐ Bình thường</option>
                        <option value="2">⭐⭐ Tệ</option>
                        <option value="1">⭐ Rất tệ</option>
                    </select>

                    <textarea name="comment" class="input-field" placeholder="Mô tả cảm nhận của bạn về sản phẩm... *" required></textarea>

                    <button type="submit" name="submit_review" class="btn-submit-review">Gửi chia sẻ</button>
                </form>
            </div>

            <!-- Review List -->
            <div class="reviews-list-wrap">
                <?php
                $pid = $product['id'];
                $reviews = mysqli_query($conn, "SELECT * FROM reviews WHERE product_id=$pid ORDER BY id DESC");
                
                if (mysqli_num_rows($reviews) > 0): 
                    while($r = mysqli_fetch_assoc($reviews)):
                        $first_char = mb_strtoupper(mb_substr($r['name'], 0, 1, 'UTF-8'));
                ?>
                    <div class="review-card">
                        <div class="review-author-row">
                            <div class="review-author">
                                <div class="author-avatar"><?= htmlspecialchars($first_char) ?></div>
                                <?= htmlspecialchars($r['name']) ?>
                            </div>
                            <div class="review-date">
                                <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                            </div>
                        </div>
                        <div class="review-stars">
                            <?php 
                            for($i=0; $i<5; $i++) {
                                if ($i < $r['rating']) echo "★";
                                else echo "☆";
                            }
                            ?>
                        </div>
                        <div class="review-text">
                            <?= nl2br(htmlspecialchars($r['comment'])) ?>
                        </div>
                    </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <div class="empty-reviews">
                        <div style="font-size: 40px; margin-bottom: 10px;">💬</div>
                        Chưa có đánh giá nào cho sản phẩm này.<br>Hãy là người đầu tiên trải nghiệm!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include(__DIR__ . "/../includes/footer.php"); ?>
