import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const c = db.prepare('SELECT activa FROM colecciones WHERE id = ?').get(id) as { activa: number } | undefined;
  if (c) db.prepare('UPDATE colecciones SET activa = ? WHERE id = ?').run(c.activa ? 0 : 1, id);
  return redirect('/admin/colecciones');
};