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
- [ ] Game engine luật cờ (PHP) — `backend/app/Games/Xiangqi/`
- [x] Migrations + Models (User: +rating/wins/losses/draws; Room: host/guest/board/move_history/result/winner — room = lịch sử đấu)
- [ ] AuthController, RoomController, Events, Leaderboard
- [ ] Next.js scaffold (`frontend/`)
- [ ] docker-compose.yml (MySQL + backend + reverb + frontend)
- [ ] Các trang: đăng nhập/đăng ký, lobby, bảng xếp hạng, bàn cờ

## Chạy dự án (sau khi hoàn thiện)

```bash
docker compose up
```

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000
- Reverb (WebSocket): ws://localhost:6001
- MySQL: localhost:3306
