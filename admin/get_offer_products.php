<?php
/**
 * AJAX handler for fetching products in a flash offer
 * 
 * This file retrieves products associated with a flash offer
 * and returns them as JSON for display in a modal
 */

// Define ADMIN_ACCESS constant to control access to included files
define('ADMIN_ACCESS', true);

// Start session for authentication
session_start();

// Include database connection
require_once 'db_config.php';

// Authentication check
// Uncomment this section when ready to implement proper authentication
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Content-Type: application/json');
//     echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
//     exit;
// }

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de oferta no proporcionado']);
    exit;
}

// Get offer ID
$id = (int) $_GET['id'];

// Check if offer exists
$offer_sql = "SELECT * FROM ofertas_flash WHERE id = ?";
$offer_stmt = mysqli_prepare($conn, $offer_sql);
mysqli_stmt_bind_param($offer_stmt, 'i', $id);
mysqli_stmt_execute($offer_stmt);
$offer_result = mysqli_stmt_get_result($offer_stmt);

if (!$offer_result || !mysqli_fetch_assoc($offer_result)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oferta no encontrada']);
    exit;
}

// Get products in this offer
$products_sql = "SELECT p.id, p.nombre, p.precio_actual, p.precio_original, 
                p.descripcion, p.categoria, p.imagen, p.stock, p.etiqueta
                FROM productos p 
                JOIN productos_oferta_flash pof ON p.id = pof.producto_id 
                WHERE pof.oferta_id = ?
                ORDER BY p.nombre";
$products_stmt = mysqli_prepare($conn, $products_sql);
mysqli_stmt_bind_param($products_stmt, 'i', $id);
mysqli_stmt_execute($products_stmt);
$products_result = mysqli_stmt_get_result($products_stmt);
$products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);

// Return products
header('Content-Type: application/json');
echo json_encode(['success' => true, 'products' => $products]);

// Close the connection
mysqli_close($conn);
?>