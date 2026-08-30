import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const p = db.prepare('SELECT activo FROM productos WHERE id = ?').get(id) as { activo: number } | undefined;
  if (p) db.prepare('UPDATE productos SET activo = ? WHERE id = ?').run(p.activo ? 0 : 1, id);
  return redirect('/admin/productos');
};