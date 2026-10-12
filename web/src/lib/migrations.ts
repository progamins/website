import type { Database } from 'better-sqlite3';

/**
 * Migraciones versionadas de la base de datos.
 *
 * - Cada entrada es idempotente (CREATE TABLE IF NOT EXISTS / índices IF NOT EXISTS),
 *   por lo que es seguro ejecutarla en cada arranque de proceso, incluidas las
 *   instancias efímeras de Vercel que copian la BD a /tmp.
 * - Nunca destruye datos: solo añade tablas/índices nuevos.
 * - El esquema base vive en lib/schema.sql (aplicado al crear la BD); aquí
 *   se registran las evoluciones posteriores.
 */
interface Migracion {
  version: number;
  nombre: string;
  sql: string[];
}

export const MIGRACIONES: Migracion[] = [
  {
    version: 1,
    nombre: 'notificaciones-de-cliente',
    sql: [
      `CREATE TABLE IF NOT EXISTS notificaciones_usuario (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
        tipo TEXT NOT NULL DEFAULT 'pedido',
        titulo TEXT NOT NULL,
        mensaje TEXT NOT NULL,
        enlace TEXT,
        pedido_id INTEGER REFERENCES pedidos(id) ON DELETE SET NULL,
        dedup_key TEXT,
        leida INTEGER NOT NULL DEFAULT 0,
        leido_en TEXT,
        creado_en TEXT NOT NULL DEFAULT (datetime('now'))
      )`,
      `CREATE INDEX IF NOT EXISTS idx_notif_usuario
        ON notificaciones_usuario(usuario_id, leida)`,
      // Deduplicación por evento: evita duplicar la misma notificación si un
      // evento se procesa dos veces (doble clic, reintento, etc.)
      `CREATE UNIQUE INDEX IF NOT EXISTS idx_notif_dedup
        ON notificaciones_usuario(dedup_key) WHERE dedup_key IS NOT NULL`,
    ],
  },
  {
    version: 2,
    nombre: 'recuperacion-de-contrasena',
    sql: [
      `CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
        token_hash TEXT NOT NULL UNIQUE,
        expira_en TEXT NOT NULL,
        usado_en TEXT,
        creado_en TEXT NOT NULL DEFAULT (datetime('now'))
      )`,
      `CREATE INDEX IF NOT EXISTS idx_presets_usuario
        ON password_resets(usuario_id)`,
    ],
  },
];

let aplicadas = false;

/** Aplica las migraciones pendientes. Segura para llamar varias veces. */
export function aplicarMigraciones(db: Database): void {
  if (aplicadas) return;
  try {
    const run = db.transaction(() => {
      for (const m of MIGRACIONES) {
        for (const sql of m.sql) db.exec(sql);
      }
    });
    run();
    aplicadas = true;
  } catch (e) {
    // BD de solo lectura (degradación serverless): el catálogo sigue funcionando
    console.error('[migraciones] omitidas (BD de solo lectura):', e instanceof Error ? e.message : e);
  }
}
