# Cờ Tướng (Xiangqi) Online

Game cờ tướng chơi online 2 người, realtime, giao diện Bootstrap 5 hiện đại.

## Kiến trúc

- **Backend**: Laravel (PHP) — API + Eloquent/MySQL + Sanctum (auth) + Reverb (WebSocket realtime).
- **Frontend**: Next.js (App Router, JavaScript) — tiêu thụ API Laravel, nhận cập nhật realtime qua Laravel Echo.
- **Database**: MySQL — tài khoản người dùng, phòng chơi (đồng thời là lịch sử đấu), bảng xếp hạng.
- **Môi trường chạy**: Docker / docker-compose (máy dev chưa cài PHP/Composer/Node/MySQL trực tiếp, nên toàn bộ chạy qua container — không cần sudo).

Chi tiết thiết kế đầy đủ (lý do các quyết định kiến trúc, sơ đồ thư mục, API/events, thứ tự triển khai) nằm trong plan đã duyệt tại:
`~/.claude/plans/tranquil-kindling-nest.md`

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
- [ ] Reverb (đang cài)
- [ ] Game engine luật cờ (PHP) — `backend/app/Games/Xiangqi/`
- [ ] Migrations + Models (User, Room)
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
