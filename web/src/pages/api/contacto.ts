import type { APIRoute } from 'astro';
import { db, addNotificacion } from '../../lib/db';

export const POST: APIRoute = async ({ request }) => {
  try {
    const body = await request.json();
    const nombre = (body.nombre ?? '').toString().trim();
    const email = (body.email ?? '').toString().trim();
    const asunto = (body.asunto ?? '').toString().trim();
    const mensaje = (body.mensaje ?? '').toString().trim();
    if (!nombre || !email || !mensaje) return new Response(JSON.stringify({ ok: false, error: 'Campos obligatorios' }), { status: 400 });
    db.prepare('INSERT INTO mensajes (nombre, email, asunto, mensaje) VALUES (?,?,?,?)').run(nombre, email, asunto, mensaje);
    addNotificacion('contacto', `Nuevo mensaje de ${nombre}: ${asunto || 'sin asunto'}`, '/admin/mensajes');
    return new Response(JSON.stringify({ ok: true }), { headers: { 'Content-Type': 'application/json' } });
  } catch {
    return new Response(JSON.stringify({ ok: false, error: 'Error' }), { status: 500 });
  }
};