<?php
// Conexión a la base de datos
$conn = mysqli_connect("localhost", "usuario", "contraseña", "chollo_glam");

// Verificar conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}

// Verificar si se ha enviado el ID del producto
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Obtener información del producto para eliminar la imagen
    $query = "SELECT imagen FROM productos WHERE id = $id";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $imagen = $row['imagen'];

        // Eliminar la imagen del servidor si existe
        if ($imagen && file_exists("uploads/productos/" . $imagen)) {
            unlink("uploads/productos/" . $imagen);
        }

        // Eliminar el producto de la base de datos
        $sql = "DELETE FROM productos WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            echo "Producto eliminado correctamente.";
        } else {
            echo "Error al eliminar producto: " . mysqli_error($conn);
        }
    } else {
        echo "Producto no encontrado.";
    }
} else {
    echo "ID de producto no válido.";
}

echo "<br><a href='admin.php'>Volver al panel</a>";

mysqli_close($conn);
?>