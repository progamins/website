<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        .message {
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
        }

        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .error {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>

<body>
    <!-- Página de Login -->
    <div id="loginPage" class="container">
        <div class="login-container">
            <h1>Acceso Administrador</h1>
            <div id="loginMessage" class="message"></div>
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Iniciar Sesión</button>
            </form>
        </div>
    </div>

    <script>
        // Solo un usuario administrador
        const admin = {
            username: 'admin',
            password: 'admin123'
        };

        // Referencias a elementos DOM
        const loginForm = document.getElementById('loginForm');
        const loginMessage = document.getElementById('loginMessage');

        // Función para iniciar sesión
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            // Validar credenciales del administrador
            if (username === admin.username && password === admin.password) {
                // Mostrar mensaje de éxito
                loginMessage.textContent = 'Inicio de sesión exitoso. Redirigiendo...';
                loginMessage.className = 'message success';

                // Guardar en sesión que el admin está autenticado
                sessionStorage.setItem('adminAuthenticated', 'true');

                // Redirigir a index.php
                setTimeout(() => {
                    window.location.href = 'panel.php';
                }, 1000);
            } else {
                // Mostrar mensaje de error
                loginMessage.textContent = 'Usuario o contraseña incorrectos.';
                loginMessage.className = 'message error';
            }
        });
    </script>
</body>

</html>