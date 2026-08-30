import type { APIRoute } from 'astro';
import { db } from '../../lib/db';

export const GET: APIRoute = async ({ url }) => {
  const ids = (url.searchParams.get('ids') ?? '').split(',').map(Number).filter(Boolean);
  if (!ids.length) return new Response(JSON.stringify([]), { headers: { 'Content-Type': 'application/json' } });
  const marks = ids.map(() => '?').join(',');
  const rows = db.prepare(`SELECT id, nombre, slug, precio_actual, precio_original, imagen, stock FROM productos WHERE id IN (${marks})`).all(...ids);
  return new Response(JSON.stringify(rows), { headers: { 'Content-Type': 'application/json' } });
};