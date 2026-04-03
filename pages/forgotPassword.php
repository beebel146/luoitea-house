<?php
//------------------------------------
//PHP mailer 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
//----------------------------------
require_once(__DIR__ . "/../config/config.php");

$page_css = "auth.css";
$msg_type = "";
$msg_text = "";

if (isset($_POST['forgot'])) {
    $email = trim($_POST['email']);
    
    // kiểm tra email nếu bị rỗng
    if (empty($email)) {
        $msg_type = "error";
        $msg_text = "Vui lòng nhập lại email của bạn.";
    } else {
        //tìm người dùng
        $sql = "SELECT id, email, display_name FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user_result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        //nếu tồn tại thì tạo token
        if ($user_result) {
            $token = bin2hex(random_bytes(32));
            $expire = date("Y-m-d H:i:s", time() + 3600);
            
            $sql_update = "UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE email = ?";
            $stmt_upd = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_upd, "sss", $token, $expire, $email);
            mysqli_stmt_execute($stmt_upd);

            // tạo link
            $link = BASE_URL . "pages/reset_password.php?token=$token";
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;

                $mail->Username = 'nguyenhuynhkha0203@gmail.com'; // 👉 đổi thành gmail của bạn
                $mail->Password = 'oiak tedk rnry znyz'; // 👉 dán app password ở bước 1

                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                // người gửi
                $mail->setFrom('nguyenhuynhkha0203@gmail.com', 'LườiTea House');

                // người nhận
                $mail->addAddress($email);

                // nội dung
                $mail->isHTML(true);
                $mail->Subject = 'Khôi phục mật khẩu - LườiTea House';
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <h3 style='color: #3D5A45;'>Yêu cầu đặt lại mật khẩu!</h3>
                    <p>Xin chào, chúng tôi nhận được yêu cầu khôi phục quyền truy cập vào tài khoản của bạn tại LườiTea House.</p>
                    <p>Vui lòng Click vào liên kết bên dưới để tạo mật khẩu mới:</p>
                    <a href='$link' style='display:inline-block; padding:12px 25px; background-color:#3D5A45; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold;'>Khôi Phục Mật Khẩu Ngay</a>
                    <p style='margin-top:20px; font-size:13px; color:#888;'>Liên kết này sẽ tự động hết hạn sau 1 giờ.</p>
                </div>
                ";

                $mail->send();
                $msg_type = "success";
                $msg_text = "Đã gửi Email! Vui lòng kiểm tra Hộp thư của bạn.";
            } catch (Exception $e) {
                $msg_type = "error";
                $msg_text = "Không thể thiết lập mail: " . $mail->ErrorInfo;
            }
        } else {
            // Im lặng
            $msg_type = "success";
            $msg_text = "Đã gửi yêu cầu. Nếu Email này tồn tại, bạn sẽ nhận được liên kết bảo mật.";
        }
    }
}

include("../includes/header.php");
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Quên mật khẩu</h2>
        <p class="auth-subtitle">Nhập địa chỉ email của bạn để nhận liên kết khôi phục mật khẩu.</p>

        <?php if (!empty($msg_text)): ?>
            <?php if ($msg_type === 'error'): ?>
                <div class="auth-error"><?= htmlspecialchars($msg_text) ?></div>
            <?php else: ?>
                <div class="auth-error" style="background: rgba(34, 197, 94, 0.1); color: #15803d; border: 1px solid rgba(34, 197, 94, 0.2);">
                    <?= htmlspecialchars($msg_text) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Nhập địa chỉ email của bạn" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <button type="submit" name="forgot" class="btn-auth">Gửi yêu cầu khôi phục</button>
        </form>

        <div class="auth-footer">
            Bạn đã nhớ ra mật khẩu? <a href="login.php">Đăng nhập ngay</a>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>