<?php
/**
 * Admin Panel for Chollo Glam - Testimonials Management
 * 
 * This file provides the interface for managing customer testimonials.
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

// Initialize variables
$error = '';
$success = '';
$categoria = [
    'id' => '',
    'nombre' => '',
    'descripcion' => '',
    'imagen' => '',
    'activa' => 1,
    'meta_titulo' => '',
    'meta_descripcion' => '',
    'url_amigable' => ''
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        // Get common fields
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activa = isset($_POST['activa']) ? 1 : 0;
        $meta_titulo = trim($_POST['meta_titulo'] ?? '');
        $meta_descripcion = trim($_POST['meta_descripcion'] ?? '');
        $url_amigable = trim($_POST['url_amigable'] ?? '');

        // Generate URL amigable if not provided
        if (empty($url_amigable) && !empty($nombre)) {
            $url_amigable = generar_url_amigable($nombre);
        }

        // Handle image upload
        $imagen = $_POST['imagen_actual'] ?? null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['size'] > 0) {
            $resultado_upload = subir_imagen('imagen', 'categorias');
            if (isset($resultado_upload['error'])) {
                $error = $resultado_upload['error'];
            } else {
                $imagen = $resultado_upload['ruta'];
            }
        }

        switch ($_POST['action']) {
            case 'add':
                if (empty($nombre)) {
                    $error = "El nombre de la categoría es obligatorio";
                } else {
                    // Check if category already exists
                    $check_stmt = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
                    $check_stmt->bind_param("s", $nombre);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $check = $check_result->fetch_assoc();
                    $check_stmt->close();

                    if ($check) {
                        $error = "Ya existe una categoría con ese nombre";
                    } else {
                        // Insert new category
                        $sql = "INSERT INTO categorias (nombre, descripcion, imagen, activa, meta_titulo, meta_descripcion, url_amigable) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssssss", $nombre, $descripcion, $imagen, $activa, $meta_titulo, $meta_descripcion, $url_amigable);
                        $stmt->execute();
                        $result = $stmt->affected_rows > 0;
                        $insert_id = $stmt->insert_id;
                        $stmt->close();

                        if ($result) {
                            $success = "Categoría añadida correctamente";
                            // Reset form
                            $categoria = [
                                'id' => '',
                                'nombre' => '',
                                'descripcion' => '',
                                'imagen' => '',
                                'activa' => 1,
                                'meta_titulo' => '',
                                'meta_descripcion' => '',
                                'url_amigable' => ''
                            ];
                        } else {
                            $error = "Error al añadir la categoría";
                        }
                    }
                }
                break;

            case 'edit':
                $id = intval($_POST['id']);

                if (empty($nombre)) {
                    $error = "El nombre de la categoría es obligatorio";
                } else {
                    // Check if name exists for a different category
                    $check_stmt = $conn->prepare("SELECT id FROM categorias WHERE nombre = ? AND id != ?");
                    $check_stmt->bind_param("si", $nombre, $id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $check = $check_result->fetch_assoc();
                    $check_stmt->close();

                    if ($check) {
                        $error = "Ya existe otra categoría con ese nombre";
                    } else {
                        // Update category
                        $sql = "UPDATE categorias SET 
                                nombre = ?, 
                                descripcion = ?, 
                                imagen = ?, 
                                activa = ?, 
                                meta_titulo = ?, 
                                meta_descripcion = ?, 
                                url_amigable = ? 
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param(
                            "sssisssi",
                            $nombre,
                            $descripcion,
                            $imagen,
                            $activa,
                            $meta_titulo,
                            $meta_descripcion,
                            $url_amigable,
                            $id
                        );
                        $stmt->execute();
                        $result = $stmt->affected_rows >= 0; // >= 0 because even if no row changed (identical data), it's still successful
                        $stmt->close();

                        if ($result !== false) {
                            $success = "Categoría actualizada correctamente";
                            // Reset form
                            $categoria = [
                                'id' => '',
                                'nombre' => '',
                                'descripcion' => '',
                                'imagen' => '',
                                'activa' => 1,
                                'meta_titulo' => '',
                                'meta_descripcion' => '',
                                'url_amigable' => ''
                            ];
                        } else {
                            $error = "Error al actualizar la categoría";
                        }
                    }
                }
                break;

            case 'delete':
                $id = intval($_POST['id']);

                // Check if category has products
                $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ?");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $productos = $check_result->fetch_assoc();
                $check_stmt->close();

                if ($productos && $productos['total'] > 0) {
                    $error = "No se puede eliminar la categoría porque tiene productos asociados";
                } else {
                    // Delete category
                    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->affected_rows > 0;
                    $stmt->close();

                    if ($result) {
                        $success = "Categoría eliminada correctamente";
                    } else {
                        $error = "Error al eliminar la categoría";
                    }
                }
                break;
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_category') {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $categoria = $result->fetch_assoc();
    $stmt->close();

    if ($categoria) {
        header('Content-Type: application/json');
        echo json_encode($categoria);
        exit;
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Categoría no encontrada']);
        exit;
    }
}

// Fetch all categories
$sql = "SELECT * FROM categorias ORDER BY nombre ASC";
$result = $conn->query($sql);
$categorias = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
}

// Function to generate URL friendly slug
function generar_url_amigable($texto)
{
    // Remove accents
    $texto = strtr($texto, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'Á' => 'a',
        'É' => 'e',
        'Í' => 'i',
        'Ó' => 'o',
        'Ú' => 'u',
        'ñ' => 'n',
        'Ñ' => 'n',
        'ü' => 'u',
        'Ü' => 'u'
    ]);

    // Convert to lowercase and replace spaces with hyphens
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9\-]/', '-', $texto);
    $texto = preg_replace('/-+/', '-', $texto);
    $texto = trim($texto, '-');

    return $texto;
}

// Function to handle image uploads
function subir_imagen($input_name, $subfolder = '')
{
    $target_dir = "uploads/";

    if (!empty($subfolder)) {
        $target_dir .= $subfolder . "/";
    }

    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $filename = time() . "_" . basename($_FILES[$input_name]["name"]);
    $target_file = $target_dir . $filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image
    $check = getimagesize($_FILES[$input_name]["tmp_name"]);
    if ($check === false) {
        return ['error' => "El archivo no es una imagen válida."];
    }

    // Check file size (limit to 2MB)
    if ($_FILES[$input_name]["size"] > 2000000) {
        return ['error' => "El archivo es demasiado grande. Máximo 2MB."];
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        return ['error' => "Solo se permiten archivos JPG, JPEG, PNG y GIF."];
    }

    if (move_uploaded_file($_FILES[$input_name]["tmp_name"], $target_file)) {
        return ['ruta' => $target_file];
    } else {
        return ['error' => "Error al subir el archivo."];
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestión de Categorías | Chollo Glam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar {
            height: 100vh;
            background-color: #212529;
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: #fff;
            margin-bottom: 5px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #343a40;
        }

        .main-content {
            padding: 20px;
        }

        .category-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }

        .form-check-input {
            cursor: pointer;
        }

        #imagePreview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="text-center mb-4">
                    <h4 class="text-white">Admin Panel</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="panel.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="panel.php">
                            <i class="bi bi-box-seam me-2"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="gestionar_categorias.php">
                            <i class="bi bi-tags me-2"></i> Categorías
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="coleccion.php">
                            <i class="bi bi-collection me-2"></i> Colecciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-cart me-2"></i> Pedidos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-people me-2"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-gear me-2"></i> Configuración
                        </a>
                    </li>
                    <li class="nav-item mt-5">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-tags me-2"></i> Gestión de Categorías</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#categoryModal">
                        <i class="bi bi-plus-circle me-2"></i> Nueva Categoría
                    </button>
                </div>

                <!-- Alerts -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>URL Amigable</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categorias)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No hay categorías registradas</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categorias as $cat): ?>
                                            <tr>
                                                <td><?php echo $cat['id']; ?></td>
                                                <td>
                                                    <?php if (!empty($cat['imagen'])): ?>
                                                        <img src="<?php echo $cat['imagen']; ?>" alt="<?php echo $cat['nombre']; ?>"
                                                            class="category-image">
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin imagen</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $cat['nombre']; ?></td>
                                                <td>
                                                    <?php if (!empty($cat['descripcion'])): ?>
                                                        <?php echo substr($cat['descripcion'], 0, 50) . (strlen($cat['descripcion']) > 50 ? '...' : ''); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin descripción</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($cat['activa']): ?>
                                                        <span class="badge bg-success">Activa</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactiva</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($cat['url_amigable'])): ?>
                                                        <?php echo $cat['url_amigable']; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No definida</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary edit-category"
                                                            data-id="<?php echo $cat['id']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger delete-category"
                                                            data-id="<?php echo $cat['id']; ?>"
                                                            data-nombre="<?php echo $cat['nombre']; ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
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
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="categoryForm" action="gestionar_categorias.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="categoria_id" value="">
                        <input type="hidden" name="imagen_actual" id="imagen_actual" value="">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                            <div id="imagePreviewContainer" class="mt-2 d-none">
                                <img id="imagePreview" src="" alt="Vista previa">
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="activa" name="activa" checked>
                            <label class="form-check-label" for="activa">
                                Categoría activa
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="url_amigable" class="form-label">URL Amigable</label>
                            <input type="text" class="form-control" id="url_amigable" name="url_amigable"
                                placeholder="Se generará automáticamente si se deja vacío">
                        </div>

                        <div class="mb-3">
                            <label for="meta_titulo" class="form-label">Meta Título (SEO)</label>
                            <input type="text" class="form-control" id="meta_titulo" name="meta_titulo">
                        </div>

                        <div class="mb-3">
                            <label for="meta_descripcion" class="form-label">Meta Descripción (SEO)</label>
                            <textarea class="form-control" id="meta_descripcion" name="meta_descripcion"
                                rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la categoría <strong id="categoryNameToDelete"></strong>?
                    </p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" action="gestionar_categorias.php" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId" value="">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

            // Handle edit button clicks
            document.querySelectorAll('.edit-category').forEach(button => {
                button.addEventListener('click', function () {
                    const categoryId = this.getAttribute('data-id');

                    // Reset form
                    document.getElementById('categoryForm').reset();
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('categoryModalLabel').textContent = 'Editar Categoría';

                    // Fetch category data
                    fetch(`gestionar_categorias.php?action=get_category&id=${categoryId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Populate form
                            document.getElementById('categoria_id').value = data.id;
                            document.getElementById('nombre').value = data.nombre;
                            document.getElementById('descripcion').value = data.descripcion;
                            document.getElementById('imagen_actual').value = data.imagen || '';
                            document.getElementById('activa').checked = data.activa == 1;
                            document.getElementById('url_amigable').value = data.url_amigable || '';
                            document.getElementById('meta_titulo').value = data.meta_titulo || '';
                            document.getElementById('meta_descripcion').value = data.meta_descripcion || '';

                            // Show image preview if available
                            const previewContainer = document.getElementById('imagePreviewContainer');
                            const imagePreview = document.getElementById('imagePreview');

                            if (data.imagen) {
                                imagePreview.src = data.imagen;
                                previewContainer.classList.remove('d-none');
                            } else {
                                previewContainer.classList.add('d-none');
                            }

                            // Show modal
                            categoryModal.show();
                        })
                        .catch(error => {
                            console.error('Error fetching category data:', error);
                            alert('Error al cargar los datos de la categoría');
                        });
                });
            });

            // Handle delete button clicks
            document.querySelectorAll('.delete-category').forEach(button => {
                button.addEventListener('click', function () {
                    const categoryId = this.getAttribute('data-id');
                    const categoryName = this.getAttribute('data-nombre');

                    document.getElementById('deleteId').value = categoryId;
                    document.getElementById('categoryNameToDelete').textContent = categoryName;

                    deleteModal.show();
                });
            });

            // Image preview functionality
            document.getElementById('imagen').addEventListener('change', function (event) {
                const previewContainer = document.getElementById('imagePreviewContainer');
                const imagePreview = document.getElementById('imagePreview');

                if (this.files && this.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        previewContainer.classList.remove('d-none');
                    }

                    reader.readAsDataURL(this.files[0]);
                } else {
                    previewContainer.classList.add('d-none');
                }
            });

            // Reset modal on new category
            document.querySelector('[data-bs-target="#categoryModal"]').addEventListener('click', function () {
                document.getElementById('categoryForm').reset();
                document.getElementById('formAction').value = 'add';
                document.getElementById('categoryModalLabel').textContent = 'Nueva Categoría';
                document.getElementById('categoria_id').value = '';
                document.getElementById('imagen_actual').value = '';
                document.getElementById('imagePreviewContainer').classList.add('d-none');
            });

            // Auto-generate URL amigable from name
            document.getElementById('nombre').addEventListener('blur', function () {
                const urlAmigable = document.getElementById('url_amigable');

                if (this.value && !urlAmigable.value) {
                    // Simple URL slug generator
                    let slug = this.value.toLowerCase()
                        .replace(/[áàäâ]/g, 'a')
                        .replace(/[éèëê]/g, 'e')
                        .replace(/[íìïî]/g, 'i')
                        .replace(/[óòöô]/g, 'o')
                        .replace(/[úùüû]/g, 'u')
                        .replace(/ñ/g, 'n')
                        .replace(/[^a-z0-9]/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');

                    urlAmigable.value = slug;
                }
            });
        });
    </script>
</body>

</html>