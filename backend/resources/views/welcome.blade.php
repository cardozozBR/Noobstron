<!DOCTYPE html>

<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>{{ __('ui.app_name') }}</title>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        min-height: 100vh;
        font-family: Arial, Helvetica, sans-serif;
        background: #f5f7fb;
        color: #1f2937;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .container {
        width: min(900px, 92%);
        background: white;
        border-radius: 16px;
        padding: 48px;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .logo {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .subtitle {
        color: #6b7280;
        font-size: 18px;
        margin-bottom: 36px;
    }

    .status {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 30px;
    }

    h1 {
        font-size: 28px;
        margin: 0 0 16px;
    }

    p {
        line-height: 1.6;
        color: #6b7280;
        margin: 0;
    }
</style>


</head>

<body>
    <main class="container">
        <div class="logo">{{ __('ui.app_name') }}</div>


    <div class="status">
        Plataforma online
    </div>

    <h1>Bem-vindo!</h1>

    <div class="subtitle">
        Seu ambiente está configurado e funcionando.
    </div>

    <p>
        O sistema identificou corretamente este tenant
        e a conexão com o banco de dados está ativa.
    </p>
</main>


</body>
</html>
