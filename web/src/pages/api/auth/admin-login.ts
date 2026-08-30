import type { APIRoute } from 'astro';
import { verificarLoginAdmin, crearSesion, ADMIN_COOKIE_NAME } from '../../../lib/auth';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const email = (form.get('email') ?? '').toString();
  const password = (form.get('password') ?? '').toString();
  const next = (form.get('next') ?? '/admin').toString();
  const user = verificarLoginAdmin(email, password);
  if (!user) {
    return redirect('/admin/login?error=' + encodeURIComponent('Credenciales inválidas o sin permisos de administrador'));
  }
  const token = crearSesion(user.id);
  cookies.set(ADMIN_COOKIE_NAME, token, { httpOnly: true, sameSite: 'lax', path: '/', maxAge: 60 * 60 * 24 * 7 });
  return redirect(next.startsWith('/admin') ? next : '/admin');
};