<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de expiração de documento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #0044cc;
            font-size: 24px;
        }
        p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h1>Alerta de expiração de documento</h1>
        <p>Olá,</p>
        <p>O documento <strong>{{ $documentName }}</strong> expira hoje.</p>
        <p>Acesse sua conta para verificar os detalhes e atualizar a documentação, se necessário.</p>
        <div class="footer">
            <p>© {{ date('Y') }} Localizadordeeditais. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
