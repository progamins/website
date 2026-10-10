# 🤖 Asistente IA — Chollo & Glam (RAG)

Microservicio de **búsqueda semántica y RAG** (Retrieval-Augmented Generation) sobre el catálogo real de la tienda Chollo & Glam. El asistente entiende lenguaje natural ("un regalo elegante para aniversario"), recupera los productos más relevantes por similitud de embeddings y genera una recomendación con citas — solo sobre productos que existen.

## Arquitectura

```
                       ┌─────────────────────────────────────────────┐
 Navegador ──POST──►  │ Astro SSR (tienda)                          │
                       │  /asistente  (UI)                           │
                       │  /api/asistente (proxy server-side)         │
                       └──────────────────┬──────────────────────────┘
                                          │ fetch + X-API-Key (solo server)
                                          ▼
                       ┌─────────────────────────────────────────────┐
                       │ FastAPI (Vercel Python serverless)          │
                       │  POST /api/search   → solo embeddings        │
                       │  POST /api/chat     → RAG + LLM + citas     │
                       │  POST /api/ingest   → embeddea catálogo      │
                       └───────┬──────────────────────┬───────────────┘
                               │                      │
                               ▼                      ▼
               ┌───────────────────────┐   ┌──────────────────────────┐
               │ Supabase PostgreSQL   │   │ OpenAI (nube)            │
               │ + pgvector            │   │  · text-embedding-3-small │
               │ · tabla documents     │   │  · gpt-4o-mini            │
               │ · índice IVF (coseno) │   │ O Ollama (local, gratis) │
               │ · RLS activada        │   │  · nomic-embed-text       │
               │ · RPC match_documents │   │  · llama3.1               │
               └───────────────────────┘   └──────────────────────────┘
```

## Stack

| Capa | Tecnología |
|---|---|
| API | Python · **FastAPI** · Pydantic (validación estricta) |
| Búsqueda vectorial | **Supabase PostgreSQL + pgvector** (índice IVF, similitud coseno, función RPC) |
| IA | **OpenAI** (embeddings + LLM) con proveedor alternativo **Ollama** local vía API compatible |
| Frontend | Astro SSR (proxy server-side — el cliente nunca ve la URL ni las claves) |
| Deploy | **Vercel** (Python serverless) |
| Ingesta | `web/scripts/ingest-ai.mjs` — lee el catálogo real de SQLite y envía lotes |

## Puesta en marcha

### 1 · Supabase

1. Crea un proyecto en [supabase.com](https://supabase.com).
2. **SQL Editor → New query** → pega y ejecuta `supabase/schema.sql` completo.
3. Anota **Project URL** y **service_role key** (Settings → API). La tabla queda con **RLS activada y sin políticas públicas**: solo la service_role key accede.

### 2 · Vercel

1. En Vercel → **Add New → Project** → importa el repo `website`.
2. **Root Directory: `ai-search`** (clave — es un monorepo).
3. Framework Preset: Other. Vercel detecta `api/index.py` como Python.
4. **Environment Variables** (Production + Preview):

| Variable | Valor |
|---|---|
| `SUPABASE_URL` | `https://TU-PROYECTO.supabase.co` |
| `SUPABASE_SERVICE_KEY` | service_role key (secreta) |
| `API_KEY` | clave larga aleatoria — `python -c "import secrets; print(secrets.token_urlsafe(32))"` |
| `AI_PROVIDER` | `openai` (o `ollama`) |
| `OPENAI_API_KEY` | tu clave de OpenAI (si provider=openai) |
| `EMBEDDING_MODEL` | `text-embedding-3-small` |
| `EMBEDDING_DIM` | `1536` (OpenAI) · `768` (Ollama nomic-embed-text) |
| `CHAT_MODEL` | `gpt-4o-mini` |
| `ALLOWED_ORIGINS` | dominio de la tienda (ej. `https://tu-tienda.vercel.app,http://localhost:4321`) |

5. Deploy. Verifica: `GET https://tu-proyecto.vercel.app/api/health`

### 3 · Ingesta del catálogo

Desde `web/` (con la BD `data/chollo.db` generada por el seed):

```bash
AI_SEARCH_URL="https://tu-proyecto.vercel.app" \
AI_SEARCH_KEY="la-misma-API_KEY" \
node scripts/ingest-ai.mjs
```

Idempotente (upsert por `external_id`). Con `REINGEST_ALL=1` reemplaza todo el origen `catalogo`.

### 4 · Conectar la tienda (Astro)

En el servidor del sitio Astro (`.env` o Vercel del proyecto `web`):

```
AI_SEARCH_URL=https://tu-proyecto.vercel.app
AI_SEARCH_KEY=la-misma-API_KEY
```

La página queda en `/asistente`.

## Uso local (sin Vercel)

```bash
cd ai-search
python -m venv venv && venv\Scripts\activate      # Windows
pip install -r requirements.txt
copy .env.example .env                             # rellena tus claves
uvicorn api.index:app --reload --port 8000
```

## API

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| GET | `/api/health` | — | Estado de proveedores (sin secretos) |
| POST | `/api/search` | rate limit | Búsqueda semántica: top-k con score |
| POST | `/api/chat` | rate limit | RAG: respuesta con LLM + citas `[n]` + sources |
| POST | `/api/ingest` | **X-API-Key** | Embeddea y guarda lotes (≤100 docs) |
| DELETE | `/api/documents?source=` | **X-API-Key** | Borra documentos de un origen |

```bash
# Búsqueda semántica
curl -X POST https://tu-proyecto.vercel.app/api/search \
  -H 'Content-Type: application/json' \
  -d '{"query":"anillo de plata elegante","limit":5}'

# RAG
curl -X POST https://tu-proyecto.vercel.app/api/chat \
  -H 'Content-Type: application/json' \
  -d '{"query":"regalo para aniversario hasta S/ 200"}'
```

## Seguridad (mismo estándar que el resto del ecosistema)

- **Row-Level Security** activada en la tabla (solo service_role accede)
- **API key** para endpoints administrativos (comparación en tiempo constante `hmac.compare_digest`)
- **Rate limiting** por IP con ventana deslizante (search/chat 20/min, ingest 10, delete 5)
- **Validación estricta** con Pydantic: longitudes, rangos, tipos en TODA entrada
- **CORS con lista explícita** de orígenes — nunca `*`
- **Security headers** en todas las respuestas (CSP `default-src 'none'`, nosniff, DENY, no-store)
- **Errores genéricos** al cliente — sin stack traces ni detalles internos
- **Secretos solo en variables de entorno** (`.env.example` documentado, `.gitignore`)
- Prompt con **guardrails**: el modelo solo responde con el contexto del catálogo y cita fuentes
- La tienda consume el servicio vía **proxy server-side**: la URL y claves nunca llegan al navegador

## Qué demuestra este proyecto

- **IA aplicada de extremo a extremo**: embeddings, búsqueda semántica, RAG con citas, prompt engineering con guardrails
- **Python + FastAPI**: API REST con validación Pydantic, middleware, manejo de errores
- **PostgreSQL avanzado**: pgvector, índices IVF, funciones RPC, Row-Level Security
- **Doble proveedor de IA** con la misma base de código: OpenAI (nube) ↔ Ollama (local)
- **Cloud/Serverless**: despliegue en Vercel, configuración por entorno, ingesta idempotente
- **Integración con producto real**: no es un demo aislado — consume el catálogo en producción de la tienda
