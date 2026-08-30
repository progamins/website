import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  db.prepare('UPDATE notificaciones SET leida = 1 WHERE leida = 0').run();
  return redirect('/admin/notificaciones');
};