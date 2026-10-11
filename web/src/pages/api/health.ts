import type { APIRoute } from 'astro';

/**
 * /api/health — diagnóstico de runtime SIN importar la capa de BD.
 * Si este endpoint responde, el runtime base funciona; si las páginas
 * siguen fallando, el problema está aislado en el módulo de BD.
 */
export const GET: APIRoute = () => {
  return new Response(
    JSON.stringify({
      ok: true,
      runtime: 'node',
      vercel: !!(process.env.VERCEL || process.env.VERCEL_REGION),
      cwd: process.cwd(),
      node: process.version,
      ts: new Date().toISOString(),
    }),
    { status: 200, headers: { 'Content-Type': 'application/json' } }
  );
};
