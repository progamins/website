import type { APIRoute } from 'astro';
import { destruirSesion, COOKIE_NAME } from '../../../lib/auth';

export const POST: APIRoute = async ({ cookies, redirect }) => {
  const token = cookies.get(COOKIE_NAME)?.value;
  if (token) destruirSesion(token);
  cookies.delete(COOKIE_NAME, { path: '/' });
  return redirect('/');
};