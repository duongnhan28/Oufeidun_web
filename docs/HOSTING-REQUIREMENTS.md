# Yêu cầu hosting cho Oufeidun

## Kiến trúc triển khai

- Website: PHP thuần, giao diện render phía server; không cần Node.js khi chạy production.
- PHP: 8.2 hoặc 8.3.
- Database: MySQL 8.0+, gồm hai database trên cùng máy chủ (có thể dùng một user chung hoặc hai user riêng):
  - `oufeidun_web`: website, quản trị, liên hệ, dữ liệu tra cứu mã kính và ảnh.
  - `oufeidun_app`: dữ liệu ứng dụng bán hàng, truy cập thông qua API PHP của website.
- Web server: Apache hoặc LiteSpeed có URL rewrite; document root phải trỏ vào thư mục `public/`.
- HTTPS/SSL: bắt buộc.

## Cấu hình tối thiểu

- 1.5 CPU core, RAM 2 GB.
- SSD/NVMe từ 5 GB.
- Băng thông tối thiểu 40 GB/tháng; khuyến nghị 80 GB/tháng trở lên.
- PHP `memory_limit` từ 256 MB.
- `upload_max_filesize` và `post_max_size` từ 8 MB.
- MySQL `max_allowed_packet` từ 16 MB vì ảnh tra cứu được lưu trong database.
- Cho phép ít nhất 2 database MySQL; ưu tiên cho phép cấp user/quyền riêng cho từng database.

## PHP extension bắt buộc

- `pdo_mysql`
- `mbstring`
- `fileinfo`
- `openssl`
- `json`
- `iconv`
- `session`

## Tính năng hosting cần xác nhận

- Có SSH hoặc terminal/CLI để chạy migration và import dữ liệu.
- Cho phép đặt document root vào `public/` và bật rewrite về `public/index.php`.
- Cho phép kết nối SMTP ra ngoài qua cổng 465 hoặc 587.
- Có SSL miễn phí và tự gia hạn.
- Có backup tự động hằng ngày cho file và cả hai database; lưu tối thiểu 7 ngày.
- Có thể import database website khoảng 80 MB; nên cho phép file SQL ít nhất 150 MB.
- Cho phép request API từ app desktop với header `Authorization` và xử lý `OPTIONS`/CORS.
- Không cần cron chống ngủ đông khi sử dụng MySQL hosting thông thường.

## Quy mô hiện tại

- Source và media khoảng 38 MB.
- `oufeidun_web` khoảng 80 MB: 44 mã kính, 772 model tương thích, 88 ảnh tra cứu và 3 sản phẩm marketing.
- `oufeidun_app` đã có 19 bảng và API nghiệp vụ; dung lượng ban đầu dưới 1 MB trước khi nhập dữ liệu vận hành.
- Website có trang công khai, tra cứu mã kính, quản trị sản phẩm/mã kính/tin nhắn và API dành cho app bán hàng.

## Gói khuyến nghị

Ưu tiên hosting Linux có 2 CPU core, RAM 2 GB, SSD/NVMe 5–10 GB, băng thông từ 80 GB/tháng, ít nhất 2 database MySQL, SSH, SSL, SMTP outbound và backup hằng ngày. Nếu shared hosting không cho document root `public/`, URL rewrite hoặc API PHP dài hạn, nên chọn VPS nhỏ 2 vCPU/2 GB RAM.
