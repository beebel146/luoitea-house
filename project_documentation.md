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

## 7. Phân Tích Chức Năng Của Từng Tệp (File-By-File Breakdown)

Dưới đây là danh sách chi tiết các công việc và nhiệm vụ của từng file PHP trong hệ thống:

### Thư mục Gốc (Root)
- **`index.php`**: Trang chủ của website, hiển thị danh sách sản phẩm nổi bật/tất cả, xử lý các bộ lọc tìm kiếm sản phẩm.
- **`README.md`**: Tệp văn bản Markdown chứa thông tin giới thiệu và tóm tắt cài đặt dự án.

### Thư mục `config/` (Cấu hình hệ thống)
- **`config.php`**: Khai báo các hằng số, biến môi trường dùng chung (`BASE_URL`, v.v.).
- **`databases.php`**: Class kết nối đến MySQL. Tự động kiểm tra và tạo CSDL `luoitea_house_2` nếu chưa tồn tại.
- **`migrate.php`**: Tệp chạy chung để khởi chạy tự động các file tạo bảng trong thư mục `migration/`.
- **`migrate_notifications.php`**, **`migrate_order_items.php`**, **`migrate_sizes.php`**: Các file script chạy lệnh thay đổi / thêm cột CSDL thủ công (được đặt ngoài thư mục `migration/` để chạy độc lập theo yêu cầu thay vì chạy tự động).

### Thư mục `pages/` (Giao diện Frontend Người dùng)
- **`cart.php`**: Trang hiển thị giỏ hàng hiện tại, các sản phẩm đã chọn, và tổng tiền dự kiến.
- **`category.php`**: Trang danh sách sản phẩm hiển thị riêng theo từng danh mục cụ thể (Ví dụ: Trà sữa, Trà trái cây...).
- **`checkout.php`**: Form trang thanh toán, cho phép người dùng điền thông tin, nhập mã giảm giá và chọn phương thức thanh toán.
- **`contact.php`**: Trang thông tin liên hệ tĩnh của cửa hàng.
- **`forgotPassword.php`**: Trang yêu cầu cấp lại mật khẩu (cho người dùng quên mật khẩu).
- **`login.php`**: Trang đăng nhập vào hệ thống dành cho khách hàng.
- **`logout.php`**: File xử lý logic hủy phiên đăng nhập (`session_destroy()`).
- **`notifications.php`**: Hộp thư đọc thông báo hệ thống được gửi từ Admin.
- **`order_detail.php`**: Trang xem chi tiết một đơn đặt hàng của người dùng (món nước, giá, tổng tiền, trạng thái giao hàng và đặc biệt là trạng thái/phương thức thanh toán chính xác).
- **`orders.php`**: Trang liệt kê toàn bộ lịch sử các đơn hàng đã đặt của khách hàng.
- **`payment.php`**: Trang hiển thị trạng thái trung gian hoặc thông tin xử lý thanh toán.
- **`product_detail.php`**: Trang hiển thị chi tiết một loại đồ uống, nơi người dùng chọn thuộc tính (Size, Số lượng) để thêm vào giỏ.
- **`profile.php`**: Trang hồ sơ cá nhân để người dùng thay đổi tên, mật khẩu, cập nhật địa chỉ.
- **`register.php`**: Form đăng ký tài khoản khách hàng mới.
- **`reset_password.php`**: Trang điền mật khẩu mới (kết hợp với token lấy lại mật khẩu).

### Thư mục `includes/` (Thành phần giao diện tái sử dụng)
- **`filter.php`**: Mã HTML và JS chứa bộ lọc sản phẩm (tìm kiếm, giá bán, độ phổ biến).
- **`footer.php`**: Phần chân trang hiển thị toàn bộ trang web (thông tin bản quyền, links).
- **`header.php`**: Thanh Menu (Navbar) chính, logo, và icon giỏ hàng hiển thị ở trên cùng.
- **`header_logic.php`**: Đoạn mã PHP tính toán nhanh số lượng đồ uống trong giỏ hàng hiện tại để hiển thị số lượng trên biểu tượng giỏ ở Header.

### Thư mục `api/` (API nghiệp vụ người dùng)
- **`cancel_order.php`**: Xử lý logic khi khách hàng muốn chủ động Hủy đơn hàng trước khi quán duyệt.
- **`check_coupon.php`**: Logic đối chiếu mã giảm giá người dùng nhập xem có hợp lệ, còn số lượng và thời hạn không.
- **`checkout.php`**: API tiếp nhận bước đầu các yêu cầu thông tin tạo thanh toán.
- **`comfirm_payment.php`**: API callback xử lý lưu trữ trạng thái thanh toán sau khi người dùng quét mã/chuyển khoản online thành công.
- **`confirm_cod_order.php`**: API xử lý bước xác nhận thanh toán khi chọn Giao hàng nhận tiền (COD).
- **`create_order.php`**: Logic tạo mới thông tin đơn hàng lưu vào CSDL, chốt giỏ hàng.
- **`get_order_status.php`**: Endpoint lấy trạng thái thời gian thực của đơn hàng để hiển thị cho phía client (ví dụ đang chờ, hay đã xong).
- **`save_checkout.php`**: API hỗ trợ lưu nháp dữ liệu đơn hàng ngay trước khi chuyển hướng đến cổng thanh toán.

### Thư mục `ajax/` (Thao tác giỏ hàng bất đồng bộ)
- **`add_cart.php`**: Nhận ID đồ uống, size và số lượng để bổ sung vào mảng `$_SESSION['cart']`.
- **`change_cart_size.php`**: Cập nhật thay đổi khi người dùng đổi size (ví dụ từ M lên L) trong trang giỏ hàng.
- **`remove_cart.php`**: Xóa một món cụ thể dựa trên ID và Size ra khỏi giỏ.
- **`remove_multiple_cart.php`**: Logic xóa đồng loạt nhiều món trong giỏ cùng lúc.
- **`update_cart.php`**: Cập nhật lại số lượng mua (Cộng/Trừ) cho một món trong giỏ hàng.

### Thư mục `cron/` (Tiến trình tự động hóa)
- **`cleanup_payment_waiting.php`**: Logic quét DB, tự động đánh dấu Hủy (`cancel`) cho các đơn hàng đợi thanh toán online nhưng bị quá hạn thời gian cho phép.

### Thư mục `admin/` (Khu vực Quản Trị Viên)
- **`auth_admin.php`**: Middleware kiểm soát phiên bảo mật; đuổi người dùng thường ra khỏi khu vực admin.
- **`dashboard.php`**: Bảng điều khiển trung tâm của Admin, chứa báo cáo doanh thu, thống kê các thống số bán hàng.
- **`delete_coupon.php`**: Logic xóa bản ghi Mã giảm giá khỏi Database.
- **`delete_order.php`**: Logic xóa bỏ hẳn một đơn hàng khỏi hệ thống CSDL.
- **`delete_product.php`**: Logic xóa bỏ một thức uống vĩnh viễn khỏi danh sách.
- **`delete_user.php`**: Logic khóa/xóa tài khoản khách hàng.

### Thư mục `admin/pages/` (Giao diện Quản trị)
- **`add_coupon.php`**: Form tạo mã giảm giá, mức giảm, thời hạn.
- **`add_product.php`**: Form nhập thức uống mới (đăng ảnh, thiết lập giá S, M, L).
- **`edit_coupon.php`**: Chỉnh sửa mã giảm giá hiện có.
- **`edit_product.php`**: Cập nhật thông tin hình ảnh, giá bán của đồ uống.
- **`edit_status_product_user.php`**: Cửa sổ thiết lập chi tiết trạng thái đơn hàng. Nơi hiển thị thông tin chi tiết đầy đủ của đơn (tên thật người nhận, ngày đặt, hình thức và trạng thái thanh toán, ghi chú, mã coupon). Tích hợp sẵn logic tự động quản lý trạng thái thanh toán đối với đơn COD.
- **`manage_coupons.php`**: Giao diện quản lý toàn bộ Mã Khuyến Mãi.
- **`manage_products.php`**: Giao diện hiển thị danh sách các loại đồ uống.
- **`notifications.php`**: Hệ thống soạn thảo và gửi thông báo chung (Khuyến mãi dịp lễ...) đến toàn bộ Users.
- **`orders.php`**: Trang hiển thị danh sách tổng hợp tất cả các đơn hàng, cung cấp đường dẫn để xem và cập nhật chi tiết tình trạng đơn.
- **`users.php`**: Trang danh sách tài khoản thành viên trong hệ thống.

### Thư mục `admin/api/` (API vùng Quản trị)
- **`product.php`**: API cung cấp danh sách dữ liệu đồ uống hiển thị qua ajax cho Admin.
- **`revenue.php`**: API xuất dữ liệu báo cáo thống kê doanh thu theo ngày/tháng để vẽ đồ thị (charts) lên Dashboard.
- **`user.php`**: API cung cấp thông tin Users cho việc hiển thị bảng.

---
*Tài liệu chi tiết được cập nhật theo cấu trúc source code hiện thời.*
