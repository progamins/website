-- Base de datos Chollo & Glam
CREATE DATABASE IF NOT EXISTS chollo_glam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chollo_glam;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    activa TINYINT(1) DEFAULT 1,
    meta_titulo VARCHAR(200),
    meta_descripcion VARCHAR(300),
    url_amigable VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de colecciones
CREATE TABLE IF NOT EXISTS colecciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen VARCHAR(255),
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    categoria_id INT,
    coleccion_id INT,
    precio_actual DECIMAL(10,2) NOT NULL,
    precio_original DECIMAL(10,2),
    descripcion TEXT,
    etiqueta VARCHAR(50),
    imagen VARCHAR(255),
    tipo VARCHAR(100),
    stock INT DEFAULT 0,
    destacado TINYINT(1) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    valoracion DECIMAL(3,1) DEFAULT 4.5,
    num_valoraciones INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (coleccion_id) REFERENCES colecciones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de ofertas flash
CREATE TABLE IF NOT EXISTS ofertas_flash (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tiempo_fin DATETIME NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de productos en oferta flash
CREATE TABLE IF NOT EXISTS productos_oferta_flash (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oferta_id INT NOT NULL,
    producto_id INT NOT NULL,
    FOREIGN KEY (oferta_id) REFERENCES ofertas_flash(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    rol ENUM('admin', 'cliente') DEFAULT 'cliente',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    referencia VARCHAR(50),
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    nombre_envio VARCHAR(200),
    direccion_envio TEXT,
    ciudad_envio VARCHAR(100),
    codigo_postal VARCHAR(10),
    telefono_envio VARCHAR(20),
    notas TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de detalles de pedido
CREATE TABLE IF NOT EXISTS pedido_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de testimonios
CREATE TABLE IF NOT EXISTS testimonios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cliente VARCHAR(100) NOT NULL,
    comentario TEXT,
    valoracion DECIMAL(3,1) DEFAULT 5.0,
    fecha DATE,
    foto_cliente VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de valoraciones
CREATE TABLE IF NOT EXISTS valoraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT,
    puntuacion INT NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
    comentario TEXT,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de instagram feed
CREATE TABLE IF NOT EXISTS instagram_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imagen VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de lista de deseos
CREATE TABLE IF NOT EXISTS lista_deseos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de suscriptores newsletter
CREATE TABLE IF NOT EXISTS suscriptores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de SEO de productos
CREATE TABLE IF NOT EXISTS producto_seo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    meta_titulo VARCHAR(200),
    meta_descripcion VARCHAR(300),
    url_amigable VARCHAR(100),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de historial de precios
CREATE TABLE IF NOT EXISTS producto_historial_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- INSERTAR DATOS DE EJEMPLO
-- ============================================

-- Categorías
INSERT INTO categorias (nombre, descripcion, imagen, activa, url_amigable) VALUES
('Collares', 'Collares artesanales de diseño exclusivo', 'uploads/categorias/collares.jpg', 1, 'collares'),
('Aretes', 'Aretes elegantes para toda ocasión', 'uploads/categorias/aretes.jpg', 1, 'aretes'),
('Pulseras', 'Pulseras tejidas y de plata 925', 'uploads/categorias/pulseras.jpg', 1, 'pulseras'),
('Anillos', 'Anillos de diseño único y artesanal', 'uploads/categorias/anillos.jpg', 1, 'anillos'),
('Premium', 'Piezas exclusivas de colección limitada', 'uploads/categorias/premium.jpg', 1, 'premium'),
('Ofertas', 'Productos con descuento especial', 'uploads/categorias/ofertas.jpg', 1, 'ofertas');

-- Colecciones
INSERT INTO colecciones (nombre, imagen, descripcion) VALUES
('Andes Dorados', 'uploads/colecciones/andes_dorados.jpg', 'Inspirada en la majestuosidad de los Andes, esta colección fusiona el oro tradicional con diseños contemporáneos.'),
('Amazonía Mística', 'uploads/colecciones/amazonia.jpg', 'Piezas que capturan la esencia de la selva amazónica con gemas naturales y motivos vegetales.'),
('Costa Brilla', 'uploads/colecciones/costa.jpg', 'Diseños inspirados en la costa peruana con conchas, corales y tonos azules.');

-- Productos
INSERT INTO productos (nombre, categoria_id, coleccion_id, precio_actual, precio_original, descripcion, etiqueta, imagen, tipo, stock, destacado, activo, valoracion, num_valoraciones) VALUES
('Collar Sol de Lima', 1, 1, 45.99, 69.99, 'Collar artesanal inspirado en el sol de Lima, fabricado con plata 925 y baño de oro de 18k. Pieza única con detalles grabados a mano.', 'Más Vendido', 'uploads/productos/collar_sol_lima.jpg', 'Collares', 25, 1, 1, 4.8, 127),
('Aretes Luna Cusqueña', 2, 1, 29.99, 39.99, 'Aretes con forma de luna creciente, inspirados en la arquitectura colonial de Cusco. Plata 925 con esmalte artesanal.', 'Nuevo', 'uploads/productos/aretes_luna_cusqueña.jpg', 'Aretes', 30, 1, 1, 4.7, 89),
('Pulsera Río Amazonas', 3, 2, 34.99, 49.99, 'Pulsera tejida a mano con piedras semipreciosas amazónicas. Cada pieza es única y cuenta la historia de la selva.', 'Edición Limitada', 'uploads/productos/pulsera_rio_amazonas.jpg', 'Pulseras', 15, 1, 1, 4.9, 203),
('Anillo Ocopa Dorado', 4, 1, 59.99, 79.99, 'Anillo de diseño artesanal con baño de oro 18k, inspirado en los tejados de Ocopa. Resistente y elegante.', 'Exclusivo', 'uploads/productos/anillo_ocopa_dorado.jpg', 'Anillos', 20, 1, 1, 4.6, 156),
('Collar Versalles Andino', 1, 3, 55.00, 85.00, 'Collar premium con cristales de cuarzo rosa y baño de platino. Diseño que fusiona lo barroco con lo andino.', 'Premium', 'uploads/productos/collar_versalles_andino.jpg', 'Collares', 10, 1, 1, 4.9, 78),
('Aretes Mariposa Nazca', 2, 2, 38.50, 55.00, 'Aretes inspirados en las líneas de Nazca con forma de colibrí. Plata 925 con turquesa natural.', NULL, 'uploads/productos/aretes_nazca.jpg', 'Aretes', 18, 0, 1, 4.4, 62),
('Pulsera Machu Picchu', 3, 1, 42.00, 58.00, 'Pulsera con dije del sol naciente y detalles de la ciudadela. Cadenas de plata 925 intercaladas con cuentas de obsidiana.', NULL, 'uploads/productos/pulsera_machu_picchu.jpg', 'Pulseras', 22, 0, 1, 4.5, 145),
('Anillo Nazca Star', 4, 3, 48.00, 65.00, 'Anillo con incrustación de lapislázuli y detalles tallados a mano. Inspirado en las constelaciones de Nazca.', NULL, 'uploads/productos/anillo_nazca_star.jpg', 'Anillos', 12, 0, 1, 4.3, 34),
('Collar Pacífico Dorado', 1, 3, 62.00, 90.00, 'Collar premium con pendientes de ámbar del Pacífico y cadena de oro 18k. Diseño exclusivo de colección limitada.', 'Premium', 'uploads/productos/collar_pacifico_dorado.jpg', 'Collares', 8, 1, 1, 4.8, 91),
('Aretes Cielo Arequipa', 2, 1, 32.00, 45.00, 'Aretes con tonos celestes y perlas de agua dulce. Inspirados en los cielos de la Ciudad Blanca.', NULL, 'uploads/productos/aretes_cielo_arequipa.jpg', 'Aretes', 28, 0, 1, 4.6, 53);

-- Usuarios de ejemplo
INSERT INTO usuarios (nombre, apellidos, email, password, telefono, rol) VALUES
('Admin', 'CholloGlam', 'admin@cholloyglam.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+34 600 000 000', 'admin'),
('María', 'García López', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+34 612 345 678', 'cliente'),
('Carlos', 'Mendoza Silva', 'carlos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+34 698 765 432', 'cliente');

-- Ofertas flash
INSERT INTO ofertas_flash (tiempo_fin) VALUES (DATE_ADD(NOW(), INTERVAL 5 HOUR));

-- Asociar productos a oferta flash
INSERT INTO productos_oferta_flash (oferta_id, producto_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4);

-- Instagram feed
INSERT INTO instagram_feed (imagen, url) VALUES
('uploads/instagram/insta1.jpg', 'https://www.instagram.com/cholloyglam'),
('uploads/instagram/insta2.jpg', 'https://www.instagram.com/cholloyglam'),
('uploads/instagram/insta3.jpg', 'https://www.instagram.com/cholloyglam'),
('uploads/instagram/insta4.jpg', 'https://www.instagram.com/cholloyglam'),
('uploads/instagram/insta5.jpg', 'https://www.instagram.com/cholloyglam'),
('uploads/instagram/insta6.jpg', 'https://www.instagram.com/cholloyglam');

-- Testimonios
INSERT INTO testimonios (nombre_cliente, comentario, valoracion, fecha, foto_cliente, activo) VALUES
('Ana M.', '¡Increíble calidad! El collar superó mis expectativas. Las atenciones al detalle son impresionantes.', 5.0, '2026-08-15', NULL, 1),
('Pedro R.', 'Compré los aretes para mi novia y quedó encantada. El envío fue rápido y el empaque muy elegante.', 4.5, '2026-08-10', NULL, 1),
('Lucía F.', 'Ya es mi tercera compra y no me decepciona. Las piezas son únicas y el servicio al cliente excepcional.', 5.0, '2026-08-05', NULL, 1),
('Roberto S.', 'Excelente relación calidad-precio. La pulsera Río Amazonas es una obra de arte.', 4.5, '2026-07-28', NULL, 1);
