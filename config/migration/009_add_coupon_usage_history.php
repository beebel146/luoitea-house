<?php
return function($conn) {

    // Bảng lưu tổng lượt sử dụng của 1 user cho 1 mã (có thể chỉnh sửa bởi Admin)
    $conn->query("
    CREATE TABLE IF NOT EXISTS coupon_user_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coupon_id INT NOT NULL,
        user_id INT NOT NULL,
        used_count INT DEFAULT 0,
        UNIQUE KEY unique_coupon_user (coupon_id, user_id),
        CONSTRAINT fk_cu_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
        CONSTRAINT fk_cu_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Bảng lưu lịch sử chi tiết mỗi lần sử dụng mã (chỉ thêm, không sửa)
    $conn->query("
    CREATE TABLE IF NOT EXISTS coupon_usage_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coupon_id INT NOT NULL,
        user_id INT NOT NULL,
        order_id INT NULL,
        used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_cuh_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
        CONSTRAINT fk_cuh_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cuh_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
    )");

};
