import type { APIRoute } from 'astro';
import { db, addNotificacion } from '../../../lib/db';
import { registrarPago, type Gateway } from '../../../lib/pagos';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user) return redirect('/login');
  const body = await request.json();
  const lineas = Array.isArray(body.lineas) ? body.lineas : [];
  if (!lineas.length) return new Response(JSON.stringify({ ok: false, error: 'Carrito vacío' }), { status: 400 });

  const shipping = {
    nombre_envio: (body.nombre_envio ?? '').toString().trim(),
    direccion_envio: (body.direccion_envio ?? '').toString().trim(),
    ciudad_envio: (body.ciudad_envio ?? '').toString().trim(),
    codigo_postal: (body.codigo_postal ?? '').toString().trim(),
    telefono_envio: (body.telefono_envio ?? '').toString().trim(),
    notas: (body.notas ?? '').toString().trim(),
    metodo_pago: (body.metodo_pago ?? 'tarjeta').toString().trim(),
  };
  if (!shipping.nombre_envio || !shipping.direccion_envio || !shipping.ciudad_envio || !shipping.codigo_postal) {
    return new Response(JSON.stringify({ ok: false, error: 'Completa los datos de envío' }), { status: 400 });
  }

  const insertPed = db.prepare(`INSERT INTO pedidos
    (usuario_id, referencia, total, estado, nombre_envio, direccion_envio, ciudad_envio, codigo_postal, telefono_envio, notas, metodo_pago)
    VALUES (?,?,?,'pendiente',?,?,?,?,?,?,?)`);
  const insertDet = db.prepare('INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)');
  const updStock = db.prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');

  const crear = db.transaction(() => {
    let total = 0;
    const compras: { id: number; nombre: string; qty: number; precio: number }[] = [];
    for (const l of lineas) {
      const p = db.prepare('SELECT id, nombre, precio_actual, stock FROM productos WHERE id = ? AND activo = 1').get(Number(l.producto_id)) as any;
      if (!p) continue;
      const qty = Math.min(Number(l.cantidad) || 1, p.stock);
      if (qty <= 0) continue;
      total += p.precio_actual * qty;
      compras.push({ id: p.id, nombre: p.nombre, qty, precio: p.precio_actual });
    }
    if (!compras.length) throw new Error('No hay productos válidos');
    const referencia = 'PED-' + Date.now().toString().slice(-8);
    const res = insertPed.run(user.id, referencia, Math.round(total * 100) / 100,
      shipping.nombre_envio, shipping.direccion_envio, shipping.ciudad_envio, shipping.codigo_postal,
      shipping.telefono_envio, shipping.notas, shipping.metodo_pago);
    const pedidoId = Number(res.lastInsertRowid);
    for (const c of compras) {
      insertDet.run(pedidoId, c.id, c.qty, c.precio, Math.round(c.precio * c.qty * 100) / 100);
      updStock.run(c.qty, c.id);
      const restante = (db.prepare('SELECT stock FROM productos WHERE id = ?').get(c.id) as { stock: number }).stock;
      if (restante === 0) {
        addNotificacion('stock', `El producto "${c.nombre}" se ha agotado`, '/admin/productos');
      }
    }
    return { pedidoId, referencia, total };
  });

  try {
    const r = crear();
    const gateway = (['tarjeta', 'paypal', 'transferencia'].includes(shipping.metodo_pago) ? shipping.metodo_pago : 'manual') as Gateway;
    registrarPago({
      pedido_id: r.pedidoId,
      gateway,
      monto: Math.round(r.total * 100) / 100,
      tarjeta_ultimos4: gateway === 'tarjeta' ? '4242' : undefined,
      transaccion_externa: gateway !== 'transferencia' && gateway !== 'manual' ? 'TXN-SIM-' + r.referencia : undefined,
      notas: 'Pago registrado desde el checkout',
    });
    addNotificacion('pedido', `Nuevo pedido ${r.referencia} por S/ ${r.total.toFixed(2)} (${shipping.metodo_pago})`, '/admin/pedidos');
    return new Response(JSON.stringify({ ok: true, referencia: r.referencia, total: r.total }), { headers: { 'Content-Type': 'application/json' } });
  } catch (e: any) {
    return new Response(JSON.stringify({ ok: false, error: e.message }), { status: 400 });
  }
};