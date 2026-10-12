import type { APIRoute } from 'astro';
import { marcarLeida, marcarTodasLeidas, countNotificacionesNoLeidasUsuario } from '../../../lib/notificaciones';

/**
 * Marca notificaciones como leídas. Solo afecta a las del usuario autenticado.
 * POST /api/notificaciones/leer  { id: number }  → marca una
 * POST /api/notificaciones/leer  { todas: true } → marca todas
 */
export const POST: APIRoute = async ({ request, locals }) => {
  const user = locals.user;
  if (!user) return new Response(JSON.stringify({ error: 'No autorizado' }), { status: 401, headers: { 'Content-Type': 'application/json' } });

  let body: { id?: unknown; todas?: unknown } = {};
  try { body = await request.json(); } catch { /* cuerpo vacío */ }

  let cambio = false;
  if (body.todas === true) {
    cambio = marcarTodasLeidas(user.id) > 0;
  } else if (Number.isInteger(Number(body.id)) && Number(body.id) > 0) {
    cambio = marcarLeida(user.id, Number(body.id));
  } else {
    return new Response(JSON.stringify({ error: 'Solicitud inválida' }), { status: 400, headers: { 'Content-Type': 'application/json' } });
  }

  return new Response(
    JSON.stringify({ ok: true, cambio, noLeidas: countNotificacionesNoLeidasUsuario(user.id) }),
    { headers: { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' } }
  );
};
