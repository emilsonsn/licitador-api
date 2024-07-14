<!DOCTYPE html>
<html>
<head>
    <title>Recuperação de Senha</title>
</head>
<body>
    <h1>Recuperação de Senha</h1>
    <p>Você solicitou a recuperação de senha. Use o link abaixo para redefinir sua senha:</p>
    <p><a href="{{env('FRONT_URL') . '/password_recovery?code=' . $code }}">Clique aqui para recuperar sua senha</a></p>
    <p>Se você não solicitou essa recuperação, por favor ignore este e-mail.</p>
</body>
</html>
