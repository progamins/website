import type { APIRoute } from 'astro';
import { verificarLoginAdmin, crearSesion, ADMIN_COOKIE_NAME, estaBloqueadoLogin, registrarFalloLogin, limpiarFallosLogin } from '../../../lib/auth';

export const POST: APIRoute = async ({ request, cookies, redirect, clientAddress }) => {
  const form = await request.formData();
  const email = (form.get('email') ?? '').toString();
  const password = (form.get('password') ?? '').toString();
  const next = (form.get('next') ?? '/admin').toString();

  // Rate limiting estricto en el panel admin
  const ip = clientAddress ?? 'desconocida';
  const claveRate = `admin:${ip}:${email.toLowerCase()}`;
  if (estaBloqueadoLogin(claveRate)) {
    return redirect('/admin/login?error=' + encodeURIComponent('Demasiados intentos. Espera unos minutos e inténtalo de nuevo.'));
  }

  const user = verificarLoginAdmin(email, password);
  if (!user) {
    registrarFalloLogin(claveRate);
    // Mensaje genérico: no revela si el email existe o si tiene permisos
    return redirect('/admin/login?error=' + encodeURIComponent('Credenciales inválidas o sin permisos de administrador'));
  }
  limpiarFallosLogin(claveRate);
  const token = crearSesion(user.id);
  cookies.set(ADMIN_COOKIE_NAME, token, { httpOnly: true, sameSite: 'lax', secure: import.meta.env.PROD, path: '/', maxAge: 60 * 60 * 24 * 7 });
  return redirect(next.startsWith('/admin') ? next : '/admin');
};
