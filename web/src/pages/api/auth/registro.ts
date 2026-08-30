import type { APIRoute } from 'astro';
import { registrarUsuario, crearSesion, COOKIE_NAME } from '../../../lib/auth';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const nombre = (form.get('nombre') ?? '').toString();
  const apellidos = (form.get('apellidos') ?? '').toString();
  const email = (form.get('email') ?? '').toString();
  const password = (form.get('password') ?? '').toString();
  const next = (form.get('next') ?? '/').toString();
  const r = registrarUsuario(nombre, apellidos, email, password);
  if (!r.ok) return redirect('/registro?error=' + encodeURIComponent(r.error ?? 'Error'));
  const token = crearSesion(r.id!);
  cookies.set(COOKIE_NAME, token, { httpOnly: true, sameSite: 'lax', path: '/', maxAge: 60 * 60 * 24 * 7 });
  return redirect(next.startsWith('/') ? next : '/');
};