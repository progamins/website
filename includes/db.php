<?php
// includes/db.php
// Archivo para la conexión a la base de datos

define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Cambia esto por tu usuario de MySQL
define('DB_PASS', '');         // Cambia esto por tu contraseña de MySQL
define('DB_NAME', 'chollo_glam');

// Crear conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer charset
$conn->set_charset("utf8mb4");

// Función para ejecutar consultas seguras con prepared statements
function consulta($sql, $params = [])
{
    global $conn;

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error en la consulta: " . $conn->error);
    }

    if (!empty($params)) {
        $tipos = '';
        $valores = [];

        foreach ($params as $param) {
            if (is_int($param)) {
                $tipos .= 'i';
            } elseif (is_float($param)) {
                $tipos .= 'd';
            } elseif (is_string($param)) {
                $tipos .= 's';
            } else {
                $tipos .= 'b';
            }
            $valores[] = $param;
        }

        $referencias = [];
        $referencias[] = &$tipos;

        for ($i = 0; $i < count($valores); $i++) {
            $referencias[] = &$valores[$i];
        }

        call_user_func_array([$stmt, 'bind_param'], $referencias);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result;
}

// Función para obtener un solo registro
function obtener_registro($sql, $params = [])
{
    $result = consulta($sql, $params);
    return $result->fetch_assoc();
}

// Función para obtener múltiples registros
function obtener_registros($sql, $params = [])
{
    $result = consulta($sql, $params);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para insertar y obtener el ID
function insertar($sql, $params = [])
{
    global $conn;
    consulta($sql, $params);
    return $conn->insert_id;
}

// Función para actualizar o eliminar y obtener filas afectadas
function actualizar_eliminar($sql, $params = [])
{
    global $conn;
    consulta($sql, $params);
    return $conn->affected_rows;
}

// Función para escapar cadenas (uso cuando no se puede usar prepared statements)
function escapar($string)
{
    global $conn;
    return $conn->real_escape_string($string);
}
?>