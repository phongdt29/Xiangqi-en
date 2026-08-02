# Cờ Tướng (Xiangqi) Online

Game cờ tướng chơi online 2 người, realtime, giao diện Bootstrap 5 hiện đại.

## Kiến trúc

- **Backend**: Laravel (PHP) — API + Eloquent/MySQL + Sanctum (auth). Reverb (WebSocket realtime) vẫn có trong code nhưng **không bật ở production** (xem mục Deploy) — hosting thật là CyberPanel chia sẻ, không chạy được daemon WebSocket tuỳ ý.
- **Frontend**: Next.js (App Router, JavaScript) — tiêu thụ API Laravel, build ra static export (`output: "export"`, không cần Node.js lúc chạy). `/rooms` cập nhật bằng polling API mỗi 2.5s thay vì Reverb/Echo (dev local vẫn có thể bật Reverb nếu muốn, nhưng trang không còn phụ thuộc vào nó).
- **Database**: MySQL — tài khoản người dùng, phòng chơi (đồng thời là lịch sử đấu), bảng xếp hạng.
- **Môi trường chạy dev**: PHP 8.4 local + `npm run dev`. **Production**: CyberPanel hosting có sẵn (domain `chinesechess.online`) — xem mục "Deploy production" bên dưới. `docker-compose.yml`/Dockerfile đã build và verify chạy thật, giữ lại làm phương án dự phòng nếu sau này nâng cấp lên VPS riêng, không phải đường đi hiện tại.

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
- [x] docker-compose.yml + Dockerfile (MySQL + backend + reverb + frontend) — đã build và verify chạy thật (kể cả build-time env bake của frontend), giữ làm phương án dự phòng cho VPS tương lai
- [x] **Deploy production trên CyberPanel hosting có sẵn** (domain `chinesechess.online`, DB `chin_chinesechess` qua `localhost`): không SSH/Docker → build sẵn ở local rồi upload; backend qua subdomain `api.chinesechess.online` (document root của website trỏ vào `public/` của app Laravel đã upload), frontend build static (Next.js `output: "export"`) upload thẳng vào document root của domain chính, không cần Node.js chạy nền; Reverb tạm bỏ, `/rooms` chuyển sang polling API — xem chi tiết từng bước ở mục "Deploy production" bên dưới

## Chạy thử ngay (chưa cần Docker)

```bash
# Terminal 1 - backend (cần PHP >= 8.4 trên PATH, xem ghi chú PHP local ở trên)
cd backend && php artisan serve --port=8000

# Terminal 2 - frontend
cd frontend && npm run dev
```

Mở http://localhost:3000:
- `/play` — 2 người thay phiên bấm quân trên cùng 1 máy, hoặc chọn "vs Computer", không cần tài khoản.
- `/puzzles` — chiếu bí trong N nước, không cần tài khoản.
- `/hidden-pieces` — cờ úp (quân úp ngẫu nhiên), không cần tài khoản.
- `/rooms` — cần đăng ký/đăng nhập; tạo phòng, chia sẻ code cho người khác join, chơi (poll mỗi 2.5s, mở 2 trình duyệt khác nhau/ẩn danh để thử 2 tài khoản).

`frontend/.env.local` chỉ cần `NEXT_PUBLIC_API_URL`. Reverb (`php artisan reverb:start`) không còn bắt buộc cho dev — `/rooms` đã chuyển sang polling.

## Deploy production: CyberPanel hosting có sẵn (chinesechess.online)

Hosting thật là **CyberPanel chia sẻ** (không SSH, không Docker) — MySQL chạy ngay trên máy đó qua `localhost`. Vì vậy **không dùng** `docker-compose.yml`/`Dockerfile` ở repo này để deploy (xem lý do giữ lại 2 file đó ở mục dưới). Quyết định quan trọng: **tạm bỏ Reverb/realtime** — `/rooms` giờ polling API mỗi 2.5s (`frontend/app/rooms/[id]/page.js`) thay vì WebSocket, nên không cần chạy tiến trình nền nào cả — phù hợp giới hạn shared hosting (không cho phép daemon cổng tuỳ ý). Quyết định thứ hai: frontend build **static export** (`next.config.mjs` → `output: "export"`) thay vì server Node.js — host không chạy Node.js lúc runtime, chỉ serve file tĩnh qua OpenLiteSpeed.

### 1. Build sẵn ở máy local rồi upload (vì CyberPanel không có SSH để chạy composer/npm)

Cả 2 gói build được đóng gói sẵn vào `deploy/backend-deploy.zip` và `deploy/frontend-deploy.zip` (thư mục `deploy/` bị gitignore vì chứa `.env` thật) — build lại khi có thay đổi code hoặc đổi cấu hình domain/env:

**Backend** (build trong 1 bản copy riêng, không đụng `backend/vendor` đang dùng cho dev — máy dev chỉ có PHP 8.2/8.3, thấp hơn yêu cầu `^8.3`/runtime 8.4 của Laravel 13, nên phải `--ignore-platform-reqs`; production host thật có PHP 8.4 nên vendor cài kiểu này vẫn chạy đúng):
```bash
# copy backend/ (trừ vendor, .env*, .phpunit.result.cache, database.sqlite) ra thư mục tạm
cd <thư mục tạm>
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
cp backend/.env.production .env   # đã có APP_URL=https://api.chinesechess.online thật
# nén toàn bộ thư mục tạm (đã có vendor/ + .env) thành deploy/backend-deploy.zip
```

**Frontend** (build với đúng biến `NEXT_PUBLIC_*` trong `frontend/.env.production.local`, biến này bị "bake" lúc build, không sửa được sau khi build xong — hiện là `NEXT_PUBLIC_API_URL=https://api.chinesechess.online`):
```bash
cd frontend
npm run build
```
`next.config.mjs` đã bật `output: "export"` + `trailingSlash: true` — build ra thư mục `out/` chứa HTML/CSS/JS tĩnh (`out/rooms/index.html` phục vụ `/rooms/` không cần rewrite rule gì thêm). Nén **nội dung bên trong** `out/` (không nén cả thư mục `out`) thành `deploy/frontend-deploy.zip`.

### 2. Backend (Laravel) trên CyberPanel

- Tạo subdomain/website `api.chinesechess.online` trong CyberPanel, giải nén `backend-deploy.zip` vào đó, rồi cấu hình **document root của website trỏ vào `public/`** bên trong app Laravel đã upload (không phải trỏ vào thư mục gốc app) — CyberPanel cho sửa vhost conf/document root qua trang quản lý website.
- Chọn PHP **8.4** cho website này (Laravel 13/Sanctum yêu cầu ≥ 8.4).
- `.env` (đổi tên từ `.env.production`, đã có `DB_HOST=localhost`, `BROADCAST_CONNECTION=log`, `APP_URL=https://api.chinesechess.online`) đã nằm sẵn trong zip ở bước 1.
- Chạy migration mà không cần SSH — route sẵn có `GET /deploy/migrate?token=...` (`backend/routes/web.php`, khoá bằng `DEPLOY_MIGRATE_TOKEN` trong `.env`): mở URL này 1 lần qua trình duyệt sau khi upload để chạy `migrate` + `db:seed`, xong thì xoá dòng `DEPLOY_MIGRATE_TOKEN` khỏi `.env` trên server để đóng lại (route tự trả 404 nếu thiếu token).
- `storage/` và `bootstrap/cache/` cần quyền ghi (cùng chủ sở hữu user trên CyberPanel thường đã đủ, không cần đổi 755/775 thủ công trừ khi lỗi permission).

### 3. Frontend (Next.js static) trên CyberPanel

- Giải nén nội dung `frontend-deploy.zip` thẳng vào document root của domain chính `chinesechess.online` (mặc định `public_html` của website đó trong CyberPanel).
- Không cần "Setup Node.js App", không cần cài `node_modules` trên host — toàn bộ đã là file tĩnh.

### 4. DNS + SSL

- 2 bản ghi DNS: `chinesechess.online` (domain chính) và `api.chinesechess.online` (subdomain/child domain).
- Dùng SSL Let's Encrypt tích hợp sẵn trong CyberPanel (mục "Issue SSL" ở trang quản lý từng website) cho cả 2 — không cần acme-companion.

### Phương án dự phòng cho VPS trong tương lai

`docker-compose.yml`, `backend/Dockerfile`, `frontend/Dockerfile`, `backend/docker/*` đã build và **verify chạy thật** (web + Reverb + frontend build-time env) — giữ nguyên trong repo làm phương án nếu sau này nâng cấp lên VPS riêng (có Docker + Reverb thật cho realtime), nhưng **không dùng** cho CyberPanel chia sẻ hiện tại vì không có quyền chạy Docker/daemon tuỳ ý.
