# Tài Liệu Chức Năng Và Logic Dự Án LườiTea House

Dự án **LườiTea House** là một hệ thống website thương mại điện tử chuyên cung cấp đồ uống (trà sữa). Website được xây dựng bằng **PHP thuần** kết hợp với **MySQL** làm cơ sở dữ liệu, có giao diện thiết kế hiện đại sử dụng **HTML, CSS và JavaScript**.

Dưới đây là tài liệu chi tiết về toàn bộ cấu trúc dự án, chức năng và logic hoạt động của các thành phần.

---

## 1. Cấu Trúc Thư Mục (Directory Structure)

- **`/` (Root):** Chứa các file cấu hình, file `index.php` (trang chủ) và file `README.md`.
- **`admin/`:** Khu vực dành riêng cho Quản trị viên (Admin Dashboard). Chứa các giao diện và logic quản lý (Sản phẩm, Người dùng, Đơn hàng, Mã giảm giá, Thống kê).
- **`ajax/`:** Chứa các file PHP xử lý gửi/nhận dữ liệu bất đồng bộ (AJAX) từ phía client, chủ yếu dành cho thao tác giỏ hàng (thêm, sửa, xóa).
- **`api/`:** Xử lý các logic nghiệp vụ quan trọng không có giao diện như thanh toán, tạo đơn hàng, xác nhận mã giảm giá và kiểm tra trạng thái đơn.
- **`config/`:** Chứa cấu hình dự án (`config.php`), kết nối cơ sở dữ liệu (`databases.php`) và các file migration để tự động tạo/cập nhật bảng CSDL.
- **`cron/`:** Các tác vụ tự động chạy ngầm (Cron Jobs) như dọn dẹp các đơn hàng chờ thanh toán quá hạn.
- **`includes/`:** Chứa các phần dùng chung của giao diện (Header, Footer, Menu).
- **`pages/`:** Chứa các trang hiển thị cho người dùng phía Frontend (Đăng nhập, Đăng ký, Giỏ hàng, Chi tiết sản phẩm, v.v.).
- **`images/`, `css/`, `js/`, `uploads/`:** Thư mục lưu trữ assets tĩnh (hình ảnh, stylesheet, script).

---

## 2. Cấu Hình và Cơ Sở Dữ Liệu (Config & Database)

*   **`config/config.php`:** Chứa các cấu hình toàn cục (Global configuration) như `BASE_URL`, các hằng số chung cho toàn hệ thống.
*   **`config/databases.php`:** Chứa Class `Database` dùng thư viện `mysqli`. Khi khởi tạo, nó sẽ kiểm tra và thực hiện **tự động tạo Database** (CREATE DATABASE IF NOT EXISTS) nếu CSDL chưa tồn tại, đảm bảo source code dễ dàng cài đặt trên máy chủ mới.
*   **`migration/`:** Hệ thống tự tạo các bảng (tables) cần thiết cho vận hành tự động thông qua các file migrate (ví dụ: `migrate_order_items.php`).

---

## 3. Hệ Thống Frontend dành cho Người dùng (Frontend & Pages)

Các file này hiển thị giao diện và nhận tương tác từ khách hàng:

### 3.1. Trang Chủ & Sản Phẩm
*   **`index.php`:**
    *   **Logic:** Đọc danh sách sản phẩm từ DB hiển thị dưới dạng lưới (grid). Hỗ trợ logic lọc (Filter) theo Categories (Danh mục), Tìm kiếm bằng từ khóa (Search), Khoảng giá (Min/Max), và Sắp xếp (Giá tăng/giảm, Mới nhất).
    *   **Hiển thị:** Hiển thị ảnh, giá, trạng thái (Còn hàng/Hết hàng) và cho phép bấm thêm vào giỏ hàng nhanh (AJAX).
*   **`pages/product_detail.php`:**
    *   **Logic:** Nhận ID sản phẩm từ URL, truy vấn chi tiết. Cho phép người dùng chọn **Size (S, M, L)** để hiển thị giá tương ứng, sau đó thêm vào đồ uống vào giỏ.

### 3.2. Giỏ Hàng & Thanh Toán
*   **`pages/cart.php`:** 
    *   **Logic:** Đọc giỏ hàng từ Session hoặc CSDL, hiển thị danh sách sản phẩm đã chọn, số lượng, size và tính tổng tiền.
*   **`pages/checkout.php`:** 
    *   **Logic:** Lấy thông tin người dùng đang đăng nhập (địa chỉ, số điện thoại mặc định). Cho phép đổi thông tin. Cho phép nhập mã giảm giá (Coupon). Người dùng chọn hình thức thanh toán (COD hoặc Online).

### 3.3. Tài Khoản & Đơn Hàng
*   **`pages/login.php` & `pages/register.php`:** Đăng ký, đăng nhập với mã hóa mật khẩu bảo mật, lưu trạng thái đăng nhập vào `$_SESSION`.
*   **`pages/profile.php`:** Quản lý thông tin tài khoản (Đổi tên, mật khẩu,...).
*   **`pages/orders.php` & `pages/order_detail.php`:** Lưu trữ lịch sử đặt hàng, trạng thái của từng đơn hàng.

---

## 4. Xử Lý API và AJAX (Backend Logic)

### 4.1. `ajax/` - Quản lý Giỏ Hàng
Hoạt động thông qua việc tiếp nhận request POST từ JavaScript, trả về chuỗi JSON để cập nhật giao diện mà không cần load lại trang.
*   `add_cart.php`: Thêm một object (sản phẩm, ID, size, số lượng, giá) vào Session giỏ hàng.
*   `update_cart.php`: Cập nhật số lượng của một mặt hàng.
*   `change_cart_size.php`: Thay đổi size và cập nhật lại mức giá của sản phẩm trong giỏ.
*   `remove_cart.php` / `remove_multiple_cart.php`: Xóa một hoặc nhiều sản phẩm khỏi giỏ.

### 4.2. `api/` - Thanh toán & Xử lý Nghiệp vụ
*   `check_coupon.php`: Xác thực Coupon. Kiểm tra thời hạn, số lượng còn lại, mức giảm và áp dụng vào tổng giỏ hàng.
*   `create_order.php`: Sau khi Checkout, API này nhận thông tin giỏ hàng và địa chỉ.
    *   **Logic:** Tạo record trong bảng `orders`, sau đó duyệt giỏ hàng để insert vào bảng `order_items`. Cuối cùng xóa giỏ hàng (clear session).
*   `comfirm_payment.php` / `confirm_cod_order.php`: Cập nhật trạng thái Payment status (ví dụ: chuyển từ Pending sang Success sau khi thanh toán thành công).

---

## 5. Hệ Thống Quản Trị (Admin Dashboard - `admin/`)

Hệ thống cung cấp trang quản trị toàn diện dành cho chủ cửa hàng tại đường dẫn `/admin`.
*   **`auth_admin.php`:** Middleware kiểm tra xem người dùng có quyền (Role = Admin) hay không. Nếu không sẽ chặn truy cập.
*   **`dashboard.php`:** Trang tổng quan, hiển thị các biểu đồ doanh thu, số lượng đơn hàng, người dùng mới. Tính toán tổng quan dựa trên database.
*   **`pages/manage_products.php`, `add_product.php`, `edit_product.php`:** (CRUD Sản Phẩm)
    *   Thêm/Sửa ảnh (Upload qua `uploads/`).
    *   Cấu hình giá theo size (S, M, L).
    *   Sửa trạng thái hết hàng/còn hàng (`edit_status_product_user.php`).
*   **`pages/manage_coupons.php`:** Quản lý mã giảm giá, đặt ra giới hạn sử dụng và loại giảm giá (Tỉ lệ phần trăm hoặc giảm thẳng theo số tiền).
*   **`pages/orders.php`:** Duyệt đơn hàng (Cập nhật từ trạng thái Chờ xử lý -> Đang giao -> Thành công hoặc Đã hủy).
*   **`pages/users.php`:** Quản trị danh sách thành viên.
*   **`pages/notifications.php`:** Gửi thông báo broadcast tới người dùng toàn hệ thống (hiện thông báo trên góc của User).

---

## 6. Logic Hoạt Động Cốt Lõi (Core Workflow)

1.  **Duyệt Web:** Khi vào `index.php`, hệ thống gọi Query MySQL để liệt kê sản phẩm dựa trên Category hoặc Search Keyword.
2.  **Đặt Hàng (Order Flow):**
    *   *Người dùng (User)* đăng nhập -> Gửi request AJAX `add_cart.php` -> PHP thêm mảng dữ liệu vào biến toàn cục `$_SESSION['cart']`.
    *   User đến `/pages/checkout.php` -> Nhập địa chỉ, nhập Coupon -> Bấm "Đặt hàng".
    *   Hệ thống gọi API `create_order.php`. Database ghi lại vào `orders` (tổng tiền thanh toán) và `order_items` (từng mặt hàng, size).
3.  **Xử Lý Đơn Hàng:**
    *   *Người quản trị (Admin)* truy cập `admin/pages/orders.php` -> Thay đổi trạng thái đơn.
    *   (Tính năng mở rộng tại `cron/cleanup_payment_waiting.php`): Tự động quét các đơn Online không thanh toán quá lâu để hủy.

---

## 7. Cơ Sở Dữ Liệu `luoitea_house_2` — Cấu Trúc Chi Tiết Các Bảng

> **Kết nối DB:** `http://localhost/phpmyadmin/index.php?route=/database/structure&db=luoitea_house_2`

Database bao gồm **16 bảng** với vai trò như sau:

### 7.1. Bảng `users` — Tài khoản người dùng

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `username` | varchar(50) | Tên đăng nhập (duy nhất) |
| `email` | varchar(120) | Địa chỉ email |
| `phone` | text | Số điện thoại (lưu dạng JSON array) |
| `address` | text | Địa chỉ giao hàng (lưu dạng JSON array) |
| `password` | varchar(255) | Mật khẩu đã mã hóa bcrypt |
| `display_name` | varchar(100) | Tên hiển thị |
| `avatar` | varchar(255) | Tên file ảnh đại diện (trong `/uploads/`) |
| `role` | enum('guest','user','admin') | Phân quyền |
| `created_at` | timestamp | Thời điểm tạo tài khoản |
| `reset_token` | varchar(255) | Token dùng để đặt lại mật khẩu |
| `reset_token_expire` | datetime | Thời hạn hiệu lực của token |

---

### 7.2. Bảng `categories` — Danh mục sản phẩm

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `name` | varchar(100) | Tên danh mục (Trà sữa, Trà trái cây...) |
| `created_at` | timestamp | Thời điểm tạo |

---

### 7.3. Bảng `products` — Sản phẩm / Đồ uống

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `name` | varchar(255) | Tên món nước |
| `price_s` | int(11) | Giá Size S |
| `price_m` | int(11) | Giá Size M |
| `price_l` | int(11) | Giá Size L |
| `image` | varchar(255) | Tên file ảnh chính (trong `/uploads/`) |
| `description` | text | Mô tả sản phẩm |
| `category_id` | int(11) FK | Liên kết bảng `categories` |
| `stock` | int(11) | Số lượng tồn kho thực tế |
| `reserved_stock` | int(11) | Số lượng đang được giữ chỗ (chưa thanh toán) |
| `created_at` | timestamp | Thời điểm thêm sản phẩm |

---

### 7.4. Bảng `product_images` — Ảnh phụ sản phẩm

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `product_id` | int(11) FK | Liên kết bảng `products` |
| `filename` | varchar(255) | Tên file ảnh phụ |
| `is_primary` | tinyint(4) | Đánh dấu ảnh chính (1 = chính) |

---

### 7.5. Bảng `coupons` — Mã giảm giá

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `code` | varchar(50) | Mã code (VD: SUMMER30) |
| `type` | enum('percentage','fixed') | Loại giảm: % hoặc tiền mặt |
| `value` | decimal(10,2) | Giá trị giảm |
| `min_order_value` | decimal(12,0) | Giá trị đơn tối thiểu để áp dụng |
| `usage_limit` | int(11) | Tổng số lượt sử dụng tối đa |
| `used_count` | int(11) | Số lượt đã dùng thực tế |
| `start_date` | datetime | Ngày bắt đầu hiệu lực |
| `end_date` | datetime | Ngày kết thúc hiệu lực |
| `status` | tinyint(1) | 1 = Đang kích hoạt, 0 = Đã khóa |
| `created_at` | timestamp | Thời điểm tạo |

---

### 7.6. Bảng `coupon_usage_history` — Lịch sử dùng coupon (chi tiết theo đơn)

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `coupon_id` | int(11) FK | Mã giảm giá đã dùng |
| `user_id` | int(11) FK | Người dùng |
| `order_id` | int(11) FK | Đơn hàng áp dụng |
| `used_at` | timestamp | Thời điểm sử dụng |

---

### 7.7. Bảng `coupon_user_usage` — Đếm số lượt dùng theo user

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `user_id` | int(11) FK | Người dùng |
| `coupon_id` | int(11) FK | Mã giảm giá |
| `usage_count` | int(11) | Số lần user này đã dùng mã này |
| `last_used_at` | timestamp | Lần cuối sử dụng |

---

### 7.8. Bảng `orders` — Đơn hàng

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID đơn hàng |
| `user_id` | int(11) FK | Người đặt hàng |
| `coupon_id` | int(11) FK | Mã giảm giá áp dụng (nếu có) |
| `total` | decimal(12,0) | Tổng tiền sau giảm giá (thực tế thanh toán) |
| `discount_amount` | decimal(12,0) | Số tiền đã được giảm |
| `status` | varchar(50) | Trạng thái đơn: `pending`, `processing`, `shipping`, `completed`, `cancelled` |
| `created_at` | timestamp | Thời gian đặt hàng |
| `name` | varchar(255) | Tên người nhận hàng |
| `phone` | varchar(20) | SĐT người nhận |
| `address` | text | Địa chỉ giao hàng |
| `note` | text | Ghi chú của khách |
| `payment_method` | varchar(50) | Phương thức: `cod` hoặc `bank_transfer` |
| `payment_status` | varchar(50) | Trạng thái TT: `unpaid`, `paid` |
| `payment_expires_at` | datetime | Thời hạn thanh toán online |
| `qr_content` | text | Nội dung QR / mã tham chiếu thanh toán |
| `paid_at` | datetime | Thời điểm thanh toán thành công |
| `expired_at` | datetime | Thời điểm đơn hàng hết hạn |

---

### 7.9. Bảng `order_items` — Chi tiết từng sản phẩm trong đơn

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `order_id` | int(11) FK | Đơn hàng chứa item này |
| `product_id` | int(11) FK | Sản phẩm được đặt |
| `qty` | int(11) | Số lượng |
| `price` | decimal(12,0) | Giá tại thời điểm đặt |
| `size` | varchar(5) | Size chọn: `S`, `M`, hoặc `L` |

---

### 7.10. Bảng `payments` — Giao dịch thanh toán

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `order_id` | int(11) FK | Đơn hàng liên quan |
| `method` | varchar(50) | Phương thức thanh toán |
| `amount` | decimal(12,0) | Số tiền giao dịch |
| `status` | enum('pending','paid','failed','expired') | Trạng thái giao dịch |
| `transaction_code` | varchar(100) | Mã giao dịch ngân hàng |
| `qr_content` | text | Nội dung QR code |
| `created_at` | timestamp | Thời điểm tạo giao dịch |
| `paid_at` | datetime | Thời điểm xác nhận thanh toán |

---

### 7.11. Bảng `payment_waiting` — Đơn hàng chờ thanh toán (bảng tạm)

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `reference` | varchar(100) | Mã tham chiếu duy nhất (REF + timestamp) |
| `order_data` | longtext | Toàn bộ dữ liệu đơn hàng (JSON) gồm: thông tin người dùng, sản phẩm, giá, coupon |
| `is_paid` | tinyint(1) | 0 = chưa thanh toán, 1 = đã thanh toán |
| `created_at` | timestamp | Thời điểm tạo |

---

### 7.12. Bảng `notifications` — Thông báo hệ thống

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `user_id` | int(11) FK | Người nhận (NULL = gửi tất cả) |
| `title` | varchar(255) | Tiêu đề thông báo |
| `content` | text | Nội dung chi tiết |
| `is_read` | tinyint(1) | 0 = chưa đọc, 1 = đã đọc |
| `created_at` | timestamp | Thời điểm gửi |

---

### 7.13. Bảng `inventory_logs` — Nhật ký tồn kho

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `product_id` | int(11) FK | Sản phẩm liên quan |
| `order_id` | int(11) FK | Đơn hàng (nếu có) |
| `change_qty` | int(11) | Số lượng thay đổi (âm = xuất kho) |
| `type` | enum('import','export','order_reserved','order_cancelled','order_completed','manual_adjustment') | Loại thay đổi |
| `note` | varchar(255) | Ghi chú |
| `created_at` | timestamp | Thời điểm ghi log |

---

### 7.14. Bảng `reviews` — Đánh giá sản phẩm

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `product_id` | int(11) FK | Sản phẩm được đánh giá |
| `user_id` | int(11) FK | Người đánh giá |
| `name` | varchar(100) | Tên hiển thị khi đánh giá |
| `rating` | int(11) | Điểm đánh giá (1–5) |
| `comment` | text | Nội dung bình luận |
| `created_at` | timestamp | Thời điểm gửi đánh giá |

---

### 7.15. Bảng `admin_logs` — Nhật ký hành động Admin

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `admin_id` | int(11) FK | Admin thực hiện hành động |
| `action` | varchar(255) | Mô tả hành động (VD: "Cập nhật trạng thái đơn #5") |
| `meta` | text | Dữ liệu mở rộng (JSON, trước/sau thay đổi,...) |
| `created_at` | timestamp | Thời điểm ghi log |

---

### 7.16. Bảng `migrations` — Theo dõi migration đã chạy

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) PK | ID tự tăng |
| `name` | varchar(255) | Tên file migration đã thực thi |

---

## 8. Bản Đồ Chức Năng → Dữ Liệu (Feature → Database Mapping)

Phần này mô tả **khi người dùng thực hiện một chức năng**, dữ liệu sẽ được ghi vào **bảng nào** trong database `luoitea_house_2`.

---

### 8.1. 🧑 Đăng Ký Tài Khoản (`pages/register.php`)

**Người dùng nhập:** Tên đăng nhập, Email, Mật khẩu

**Dữ liệu được INSERT vào:**

| Bảng | Các cột được ghi |
|------|-----------------|
| `users` | `username`, `email`, `password` (bcrypt hash), `role = 'user'`, `created_at` |

**Lưu ý thêm:** Session `$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['role']` được khởi tạo ngay sau khi đăng ký thành công.

---

### 8.2. 🔐 Đăng Nhập (`pages/login.php`)

**Người dùng nhập:** Tên đăng nhập / Email, Mật khẩu

**Không INSERT** vào database. Hệ thống chỉ **SELECT** từ bảng `users` để xác thực và tạo Session.

**Session được tạo:** `$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['role']`

---

### 8.3. 👤 Cập Nhật Hồ Sơ (`pages/profile.php`)

**Người dùng nhập:** Tên hiển thị, Email, Danh sách SĐT, Danh sách địa chỉ

**Dữ liệu được UPDATE trong:**

| Bảng | Các cột được cập nhật |
|------|-----------------------|
| `users` | `display_name`, `email`, `phone` (JSON array), `address` (JSON array) |

**Khi đổi ảnh đại diện:**

| Bảng | Cột |
|------|-----|
| `users` | `avatar` (tên file mới) |

**File ảnh vật lý:** Lưu vào thư mục `/uploads/` với tên dạng `u{id}_avatar_{timestamp}.{ext}`

---

### 8.4. 🔑 Đổi Mật Khẩu (`pages/profile.php` — tab Bảo Mật)

**Người dùng nhập:** Mật khẩu hiện tại, Mật khẩu mới

**Dữ liệu được UPDATE trong:**

| Bảng | Cột |
|------|-----|
| `users` | `password` (bcrypt hash mới) |

---

### 8.5. 🔑 Quên/Đặt Lại Mật Khẩu (`pages/forgotPassword.php`, `pages/reset_password.php`)

**Dữ liệu tạm thời được UPDATE trong:**

| Bảng | Các cột |
|------|---------|
| `users` | `reset_token`, `reset_token_expire` |

Sau khi người dùng đặt lại mật khẩu thành công, `reset_token` và `reset_token_expire` được xóa (set NULL), và `password` được cập nhật.

---

### 8.6. 🛒 Thêm Sản Phẩm Vào Giỏ (`ajax/add_cart.php`)

**Người dùng thao tác:** Chọn sản phẩm + size + số lượng, bấm "Thêm vào giỏ"

**Không lưu vào database.** Dữ liệu chỉ lưu trong **PHP Session**:

```
$_SESSION['cart']["{product_id}_{size}"] = số_lượng
```

Ví dụ: `$_SESSION['cart']['5_M'] = 2` (2 ly Size M của sản phẩm ID=5)

---

### 8.7. 🏷️ Kiểm Tra Mã Giảm Giá (`api/check_coupon.php`)

**Người dùng nhập:** Mã coupon tại trang Checkout

**Không INSERT/UPDATE** vào database ở bước này. Hệ thống chỉ **SELECT** từ bảng `coupons` và `orders` để xác thực.

**Session được ghi:** `$_SESSION['coupon_id']`, `$_SESSION['discount_amount']`

---

### 8.8. 📦 Tạo Đơn Hàng — Bước 1: Lưu Tạm (`api/create_order.php`)

**Người dùng thao tác:** Bấm "Đặt hàng" tại trang Checkout

**Dữ liệu được INSERT vào bảng tạm:**

| Bảng | Các cột được ghi |
|------|-----------------|
| `payment_waiting` | `reference` (REF + timestamp), `order_data` (JSON chứa toàn bộ thông tin đơn: user_id, coupon_id, total, discount_amount, name, phone, address, note, payment_method, danh sách items), `is_paid = 0`, `created_at` |

**Mục đích:** Lưu trữ tạm thời toàn bộ thông tin đơn hàng trong khi chờ người dùng xác nhận thanh toán (COD) hoặc hoàn tất chuyển khoản (Online).

---

### 8.9. ✅ Xác Nhận Đơn COD (`api/confirm_cod_order.php`)

**Người dùng thao tác:** Xác nhận đặt hàng COD (thanh toán khi nhận hàng)

**Dữ liệu được ghi vào các bảng:**

| Bảng | Thao tác | Các cột được ghi |
|------|---------|-----------------|
| `orders` | INSERT | `user_id`, `coupon_id`, `total`, `discount_amount`, `status = 'pending'`, `name`, `phone`, `address`, `note`, `payment_method = 'cod'`, `payment_status = 'unpaid'`, `qr_content` (mã reference) |
| `order_items` | INSERT (mỗi sản phẩm 1 dòng) | `order_id`, `product_id`, `size`, `qty`, `price` |
| `products` | UPDATE | `stock = stock - qty` (trừ kho cho mỗi sản phẩm) |
| `coupons` | UPDATE (nếu có dùng coupon) | `used_count = used_count + 1` |
| `coupon_user_usage` | INSERT hoặc UPDATE | `user_id`, `coupon_id`, `usage_count + 1`, `last_used_at` |
| `coupon_usage_history` | INSERT | `coupon_id`, `user_id`, `order_id`, `used_at` |
| `notifications` | INSERT | `user_id`, `title = "Đặt hàng thành công"`, `content/message`, `link` |
| `payment_waiting` | DELETE | Xóa bản ghi tạm theo `reference` |

**Session bị xóa:** `$_SESSION['cart']`, `$_SESSION['checkout_items']`, `$_SESSION['coupon_id']`, `$_SESSION['discount_amount']`

---

### 8.10. 💳 Xác Nhận Thanh Toán Online (`api/comfirm_payment.php`)

**Người dùng thao tác:** Chuyển khoản ngân hàng / quét QR thành công

**Dữ liệu được ghi vào các bảng:**

| Bảng | Thao tác | Các cột được ghi |
|------|---------|-----------------|
| `orders` | INSERT | `user_id`, `coupon_id`, `total`, `discount_amount`, `status = 'pending'`, `name`, `phone`, `address`, `note`, `payment_method`, `payment_status = 'paid'`, `paid_at = NOW()`, `qr_content` |
| `order_items` | INSERT (mỗi sản phẩm) | `order_id`, `product_id`, `size`, `qty`, `price` |
| `products` | UPDATE | `stock = stock - qty` (trừ kho) |
| `coupons` | UPDATE (nếu có) | `used_count = used_count + 1` |
| `coupon_user_usage` | INSERT hoặc UPDATE | `user_id`, `coupon_id`, `usage_count`, `last_used_at` |
| `coupon_usage_history` | INSERT | `coupon_id`, `user_id`, `order_id`, `used_at` |
| `notifications` | INSERT | `user_id`, `title = "Thanh toán thành công"`, `message`, `link` |
| `payment_waiting` | DELETE | Xóa bản ghi tạm theo `reference` |

---

### 8.11. ❌ Hủy Đơn Hàng (`api/cancel_order.php`)

**Người dùng thao tác:** Bấm "Hủy đơn" tại trang lịch sử đơn hàng

**Dữ liệu được UPDATE trong:**

| Bảng | Cột |
|------|-----|
| `orders` | `status = 'cancelled'` |

---

### 8.12. 🏪 Admin Cập Nhật Trạng Thái Đơn (`admin/pages/edit_status_product_user.php`)

**Admin thao tác:** Chọn trạng thái mới và bấm "Cập nhật"

**Dữ liệu được UPDATE trong:**

| Bảng | Cột |
|------|-----|
| `orders` | `status` (pending/processing/shipping/completed/cancelled) |

**Logic đặc biệt cho đơn COD:**
- Khi chuyển sang `completed`: tự động UPDATE thêm `payment_status = 'paid'`, `paid_at = NOW()`
- Khi chuyển về trạng thái khác từ `completed`: tự động UPDATE lại `payment_status = 'unpaid'`, `paid_at = NULL`

---

### 8.13. 📦 Admin Thêm Sản Phẩm (`admin/pages/add_product.php`)

**Admin nhập:** Tên, Giá S/M/L, Số lượng kho, Danh mục

**Dữ liệu được INSERT vào:**

| Bảng | Các cột được ghi |
|------|-----------------|
| `products` | `name`, `price_s`, `price_m`, `price_l`, `stock`, `category_id`, `image = 'default.jpg'`, `created_at` |

---

### 8.14. ✏️ Admin Sửa Sản Phẩm (`admin/pages/edit_product.php`)

**Admin nhập:** Thông tin mới, ảnh mới

**Dữ liệu được UPDATE trong:**

| Bảng | Các cột |
|------|---------|
| `products` | `name`, `price_s`, `price_m`, `price_l`, `stock`, `category_id`, `image`, `description` |

**File ảnh** mới (nếu upload): Lưu vào `/uploads/`

---

### 8.15. 🎟️ Admin Thêm Mã Giảm Giá (`admin/pages/add_coupon.php`)

**Admin nhập:** Code, Loại, Mức giảm, Đơn tối thiểu, Giới hạn lượt

**Dữ liệu được INSERT vào:**

| Bảng | Các cột được ghi |
|------|-----------------|
| `coupons` | `code`, `type`, `value`, `min_order_value`, `usage_limit`, `status` (is_active), `created_at` |

---

### 8.16. 📣 Admin Gửi Thông Báo (`admin/pages/notifications.php` → `admin/services/notification_service.php`)

**Admin nhập:** Người nhận (hoặc tất cả), Tiêu đề, Nội dung, Link

**Dữ liệu được INSERT vào:**

| Bảng | Các cột được ghi |
|------|-----------------|
| `notifications` | `user_id` (NULL nếu gửi tất cả), `title`, `content` (message), `is_read = 0`, `created_at` |

**Nếu gửi tất cả:** Hệ thống SELECT toàn bộ `user_id` từ bảng `users` (role != 'admin') rồi INSERT nhiều dòng vào `notifications`.

---

### 8.17. ⏱️ Cron Job Tự Động Hủy Đơn Chờ (`cron/cleanup_payment_waiting.php`)

**Kích hoạt:** Chạy tự động theo lịch (hoặc thủ công)

**Dữ liệu bị xóa/cập nhật:**

| Bảng | Thao tác | Điều kiện |
|------|---------|-----------|
| `payment_waiting` | DELETE | Các bản ghi tạo quá thời gian cho phép và `is_paid = 0` |

---

## 9. Sơ Đồ Luồng Dữ Liệu (Data Flow Summary)

```
[Người dùng đăng ký]
        ↓ INSERT
    users (username, email, password, role)

[Thêm vào giỏ]
        ↓ Lưu Session
    $_SESSION['cart']['product_id_size'] = qty

[Đặt hàng bấm "Thanh toán"]
        ↓ INSERT
    payment_waiting (reference, order_data JSON)

[Xác nhận COD]               [Thanh toán Online thành công]
        ↓ INSERT                         ↓ INSERT
    orders (status=pending,           orders (status=pending,
            payment_status=unpaid)            payment_status=paid)
        ↓ INSERT (loop)                  ↓ INSERT (loop)
    order_items                        order_items
        ↓ UPDATE                         ↓ UPDATE
    products.stock - qty               products.stock - qty
        ↓ UPDATE/INSERT (nếu coupon)     ↓ UPDATE/INSERT (nếu coupon)
    coupons.used_count++              coupons.used_count++
    coupon_user_usage                 coupon_user_usage
    coupon_usage_history              coupon_usage_history
        ↓ INSERT                         ↓ INSERT
    notifications                      notifications
        ↓ DELETE                         ↓ DELETE
    payment_waiting                    payment_waiting

[Admin cập nhật trạng thái đơn]
        ↓ UPDATE
    orders (status, payment_status nếu COD)

[Admin gửi thông báo]
        ↓ INSERT (bulk)
    notifications (user_id hoặc NULL cho broadcast)
```

---

## 10. Phân Tích Chức Năng Của Từng Tệp (File-By-File Breakdown)

Dưới đây là danh sách chi tiết các công việc và nhiệm vụ của từng file PHP trong hệ thống:

### Thư mục Gốc (Root)
- **`index.php`**: Trang chủ của website, hiển thị danh sách sản phẩm nổi bật/tất cả, xử lý các bộ lọc tìm kiếm sản phẩm.
- **`README.md`**: Tệp văn bản Markdown chứa thông tin giới thiệu và tóm tắt cài đặt dự án.

### Thư mục `config/` (Cấu hình hệ thống)
- **`config.php`**: Khai báo các hằng số, biến môi trường dùng chung (`BASE_URL`, v.v.).
- **`databases.php`**: Class kết nối đến MySQL. Tự động kiểm tra và tạo CSDL `luoitea_house_2` nếu chưa tồn tại.
- **`migrate.php`**: Tệp chạy chung để khởi chạy tự động các file tạo bảng trong thư mục `migration/`.
- **`migrate_notifications.php`**, **`migrate_order_items.php`**, **`migrate_sizes.php`**: Các file script chạy lệnh thay đổi / thêm cột CSDL thủ công.

### Thư mục `pages/` (Giao diện Frontend Người dùng)
- **`cart.php`**: Trang hiển thị giỏ hàng hiện tại, các sản phẩm đã chọn, và tổng tiền dự kiến.
- **`category.php`**: Trang danh sách sản phẩm hiển thị riêng theo từng danh mục cụ thể.
- **`checkout.php`**: Form trang thanh toán, cho phép người dùng điền thông tin, nhập mã giảm giá và chọn phương thức thanh toán.
- **`contact.php`**: Trang thông tin liên hệ tĩnh của cửa hàng.
- **`forgotPassword.php`**: Trang yêu cầu cấp lại mật khẩu.
- **`login.php`**: Trang đăng nhập vào hệ thống dành cho khách hàng.
- **`logout.php`**: File xử lý logic hủy phiên đăng nhập (`session_destroy()`).
- **`notifications.php`**: Hộp thư đọc thông báo hệ thống được gửi từ Admin.
- **`order_detail.php`**: Trang xem chi tiết một đơn đặt hàng.
- **`orders.php`**: Trang liệt kê toàn bộ lịch sử các đơn hàng đã đặt của khách hàng.
- **`payment.php`**: Trang hiển thị trạng thái trung gian hoặc thông tin xử lý thanh toán.
- **`product_detail.php`**: Trang hiển thị chi tiết một loại đồ uống, nơi người dùng chọn thuộc tính (Size, Số lượng) để thêm vào giỏ.
- **`profile.php`**: Trang hồ sơ cá nhân để người dùng thay đổi tên, mật khẩu, cập nhật địa chỉ.
- **`register.php`**: Form đăng ký tài khoản khách hàng mới.
- **`reset_password.php`**: Trang điền mật khẩu mới (kết hợp với token lấy lại mật khẩu).

### Thư mục `includes/` (Thành phần giao diện tái sử dụng)
- **`filter.php`**: Mã HTML và JS chứa bộ lọc sản phẩm (tìm kiếm, giá bán, độ phổ biến).
- **`footer.php`**: Phần chân trang hiển thị toàn bộ trang web.
- **`header.php`**: Thanh Menu (Navbar) chính, logo, và icon giỏ hàng.
- **`header_logic.php`**: Đoạn mã PHP tính toán nhanh số lượng đồ uống trong giỏ hàng hiện tại.

### Thư mục `api/` (API nghiệp vụ người dùng)
- **`cancel_order.php`**: Xử lý logic khi khách hàng muốn chủ động Hủy đơn hàng. → UPDATE `orders.status = 'cancelled'`
- **`check_coupon.php`**: Logic đối chiếu mã giảm giá. → Chỉ SELECT từ `coupons`, không ghi DB.
- **`checkout.php`**: API tiếp nhận bước đầu các yêu cầu thông tin tạo thanh toán.
- **`comfirm_payment.php`**: API xác nhận thanh toán online → INSERT vào `orders`, `order_items`, `notifications`; UPDATE `products`, `coupons`; DELETE `payment_waiting`.
- **`confirm_cod_order.php`**: API xác nhận COD → INSERT vào `orders`, `order_items`, `notifications`; UPDATE `products`, `coupons`; DELETE `payment_waiting`.
- **`create_order.php`**: Logic tạo đơn hàng tạm → INSERT vào `payment_waiting`.
- **`get_order_status.php`**: Endpoint lấy trạng thái thời gian thực của đơn hàng → SELECT từ `orders`.
- **`save_checkout.php`**: API hỗ trợ lưu nháp dữ liệu đơn hàng trước khi thanh toán.

### Thư mục `ajax/` (Thao tác giỏ hàng bất đồng bộ)
- **`add_cart.php`**: Thêm vào `$_SESSION['cart']` → Không ghi DB.
- **`change_cart_size.php`**: Cập nhật `$_SESSION['cart']` khi đổi size → Không ghi DB.
- **`remove_cart.php`**: Xóa item khỏi `$_SESSION['cart']` → Không ghi DB.
- **`remove_multiple_cart.php`**: Xóa nhiều items khỏi `$_SESSION['cart']` → Không ghi DB.
- **`update_cart.php`**: Cập nhật số lượng trong `$_SESSION['cart']` → Không ghi DB.

### Thư mục `cron/` (Tiến trình tự động hóa)
- **`cleanup_payment_waiting.php`**: Tự động quét và xóa các đơn trong `payment_waiting` quá hạn chưa thanh toán.

### Thư mục `admin/` (Khu vực Quản Trị Viên)
- **`auth_admin.php`**: Middleware kiểm soát phiên bảo mật.
- **`dashboard.php`**: Bảng điều khiển trung tâm → SELECT từ `orders`, `users`, `products`.
- **`delete_coupon.php`**: Xóa mã giảm giá → DELETE từ `coupons`.
- **`delete_order.php`**: Xóa đơn hàng → DELETE từ `orders` và `order_items`.
- **`delete_product.php`**: Xóa sản phẩm → DELETE từ `products`.
- **`delete_user.php`**: Khóa/xóa tài khoản → DELETE hoặc UPDATE từ `users`.

### Thư mục `admin/pages/` (Giao diện Quản trị)
- **`add_coupon.php`**: Tạo mã giảm giá mới → INSERT vào `coupons`.
- **`add_product.php`**: Thêm sản phẩm mới → INSERT vào `products`.
- **`edit_coupon.php`**: Chỉnh sửa mã giảm giá → UPDATE `coupons`.
- **`edit_product.php`**: Cập nhật sản phẩm → UPDATE `products`.
- **`edit_status_product_user.php`**: Cập nhật trạng thái đơn hàng → UPDATE `orders` (status, payment_status, paid_at).
- **`manage_coupons.php`**: Giao diện quản lý Mã Khuyến Mãi → SELECT từ `coupons`.
- **`manage_products.php`**: Giao diện danh sách đồ uống → SELECT từ `products`.
- **`notifications.php`**: Gửi thông báo → INSERT vào `notifications`.
- **`orders.php`**: Danh sách tất cả đơn hàng → SELECT từ `orders`.
- **`users.php`**: Danh sách tài khoản thành viên → SELECT từ `users`.

### Thư mục `admin/api/` (API vùng Quản trị)
- **`product.php`**: Cung cấp dữ liệu đồ uống cho Ajax → SELECT từ `products`.
- **`revenue.php`**: Xuất dữ liệu báo cáo doanh thu → SELECT từ `orders` theo ngày/tháng.
- **`user.php`**: Cung cấp thông tin Users → SELECT từ `users`.

---

*Tài liệu chi tiết được cập nhật lần cuối: 2026-04-04 — Bao gồm toàn bộ 16 bảng và bản đồ dữ liệu cho từng chức năng.*
