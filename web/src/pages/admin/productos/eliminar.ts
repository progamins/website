import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  db.prepare('DELETE FROM productos WHERE id = ?').run(id);
  return redirect('/admin/productos');
};