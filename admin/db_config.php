<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'chollo_glam';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Error de conexion: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
