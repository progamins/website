import type { APIRoute } from 'astro';
import { verificarLogin, crearSesion, COOKIE_NAME, estaBloqueadoLogin, registrarFalloLogin, limpiarFallosLogin } from '../../../lib/auth';

export const POST: APIRoute = async ({ request, cookies, redirect, clientAddress }) => {
  const form = await request.formData();
  const email = (form.get('email') ?? '').toString();
  const password = (form.get('password') ?? '').toString();
  const next = (form.get('next') ?? '/').toString();

  // Rate limiting: bloqueo temporal por IP + email tras 5 fallos por minuto
  const ip = clientAddress ?? 'desconocida';
  const claveRate = `login:${ip}:${email.toLowerCase()}`;
  if (estaBloqueadoLogin(claveRate)) {
    return redirect('/login?error=' + encodeURIComponent('Demasiados intentos. Espera unos minutos e inténtalo de nuevo.'));
  }

  const user = verificarLogin(email, password);
  if (!user) {
    registrarFalloLogin(claveRate);
    return redirect('/login?error=' + encodeURIComponent('Credenciales incorrectas') + (next && next !== '/' ? '&next=' + encodeURIComponent(next) : ''));
  }
  limpiarFallosLogin(claveRate);
  const token = crearSesion(user.id);
  // secure solo en producción (el dev server corre en http://localhost)
  cookies.set(COOKIE_NAME, token, { httpOnly: true, sameSite: 'lax', secure: import.meta.env.PROD, path: '/', maxAge: 60 * 60 * 24 * 7 });
  return redirect(next.startsWith('/') && !next.startsWith('/admin') ? next : '/');
};
