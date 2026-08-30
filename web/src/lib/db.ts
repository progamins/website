import Database from 'better-sqlite3';
import path from 'node:path';

const dbPath = path.join(process.cwd(), 'data', 'chollo.db');
export const db = new Database(dbPath);
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

export interface Categoria {
  id: number; nombre: string; slug: string; descripcion: string | null;
  imagen: string | null; icono: string | null; activa: number;
}
export interface Coleccion {
  id: number; nombre: string; slug: string; imagen: string | null;
  descripcion: string | null; activa: number;
}
export interface Producto {
  id: number; nombre: string; slug: string; categoria_id: number | null;
  coleccion_id: number | null; precio_actual: number; precio_original: number | null;
  descripcion: string | null; etiqueta: string | null; imagen: string | null;
  tipo: string | null; stock: number; destacado: number; activo: number;
  valoracion: number; num_valoraciones: number; creado_en: string;
  categoria_nombre?: string; coleccion_nombre?: string;
}
export interface Usuario {
  id: number; nombre: string; apellidos: string | null; email: string;
  rol: 'admin' | 'moderador' | 'cliente'; activo: number; telefono: string | null;
}
export interface Testimonio {
  id: number; nombre_cliente: string; comentario: string | null;
  valoracion: number; foto_cliente: string | null; activo: number;
}
export interface Valoracion {
  id: number; producto_id: number; usuario_id: number | null; nombre: string | null;
  puntuacion: number; comentario: string | null; estado: 'pendiente' | 'aprobada' | 'rechazada';
  creado_en: string; producto_nombre?: string;
}
export interface Notificacion {
  id: number; tipo: string; mensaje: string; enlace: string | null; leida: number; creado_en: string;
}

const PROD_SELECT = `SELECT p.*, c.nombre AS categoria_nombre, co.nombre AS coleccion_nombre
  FROM productos p
  LEFT JOIN categorias c ON c.id = p.categoria_id
  LEFT JOIN colecciones co ON co.id = p.coleccion_id`;

export const getCategorias = (soloActivas = true) =>
  db.prepare(`SELECT * FROM categorias ${soloActivas ? 'WHERE activa = 1' : ''} ORDER BY nombre`).all() as Categoria[];

export const getCategoriaBySlug = (slug: string) =>
  db.prepare('SELECT * FROM categorias WHERE slug = ?').get(slug) as Categoria | undefined;

export const getColecciones = () =>
  db.prepare('SELECT * FROM colecciones WHERE activa = 1 ORDER BY id').all() as Coleccion[];

export const getProductosDestacados = (limit = 8) =>
  db.prepare(`${PROD_SELECT} WHERE p.activo = 1 AND p.destacado = 1 ORDER BY p.num_valoraciones DESC LIMIT ?`).all(limit) as Producto[];

export const getNovedades = (limit = 12) =>
  db.prepare(`${PROD_SELECT} WHERE p.activo = 1 ORDER BY p.creado_en DESC, p.id DESC LIMIT ?`).all(limit) as Producto[];

export const getProductoBySlug = (slug: string) =>
  db.prepare(`${PROD_SELECT} WHERE p.slug = ?`).get(slug) as Producto | undefined;

export const getProductoById = (id: number) =>
  db.prepare(`${PROD_SELECT} WHERE p.id = ?`).get(id) as Producto | undefined;

export const getProductosPorCategoria = (categoriaId: number, page = 1, perPage = 12) => {
  const total = (db.prepare('SELECT COUNT(*) n FROM productos WHERE activo = 1 AND categoria_id = ?').get(categoriaId) as { n: number }).n;
  const items = db.prepare(`${PROD_SELECT} WHERE p.activo = 1 AND p.categoria_id = ? ORDER BY p.nombre LIMIT ? OFFSET ?`)
    .all(categoriaId, perPage, (page - 1) * perPage) as Producto[];
  return { items, total, totalPages: Math.max(1, Math.ceil(total / perPage)) };
};

export const getOfertas = () =>
  db.prepare(`${PROD_SELECT} WHERE p.activo = 1 AND p.precio_original IS NOT NULL AND p.precio_original > p.precio_actual ORDER BY p.nombre`).all() as Producto[];

export const getOfertaFlashActiva = () => {
  const oferta = db.prepare(`SELECT * FROM ofertas_flash WHERE activa = 1 AND tiempo_fin > datetime('now') ORDER BY tiempo_fin ASC LIMIT 1`).get() as
    { id: number; titulo: string; tiempo_fin: string } | undefined;
  if (!oferta) return null;
  const productos = db.prepare(`${PROD_SELECT}
    JOIN productos_oferta_flash pof ON pof.producto_id = p.id
    WHERE pof.oferta_id = ? AND p.activo = 1`).all(oferta.id) as Producto[];
  return { oferta, productos };
};

export const buscarProductos = (q: string) =>
  db.prepare(`${PROD_SELECT} WHERE p.activo = 1 AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.tipo LIKE ?) ORDER BY p.nombre`)
    .all(`%${q}%`, `%${q}%`, `%${q}%`) as Producto[];

export const getProductosPorIds = (ids: number[]) => {
  if (!ids.length) return [] as Producto[];
  const marks = ids.map(() => '?').join(',');
  return db.prepare(`${PROD_SELECT} WHERE p.id IN (${marks})`).all(...ids) as Producto[];
};

export const getTestimonios = () =>
  db.prepare('SELECT * FROM testimonios WHERE activo = 1 ORDER BY id DESC LIMIT 8').all() as Testimonio[];

export const getInstagramFeed = () =>
  db.prepare('SELECT * FROM instagram_feed WHERE activo = 1 ORDER BY id LIMIT 6').all() as { id: number; imagen: string; url: string }[];

export const getValoracionesAprobadas = (productoId: number) =>
  db.prepare(`SELECT * FROM valoraciones WHERE producto_id = ? AND estado = 'aprobada' ORDER BY creado_en DESC`).all(productoId) as Valoracion[];

export const getValoracionesPendientes = () =>
  db.prepare(`SELECT v.*, p.nombre AS producto_nombre FROM valoraciones v JOIN productos p ON p.id = v.producto_id WHERE v.estado = 'pendiente' ORDER BY v.creado_en DESC`).all() as Valoracion[];

export const recalcularValoracion = (productoId: number) => {
  const r = db.prepare(`SELECT AVG(puntuacion) avg, COUNT(*) n FROM valoraciones WHERE producto_id = ? AND estado = 'aprobada'`).get(productoId) as { avg: number | null; n: number };
  if (r.n > 0) db.prepare('UPDATE productos SET valoracion = ?, num_valoraciones = ? WHERE id = ?').run(Math.round((r.avg ?? 0) * 10) / 10, r.n, productoId);
};

export const addNotificacion = (tipo: string, mensaje: string, enlace: string | null = null) =>
  db.prepare('INSERT INTO notificaciones (tipo, mensaje, enlace) VALUES (?,?,?)').run(tipo, mensaje, enlace);

export const getNotificacionesRecientes = (limit = 10) =>
  db.prepare('SELECT * FROM notificaciones ORDER BY id DESC LIMIT ?').all(limit) as Notificacion[];

export const countNotificacionesNoLeidas = () =>
  (db.prepare('SELECT COUNT(*) n FROM notificaciones WHERE leida = 0').get() as { n: number }).n;

export const getStats = () => ({
  productos: (db.prepare('SELECT COUNT(*) n FROM productos WHERE activo = 1').get() as { n: number }).n,
  productosTotal: (db.prepare('SELECT COUNT(*) n FROM productos').get() as { n: number }).n,
  destacados: (db.prepare('SELECT COUNT(*) n FROM productos WHERE destacado = 1 AND activo = 1').get() as { n: number }).n,
  usuarios: (db.prepare('SELECT COUNT(*) n FROM usuarios').get() as { n: number }).n,
  usuariosAdmin: (db.prepare("SELECT COUNT(*) n FROM usuarios WHERE rol = 'admin'").get() as { n: number }).n,
  usuariosModerador: (db.prepare("SELECT COUNT(*) n FROM usuarios WHERE rol = 'moderador'").get() as { n: number }).n,
  usuariosCliente: (db.prepare("SELECT COUNT(*) n FROM usuarios WHERE rol = 'cliente'").get() as { n: number }).n,
  pedidos: (db.prepare('SELECT COUNT(*) n FROM pedidos').get() as { n: number }).n,
  pedidosActivos: (db.prepare("SELECT COUNT(*) n FROM pedidos WHERE estado NOT IN ('entregado','cancelado')").get() as { n: number }).n,
  ventas: (db.prepare("SELECT COALESCE(SUM(total),0) t FROM pedidos WHERE estado != 'cancelado'").get() as { t: number }).t,
  pendientes: (db.prepare("SELECT COUNT(*) n FROM valoraciones WHERE estado = 'pendiente'").get() as { n: number }).n,
  mensajesSinLeer: (db.prepare('SELECT COUNT(*) n FROM mensajes WHERE leido = 0').get() as { n: number }).n,
  suscriptores: (db.prepare('SELECT COUNT(*) n FROM suscriptores').get() as { n: number }).n,
  sinStock: db.prepare('SELECT id, nombre, imagen FROM productos WHERE stock = 0 AND activo = 1').all() as { id: number; nombre: string; imagen: string | null }[],
});

export const getPedidosPorEstado = () =>
  db.prepare('SELECT estado, COUNT(*) n FROM pedidos GROUP BY estado').all() as { estado: string; n: number }[];

export const getConteoPorCategoria = () =>
  db.prepare(`SELECT c.nombre, COUNT(p.id) n FROM categorias c LEFT JOIN productos p ON p.categoria_id = c.id
    WHERE p.activo = 1 GROUP BY c.id ORDER BY n DESC, c.nombre`).all() as { nombre: string; n: number }[];

export const getTopProductos = (limit = 5) =>
  db.prepare(`SELECT p.id, p.nombre, p.imagen, p.precio_actual, COALESCE(SUM(pd.cantidad), 0) AS vendidos
    FROM productos p LEFT JOIN pedido_detalles pd ON pd.producto_id = p.id
    GROUP BY p.id ORDER BY vendidos DESC, p.num_valoraciones DESC LIMIT ?`).all(limit) as
    { id: number; nombre: string; imagen: string | null; precio_actual: number; vendidos: number }[];

export const getPedidosRecientes = (limit = 6) =>
  db.prepare(`SELECT p.id, p.referencia, p.total, p.estado, p.creado_en, u.nombre AS cliente
    FROM pedidos p LEFT JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id DESC LIMIT ?`).all(limit) as
    { id: number; referencia: string; total: number; estado: string; creado_en: string; cliente: string | null }[];

export const getPedidosPorMes = () =>
  db.prepare(`SELECT strftime('%Y-%m', creado_en) mes, COUNT(*) pedidos, SUM(total) total
    FROM pedidos WHERE estado != 'cancelado' GROUP BY mes ORDER BY mes`).all() as { mes: string; pedidos: number; total: number }[];

export const MONEDA = 'PEN';

export function getConfig(clave: string, porDefecto: any = null): any {
  const row = db.prepare('SELECT valor FROM configuracion WHERE clave = ?').get(clave) as { valor: string } | undefined;
  if (!row) return porDefecto;
  try { return JSON.parse(row.valor); } catch { return porDefecto; }
}

export function setConfig(clave: string, valor: any) {
  db.prepare(`INSERT INTO configuracion (clave, valor, actualizado_en) VALUES (?,?,datetime('now'))
    ON CONFLICT(clave) DO UPDATE SET valor = excluded.valor, actualizado_en = datetime('now')`).run(clave, JSON.stringify(valor));
}

export function getLogo(): string | null {
  const logo = getConfig('logo');
  return logo ? String(logo) : null;
}

export interface Banner {
  tipo: 'video' | 'imagen' | 'color';
  fondo: string | null;
  enlace: string | null;
}

export function getBanners(): Banner[] {
  const b = getConfig('banners');
  if (Array.isArray(b) && b.length) return b as Banner[];
  return [{ tipo: 'imagen', fondo: '/uploads/banners/banner1.svg', enlace: '/novedades' }];
}

export const formatEUR = (n: number) =>
  new Intl.NumberFormat('es-PE', { style: 'currency', currency: MONEDA }).format(n);

export const slugify = (s: string) =>
  s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
