import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';
import { registrarPago, type Gateway } from '../../../lib/pagos';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const pedidoId = Number(form.get('pedido_id'));
  const gateway = (form.get('gateway') ?? 'manual').toString() as Gateway;
  const monto = Number(form.get('monto'));
  const notas = (form.get('notas') ?? '').toString();
  const ped = db.prepare('SELECT id FROM pedidos WHERE id = ?').get(pedidoId) as { id: number } | undefined;
  if (!ped || !monto || monto <= 0) return redirect('/admin/pagos/registrar?error=' + encodeURIComponent('Datos inválidos'));
  registrarPago({ pedido_id: pedidoId, gateway, monto, notas: notas || undefined });
  return redirect('/admin/pagos');
};