/**
 * URL pública del sitio para enlaces de email.
 * En Vercel la inyeuta la plataforma; en desarrollo usa localhost.
 */
export function siteUrl(): string {
  const url = (import.meta.env.PUBLIC_SITE_URL ?? (import.meta.env.VERCEL_URL ? `https://${import.meta.env.VERCEL_URL}` : 'http://localhost:4321')).toString();
  return url.replace(/\/+$/, '');
}
