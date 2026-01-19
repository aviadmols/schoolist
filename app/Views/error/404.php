<?php
/**
 * 404 Not Found Error Template
 */
$title = '404 - דף לא נמצא';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f7fa;
            font-family: system-ui, -apple-system, sans-serif;
            text-align: center;
            margin: 0;
        }
        .error-container {
            padding: 2rem;
            max-width: 500px;
        }
        h1 {
            font-size: 6rem;
            margin: 0;
            color: #0c4a6e;
            line-height: 1;
        }
        h2 {
            font-size: 1.5rem;
            color: #334155;
            margin: 1rem 0;
        }
        p {
            color: #64748b;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background: #0c4a6e;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <h2>אופס! הדף שחיפשת לא נמצא</h2>
        <p>ייתכן שהכתובת שגויה או שהדף הוסר מהמערכת.</p>
        <a href="/" class="btn">חזרה לדף הבית</a>
    </div>
</body>
</html>
