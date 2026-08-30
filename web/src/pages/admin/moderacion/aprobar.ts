import type { APIRoute } from 'astro';
import { db, recalcularValoracion, addNotificacion } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const v = db.prepare('SELECT producto_id FROM valoraciones WHERE id = ?').get(id) as { producto_id: number } | undefined;
  if (v) {
    db.prepare("UPDATE valoraciones SET estado = 'aprobada' WHERE id = ?").run(id);
    recalcularValoracion(v.producto_id);
    addNotificacion('info', 'Valoración aprobada', '/admin/moderacion');
  }
  return redirect('/admin/moderacion');
};