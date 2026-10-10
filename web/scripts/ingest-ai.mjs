/**
 * Chollo & Glam — Script de ingesta para el Asistente IA (RAG)
 * ============================================================
 * Lee los productos de la BD local (data/chollo.db) y los envía por lotes
 * al endpoint /api/ingest del microservicio FastAPI, que los embeddea
 * y guarda en Supabase (pgvector).
 *
 * Uso (desde web/):
 *   AI_SEARCH_URL="https://tu-deploy.vercel.app" \
 *   AI_SEARCH_KEY="la-misma-API_KEY-del-servicio" \
 *   node scripts/ingest-ai.mjs
 *
 * Variables opcionales:
 *   AI_SEARCH_BATCH (default 50) — tamaño de lote
 *   REINGEST_ALL=1 — borra primero los documentos del origen "catalogo"
 */
import Database from 'better-sqlite3';
import path from 'node:path';

const BASE = (process.env.AI_SEARCH_URL ?? '').replace(/\/+$/, '');
const KEY = process.env.AI_SEARCH_KEY ?? '';
const BATCH = Number(process.env.AI_SEARCH_BATCH ?? 50);

if (!BASE || !KEY) {
  console.error('Faltan AI_SEARCH_URL o AI_SEARCH_KEY. Ejemplo:');
  console.error('  AI_SEARCH_URL="https://chollo-ai.vercel.app" AI_SEARCH_KEY="..." node scripts/ingest-ai.mjs');
  process.exit(1);
}

const dbPath = path.join(process.cwd(), 'data', 'chollo.db');
const db = new Database(dbPath, { readonly: true });

const productos = db.prepare(`
  SELECT p.id, p.nombre, p.slug, p.descripcion, p.precio_actual, p.precio_original,
         p.stock, p.etiqueta, p.tipo, p.imagen, c.nombre AS categoria
  FROM productos p
  LEFT JOIN categorias c ON c.id = p.categoria_id
  WHERE p.activo = 1
`).all();

if (!productos.length) {
  console.error('No hay productos activos. Corre primero: node scripts/seed.mjs');
  process.exit(1);
}

/** Texto que se embeddea: nombre + categoría + descripción (el "contenido semántico"). */
function construirDocumento(p) {
  const contenido = [
    p.nombre,
    p.categoria ? `Categoría: ${p.categoria}` : '',
    p.tipo ? `Tipo: ${p.tipo}` : '',
    p.etiqueta ? `Etiqueta: ${p.etiqueta}` : '',
    p.descripcion ?? '',
  ].filter(Boolean).join('\n');

  return {
    external_id: `producto:${p.id}`,
    content: contenido,
    metadata: {
      titulo: p.nombre,
      categoria: p.categoria ?? '-',
      precio: `S/ ${Number(p.precio_actual).toFixed(2)}`,
      precio_original: p.precio_original ? `S/ ${Number(p.precio_original).toFixed(2)}` : null,
      stock: p.stock,
      imagen: p.imagen ?? null,
      url: `/producto/${p.slug}`,
    },
    source: 'catalogo',
  };
}

async function reingestAll() {
  if (process.env.REINGEST_ALL !== '1') return;
  console.log('REINGEST_ALL=1 → borrando documentos del origen "catalogo"…');
  const res = await fetch(`${BASE}/api/documents?source=catalogo`, {
    method: 'DELETE',
    headers: { 'X-API-Key': KEY },
  });
  if (!res.ok) {
    console.error(`No se pudo limpiar (${res.status}):`, await res.text());
    process.exit(1);
  }
  console.log('Origen limpio.');
}

async function enviarLote(lote) {
  const res = await fetch(`${BASE}/api/ingest`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-API-Key': KEY },
    body: JSON.stringify({ documents: lote }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(`HTTP ${res.status}: ${data.detail ?? JSON.stringify(data)}`);
  }
  return data;
}

console.log(`Encontrados ${productos.length} productos activos.`);
console.log(`Endpoint: ${BASE} · lotes de ${BATCH}\n`);

await reingestAll();

let enviados = 0;
for (let i = 0; i < productos.length; i += BATCH) {
  const lote = productos.slice(i, i + BATCH).map(construirDocumento);
  try {
    const r = await enviarLote(lote);
    enviados += r.ingested ?? lote.length;
    console.log(`  Lote ${Math.floor(i / BATCH) + 1}: ${r.ingested ?? lote.length} documentos (total ${enviados})`);
  } catch (err) {
    console.error(`  Error en lote ${Math.floor(i / BATCH) + 1}: ${err.message}`);
    process.exit(1);
  }
}

console.log(`\n✅ Ingesta completa: ${enviados} productos embeddeados en Supabase (pgvector).`);
console.log('Prueba la búsqueda: curl -X POST ' + BASE + "/api/search -H 'Content-Type: application/json' -d '{\"query\":\"anillo de plata elegante\",\"limit\":5}'");
db.close();
