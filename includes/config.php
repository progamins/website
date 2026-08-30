<?php
// includes/config.php
// Configuración general del sitio

// URL base del sitio
define('BASE_URL', 'http://localhost:8080');

// Rutas del sistema
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('PRODUCTS_IMAGES_PATH', UPLOADS_PATH . '/productos');
define('CATEGORIES_IMAGES_PATH', UPLOADS_PATH . '/categorias');
define('COLLECTIONS_IMAGES_PATH', UPLOADS_PATH . '/colecciones');

// Variables generales del sitio
define('SITE_NAME', 'Chollo & Glam');
define('SITE_EMAIL', 'info@cholloyglam.com');
define('SITE_PHONE', '+34 912 345 678');

// Configuración de imágenes
define('MAX_IMAGE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('DEFAULT_IMAGE', '/assets/img/default.jpg');

// Configuración de pagos (ejemplo con PayPal)
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_CLIENT_SECRET', 'YOUR_PAYPAL_SECRET');
define('PAYPAL_SANDBOX', true); // Cambiar a false en producción

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de correo
define('SMTP_HOST', 'smtp.tuservidor.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tunombre@tuservidor.com');
define('SMTP_PASSWORD', 'tu_password');
define('SMTP_FROM', 'info@cholloyglam.com');
define('SMTP_FROM_NAME', 'Chollo & Glam');

// IVA
define('IVA', 21); // 21% para España
?>