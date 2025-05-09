<?php
/**
 * Admin Panel for Chollo Glam - Collections Management
 * 
 * This file provides the interface for managing collections.
 */

// Define ADMIN_ACCESS constant to control access to included files
define('ADMIN_ACCESS', true);

// Start session for authentication
session_start();

// Include database connection
$conn = require_once 'db_config.php';

// Authentication check
// Uncomment this section when ready to implement proper authentication
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

// Function to safely escape values for SQL queries
function escape($conn, $value)
{
    if ($value === null) {
        return 'NULL';
    }
    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

// Handle collection actions (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission based on action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create' || $action === 'update') {
        // Get form data
        $nombre = escape($conn, $_POST['nombre']);
        $descripcion = escape($conn, $_POST['descripcion']);

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

        if ($action === 'create') {
            // Insert new collection
            $imagen_value = !empty($imagen) ? escape($conn, $imagen) : 'NULL';

            $sql = "INSERT INTO colecciones (nombre, imagen, descripcion) 
                    VALUES ($nombre, $imagen_value, $descripcion)";

            if (mysqli_query($conn, $sql)) {
                $message = "Colección creada con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        } else if ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];

            // Update existing collection
            if (!empty($imagen)) {
                $imagen_clause = "imagen = " . escape($conn, $imagen) . ",";
            } else if (!$keep_existing_image) {
                $imagen_clause = "imagen = NULL,";
            } else {
                $imagen_clause = "";
            }

            $sql = "UPDATE colecciones SET 
                    nombre = $nombre, 
                    $imagen_clause
                    descripcion = $descripcion
                    WHERE id = $id";

            if (mysqli_query($conn, $sql)) {
                $message = "Colección actualizada con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    } else if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];

        // Check if there are products using this collection
        $check_products_sql = "SELECT COUNT(*) as count FROM productos WHERE coleccion_id = $id";
        $check_result = mysqli_query($conn, $check_products_sql);
        $check_data = mysqli_fetch_assoc($check_result);
        
        if ($check_data['count'] > 0) {
            // Update products to remove the collection reference
            $update_products_sql = "UPDATE productos SET coleccion_id = NULL WHERE coleccion_id = $id";
            mysqli_query($conn, $update_products_sql);
        }

        // Delete collection
        $sql = "DELETE FROM colecciones WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            $message = "Colección eliminada con éxito.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch all collections
$sql = "SELECT * FROM colecciones ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
$colecciones = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get product count for each collection
foreach ($colecciones as $key => $coleccion) {
    $collection_id = (int) $coleccion['id'];
    $count_sql = "SELECT COUNT(*) as count FROM productos WHERE coleccion_id = $collection_id";
    $count_result = mysqli_query($conn, $count_sql);
    $count_data = mysqli_fetch_assoc($count_result);
    $colecciones[$key]['product_count'] = $count_data['count'];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Colecciones - Chollo Glam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons .btn {
            margin-right: 5px;
        }

        .table img {
            max-height: 50px;
            object-fit: contain;
        }

        .form-label.required:after {
            content: " *";
            color: red;
        }

        #imagePreview {
            max-height: 150px;
            object-fit: contain;
        }

        .nav-link.active {
            font-weight: bold;
        }

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .table th {
            position: sticky;
            top: 0;
            background-color: #212529;
        }

        .dashboard-stats {
            margin-bottom: 20px;
        }

        .dashboard-stats .card {
            transition: transform 0.2s;
        }

        .dashboard-stats .card:hover {
            transform: translateY(-5px);
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

        .collection-card {
            height: 100%;
            transition: transform 0.2s;
        }

        .collection-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .collection-img {
            height: 180px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <!-- Dashboard Stats -->
        <div class="row dashboard-stats">
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Colecciones</h6>
                                <h2 class="mb-0"><?php echo count($colecciones); ?></h2>
                            </div>
                            <i class="fas fa-layer-group fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Productos en Colecciones</h6>
                                <h2 class="mb-0"><?php
                                $total_products = 0;
                                foreach ($colecciones as $coleccion) {
                                    $total_products += $coleccion['product_count'];
                                }
                                echo $total_products;
                                ?></h2>
                            </div>
                            <i class="fas fa-cubes fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Colecciones Nuevas</h6>
                                <h2 class="mb-0"><?php
                                $new_collections = 0;
                                $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
                                foreach ($colecciones as $coleccion) {
                                    if ($coleccion['fecha_creacion'] > $thirty_days_ago) {
                                        $new_collections++;
                                    }
                                }
                                echo $new_collections;
                                ?></h2>
                            </div>
                            <i class="fas fa-certificate fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestionar Colecciones</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#collectionModal">
                <i class="fas fa-plus me-1"></i> Nueva Colección
            </button>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Collections Grid View -->
        <div class="row mb-4">
            <?php if (empty($colecciones)): ?>
                <div class="col-12">
                    <div class="card shadow-sm p-4 text-center">
                        <i class="fas fa-layer-group fa-3x mb-3 text-muted"></i>
                        <h3 class="text-muted">No hay colecciones disponibles</h3>
                        <p>Crea una nueva colección para comenzar</p>
                        <button class="btn btn-primary mx-auto" style="width: fit-content;" data-bs-toggle="modal"
                            data-bs-target="#collectionModal">
                            <i class="fas fa-plus me-1"></i> Nueva Colección
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($colecciones as $coleccion): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm collection-card">
                            <?php if (!empty($coleccion['imagen'])): ?>
                                <img src="../<?php echo $coleccion['imagen']; ?>" alt="<?php echo $coleccion['nombre']; ?>"
                                    class="card-img-top collection-img">
                            <?php else: ?>
                                <div class="card-img-top collection-img bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $coleccion['nombre']; ?></h5>
                                <p class="card-text text-muted">
                                    <?php echo !empty($coleccion['descripcion']) ?
                                        (strlen($coleccion['descripcion']) > 100 ?
                                            substr($coleccion['descripcion'], 0, 100) . '...' :
                                            $coleccion['descripcion']) :
                                        'Sin descripción'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-info text-dark">
                                        <i class="fas fa-cubes me-1"></i> <?php echo $coleccion['product_count']; ?> productos
                                    </span>
                                    <small class="text-muted">ID: <?php echo $coleccion['id']; ?></small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">Creada:
                                        <?php echo date('d/m/Y', strtotime($coleccion['fecha_creacion'])); ?></small>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $coleccion['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn"
                                            data-id="<?php echo $coleccion['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($coleccion['nombre']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- List View -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Colecciones</h5>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-secondary active">
                        <i class="fas fa-list me-1"></i> Lista
                    </button>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-th-large me-1"></i> Grid
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Productos</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($colecciones)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-layer-group fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0">No hay colecciones disponibles</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($colecciones as $coleccion): ?>
                                    <tr>
                                        <td><?php echo $coleccion['id']; ?></td>
                                        <td>
                                            <?php if (!empty($coleccion['imagen'])): ?>
                                                <img src="../<?php echo $coleccion['imagen']; ?>"
                                                    alt="<?php echo $coleccion['nombre']; ?>" class="img-thumbnail">
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-image"></i> Sin imagen</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $coleccion['nombre']; ?></td>
                                        <td>
                                            <?php echo !empty($coleccion['descripcion']) ?
                                                (strlen($coleccion['descripcion']) > 50 ?
                                                    substr($coleccion['descripcion'], 0, 50) . '...' :
                                                    $coleccion['descripcion']) :
                                                'Sin descripción'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?php echo $coleccion['product_count']; ?> productos
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($coleccion['fecha_creacion'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-info edit-btn"
                                                data-id="<?php echo $coleccion['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?php echo $coleccion['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($coleccion['nombre']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Modal -->
    <div class="modal fade" id="collectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Crear Nueva Colección</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data" id="collectionForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="collectionId">

                        <div class="mb-3">
                            <label for="nombre" class="form-label required">Nombre de la Colección</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4"></textarea>
                            <div class="form-text">Una descripción atractiva ayudará a los clientes a entender el tema y
                                estilo de la colección.</div>
                        </div>

                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen de la Colección</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                            <div class="form-text">Se recomienda una imagen de alta calidad con formato 16:9 o cuadrado.
                            </div>

                            <div id="currentImageContainer" class="mt-2 d-none">
                                <div class="card p-2 bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="img-upload-preview me-3">
                                            <img id="currentImage" class="img-thumbnail" style="max-height: 100px;">
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

                            <div id="imagePreviewContainer" class="mt-2 d-none">
                                <p class="mb-1">Vista previa:</p>
                                <img id="imagePreview" class="img-thumbnail">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><i class="fas fa-exclamation-triangle text-warning me-2"></i> ¿Estás seguro de que deseas
                        eliminar la colección <strong id="deleteCollectionName"></strong>?</p>
                    <p class="text-danger mb-0">Esta acción no se puede deshacer y podría afectar a los productos
                        asociados a esta colección.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Eliminar
                        </button>
                    </form>
                </div>
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

            // Manejar botones de eliminación
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const collectionId = this.getAttribute('data-id');
                    const collectionName = this.getAttribute('data-name');
                    document.getElementById('deleteId').value = collectionId;
                    document.getElementById('deleteCollectionName').textContent = collectionName;
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });

            // Resetear formulario al abrir modal para crear nueva colección
            const collectionModal = document.getElementById('collectionModal');
            collectionModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                // Si no es un botón de editar, resetear el formulario (es una nueva colección)
                if (!button || !button.classList.contains('edit-btn')) {
                    resetForm();
                }
            });

            // Manejar botones de edición
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const collectionId = this.getAttribute('data-id');
                    
                    // En una implementación real, harías una solicitud AJAX para obtener los datos
                    // Aquí simularemos el comportamiento para editar la colección seleccionada
                    
                    // Buscar los datos en la página actual (esto es simplificado)
                    const row = this.closest('tr');
                    if (row) {
                        const name = row.cells[2].textContent.trim();
                        const description = row.cells[3].textContent.trim() === 'Sin descripción' ? '' : row.cells[3].textContent.trim();
                        const imageEl = row.cells[1].querySelector('img');
                        const hasImage = imageEl !== null;
                        
                        // Rellenar el formulario
                        document.getElementById('modalTitle').textContent = 'Editar Colección';
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('collectionId').value = collectionId;
                        document.getElementById('nombre').value = name;
                        document.getElementById('descripcion').value = description;
                        
                        // Mostrar imagen actual si existe
                        if (hasImage) {
                            document.getElementById('currentImage').src = imageEl.src;
                            document.getElementById('currentImageContainer').classList.remove('d-none');
                        } else {
                            document.getElementById('currentImageContainer').classList.add('d-none');
                        }
                        
                        // Mostrar el modal
                        const modal = new bootstrap.Modal(document.getElementById('collectionModal'));
                        modal.show();
                    } else {
                        // Si no encontramos la fila, es posible que estemos en la vista de tarjetas
                        // Buscar la tarjeta correspondiente
                        const card = this.closest('.collection-card');
                        if (card) {
                            const name = card.querySelector('.card-title').textContent.trim();
                            const description = card.querySelector('.card-text').textContent.trim() === 'Sin descripción' ? '' : card.querySelector('.card-text').textContent.trim();
                            const imageEl = card.querySelector('.card-img-top');
                            const hasImage = imageEl && !imageEl.classList.contains('bg-light');
                            
                            // Rellenar el formulario
                            document.getElementById('modalTitle').textContent = 'Editar Colección';
                            document.getElementById('formAction').value = 'update';
                            document.getElementById('collectionId').value = collectionId;
                            document.getElementById('nombre').value = name;
                            document.getElementById('descripcion').value = description;
                            
                            // Mostrar imagen actual si existe
                            if (hasImage) {
                                document.getElementById('currentImage').src = imageEl.src;
                                document.getElementById('currentImageContainer').classList.remove('d-none');
                            } else {
                                document.getElementById('currentImageContainer').classList.add('d-none');
                            }
                            
                            // Mostrar el modal
                            const modal = new bootstrap.Modal(document.getElementById('collectionModal'));
                            modal.show();
                        }
                    }
                });
            });

            function resetForm() {
                document.getElementById('modalTitle').textContent = 'Crear Nueva Colección';
                document.getElementById('formAction').value = 'create';
                document.getElementById('collectionId').value = '';
                document.getElementById('collectionForm').reset();
                document.getElementById('imagePreviewContainer').classList.add('d-none');
                document.getElementById('currentImageContainer').classList.add('d-none');
            }
        });
    </script>
</body>
</html>