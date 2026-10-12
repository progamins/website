/**
 * Limpieza tras las pruebas de QA: elimina los usuarios de prueba
 * (qa-*@*.test), sus sesiones, pedidos, pagos, detalles, favoritos,
 * valoraciones, notificaciones y tokens de reset. Restaura el stock
 * consumido por los pedidos de prueba y borra las notificaciones del
 * panel admin asociadas a esos pedidos.
 * SOLO para desarrollo: nunca en producción.
 * Uso: node scripts/limpiar-qa.mjs
 */
import Database from 'better-sqlite3';

const db = new Database('data/chollo.db');

const limpiar = db.transaction(() => {
  const usuarios = db.prepare(
    "SELECT id FROM usuarios WHERE email LIKE 'qa-%@%' OR email LIKE '%@cholloyglam.test' OR email LIKE '%@nadie.test'"
  ).all();
  const refsBorradas = [];
  let stockRestaurado = 0;

  for (const u of usuarios) {
    const pedidos = db.prepare('SELECT id, referencia FROM pedidos WHERE usuario_id = ?').all(u.id);
    for (const p of pedidos) {
      // Restaura el stock consumido por el pedido de prueba
      const detalles = db.prepare('SELECT producto_id, cantidad FROM pedido_detalles WHERE pedido_id = ?').all(p.id);
      for (const d of detalles) {
        if (d.producto_id) {
          db.prepare('UPDATE productos SET stock = stock + ? WHERE id = ?').run(d.cantidad, d.producto_id);
          stockRestaurado += d.cantidad;
        }
      }
      refsBorradas.push(p.referencia);
      db.prepare('DELETE FROM pagos WHERE pedido_id = ?').run(p.id);
      db.prepare('DELETE FROM pedido_detalles WHERE pedido_id = ?').run(p.id);
    }
    db.prepare('DELETE FROM pedidos WHERE usuario_id = ?').run(u.id);
    db.prepare('DELETE FROM sesiones WHERE usuario_id = ?').run(u.id);
    db.prepare('DELETE FROM lista_deseos WHERE usuario_id = ?').run(u.id);
    db.prepare('DELETE FROM valoraciones WHERE usuario_id = ?').run(u.id);
    db.prepare('DELETE FROM notificaciones_usuario WHERE usuario_id = ?').run(u.id);
    db.prepare('DELETE FROM password_resets WHERE usuario_id = ?').run(u.id);
    db.prepare('DELETE FROM usuarios WHERE id = ?').run(u.id);
  }

  // Notificaciones del panel admin vinculadas a los pedidos de prueba
  for (const ref of refsBorradas) {
    db.prepare('DELETE FROM notificaciones WHERE mensaje LIKE ?').run(`%${ref}%`);
  }
  // Tokens de reset huérfanos de pruebas
  db.prepare('DELETE FROM password_resets').run();

  return { usuarios: usuarios.length, pedidos: refsBorradas.length, stockRestaurado };
});

const r = limpiar();
console.log(`Limpieza de QA: ${r.usuarios} usuario(s), ${r.pedidos} pedido(s), stock restaurado: ${r.stockRestaurado} unidad(es).`);
