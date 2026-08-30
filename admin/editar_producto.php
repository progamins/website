<?php
/**
 * Editar Producto - Chollo Glam
 * 
 * This file provides the interface for editing existing products.
 */

// Define ADMIN_ACCESS constant to control access to included files
define('ADMIN_ACCESS', true);

// Start session for authentication
session_start();

// Include database connection
require_once 'db_config.php';

// Authentication check
// Uncomment this section when ready to implement proper authentication
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

// Check if we have a product ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: panel.php');
    exit;
}

$product_id = (int) $_GET['id'];

// Fetch product data
$sql = "SELECT * FROM productos WHERE id = $product_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    // Product not found, redirect to admin page
    header('Location: panel.php');
    exit;
}

$producto = mysqli_fetch_assoc($result);

// Fetch all collections for dropdown
$sql_collections = "SELECT * FROM colecciones ORDER BY nombre";
$result_collections = mysqli_query($conn, $sql_collections);
$colecciones = mysqli_fetch_all($result_collections, MYSQLI_ASSOC);

// Function to safely escape values for SQL queries
function escape($conn, $value)
{
    if ($value === null) {
        return 'NULL';
    }
    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $nombre = escape($conn, $_POST['nombre']);
    $categoria = escape($conn, $_POST['categoria']);
    $precio_actual = escape($conn, $_POST['precio_actual']);
    $precio_original = !empty($_POST['precio_original']) ? escape($conn, $_POST['precio_original']) : 'NULL';
    $descripcion = escape($conn, $_POST['descripcion']);
    $etiqueta = !empty($_POST['etiqueta']) ? escape($conn, $_POST['etiqueta']) : 'NULL';
    $stock = (int) $_POST['stock'];
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $coleccion = !empty($_POST['coleccion']) ? escape($conn, $_POST['coleccion']) : 'NULL';

    // Handle image upload
    $imagen = '';
    $keep_existing_image = isset($_POST['keep_existing_image']) ? (bool) $_POST['keep_existing_image'] : false;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $upload_dir = '../uploads/productos/';

        // Make sure directory exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['imagen']['name']));
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
            $imagen = 'uploads/productos/' . $filename;
        }
    }

    // Update existing product
    if (!empty($imagen)) {
        $imagen_clause = "imagen = " . escape($conn, $imagen) . ",";
    } else if (!$keep_existing_image) {
        $imagen_clause = "imagen = NULL,";
    } else {
        $imagen_clause = "";
    }

    $sql = "UPDATE productos SET 
            nombre = $nombre, 
            categoria = $categoria, 
            precio_actual = $precio_actual, 
            precio_original = $precio_original, 
            descripcion = $descripcion, 
            etiqueta = $etiqueta, 
            $imagen_clause
            stock = $stock, 
            destacado = $destacado, 
            coleccion = $coleccion 
            WHERE id = $product_id";

    if (mysqli_query($conn, $sql)) {
        // Successful update, redirect back to admin page with success message
        $_SESSION['message'] = "Producto actualizado con éxito.";
        header('Location: panel.php');
        exit;
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Chollo Glam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-label.required:after {
            content: " *";
            color: red;
        }

        #imagePreview {
            max-height: 150px;
            object-fit: contain;
        }

        .img-upload-preview {
            position: relative;
            display: inline-block;
        }

        .img-upload-preview .remove-img {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            text-align: center;
            line-height: 24px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="panel.php">
                <i class="fas fa-shopping-bag me-2"></i>Chollo Glam Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="panel.php">
                            <i class="fas fa-box me-1"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-layer-group me-1"></i> Colecciones
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Editar Producto</h1>
            <a href="panel.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="" method="post" enctype="multipart/form-data" id="productForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label required">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="categoria" class="form-label required">Categoría</label>
                            <input type="text" class="form-control" id="categoria" name="categoria"
                                value="<?php echo htmlspecialchars($producto['categoria']); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="precio_actual" class="form-label required">Precio Actual</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="precio_actual" name="precio_actual"
                                    step="0.01" min="0" value="<?php echo $producto['precio_actual']; ?>" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="precio_original" class="form-label">Precio Original</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="precio_original" name="precio_original"
                                    step="0.01" min="0" value="<?php echo $producto['precio_original']; ?>">
                                <span class="input-group-text">€</span>
                            </div>
                            <div class="form-text">Dejar vacío si no hay descuento</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="stock" class="form-label required">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0"
                                value="<?php echo $producto['stock']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="etiqueta" class="form-label">Etiqueta</label>
                            <input type="text" class="form-control" id="etiqueta" name="etiqueta"
                                value="<?php echo htmlspecialchars($producto['etiqueta'] ?? ''); ?>">
                            <div class="form-text">Por ejemplo: Nuevo, Oferta, Limitado...</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="coleccion" class="form-label">Colección</label>
                            <select class="form-select" id="coleccion" name="coleccion">
                                <option value="">Ninguna</option>
                                <?php foreach ($colecciones as $coleccion): ?>
                                    <option value="<?php echo $coleccion['nombre']; ?>" <?php echo ($producto['coleccion'] == $coleccion['nombre']) ? 'selected' : ''; ?>>
                                        <?php echo $coleccion['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destacado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="destacado" name="destacado" <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="destacado">Marcar como producto destacado</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion"
                            rows="4"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen del Producto</label>
                        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">

                        <?php if (!empty($producto['imagen'])): ?>
                            <div id="currentImageContainer" class="mt-2">
                                <div class="card p-2 bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="img-upload-preview me-3">
                                            <img id="currentImage" src="../<?php echo $producto['imagen']; ?>"
                                                class="img-thumbnail" style="max-height: 100px;">
                                            <span class="remove-img" title="Eliminar imagen"><i
                                                    class="fas fa-times"></i></span>
                                        </div>
                                        <div>
                                            <p class="mb-1">Imagen actual</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="keep_existing_image"
                                                    name="keep_existing_image" value="1" checked>
                                                <label class="form-check-label" for="keep_existing_image">
                                                    Mantener imagen actual
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="imagePreviewContainer" class="mt-2 d-none">
                            <p class="mb-1">Vista previa:</p>
                            <img id="imagePreview" class="img-thumbnail">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="panel.php" class="btn btn-secondary me-2">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Vista previa de imagen cargada
            document.getElementById('imagen').addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        document.getElementById('imagePreview').src = e.target.result;
                        document.getElementById('imagePreviewContainer').classList.remove('d-none');
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Manejar eliminar imagen actual
            const removeImgBtn = document.querySelector('.remove-img');
            if (removeImgBtn) {
                removeImgBtn.addEventListener('click', function () {
                    document.getElementById('keep_existing_image').checked = false;
                    document.getElementById('currentImageContainer').classList.add('d-none');
                });
            }
        });
    </script>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; <?php echo date('Y'); ?> Chollo Glam - Panel de Administración
            </p>
        </div>
    </footer>
</body>

</html>