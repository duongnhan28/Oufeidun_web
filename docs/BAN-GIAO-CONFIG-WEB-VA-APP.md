# TÀI LIỆU CẤU HÌNH HOSTING CHUNG CHO WEB VÀ APP OUFEIDUN

## 1. Mục đích

Tài liệu này dùng để gửi cho nhà cung cấp hosting triển khai đồng thời:

- Website Oufeidun viết bằng PHP.
- Backend API phục vụ ứng dụng bán hàng Oufeidun.
- Hai vùng dữ liệu độc lập trên cùng một máy chủ MySQL.

Ứng dụng bán hàng là ứng dụng Electron cài trên máy Windows của khách hàng. App **không cài trên hosting** và **không kết nối trực tiếp MySQL**. App chỉ gọi API HTTPS do source PHP cung cấp.

## 2. Source code

| Hạng mục | Vị trí local | Repository |
| --- | --- | --- |
| Website + PHP API | `E:\Oufeidun_php` | https://github.com/duongnhan28/Oufeidun_web.git |
| App bán hàng | `E:\AppBanHang` | https://github.com/duongnhan28/oufeidun_app.git |

Source cần đưa lên hosting là repository **Oufeidun_web**. Source **oufeidun_app** chỉ dùng để build file cài đặt `.exe` trên máy phát triển.

## 3. Kiến trúc triển khai

```text
Trình duyệt người dùng ───────┐
                             ├── HTTPS ──> Website PHP + API
App bán hàng trên Windows ───┘                    │
                                                  ├── oufeidun_web
                                                  └── oufeidun_app
                                                       MySQL
```

- Website và API dùng chung một domain và một source PHP.
- Một MySQL server chứa hai database/schema riêng:
  - `oufeidun_web`: nội dung website, quản trị web, liên hệ, bài viết và dữ liệu tra cứu kính.
  - `oufeidun_app`: tài khoản nhân viên, sản phẩm, kho, đơn hàng, thanh toán và lịch sử app bán hàng.
- Trong MySQL, `DATABASE` và `SCHEMA` được hiểu tương đương. Đây là **một MySQL server nhưng hai database**, không phải hai máy chủ.
- App gọi API bằng bearer token. Mọi tài khoản, mật khẩu MySQL chỉ nằm trong file `.env` của PHP trên server.
- Không cần cron job, Redis, WebSocket hoặc tiến trình chạy nền.

## 4. Yêu cầu gói hosting

### Bắt buộc

- Linux shared hosting, Cloud Hosting hoặc VPS có PHP **8.2 trở lên**.
- MySQL **8.0 trở lên** hoặc MariaDB **10.6 trở lên**.
- Cho phép tạo ít nhất **2 MySQL database**.
- Apache hoặc LiteSpeed hỗ trợ `.htaccess` và rewrite URL.
- Có thể đặt document root của domain vào thư mục `public/`.
- HTTPS/SSL hợp lệ.
- Hỗ trợ các HTTP method: `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`.
- Không làm mất HTTP header `Authorization` khi chuyển request vào PHP/FastCGI.
- PHP extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`, `iconv`, `session`.
- Cho phép gửi email SMTP ra cổng 465 hoặc 587.
- Có File Manager hoặc SFTP. SSH/Terminal được khuyến nghị để chạy migration.

### Tài nguyên khuyến nghị

| Tài nguyên | Tối thiểu | Khuyến nghị |
| --- | ---: | ---: |
| CPU | 1 core | 1.5–2 cores |
| RAM | 1 GB | 2 GB |
| SSD/NVMe | 2 GB | 5–10 GB |
| Băng thông | 20 GB/tháng | 40–80 GB/tháng hoặc không giới hạn |
| PHP memory limit | 128 MB | 256 MB |
| PHP max execution time | 60 giây | 120 giây |
| Upload/Post limit | 8 MB | 16 MB trở lên |
| MySQL `max_allowed_packet` | 16 MB | 32 MB trở lên |

Gói tương đương LH2 có thể sử dụng nếu nhà cung cấp xác nhận đầy đủ các điều kiện bắt buộc ở trên, đặc biệt là: 2 database, PHP 8.2+, document root `public/`, rewrite, Authorization header và HTTPS.

## 5. Cấu trúc thư mục trên hosting

Toàn bộ source PHP được upload hoặc clone vào một thư mục riêng. Domain phải trỏ trực tiếp tới thư mục `public/`.

Ví dụ:

```text
/home/ACCOUNT/oufeidun/
├── app/
├── config/
├── database/
├── scripts/
├── storage/
├── public/             <-- Document Root của domain
│   ├── index.php
│   └── .htaccess
├── routes.php
└── .env                <-- Không public, không commit Git
```

Không trỏ domain vào thư mục gốc của source vì có thể làm lộ `.env`, script migration và file nội bộ.

## 6. Cấu hình MySQL

Nhà cung cấp cần tạo:

1. Database web: `oufeidun_web`.
2. Database app: `oufeidun_app`.
3. Một MySQL user chung có quyền trên cả hai database, hoặc hai user riêng có quyền trên từng database.
4. Charset `utf8mb4`, collation ưu tiên `utf8mb4_unicode_ci`.

Tên database/user trên shared hosting có thể bị thêm tiền tố tài khoản, ví dụ `account_oufeidun_web`. Khi đó cần dùng chính xác tên đầy đủ do control panel tạo ra trong `.env`.

Quyền cần có trên database tương ứng:

```text
SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP,
REFERENCES, CREATE TEMPORARY TABLES
```

Không cần cấp quyền MySQL từ Internet cho app. Chỉ PHP trên hosting kết nối tới MySQL nội bộ.

## 7. File `.env` của PHP trên hosting

Sao chép `.env.example` thành `.env`, sau đó điền giá trị thật. Không gửi file chứa mật khẩu qua Git hoặc kênh công khai.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TEN-MIEN-THAT
APP_KEY=CHUOI_NGAU_NHIEN_TOI_THIEU_64_KY_TU

DB_HOST=127.0.0.1
DB_PORT=3306
DB_WEB_DATABASE=TEN_DATABASE_WEB_DAY_DU
DB_APP_DATABASE=TEN_DATABASE_APP_DAY_DU

# Phương án 1: một MySQL user dùng chung
DB_USERNAME=MYSQL_USER
DB_PASSWORD=MYSQL_PASSWORD

# Phương án 2: dùng user riêng; điền bốn biến dưới và có thể để trống
# DB_USERNAME/DB_PASSWORD nếu nhà cung cấp yêu cầu
DB_WEB_USERNAME=
DB_WEB_PASSWORD=
DB_APP_USERNAME=
DB_APP_PASSWORD=

DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_CREATE_DATABASES=false

MAIL_HOST=SMTP_HOST
MAIL_PORT=587
MAIL_USERNAME=SMTP_USERNAME
MAIL_PASSWORD=SMTP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@TEN-MIEN-THAT
MAIL_FROM_NAME=Oufeidun
CONTACT_TO_EMAIL=EMAIL_NHAN_LIEN_HE

GLASS_LOOKUP_ENABLED=true
TRUSTED_PROXY_IP_HEADER=
APP_API_ALLOWED_ORIGINS=shop://app
```

Lưu ý:

- Trên shared hosting, giữ `DB_CREATE_DATABASES=false` vì database được tạo sẵn trong control panel.
- Chỉ bật `APP_DEBUG=true` tạm thời khi xử lý lỗi, sau đó phải trả về `false`.
- Nếu domain đi qua Cloudflare/reverse proxy, nhà cung cấp xác nhận giá trị phù hợp cho `TRUSTED_PROXY_IP_HEADER`; không tự đặt khi chưa xác định proxy tin cậy.
- Có thể thêm `http://127.0.0.1:5178` vào `APP_API_ALLOWED_ORIGINS` ở môi trường test local; production chỉ cần `shop://app`.

## 8. Cấu hình app production

Sau khi website/API đã hoạt động với HTTPS, tạo file local trong source app dựa trên `.env.production-mysql.example`:

```dotenv
VITE_SERVER_ENV=production
VITE_DEMO_MODE=false
VITE_DATA_BACKEND=api
VITE_API_BASE_URL=https://TEN-MIEN-THAT/api/app/v1
VITE_GLASS_LOOKUP_API_URL=https://TEN-MIEN-THAT/api/glass-lookup
VITE_SUPABASE_URL=
VITE_SUPABASE_PUBLISHABLE_KEY=
```

App production chỉ nhận hai URL công khai trên. Tuyệt đối không đưa các biến `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` hoặc tài khoản hosting vào source/bộ cài app.

Build app thực hiện trên máy phát triển, không thực hiện trên shared hosting:

```powershell
npm.cmd install
npm.cmd run build:production
npm.cmd run make:production
```

Do đó production hosting **không bắt buộc có Node.js** và không cần lưu source Electron.

## 9. API mà app sử dụng

Base URL: `https://TEN-MIEN-THAT/api/app/v1`

Các endpoint chính:

```text
POST   /auth/login
POST   /auth/logout
POST   /session/touch
GET    /workspace
POST   /query
POST   /mutations
POST   /orders/features
POST   /products/delete
POST   /reports
POST   /staff
POST   /images
GET    /images/{path}
DELETE /images/{path}
```

API công khai liên quan:

```text
GET  /api/glass-lookup
GET  /api/images/{id}
POST /api/contact
```

Yêu cầu kỹ thuật:

- Login trả token; các request nghiệp vụ tiếp theo gửi `Authorization: Bearer <token>`.
- Web server/WAF/ModSecurity không được chặn `Authorization`, `OPTIONS`, `PUT` hoặc `DELETE` của các đường dẫn API hợp lệ.
- CORS cho app được giới hạn bởi `APP_API_ALLOWED_ORIGINS=shop://app`.
- Website, API app và API tra cứu nên chạy chung một HTTPS origin để giảm lỗi CORS và cấu hình đơn giản hơn.

## 10. Các bước triển khai

1. Trỏ DNS tên miền về hosting và cấp SSL.
2. Tạo hai database MySQL cùng user và quyền cần thiết.
3. Upload/clone repository `Oufeidun_web` lên server.
4. Trỏ document root của domain vào `public/`.
5. Tạo `.env` production theo mục 7.
6. Bảo đảm PHP có quyền ghi vào các thư mục runtime/upload theo hướng dẫn trong source.
7. Chạy migration:

   ```bash
   php scripts/migrate.php
   ```

8. Kiểm tra kết nối và cấu trúc hai database:

   ```bash
   php scripts/check-databases.php
   ```

9. Import dữ liệu đã chốt từ Supabase/PostgreSQL cũ sang hai database MySQL theo tài liệu migration của dự án.
10. Tạo tài khoản quản trị web:

    ```bash
    php scripts/create-admin.php USERNAME PASSWORD
    ```

11. Tạo tài khoản quản trị app/POS:

    ```bash
    php scripts/create-pos-admin.php LOGIN "DISPLAY NAME" PASSWORD
    ```

12. Kiểm tra website, admin, tra cứu kính, gửi email và toàn bộ API.
13. Điền domain thật vào cấu hình production của app, build bộ cài và kiểm thử đăng nhập/nghiệp vụ.
14. Chỉ chuyển vận hành chính thức sau khi đối soát dữ liệu và có bản backup.

Nếu shared hosting không có SSH/Terminal, nhà cung cấp cần hỗ trợ chạy hai lệnh ở bước 7–8 và các lệnh tạo tài khoản, hoặc cung cấp cơ chế chạy PHP CLI an toàn.

## 11. Checklist nghiệm thu hosting

- [ ] Domain mở bằng HTTPS và không có mixed content.
- [ ] Document root là thư mục `public/`, không thể tải `.env` qua trình duyệt.
- [ ] Trang chủ và các trang web chính hoạt động.
- [ ] `/api/glass-lookup?q=A12` trả JSON hợp lệ.
- [ ] Đăng nhập quản trị web hoạt động.
- [ ] Đăng nhập app qua `/api/app/v1/auth/login` hoạt động.
- [ ] Request có bearer token tới `/api/app/v1/workspace` không bị mất header.
- [ ] `OPTIONS`, `PUT` và `DELETE` không bị web server/WAF chặn nhầm.
- [ ] Upload và xem ảnh hoạt động.
- [ ] Form liên hệ gửi được email SMTP.
- [ ] Dữ liệu web nằm trong database web; dữ liệu bán hàng nằm trong database app.
- [ ] App không chứa hoặc hiển thị thông tin đăng nhập MySQL.
- [ ] Backup tự động bao gồm cả hai database và file ảnh/upload.
- [ ] Có phương án khôi phục backup và thông tin thời gian lưu backup.

## 12. Thông tin đề nghị nhà cung cấp trả lại

Nhà cung cấp vui lòng điền hoặc xác nhận các mục sau. Mật khẩu nên gửi qua kênh riêng, không ghi trực tiếp vào tài liệu công khai.

```text
Tên gói hosting:
Control panel URL:
Tên miền chính:
Document root đã cấu hình:
PHP version:
MySQL/MariaDB version:
SSH/Terminal: Có / Không
SFTP host và port:

DB_HOST:
DB_PORT:
DB_WEB_DATABASE:
DB_APP_DATABASE:
DB_USERNAME dùng chung hoặc tên hai user riêng:
Đã cấp quyền cho cả hai database: Có / Không

SMTP_HOST:
SMTP_PORT:
SMTP_ENCRYPTION:
SMTP_USERNAME:

SSL đã kích hoạt: Có / Không
Authorization header được chuyển vào PHP: Có / Không
Hỗ trợ GET/POST/PUT/DELETE/OPTIONS: Có / Không
Document root trỏ được vào public/: Có / Không
Backup database và file: tần suất / số ngày lưu
Giới hạn dung lượng, RAM, CPU, băng thông:
Giới hạn upload, PHP memory, execution time:
```

## 13. Bảo mật và vận hành

- Không commit `.env`, database dump, token, mật khẩu hoặc khóa SMTP lên Git.
- Không mở MySQL public cho app desktop; app chỉ giao tiếp với API HTTPS.
- Bật HTTPS bắt buộc, `APP_DEBUG=false` và mật khẩu mạnh ở production.
- Nên dùng user MySQL riêng cho từng database nếu gói hosting hỗ trợ; nếu dùng chung thì chỉ cấp quyền trên đúng hai database này.
- Backup trước khi migration/cập nhật source. Backup phải gồm hai database và toàn bộ ảnh/upload.
- Khi cập nhật source, chạy migration trước khi phát hành app cần schema mới; không xóa dữ liệu production thủ công.
- Không cần cron job để giữ database hoạt động. MySQL của hosting hoạt động liên tục theo dịch vụ của nhà cung cấp.

## 14. Tóm tắt ngắn cho nhà cung cấp

Khách hàng cần host một website **PHP 8.2 + MySQL 8/MariaDB 10.6**, đồng thời website cung cấp API HTTPS cho app Windows. Một MySQL server cần **hai database** là web và app. Domain phải trỏ vào thư mục `public/`; server phải hỗ trợ `.htaccess`, rewrite, header `Authorization`, các method `GET/POST/PUT/DELETE/OPTIONS`, SSL và SMTP. Không cần Node.js production, cron, Redis hay WebSocket. App không kết nối trực tiếp database.
