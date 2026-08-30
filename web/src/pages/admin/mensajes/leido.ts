import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const m = db.prepare('SELECT leido FROM mensajes WHERE id = ?').get(id) as { leido: number } | undefined;
  if (m) db.prepare('UPDATE mensajes SET leido = ? WHERE id = ?').run(m.leido ? 0 : 1, id);
  return redirect('/admin/mensajes');
};