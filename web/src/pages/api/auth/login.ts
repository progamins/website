import type { APIRoute } from 'astro';
import { verificarLogin, crearSesion, COOKIE_NAME } from '../../../lib/auth';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const email = (form.get('email') ?? '').toString();
  const password = (form.get('password') ?? '').toString();
  const next = (form.get('next') ?? '/').toString();
  const user = verificarLogin(email, password);
  if (!user) return redirect('/login?error=' + encodeURIComponent('Credenciales incorrectas') + (next && next !== '/' ? '&next=' + encodeURIComponent(next) : ''));
  const token = crearSesion(user.id);
  cookies.set(COOKIE_NAME, token, { httpOnly: true, sameSite: 'lax', path: '/', maxAge: 60 * 60 * 24 * 7 });
  return redirect(next.startsWith('/') && !next.startsWith('/admin') ? next : '/');
};