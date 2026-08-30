import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol !== 'admin') return redirect('/admin');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const target = db.prepare('SELECT rol FROM usuarios WHERE id = ?').get(id) as { rol: string } | undefined;
  if (target && target.rol !== 'admin') {
    const act = db.prepare('SELECT activo FROM usuarios WHERE id = ?').get(id) as { activo: number };
    db.prepare('UPDATE usuarios SET activo = ? WHERE id = ?').run(act.activo ? 0 : 1, id);
  }
  return redirect('/admin/usuarios');
};