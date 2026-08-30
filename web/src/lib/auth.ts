import bcrypt from 'bcryptjs';
import crypto from 'node:crypto';
import { db, type Usuario } from './db';

const SESSION_DAYS = 7;
export const COOKIE_NAME = 'cg_session';
export const ADMIN_COOKIE_NAME = 'cg_admin_session';

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
  if (!nombre || !email || !password) return { ok: false, error: 'Todos los campos son obligatorios' };
  if (password.length < 8) return { ok: false, error: 'La contraseña debe tener al menos 8 caracteres' };
  const existe = db.prepare('SELECT id FROM usuarios WHERE email = ?').get(email);
  if (existe) return { ok: false, error: 'Ya existe una cuenta con ese email' };
  const hash = bcrypt.hashSync(password, 10);
  const r = db.prepare("INSERT INTO usuarios (nombre, apellidos, email, password, rol) VALUES (?,?,?,?,'cliente')")
    .run(nombre, apellidos, email, hash);
  return { ok: true, id: Number(r.lastInsertRowid) };
}
