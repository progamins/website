import type { APIRoute } from 'astro';
import { registrarUsuario, crearSesion, COOKIE_NAME, estaBloqueadoLogin, registrarFalloLogin } from '../../../lib/auth';
import { addNotificacionUsuario } from '../../../lib/notificaciones';

export const POST: APIRoute = async ({ request, cookies, redirect, clientAddress }) => {
  const form = await request.formData();
  const nombre = (form.get('nombre') ?? '').toString();
  const apellidos = (form.get('apellidos') ?? '').toString();
  const email = (form.get('email') ?? '').toString();
  const password = (form.get('password') ?? '').toString();
  const next = (form.get('next') ?? '/').toString();

  // Rate limiting: evita creación masiva de cuentas por bot
  const ip = clientAddress ?? 'desconocida';
  const claveRate = `registro:${ip}`;
  if (estaBloqueadoLogin(claveRate)) {
    return redirect('/registro?error=' + encodeURIComponent('Demasiados registros desde esta conexión. Espera unos minutos.'));
  }

  const r = registrarUsuario(nombre, apellidos, email, password);
  if (!r.ok) {
    registrarFalloLogin(claveRate);
    return redirect('/registro?error=' + encodeURIComponent(r.error ?? 'Error'));
  }
  const token = crearSesion(r.id!);
  cookies.set(COOKIE_NAME, token, { httpOnly: true, sameSite: 'lax', secure: import.meta.env.PROD, path: '/', maxAge: 60 * 60 * 24 * 7 });
  // Notificación de bienvenida real para el nuevo cliente
  addNotificacionUsuario({
    usuario_id: r.id!,
    tipo: 'bienvenida',
    titulo: '¡Bienvenida a Chollo & Glam!',
    mensaje: 'Gracias por unirte. Explora nuestras colecciones de joyería artesanal peruana.',
    enlace: '/novedades',
    dedup_key: `bienvenida:${r.id}`,
  });
  return redirect(next.startsWith('/') && !next.startsWith('/admin') ? next : '/');
};
