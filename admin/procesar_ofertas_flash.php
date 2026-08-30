<?php
// Conexión a la base de datos
$conn = mysqli_connect("localhost", "usuario", "contraseña", "chollo_glam");

// Verificar conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $tiempo_fin = mysqli_real_escape_string($conn, $_POST['tiempo_fin']);

    // Verificar si se seleccionaron productos
    if (!isset($_POST['flash_productos']) || empty($_POST['flash_productos'])) {
        die("Error: Debes seleccionar al menos un producto para la oferta flash.");
    }

    $productos = $_POST['flash_productos'];

    // Iniciar transacción
    mysqli_begin_transaction($conn);

    try {
        // Insertar la oferta flash
        $sql = "INSERT INTO ofertas_flash (tiempo_fin) VALUES ('$tiempo_fin')";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error al crear la oferta flash: " . mysqli_error($conn));
        }

        // Obtener el ID de la oferta recién creada
        $oferta_id = mysqli_insert_id($conn);

        // Insertar los productos seleccionados en la oferta flash
        foreach ($productos as $producto_id) {
            $producto_id = intval($producto_id);
            $sql = "INSERT INTO productos_oferta_flash (oferta_id, producto_id) VALUES ($oferta_id, $producto_id)";

            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error al asociar el producto ID $producto_id a la oferta flash: " . mysqli_error($conn));
            }
        }

        // Confirmar transacción
        mysqli_commit($conn);

        echo "Oferta flash creada correctamente.";
        echo "<br><a href='panel.php'>Volver al panel</a>";

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conn);
        echo $e->getMessage();
    }
}

mysqli_close($conn);
?>