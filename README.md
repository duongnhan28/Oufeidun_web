# Oufeidun PHP

Bản migration 1–1 từ website Next.js Oufeidun sang PHP 8.2 + MySQL 8, tối ưu để triển khai trên shared hosting.

## Yêu cầu

- PHP 8.2+ với PDO MySQL, mbstring, fileinfo, openssl
- MySQL 8.0+ hoặc MariaDB 10.6+
- Apache có `mod_rewrite`, document root trỏ tới thư mục `public/`

## Chạy sau khi có database

1. Sao chép `.env.example` thành `.env`, cấu hình `DB_WEB_DATABASE` và `DB_APP_DATABASE` cùng tài khoản MySQL có quyền trên cả hai schema.
2. Trỏ domain tới `public/`.
3. Chạy `php scripts/migrate.php`.
4. Tạo tài khoản quản trị web bằng `php scripts/create-admin.php <username> <password>`.
5. Tạo tài khoản app bằng `php scripts/create-pos-admin.php <login> <display-name> <password>` nếu không import Auth cũ.
6. Chạy công cụ import PostgreSQL/Supabase theo tài liệu trong `docs/` khi thông tin DB nguồn và đích được cung cấp.

Không commit `.env`, file export database hoặc thông tin bí mật lên Git.

Website và app dùng chung một máy chủ MySQL nhưng tách dữ liệu thành hai schema: `oufeidun_web` và `oufeidun_app`.
