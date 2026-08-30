import type { APIRoute } from 'astro';
import { db, addNotificacion } from '../../lib/db';

export const POST: APIRoute = async ({ request, locals }) => {
  const user = locals.user;
  if (!user) return new Response(JSON.stringify({ ok: false, error: 'Debes iniciar sesión' }), { status: 401 });
  const body = await request.json();
  const productoId = Number(body.producto_id);
  const puntuacion = Number(body.puntuacion);
  const comentario = (body.comentario ?? '').toString().trim();
  if (!productoId || !puntuacion || puntuacion < 1 || puntuacion > 5) {
    return new Response(JSON.stringify({ ok: false, error: 'Datos inválidos' }), { status: 400 });
  }
  const nombre = (user.nombre + (user.apellidos ? ' ' + user.apellidos : '')).trim() || user.email;
  db.prepare('INSERT INTO valoraciones (producto_id, usuario_id, nombre, puntuacion, comentario, estado) VALUES (?,?,?,?,?,?)')
    .run(productoId, user.id, nombre, puntuacion, comentario, 'pendiente');
  addNotificacion('moderacion', `Nueva valoración pendiente en producto #${productoId}`, '/admin/moderacion');
  return new Response(JSON.stringify({ ok: true }), { headers: { 'Content-Type': 'application/json' } });
};