PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS categorias (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  descripcion TEXT,
  imagen TEXT,
  icono TEXT,
  activa INTEGER NOT NULL DEFAULT 1,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  actualizado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS colecciones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  imagen TEXT,
  descripcion TEXT,
  activa INTEGER NOT NULL DEFAULT 1,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  actualizado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS productos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  categoria_id INTEGER REFERENCES categorias(id) ON DELETE SET NULL,
  coleccion_id INTEGER REFERENCES colecciones(id) ON DELETE SET NULL,
  precio_actual REAL NOT NULL CHECK (precio_actual >= 0),
  precio_original REAL CHECK (precio_original IS NULL OR precio_original >= 0),
  descripcion TEXT,
  etiqueta TEXT,
  imagen TEXT,
  tipo TEXT,
  stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
  destacado INTEGER NOT NULL DEFAULT 0,
  activo INTEGER NOT NULL DEFAULT 1,
  valoracion REAL NOT NULL DEFAULT 0,
  num_valoraciones INTEGER NOT NULL DEFAULT 0,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  actualizado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_productos_categoria ON productos(categoria_id);
CREATE INDEX IF NOT EXISTS idx_productos_coleccion ON productos(coleccion_id);
CREATE INDEX IF NOT EXISTS idx_productos_destacado ON productos(destacado, activo);

CREATE TABLE IF NOT EXISTS ofertas_flash (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo TEXT NOT NULL DEFAULT 'Oferta Flash',
  tiempo_fin TEXT NOT NULL,
  activa INTEGER NOT NULL DEFAULT 1,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS productos_oferta_flash (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  oferta_id INTEGER NOT NULL REFERENCES ofertas_flash(id) ON DELETE CASCADE,
  producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
  UNIQUE(oferta_id, producto_id)
);
CREATE INDEX IF NOT EXISTS idx_pof_oferta ON productos_oferta_flash(oferta_id);
CREATE INDEX IF NOT EXISTS idx_pof_producto ON productos_oferta_flash(producto_id);

CREATE TABLE IF NOT EXISTS usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  apellidos TEXT,
  email TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  telefono TEXT,
  rol TEXT NOT NULL DEFAULT 'cliente' CHECK (rol IN ('admin','moderador','cliente')),
  activo INTEGER NOT NULL DEFAULT 1,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  actualizado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);

CREATE TABLE IF NOT EXISTS sesiones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  token TEXT NOT NULL UNIQUE,
  usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  expira_en TEXT NOT NULL,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_sesiones_token ON sesiones(token);

CREATE TABLE IF NOT EXISTS pedidos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario_id INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
  referencia TEXT NOT NULL UNIQUE,
  total REAL NOT NULL CHECK (total >= 0),
  moneda TEXT NOT NULL DEFAULT 'EUR',
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','procesando','enviado','entregado','cancelado')),
  pago_estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (pago_estado IN ('pendiente','pagado','parcial','fallido','reembolsado')),
  nombre_envio TEXT,
  direccion_envio TEXT,
  ciudad_envio TEXT,
  codigo_postal TEXT,
  telefono_envio TEXT,
  notas TEXT,
  metodo_pago TEXT,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_pedidos_usuario ON pedidos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_pago ON pedidos(pago_estado);
CREATE INDEX IF NOT EXISTS idx_pedidos_estado ON pedidos(estado);

-- Pagos: trazabilidad profesional de transacciones (pagos y reembolsos)
CREATE TABLE IF NOT EXISTS pagos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  pedido_id INTEGER NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
  referencia TEXT NOT NULL UNIQUE,
  gateway TEXT NOT NULL DEFAULT 'manual' CHECK (gateway IN ('tarjeta','paypal','transferencia','manual')),
  tipo TEXT NOT NULL DEFAULT 'pago' CHECK (tipo IN ('pago','reembolso')),
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','aprobado','fallido','reembolsado')),
  monto REAL NOT NULL CHECK (monto > 0),
  moneda TEXT NOT NULL DEFAULT 'EUR',
  transaccion_externa TEXT,
  tarjeta_ultimos4 TEXT,
  notas TEXT,
  pagado_en TEXT,
  reembolsado_en TEXT,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  actualizado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_pagos_pedido ON pagos(pedido_id);
CREATE INDEX IF NOT EXISTS idx_pagos_estado ON pagos(estado);
CREATE INDEX IF NOT EXISTS idx_pagos_gateway ON pagos(gateway);

CREATE TABLE IF NOT EXISTS pedido_detalles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  pedido_id INTEGER NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
  producto_id INTEGER REFERENCES productos(id) ON DELETE SET NULL,
  cantidad INTEGER NOT NULL DEFAULT 1 CHECK (cantidad > 0),
  precio_unitario REAL NOT NULL CHECK (precio_unitario >= 0),
  subtotal REAL NOT NULL CHECK (subtotal >= 0)
);
CREATE INDEX IF NOT EXISTS idx_pd_pedido ON pedido_detalles(pedido_id);

CREATE TABLE IF NOT EXISTS testimonios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre_cliente TEXT NOT NULL,
  comentario TEXT,
  valoracion REAL NOT NULL DEFAULT 5 CHECK (valoracion BETWEEN 1 AND 5),
  foto_cliente TEXT,
  activo INTEGER NOT NULL DEFAULT 1,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Valoraciones con moderación integrada (estado) = tabla "comentarios"
CREATE TABLE IF NOT EXISTS valoraciones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
  usuario_id INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
  nombre TEXT,
  puntuacion INTEGER NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
  comentario TEXT,
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','aprobada','rechazada')),
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_val_producto ON valoraciones(producto_id);
CREATE INDEX IF NOT EXISTS idx_val_estado ON valoraciones(estado);

CREATE TABLE IF NOT EXISTS instagram_feed (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  imagen TEXT NOT NULL,
  url TEXT,
  activo INTEGER NOT NULL DEFAULT 1,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS lista_deseos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE(usuario_id, producto_id)
);
CREATE INDEX IF NOT EXISTS idx_ld_usuario ON lista_deseos(usuario_id);

CREATE TABLE IF NOT EXISTS suscriptores (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  activo INTEGER NOT NULL DEFAULT 1,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS notificaciones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tipo TEXT NOT NULL DEFAULT 'info',
  mensaje TEXT NOT NULL,
  enlace TEXT,
  leida INTEGER NOT NULL DEFAULT 0,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_notif_leida ON notificaciones(leida);

-- Configuración del sitio (logo, hero, etc.) en formato JSON
CREATE TABLE IF NOT EXISTS configuracion (
  clave TEXT PRIMARY KEY,
  valor TEXT NOT NULL,
  actualizado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS mensajes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  email TEXT NOT NULL,
  asunto TEXT,
  mensaje TEXT NOT NULL,
  leido INTEGER NOT NULL DEFAULT 0,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Triggers para actualizado_en
CREATE TRIGGER IF NOT EXISTS trg_categorias_upd AFTER UPDATE ON categorias
BEGIN UPDATE categorias SET actualizado_en = datetime('now') WHERE id = NEW.id; END;
CREATE TRIGGER IF NOT EXISTS trg_productos_upd AFTER UPDATE ON productos
BEGIN UPDATE productos SET actualizado_en = datetime('now') WHERE id = NEW.id; END;
CREATE TRIGGER IF NOT EXISTS trg_usuarios_upd AFTER UPDATE ON usuarios
BEGIN UPDATE usuarios SET actualizado_en = datetime('now') WHERE id = NEW.id; END;
CREATE TRIGGER IF NOT EXISTS trg_pagos_upd AFTER UPDATE ON pagos
BEGIN UPDATE pagos SET actualizado_en = datetime('now') WHERE id = NEW.id; END;
