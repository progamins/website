<?php
// includes/functions.php
// Funciones generales para el sistema

// Incluir archivos necesarios
require_once 'config.php';
require_once 'db.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Función para mostrar alertas
 * @param string $mensaje Mensaje a mostrar
 * @param string $tipo Tipo de alerta (success, danger, warning, info)
 * @return string HTML con la alerta
 */
function alerta($mensaje, $tipo = 'info')
{
    return '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">
                ' . $mensaje . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Función para redireccionar
 * @param string $url URL a la que redireccionar
 */
function redireccionar($url)
{
    header('Location: ' . $url);
    exit();
}

/**
 * Función para crear un slug desde un texto
 * @param string $texto Texto a convertir en slug
 * @return string Slug generado
 */
function crear_slug($texto)
{
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', ' ', $texto);
    $texto = preg_replace('/\s/', '-', $texto);
    return $texto;
}

/**
 * Función para subir una imagen
 * @param array $archivo $_FILES['nombre_del_campo']
 * @param string $directorio Directorio donde guardar la imagen
 * @return string|false Nombre del archivo o false en caso de error
 */
function subir_imagen($archivo, $directorio)
{
    // Verificar que el directorio exista
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }

    // Verificar si es una imagen válida
    if (!in_array($archivo['type'], ALLOWED_IMAGE_TYPES)) {
        return false;
    }

    // Verificar tamaño
    if ($archivo['size'] > MAX_IMAGE_SIZE) {
        return false;
    }

    // Generar nombre único
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid() . '.' . $extension;
    $ruta_destino = $directorio . '/' . $nombre_archivo;

    // Mover el archivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return $nombre_archivo;
    }

    return false;
}

/**
 * Función para formatear precio
 * @param float $precio Precio a formatear
 * @return string Precio formateado
 */
function formato_precio($precio)
{
    return number_format($precio, 2, ',', '.') . '€';
}

/**
 * Función para verificar si el usuario está logeado
 * @return bool true si está logeado, false si no
 */
function esta_logeado()
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Función para verificar si el usuario es administrador
 * @return bool true si es administrador, false si no
 */
function es_admin()
{
    return esta_logeado() && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Función para obtener el usuario actual
 * @return array|null Datos del usuario o null si no está logeado
 */
function usuario_actual()
{
    if (!esta_logeado()) {
        return null;
    }

    $usuario_id = $_SESSION['usuario_id'];
    $sql = "SELECT * FROM usuarios WHERE id = ?";

    return obtener_registro($sql, [$usuario_id]);
}

/**
 * Función para limpiar datos de entrada
 * @param string $dato Dato a limpiar
 * @return string Dato limpio
 */
function limpiar_dato($dato)
{
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

/**
 * Función para verificar si un producto está en favoritos
 * @param int $producto_id ID del producto
 * @return bool true si está en favoritos, false si no
 */
function en_favoritos($producto_id)
{
    if (!esta_logeado()) {
        return false;
    }

    $usuario_id = $_SESSION['usuario_id'];
    $sql = "SELECT id FROM lista_deseos WHERE usuario_id = ? AND producto_id = ?";
    $result = obtener_registro($sql, [$usuario_id, $producto_id]);

    return !empty($result);
}

/**
 * Función para obtener las categorías
 * @param bool $solo_activas Obtener solo categorías activas
 * @return array Categorías
 */
function obtener_categorias($solo_activas = true)
{
    $sql = "SELECT * FROM categorias";

    if ($solo_activas) {
        $sql .= " WHERE activa = 1";
    }

    $sql .= " ORDER BY orden ASC";

    return obtener_registros($sql);
}

/**
 * Función para obtener los productos destacados
 * @param int $limite Número de productos a obtener
 * @return array Productos destacados
 */
function obtener_productos_destacados($limite = 8)
{
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE p.destacado = 1 AND p.activo = 1
            ORDER BY p.fecha_creacion DESC
            LIMIT ?";

    return obtener_registros($sql, [$limite]);
}

/**
 * Función para obtener productos en oferta
 * @param int $limite Número de productos a obtener
 * @return array Productos en oferta
 */
function obtener_productos_oferta($limite = 4)
{
    $sql = "SELECT p.*, c.nombre as categoria_nombre,
            (SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones WHERE producto_id = p.id) as rating,
            (SELECT COUNT(*) FROM valoraciones WHERE producto_id = p.id) as num_valoraciones
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE p.en_oferta = 1 AND p.activo = 1 AND p.precio_oferta IS NOT NULL
            ORDER BY p.porcentaje_descuento DESC
            LIMIT ?";

    return obtener_registros($sql, [$limite]);
}

/**
 * Función para obtener las colecciones
 * @param bool $solo_activas Obtener solo colecciones activas
 * @return array Colecciones
 */
function obtener_colecciones($solo_activas = true)
{
    $sql = "SELECT * FROM colecciones";

    if ($solo_activas) {
        $sql .= " WHERE activa = 1";
    }

    $sql .= " ORDER BY id DESC";

    return obtener_registros($sql);
}

/**
 * Función para obtener los testimonios
 * @param int $limite Número de testimonios a obtener
 * @return array Testimonios
 */
function obtener_testimonios($limite = 2)
{
    $sql = "SELECT * FROM testimonios WHERE activo = 1 ORDER BY id DESC LIMIT ?";
    return obtener_registros($sql, [$limite]);
}

/**
 * Función para generar una referencia única para pedidos
 * @return string Referencia generada
 */
function generar_referencia_pedido()
{
    $prefijo = 'CG-';
    $fecha = date('Ymd');
    $aleatorio = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefijo . $fecha . '-' . $aleatorio;
}

/**
 * Función para verificar si hay una oferta flash activa
 * @return array|null Datos de la oferta o null si no hay
 */
function obtener_oferta_flash_activa()
{
    $ahora = date('Y-m-d H:i:s');
    $sql = "SELECT * FROM ofertas_flash 
            WHERE activa = 1 AND fecha_inicio <= ? AND fecha_fin >= ? 
            ORDER BY id DESC LIMIT 1";

    return obtener_registro($sql, [$ahora, $ahora]);
}

/**
 * Función para obtener los productos de una oferta flash
 * @param int $oferta_id ID de la oferta
 * @return array Productos de la oferta
 */
function obtener_productos_oferta_flash($oferta_id)
{
    $sql = "SELECT p.*, ofp.precio_oferta, ofp.porcentaje_descuento, c.nombre as categoria_nombre,
            (SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones WHERE producto_id = p.id) as rating,
            (SELECT COUNT(*) FROM valoraciones WHERE producto_id = p.id) as num_valoraciones
            FROM oferta_flash_productos ofp
            INNER JOIN productos p ON ofp.producto_id = p.id
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE ofp.oferta_id = ? AND p.activo = 1
            ORDER BY ofp.id ASC";

    return obtener_registros($sql, [$oferta_id]);
}

/**
 * Función para inicializar el carrito
 */
function inicializar_carrito()
{
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [
            'productos' => [],
            'total' => 0,
            'cantidad' => 0
        ];
    }
}

/**
 * Función para añadir un producto al carrito
 * @param int $producto_id ID del producto
 * @param int $cantidad Cantidad del producto
 * @return bool true si se añadió correctamente, false si no
 */
function agregar_al_carrito($producto_id, $cantidad = 1)
{
    inicializar_carrito();

    $sql = "SELECT * FROM productos WHERE id = ? AND activo = 1";
    $producto = obtener_registro($sql, [$producto_id]);

    if (!$producto) {
        return false;
    }

    // Verificar si ya existe en el carrito
    $existe = false;
    foreach ($_SESSION['carrito']['productos'] as $key => $item) {
        if ($item['id'] == $producto_id) {
            $_SESSION['carrito']['productos'][$key]['cantidad'] += $cantidad;
            $existe = true;
            break;
        }
    }

    // Si no existe, añadirlo
    if (!$existe) {
        $precio = !empty($producto['precio_oferta']) ? $producto['precio_oferta'] : $producto['precio_normal'];

        $_SESSION['carrito']['productos'][] = [
            'id' => $producto['id'],
            'nombre' => $producto['nombre'],
            'imagen' => $producto['imagen_principal'],
            'precio' => $precio,
            'cantidad' => $cantidad,
            'subtotal' => $precio * $cantidad
        ];
    }

    // Actualizar totales
    actualizar_totales_carrito();

    return true;
}

/**
 * Función para actualizar los totales del carrito
 */
function actualizar_totales_carrito()
{
    $total = 0;
    $cantidad = 0;

    foreach ($_SESSION['carrito']['productos'] as $key => $item) {
        $_SESSION['carrito']['productos'][$key]['subtotal'] = $item['precio'] * $item['cantidad'];
        $total += $_SESSION['carrito']['productos'][$key]['subtotal'];
        $cantidad += $item['cantidad'];
    }

    $_SESSION['carrito']['total'] = $total;
    $_SESSION['carrito']['cantidad'] = $cantidad;
}

/**
 * Función para eliminar un producto del carrito
 * @param int $producto_id ID del producto
 */
function eliminar_del_carrito($producto_id)
{
    inicializar_carrito();

    foreach ($_SESSION['carrito']['productos'] as $key => $item) {
        if ($item['id'] == $producto_id) {
            unset($_SESSION['carrito']['productos'][$key]);
            break;
        }
    }

    // Reindexar el array
    $_SESSION['carrito']['productos'] = array_values($_SESSION['carrito']['productos']);

    // Actualizar totales
    actualizar_totales_carrito();
}
/**
 * Función para vaciar el carrito
 */
function vaciar_carrito()
{
    $_SESSION['carrito'] = [
        'productos' => [],
        'total' => 0,
        'cantidad' => 0
    ];
}

/**
 * Función para obtener el número de productos en el carrito
 * @return int Número de productos
 */
function obtener_cantidad_carrito()
{
    inicializar_carrito();
    return $_SESSION['carrito']['cantidad'];
}

/**
 * Función para obtener el total del carrito
 * @return float Total del carrito
 */
function obtener_total_carrito()
{
    inicializar_carrito();
    return $_SESSION['carrito']['total'];
}

/**
 * Función para aplicar un cupón de descuento
 * @param string $codigo Código del cupón
 * @return array Resultado de la operación
 */
function aplicar_cupon($codigo)
{
    inicializar_carrito();

    $codigo = limpiar_dato($codigo);
    $ahora = date('Y-m-d');

    $sql = "SELECT * FROM cupones 
            WHERE codigo = ? AND activo = 1 
            AND (fecha_inicio IS NULL OR fecha_inicio <= ?) 
            AND (fecha_fin IS NULL OR fecha_fin >= ?) 
            AND (uso_maximo IS NULL OR uso_actual < uso_maximo)";

    $cupon = obtener_registro($sql, [$codigo, $ahora, $ahora]);

    if (!$cupon) {
        return [
            'exito' => false,
            'mensaje' => 'El cupón no es válido o ha expirado'
        ];
    }

    // Verificar si cumple con el mínimo de compra
    if ($cupon['minimo_compra'] > 0 && $_SESSION['carrito']['total'] < $cupon['minimo_compra']) {
        return [
            'exito' => false,
            'mensaje' => 'El pedido mínimo para usar este cupón es de ' . formato_precio($cupon['minimo_compra'])
        ];
    }

    // Calcular el descuento
    $descuento = 0;
    if ($cupon['tipo'] === 'porcentaje') {
        $descuento = $_SESSION['carrito']['total'] * ($cupon['valor'] / 100);
    } else {
        $descuento = $cupon['valor'];
        // El descuento no puede ser mayor que el total
        if ($descuento > $_SESSION['carrito']['total']) {
            $descuento = $_SESSION['carrito']['total'];
        }
    }

    $_SESSION['cupon'] = [
        'id' => $cupon['id'],
        'codigo' => $cupon['codigo'],
        'tipo' => $cupon['tipo'],
        'valor' => $cupon['valor'],
        'descuento' => $descuento
    ];

    return [
        'exito' => true,
        'mensaje' => 'Cupón aplicado correctamente',
        'descuento' => $descuento
    ];
}

/**
 * Función para eliminar un cupón aplicado
 */
function eliminar_cupon()
{
    if (isset($_SESSION['cupon'])) {
        unset($_SESSION['cupon']);
    }
}

/**
 * Función para obtener el descuento del cupón
 * @return float Descuento del cupón
 */
function obtener_descuento_cupon()
{
    return isset($_SESSION['cupon']) ? $_SESSION['cupon']['descuento'] : 0;
}

/**
 * Función para aumentar el uso de un cupón
 * @param int $cupon_id ID del cupón
 */
function aumentar_uso_cupon($cupon_id)
{
    $sql = "UPDATE cupones SET uso_actual = uso_actual + 1 WHERE id = ?";
    actualizar_eliminar($sql, [$cupon_id]);
}

/**
 * Función para registrar un pedido
 * @param array $datos Datos del pedido
 * @return int|bool ID del pedido o false en caso de error
 */
function registrar_pedido($datos)
{
    global $conn;

    try {
        $conn->begin_transaction();

        // Datos del usuario
        $usuario_id = $datos['usuario_id'];
        $total = $datos['total'];
        $referencia = generar_referencia_pedido();

        // Insertar el pedido
        $sql = "INSERT INTO pedidos (usuario_id, referencia, total, estado, nombre_envio, 
                direccion_envio, codigo_postal_envio, ciudad_envio, provincia_envio, 
                pais_envio, telefono_envio, notas, metodo_pago) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $usuario_id,
            $referencia,
            $total,
            'pendiente',
            $datos['nombre'],
            $datos['direccion'],
            $datos['codigo_postal'],
            $datos['ciudad'],
            $datos['provincia'],
            $datos['pais'],
            $datos['telefono'],
            $datos['notas'] ?? '',
            $datos['metodo_pago']
        ];

        $pedido_id = insertar($sql, $params);

        // Insertar los detalles del pedido
        foreach ($_SESSION['carrito']['productos'] as $producto) {
            $sql = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                    VALUES (?, ?, ?, ?, ?)";

            $params = [
                $pedido_id,
                $producto['id'],
                $producto['cantidad'],
                $producto['precio'],
                $producto['subtotal']
            ];

            insertar($sql, $params);

            // Actualizar stock del producto
            $sql = "UPDATE productos SET stock = stock - ? WHERE id = ?";
            actualizar_eliminar($sql, [$producto['cantidad'], $producto['id']]);
        }

        // Si hay cupón, aumentar su uso
        if (isset($_SESSION['cupon'])) {
            aumentar_uso_cupon($_SESSION['cupon']['id']);
        }

        $conn->commit();

        // Limpiar carrito y cupón
        vaciar_carrito();
        eliminar_cupon();

        return $pedido_id;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Función para obtener un producto por su ID
 * @param int $id ID del producto
 * @return array|null Datos del producto o null si no existe
 */
function obtener_producto($id)
{
    $sql = "SELECT p.*, c.nombre as categoria_nombre, col.nombre as coleccion_nombre,
            (SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones WHERE producto_id = p.id) as rating,
            (SELECT COUNT(*) FROM valoraciones WHERE producto_id = p.id) as num_valoraciones
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN colecciones col ON p.coleccion_id = col.id
            WHERE p.id = ?";

    return obtener_registro($sql, [$id]);
}

/**
 * Función para obtener productos por categoría
 * @param int $categoria_id ID de la categoría
 * @param int $pagina Número de página
 * @param int $por_pagina Productos por página
 * @return array Productos de la categoría
 */
function obtener_productos_por_categoria($categoria_id, $pagina = 1, $por_pagina = 12)
{
    $offset = ($pagina - 1) * $por_pagina;

    $sql = "SELECT p.*, c.nombre as categoria_nombre,
            (SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones WHERE producto_id = p.id) as rating,
            (SELECT COUNT(*) FROM valoraciones WHERE producto_id = p.id) as num_valoraciones
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE p.categoria_id = ? AND p.activo = 1
            ORDER BY p.destacado DESC, p.fecha_creacion DESC
            LIMIT ? OFFSET ?";

    return obtener_registros($sql, [$categoria_id, $por_pagina, $offset]);
}

/**
 * Función para contar productos por categoría
 * @param int $categoria_id ID de la categoría
 * @return int Número de productos
 */
function contar_productos_por_categoria($categoria_id)
{
    $sql = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1";
    $resultado = obtener_registro($sql, [$categoria_id]);
    return $resultado['total'];
}

/**
 * Función para obtener las imágenes de un producto
 * @param int $producto_id ID del producto
 * @return array Imágenes del producto
 */
function obtener_imagenes_producto($producto_id)
{
    $sql = "SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY orden ASC";
    return obtener_registros($sql, [$producto_id]);
}

/**
 * Función para obtener productos relacionados
 * @param int $producto_id ID del producto
 * @param int $limite Número de productos a obtener
 * @return array Productos relacionados
 */
function obtener_productos_relacionados($producto_id, $limite = 4)
{
    // Primero obtenemos la categoría del producto
    $producto = obtener_producto($producto_id);

    if (!$producto) {
        return [];
    }

    $categoria_id = $producto['categoria_id'];

    $sql = "SELECT p.*, c.nombre as categoria_nombre,
            (SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones WHERE producto_id = p.id) as rating,
            (SELECT COUNT(*) FROM valoraciones WHERE producto_id = p.id) as num_valoraciones
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE p.categoria_id = ? AND p.id != ? AND p.activo = 1
            ORDER BY RAND()
            LIMIT ?";

    return obtener_registros($sql, [$categoria_id, $producto_id, $limite]);
}

/**
 * Función para guardar una valoración
 * @param array $datos Datos de la valoración
 * @return int|bool ID de la valoración o false en caso de error
 */
function guardar_valoracion($datos)
{
    $sql = "INSERT INTO valoraciones (producto_id, usuario_id, puntuacion, comentario, activa) 
            VALUES (?, ?, ?, ?, ?)";

    $params = [
        $datos['producto_id'],
        $datos['usuario_id'],
        $datos['puntuacion'],
        $datos['comentario'],
        0 // Por defecto inactiva hasta revisión
    ];

    return insertar($sql, $params);
}

/**
 * Función para registrar un usuario
 * @param array $datos Datos del usuario
 * @return int|bool ID del usuario o false en caso de error
 */
function registrar_usuario($datos)
{
    // Verificar si el email ya existe
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $existe = obtener_registro($sql, [$datos['email']]);

    if ($existe) {
        return false;
    }

    // Hash de la contraseña
    $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre, apellidos, email, password, telefono, rol) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $params = [
        $datos['nombre'],
        $datos['apellidos'],
        $datos['email'],
        $password_hash,
        $datos['telefono'] ?? '',
        'cliente' // Por defecto cliente
    ];

    return insertar($sql, $params);
}

/**
 * Función para iniciar sesión
 * @param string $email Email del usuario
 * @param string $password Contraseña del usuario
 * @return array|bool Datos del usuario o false en caso de error
 */
function login_usuario($email, $password)
{
    $sql = "SELECT * FROM usuarios WHERE email = ? AND activo = 1";
    $usuario = obtener_registro($sql, [$email]);

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        return false;
    }

    // Actualizar última sesión
    $sql = "UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?";
    actualizar_eliminar($sql, [$usuario['id']]);

    // Guardar datos en sesión
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_rol'] = $usuario['rol'];

    return $usuario;
}

/**
 * Función para cerrar sesión
 */
function logout_usuario()
{
    // Eliminar variables de sesión
    unset($_SESSION['usuario_id']);
    unset($_SESSION['usuario_nombre']);
    unset($_SESSION['usuario_email']);
    unset($_SESSION['usuario_rol']);

    // Destruir la sesión
    session_destroy();
}

/**
 * Función para buscar productos
 * @param string $termino Término de búsqueda
 * @param int $pagina Número de página
 * @param int $por_pagina Productos por página
 * @return array Productos encontrados
 */
function buscar_productos($termino, $pagina = 1, $por_pagina = 12)
{
    $offset = ($pagina - 1) * $por_pagina;
    $termino = "%$termino%";

    $sql = "SELECT p.*, c.nombre as categoria_nombre,
            (SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones WHERE producto_id = p.id) as rating,
            (SELECT COUNT(*) FROM valoraciones WHERE producto_id = p.id) as num_valoraciones
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.descripcion_corta LIKE ?) 
            AND p.activo = 1
            ORDER BY p.destacado DESC, p.fecha_creacion DESC
            LIMIT ? OFFSET ?";

    return obtener_registros($sql, [$termino, $termino, $termino, $por_pagina, $offset]);
}

/**
 * Función para contar resultados de búsqueda
 * @param string $termino Término de búsqueda
 * @return int Número de productos encontrados
 */
function contar_resultados_busqueda($termino)
{
    $termino = "%$termino%";

    $sql = "SELECT COUNT(*) as total FROM productos 
            WHERE (nombre LIKE ? OR descripcion LIKE ? OR descripcion_corta LIKE ?) 
            AND activo = 1";

    $resultado = obtener_registro($sql, [$termino, $termino, $termino]);
    return $resultado['total'];
}

/**
 * Función para añadir a favoritos
 * @param int $producto_id ID del producto
 * @param int $usuario_id ID del usuario
 * @return bool true si se añadió correctamente, false si no
 */
function agregar_a_favoritos($producto_id, $usuario_id)
{
    // Verificar si ya existe
    $sql = "SELECT id FROM lista_deseos WHERE usuario_id = ? AND producto_id = ?";
    $existe = obtener_registro($sql, [$usuario_id, $producto_id]);

    if ($existe) {
        return true; // Ya está en favoritos
    }

    $sql = "INSERT INTO lista_deseos (usuario_id, producto_id) VALUES (?, ?)";
    return insertar($sql, [$usuario_id, $producto_id]) > 0;
}

/**
 * Función para eliminar de favoritos
 * @param int $producto_id ID del producto
 * @param int $usuario_id ID del usuario
 * @return bool true si se eliminó correctamente, false si no
 */
function eliminar_de_favoritos($producto_id, $usuario_id)
{
    $sql = "DELETE FROM lista_deseos WHERE usuario_id = ? AND producto_id = ?";
    return actualizar_eliminar($sql, [$usuario_id, $producto_id]) > 0;
}

/**
 * Función para obtener favoritos de un usuario
 * @param int $usuario_id ID del usuario
 * @return array Productos favoritos
 */
function obtener_favoritos($usuario_id)
{
    $sql = "SELECT p.*, c.nombre as categoria_nombre, ld.fecha_creacion as fecha_agregado
            FROM lista_deseos ld
            INNER JOIN productos p ON ld.producto_id = p.id
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE ld.usuario_id = ?
            ORDER BY ld.fecha_creacion DESC";

    return obtener_registros($sql, [$usuario_id]);
}

/**
 * Función para suscribir al newsletter
 * @param string $email Email del suscriptor
 * @return bool true si se suscribió correctamente, false si no
 */
function suscribir_newsletter($email)
{
    // Verificar si ya existe
    $sql = "SELECT id FROM suscriptores WHERE email = ?";
    $existe = obtener_registro($sql, [$email]);

    if ($existe) {
        return false; // Ya está suscrito
    }

    $sql = "INSERT INTO suscriptores (email) VALUES (?)";
    return insertar($sql, [$email]) > 0;
}

/**
 * Función para enviar email
 * @param string $destinatario Email del destinatario
 * @param string $asunto Asunto del email
 * @param string $mensaje Contenido del email
 * @return bool true si se envió correctamente, false si no
 */
function enviar_email($destinatario, $asunto, $mensaje)
{
    // Esta función es un placeholder. En un entorno real, usarías PHPMailer o similar

    // Cabeceras
    $cabeceras = 'MIME-Version: 1.0' . "\r\n";
    $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    $cabeceras .= 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>' . "\r\n";

    // Enviar email
    return mail($destinatario, $asunto, $mensaje, $cabeceras);
}

/**
 * Función para obtener los pedidos de un usuario
 * @param int $usuario_id ID del usuario
 * @return array Pedidos del usuario
 */
function obtener_pedidos_usuario($usuario_id)
{
    $sql = "SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha_creacion DESC";
    return obtener_registros($sql, [$usuario_id]);
}

/**
 * Función para obtener los detalles de un pedido
 * @param int $pedido_id ID del pedido
 * @return array Detalles del pedido
 */
function obtener_detalles_pedido($pedido_id)
{
    $sql = "SELECT pd.*, p.nombre, p.imagen_principal
            FROM pedido_detalles pd
            INNER JOIN productos p ON pd.producto_id = p.id
            WHERE pd.pedido_id = ?";

    return obtener_registros($sql, [$pedido_id]);
}

/**
 * Función para actualizar los datos de un usuario
 * @param int $usuario_id ID del usuario
 * @param array $datos Datos a actualizar
 * @return bool true si se actualizó correctamente, false si no
 */
function actualizar_usuario($usuario_id, $datos)
{
    $campos = [];
    $valores = [];

    foreach ($datos as $campo => $valor) {
        if ($campo !== 'id' && $campo !== 'password') {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
    }

    // Si hay contraseña, actualizarla
    if (isset($datos['password']) && !empty($datos['password'])) {
        $campos[] = "password = ?";
        $valores[] = password_hash($datos['password'], PASSWORD_DEFAULT);
    }

    $valores[] = $usuario_id; // Para el WHERE

    $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";

    return actualizar_eliminar($sql, $valores) !== false;
}

/**
 * Función para generar token de recuperación de contraseña
 * @param string $email Email del usuario
 * @return string|bool Token generado o false si el email no existe
 */
function generar_token_recuperacion($email)
{
    $sql = "SELECT id FROM usuarios WHERE email = ? AND activo = 1";
    $usuario = obtener_registro($sql, [$email]);

    if (!$usuario) {
        return false;
    }

    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $sql = "UPDATE usuarios SET token_recuperacion = ?, fecha_token = ? WHERE id = ?";
    $actualizado = actualizar_eliminar($sql, [$token, $expiracion, $usuario['id']]);

    if ($actualizado) {
        return $token;
    }

    return false;
}

/**
 * Función para verificar token de recuperación
 * @param string $token Token a verificar
 * @return array|bool Datos del usuario o false si el token no es válido
 */
function verificar_token_recuperacion($token)
{
    $ahora = date('Y-m-d H:i:s');

    $sql = "SELECT id, email FROM usuarios 
            WHERE token_recuperacion = ? AND fecha_token > ? AND activo = 1";

    return obtener_registro($sql, [$token, $ahora]);
}

/**
 * Función para cambiar contraseña
 * @param int $usuario_id ID del usuario
 * @param string $password Nueva contraseña
 * @return bool true si se cambió correctamente, false si no
 */
function cambiar_password($usuario_id, $password)
{
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE usuarios SET password = ?, token_recuperacion = NULL, fecha_token = NULL WHERE id = ?";

    return actualizar_eliminar($sql, [$password_hash, $usuario_id]) !== false;
}

/**
 * Función para paginación
 * @param int $total_registros Total de registros
 * @param int $pagina_actual Página actual
 * @param int $registros_por_pagina Registros por página
 * @param string $url_base URL base para los enlaces
 * @return string HTML con la paginación
 */
function paginacion($total_registros, $pagina_actual, $registros_por_pagina, $url_base)
{
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    if ($total_paginas <= 1) {
        return '';
    }

    $html = '<nav aria-label="Paginación"><ul class="pagination">';

    // Anterior
    if ($pagina_actual > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_base . ($pagina_actual - 1) . '">Anterior</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
    }

    // Números de página
    $desde = max(1, $pagina_actual - 2);
    $hasta = min($total_paginas, $pagina_actual + 2);

    for ($i = $desde; $i <= $hasta; $i++) {
        if ($i == $pagina_actual) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url_base . $i . '">' . $i . '</a></li>';
        }
    }

    // Siguiente
    if ($pagina_actual < $total_paginas) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_base . ($pagina_actual + 1) . '">Siguiente</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Siguiente</span></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}