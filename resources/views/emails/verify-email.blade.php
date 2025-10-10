<!DOCTYPE html>
<html>
<head>
    <title>Verificación de Email</title>
</head>
<body>
    <h1>Hola {{ $user->name }}!</h1>
    <p>Por favor verifica tu email haciendo clic en este enlace:</p>
    <a href="{{ $url }}">Verificar mi cuenta</a>
    
    <p>Si no creaste esta cuenta, ignora este mensaje.</p>
</body>
</html>