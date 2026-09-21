# API cho AppBanHang

Base URL: `https://ten-mien.vn/api/app/v1`

Mọi endpoint sau đăng nhập dùng header `Authorization: Bearer <access_token>`. Token không lưu cố định trong source và sẽ mất khi đóng app, giữ đúng hành vi hiện tại.

| Chức năng Supabase cũ | Endpoint PHP mới |
|---|---|
| `signInWithPassword` + `claim_session` | `POST /auth/login` |
| `touch_session` | `POST /session/touch` |
| `release_session` | `POST /auth/logout` |
| `app_ban_hang_read` | `GET /workspace` |
| `app_ban_hang_write` | `POST /mutations` |
| `app_ban_hang_delete_product` | `POST /products/delete` |
| `app_ban_hang_report` | `POST /reports` |
| `app_ban_hang_manage_staff` | `POST /staff` |
| Storage upload/remove/read | `/images` và `/images/{path}` |

Payload mutation giữ nguyên `action`, `key` và các field hiện tại nên UI Electron không phải viết lại. Chỉ thay module `src/data/client.ts` từ Supabase SDK sang `fetch` API.

Các bảo vệ đã giữ lại trong backend PHP:

- Một tài khoản chỉ hoạt động trên một thiết bị tại một thời điểm.
- Role admin/staff và thu hồi phiên khi khóa hoặc đổi mật khẩu.
- Idempotency key cho mọi mutation.
- Optimistic version cho sản phẩm, đơn hàng và tồn kho.
- Transaction + `SELECT ... FOR UPDATE` khi trừ/hoàn kho.
- Chặn thiếu tồn, thay đổi giá giữa lúc lập đơn, trả vượt số lượng đã bán.
- Giữ snapshot SKU, tên, giá bán và giá vốn trên giao dịch.
- Soft-delete sản phẩm/nhân viên để giữ lịch sử.

