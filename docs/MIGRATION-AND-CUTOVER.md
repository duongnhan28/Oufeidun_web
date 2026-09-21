# Migration PostgreSQL/Supabase sang MySQL

## Nguyên tắc

- Source PostgreSQL/Supabase không bị sửa hoặc xóa.
- Import MySQL theo `legacy_id`, có thể chạy lại mà không tạo dữ liệu trùng.
- Website và app bán hàng cùng dùng một máy chủ MySQL nhưng tách thành `oufeidun_web` và `oufeidun_app`; chỉ backend PHP được giữ thông tin kết nối.
- App Electron gọi API HTTPS; tuyệt đối không nhúng username/password MySQL vào bộ cài.
- Ảnh được đối chiếu bằng ID, MIME, kích thước và checksum trước khi nghiệm thu.

## Bảng website đã ánh xạ

- `admin_users`, `admin_sessions`, `login_limits`
- `products`, `product_images`
- `contact_messages`
- `glass_lookup_products`, `glass_lookup_models`, `glass_lookup_images`

## Thông tin còn chờ

- MySQL production: host, port, username, password và quyền truy cập hai schema.
- Phiên bản MySQL/MariaDB thực tế của hosting.
- Document root và quyền chạy PHP CLI/Cron.
- Cấu hình SMTP.
- Quyết định lưu ảnh dạng BLOB như hiện tại hay chuyển sang file/object storage.

## Trình tự cutover

1. Backup đầy đủ PostgreSQL/Supabase và Storage.
2. Chạy migration MySQL trên database thử nghiệm.
3. Import lần thử; đối chiếu số bảng, số dòng, SKU, model, ảnh và checksum.
4. Kiểm thử public site, admin, tra cứu, form liên hệ và API app bán hàng.
5. Chốt thời gian bảo trì; dừng ghi dữ liệu hệ thống cũ.
6. Import delta cuối cùng và đối chiếu tồn kho/hóa đơn nếu app bán hàng đã tham gia.
7. Chuyển domain, phát hành cấu hình API mới cho app Electron.
8. Giữ Supabase ở trạng thái chỉ đọc tối thiểu 2–4 tuần để rollback.
