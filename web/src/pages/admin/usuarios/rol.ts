import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol !== 'admin') return redirect('/admin');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const rol = (form.get('rol') ?? '').toString();
  if (rol === 'admin' || !['admin', 'moderador', 'cliente'].includes(rol)) return redirect('/admin/usuarios');
  const target = db.prepare('SELECT rol FROM usuarios WHERE id = ?').get(id) as { rol: string } | undefined;
  if (target && target.rol !== 'admin') db.prepare('UPDATE usuarios SET rol = ? WHERE id = ?').run(rol, id);
  return redirect('/admin/usuarios');
};