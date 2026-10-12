/**
 * Suite de pruebas funcionales — Parte 2 (recuperación, admin→notificaciones,
 * rate limiting, logout). Requiere el servidor Node en foreground:
 *   node dist/server/entry.mjs
 * Uso: node scripts/qa-funcional-2.mjs <ruta-del-log-del-servidor>
 */
const BASE = 'http://localhost:4321';
const LOG = process.argv[2];
import fs from 'node:fs';
import Database from 'better-sqlite3';

// Acceso directo a la BD local SOLO para localizar ids de prueba
// (las operaciones se hacen por HTTP contra la app real)
const dbqa = new Database('data/chollo.db', { readonly: true });

function crearCliente() {
  const jar = new Map();
  const guardarCookies = (res) => {
    for (const c of res.headers.getSetCookie?.() ?? []) {
      const [pair] = c.split(';');
      const [name, ...rest] = pair.split('=');
      if (rest.join('=') === '' || /expires=Thu, 01 Jan 1970/i.test(c) || /Max-Age=0/i.test(c)) jar.delete(name.trim());
      else jar.set(name.trim(), rest.join('='));
    }
  };
  const header = () => Array.from(jar.entries()).map(([k, v]) => `${k}=${v}`).join('; ');
  return {
    cookieHeader: header,
    async get(path) {
      const res = await fetch(BASE + path, { redirect: 'manual', headers: { cookie: header() } });
      guardarCookies(res);
      return res;
    },
    async postForm(path, data) {
      const res = await fetch(BASE + path, { method: 'POST', redirect: 'manual', headers: { cookie: header(), 'content-type': 'application/x-www-form-urlencoded', origin: BASE }, body: new URLSearchParams(data) });
      guardarCookies(res);
      return res;
    },
    async postJson(path, data) {
      const res = await fetch(BASE + path, { method: 'POST', redirect: 'manual', headers: { cookie: header(), 'content-type': 'application/json', origin: BASE }, body: JSON.stringify(data) });
      guardarCookies(res);
      return res;
    },
  };
}

const resultados = [];
let fallos = 0;
function check(nombre, cond, detalle = '') {
  const ok = Boolean(cond);
  if (!ok) fallos++;
  resultados.push(`${ok ? '✅' : '❌'} ${nombre}${detalle && !ok ? ` — ${detalle}` : ''}`);
}
const loc = (r) => decodeURIComponent(r.headers.get('location') ?? '');
const ultimoToken = () => {
  try {
    const txt = fs.readFileSync(LOG, 'utf8');
    const tokens = [...txt.matchAll(/\/recuperar\/([a-f0-9]{64})/g)].map((m) => m[1]);
    return tokens.length ? tokens[tokens.length - 1] : null;
  } catch { return null; }
};

async function main() {
  console.log('=== FASE 13 · Parte 2: recuperación, admin y límites ===\n');

  // ---------- RECUPERACIÓN DE CONTRASEÑA ----------
  console.log('--- Recuperación completa ---');
  const u = crearCliente();
  const email = 'qa-recup-' + Date.now() + '@cholloyglam.test';
  const pass1 = 'ClaveOriginal123';
  const pass2 = 'ClaveNueva456';
  let r = await u.postForm('/api/auth/registro', { nombre: 'Rosa', apellidos: 'Q Recup', email, password: pass1, next: '/cuenta' });
  check('R1 Registro y sesión inicial', loc(r).startsWith('/cuenta') && u.cookieHeader().includes('cg_session='));

  // Solicitud de recuperación
  r = await u.postForm('/api/auth/recuperar', { email });
  check('R2 Solicitud aceptada con respuesta genérica', loc(r).includes('enviado=1'), loc(r));

  const token = ultimoToken();
  check('R3 Token de un solo uso generado (log del servidor, email no configurado)', /^[a-f0-9]{64}$/.test(token ?? ''), 'token no encontrado en el log');

  // Cambio de contraseña con el token
  r = await u.postForm('/api/auth/recuperar/cambiar', { token, password: pass2, password2: pass2 });
  check('R4 Contraseña cambiada → redirige a login con confirmación', loc(r).includes('ok=Contrase%C3%B1a%20actualizada') || loc(r).includes('Contraseña actualizada'), loc(r));

  // La sesión antigua queda invalidada
  r = await u.get('/cuenta');
  check('R5 Sesiones antiguas invalidadas tras el cambio', loc(r).startsWith('/login?next='), loc(r));

  // Login con la nueva contraseña
  const u2 = crearCliente();
  r = await u2.postForm('/api/auth/login', { email, password: pass2, next: '/cuenta' });
  check('R6 Login con la nueva contraseña', loc(r).startsWith('/cuenta'), loc(r));

  // Reutilización del token → rechazada
  const u3 = crearCliente();
  r = await u3.postForm('/api/auth/recuperar/cambiar', { token, password: 'OtraClave789', password2: 'OtraClave789' });
  check('R7 Token reutilizado rechazado', loc(r).includes('caducado') || loc(r).includes('utilizó'), loc(r));

  // Contraseña antigua ya no sirve
  const u4 = crearCliente();
  r = await u4.postForm('/api/auth/login', { email, password: pass1, next: '/' });
  check('R8 Contraseña antigua rechazada', loc(r).includes('Credenciales incorrectas'), loc(r));

  // Notificación de seguridad
  r = await u2.get('/api/notificaciones/mias');
  const notif = await r.json();
  check('R9 Aviso de seguridad notificado al usuario', notif.items.some((n) => n.tipo === 'seguridad'));

  // ---------- ADMIN → NOTIFICACIONES DEL CLIENTE ----------
  console.log('\n--- Admin → notificaciones ---');
  const cliente = crearCliente();
  const cEmail = 'qa-pedidos-' + Date.now() + '@cholloyglam.test';
  await cliente.postForm('/api/auth/registro', { nombre: 'Pedro', apellidos: 'Q Pedidos', email: cEmail, password: 'ClavePedidos123', next: '/' });
  r = await cliente.postJson('/api/carrito/crear', {
    lineas: [{ producto_id: 2, cantidad: 1 }],
    nombre_envio: 'Pedro Q', direccion_envio: 'Calle Test 45', ciudad_envio: 'Cusco', codigo_postal: '08000', metodo_pago: 'transferencia',
  });
  const ped = await r.json();
  check('N1 Pedido de prueba creado', ped.ok === true, JSON.stringify(ped));

  const admin = crearCliente();
  r = await admin.postForm('/api/auth/admin-login', { email: 'qa-admin@cholloyglam.test', password: 'ClaveAdmin123', next: '/admin' });
  check('N2 Login admin de QA', admin.cookieHeader().includes('cg_admin_session='));

  // Extraer id del pedido directamente de la BD local (QA)
  let pedidoId = null;
  try {
    pedidoId = String(dbqa.prepare('SELECT id FROM pedidos WHERE referencia = ?').get(ped.referencia).id);
  } catch { pedidoId = null; }
  check('N3 Pedido localizado', pedidoId != null, `ref=${ped.referencia}`);

  // Cambio de estado → notificación al cliente
  if (pedidoId) {
    await admin.postForm('/admin/pedidos/estado', { id: pedidoId, estado: 'enviado' });
    r = await cliente.get('/api/notificaciones/mias');
    let d = await r.json();
    check('N4 Cambio de estado notifica al cliente (enviado)', d.items.some((n) => n.tipo === 'pedido_estado' && n.mensaje.includes('camino')), JSON.stringify(d.items.map((n) => n.tipo)));
    const antes = d.items.length;
    // Dedup: repetir el mismo estado no duplica
    await admin.postForm('/admin/pedidos/estado', { id: pedidoId, estado: 'enviado' });
    r = await cliente.get('/api/notificaciones/mias');
    d = await r.json();
    check('N5 Deduplicación: mismo evento no genera duplicado', d.items.length === antes, `antes=${antes} ahora=${d.items.length}`);

    // Aprobar el pago → notificación
    let pagoId = null;
    try {
      pagoId = String(dbqa.prepare("SELECT id FROM pagos WHERE pedido_id = ? AND tipo = 'pago'").get(Number(pedidoId)).id);
    } catch { pagoId = null; }
    check('N6 Pago pendiente localizado', pagoId != null);
    if (pagoId) {
      await admin.postForm('/admin/pagos/aprobar', { id: pagoId });
      r = await cliente.get('/api/notificaciones/mias');
      d = await r.json();
      check('N7 Pago aprobado notifica al cliente', d.items.some((n) => n.tipo === 'pago' && n.titulo.includes('confirmado')), JSON.stringify(d.items.map((n) => n.tipo)));
      // El pedido refleja pago pagado en la cuenta del cliente
      r = await cliente.get('/cuenta');
      const htmlC = await r.text();
      check('N8 Estado de pago visible en Mis pedidos', htmlC.includes('Pagado'));
    }
  }

  // ---------- RATE LIMITING ----------
  console.log('\n--- Rate limiting de login ---');
  const uF = crearCliente();
  let bloqueado = false;
  for (let i = 0; i < 6; i++) {
    r = await uF.postForm('/api/auth/login', { email: 'rate-limit-qa@x.test', password: 'mala', next: '/' });
    if (loc(r).includes('Demasiados intentos')) { bloqueado = true; break; }
  }
  check('L Bloqueo tras intentos fallidos repetidos', bloqueado);

  // ---------- LOGOUT ----------
  console.log('\n--- Logout ---');
  const uL = crearCliente();
  await uL.postForm('/api/auth/login', { email: cEmail, password: 'ClavePedidos123', next: '/cuenta' });
  r = await uL.postForm('/api/auth/logout', {});
  check('L1 Logout limpia la cookie y redirige a inicio', loc(r) === '/' && !uL.cookieHeader().includes('cg_session='), loc(r));
  r = await uL.get('/cuenta');
  check('L2 Pantalla privada inaccesible tras logout', loc(r).startsWith('/login?next='), loc(r));

  // ---------- RESUMEN ----------
  console.log('\n================= RESUMEN (Parte 2) =================');
  resultados.forEach((l) => console.log(l));
  console.log(`\n${resultados.filter((l) => l.startsWith('✅')).length} OK · ${fallos} FALLOS`);
  process.exit(fallos ? 1 : 0);
}

main().catch((e) => { console.error('ERROR DE LA SUITE:', e); process.exit(2); });
