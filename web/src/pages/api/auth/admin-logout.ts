import type { APIRoute } from 'astro';
import { destruirSesion, ADMIN_COOKIE_NAME } from '../../../lib/auth';

export const POST: APIRoute = async ({ cookies, redirect }) => {
  const token = cookies.get(ADMIN_COOKIE_NAME)?.value;
  if (token) destruirSesion(token);
  cookies.delete(ADMIN_COOKIE_NAME, { path: '/' });
  return redirect('/admin/login');
};