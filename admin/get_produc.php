<?php
/**
 * Product Detail API Endpoint
 * 
 * Returns JSON data for a specific product by ID
 */

// Define ADMIN_ACCESS constant to control access
define('ADMIN_ACCESS', true);

// Incluir conexión a la base de datos
$conn = require_once 'db_config.php';

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de producto no válido o no proporcionado']);
    exit;
}

$id = (int) $_GET['id'];

// Obtener datos del producto
$sql = "SELECT * FROM productos WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Producto no encontrado']);
    exit;
}

// Obtener datos del producto y devolver como JSON
$product = mysqli_fetch_assoc($result);

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($product);
exit;
?>