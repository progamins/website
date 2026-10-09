import { defineMiddleware } from 'astro:middleware';
import { getUsuarioPorToken, COOKIE_NAME, ADMIN_COOKIE_NAME } from './lib/auth';

export const onRequest = defineMiddleware(async (context, next) => {
  const path = context.url.pathname;

  // ===== 1. Autenticación / autorización (redirige según zona) =====
  const response = await manejarAuth(context, path, next);

  // ===== 2. Security headers en TODAS las respuestas (páginas y API) =====
  aplicarSecurityHeaders(response, path);
  return response;
});

async function manejarAuth(context: any, path: string, next: () => Promise<Response>): Promise<Response> {
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
}

function aplicarSecurityHeaders(response: Response, path: string): void {
  // CSP: permite lo mínimo que la storefront necesita (Tailwind usa <style> inline
  // en dev; en build los estilos van en archivos .css)
  const csp = [
    "default-src 'self'",
    // Astro hydration usa scripts inline con nonce en dev; en prod son módulos externos
    "script-src 'self' 'unsafe-inline'",
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data: https:",
    "font-src 'self' data:",
    "connect-src 'self'",
    "frame-ancestors 'none'",
    "base-uri 'self'",
    "form-action 'self'",
  ].join('; ');

  response.headers.set('Content-Security-Policy', csp);
  response.headers.set('X-Content-Type-Options', 'nosniff');
  response.headers.set('X-Frame-Options', 'DENY');
  response.headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');
  response.headers.set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
  // HSTS solo tiene efecto sobre HTTPS (en localhost dev no hace nada, es inofensivo)
  response.headers.set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
  // No cachear páginas autenticadas ni respuestas de API
  if (path.startsWith('/admin') || path.startsWith('/api') || path.startsWith('/cuenta')) {
    response.headers.set('Cache-Control', 'no-store');
  }
}
