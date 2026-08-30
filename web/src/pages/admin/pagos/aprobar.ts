import type { APIRoute } from 'astro';
import { aprobarPago } from '../../../lib/pagos';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  if (id) aprobarPago(id);
  return redirect('/admin/pagos');
};