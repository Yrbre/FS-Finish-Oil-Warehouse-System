<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Gagal Menerbitkan Tanda Terima</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: #f8f9fa;
        }

        .box {
            max-width: 480px;
            padding: 2rem;
            background: #fff;
            border-radius: 6px;
            border-left: 4px solid #dc3545;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        h1 {
            font-size: 1.1rem;
            margin: 0 0 .75rem;
            color: #dc3545;
        }

        p {
            margin: 0 0 1rem;
            color: #333;
            line-height: 1.5;
        }

        button {
            padding: .5rem 1rem;
            border: 0;
            border-radius: 4px;
            background: #6c757d;
            color: #fff;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1>Gagal Menerbitkan Tanda Terima</h1>
        <p>{{ $message }}</p>
        <button onclick="window.close()">Tutup Tab Ini</button>
    </div>
</body>

</html>
