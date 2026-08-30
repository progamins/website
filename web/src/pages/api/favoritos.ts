import type { APIRoute } from 'astro';
import { db } from '../../lib/db';

export const POST: APIRoute = async ({ request, locals }) => {
  const user = locals.user;
  if (!user) return new Response(JSON.stringify({ ok: false, error: 'Debes iniciar sesión' }), { status: 401 });
  const body = await request.json();
  const productoId = Number(body.producto_id);
  if (!productoId) return new Response(JSON.stringify({ ok: false, error: 'Producto inválido' }), { status: 400 });
  const existe = db.prepare('SELECT id FROM lista_deseos WHERE usuario_id = ? AND producto_id = ?').get(user.id, productoId);
  if (existe) {
    db.prepare('DELETE FROM lista_deseos WHERE usuario_id = ? AND producto_id = ?').run(user.id, productoId);
    return new Response(JSON.stringify({ ok: true, favorito: false }), { headers: { 'Content-Type': 'application/json' } });
  }
  db.prepare('INSERT INTO lista_deseos (usuario_id, producto_id) VALUES (?,?)').run(user.id, productoId);
  return new Response(JSON.stringify({ ok: true, favorito: true }), { headers: { 'Content-Type': 'application/json' } });
};