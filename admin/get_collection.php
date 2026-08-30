<?php
/**
 * AJAX handler for fetching collection data for editing
 * 
 * This file retrieves collection data from the database and returns it as JSON
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
    echo json_encode(['success' => false, 'message' => 'ID de colección no proporcionado']);
    exit;
}

// Get collection ID
$id = (int) $_GET['id'];

// Get collection data
$sql = "SELECT * FROM colecciones WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $collection = mysqli_fetch_assoc($result)) {
    // Return success with collection data
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'collection' => $collection]);
} else {
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Colección no encontrada']);
}

// Close the connection
mysqli_close($conn);
?>