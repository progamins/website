import type { APIRoute } from 'astro';
import { getUsuarioPorToken, ADMIN_COOKIE_NAME } from '../../../lib/auth';
import { countNotificacionesNoLeidas } from '../../../lib/db';

export const GET: APIRoute = async ({ cookies }) => {
  const token = cookies.get(ADMIN_COOKIE_NAME)?.value;
  const admin = getUsuarioPorToken(token);
  if (!admin || admin.rol === 'cliente') {
    return new Response(JSON.stringify({ error: 'No autorizado' }), { status: 403, headers: { 'Content-Type': 'application/json' } });
  }
  return new Response(JSON.stringify({ n: countNotificacionesNoLeidas() }), { headers: { 'Content-Type': 'application/json' } });
};