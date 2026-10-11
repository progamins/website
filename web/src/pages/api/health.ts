import type { APIRoute } from 'astro';
import fs from 'node:fs';
import path from 'node:path';

/**
 * /api/health — diagnóstico de runtime sin depender de logs:
 * reporta el estado del entorno y de la capa de BD.
 */
export const GET: APIRoute = async () => {
  const cwd = process.cwd();
  const dbFile = path.join(cwd, 'data', 'chollo.db');

  let dbStatus: string;
  try {
    const { db } = await import('../lib/db');
    const r = db.prepare('SELECT COUNT(*) n FROM productos').get() as { n: number };
    dbStatus = `ok (${r.n} productos)`;
  } catch (e) {
    dbStatus = 'ERROR: ' + (e instanceof Error ? e.message : String(e));
  }

  return new Response(
    JSON.stringify({
      ok: true,
      runtime: 'node',
      vercel: !!(process.env.VERCEL || process.env.VERCEL_REGION),
      node: process.version,
      cwd,
      dbFileExiste: fs.existsSync(dbFile),
      tmpExiste: fs.existsSync('/tmp'),
      dbStatus,
      ts: new Date().toISOString(),
    }),
    { status: 200, headers: { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' } }
  );
};
