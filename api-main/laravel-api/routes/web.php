<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $frontendUrl = env('FRONTEND_URL', 'http://127.0.0.1:5173');

    return response()->make(
        <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yono API</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #09111f 0%, #14243b 100%);
            color: #f5f7fb;
        }
        .card {
            width: min(680px, calc(100% - 32px));
            padding: 28px;
            border-radius: 18px;
            background: rgba(13, 24, 42, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.28);
        }
        a {
            color: #7dd3fc;
        }
        code {
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.14);
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Laravel backend is running.</h1>
        <p>This port is for the API/backend. Open the React frontend here:</p>
        <p><a href="{$frontendUrl}" target="_blank" rel="noreferrer">{$frontendUrl}</a></p>
        <p>API base URL: <code>http://127.0.0.1:8000/api</code></p>
    </main>
</body>
</html>
HTML,
        200,
        ['Content-Type' => 'text/html; charset=UTF-8']
    );
});
