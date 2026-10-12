import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';
import { addNotificacionUsuario } from '../../../lib/notificaciones';

const MENSAJES: Record<string, string> = {
  procesando: 'Tu pedido está siendo preparado.',
  enviado: '¡Tu pedido está en camino! Te avisaremos cuando llegue.',
  entregado: 'Tu pedido ha sido entregado. ¡Gracias por tu compra!',
  cancelado: 'Tu pedido ha sido cancelado. Si crees que es un error, escríbenos.',
  pendiente: 'Tu pedido volvió a estado pendiente.',
};

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = Number(form.get('id'));
  const estado = (form.get('estado') ?? '').toString();
  const validos = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
  if (validos.includes(estado)) {
    const cambios = db.prepare('UPDATE pedidos SET estado = ? WHERE id = ?').run(estado, id);
    // Notificación real al cliente: el estado de su pedido cambió
    if (cambios.changes > 0 && MENSAJES[estado]) {
      const pedido = db.prepare('SELECT usuario_id, referencia FROM pedidos WHERE id = ?').get(id) as
        | { usuario_id: number | null; referencia: string }
        | undefined;
      if (pedido?.usuario_id) {
        addNotificacionUsuario({
          usuario_id: pedido.usuario_id,
          tipo: 'pedido_estado',
          titulo: `Pedido ${pedido.referencia}: ${estado}`,
          mensaje: MENSAJES[estado],
          enlace: '/cuenta#pedidos',
          pedido_id: id,
          dedup_key: `pedido:estado:${id}:${estado}`,
        });
      }
    }
  }
  return redirect('/admin/pedidos?ok=' + encodeURIComponent('Estado del pedido actualizado'));
};
