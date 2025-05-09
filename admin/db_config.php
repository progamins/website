<?php
/**
 * Database Connection Configuration
 * 
 * Esta archivo establece la conexión con la base de datos MySQL
 */

// Conexión a la base de datos
$host = 'localhost'; // o 127.0.0.1
$username = 'root';  // Tu nombre de usuario de la base de datos
$password = '';      // Tu contraseña de la base de datos
$database = 'chollo_glam';  // El nombre de tu base de datos

// Crear conexión
$conn = mysqli_connect($host, $username, $password, $database);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Establecer charset para evitar problemas con caracteres especiales
mysqli_set_charset($conn, "utf8mb4");

// Devolver la conexión
return $conn;
?>