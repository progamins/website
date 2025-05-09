<?php
/**
 * Admin Panel for Chollo Glam - Flash Offers Management
 * 
 * This file provides the interface for managing flash offers.
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

// Handle flash offer actions (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission based on action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create' || $action === 'update') {
        // Get form data
        $tiempo_fin = escape($conn, $_POST['tiempo_fin']);
        $productos = isset($_POST['productos']) ? $_POST['productos'] : array();

        if ($action === 'create') {
            // Insert new flash offer
            $sql = "INSERT INTO ofertas_flash (tiempo_fin) VALUES ($tiempo_fin)";

            if (mysqli_query($conn, $sql)) {
                $oferta_id = mysqli_insert_id($conn);

                // Associate products with the flash offer
                foreach ($productos as $producto_id) {
                    $producto_id = (int) $producto_id;
                    $sql = "INSERT INTO productos_oferta_flash (oferta_id, producto_id) VALUES ($oferta_id, $producto_id)";
                    mysqli_query($conn, $sql);
                }

                $message = "Oferta flash creada con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        } else if ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];

            // Update existing flash offer
            $sql = "UPDATE ofertas_flash SET tiempo_fin = $tiempo_fin WHERE id = $id";

            if (mysqli_query($conn, $sql)) {
                // Remove all product associations
                $sql = "DELETE FROM productos_oferta_flash WHERE oferta_id = $id";
                mysqli_query($conn, $sql);

                // Add new product associations
                foreach ($productos as $producto_id) {
                    $producto_id = (int) $producto_id;
                    $sql = "INSERT INTO productos_oferta_flash (oferta_id, producto_id) VALUES ($id, $producto_id)";
                    mysqli_query($conn, $sql);
                }

                $message = "Oferta flash actualizada con éxito.";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    } else if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];

        // Delete flash offer (product associations will be deleted automatically due to foreign key constraints)
        $sql = "DELETE FROM ofertas_flash WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            $message = "Oferta flash eliminada con éxito.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch all flash offers
$sql = "SELECT * FROM ofertas_flash ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
$ofertas = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get product count and product details for each offer
foreach ($ofertas as $key => $oferta) {
    $oferta_id = (int) $oferta['id'];

    // Get product count
    $count_sql = "SELECT COUNT(*) as count FROM productos_oferta_flash WHERE oferta_id = $oferta_id";
    $count_result = mysqli_query($conn, $count_sql);
    $count_data = mysqli_fetch_assoc($count_result);
    $ofertas[$key]['product_count'] = $count_data['count'];

    // Get product details
    $products_sql = "SELECT p.* FROM productos p 
                    JOIN productos_oferta_flash pof ON p.id = pof.producto_id 
                    WHERE pof.oferta_id = $oferta_id";
    $products_result = mysqli_query($conn, $products_sql);
    $ofertas[$key]['productos'] = mysqli_fetch_all($products_result, MYSQLI_ASSOC);
}

// Fetch all products for dropdown
$sql_products = "SELECT * FROM productos WHERE stock > 0 ORDER BY nombre";
$result_products = mysqli_query($conn, $sql_products);
$productos = mysqli_fetch_all($result_products, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Ofertas Flash - Chollo Glam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
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

        .select2-container {
            width: 100% !important;
        }

        .offer-card {
            height: 100%;
            transition: transform 0.2s;
        }

        .offer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .offer-product {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .offer-product img {
            height: 40px;
            width: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .status-badge.active {
            background-color: #28a745;
        }

        .status-badge.expired {
            background-color: #dc3545;
        }

        .timer-icon {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
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
                        <a class="nav-link active" href="#">
                            <i class="fas fa-bolt me-1"></i> Ofertas Flash
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="testimonio.php">
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
                                <h6 class="card-title">Total Ofertas Flash</h6>
                                <h2 class="mb-0"><?php echo count($ofertas); ?></h2>
                            </div>
                            <i class="fas fa-bolt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Ofertas Activas</h6>
                                <h2 class="mb-0"><?php
                                $active_count = 0;
                                foreach ($ofertas as $oferta) {
                                    if (strtotime($oferta['tiempo_fin']) > time()) {
                                        $active_count++;
                                    }
                                }
                                echo $active_count;
                                ?></h2>
                            </div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Ofertas Expiradas</h6>
                                <h2 class="mb-0"><?php
                                $expired_count = 0;
                                foreach ($ofertas as $oferta) {
                                    if (strtotime($oferta['tiempo_fin']) <= time()) {
                                        $expired_count++;
                                    }
                                }
                                echo $expired_count;
                                ?></h2>
                            </div>
                            <i class="fas fa-hourglass-end fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestionar Ofertas Flash</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#offerModal">
                <i class="fas fa-plus me-1"></i> Nueva Oferta Flash
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

        <!-- Flash Offers Grid View -->
        <div class="row mb-4">
            <?php if (empty($ofertas)): ?>
                <div class="col-12">
                    <div class="card shadow-sm p-4 text-center">
                        <i class="fas fa-bolt fa-3x mb-3 text-muted"></i>
                        <h3 class="text-muted">No hay ofertas flash disponibles</h3>
                        <p>Crea una nueva oferta flash para comenzar</p>
                        <button class="btn btn-primary mx-auto" style="width: fit-content;" data-bs-toggle="modal"
                            data-bs-target="#offerModal">
                            <i class="fas fa-plus me-1"></i> Nueva Oferta Flash
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($ofertas as $oferta): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm offer-card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Oferta Flash #<?php echo $oferta['id']; ?></h5>
                                </div>
                                <div>
                                    <?php
                                    $is_active = strtotime($oferta['tiempo_fin']) > time();
                                    $status_class = $is_active ? 'active' : 'expired';
                                    $status_text = $is_active ? 'Activa' : 'Expirada';
                                    ?>
                                    <span class="badge status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6>Finaliza el:</h6>
                                    <p class="mb-1"><?php echo date('d/m/Y H:i', strtotime($oferta['tiempo_fin'])); ?></p>

                                    <?php if ($is_active): ?>
                                        <div class="countdown mt-2" data-end="<?php echo strtotime($oferta['tiempo_fin']); ?>">
                                            <i class="fas fa-stopwatch me-2 timer-icon text-danger"></i>
                                            <span class="countdown-text"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <h6>Productos en oferta (<?php echo $oferta['product_count']; ?>):</h6>
                                    <?php if (empty($oferta['productos'])): ?>
                                        <p class="text-muted">No hay productos en esta oferta</p>
                                    <?php else: ?>
                                        <div class="mb-2">
                                            <?php foreach ($oferta['productos'] as $producto): ?>
                                                <div class="offer-product" title="<?php echo $producto['nombre']; ?>">
                                                    <?php if (!empty($producto['imagen'])): ?>
                                                        <img src="../<?php echo $producto['imagen']; ?>"
                                                            alt="<?php echo $producto['nombre']; ?>">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px; border-radius: 50%;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-outline-primary view-products-btn"
                                                data-offer-id="<?php echo $oferta['id']; ?>">
                                                <i class="fas fa-eye me-1"></i> Ver productos
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">Creada:
                                        <?php echo date('d/m/Y', strtotime($oferta['fecha_creacion'])); ?></small>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $oferta['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $oferta['id']; ?>">
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
                <h5 class="mb-0">Lista de Ofertas Flash</h5>
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
                                <th>Estado</th>
                                <th>Fecha Fin</th>
                                <th>Tiempo Restante</th>
                                <th>Productos</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ofertas)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-bolt fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0">No hay ofertas flash disponibles</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ofertas as $oferta): ?>
                                    <?php
                                    $is_active = strtotime($oferta['tiempo_fin']) > time();
                                    $status_class = $is_active ? 'active' : 'expired';
                                    $status_text = $is_active ? 'Activa' : 'Expirada';
                                    ?>
                                    <tr>
                                        <td><?php echo $oferta['id']; ?></td>
                                        <td>
                                            <span class="badge status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($oferta['tiempo_fin'])); ?></td>
                                        <td>
                                            <?php if ($is_active): ?>
                                                <div class="countdown" data-end="<?php echo strtotime($oferta['tiempo_fin']); ?>">
                                                    <span class="countdown-text"></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-danger">Expirada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary view-products-btn"
                                                data-offer-id="<?php echo $oferta['id']; ?>">
                                                <?php echo $oferta['product_count']; ?> productos
                                            </button>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($oferta['fecha_creacion'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $oferta['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?php echo $oferta['id']; ?>">
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

    <!-- Flash Offer Modal -->
    <div class="modal fade" id="offerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Crear Nueva Oferta Flash</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="" method="post" id="offerForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="offerId">

                        <div class="mb-3">
                            <label for="tiempo_fin" class="form-label required">Fecha y hora de finalización</label>
                            <input type="datetime-local" class="form-control" id="tiempo_fin" name="tiempo_fin"
                                required>
                            <div class="form-text">Establece cuándo terminará esta oferta flash.</div>
                        </div>

                        <div class="mb-3">
                            <label for="productos" class="form-label required">Productos en oferta</label>
                            <select class="form-select" id="productos" name="productos[]" multiple required>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?php echo $producto['id']; ?>">
                                        <?php echo $producto['nombre']; ?> -
                                        <?php echo number_format($producto['precio_actual'], 2); ?>€
                                        <?php if ($producto['precio_original']): ?>
                                            (Antes: <?php echo number_format($producto['precio_original'], 2); ?>€)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecciona los productos que estarán en oferta flash. Puedes
                                seleccionar varios.</div>
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

    <!-- Products View Modal -->
    <div class="modal fade" id="productsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Productos en Oferta Flash</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" id="productsModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando productos...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
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
                        eliminar la oferta flash #<strong id="deleteOfferId"></strong>?</p>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Select2
            $(document).ready(function () {
                $('#productos').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Selecciona productos',
                    allowClear: true
                });
            });

            // Update countdowns
            function updateCountdowns() {
                const countdowns = document.querySelectorAll('.countdown');
                countdowns.forEach(countdown => {
                    const endTime = parseInt(countdown.getAttribute('data-end')) * 1000;
                    const now = new Date().getTime();
                    const distance = endTime - now;

                    const countdownText = countdown.querySelector('.countdown-text');

                    if (distance <= 0) {
                        countdownText.innerHTML = 'EXPIRADA';
                        countdown.classList.add('text-danger');
                    } else {
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        let timeStr = '';
                        if (days > 0) {
                            timeStr += `${days}d `;
                        }
                        timeStr += `${hours}h ${minutes}m ${seconds}s`;
                        countdownText.innerHTML = timeStr;
                    }
                });
            }

            // Update countdowns every second
            updateCountdowns();
            setInterval(updateCountdowns, 1000);

            // Manejar botones de eliminación
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const offerId = this.getAttribute('data-id');
                    document.getElementById('deleteId').value = offerId;
                    document.getElementById('deleteOfferId').textContent = offerId;
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });

            // Resetear formulario al abrir modal para crear nueva oferta
            const offerModal = document.getElementById('offerModal');
            offerModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                // Si no es un botón de editar, resetear el formulario (es una nueva oferta)
                if (!button || !button.classList.contains('edit-btn')) {
                    resetForm();
                }
            });

            // Manejar botones de edición
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const offerId = this.getAttribute('data-id');

                    // Hacer una solicitud AJAX para obtener los datos de la oferta
                    fetch(`get_offer.php?id=${offerId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                fillForm(data.offer);
                                const offerModal = new bootstrap.Modal(document.getElementById('offerModal'));
                                offerModal.show();
                            } else {
                                alert('Error al cargar los datos de la oferta');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al cargar los datos de la oferta');
                        });
                });
            });

            // Manejar botones de ver productos
            document.querySelectorAll('.view-products-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const offerId = this.getAttribute('data-offer-id');

                    // Mostrar modal con productos
                    const productsModal = new bootstrap.Modal(document.getElementById('productsModal'));
                    productsModal.show();

                    // Cargar datos de productos
                    fetch(`get_offer_products.php?id=${offerId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayProductsInModal(data.products, offerId);
                            } else {
                                document.getElementById('productsModalBody').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                                </div>
                            `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('productsModalBody').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> Error al cargar los productos
                            </div>
                        `;
                        });
                });
            });

            function resetForm() {
                document.getElementById('modalTitle').textContent = 'Crear Nueva Oferta Flash';
                document.getElementById('formAction').value = 'create';
                document.getElementById('offerId').value = '';
                document.getElementById('offerForm').reset();

                // Establecer fecha y hora predeterminada (24 horas a partir de ahora)
                const now = new Date();
                now.setDate(now.getDate() + 1);
                document.getElementById('tiempo_fin').value = now.toISOString().slice(0, 16);

                // Resetear Select2
                $('#productos').val(null).trigger('change');
            }

            function fillForm(offer) {
                document.getElementById('modalTitle').textContent = 'Editar Oferta Flash';
                document.getElementById('formAction').value = 'update';
                document.getElementById('offerId').value = offer.id;

                // Formatear fecha y hora
                const date = new Date(offer.tiempo_fin);
                const formattedDateTime = date.toISOString().slice(0, 16);
                document.getElementById('tiempo_fin').value = formattedDateTime;

                // Seleccionar productos
                $('#productos').val(offer.products.map(p => p.id)).trigger('change');
            }

            function displayProductsInModal(products, offerId) {
                let html = `
                <h5 class="mb-3">Productos en Oferta Flash #${offerId}</h5>
            `;

                if (products.length === 0) {
                    html += `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No hay productos en esta oferta
                    </div>
                `;
                } else {
                    html += `<div class="row">`;

                    products.forEach(product => {
                        html += `
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        ${product.imagen ?
                                `<img src="../${product.imagen}" class="img-fluid rounded-start" alt="${product.nombre}" style="height: 100%; object-fit: cover;">` :
                                `<div class="bg-light d-flex align-items-center justify-content-center h-100">
                                                <i class="fas fa-image text-muted fa-2x"></i>
                                            </div>`
                            }
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title">${product.nombre}</h5>
                                            <p class="card-text">
                                                <span class="text-danger fw-bold">${parseFloat(product.precio_actual).toFixed(2)}€</span>
                                                ${product.precio_original ?
                                `<del class="text-muted ms-2">${parseFloat(product.precio_original).toFixed(2)}€</del>` :
                                ''
                            }
                                            </p>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-tag me-1"></i> ${product.categoria}
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    });

                    html += `</div>`;
                }

                document.getElementById('productsModalBody').innerHTML = html;
            }

            // Establecer fecha y hora predeterminada en el formulario al cargar la página
            const now = new Date();
            now.setDate(now.getDate() + 1);
            document.getElementById('tiempo_fin').value = now.toISOString().slice(0, 16);
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