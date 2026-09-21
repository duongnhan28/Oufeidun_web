# Trạng thái migration 1–1

## Phạm vi phải giữ nguyên

- Route public, nội dung, UI responsive, ảnh và video.
- Tra cứu mã kính và xem hai ảnh bao bì.
- Danh mục/chi tiết sản phẩm, tin tức, OEM, đại lý, giới thiệu, liên hệ.
- Đăng nhập và trang quản trị sản phẩm, tra cứu mã kính, tin nhắn.
- Validation, rate limit, CSRF, session và phân quyền.

## Trạng thái

- [x] Khởi tạo source PHP 8.2 không phụ thuộc Composer.
- [x] Sao chép 48 asset ảnh/video đang được website sử dụng.
- [x] Tạo cấu hình `.env`, router, PDO MySQL, session và CSRF.
- [x] Chuyển toàn bộ route/view public sang PHP, giữ route và asset.
- [x] Chuyển API tra cứu, liên hệ và ảnh sang PDO MySQL.
- [x] Dựng đăng nhập, rate limit, session và backend CRUD admin.
- [x] Hoàn tất schema MySQL cho dữ liệu website.
- [x] Hoàn thiện gửi SMTP tự chứa, không phụ thuộc package ngoài.
- [x] Hoàn thiện tương tác form CRUD sản phẩm, upload ảnh và mã kính.
- [x] Viết importer dry-run/apply cho snapshot tra cứu 44 SKU, 772 model và 88 ảnh.
- [x] Kết nối MySQL local 8.0.46, tạo đủ 28 bảng và nhập thành công 44 SKU, 772 model, 88 quan hệ ảnh cùng 88 tệp ảnh.
- [ ] Viết importer cho sản phẩm marketing, tài khoản, liên hệ và dữ liệu app khi có quyền đọc DB nguồn/đích.
- [x] Tạo schema MySQL riêng cho nghiệp vụ app bán hàng trong cùng database.
- [x] Xây API đăng nhập/session, workspace, mutation, báo cáo, nhân viên và ảnh cho app.
- [ ] Thay adapter `src/data/client.ts` của app sau khi có URL API thử nghiệm.
- [ ] Import và đối chiếu dữ liệu app bán hàng từ Supabase.
- [ ] Chạy visual regression desktop/mobile.
- [ ] Chạy UAT và cutover production.

Database local đã được kết nối và kiểm thử. Database production vẫn chờ thông tin hosting để chạy migration, nhập dữ liệu và cutover.
