import { defineMiddleware } from 'astro:middleware';
import { getUsuarioPorToken, COOKIE_NAME, ADMIN_COOKIE_NAME } from './lib/auth';

export const onRequest = defineMiddleware(async (context, next) => {
  const path = context.url.pathname;
  const isAdminRoute = path.startsWith('/admin');

  // ===== Zona admin: sesión propia (cookie cg_admin_session) =====
  if (isAdminRoute) {
    // Página de login del admin: accesible libremente (si ya hay sesión, va al panel)
    if (path === '/admin/login') {
      const adminToken = context.cookies.get(ADMIN_COOKIE_NAME)?.value;
      const admin = getUsuarioPorToken(adminToken);
      if (admin && admin.rol !== 'cliente') {
        context.locals.user = admin;
        return context.redirect('/admin');
      }
      context.locals.user = null;
      return next();
    }
    const adminToken = context.cookies.get(ADMIN_COOKIE_NAME)?.value;
    const admin = getUsuarioPorToken(adminToken);
    if (!admin || admin.rol === 'cliente') {
      return context.redirect('/admin/login');
    }
    context.locals.user = admin;
    // Moderador no puede gestionar usuarios
    if (admin.rol === 'moderador' && path.startsWith('/admin/usuarios')) {
      return context.redirect('/admin');
    }
    return next();
  }

  // ===== Zona cliente: sesión propia (cookie cg_session) =====
  const token = context.cookies.get(COOKIE_NAME)?.value;
  context.locals.user = getUsuarioPorToken(token);

  const user = context.locals.user;
  if ((path.startsWith('/cuenta') || path.startsWith('/favoritos') || path.startsWith('/checkout')) && !user) {
    return context.redirect('/login?next=' + encodeURIComponent(path));
  }

  return next();
});