-- ============================================================
-- Chollo & Glam — Asistente IA · Esquema pgvector (Supabase)
-- ============================================================
-- Ejecuta este script completo en: Supabase Dashboard → SQL Editor → New query
--
-- IMPORTANTE — dimensiones de embedding:
--   OpenAI  text-embedding-3-small → 1536 (por defecto en este esquema)
--   Ollama nomic-embed-text        → 768  (reemplaza 1536 por 768 en TODO el script)
-- El valor debe coincidir con la variable EMBEDDING_DIM del servicio.

-- 1) Extensión de vectores
create extension if not exists vector;

-- 2) Tabla de documentos (el catálogo embeddido)
create table if not exists documents (
  id           bigserial primary key,
  external_id  text unique not null,          -- ej. "producto:12"
  content      text not null,                 -- texto que se embeddea
  metadata     jsonb not null default '{}'::jsonb,  -- titulo, precio, url, imagen...
  source       text not null default 'catalogo',
  embedding    vector(1536) not null,
  created_at   timestamptz not null default now()
);

-- 3) Índice de similitud (IVF Flat, distancia coseno)
create index if not exists documents_embedding_idx
  on documents using ivfflat (embedding vector_cosine_ops) with (lists = 100);

-- 4) Row-Level Security: ACTIVADA y sin políticas públicas.
--    Solo la service_role key (server-side) puede leer/escribir.
--    La anon key queda fuera por diseño.
alter table documents enable row level security;

-- 5) Función de búsqueda por similitud (la llama el servicio vía RPC)
create or replace function match_documents(
  query_embedding vector(1536),
  match_count int default 5
)
returns table (
  id bigint,
  external_id text,
  content text,
  metadata jsonb,
  similarity float
)
language sql stable
as $$
  select
    d.id,
    d.external_id,
    d.content,
    d.metadata,
    1 - (d.embedding <=> query_embedding) as similarity
  from documents d
  order by d.embedding <=> query_embedding
  limit match_count;
$$;

-- ============================================================
-- Verificación rápida (opcional):
-- select count(*) from documents;
-- El servicio la llena con POST /api/ingest (ver scripts/ingest-local.mjs).
-- ============================================================
