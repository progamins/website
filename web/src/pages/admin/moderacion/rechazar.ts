import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  db.prepare("UPDATE valoraciones SET estado = 'rechazada' WHERE id = ?").run(Number(form.get('id')));
  return redirect('/admin/moderacion');
};