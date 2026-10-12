/**
 * Crea (o restablece) un usuario admin de QA en la BD local para las
 * pruebas funcionales. SOLO para desarrollo: nunca en producción.
 * Uso: node scripts/crear-qa-admin.mjs
 */
import Database from 'better-sqlite3';
import bcrypt from 'bcryptjs';

const db = new Database('data/chollo.db');
const email = 'qa-admin@cholloyglam.test';
const pass = 'ClaveAdmin123';
const hash = bcrypt.hashSync(pass, 12);

const existe = db.prepare('SELECT id FROM usuarios WHERE email = ?').get(email);
if (existe) {
  db.prepare('UPDATE usuarios SET password = ?, rol = ?, activo = 1 WHERE id = ?').run(hash, 'admin', existe.id);
  db.prepare('DELETE FROM sesiones WHERE usuario_id = ?').run(existe.id);
  console.log(`Admin de QA restablecido (id ${existe.id})`);
} else {
  const r = db.prepare("INSERT INTO usuarios (nombre, apellidos, email, password, rol) VALUES ('QA','Admin',?,?,'admin')").run(email, hash);
  console.log(`Admin de QA creado (id ${Number(r.lastInsertRowid)})`);
}
