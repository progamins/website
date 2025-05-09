<?php
/**
 * Admin Panel for Chollo Glam
 * 
 * This file provides the main admin interface for managing products and other resources.
 * Enhanced with improved edit functionality, proper AJAX handling, and custom alerts.
 */

// Define ADMIN_ACCESS constant to control access to included files
define('ADMIN_ACCESS', true);

// Start session for authentication and alerts
session_start();

// Include database connection
$conn = require_once 'db_config.php';

// Authentication check
// Uncomment this section when ready to implement proper authentication
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

// Alert message initialization
if (!isset($_SESSION['alerts'])) {
    $_SESSION['alerts'] = [];
}

// Function to safely escape values for SQL queries
function escape($conn, $value)
{
    if ($value === null) {
        return 'NULL';
    }
    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

// Function to add an alert to the session
function addAlert($type, $message, $title = '', $persist = false, $icon = '', $autoDismiss = true, $dismissTime = 5000)
{
    if (!isset($_SESSION['alerts'])) {
        $_SESSION['alerts'] = [];
    }

    if (empty($icon)) {
        switch ($type) {
            case 'success':
                $icon = 'check-circle';
                break;
            case 'danger':
                $icon = 'exclamation-circle';
                break;
            case 'warning':
                $icon = 'exclamation-triangle';
                break;
            case 'info':
                $icon = 'info-circle';
                break;
            default:
                $icon = 'bell';
        }
    }

    $_SESSION['alerts'][] = [
        'type' => $type,
        'message' => $message,
        'title' => $title,
        'persist' => $persist,
        'icon' => $icon,
        'autoDismiss' => $autoDismiss,
        'dismissTime' => $dismissTime
    ];
}

// Handle product actions (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission based on action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create' || $action === 'update') {
        // Get form data
        $nombre = escape($conn, $_POST['nombre']);
        $categoria_id = (int) $_POST['categoria_id'];
        $precio_actual = escape($conn, $_POST['precio_actual']);
        $precio_original = !empty($_POST['precio_original']) ? escape($conn, $_POST['precio_original']) : 'NULL';
        $descripcion = escape($conn, $_POST['descripcion']);
        $etiqueta = !empty($_POST['etiqueta']) ? escape($conn, $_POST['etiqueta']) : 'NULL';
        $stock = (int) $_POST['stock'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $coleccion_id = !empty($_POST['coleccion_id']) ? (int) $_POST['coleccion_id'] : 'NULL';
        $stock_minimo = !empty($_POST['stock_minimo']) ? (int) $_POST['stock_minimo'] : 5;
        $alerta_stock_activa = isset($_POST['alerta_stock_activa']) ? 1 : 0;

        // Handle image upload
        $imagen = '';
        $keep_existing_image = isset($_POST['keep_existing_image']) ? (bool) $_POST['keep_existing_image'] : false;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $upload_dir = '../uploads/products/';

            // Make sure directory exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['imagen']['name']));
            $target = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                $imagen = 'uploads/products/' . $filename;
            } else {
                addAlert('warning', 'No se pudo subir la imagen. Por favor, inténtalo de nuevo.', 'Error de imagen');
            }
        }

        if ($action === 'create') {
            // Insert new product
            $imagen_value = !empty($imagen) ? escape($conn, $imagen) : 'NULL';

            $sql = "INSERT INTO productos (nombre, categoria_id, precio_actual, precio_original, 
                    descripcion, etiqueta, imagen, stock, destacado, coleccion_id, stock_minimo, alerta_stock_activa) 
                    VALUES ($nombre, $categoria_id, $precio_actual, $precio_original, 
                    $descripcion, $etiqueta, $imagen_value, $stock, $destacado, $coleccion_id, $stock_minimo, $alerta_stock_activa)";

            if (mysqli_query($conn, $sql)) {
                $newProductId = mysqli_insert_id($conn);
                addAlert('success', 'El producto <strong>' . htmlspecialchars($_POST['nombre']) . '</strong> ha sido añadido correctamente.', 'Producto creado', false, 'check-circle');

                // Check if stock is low
                if ($stock < $stock_minimo) {
                    addAlert('warning', 'El producto recién creado tiene poco stock. Considera reabastecerlo pronto.', 'Stock bajo', true, 'exclamation-triangle', false);
                }

                // Add SEO entry for the product
                $url_amigable = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['nombre']), '-'));
                $seoSql = "INSERT INTO producto_seo (producto_id, meta_titulo, meta_descripcion, url_amigable) 
                           VALUES ($newProductId, $nombre, " . escape($conn, substr($_POST['descripcion'], 0, 255)) . ", '" . mysqli_real_escape_string($conn, $url_amigable) . "')";
                mysqli_query($conn, $seoSql);

                // Add initial price history
                $priceSql = "INSERT INTO producto_historial_precios (producto_id, precio) 
                             VALUES ($newProductId, " . (float) $_POST['precio_actual'] . ")";
                mysqli_query($conn, $priceSql);
            } else {
                addAlert('danger', 'Error al crear el producto: ' . mysqli_error($conn), 'Error en la base de datos', true, 'database', false);
            }
        } else if ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];

            // Get current product data for comparison
            $oldProductSql = "SELECT * FROM productos WHERE id = $id";
            $oldProductResult = mysqli_query($conn, $oldProductSql);
            $oldProduct = mysqli_fetch_assoc($oldProductResult);

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
                    categoria_id = $categoria_id, 
                    precio_actual = $precio_actual, 
                    precio_original = $precio_original, 
                    descripcion = $descripcion, 
                    etiqueta = $etiqueta, 
                    $imagen_clause
                    stock = $stock, 
                    destacado = $destacado, 
                    coleccion_id = $coleccion_id,
                    stock_minimo = $stock_minimo,
                    alerta_stock_activa = $alerta_stock_activa
                    WHERE id = $id";

            if (mysqli_query($conn, $sql)) {
                addAlert('success', 'El producto <strong>' . htmlspecialchars($_POST['nombre']) . '</strong> ha sido actualizado correctamente.', 'Producto actualizado');

                // Alert for price changes
                if ($oldProduct['precio_actual'] != $_POST['precio_actual']) {
                    $priceDiff = $_POST['precio_actual'] - $oldProduct['precio_actual'];
                    $changeType = $priceDiff > 0 ? 'aumentado' : 'reducido';
                    $diffAmount = abs($priceDiff);
                    addAlert('info', "El precio del producto ha $changeType en $diffAmount €.", 'Cambio de precio', false, 'tags');

                    // Add to price history - the trigger should handle this automatically
                }

                // Alert for stock changes
                if ($oldProduct['stock'] > $stock_minimo && $stock < $stock_minimo && $stock > 0) {
                    addAlert('warning', 'El stock del producto es bajo. Considera reabastecerlo pronto.', 'Stock bajo', true, 'exclamation-triangle', false);
                } elseif ($oldProduct['stock'] > 0 && $stock == 0) {
                    addAlert('danger', 'El producto se ha quedado sin stock. Realiza un pedido para reabastecerlo.', 'Sin stock', true, 'ban', false);
                }

                // Update SEO entry
                $url_amigable = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['nombre']), '-'));
                $seoUpdateSql = "UPDATE producto_seo SET 
                                 meta_titulo = $nombre,
                                 meta_descripcion = " . escape($conn, substr($_POST['descripcion'], 0, 255)) . ",
                                 url_amigable = '" . mysqli_real_escape_string($conn, $url_amigable) . "'
                                 WHERE producto_id = $id";
                mysqli_query($conn, $seoUpdateSql);
            } else {
                addAlert('danger', 'Error al actualizar el producto: ' . mysqli_error($conn), 'Error en la base de datos', true, 'database', false);
            }
        }
    } else if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];

        // Get product name before deletion
        $productNameSql = "SELECT nombre FROM productos WHERE id = $id";
        $productNameResult = mysqli_query($conn, $productNameSql);
        $productName = '';

        if ($productRow = mysqli_fetch_assoc($productNameResult)) {
            $productName = $productRow['nombre'];
        }

        // Delete product
        $sql = "DELETE FROM productos WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            addAlert('success', 'El producto <strong>' . htmlspecialchars($productName) . '</strong> ha sido eliminado correctamente.', 'Producto eliminado');
        } else {
            addAlert('danger', 'Error al eliminar el producto: ' . mysqli_error($conn), 'Error en la base de datos', true, 'database', false);
        }
    }
}

// Fetch all products with joined data from categories and collections
$sql = "SELECT p.*, c.nombre AS categoria_nombre, col.nombre AS coleccion_nombre 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        LEFT JOIN colecciones col ON p.coleccion_id = col.id 
        ORDER BY p.id DESC";
$result = mysqli_query($conn, $sql);
$productos = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch all categories for dropdown
$sql_categories = "SELECT * FROM categorias ORDER BY nombre";
$result_categories = mysqli_query($conn, $sql_categories);
$categorias = mysqli_fetch_all($result_categories, MYSQLI_ASSOC);

// Fetch all collections for dropdown
$sql_collections = "SELECT * FROM colecciones ORDER BY nombre";
$result_collections = mysqli_query($conn, $sql_collections);
$colecciones = mysqli_fetch_all($result_collections, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Chollo Glam</title>
    <link rel="stylesheet" href="panel.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <!-- System alerts (persistent, important) -->
        <div id="systemAlerts">
            <?php
            $persistentAlerts = array_filter($_SESSION['alerts'] ?? [], function ($alert) {
                return $alert['persist'] === true;
            });

            foreach ($persistentAlerts as $key => $alert):
                // Remove from session alerts so they don't appear in toast notifications
                if (isset($_SESSION['alerts'][$key])) {
                    unset($_SESSION['alerts'][$key]);
                }
                ?>
                    <div class="system-alert alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-<?php echo $alert['icon']; ?> me-2"></i>
                            <?php if (!empty($alert['title'])): ?>
                                    <strong><?php echo $alert['title']; ?>:</strong>&nbsp;
                            <?php endif; ?>
                            <span><?php echo $alert['message']; ?></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="row dashboard-stats">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Productos</h6>
                                <h2 class="mb-0"><?php echo count($productos); ?></h2>
                            </div>
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Productos Destacados</h6>
                                <h2 class="mb-0"><?php echo count(array_filter($productos, function ($p) {
                                    return $p['destacado'] == 1;
                                })); ?></h2>
                            </div>
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Stock Bajo</h6>
                                <h2 class="mb-0"><?php echo count(array_filter($productos, function ($p) {
                                    return $p['stock'] < $p['stock_minimo'] && $p['stock'] > 0;
                                })); ?></h2>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Sin Stock</h6>
                                <h2 class="mb-0"><?php echo count(array_filter($productos, function ($p) {
                                    return $p['stock'] == 0;
                                })); ?></h2>
                            </div>
                            <i class="fas fa-ban fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Administrar Productos</h1>
            <div>
                <button class="btn btn-outline-secondary me-2">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                    <i class="fas fa-plus me-1"></i> Nuevo Producto
                </button>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio Actual</th>
                                <th>Precio Original</th>
                                <th>Stock</th>
                                <th>Destacado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                                            <p class="mb-0">No hay productos disponibles</p>
                                        </td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($productos as $producto): ?>
                                            <tr>
                                                <td><?php echo $producto['id']; ?></td>
                                                <td>
                                                    <?php if (!empty($producto['imagen'])): ?>
                                                            <img src="../<?php echo $producto['imagen']; ?>" alt="<?php echo $producto['nombre']; ?>" class="img-thumbnail" style="max-height: 50px;">
                                                    <?php else: ?>
                                                            <span class="text-muted"><i class="fas fa-image"></i> Sin imagen</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $producto['nombre']; ?></td>
                                                <td>
                                                    <span class="badge bg-info text-dark">
                                                        <?php echo $producto['categoria_nombre']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($producto['precio_actual'], 2); ?> €</td>
                                                <td>
                                                    <?php if ($producto['precio_original']): ?>
                                                            <del class="text-muted"><?php echo number_format($producto['precio_original'], 2); ?> €</del>
                                                    <?php else: ?>
                                                            -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($producto['stock'] == 0): ?>
                                                            <span class="badge bg-danger">Sin stock</span>
                                                    <?php elseif ($producto['stock'] < $producto['stock_minimo']): ?>
                                                            <span class="badge bg-warning text-dark">Bajo: <?php echo $producto['stock']; ?></span>
                                                    <?php else: ?>
                                                            <span class="badge bg-success"><?php echo $producto['stock']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($producto['destacado']): ?>
                                                            <span class="badge bg-success"><i class="fas fa-star me-1"></i> Sí</span>
                                                    <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $producto['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $producto['id']; ?>" data-name="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-secondary view-btn" data-id="<?php echo $producto['id']; ?>" data-bs-toggle="tooltip" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Mostrando <?php echo count($productos); ?> productos</small>
                </div>
                <div>
                    <!-- Simple pagination placeholder - would be dynamic in a real implementation -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Alerts Container -->
    <div class="alert-container" id="alertContainer"></div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Crear Nuevo Producto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data" id="productForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="productId">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label required">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label for="categoria_id" class="form-label required">Categoría</label>
                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>">
                                                <?php echo $categoria['nombre']; ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="precio_actual" class="form-label required">Precio Actual</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="precio_actual" name="precio_actual" step="0.01" min="0" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="precio_original" class="form-label">Precio Original</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="precio_original" name="precio_original" step="0.01" min="0">
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="form-text">Dejar vacío si no hay descuento</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stock" class="form-label required">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="1" value="5">
                                <div class="form-text">Nivel para alertas de bajo stock</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="etiqueta" class="form-label">Etiqueta</label>
                                <input type="text" class="form-control" id="etiqueta" name="etiqueta">
                                <div class="form-text">Por ejemplo: Nuevo, Oferta, Limitado...</div>
                            </div>
                            <div class="col-md-6">
                                <label for="coleccion_id" class="form-label">Colección</label>
                                <select class="form-select" id="coleccion_id" name="coleccion_id">
                                    <option value="">Ninguna</option>
                                    <?php foreach ($colecciones as $coleccion): ?>
                                            <option value="<?php echo $coleccion['id']; ?>">
                                                <?php echo $coleccion['nombre']; ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Destacado</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                                    <label class="form-check-label" for="destacado">Marcar como producto destacado</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Alertas de stock</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="alerta_stock_activa" name="alerta_stock_activa" checked>
                                    <label class="form-check-label" for="alerta_stock_activa">Activar alertas de stock bajo</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen del Producto</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                            
                            <div id="currentImageContainer" class="mt-2 d-none">
                                <div class="card p-2 bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="img-upload-preview me-3">
                                            <img id="currentImage" class="img-thumbnail" style="max-height: 100px;">
                                            <span class="remove-img" title="Eliminar imagen"><i class="fas fa-times"></i></span>
                                        </div>
                                        <div>
                                            <p class="mb-1">Imagen actual</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="keep_existing_image" name="keep_existing_image" value="1" checked>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><i class="fas fa-exclamation-triangle text-warning me-2"></i> ¿Estás seguro de que deseas eliminar el producto <strong id="deleteProductName"></strong>?</p>
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

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">Detalles del Producto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <img id="detailsImage" class="img-fluid rounded" style="max-height: 200px;" alt="Imagen del producto">
                            <div id="noImageMessage" class="d-none">
                                <i class="fas fa-image fa-4x text-muted my-3"></i>
                                <p class="text-muted">Sin imagen</p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h4 id="detailsName" class="mb-3"></h4>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Categoría:</div>
                                <div class="col-sm-8"><span id="detailsCategory" class="badge bg-info text-dark"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Precio Actual:</div>
                                <div class="col-sm-8"><span id="detailsPrice" class="text-primary fw-bold"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Precio Original:</div>
                                <div class="col-sm-8"><span id="detailsOriginalPrice"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Stock:</div>
                                <div class="col-sm-8"><span id="detailsStock"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Stock Mínimo:</div>
                                <div class="col-sm-8"><span id="detailsStockMin"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Etiqueta:</div>
                                <div class="col-sm-8"><span id="detailsTag"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Colección:</div>
                                <div class="col-sm-8"><span id="detailsCollection"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Destacado:</div>
                                <div class="col-sm-8"><span id="detailsFeatured"></span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Alerta Stock:</div>
                                <div class="col-sm-8"><span id="detailsAlertaStock"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h5>Descripción</h5>
                        <p id="detailsDescription" class="text-muted"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-info edit-from-details">
                        <i class="fas fa-edit me-1"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Alert system
        function showAlert(type, message, title = '', persist = false, icon = '', autoDismiss = true, dismissTime = 5000) {
            const alertContainer = document.getElementById('alertContainer');
            
            if (!icon) {
                switch (type) {
                    case 'success':
                        icon = 'check-circle';
                        break;
                    case 'danger':
                        icon = 'exclamation-circle';
                        break;
                    case 'warning':
                        icon = 'exclamation-triangle';
                        break;
                    case 'info':
                        icon = 'info-circle';
                        break;
                    default:
                        icon = 'bell';
                }
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-alert alert-${type} d-flex`;
            alertDiv.innerHTML = `
                <div class="alert-icon">
                    <i class="fas fa-${icon}"></i>
                </div>
                <div class="alert-content">
                    ${title ? '<div class="alert-title">' + title + '</div>' : ''}
                    <div class="alert-message">${message}</div>
                </div>
                <div class="alert-close">
                    <i class="fas fa-times"></i>
                </div>
            `;
            
            alertContainer.appendChild(alertDiv);
            
            // Event listener for closing the alert
            alertDiv.querySelector('.alert-close').addEventListener('click', function() {
                removeAlert(alertDiv);
            });
            
            // Auto dismiss
            if (autoDismiss && !persist) {
                setTimeout(() => {
                    removeAlert(alertDiv);
                }, dismissTime);
            }
            
            return alertDiv;
        }
        
        function removeAlert(alertDiv) {
            alertDiv.classList.add('alert-dismissing');
            setTimeout(() => {
                alertDiv.remove();
            }, 500);
        }
        
        // Vista previa de imagen cargada
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreviewContainer').classList.remove('d-none');
                    
                    // Show alert
                    showAlert('info', 'Imagen cargada. No olvides guardar los cambios para aplicarla.', 'Vista previa', false, 'image');
                }
                reader.readAsDataURL(file);
            }
        });

        // Manejar eliminar imagen actual
        const removeImgBtn = document.querySelector('.remove-img');
        if (removeImgBtn) {
            removeImgBtn.addEventListener('click', function() {
                document.getElementById('keep_existing_image').checked = false;
                document.getElementById('currentImageContainer').classList.add('d-none');
                
                // Show alert
                showAlert('warning', 'La imagen se eliminará cuando guardes los cambios.', 'Imagen eliminada');
            });
        }

        // Manejar botones de eliminación
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                document.getElementById('deleteId').value = productId;
                document.getElementById('deleteProductName').textContent = productName;
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Manejar botones de vista
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                // Show loading alert
                const loadingAlert = showAlert('info', 'Cargando detalles del producto...', 'Cargando', false, 'spinner-border', false);
                
                // En una implementación real, harías una petición AJAX para obtener los datos completos
                // Aquí simularemos que obtenemos los datos de la fila de la tabla
                setTimeout(() => {
                    removeAlert(loadingAlert);
                    
                    // Find product data from the table row
                    const row = this.closest('tr');
                    const id = row.cells[0].textContent;
                    const imageEl = row.cells[1].querySelector('img');
                    const image = imageEl ? imageEl.src : null;
                    const name = row.cells[2].textContent;
                    const category = row.cells[3].textContent.trim();
                    const price = row.cells[4].textContent;
                    const originalPrice = row.cells[5].textContent !== '-' ? row.cells[5].textContent : 'No disponible';
                    const stockEl = row.cells[6].querySelector('.badge');
                    const stock = stockEl ? stockEl.textContent : '';
                    const featuredEl = row.cells[7].querySelector('.badge');
                    const featured = featuredEl ? featuredEl.textContent : '';
                    
                    // Estos valores serían obtenidos desde la base de datos en una implementación real
                    const description = "Descripción no disponible desde la vista de tabla. En producción, se obtendría de la base de datos.";
                    const tag = "N/A";
                    const collection = "N/A";
                    const stockMin = "5"; // Valor por defecto
                    const alertaStock = "Activada"; // Valor por defecto
                    
                    // Set values in the view modal
                    document.getElementById('detailsName').textContent = name;
                    document.getElementById('detailsCategory').textContent = category;
                    document.getElementById('detailsPrice').textContent = price;
                    document.getElementById('detailsOriginalPrice').textContent = originalPrice;
                    document.getElementById('detailsStock').textContent = stock;
                    document.getElementById('detailsStockMin').textContent = stockMin;
                    document.getElementById('detailsTag').textContent = tag;
                    document.getElementById('detailsCollection').textContent = collection;
                    document.getElementById('detailsFeatured').textContent = featured;
                    document.getElementById('detailsAlertaStock').textContent = alertaStock;
                    document.getElementById('detailsDescription').textContent = description;
                    
                    if (image) {
                        document.getElementById('detailsImage').src = image;
                        document.getElementById('detailsImage').classList.remove('d-none');
                        document.getElementById('noImageMessage').classList.add('d-none');
                    } else {
                        document.getElementById('detailsImage').classList.add('d-none');
                        document.getElementById('noImageMessage').classList.remove('d-none');
                    }
                    
                    // Store ID for edit functionality
                    document.querySelector('.edit-from-details').setAttribute('data-id', id);
                    
                    // Show the modal
                    const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                    viewModal.show();
                    
                    // Show success alert
                    showAlert('success', 'Detalles del producto cargados correctamente.', 'Información disponible');
                }, 500);
            });
        });
        
        // Edit from details view
        document.querySelector('.edit-from-details').addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            
            // Close view modal
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewModal'));
            viewModal.hide();
            
            // Show loading alert
            const loadingAlert = showAlert('info', 'Cargando formulario de edición...', 'Preparando', false, 'spinner-border', false);
            
            // En una implementación real, harías una petición AJAX para cargar los datos
            // Aquí simularemos la carga de datos
            setTimeout(() => {
                removeAlert(loadingAlert);
                
                // Simulamos la carga de datos en el formulario - en realidad se haría con AJAX
                fetch(`obtener_producto.php?id=${productId}`) // Endpoint que devolvería los datos del producto
                    .then(response => response.json())
                    .then(data => {
                        // Aquí cargarías los datos en el formulario
                        // Como es una simulación, avanzamos al siguiente paso
                        showAlert('info', 'En producción, aquí se cargarían los datos del producto en el formulario', 'Simulación');
                    })
                    .catch(error => {
                        showAlert('error', 'Error al cargar los datos del producto', 'Error');
                    });
                
                // En esta versión simplificada, simplemente abrimos el modal
                document.getElementById('modalTitle').textContent = 'Editar Producto';
                document.getElementById('formAction').value = 'update';
                document.getElementById('productId').value = productId;
                
                const productModal = new bootstrap.Modal(document.getElementById('productModal'));
                productModal.show();
            }, 500);
        });

        // Resetear formulario al abrir modal para crear nuevo producto
        const productModal = document.getElementById('productModal');
        productModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            // Si no es un botón de editar, resetear el formulario (es un nuevo producto)
            if (!button || !button.classList.contains('edit-btn')) {
                resetForm();
                
                // Show alert
                showAlert('info', 'Formulario listo para crear un nuevo producto.', 'Nuevo producto');
            }
        });

        // Manejar botones de edición
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Prevenir comportamiento por defecto
                const productId = this.getAttribute('data-id');
                
                // Show loading alert
                const loadingAlert = showAlert('info', 'Cargando datos del producto...', 'Preparando', false, 'spinner-border', false);
                
                // En una implementación real, harías una petición AJAX para cargar los datos
                // Aquí simularemos la carga de datos
                setTimeout(() => {
                    removeAlert(loadingAlert);
                    
                    // Aquí se cargarían los datos del producto en el formulario
                    document.getElementById('modalTitle').textContent = 'Editar Producto';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('productId').value = productId;
                    
                    // En producción, se cargarían todos los campos desde la base de datos
                    showAlert('info', 'En producción, aquí se cargarían los datos del producto en el formulario', 'Simulación');
                    
                    // Mostrar el modal de edición
                    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
                    productModal.show();
                }, 500);
            });
        });

        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Crear Nuevo Producto';
            document.getElementById('formAction').value = 'create';
            document.getElementById('productId').value = '';
            document.getElementById('productForm').reset();
            document.getElementById('imagePreviewContainer').classList.add('d-none');
            document.getElementById('currentImageContainer').classList.add('d-none');
        }
        
        // Submit form handler
        document.getElementById('productForm').addEventListener('submit', function(e) {
            // Mostrar alerta de carga
            showAlert('info', 'Procesando la solicitud...', 'En progreso', false, 'spinner-border', false);
        });
        
        // Process PHP session alerts
        <?php
        if (!empty($_SESSION['alerts'])) {
            foreach ($_SESSION['alerts'] as $alert) {
                echo "showAlert('" . $alert['type'] . "', '" . addslashes($alert['message']) . "', '" . addslashes($alert['title']) . "', " . ($alert['persist'] ? 'true' : 'false') . ", '" . $alert['icon'] . "', " . ($alert['autoDismiss'] ? 'true' : 'false') . ", " . $alert['dismissTime'] . ");\n";
            }
            // Clear alerts after displaying
            $_SESSION['alerts'] = [];
        }
        ?>
        
        // Add animation for dashboard cards
        document.querySelectorAll('.dashboard-stats .card').forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('animated');
            }, index * 100);
        });
    });
    </script>
</body>
</html>