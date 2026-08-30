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

// Categorías (20 secciones)
const cats = [
  ['collares', 'Collares', 'Collares artesanales de diseño exclusivo', '/uploads/categorias/collares.svg'],
  ['aretes', 'Aretes', 'Aretes elegantes para toda ocasión', '/uploads/categorias/aretes.svg'],
  ['pulseras', 'Pulseras', 'Pulseras tejidas y de plata 925', '/uploads/categorias/pulseras.svg'],
  ['anillos', 'Anillos', 'Anillos de diseño único y artesanal', '/uploads/categorias/anillos.svg'],
  ['relojes', 'Relojes', 'Relojes clásicos y deportivos', '/uploads/categorias/relojes.svg'],
  ['accesorios', 'Accesorios', 'Complementos para tu estilo', '/uploads/categorias/accesorios.svg'],
  ['ropa-hombre', 'Ropa Hombre', 'Prendas cómodas y modernas', '/uploads/categorias/ropa-hombre.svg'],
  ['ropa-mujer', 'Ropa Mujer', 'Moda femenina de temporada', '/uploads/categorias/ropa-mujer.svg'],
  ['calzado', 'Calzado', 'Zapatos y zapatillas', '/uploads/categorias/calzado.svg'],
  ['bolsos', 'Bolsos y Carteras', 'Bolsos, mochilas y carteras', '/uploads/categorias/bolsos.svg'],
  ['tecnologia', 'Tecnología', 'Gadgets y electrónica', '/uploads/categorias/tecnologia.svg'],
  ['mandos', 'Mandos y Gaming', 'Mandos y accesorios gamer', '/uploads/categorias/mandos.svg'],
  ['hogar', 'Hogar y Decoración', 'Detalles para tu hogar', '/uploads/categorias/hogar.svg'],
  ['mascotas', 'Mascotas', 'Collares, arneses y accesorios', '/uploads/categorias/mascotas.svg'],
  ['belleza', 'Belleza y Cuidado', 'Cuidado personal y cosmética', '/uploads/categorias/belleza.svg'],
  ['deportes', 'Deportes', 'Accesorios deportivos', '/uploads/categorias/deportes.svg'],
  ['bebes', 'Bebés y Niños', 'Ropa y accesorios para peques', '/uploads/categorias/bebes.svg'],
  ['gafas', 'Gafas y Lentes', 'Protección y estilo', '/uploads/categorias/gafas.svg'],
  ['papeleria', 'Papelería y Oficina', 'Útiles y organización', '/uploads/categorias/papeleria.svg'],
  ['ofertas', 'Ofertas', 'Productos con descuento especial', '/uploads/categorias/ofertas.svg'],
];
const insCat = db.prepare('INSERT INTO categorias (nombre, slug, descripcion, imagen, icono) VALUES (?,?,?,?,?)');
const catId = {};
const catName = {};
for (const [s, n, d, i] of cats) { const r = insCat.run(n, s, d, i, `/uploads/categorias/${s}-icon.svg`); catId[s] = Number(r.lastInsertRowid); catName[s] = n; }

// Colecciones
const cols = [
  ['Andes Dorados', '/uploads/colecciones/andes-dorados.svg', 'Inspirada en la majestuosidad de los Andes, esta colección fusiona el oro tradicional con diseños contemporáneos.'],
  ['Amazonía Mística', '/uploads/colecciones/amazonia.svg', 'Piezas que capturan la esencia de la selva amazónica con gemas naturales y motivos vegetales.'],
  ['Costa Brillante', '/uploads/colecciones/costa.svg', 'Diseños inspirados en la costa peruana con conchas, corales y tonos azules.'],
];
const insCol = db.prepare('INSERT INTO colecciones (nombre, slug, imagen, descripcion) VALUES (?,?,?,?)');
for (const [n, i, d] of cols) insCol.run(n, slug(n), i, d);

// Joyería (productos con renders PNG reales)
const joya = [
  ['Collar Sol de Lima', 'collares', 1, 45.99, 69.99, 'Collar artesanal inspirado en el sol de Lima, fabricado con plata 925 y baño de oro de 18k.', 'Más Vendido', '/uploads/productos/collar-plata.png', 25, 1],
  ['Aretes Luna Cusqueña', 'aretes', 1, 29.99, 39.99, 'Aretes con forma de luna creciente, inspirados en la arquitectura colonial de Cusco.', 'Nuevo', '/uploads/productos/pendiente_perla.png', 30, 1],
  ['Pulsera Río Amazonas', 'pulseras', 2, 34.99, 49.99, 'Pulsera tejida a mano con piedras semipreciosas amazónicas.', 'Edición Limitada', '/uploads/productos/pulsera-plata.png', 15, 1],
  ['Anillo Ocopa Dorado', 'anillos', 1, 59.99, 79.99, 'Anillo de diseño artesanal con baño de oro 18k.', 'Exclusivo', '/uploads/productos/anilli-0.5.png', 20, 1],
  ['Collar Versalles Andino', 'collares', 3, 55.0, 85.0, 'Collar premium con cristales de cuarzo rosa y baño de platino.', 'Premium', '/uploads/productos/minimalista.png', 10, 1],
  ['Aretes Mariposa Nazca', 'aretes', 2, 38.5, 55.0, 'Aretes inspirados en las líneas de Nazca con forma de colibrí.', null, '/uploads/productos/pendiente-oro.png', 18, 0],
  ['Pulsera Machu Picchu', 'pulseras', 1, 42.0, 58.0, 'Pulsera con dije del sol naciente y detalles de la ciudadela.', null, '/uploads/productos/pulsera_18k.png', 22, 0],
  ['Anillo Nazca Star', 'anillos', 3, 48.0, 65.0, 'Anillo con incrustación de lapislázuli y detalles tallados a mano.', null, '/uploads/productos/estrella.png', 12, 0],
  ['Collar Pacífico Dorado', 'collares', 3, 62.0, 90.0, 'Collar premium con pendientes de ámbar del Pacífico y cadena de oro 18k.', 'Premium', '/uploads/productos/collar-plata.png', 8, 1],
  ['Aretes Cielo Arequipa', 'aretes', 1, 32.0, 45.0, 'Aretes con tonos celestes y perlas de agua dulce.', null, '/uploads/productos/pendiente_perla.png', 28, 0],
  ['Anillo Esmeralda Imperial', 'anillos', 2, 74.99, 99.99, 'Anillo con esmeralda natural engarzada en oro de 18k.', 'Premium', '/uploads/productos/anillo-esmeralda.png', 6, 1],
  ['Pulsera Oro 18k Shalom', 'pulseras', 3, 89.99, 120.0, 'Pulsera de oro 18k con eslabones tejidos a mano.', 'Exclusivo', '/uploads/productos/pulsera_18k.png', 5, 1],
  ['Pendiente Oro Colonial', 'aretes', 3, 39.99, 54.99, 'Pendientes de oro con motivos coloniales, acabado pulido espejo.', null, '/uploads/productos/pendiente-oro.png', 0, 0],
  ['Collar Plata Minimalista', 'collares', 2, 27.99, null, 'Collar de plata 925 de líneas minimalistas.', null, '/uploads/productos/minimalista.png', 40, 0],
];

// Otras categorías (marketplace) — imagen ilustrada por categoría
const DESC = 'Producto seleccionado por su calidad y diseño. Envío disponible a todo el Perú.';
const extras = [
  { cat: 'relojes', items: [['Reloj Minimalista Plata', 89.99, 129.99, 'Nuevo', 1], ['Reloj Deportivo Acero', 59.99, 79.99, null, 0], ['Reloj Clásico Cuero', 99.99, 149.99, null, 1]] },
  { cat: 'accesorios', items: [['Gargantilla Capas Doradas', 24.99, 39.99, 'Nuevo', 1], ['Set Gorro y Bufanda', 19.99, 29.99, null, 0], ['Llavero Cuero Grabado', 9.99, 14.99, null, 0]] },
  { cat: 'ropa-hombre', items: [['Camiseta Algodón Premium', 24.99, 34.99, 'Nuevo', 1], ['Polera Básica Unisex', 29.99, 39.99, null, 0], ['Casaca Ligera Urbana', 49.99, 69.99, null, 0]] },
  { cat: 'ropa-mujer', items: [['Blusa Seda Estampada', 27.99, 39.99, 'Nuevo', 1], ['Vestido Verano Floral', 39.99, 59.99, null, 0], ['Cárdigan Tejido Suave', 34.99, 49.99, null, 0]] },
  { cat: 'calzado', items: [['Zapatillas Urbanas', 45.99, 64.99, 'Nuevo', 1], ['Sandalias Artesanales', 21.99, 32.99, null, 0], ['Botines de Cuero', 69.99, 99.99, null, 0]] },
  { cat: 'bolsos', items: [['Cartera Tote Grande', 39.99, 59.99, null, 1], ['Bolso Cruzado Compacto', 25.99, 39.99, null, 0], ['Mochila Tela Resistente', 35.99, 49.99, null, 0]] },
  { cat: 'tecnologia', items: [['Audífonos Inalámbricos', 29.99, 49.99, 'Nuevo', 1], ['Smartwatch Deportivo', 59.99, 89.99, null, 1], ['Power Bank 20.000 mAh', 24.99, 39.99, null, 0]] },
  { cat: 'mandos', items: [['Mando Inalámbrico Pro', 39.99, 59.99, 'Nuevo', 1], ['Auriculares Gamer RGB', 34.99, 54.99, null, 0], ['Soporte Doble de Mando', 12.99, 19.99, null, 0]] },
  { cat: 'hogar', items: [['Juego de Sábanas Algodón', 44.99, 69.99, null, 0], ['Lámpara Decorativa', 32.99, 49.99, 'Nuevo', 1], ['Set de Toallas Premium', 27.99, 39.99, null, 0]] },
  { cat: 'mascotas', items: [['Collar Perro Ajustable', 12.99, 19.99, 'Nuevo', 1], ['Arnés Paseo Acolchado', 18.99, 29.99, null, 0], ['Juguete Interactivo', 9.99, 14.99, null, 0]] },
  { cat: 'belleza', items: [['Kit Básico de Cuidado', 22.99, 34.99, null, 0], ['Set Brochas Maquillaje', 18.99, 27.99, 'Nuevo', 1], ['Espejo LED Maquillaje', 25.99, 39.99, null, 1]] },
  { cat: 'deportes', items: [['Botella Deportiva', 9.99, 14.99, null, 0], ['Cuerda de Saltar Pro', 7.99, 12.99, null, 0], ['Mat Yoga Antideslizante', 19.99, 29.99, 'Nuevo', 1]] },
  { cat: 'bebes', items: [['Conjunto Bebé Algodón', 24.99, 36.99, 'Nuevo', 1], ['Manta Suave de Estrellas', 19.99, 27.99, null, 0], ['Mochila Pequeña Kínder', 22.99, 33.99, null, 0]] },
  { cat: 'gafas', items: [['Gafas de Sol Clásicas', 15.99, 22.99, null, 0], ['Lentes Blue Light', 13.99, 19.99, 'Nuevo', 1], ['Estuche Lentes Rígido', 6.99, 9.99, null, 0]] },
  { cat: 'papeleria', items: [['Set de Útiles Escolares', 14.99, 21.99, null, 0], ['Cuaderno Pasta Dura', 8.99, 12.99, null, 0], ['Organizador de Escritorio', 11.99, 16.99, null, 1]] },
  { cat: 'ofertas', items: [['Pack Collares x3', 19.99, 39.99, 'Oferta', 1], ['Pack Aretes x2', 12.99, 24.99, 'Oferta', 0], ['Set Regalo de Joyas', 29.99, 54.99, 'Oferta', 1]] },
];

const prods = [];
for (const [nombre, c, col, pa, po, desc, etq, img, stock, dest] of joya) prods.push([nombre, c, col, pa, po, desc, etq, img, stock, dest]);
for (const g of extras) for (const [nombre, pa, po, etq, dest] of g.items) prods.push([nombre, g.cat, null, pa, po, DESC, etq, `/uploads/productos/${g.cat}.svg`, 25, dest]);

const insProd = db.prepare(`INSERT INTO productos
  (nombre, slug, categoria_id, coleccion_id, precio_actual, precio_original, descripcion, etiqueta, imagen, tipo, stock, destacado, valoracion, num_valoraciones)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)`);
prods.forEach((p, i) => {
  const val = (4.0 + (i % 7) * 0.1).toFixed(1);
  const cs = p[1];
  insProd.run(p[0], slug(p[0]), catId[cs], p[2], p[3], p[4], p[5], p[6], p[7], catName[cs], p[8], p[9], Number(val), 10 + i * 7);
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
  { tipo: 'imagen', fondo: '/uploads/banners/banner1.svg', enlace: '/novedades' },
  { tipo: 'imagen', fondo: '/uploads/banners/banner2.svg', enlace: '/novedades' },
  { tipo: 'imagen', fondo: '/uploads/banners/banner3.svg', enlace: '/ofertas' },
]));

console.log('Base de datos creada en', dbPath);
console.log('Admin:    admin@cholloyglam.com / Admin123!');
console.log('Moderador: moderador@cholloyglam.com / Moderador123!');
console.log('Cliente:  maria@email.com / Cliente123!');
