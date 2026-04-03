<?php
require_once(__DIR__ . "/../config/config.php");

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_css = "auth.css";

// Login logic
global $conn;
$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: ../index.php");
        exit();
    } else {
        $error = "Email hoặc mật khẩu không chính xác.";
    }
}

include("../includes/header.php");
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Đăng nhập</h2>
        <p class="auth-subtitle">Chào mừng trở lại! Vui lòng đăng nhập vào tài khoản của bạn.</p>

        <?php if (!empty($error)): ?>
            <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Nhập địa chỉ email">
            </div>

            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
                    <span class="toggle-password" data-target="password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
            </div>

            <div class="auth-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    Ghi nhớ đăng nhập
                </label>
                <a href="forgotPassword.php">Quên mật khẩu?</a>
            </div>

            <button type="submit" name="login" class="btn-auth">Đăng nhập</button>
        </form>

        <div class="auth-footer">
            Bạn chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
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