import { db, addNotificacion } from './db';

export type Gateway = 'tarjeta' | 'paypal' | 'transferencia' | 'manual';
export type PagoEstado = 'pendiente' | 'aprobado' | 'fallido' | 'reembolsado';

export interface Pago {
  id: number;
  pedido_id: number;
  referencia: string;
  gateway: Gateway;
  tipo: 'pago' | 'reembolso';
  estado: PagoEstado;
  monto: number;
  moneda: string;
  transaccion_externa: string | null;
  tarjeta_ultimos4: string | null;
  notas: string | null;
  pagado_en: string | null;
  reembolsado_en: string | null;
  creado_en: string;
  pedido_ref?: string;
  cliente?: string;
}

function nuevaRef(prefix: string): string {
  const fecha = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  const rand = Math.random().toString(36).slice(2, 8).toUpperCase();
  return `${prefix}-${fecha}-${rand}`;
}

/**
 * Recalcula el pago_estado de un pedido a partir de sus pagos/reembolsos.
 * Lógica de producción:
 *  - Si hay reembolsos aprobados que cubran el total  -> 'reembolsado'
 *  - Si la suma de pagos aprobados >= total           -> 'pagado'
 *  - Si hay pagos aprobados pero no cubren el total   -> 'parcial'
 *  - Si hay pagos fallidos y ninguno aprobado         -> 'fallido'
 *  - En otro caso                                     -> 'pendiente'
 */
export function recomputarPagoEstado(pedidoId: number): string {
  const ped = db.prepare('SELECT total FROM pedidos WHERE id = ?').get(pedidoId) as { total: number } | undefined;
  if (!ped) return 'pendiente';
  const r = db.prepare(`
    SELECT
      COALESCE(SUM(CASE WHEN tipo='pago' AND estado='aprobado' THEN monto ELSE 0 END),0) AS pagado,
      COALESCE(SUM(CASE WHEN tipo='reembolso' AND estado='aprobado' THEN monto ELSE 0 END),0) AS reembolsado,
      COALESCE(SUM(CASE WHEN tipo='pago' AND estado='fallido' THEN 1 ELSE 0 END),0) AS fallidos,
      COALESCE(SUM(CASE WHEN tipo='pago' AND estado='aprobado' THEN 1 ELSE 0 END),0) AS aprobados
    FROM pagos WHERE pedido_id = ?`).get(pedidoId) as { pagado: number; reembolsado: number; fallidos: number; aprobados: number };

  let estado: string;
  if (r.reembolsado > 0 && r.reembolsado >= (ped.total - r.pagado)) estado = 'reembolsado';
  else if (r.pagado >= ped.total) estado = 'pagado';
  else if (r.pagado > 0) estado = 'parcial';
  else if (r.fallidos > 0 && r.aprobados === 0) estado = 'fallido';
  else estado = 'pendiente';

  db.prepare('UPDATE pedidos SET pago_estado = ? WHERE id = ?').run(estado, pedidoId);
  return estado;
}

/** Crea un pago pendiente vinculado a un pedido. */
export function registrarPago(opts: {
  pedido_id: number;
  gateway: Gateway;
  monto: number;
  transaccion_externa?: string;
  tarjeta_ultimos4?: string;
  notas?: string;
}): Pago {
  const ref = nuevaRef('PAY');
  const info = db.prepare("INSERT INTO pagos (pedido_id, referencia, gateway, estado, monto, transaccion_externa, tarjeta_ultimos4, notas) VALUES (?,?,?,'pendiente',?,?,?,?)")
    .run(opts.pedido_id, ref, opts.gateway, opts.monto, opts.transaccion_externa ?? null, opts.tarjeta_ultimos4 ?? null, opts.notas ?? null);
  recomputarPagoEstado(opts.pedido_id);
  return db.prepare('SELECT * FROM pagos WHERE id = ?').get(Number(info.lastInsertRowid)) as Pago;
}

/** Aprobar un pago (simula confirmación del gateway en producción). */
export function aprobarPago(pagoId: number, transaccionExterna?: string): Pago | null {
  const pago = db.prepare("SELECT * FROM pagos WHERE id = ? AND tipo = 'pago'").get(pagoId) as Pago | undefined;
  if (!pago) return null;
  db.prepare(`UPDATE pagos SET estado='aprobado', pagado_en=datetime('now'), transaccion_externa = COALESCE(?, transaccion_externa) WHERE id=?`)
    .run(transaccionExterna ?? null, pagoId);
  const p = db.prepare('SELECT * FROM pagos WHERE id = ?').get(pagoId) as Pago;
  const estado = recomputarPagoEstado(pago.pedido_id);
  addNotificacion('pago', `Pago ${p.referencia} aprobado (${p.gateway}). Pedido ${p.pedido_id} → ${estado}`, '/admin/pagos');
  return p;
}

/** Marcar un pago como fallido. */
export function fallarPago(pagoId: number, notas?: string): Pago | null {
  const pago = db.prepare("SELECT * FROM pagos WHERE id = ? AND tipo = 'pago'").get(pagoId) as Pago | undefined;
  if (!pago) return null;
  db.prepare(`UPDATE pagos SET estado='fallido', notas = COALESCE(?, notas) WHERE id=?`).run(notas ?? null, pagoId);
  recomputarPagoEstado(pago.pedido_id);
  addNotificacion('pago', `Pago ${pago.referencia} marcado como fallido`, '/admin/pagos');
  return db.prepare('SELECT * FROM pagos WHERE id = ?').get(pagoId) as Pago;
}

/** Crea un reembolso aprobado (total o parcial). */
export function crearReembolso(pedidoId: number, monto: number, notas?: string): Pago | null {
  const ped = db.prepare('SELECT total FROM pedidos WHERE id = ?').get(pedidoId) as { total: number } | undefined;
  if (!ped || monto <= 0) return null;
  const reembolsado = (db.prepare(`SELECT COALESCE(SUM(monto),0) s FROM pagos WHERE pedido_id=? AND tipo='reembolso' AND estado='aprobado'`).get(pedidoId) as { s: number }).s;
  const pagado = (db.prepare(`SELECT COALESCE(SUM(monto),0) s FROM pagos WHERE pedido_id=? AND tipo='pago' AND estado='aprobado'`).get(pedidoId) as { s: number }).s;
  const reembolsable = Math.min(monto, pagado - reembolsado);
  if (reembolsable <= 0) return null;
  const ref = nuevaRef('RFD');
  const info = db.prepare(`INSERT INTO pagos (pedido_id, referencia, gateway, tipo, estado, monto, notas, reembolsado_en) VALUES (?,?,?,'reembolso','aprobado',?,?,datetime('now'))`)
    .run(pedidoId, ref, 'manual', reembolsable, notas ?? null);
  recomputarPagoEstado(pedidoId);
  addNotificacion('pago', `Reembolso ${ref} por S/ ${reembolsable.toFixed(2)} procesado`, '/admin/pagos');
  return db.prepare('SELECT * FROM pagos WHERE id = ?').get(Number(info.lastInsertRowid)) as Pago;
}

export const getPagos = (filtro?: { estado?: string; gateway?: string }) => {
  let sql = `SELECT p.*, pe.referencia AS pedido_ref, u.nombre AS cliente
    FROM pagos p JOIN pedidos pe ON pe.id = p.pedido_id LEFT JOIN usuarios u ON u.id = pe.usuario_id WHERE 1=1`;
  const args: any[] = [];
  if (filtro?.estado && filtro.estado !== 'todos') { sql += ' AND p.estado = ?'; args.push(filtro.estado); }
  if (filtro?.gateway && filtro.gateway !== 'todos') { sql += ' AND p.gateway = ?'; args.push(filtro.gateway); }
  sql += ' ORDER BY p.id DESC';
  return db.prepare(sql).all(...args) as Pago[];
};

export const getPagosDePedido = (pedidoId: number) =>
  db.prepare('SELECT * FROM pagos WHERE pedido_id = ? ORDER BY id').all(pedidoId) as Pago[];

export const getEstadisticasPago = () => {
  const aprobado = (db.prepare(`SELECT COALESCE(SUM(monto),0) t FROM pagos WHERE tipo='pago' AND estado='aprobado'`).get() as { t: number }).t;
  const pendiente = (db.prepare(`SELECT COALESCE(SUM(monto),0) t FROM pagos WHERE tipo='pago' AND estado='pendiente'`).get() as { t: number }).t;
  const fallido = (db.prepare(`SELECT COALESCE(SUM(monto),0) t FROM pagos WHERE tipo='pago' AND estado='fallido'`).get() as { t: number }).t;
  const reembolsado = (db.prepare(`SELECT COALESCE(SUM(monto),0) t FROM pagos WHERE tipo='reembolso' AND estado='aprobado'`).get() as { t: number }).t;
  const neto = aprobado - reembolsado;
  return { aprobado, pendiente, fallido, reembolsado, neto };
};

export const formatEUR = (n: number) =>
  new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(n);