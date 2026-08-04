<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Chinesechess Online API</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #2a1a12 0%, #14100c 70%);
            color: #f5e6c8;
        }
        .card {
            text-align: center;
            padding: 3rem 3.5rem;
            border-radius: 16px;
            background: #1c140e;
            border: 1px solid #7a4a1e;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        .piece {
            font-size: 3.5rem;
            line-height: 1;
            display: inline-block;
            width: 5rem;
            height: 5rem;
            border-radius: 50%;
            background: #f5e6c8;
            color: #b3222c;
            border: 3px solid #b3222c;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            letter-spacing: 0.02em;
        }
        p {
            margin: 0;
            color: #c9b28a;
            font-size: 0.95rem;
        }
        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #3fb950;
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="piece">帥</div>
        <h1>象棋 Chinesechess Online API</h1>
        <p><span class="dot"></span>Service is running</p>
    </div>
</body>
</html>
