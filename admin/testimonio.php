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

// Handle testimonial actions (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission based on action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create' || $action === 'update') {
        // Get form data
        $nombre_cliente = escape($conn, $_POST['nombre_cliente']);
        $comentario = escape($conn, $_POST['comentario']);
        $valoracion = (int) $_POST['valoracion'];
        $fecha = escape($conn, $_POST['fecha']);

        // Handle image upload
        $foto_cliente = '';
        $keep_existing_image = isset($_POST['keep_existing_image']) ? (bool) $_POST['keep_existing_image'] : false;

        if (isset($_FILES['foto_cliente']) && $_FILES['foto_cliente']['error'] === 0) {
            $upload_dir = '../uploads/productos/';

            // Make sure directory exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['foto_cliente']['name']));
            $target = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['foto_cliente']['tmp_name'], $target)) {
                $foto_cliente = 'uploads/productos/' . $filename;
            }
        }

        if ($action === 'create') {
            // Insert new testimonial
            $foto_cliente_value = !empty($foto_cliente) ? escape($conn, $foto_cliente) : 'NULL';

            $sql = "INSERT INTO testimonios (nombre_cliente, comentario, valoracion, fecha, foto_cliente) 
                    VALUES ($nombre_cliente, $comentario, $valoracion, $fecha, $foto_cliente_value)";

            if (mysqli_query($conn, $sql)) {
                $message = "Testimonio creado con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        } else if ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];

            // Update existing testimonial
            if (!empty($foto_cliente)) {
                $foto_cliente_clause = "foto_cliente = " . escape($conn, $foto_cliente) . ",";
            } else if (!$keep_existing_image) {
                $foto_cliente_clause = "foto_cliente = NULL,";
            } else {
                $foto_cliente_clause = "";
            }

            $sql = "UPDATE testimonios SET 
                    nombre_cliente = $nombre_cliente, 
                    comentario = $comentario, 
                    valoracion = $valoracion, 
                    fecha = $fecha,
                    $foto_cliente_clause
                    fecha_creacion = CURRENT_TIMESTAMP
                    WHERE id = $id";

            if (mysqli_query($conn, $sql)) {
                $message = "Testimonio actualizado con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    } else if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];

        // Delete testimonial
        $sql = "DELETE FROM testimonios WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            $message = "Testimonio eliminado con éxito.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch all testimonials
$sql = "SELECT * FROM testimonios ORDER BY fecha DESC";
$result = mysqli_query($conn, $sql);
$testimonios = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calcular valoración promedio
$valoracion_promedio = 0;
if (!empty($testimonios)) {
    $suma_valoraciones = 0;
    foreach ($testimonios as $testimonio) {
        $suma_valoraciones += $testimonio['valoracion'];
    }
    $valoracion_promedio = $suma_valoraciones / count($testimonios);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Testimonios - Chollo Glam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons .btn {
            margin-right: 5px;
        }

        .table img {
            max-height: 50px;
            object-fit: cover;
            border-radius: 50%;
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

        .testimonial-card {
            height: 100%;
            transition: transform 0.2s;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .client-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .client-img-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .star-rating {
            color: #ffc107;
            margin-bottom: 10px;
        }

        .stars-container {
            display: inline-block;
        }

        .stars-container input[type="radio"] {
            display: none;
        }

        .stars-container label {
            float: right;
            cursor: pointer;
            color: #ccc;
            transition: color 0.3s;
            font-size: 1.5rem;
            margin: 0 2px;
        }

        .stars-container input[type="radio"]:checked~label,
        .stars-container input[type="radio"]:checked~label~label {
            color: #ffc107;
        }

        .stars-container label:hover,
        .stars-container label:hover~label {
            color: #ffdb70;
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
                        <a class="nav-link" href="colecciones.php">
                            <i class="fas fa-layer-group me-1"></i> Colecciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ofertas_flash.php">
                            <i class="fas fa-bolt me-1"></i> Ofertas Flash
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-comment me-1"></i> Testimonios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="instagram.php">
                            <i class="fab fa-instagram me-1"></i> Instagram Feed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-envelope me-1"></i> Suscriptores
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
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
        <!-- Dashboard Stats -->
        <div class="row dashboard-stats">
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Testimonios</h6>
                                <h2 class="mb-0"><?php echo count($testimonios); ?></h2>
                            </div>
                            <i class="fas fa-comment-dots fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Valoración Promedio</h6>
                                <h2 class="mb-0">
                                    <?php echo number_format($valoracion_promedio, 1); ?>
                                    <small class="text-white-50">/5</small>
                                </h2>
                            </div>
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Testimonios Recientes</h6>
                                <h2 class="mb-0"><?php
                                $recent_count = 0;
                                $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
                                foreach ($testimonios as $testimonio) {
                                    if ($testimonio['fecha'] >= $thirty_days_ago) {
                                        $recent_count++;
                                    }
                                }
                                echo $recent_count;
                                ?></h2>
                            </div>
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestionar Testimonios</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testimonialModal">
                <i class="fas fa-plus me-1"></i> Nuevo Testimonio
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

        <!-- Testimonials Grid View -->
        <div class="row mb-4">
            <?php if (empty($testimonios)): ?>
                <div class="col-12">
                    <div class="card shadow-sm p-4 text-center">
                        <i class="fas fa-comment-dots fa-3x mb-3 text-muted"></i>
                        <h3 class="text-muted">No hay testimonios disponibles</h3>
                        <p>Agrega tu primer testimonio para comenzar</p>
                        <button class="btn btn-primary mx-auto" style="width: fit-content;" data-bs-toggle="modal"
                            data-bs-target="#testimonialModal">
                            <i class="fas fa-plus me-1"></i> Nuevo Testimonio
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($testimonios as $testimonio): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm testimonial-card">
                            <div class="card-body text-center">
                                <?php if (!empty($testimonio['foto_cliente'])): ?>
                                    <img src="../<?php echo $testimonio['foto_cliente']; ?>"
                                        alt="<?php echo $testimonio['nombre_cliente']; ?>" class="client-img">
                                <?php else: ?>
                                    <div class="client-img-placeholder mx-auto">
                                        <i class="fas fa-user fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <h5 class="card-title"><?php echo $testimonio['nombre_cliente']; ?></h5>

                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $testimonio['valoracion']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>

                                <p class="card-text">
                                    <?php echo nl2br(htmlspecialchars($testimonio['comentario'])); ?>
                                </p>

                                <div class="mt-3 text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($testimonio['fecha'])); ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">ID: <?php echo $testimonio['id']; ?></small>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $testimonio['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn"
                                            data-id="<?php echo $testimonio['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($testimonio['nombre_cliente']); ?>">
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
                <h5 class="mb-0">Lista de Testimonios</h5>
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
                                <th>Cliente</th>
                                <th>Foto</th>
                                <th>Comentario</th>
                                <th>Valoración</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($testimonios)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-comment-dots fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0">No hay testimonios disponibles</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($testimonios as $testimonio): ?>
                                    <tr>
                                        <td><?php echo $testimonio['id']; ?></td>
                                        <td><?php echo $testimonio['nombre_cliente']; ?></td>
                                        <td>
                                            <?php if (!empty($testimonio['foto_cliente'])): ?>
                                                <img src="../<?php echo $testimonio['foto_cliente']; ?>"
                                                    alt="<?php echo $testimonio['nombre_cliente']; ?>" class="img-thumbnail">
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-user"></i> Sin foto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo strlen($testimonio['comentario']) > 50 ?
                                                substr($testimonio['comentario'], 0, 50) . '...' :
                                                $testimonio['comentario']; ?>
                                        </td>
                                        <td>
                                            <div class="star-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $testimonio['valoracion']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($testimonio['fecha'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-info edit-btn"
                                                data-id="<?php echo $testimonio['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?php echo $testimonio['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($testimonio['nombre_cliente']); ?>">
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

    <!-- Testimonial Modal -->
    <div class="modal fade" id="testimonialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Crear Nuevo Testimonio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data" id="testimonialForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="testimonialId">

                        <div class="mb-3">
                            <label for="nombre_cliente" class="form-label required">Nombre del Cliente</label>
                            <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Valoración</label>
                            <div class="stars-container">
                                <input type="radio" id="star5" name="valoracion" value="5" required />
                                <label for="star5" title="5 estrellas"><i class="fas fa-star"></i></label>

                                <input type="radio" id="star4" name="valoracion" value="4" required />
                                <label for="star4" title="4 estrellas"><i class="fas fa-star"></i></label>

                                <input type="radio" id="star3" name="valoracion" value="3" required />
                                <label for="star3" title="3 estrellas"><i class="fas fa-star"></i></label>

                                <input type="radio" id="star2" name="valoracion" value="2" required />
                                <label for="star2" title="2 estrellas"><i class="fas fa-star"></i></label>

                                <input type="radio" id="star1" name="valoracion" value="1" required />
                                <label for="star1" title="1 estrella"><i class="fas fa-star"></i></label>
                            </div>
                            <div class="form-text mt-2">Selecciona una valoración de 1 a 5 estrellas.</div>
                        </div>

                        <div class="mb-3">
                            <label for="fecha" class="form-label required">Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" required>
                            <div class="form-text">Fecha en que se hizo el testimonio.</div>
                        </div>

                        <div class="mb-3">
                            <label for="comentario" class="form-label required">Comentario</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="4"
                                required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="foto_cliente" class="form-label">Foto del Cliente</label>
                            <input type="file" class="form-control" id="foto_cliente" name="foto_cliente"
                                accept="image/*">
                            <div class="form-text">Imagen opcional del cliente. Se recomienda una foto cuadrada.</div>

                            <div id="currentImageContainer" class="mt-2 d-none">
                                <div class="card p-2 bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="img-upload-preview me-3">
                                            <img id="currentImage" class="img-thumbnail"
                                                style="max-height: 100px; border-radius: 50%;">
                                            <span class="remove-img" title="Eliminar imagen"><i
                                                    class="fas fa-times"></i></span>
                                        </div>
                                        <div>
                                            <p class="mb-1">Foto actual</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="keep_existing_image"
                                                    name="keep_existing_image" value="1" checked>
                                                <label class="form-check-label" for="keep_existing_image">
                                                    Mantener foto actual
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="imagePreviewContainer" class="mt-2 d-none">
                                <p class="mb-1">Vista previa:</p>
                                <img id="imagePreview" class="img-thumbnail"
                                    style="max-height: 100px; border-radius: 50%;">
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
                        eliminar el testimonio de <strong id="deleteTestimonialName"></strong>?</p>
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
            document.getElementById('foto_cliente').addEventListener('change', function (e) {
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
                    const testimonialId = this.getAttribute('data-id');
                    const testimonialName = this.getAttribute('data-name');
                    document.getElementById('deleteId').value = testimonialId;
                    document.getElementById('deleteTestimonialName').textContent = testimonialName;
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });

            // Resetear formulario al abrir modal para crear nuevo testimonio
            const testimonialModal = document.getElementById('testimonialModal');
            testimonialModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                // Si no es un botón de editar, resetear el formulario (es un nuevo testimonio)
                if (!button || !button.classList.contains('edit-btn')) {
                    resetForm();
                }
            });

            // Manejar botones de edición
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const testimonialId = this.getAttribute('data-id');

                    // Hacer una solicitud AJAX para obtener los datos del testimonio
                    fetch(`get_testimonial.php?id=${testimonialId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                fillForm(data.testimonial);
                                const testimonialModal = new bootstrap.Modal(document.getElementById('testimonialModal'));
                                testimonialModal.show();
                            } else {
                                alert('Error al cargar los datos del testimonio');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al cargar los datos del testimonio');
                        });
                });
            });

            function resetForm() {
                document.getElementById('modalTitle').textContent = 'Crear Nuevo Testimonio';
                document.getElementById('formAction').value = 'create';
                document.getElementById('testimonialId').value = '';
                document.getElementById('testimonialForm').reset();
                document.getElementById('imagePreviewContainer').classList.add('d-none');
                document.getElementById('currentImageContainer').classList.add('d-none');

                // Establecer fecha actual por defecto
                const today = new Date();
                const formattedDate = today.toISOString().slice(0, 10);
                document.getElementById('fecha').value = formattedDate;

                // Resetear estrellas
                document.getElementById('star5').checked = false;
                document.getElementById('star4').checked = false;
                document.getElementById('star3').checked = false;
                document.getElementById('star2').checked = false;
                document.getElementById('star1').checked = false;
            }

            function fillForm(testimonial) {
                document.getElementById('modalTitle').textContent = 'Editar Testimonio';
                document.getElementById('formAction').value = 'update';
                document.getElementById('testimonialId').value = testimonial.id;
                document.getElementById('nombre_cliente').value = testimonial.nombre_cliente;
                document.getElementById('comentario').value = testimonial.comentario;

                // Establecer valoración
                const valoracion = parseInt(testimonial.valoracion);
                document.getElementById(`star${valoracion}`).checked = true;

                // Formatear fecha
                const fecha = new Date(testimonial.fecha);
                const formattedDate = fecha.toISOString().slice(0, 10);
                document.getElementById('fecha').value = formattedDate;

                // Mostrar imagen actual si existe
                if (testimonial.foto_cliente) {
                    document.getElementById('currentImage').src = '../' + testimonial.foto_cliente;
                    document.getElementById('currentImageContainer').classList.remove('d-none');
                } else {
                    document.getElementById('currentImageContainer').classList.add('d-none');
                }

                // Ocultar la vista previa de imagen nueva
                document.getElementById('imagePreviewContainer').classList.add('d-none');
            }

            // Establecer fecha actual por defecto al cargar la página
            const today = new Date();
            const formattedDate = today.toISOString().slice(0, 10);
            document.getElementById('fecha').value = formattedDate;
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