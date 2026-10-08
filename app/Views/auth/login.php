<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar sesión | AppSig</title>
    <link rel="stylesheet" href="/css/login.css" />
</head>
<body>
    <main class="login-layout">
        <section class="login-card" aria-labelledby="login-title">
            <img class="login-logo" src="/image/logoCompleto.png" alt="Enecon" />
            <h1 id="login-title">Bienvenido a AppSig</h1>
            <p class="login-intro">Inicia sesión para continuar en AppSig.</p>
            <?php if ($error !== ''): ?>
                <p class="login-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post" action="/login" class="login-form">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" autocomplete="username" placeholder="usuario@enecon.net.co" required autofocus />
                <label for="password">Contraseña</label>
                <div class="password-field">
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Ingresa tu contraseña" required />
                    <button type="button" class="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <button class="login-submit" type="submit">Ingresar</button>
            </form>
        </section>
    </main>
    <script>
        document.querySelector('.password-toggle').addEventListener('click', event => {
            const input = document.querySelector('#password');
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            event.currentTarget.setAttribute('aria-pressed', String(visible));
            event.currentTarget.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    </script>
</body>
</html>
