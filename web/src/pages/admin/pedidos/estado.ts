import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const estado = (form.get('estado') ?? '').toString();
  const validos = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
  if (validos.includes(estado)) db.prepare('UPDATE pedidos SET estado = ? WHERE id = ?').run(estado, id);
  return redirect('/admin/pedidos');
};