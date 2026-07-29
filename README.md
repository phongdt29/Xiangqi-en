# Cờ Tướng (Xiangqi) Online

Game cờ tướng chơi online 2 người, realtime, giao diện Bootstrap 5 hiện đại.

## Kiến trúc

- **Backend**: Laravel (PHP) — API + Eloquent/MySQL + Sanctum (auth) + Reverb (WebSocket realtime).
- **Frontend**: Next.js (App Router, JavaScript) — tiêu thụ API Laravel, nhận cập nhật realtime qua Laravel Echo.
- **Database**: MySQL — tài khoản người dùng, phòng chơi (đồng thời là lịch sử đấu), bảng xếp hạng.
- **Môi trường chạy**: Docker / docker-compose là mục tiêu cuối (không cần sudo, không cài trực tiếp). *Tạm thời* (2026-07-28): Docker Hub bị rate-limit pull ẩn danh nên đã cài PHP 8.4.23 local (`%LOCALAPPDATA%\Programs\php84`, ngoài PATH mặc định) để chạy `composer install`/`artisan` khi cần — XAMPP PHP 8.2 không đủ (Laravel 13/Reverb yêu cầu PHP ≥ 8.4). Sẽ quay lại thuần Docker khi viết `docker-compose.yml`.

Plan gốc (`~/.claude/plans/tranquil-kindling-nest.md`) đã mất; phần "Trạng thái hiện tại" bên dưới là nguồn tham chiếu chính. Plan cho đợt tính năng AI/Puzzle/Undo (2026-07-29) còn tại `~/.claude/plans/concurrent-puzzling-haven.md`, bao gồm cả Phase 4 (cờ úp) chưa triển khai.

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
- [x] AuthController (Sanctum bearer token: register/login/logout/me)
- [x] RoomController + Event `RoomUpdated` (tạo/join/đi quân qua Room, persist DB, realtime qua Reverb private channel `room.{id}`, auth Sanctum cho `/broadcasting/auth`)
- [x] Leaderboard API (`GET /api/leaderboard`, sort theo rating) + rating tự cập nhật kiểu ELO-nhẹ (K=32) khi ván kết thúc (checkmate/stalemate — thắng/thua, không hòa)
- [x] Next.js scaffold (`frontend/`) — App Router, JavaScript, Bootstrap 5, Navbar+Footer, responsive
- [x] **Bản hot-seat** (`/play` — 2 người 1 trình duyệt): gọi 3 API stateless của engine (`/api/xiangqi/new`, `/move`, `/legal-moves`), không cần DB/auth.
- [x] **Đấu online thật** (`/rooms` lobby + `/rooms/[id]`): tạo phòng, chia sẻ code, chơi realtime qua Reverb (laravel-echo + pusher-js), kết quả lưu vào `Room` + cập nhật rating/wins/losses. Đã verify end-to-end qua script (REST + WebSocket thật, không mock).
- [x] Trang Login/Register (Sanctum token, lưu localStorage), Leaderboard (dữ liệu thật), Rules (luật chơi cơ bản), Home (giới thiệu tính năng), Profile (thống kê + lịch sử đấu qua `GET /api/rooms/mine`)
- [x] Giao diện theo tông vàng-đỏ (đỏ = quân cờ, vàng = màu chủ đạo/nút bấm) + emoji icon, lấy cảm hứng từ danhcotuong.online (đã fetch tham khảo, không sao chép mã màu chính xác vì WebFetch chỉ đọc text)
- [x] Lịch sử nước đi (move history) hiển thị ở `/play` và `/rooms/[id]`
- [x] Âm thanh (Web Audio API tự tổng hợp, không cần file ngoài): đi quân/ăn quân/chiếu/kết thúc ván, có nút tắt/bật lưu localStorage
- [x] Đồng hồ cờ (5/10/15 phút hoặc không giới hạn khi tạo phòng): server tính giờ authoritative (trừ thời gian mỗi nước đi, phát hiện hết giờ qua `POST /rooms/{id}/claim-timeout`), rating cập nhật khi thắng/thua do hết giờ. Đã verify qua script (deduct đúng, từ chối claim sớm, xử lý timeout đúng).
- [x] **Chơi với AI** (`backend/app/Games/Xiangqi/Ai/`: `Evaluator` + `MinimaxAi` — negamax alpha-beta, iterative-deepening-safe qua time-limit, 3 độ khó Easy/Medium/Hard = depth 1/2/3). Endpoint stateless `POST /api/xiangqi/ai-move`. Chọn "vs Computer" + độ khó ngay trên `/play`, giờ nghĩ trừ vào đồng hồ của bên máy. 12 unit/feature test riêng cho AI.
- [x] **Puzzle** (`GET /api/puzzles`, `GET /api/puzzles/{id}`, trang `/puzzles` + `/puzzles/[id]`): 4 thế cờ chiếu-bí-trong-1-nước đã verify từng cái bằng script (không có thế nào "trông đúng nhưng sai" — đã bắt lỗi này nhiều lần lúc soạn). Ván nhiều nước dùng lại chính AI (độ Hard) làm đối thủ phản đòn, không cần soạn sẵn lời giải.
- [x] Undo (chỉ `/play`, hoàn tác cả lượt máy nếu đang chơi vs AI) + xem lại nước đi (bấm vào 1 nước trong lịch sử để xem lại thế cờ lúc đó, ở `/play` và puzzle) + `GET /api/rooms/{id}/replay` (dựng lại toàn bộ thế cờ theo từng nước từ lịch sử phòng online, đã verify qua script — chưa nối UI).
- [x] **Cờ Úp** (Jiéqí/hidden-pieces, `backend/app/Games/Xiangqi/CoUp/`, trang `/hidden-pieces`): luật đã xác minh từ nhiều nguồn độc lập trước khi code (Wikipedia tiếng Việt, zigavn.com, kyvuong.mobi, BoardGameGeek/chessprogramming.org). Tướng luôn lộ diện; 15 quân còn lại mỗi bên bị úp + xáo trộn trong chính phe đó; nước đầu của quân úp đi theo luật của ô đang đứng, sau đó lộ diện vĩnh viễn và đi theo đúng loại thật; Sĩ/Tượng sau khi lộ diện được thoát cung/qua sông tự do (luật đặc trưng của Cờ Úp). Server giữ toàn bộ trạng thái thật (bảng `co_up_games`), client chỉ nhận view đã che (quân úp không có trường `type`) — do đó **không thể tái dùng pattern stateless của `/play`** (client giữ state sẽ lộ hết bí mật). 17 unit test (che dấu, lộ diện, mở khoá Sĩ/Tượng, quân úp không lộ loại thật) + đã verify qua 2 script HTTP thật (che/lộ đúng theo từng nước, chơi 6 nước liên tiếp đúng số quân lộ diện).
- [ ] docker-compose.yml (MySQL + backend + reverb + frontend) — hiện chạy rời qua PHP local + `npm run dev`, xem hướng dẫn bên dưới

## Chạy thử ngay (chưa cần Docker)

```bash
# Terminal 1 - backend (cần PHP >= 8.4 trên PATH, xem ghi chú PHP local ở trên)
cd backend && php artisan serve --port=8000

# Terminal 2 - Reverb (bắt buộc cho /rooms realtime; /play hot-seat không cần)
cd backend && php artisan reverb:start

# Terminal 3 - frontend
cd frontend && npm run dev
```

Mở http://localhost:3000:
- `/play` — 2 người thay phiên bấm quân trên cùng 1 máy, hoặc chọn "vs Computer", không cần tài khoản.
- `/puzzles` — chiếu bí trong N nước, không cần tài khoản.
- `/hidden-pieces` — cờ úp (quân úp ngẫu nhiên), không cần tài khoản.
- `/rooms` — cần đăng ký/đăng nhập; tạo phòng, chia sẻ code cho người khác join, chơi realtime (mở 2 trình duyệt khác nhau/ẩn danh để thử 2 tài khoản).

`frontend/.env.local` chứa `NEXT_PUBLIC_API_URL` + `NEXT_PUBLIC_REVERB_*` (phải khớp `REVERB_APP_KEY`/host/port trong `backend/.env`).

## Chạy dự án (sau khi hoàn thiện docker-compose + DB + Reverb + auth)

```bash
docker compose up
```

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000
- Reverb (WebSocket): ws://localhost:6001
- MySQL: localhost:3306
