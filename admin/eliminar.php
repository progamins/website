<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"]) && is_numeric($_POST["id"])) {
    $id = intval($_POST["id"]);
    $result = mysqli_query($conn, "SELECT imagen FROM productos WHERE id = " . $id);
    if ($row = mysqli_fetch_assoc($result)) {
        $imagen = $row["imagen"];
        if ($imagen && file_exists("../uploads/productos/" . $imagen)) {
            unlink("../uploads/productos/" . $imagen);
        }
        mysqli_query($conn, "DELETE FROM productos WHERE id = " . $id);
    }
}

header("Location: panel.php");
exit;
?>