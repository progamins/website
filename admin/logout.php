<?php
// Iniciamos la sesión (necesario para poder destruirla)
session_start();

// Función para registrar el cierre de sesión (opcional)
function registrarCierreSesion()
{
    // Puedes personalizar esto para registrar información en logs
    if (isset($_SESSION['usuario'])) {
        $usuario = $_SESSION['usuario'];
        $fecha = date('Y-m-d H:i:s');
        // Aquí podrías guardar en un archivo de log o en base de datos
        // Ejemplo: file_put_contents('logs/sesiones.log', "Cierre de sesión: $usuario - $fecha\n", FILE_APPEND);
    }
}

// Registrar cierre (opcional)
registrarCierreSesion();

// Eliminar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destruir la sesión
session_destroy();

// Redirigir al usuario a la página de inicio o login
header("Location: login.php");
exit();
?>