import type { APIRoute } from 'astro';
import { getNotificacionesUsuario, countNotificacionesNoLeidasUsuario } from '../../../lib/notificaciones';

/**
 * Lista las notificaciones del usuario autenticado (solo las suyas).
 * GET /api/notificaciones/mias?limit=20
 */
export const GET: APIRoute = async ({ locals, url }) => {
  const user = locals.user;
  if (!user) return new Response(JSON.stringify({ error: 'No autorizado' }), { status: 401, headers: { 'Content-Type': 'application/json' } });
  const limit = Math.min(Math.max(Number(url.searchParams.get('limit')) || 20, 1), 50);
  const items = getNotificacionesUsuario(user.id, limit);
  const noLeidas = countNotificacionesNoLeidasUsuario(user.id);
  return new Response(
    JSON.stringify({ items, noLeidas }),
    { headers: { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' } }
  );
};
