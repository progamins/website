<?php
/**
 * Instagram Feed Management for Chollo Glam
 * 
 * This file provides the admin interface for managing Instagram feed posts
 * that appear on the website.
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

// Handle Instagram post actions (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission based on action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create' || $action === 'update') {
        // Get form data
        $url = escape($conn, $_POST['url']);

        // Handle image upload
        $imagen = '';
        $keep_existing_image = isset($_POST['keep_existing_image']) ? (bool) $_POST['keep_existing_image'] : false;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $upload_dir = '../uploads/instagram/';

            // Make sure directory exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['imagen']['name']));
            $target = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                $imagen = 'uploads/instagram/' . $filename;
            }
        }

        if ($action === 'create') {
            // Insert new Instagram post
            $imagen_value = !empty($imagen) ? escape($conn, $imagen) : 'NULL';

            $sql = "INSERT INTO instagram_feed (imagen, url) 
                    VALUES ($imagen_value, $url)";

            if (mysqli_query($conn, $sql)) {
                $message = "Post de Instagram añadido con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        } else if ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];

            // Update existing Instagram post
            if (!empty($imagen)) {
                $imagen_clause = "imagen = " . escape($conn, $imagen) . ",";
            } else if (!$keep_existing_image) {
                $imagen_clause = "imagen = NULL,";
            } else {
                $imagen_clause = "";
            }

            $sql = "UPDATE instagram_feed SET 
                    $imagen_clause
                    url = $url
                    WHERE id = $id";

            if (mysqli_query($conn, $sql)) {
                $message = "Post de Instagram actualizado con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    } else if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];

        // Delete Instagram post
        $sql = "DELETE FROM instagram_feed WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            $message = "Post de Instagram eliminado con éxito.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch all Instagram posts
$sql = "SELECT * FROM instagram_feed ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
$instagram_posts = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Feed - Chollo Glam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons .btn {
            margin-right: 5px;
        }

        .table img {
            max-height: 100px;
            max-width: 100px;
            object-fit: cover;
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

        .instagram-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .instagram-item {
            position: relative;
            aspect-ratio: 1/1;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .instagram-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .instagram-item:hover img {
            transform: scale(1.05);
        }

        .instagram-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            justify-content: space-between;
        }

        .instagram-item:hover .instagram-overlay {
            opacity: 1;
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
            <a class="navbar-brand" href="#">
                <i class="fas fa-shopping-bag me-2"></i>Chollo Glam Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="panel.php">
                            <i class="fas fa-box me-1"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="coleccion.php">
                            <i class="fas fa-layer-group me-1"></i> Colecciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="oferta_flash.php">
                            <i class="fas fa-bolt me-1"></i> Ofertas Flash
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="testimonio.php">
                            <i class="fas fa-comment me-1"></i> Testimonios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="instagram.php">
                            <i class="fab fa-instagram me-1"></i> Instagram Feed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="suscriptores.php">
                            <i class="fas fa-envelope me-1"></i> Suscriptores
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="configuracion.php">
                            <i class="fas fa-cog me-1"></i> Configuración
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fab fa-instagram me-2"></i> Instagram Feed</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#instagramModal">
                <i class="fas fa-plus me-1"></i> Añadir Post
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

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Vista Previa</h5>
            </div>
            <div class="card-body">
                <?php if (empty($instagram_posts)): ?>
                    <div class="text-center py-5">
                        <i class="fab fa-instagram fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">No hay posts de Instagram disponibles</p>
                        <p class="text-muted">Añade posts para mostrarlos en la web</p>
                    </div>
                <?php else: ?>
                    <div class="instagram-grid">
                        <?php foreach ($instagram_posts as $post): ?>
                            <div class="instagram-item">
                                <img src="../<?php echo $post['imagen']; ?>" alt="Instagram post">
                                <div class="instagram-overlay">
                                    <a href="<?php echo $post['url']; ?>" class="btn btn-sm btn-light" target="_blank">
                                        <i class="fab fa-instagram"></i> Ver
                                    </a>
                                    <div>
                                        <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $post['id']; ?>"
                                            data-url="<?php echo $post['url']; ?>"
                                            data-imagen="../<?php echo $post['imagen']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Lista de Posts</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>URL</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($instagram_posts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fab fa-instagram fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0">No hay posts de Instagram disponibles</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($instagram_posts as $post): ?>
                                    <tr>
                                        <td><?php echo $post['id']; ?></td>
                                        <td>
                                            <img src="../<?php echo $post['imagen']; ?>" alt="Instagram post"
                                                class="img-thumbnail">
                                        </td>
                                        <td>
                                            <a href="<?php echo $post['url']; ?>" target="_blank"
                                                class="text-truncate d-inline-block" style="max-width: 250px;">
                                                <?php echo $post['url']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($post['fecha_creacion'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $post['id']; ?>"
                                                data-url="<?php echo $post['url']; ?>"
                                                data-imagen="../<?php echo $post['imagen']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?php echo $post['id']; ?>">
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

    <!-- Instagram Modal -->
    <div class="modal fade" id="instagramModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Añadir Post de Instagram</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data" id="instagramForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="postId">

                        <div class="mb-3">
                            <label for="url" class="form-label required">URL del Post</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                <input type="url" class="form-control" id="url" name="url"
                                    placeholder="https://www.instagram.com/p/..." required>
                            </div>
                            <div class="form-text">Ingresa la URL completa del post de Instagram</div>
                        </div>

                        <div class="mb-3">
                            <label for="imagen" class="form-label required">Imagen del Post</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" required>
                            <div class="form-text">Sube una imagen de buena calidad en formato cuadrado</div>

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
                        eliminar este post de Instagram?</p>
                    <p class="text-danger mb-0">Esta acción no se puede deshacer.</p>
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

                        // Si estamos editando, el atributo required ya no es necesario
                        if (document.getElementById('formAction').value === 'update') {
                            document.getElementById('imagen').removeAttribute('required');
                        }
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
                    document.getElementById('imagen').setAttribute('required', 'required');
                });
            }

            // Manejar botones de eliminación
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const postId = this.getAttribute('data-id');
                    document.getElementById('deleteId').value = postId;
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });

            // Manejar botones de edición
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const postId = this.getAttribute('data-id');
                    const url = this.getAttribute('data-url');
                    const imagen = this.getAttribute('data-imagen');

                    document.getElementById('modalTitle').textContent = 'Editar Post de Instagram';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('postId').value = postId;
                    document.getElementById('url').value = url;

                    // Mostrar imagen actual
                    document.getElementById('currentImage').src = imagen;
                    document.getElementById('currentImageContainer').classList.remove('d-none');

                    // Al editar, la imagen no es obligatoria si se mantiene la actual
                    document.getElementById('imagen').removeAttribute('required');

                    const instagramModal = new bootstrap.Modal(document.getElementById('instagramModal'));
                    instagramModal.show();
                });
            });

            // Resetear formulario al abrir modal para crear nuevo post
            const instagramModal = document.getElementById('instagramModal');
            instagramModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                // Si no es un botón de editar, resetear el formulario (es un nuevo post)
                if (!button || !button.classList.contains('edit-btn')) {
                    resetForm();
                }
            });

            function resetForm() {
                document.getElementById('modalTitle').textContent = 'Añadir Post de Instagram';
                document.getElementById('formAction').value = 'create';
                document.getElementById('postId').value = '';
                document.getElementById('instagramForm').reset();
                document.getElementById('imagePreviewContainer').classList.add('d-none');
                document.getElementById('currentImageContainer').classList.add('d-none');
                document.getElementById('imagen').setAttribute('required', 'required');
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