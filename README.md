# Cờ Tướng (Xiangqi) Online

Game cờ tướng chơi online 2 người, realtime, giao diện Bootstrap 5 hiện đại.

## Kiến trúc

- **Backend**: Laravel (PHP) — API + Eloquent/MySQL + Sanctum (auth) + Reverb (WebSocket realtime).
- **Frontend**: Next.js (App Router, JavaScript) — tiêu thụ API Laravel, nhận cập nhật realtime qua Laravel Echo.
- **Database**: MySQL — tài khoản người dùng, phòng chơi (đồng thời là lịch sử đấu), bảng xếp hạng.
- **Môi trường chạy**: Docker / docker-compose là mục tiêu cuối (không cần sudo, không cài trực tiếp). *Tạm thời* (2026-07-28): Docker Hub bị rate-limit pull ẩn danh nên đã cài PHP 8.4.23 local (`%LOCALAPPDATA%\Programs\php84`, ngoài PATH mặc định) để chạy `composer install`/`artisan` khi cần — XAMPP PHP 8.2 không đủ (Laravel 13/Reverb yêu cầu PHP ≥ 8.4). Sẽ quay lại thuần Docker khi viết `docker-compose.yml`.

Chi tiết thiết kế đầy đủ (lý do các quyết định kiến trúc, sơ đồ thư mục, API/events, thứ tự triển khai) từng nằm trong plan đã duyệt tại `~/.claude/plans/tranquil-kindling-nest.md` — **file này hiện không còn trên máy** (đã bị dọn/mất); phần "Trạng thái hiện tại" bên dưới là nguồn tham chiếu chính cho tới khi có plan mới.

## Cấu trúc thư mục

```
Xiangqi-en/
├── backend/     # Laravel API
├── frontend/    # Next.js app
└── docker-compose.yml
```

## Trạng thái hiện tại

- [x] Laravel scaffold (`backend/`) qua Docker (`composer:2` image)
- [x] Sanctum cài đặt (`php artisan install:api`)
- [x] Reverb cài đặt (`composer install` + config `reverb`/`broadcasting` + `.env` + `reverb:start` đã verify boot sạch trên `:8080`)
- [x] Game engine luật cờ (PHP) — `backend/app/Games/Xiangqi/` (34 unit test: từng quân, check, flying-general, checkmate, stalemate — xem `backend/tests/Unit/Games/Xiangqi/`)
- [x] Migrations + Models (User: +rating/wins/losses/draws; Room: host/guest/board/move_history/result/winner — room = lịch sử đấu)
- [ ] AuthController, RoomController, Events, Leaderboard (persist ván đấu vào DB qua Room; hiện engine mới chỉ có API stateless để chơi thử)
- [x] Next.js scaffold (`frontend/`) — App Router, JavaScript, Bootstrap 5
- [x] **Bản chơi thử được** (`/` — hot-seat 2 người 1 trình duyệt): bàn cờ gọi thẳng 3 API stateless của engine (`/api/xiangqi/new`, `/move`, `/legal-moves`), chưa cần DB/auth/Reverb. Giao diện/chức năng tiếng Anh; quân cờ cố ý giữ chữ Hán truyền thống (帥仕相傌俥炮兵 / 將士象馬車砲卒).
- [ ] docker-compose.yml (MySQL + backend + reverb + frontend)
- [ ] Các trang: đăng nhập/đăng ký, lobby, bảng xếp hạng, phòng chơi thật (persist qua Room, realtime qua Reverb)

## Chạy thử ngay (bản hot-seat, chưa cần Docker/DB)

```bash
# Terminal 1 - backend (cần PHP >= 8.4 trên PATH, xem ghi chú PHP local ở trên)
cd backend && php artisan serve --port=8000

# Terminal 2 - frontend
cd frontend && npm run dev
```

Mở http://localhost:3000 → 2 người thay phiên bấm quân trên cùng 1 máy. `frontend/.env.local` trỏ `NEXT_PUBLIC_API_URL` sang `http://127.0.0.1:8000`.

## Chạy dự án (sau khi hoàn thiện docker-compose + DB + Reverb + auth)

```bash
docker compose up
```

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000
- Reverb (WebSocket): ws://localhost:6001
- MySQL: localhost:3306
