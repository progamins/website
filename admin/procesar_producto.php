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
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
    $precio_actual = floatval($_POST['precio_actual']);
    $precio_original = !empty($_POST['precio_original']) ? floatval($_POST['precio_original']) : NULL;
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $etiqueta = !empty($_POST['etiqueta']) ? mysqli_real_escape_string($conn, $_POST['etiqueta']) : NULL;
    $stock = intval($_POST['stock']);
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $coleccion = !empty($_POST['coleccion']) ? mysqli_real_escape_string($conn, $_POST['coleccion']) : NULL;

    // Procesar la imagen
    $imagen = NULL;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES['imagen']['name'];
        $filetype = $_FILES['imagen']['type'];
        $filesize = $_FILES['imagen']['size'];

        // Verificar extensión del archivo
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            die("Error: Formato de archivo no permitido.");
        }

        // Verificar tamaño del archivo - máximo 5MB
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            die("Error: El tamaño del archivo supera el límite permitido (5MB).");
        }

        // Verificar tipo MIME
        if (in_array($filetype, $allowed)) {
            // Generar nombre único para evitar sobrescribir
            $new_filename = uniqid() . "." . $ext;
            $upload_dir = "uploads/productos/";

            // Crear directorio si no existe
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Mover archivo
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . $new_filename)) {
                $imagen = $new_filename;
            } else {
                die("Error: Hubo un problema al subir el archivo.");
            }
        } else {
            die("Error: Tipo de archivo no permitido.");
        }
    } else {
        die("Error: No se ha seleccionado ninguna imagen o ha ocurrido un error.");
    }

    // Insertar datos en la base de datos
    $sql = "INSERT INTO productos (nombre, categoria, precio_actual, precio_original, descripcion, etiqueta, imagen, stock, destacado, coleccion) 
            VALUES ('$nombre', '$categoria', $precio_actual, " . ($precio_original ? "$precio_original" : "NULL") . ", '$descripcion', " .
        ($etiqueta ? "'$etiqueta'" : "NULL") . ", '$imagen', $stock, $destacado, " . ($coleccion ? "'$coleccion'" : "NULL") . ")";

    if (mysqli_query($conn, $sql)) {
        echo "Producto guardado correctamente.";
        echo "<br><a href='admin.php'>Volver al panel</a>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>