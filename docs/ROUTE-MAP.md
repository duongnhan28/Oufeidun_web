# Đối chiếu route Next.js → PHP

| Chức năng | Route giữ nguyên | Trạng thái source PHP |
|---|---|---|
| Trang chủ + tra cứu inline | `/` | Đã chuyển |
| Danh mục sản phẩm | `/san-pham` | Đã chuyển |
| Chi tiết sản phẩm | `/san-pham/{slug}` | Đã chuyển |
| OEM | `/dich-vu-oem` | Đã chuyển |
| Đại lý | `/dai-ly-phan-phoi` | Đã chuyển |
| Giới thiệu | `/gioi-thieu` | Đã chuyển |
| Tin tức | `/tin-tuc`, `/tin-tuc/{slug}` | Đã chuyển |
| Liên hệ | `/lien-he` | Đã chuyển |
| Đăng nhập/admin | `/login`, `/admin/*` | Đã dựng auth và màn hình |
| Tra cứu public | `/api/glass-lookup` | Đã chuyển sang PDO MySQL |
| Phục vụ ảnh DB | `/api/images/{id}` | Đã chuyển sang PDO MySQL |
| Form liên hệ | `/api/contact` | Đã chuyển lưu MySQL; SMTP chờ cấu hình |
| CRUD sản phẩm | `/api/admin/products*` | Đã chuyển backend |
| Upload ảnh | `/api/admin/images` | Đã chuyển backend |
| CRUD mã kính | `/api/admin/glass-lookup*` | Đã chuyển backend |

UI admin thao tác đầy đủ và API cho app bán hàng sẽ được hoàn thiện/kiểm thử sau khi có database MySQL thử nghiệm.

