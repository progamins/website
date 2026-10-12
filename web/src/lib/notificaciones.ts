import { db } from './db';

/**
 * Centro de notificaciones del cliente.
 * Cada consulta está limitada al usuario autenticado: nunca se filtran
 * notificaciones entre cuentas. La deduplicación por (pedido_id, tipo)
 * evita notificar dos veces el mismo evento.
 */
export interface NotificacionUsuario {
  id: number;
  usuario_id: number;
  tipo: string;
  titulo: string;
  mensaje: string;
  enlace: string | null;
  pedido_id: number | null;
  leida: number;
  leido_en: string | null;
  creado_en: string;
}

export type TipoNotificacion =
  | 'pedido_creado'
  | 'pedido_estado'
  | 'pago'
  | 'seguridad'
  | 'bienvenida';

/**
 * Crea una notificación para un usuario. Si se indica dedup_key y ya existe
 * una notificación con esa clave, no se crea duplicado. Devuelve true si se creó.
 */
export function addNotificacionUsuario(opts: {
  usuario_id: number;
  tipo: TipoNotificacion;
  titulo: string;
  mensaje: string;
  enlace?: string;
  pedido_id?: number;
  dedup_key?: string;
}): boolean {
  try {
    db.prepare(
      `INSERT INTO notificaciones_usuario (usuario_id, tipo, titulo, mensaje, enlace, pedido_id, dedup_key)
       VALUES (?,?,?,?,?,?,?)`
    ).run(opts.usuario_id, opts.tipo, opts.titulo, opts.mensaje, opts.enlace ?? null, opts.pedido_id ?? null, opts.dedup_key ?? null);
    return true;
  } catch {
    // Choque del índice único (evento duplicado) u BD de solo lectura
    return false;
  }
}

/** Lista las notificaciones del usuario, las más recientes primero. */
export function getNotificacionesUsuario(usuarioId: number, limit = 20): NotificacionUsuario[] {
  return db.prepare(
    `SELECT * FROM notificaciones_usuario WHERE usuario_id = ?
     ORDER BY id DESC LIMIT ?`
  ).all(usuarioId, limit) as NotificacionUsuario[];
}

/** Contador de no leídas del usuario (para la campana). */
export function countNotificacionesNoLeidasUsuario(usuarioId: number): number {
  return (db.prepare(
    `SELECT COUNT(*) n FROM notificaciones_usuario WHERE usuario_id = ? AND leida = 0`
  ).get(usuarioId) as { n: number }).n;
}

/** Marca una notificación como leída solo si pertenece al usuario. */
export function marcarLeida(usuarioId: number, notificacionId: number): boolean {
  const r = db.prepare(
    `UPDATE notificaciones_usuario
     SET leida = 1, leido_en = datetime('now')
     WHERE id = ? AND usuario_id = ? AND leida = 0`
  ).run(notificacionId, usuarioId);
  return r.changes > 0;
}

/** Marca todas las notificaciones del usuario como leídas. */
export function marcarTodasLeidas(usuarioId: number): number {
  const r = db.prepare(
    `UPDATE notificaciones_usuario
     SET leida = 1, leido_en = datetime('now')
     WHERE usuario_id = ? AND leida = 0`
  ).run(usuarioId);
  return r.changes;
}

/** Etiquetas y destinos por tipo de notificación (interfaz). */
export const TIPO_META: Record<string, { label: string; icono: string }> = {
  pedido_creado: { label: 'Pedido', icono: 'M3 3h18l-1 17-8 2-8-2L3 3zm4 4h10M7 11h10' },
  pedido_estado: { label: 'Estado', icono: 'M13 16l4-4m0 0l-4-4m4 4H5M19 4H5a1 1 0 00-1 1v14a1 1 0 001 1h14a1 1 0 001-1V5a1 1 0 00-1-1z' },
  pago: { label: 'Pago', icono: 'M3 10h18M3 14h18M6 6h12M6 18h12' },
  seguridad: { label: 'Seguridad', icono: 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.2a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.2a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.9.3h.1a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.2a1.7 1.7 0 001 1.5h.1a1.7 1.7 0 001.9-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.9v.1a1.7 1.7 0 001.5 1h.2a2 2 0 110 4h-.2a1.7 1.7 0 00-1.5 1z' },
  bienvenida: { label: 'Bienvenida', icono: 'M20 12v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7m16 0l-8-8-8 8m16 0h-4' },
};
