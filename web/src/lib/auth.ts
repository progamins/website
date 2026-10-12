import bcrypt from 'bcryptjs';
import crypto from 'node:crypto';
import { db, type Usuario } from './db';

const SESSION_DAYS = 7;
export const COOKIE_NAME = 'cg_session';
export const ADMIN_COOKIE_NAME = 'cg_admin_session';

// ===== Rate limiting (en memoria; suficiente para instancia única) =====
// Evita fuerza bruta contra /login, /admin/login y /registro.
const MAX_INTENTOS_LOGIN = 5;      // intentos por ventana
const VENTANA_MS = 60_000;         // 1 minuto
const BLOQUEO_MS = 5 * 60_000;     // bloqueo 5 min tras agotar intentos

const intentos = new Map<string, { fallos: number[]; bloqueadoHasta: number }>();

function limpiarViejos(entrada: { fallos: number[]; bloqueadoHasta: number }, ahora: number) {
  entrada.fallos = entrada.fallos.filter((t) => ahora - t < VENTANA_MS);
}

/** Registra un intento fallido y aplica bloqueo temporal si supera el límite. */
export function registrarFalloLogin(clave: string): void {
  const ahora = Date.now();
  const entrada = intentos.get(clave) ?? { fallos: [], bloqueadoHasta: 0 };
  limpiarViejos(entrada, ahora);
  entrada.fallos.push(ahora);
  if (entrada.fallos.length >= MAX_INTENTOS_LOGIN) {
    entrada.bloqueadoHasta = ahora + BLOQUEO_MS;
    entrada.fallos = [];
  }
  intentos.set(clave, entrada);
}

/** Limpia el contador tras un login exitoso. */
export function limpiarFallosLogin(clave: string): void {
  intentos.delete(clave);
}

/** Devuelve true si la clave está bloqueada por demasiados intentos. */
export function estaBloqueadoLogin(clave: string): boolean {
  const entrada = intentos.get(clave);
  if (!entrada) return false;
  const ahora = Date.now();
  if (entrada.bloqueadoHasta > ahora) return true;
  if (entrada.bloqueadoHasta <= ahora && entrada.bloqueadoHasta > 0) {
    intentos.delete(clave); // bloqueo expirado
  }
  return false;
}

// ===== Validación de entradas =====
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export function esEmailValido(email: string): boolean {
  return email.length <= 254 && EMAIL_RE.test(email);
}


export function crearSesion(usuarioId: number, dias = SESSION_DAYS): string {
  const token = crypto.randomBytes(32).toString('hex');
  const expira = new Date(Date.now() + dias * 24 * 3600 * 1000)
    .toISOString().slice(0, 19).replace('T', ' ');
  db.prepare('INSERT INTO sesiones (token, usuario_id, expira_en) VALUES (?,?,?)').run(token, usuarioId, expira);
  return token;
}

export function destruirSesion(token: string) {
  db.prepare('DELETE FROM sesiones WHERE token = ?').run(token);
}

export function getUsuarioPorToken(token: string | undefined): Usuario | null {
  if (!token) return null;
  const row = db.prepare(`SELECT u.id, u.nombre, u.apellidos, u.email, u.rol, u.activo, u.telefono
    FROM sesiones s JOIN usuarios u ON u.id = s.usuario_id
    WHERE s.token = ? AND s.expira_en > datetime('now')`).get(token) as Usuario | undefined;
  if (!row || !row.activo) return null;
  return row;
}

export function verificarLogin(email: string, password: string): Usuario | null {
  const u = db.prepare('SELECT * FROM usuarios WHERE email = ?').get(email.toLowerCase().trim()) as (Usuario & { password: string }) | undefined;
  if (!u || !u.activo) return null;
  if (!bcrypt.compareSync(password, u.password)) return null;
  const { password: _p, ...user } = u;
  return user as Usuario;
}

export function verificarLoginAdmin(email: string, password: string): Usuario | null {
  const user = verificarLogin(email, password);
  if (!user || user.rol === 'cliente') return null;
  return user;
}

export function registrarUsuario(nombre: string, apellidos: string, email: string, password: string): { ok: boolean; error?: string; id?: number } {
  email = email.toLowerCase().trim();
  nombre = nombre.trim();
  apellidos = apellidos.trim();
  // Validación de tipos/formato/longitud antes de tocar la BD
  if (!nombre || !email || !password) return { ok: false, error: 'Todos los campos son obligatorios' };
  if (nombre.length > 50 || apellidos.length > 50) return { ok: false, error: 'Nombre o apellidos demasiado largos' };
  if (!esEmailValido(email)) return { ok: false, error: 'El email no es válido' };
  if (password.length < 8) return { ok: false, error: 'La contraseña debe tener al menos 8 caracteres' };
  if (password.length > 128) return { ok: false, error: 'La contraseña no puede superar 128 caracteres' };
  const existe = db.prepare('SELECT id FROM usuarios WHERE email = ?').get(email);
  if (existe) return { ok: false, error: 'Ya existe una cuenta con ese email' };
  const hash = bcrypt.hashSync(password, 12);
  const r = db.prepare("INSERT INTO usuarios (nombre, apellidos, email, password, rol) VALUES (?,?,?,?,'cliente')")
    .run(nombre, apellidos, email, hash);
  return { ok: true, id: Number(r.lastInsertRowid) };
}

// ===== Recuperación de contraseña =====
// El token viaja por email (una sola vez, caduca en 1 h); en la BD solo
// se guarda su hash SHA-256. Al usarlo se invalida y se cierran todas
// las sesiones activas del usuario.

const RESET_MINUTOS = 60;

/** Crea un token de recuperación para un usuario. Devuelve el token en claro. */
export function crearTokenReset(usuarioId: number): string {
  const token = crypto.randomBytes(32).toString('hex');
  const tokenHash = crypto.createHash('sha256').update(token).digest('hex');
  const expira = new Date(Date.now() + RESET_MINUTOS * 60_000).toISOString().slice(0, 19).replace('T', ' ');
  db.prepare('INSERT INTO password_resets (usuario_id, token_hash, expira_en) VALUES (?,?,?)')
    .run(usuarioId, tokenHash, expira);
  return token;
}

/**
 * Valida y consume un token de recuperación.
 * Devuelve el usuario_id si el token es válido, o null si no existe,
 * caducó o ya fue utilizado.
 */
export function consumirTokenReset(token: string): number | null {
  const tokenHash = crypto.createHash('sha256').update(token).digest('hex');
  const row = db.prepare(
    `SELECT id, usuario_id FROM password_resets
     WHERE token_hash = ? AND usado_en IS NULL AND expira_en > datetime('now')`
  ).get(tokenHash) as { id: number; usuario_id: number } | undefined;
  if (!row) return null;
  db.prepare(`UPDATE password_resets SET usado_en = datetime('now') WHERE id = ?`).run(row.id);
  return row.usuario_id;
}

/** Cambia la contraseña de un usuario e invalida TODAS sus sesiones activas. */
export function cambiarPassword(usuarioId: number, nuevaPassword: string): { ok: boolean; error?: string } {
  if (nuevaPassword.length < 8) return { ok: false, error: 'La contraseña debe tener al menos 8 caracteres' };
  if (nuevaPassword.length > 128) return { ok: false, error: 'La contraseña no puede superar 128 caracteres' };
  const hash = bcrypt.hashSync(nuevaPassword, 12);
  const run = db.transaction(() => {
    db.prepare('UPDATE usuarios SET password = ? WHERE id = ?').run(hash, usuarioId);
    // Cierra todas las sesiones: obliga a iniciar sesión de nuevo en todos los dispositivos
    db.prepare('DELETE FROM sesiones WHERE usuario_id = ?').run(usuarioId);
    // Invalida cualquier otro token de reset pendiente
    db.prepare(`UPDATE password_resets SET usado_en = datetime('now') WHERE usuario_id = ? AND usado_en IS NULL`).run(usuarioId);
  });
  run();
  return { ok: true };
}

/**
 * Cambia la contraseña conservando SOLO la sesión indicada (p. ej. la
 * sesión activa del perfil). El resto de sesiones se cierran.
 */
export function cambiarPasswordMantenerSesion(usuarioId: number, nuevaPassword: string, tokenAConservar: string): { ok: boolean; error?: string } {
  if (nuevaPassword.length < 8) return { ok: false, error: 'La contraseña debe tener al menos 8 caracteres' };
  if (nuevaPassword.length > 128) return { ok: false, error: 'La contraseña no puede superar 128 caracteres' };
  const hash = bcrypt.hashSync(nuevaPassword, 12);
  const run = db.transaction(() => {
    db.prepare('UPDATE usuarios SET password = ? WHERE id = ?').run(hash, usuarioId);
    // Cierra las demás sesiones, pero mantiene la actual con su caducidad
    db.prepare('DELETE FROM sesiones WHERE usuario_id = ? AND token != ?').run(usuarioId, tokenAConservar);
    db.prepare(`UPDATE password_resets SET usado_en = datetime('now') WHERE usuario_id = ? AND usado_en IS NULL`).run(usuarioId);
  });
  run();
  return { ok: true };
}

/** Verifica la contraseña actual de un usuario (para cambiarla desde el perfil). */
export function verificarPasswordActual(usuarioId: number, password: string): boolean {
  const u = db.prepare('SELECT password FROM usuarios WHERE id = ?').get(usuarioId) as { password: string } | undefined;
  if (!u) return false;
  return bcrypt.compareSync(password, u.password);
}
