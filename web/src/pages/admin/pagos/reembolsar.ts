import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';
import { crearReembolso } from '../../../lib/pagos';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const pedidoId = Number(form.get('pedido_id'));
  const row = db.prepare('SELECT total FROM pedidos WHERE id = ?').get(pedidoId) as { total: number } | undefined;
  if (row) crearReembolso(pedidoId, row.total, 'Reembolso total desde el panel de administración');
  return redirect('/admin/pagos');
};