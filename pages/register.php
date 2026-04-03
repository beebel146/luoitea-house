<?php
require_once(__DIR__ . "/../config/config.php");

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_css = "auth.css";

global $conn;

$username = "";
$email = "";
$usernameError = "";
$emailError = "";
$passwordError = "";
$generalError = "";

if (isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Server-side validation
    if (empty($username) || empty($password) || empty($email) || empty($confirm_password)) {
        $generalError = "Vui lòng điền đủ thông tin.";
    } elseif (strlen($username) < 3) {
        $usernameError = "Tên người dùng phải ít nhất 3 ký tự.";
    } elseif ($password !== $confirm_password) {
        $passwordError = "Mật khẩu xác nhận không khớp.";
    } elseif (
        strlen($password) < 6 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W]/', $password)
    ) {
        $passwordError = "Mật khẩu phải chứa ít nhất 6 ký tự, gồm chữ hoa, chữ số và ký tự đặc biệt.";
    } else {
        // Check if username or email exists
        $stmt = mysqli_prepare($conn, "SELECT username, email FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['username'] === $username) {
                $usernameError = "Tên người dùng đã tồn tại.";
            }
            if ($row['email'] === $email) {
                $emailError = "Địa chỉ email đã được sử dụng.";
            }
        }
        
        if (empty($usernameError) && empty($emailError)) {
            $hash_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            mysqli_stmt_bind_param($insert_stmt, "sss", $username, $email, $hash_password);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $user_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                
                header("Location: ../index.php");
                exit();
            } else {
                $generalError = "Đã xảy ra lỗi hệ thống, vui lòng thử lại sau.";
            }
        }
    }
}

include("../includes/header.php");
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Tạo tài khoản</h2>
        <p class="auth-subtitle">Đăng ký để trải nghiệm dịch vụ tuyệt vời tại LườiTea House.</p>

        <?php if (!empty($generalError)): ?>
            <div class="auth-error"><?= htmlspecialchars($generalError) ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="input-group">
                <label for="username">Tên người dùng</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" required placeholder="Nhập tên người dùng">
                <?php if (!empty($usernameError)): ?>
                    <span class="auth-error" style="display:inline-block; padding: 4px 8px; margin-top:5px; background:none; color:#dc3545; font-size:13px; text-align:left; margin-bottom:0; width:100%;"><?= htmlspecialchars($usernameError) ?></span>
                <?php endif; ?>
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required placeholder="Nhập địa chỉ email">
                <?php if (!empty($emailError)): ?>
                    <span class="auth-error" style="display:inline-block; padding: 4px 8px; margin-top:5px; background:none; color:#dc3545; font-size:13px; text-align:left; margin-bottom:0; width:100%;"><?= htmlspecialchars($emailError) ?></span>
                <?php endif; ?>
            </div>

            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required placeholder="Ít nhất 6 ký tự, chữ hoa, số...">
                    <span class="toggle-password" data-target="password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
            </div>

            <div class="input-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Nhập lại mật khẩu">
                    <span class="toggle-password" data-target="confirm_password">
                         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                <?php if (!empty($passwordError)): ?>
                    <span class="auth-error" style="display:inline-block; padding: 4px 8px; margin-top:5px; background:none; color:#dc3545; font-size:13px; text-align:left; margin-bottom:0; width:100%;"><?= htmlspecialchars($passwordError) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" name="register" class="btn-auth">Đăng ký tài khoản</button>
        </form>

        <div class="auth-footer">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </div>
    </div>
</div>

<script>
document.querySelectorAll(".toggle-password").forEach(icon => {
    icon.addEventListener("click", function () {
        const targetId = this.getAttribute("data-target");
        const input = document.getElementById(targetId);
        const isPassword = input.type === "password";
        
        input.type = isPassword ? "text" : "password";
        
        // Update icon based on state
        if (isPassword) {
            this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        } else {
            this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        }
    });
});
</script>

<?php include("../includes/footer.php"); ?>