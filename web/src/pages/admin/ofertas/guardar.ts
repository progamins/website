import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const titulo = (form.get('titulo') ?? 'Oferta Flash').toString();
  const tiempo_fin = (form.get('tiempo_fin') ?? '').toString();
  if (!tiempo_fin) return redirect('/admin/ofertas');
  const ids = form.getAll('producto_ids').map((v) => Number(v));
  const crear = db.transaction(() => {
    const r = db.prepare("INSERT INTO ofertas_flash (titulo, tiempo_fin, activa) VALUES (?,?,1)").run(titulo, tiempo_fin.replace('T', ' ') + ':00');
    const ofertaId = Number(r.lastInsertRowid);
    const ins = db.prepare('INSERT INTO productos_oferta_flash (oferta_id, producto_id) VALUES (?,?)');
    for (const pid of ids) ins.run(ofertaId, pid);
  });
  crear();
  return redirect('/admin/ofertas');
};