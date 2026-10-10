import type { APIRoute } from 'astro';

/**
 * Proxy server-side hacia el microservicio RAG (FastAPI en Vercel).
 * La URL y la API key viven SOLO en el servidor (env), así el cliente
 * no conoce la ubicación del servicio ni las credenciales, y no hay CORS.
 *
 * Variables (en .env del servidor / Vercel del sitio Astro):
 *   AI_SEARCH_URL  — https://chollo-ai-search.vercel.app
 *   AI_SEARCH_KEY  — la misma API_KEY del microservicio
 */
const AI_SEARCH_URL = (import.meta.env.AI_SEARCH_URL ?? '').toString().replace(/\/+$/, '');
const AI_SEARCH_KEY = (import.meta.env.AI_SEARCH_KEY ?? '').toString();

// Rate limiting simple por IP (el servicio también aplica el suyo)
const hits = new Map<string, number[]>();
const MAX = 10;      // consultas
const VENTANA = 60_000; // por minuto

export const POST: APIRoute = async ({ request, clientAddress }) => {
  if (!AI_SEARCH_URL || !AI_SEARCH_KEY) {
    return new Response(
      JSON.stringify({ ok: false, error: 'El asistente aún no está configurado.' }),
      { status: 503, headers: { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' } }
    );
  }

  // Rate limiting
  const ip = clientAddress ?? 'desconocida';
  const ahora = Date.now();
  const recientes = (hits.get(ip) ?? []).filter((t) => ahora - t < VENTANA);
  if (recientes.length >= MAX) {
    return new Response(
      JSON.stringify({ ok: false, error: 'Demasiadas consultas. Espera un minuto.' }),
      { status: 429, headers: { 'Content-Type': 'application/json', 'Retry-After': '60' } }
    );
  }
  recientes.push(ahora);
  hits.set(ip, recientes);

  // Validación de entrada
  let body: unknown;
  try {
    body = await request.json();
  } catch {
    return new Response(JSON.stringify({ ok: false, error: 'Solicitud inválida.' }), { status: 400, headers: { 'Content-Type': 'application/json' } });
  }
  const query = ((body as Record<string, unknown>)?.query ?? '').toString().trim();
  if (query.length < 3 || query.length > 500) {
    return new Response(
      JSON.stringify({ ok: false, error: 'La consulta debe tener entre 3 y 500 caracteres.' }),
      { status: 400, headers: { 'Content-Type': 'application/json' } }
    );
  }

  try {
    const res = await fetch(`${AI_SEARCH_URL}/api/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-API-Key': AI_SEARCH_KEY },
      body: JSON.stringify({ query, limit: 5 }),
      signal: AbortSignal.timeout(25_000),
    });
    const data = await res.json().catch(() => ({}));
    return new Response(JSON.stringify(data), {
      status: res.status,
      headers: { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' },
    });
  } catch {
    return new Response(
      JSON.stringify({ ok: false, error: 'El asistente no responde. Inténtalo en un momento.' }),
      { status: 502, headers: { 'Content-Type': 'application/json' } }
    );
  }
};
