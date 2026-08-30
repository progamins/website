import Database from 'better-sqlite3';
import bcrypt from 'bcryptjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const dataDir = path.join(root, 'data');
fs.mkdirSync(dataDir, { recursive: true });
const dbPath = path.join(dataDir, 'chollo.db');
if (fs.existsSync(dbPath)) fs.rmSync(dbPath);

const db = new Database(dbPath);
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

const schema = fs.readFileSync(path.join(root, 'src/lib/schema.sql'), 'utf8');
db.exec(schema);

const slug = (s) =>
  s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');

// Categorías
const cats = [
  ['Collares', 'Collares artesanales de diseño exclusivo', '/uploads/categorias/collares.jpg'],
  ['Aretes', 'Aretes elegantes para toda ocasión', '/uploads/categorias/aretes.jpg'],
  ['Pulseras', 'Pulseras tejidas y de plata 925', '/uploads/categorias/pulseras.jpg'],
  ['Anillos', 'Anillos de diseño único y artesanal', '/uploads/categorias/anillos.jpg'],
  ['Premium', 'Piezas exclusivas de colección limitada', '/uploads/categorias/premium.jpg'],
  ['Ofertas', 'Productos con descuento especial', '/uploads/categorias/ofertas.jpg'],
];
const insCat = db.prepare('INSERT INTO categorias (nombre, slug, descripcion, imagen) VALUES (?,?,?,?)');
for (const [n, d, i] of cats) insCat.run(n, slug(n), d, i);

// Colecciones
const cols = [
  ['Andes Dorados', '/uploads/colecciones/andes_dorados.jpg', 'Inspirada en la majestuosidad de los Andes, esta colección fusiona el oro tradicional con diseños contemporáneos.'],
  ['Amazonía Mística', '/uploads/colecciones/amazonia.jpg', 'Piezas que capturan la esencia de la selva amazónica con gemas naturales y motivos vegetales.'],
  ['Costa Brillante', '/uploads/colecciones/costa.jpg', 'Diseños inspirados en la costa peruana con conchas, corales y tonos azules.'],
];
const insCol = db.prepare('INSERT INTO colecciones (nombre, slug, imagen, descripcion) VALUES (?,?,?,?)');
for (const [n, i, d] of cols) insCol.run(n, slug(n), i, d);

// Productos (imágenes reales existentes en uploads/)
const prods = [
  ['Collar Sol de Lima', 1, 1, 45.99, 69.99, 'Collar artesanal inspirado en el sol de Lima, fabricado con plata 925 y baño de oro de 18k. Pieza única con detalles grabados a mano.', 'Más Vendido', '/uploads/productos/collar_sol_lima.jpg', 'Collares', 25, 1],
  ['Aretes Luna Cusqueña', 2, 1, 29.99, 39.99, 'Aretes con forma de luna creciente, inspirados en la arquitectura colonial de Cusco. Plata 925 con esmalte artesanal.', 'Nuevo', '/uploads/productos/aretes_luna_cusqueña.jpg', 'Aretes', 30, 1],
  ['Pulsera Río Amazonas', 3, 2, 34.99, 49.99, 'Pulsera tejida a mano con piedras semipreciosas amazónicas. Cada pieza es única y cuenta la historia de la selva.', 'Edición Limitada', '/uploads/productos/pulsera_rio_amazonas.jpg', 'Pulseras', 15, 1],
  ['Anillo Ocopa Dorado', 4, 1, 59.99, 79.99, 'Anillo de diseño artesanal con baño de oro 18k, inspirado en los tejados de Ocopa. Resistente y elegante.', 'Exclusivo', '/uploads/productos/anillo_ocopa_dorado.jpg', 'Anillos', 20, 1],
  ['Collar Versalles Andino', 1, 3, 55.0, 85.0, 'Collar premium con cristales de cuarzo rosa y baño de platino. Diseño que fusiona lo barroco con lo andino.', 'Premium', '/uploads/productos/collar_versalles_andino.jpg', 'Collares', 10, 1],
  ['Aretes Mariposa Nazca', 2, 2, 38.5, 55.0, 'Aretes inspirados en las líneas de Nazca con forma de colibrí. Plata 925 con turquesa natural.', null, '/uploads/productos/aretes_nazca.jpg', 'Aretes', 18, 0],
  ['Pulsera Machu Picchu', 3, 1, 42.0, 58.0, 'Pulsera con dije del sol naciente y detalles de la ciudadela. Cadenas de plata 925 intercaladas con cuentas de obsidiana.', null, '/uploads/productos/pulsera_machu_picchu.jpg', 'Pulseras', 22, 0],
  ['Anillo Nazca Star', 4, 3, 48.0, 65.0, 'Anillo con incrustación de lapislázuli y detalles tallados a mano. Inspirado en las constelaciones de Nazca.', null, '/uploads/productos/anillo_nazca_star.jpg', 'Anillos', 12, 0],
  ['Collar Pacífico Dorado', 1, 3, 62.0, 90.0, 'Collar premium con pendientes de ámbar del Pacífico y cadena de oro 18k. Diseño exclusivo de colección limitada.', 'Premium', '/uploads/productos/collar_pacifico_dorado.jpg', 'Collares', 8, 1],
  ['Aretes Cielo Arequipa', 2, 1, 32.0, 45.0, 'Aretes con tonos celestes y perlas de agua dulce. Inspirados en los cielos de la Ciudad Blanca.', null, '/uploads/productos/aretes_cielo_arequipa.jpg', 'Aretes', 28, 0],
  ['Anillo Esmeralda Imperial', 4, 2, 74.99, 99.99, 'Anillo con esmeralda natural engarzada en oro de 18k. Pieza de alta joyería artesanal peruana.', 'Premium', '/uploads/productos/anillo-esmeralda.png', 'Anillos', 6, 1],
  ['Pulsera Oro 18k Shalom', 3, 3, 89.99, 120.0, 'Pulsera de oro 18k con eslabones tejidos a mano por maestros orfebres. Elegancia atemporal.', 'Exclusivo', '/uploads/productos/pulsera_18k.png', 'Pulseras', 5, 1],
  ['Pendiente Oro Colonial', 2, 3, 39.99, 54.99, 'Pendientes de oro con motivos coloniales, acabado pulido espejo.', null, '/uploads/productos/pendiente-oro.png', 'Aretes', 0, 0],
  ['Collar Plata Minimalista', 1, 2, 27.99, null, 'Collar de plata 925 de líneas minimalistas. Ideal para el día a día.', null, '/uploads/productos/collar-plata.png', 'Collares', 40, 0],
];
const insProd = db.prepare(`INSERT INTO productos
  (nombre, slug, categoria_id, coleccion_id, precio_actual, precio_original, descripcion, etiqueta, imagen, tipo, stock, destacado, valoracion, num_valoraciones)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)`);
prods.forEach((p, i) => {
  const val = (4.2 + (i % 7) * 0.1).toFixed(1);
  insProd.run(p[0], slug(p[0]), p[1], p[2], p[3], p[4], p[5], p[6], p[7], p[8], p[9], p[10], Number(val), 10 + i * 7);
});

// Usuarios
const hashAdmin = bcrypt.hashSync('Admin123!', 10);
const hashMod = bcrypt.hashSync('Moderador123!', 10);
const hashCli = bcrypt.hashSync('Cliente123!', 10);
const insUser = db.prepare('INSERT INTO usuarios (nombre, apellidos, email, password, telefono, rol) VALUES (?,?,?,?,?,?)');
insUser.run('Admin', 'CholloGlam', 'admin@cholloyglam.com', hashAdmin, '+34 600 000 000', 'admin');
insUser.run('Lucía', 'Torres Vega', 'moderador@cholloyglam.com', hashMod, '+34 611 222 333', 'moderador');
insUser.run('María', 'García López', 'maria@email.com', hashCli, '+34 612 345 678', 'cliente');
insUser.run('Carlos', 'Mendoza Silva', 'carlos@email.com', hashCli, '+34 698 765 432', 'cliente');

// Oferta flash activa (3 días) con productos 1-4
const fin = new Date(Date.now() + 3 * 24 * 3600 * 1000).toISOString().slice(0, 19).replace('T', ' ');
const ofertaId = db.prepare('INSERT INTO ofertas_flash (titulo, tiempo_fin) VALUES (?,?)').run('Oferta Flash de Temporada', fin).lastInsertRowid;
const insPof = db.prepare('INSERT INTO productos_oferta_flash (oferta_id, producto_id) VALUES (?,?)');
for (const pid of [1, 2, 3, 4]) insPof.run(ofertaId, pid);

// Testimonios
const insTest = db.prepare('INSERT INTO testimonios (nombre_cliente, comentario, valoracion, activo) VALUES (?,?,?,1)');
insTest.run('Ana M.', '¡Increíble calidad! El collar superó mis expectativas. Las atenciones al detalle son impresionantes.', 5);
insTest.run('Pedro R.', 'Compré los aretes para mi novia y quedó encantada. El envío fue rápido y el empaque muy elegante.', 4.5);
insTest.run('Lucía F.', 'Ya es mi tercera compra y no me decepciona. Las piezas son únicas y el servicio al cliente excepcional.', 5);
insTest.run('Roberto S.', 'Excelente relación calidad-precio. La pulsera Río Amazonas es una obra de arte.', 4.5);

// Valoraciones (algunas pendientes para moderación)
const insVal = db.prepare('INSERT INTO valoraciones (producto_id, usuario_id, nombre, puntuacion, comentario, estado) VALUES (?,?,?,?,?,?)');
insVal.run(1, 3, 'María G.', 5, 'Precioso, llegó rapidísimo y con un empaque precioso.', 'aprobada');
insVal.run(1, 4, 'Carlos M.', 4, 'Muy buena calidad, el baño de oro se ve elegante.', 'aprobada');
insVal.run(3, 3, 'María G.', 5, 'La pulsera es incluso más bonita que en las fotos.', 'aprobada');
insVal.run(2, null, 'Invitado', 3, 'Bonitos, aunque algo más pequeños de lo esperado.', 'pendiente');
insVal.run(5, 4, 'Carlos M.', 5, 'Pieza de lujo auténtica. Repetiré seguro.', 'pendiente');

// Instagram feed
const insInsta = db.prepare('INSERT INTO instagram_feed (imagen, url) VALUES (?,?)');
for (let i = 1; i <= 6; i++) insInsta.run(`/uploads/instagram/insta${i}.jpg`, 'https://www.instagram.com/cholloyglam');

// Pedidos de ejemplo (para el dashboard: línea por mes)
const insPed = db.prepare('INSERT INTO pedidos (usuario_id, referencia, total, estado, pago_estado, metodo_pago, creado_en) VALUES (?,?,?,?,?,?,?)');
const meses = [
  ['2026-03-12 10:00:00', 145.98, 'entregado', 'pagado', 'tarjeta'],
  ['2026-04-05 12:30:00', 89.99, 'entregado', 'pagado', 'paypal'],
  ['2026-05-18 09:15:00', 210.5, 'entregado', 'pagado', 'tarjeta'],
  ['2026-06-22 16:40:00', 132.0, 'enviado', 'pagado', 'transferencia'],
  ['2026-07-30 11:05:00', 175.49, 'entregado', 'pagado', 'tarjeta'],
  ['2026-08-10 14:20:00', 98.97, 'pendiente', 'pendiente', 'tarjeta'],
];
const pedIds = meses.map(([f, t, e, p, m], i) => {
  const r = insPed.run(3, `PED-2026-${1000 + i}`, t, e, p, p === 'pagado' ? m : null, f);
  return Number(r.lastInsertRowid);
});

// Pagos de ejemplo (trazabilidad profesional)
const insPago = db.prepare(`INSERT INTO pagos (pedido_id, referencia, gateway, tipo, estado, monto, transaccion_externa, tarjeta_ultimos4, pagado_en)
  VALUES (?,?,?,?,'aprobado',?,?,?,datetime('now'))`);
insPago.run(pedIds[0], 'PAY-2026-03-12-AB12', 'tarjeta', 'pago', 145.98, 'TXN-901234', '4242');
insPago.run(pedIds[1], 'PAY-2026-04-05-CD34', 'paypal', 'pago', 89.99, 'TXN-902345', null);
insPago.run(pedIds[2], 'PAY-2026-05-18-EF56', 'tarjeta', 'pago', 210.5, 'TXN-903456', '1234');
insPago.run(pedIds[3], 'PAY-2026-06-22-GH78', 'transferencia', 'pago', 132.0, null, null);
insPago.run(pedIds[4], 'PAY-2026-07-30-IJ90', 'tarjeta', 'pago', 175.49, 'TXN-904567', '0001');
// Pago pendiente del pedido 6
db.prepare(`INSERT INTO pagos (pedido_id, referencia, gateway, tipo, estado, monto, tarjeta_ultimos4)
  VALUES (?,?,'tarjeta','pago','pendiente',?,?)`).run(pedIds[5], 'PAY-2026-08-10-KL12', 98.97, '9999');
// Un reembolso parcial sobre el pedido 3
insPago.run(pedIds[2], 'RFD-2026-05-20-MN34', 'manual', 'reembolso', 30.0, null, null);

// Lista de deseos de ejemplo
db.prepare('INSERT INTO lista_deseos (usuario_id, producto_id) VALUES (?,?)').run(3, 1);
db.prepare('INSERT INTO lista_deseos (usuario_id, producto_id) VALUES (?,?)').run(3, 9);

// Notificaciones de ejemplo
const insNot = db.prepare('INSERT INTO notificaciones (tipo, mensaje, enlace) VALUES (?,?,?)');
insNot.run('pedido', 'Nuevo pedido PED-2026-1005 por S/ 98,97', '/admin');
insNot.run('moderacion', 'Hay valoraciones pendientes de moderación', '/admin/moderacion');
insNot.run('stock', 'El producto "Pendiente Oro Colonial" está sin stock', '/admin/productos');

// Suscriptor de ejemplo
db.prepare('INSERT INTO suscriptores (email) VALUES (?)').run('newsletter-fan@email.com');

// Configuración del sitio (logo y banners de portada)
const insConf = db.prepare('INSERT INTO configuracion (clave, valor) VALUES (?,?) ON CONFLICT(clave) DO NOTHING');
insConf.run('logo', JSON.stringify(null));
insConf.run('banners', JSON.stringify([
  { tipo: 'video', fondo: '/uploads/productos/banner-video1.mp4', enlace: null },
  { tipo: 'imagen', fondo: '/uploads/productos/banner1.png', enlace: '/ofertas' },
  { tipo: 'imagen', fondo: '/uploads/productos/coleccionE.png', enlace: '/novedades' },
]));

console.log('Base de datos creada en', dbPath);
console.log('Admin:    admin@cholloyglam.com / Admin123!');
console.log('Moderador: moderador@cholloyglam.com / Moderador123!');
console.log('Cliente:  maria@email.com / Cliente123!');
