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


export function crearSesion(usuarioId: number): string {
  const token = crypto.randomBytes(32).toString('hex');
  const expira = new Date(Date.now() + SESSION_DAYS * 24 * 3600 * 1000)
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
