import type { APIRoute } from 'astro';
import { fallarPago } from '../../../lib/pagos';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  if (id) fallarPago(id, 'Marcado como fallido desde el panel de administración');
  return redirect('/admin/pagos');
};