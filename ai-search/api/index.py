"""
Chollo & Glam — Asistente IA (RAG) · API FastAPI
================================================
Microservicio de búsqueda semántica y RAG sobre el catálogo de la tienda.

Arquitectura:
    Astro (tienda) → proxy server-side → esta API → Supabase (pgvector) → OpenAI/Ollama

Endpoints:
    GET    /                → info del servicio
    GET    /api/health      → estado (proveedores, BD)
    POST   /api/search      → búsqueda semántica (embeddings + cosine similarity)
    POST   /api/chat        → RAG: recupera contexto y responde con LLM + citas
    POST   /api/ingest      → (X-API-Key) inserta/actualiza documentos con embeddings
    DELETE /api/documents    → (X-API-Key) borra documentos por origen

Diseñoado para Vercel Python (serverless) y para desarrollo local con uvicorn.
"""

import os
import time
import hmac
from typing import Any, Optional

from fastapi import FastAPI, Request, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

# ----------------------------------------------------------------------
# Configuración (todo por variables de entorno — nunca secretos en código)
# ----------------------------------------------------------------------
SUPABASE_URL: str = os.environ.get("SUPABASE_URL", "").strip()
SUPABASE_SERVICE_KEY: str = os.environ.get("SUPABASE_SERVICE_KEY", "").strip()

API_KEY: str = os.environ.get("API_KEY", "").strip()

# Proveedor de IA: "openai" (nube) u "ollama" (local). Doble proveedor demostrable.
AI_PROVIDER: str = os.environ.get("AI_PROVIDER", "openai").strip().lower()
OPENAI_API_KEY: str = os.environ.get("OPENAI_API_KEY", "").strip()
# Para Ollama: base URL del servidor local (OpenAI-compatible)
AI_BASE_URL: Optional[str] = os.environ.get("AI_BASE_URL", "").strip() or None

EMBEDDING_MODEL: str = os.environ.get("EMBEDDING_MODEL", "text-embedding-3-small").strip()
EMBEDDING_DIM: int = int(os.environ.get("EMBEDDING_DIM", "1536"))
CHAT_MODEL: str = os.environ.get("CHAT_MODEL", "gpt-4o-mini").strip()

# CORS: solo orígenes explícitos (la tienda). "*" está prohibido por diseño.
ALLOWED_ORIGINS: list = [
    o.strip()
    for o in os.environ.get("ALLOWED_ORIGINS", "").split(",")
    if o.strip()
]

# Rate limiting (ventana deslizante en memoria — suficiente para serverless 1 instancia)
RATE_LIMIT_MAX: int = int(os.environ.get("RATE_LIMIT_MAX", "20"))   # peticiones
RATE_LIMIT_WINDOW_S: int = int(os.environ.get("RATE_LIMIT_WINDOW_S", "60"))

SERVICE_NAME = "chollo-ai-search"
SERVICE_VERSION = "1.0.0"

# ----------------------------------------------------------------------
# Clientes perezosos (lazy): se crean una sola vez por instancia serverless
# ----------------------------------------------------------------------
_supabase_client: Any = None
_ai_client: Any = None


def get_supabase():
    """Cliente Supabase con rol de servicio (bypass RLS — solo server-side)."""
    global _supabase_client
    if _supabase_client is None:
        if not SUPABASE_URL or not SUPABASE_SERVICE_KEY:
            raise RuntimeError("Supabase no configurado (SUPABASE_URL / SUPABASE_SERVICE_KEY)")
        from supabase import create_client  # import perezoso: arranque más rápido

        _supabase_client = create_client(SUPABASE_URL, SUPABASE_SERVICE_KEY)
    return _supabase_client


def get_ai():
    """Cliente OpenAI-compatible (OpenAI en nube, u Ollama local vía AI_BASE_URL)."""
    global _ai_client
    if _ai_client is None:
        if AI_PROVIDER == "openai":
            if not OPENAI_API_KEY:
                raise RuntimeError("OPENAI_API_KEY no configurada")
            from openai import OpenAI

            _ai_client = OpenAI(api_key=OPENAI_API_KEY, base_url=AI_BASE_URL)
        elif AI_PROVIDER == "ollama":
            from openai import OpenAI  # Ollama expone API compatible con OpenAI

            _ai_client = OpenAI(api_key="ollama", base_url=AI_BASE_URL or "http://localhost:11434/v1")
        else:
            raise RuntimeError("AI_PROVIDER debe ser 'openai' u 'ollama'")
    return _ai_client


# ----------------------------------------------------------------------
# Embeddings y recuperación
# ----------------------------------------------------------------------
def embed_texts(texts: list) -> list:
    """Convierte textos en vectores. Valida dimensión contra EMBEDDING_DIM."""
    client = get_ai()
    response = client.embeddings.create(model=EMBEDDING_MODEL, input=texts)
    vectors = [item.embedding for item in response.data]
    for v in vectors:
        if len(v) != EMBEDDING_DIM:
            raise RuntimeError(
                "Dimension de embedding incorrecta: modelo produce {}d, EMBEDDING_DIM={}. "
                "Ajusta EMBEDDING_DIM y el esquema de Supabase al mismo valor.".format(len(v), EMBEDDING_DIM)
            )
    return vectors


def retrieve_context(query: str, k: int = 5) -> list:
    """Búsqueda semántica: embeddea la consulta y recupera los k vecinos más cercanos
    vía la función RPC match_documents de Supabase (pgvector, similitud coseno)."""
    vector = embed_texts([query])[0]
    result = get_supabase().rpc(
        "match_documents", {"query_embedding": vector, "match_count": k}
    ).execute()
    return list(result.data or [])


# ----------------------------------------------------------------------
# Prompt engineering — sistema del asistente (RAG)
# ----------------------------------------------------------------------
SYSTEM_PROMPT = """Eres "Glam", el asistente de compras de Chollo & Glam, una tienda de \
joyería y artesanía peruana de diseño exclusivo. Ayudas a los clientes a encontrar productos.

REGLAS ESTRICTAS:
1. Responde SOLO usando los productos listados en el CONTEXTO. Nunca inventes productos, precios ni tallas.
2. Si el CONTEXTO no contiene la respuesta, dilo con amabilidad y ofrece la alternativa más parecida que sí exista en el CONTEXTO.
3. Cita cada producto mencionado con su número entre corchetes: [1], [2], según su orden en el CONTEXTO.
4. Sé breve: máximo 3 frases seguidas de la lista de productos recomendados con su cita.
5. Responde en el mismo idioma del usuario (español por defecto).
6. No des información de envíos, devoluciones ni promociones que no esté en el CONTEXTO.

FORMATO DE RESPUESTA:
- Una frase corta entendiendo lo que busca el cliente.
- Los productos recomendados con [n] y una razón breve (máximo 8 palabras) por cada uno."""


def build_chat_messages(query: str, context_docs: list) -> list:
    """Construye los mensajes del LLM: system prompt + contexto numerado + pregunta."""
    if context_docs:
        lines = []
        for i, doc in enumerate(context_docs, start=1):
            meta = doc.get("metadata") or {}
            lines.append(
                "[{}] {} — {}. Precio: {}. Categoría: {}. URL: {}".format(
                    i,
                    meta.get("titulo", "Producto"),
                    (doc.get("content") or "")[:300],
                    meta.get("precio", "consultar"),
                    meta.get("categoria", "-"),
                    meta.get("url", "-"),
                )
            )
        context_block = "CATÁLOGO (CONTEXTO):\n" + "\n".join(lines)
    else:
        context_block = "CATÁLOGO (CONTEXTO): (sin resultados relevantes)"
    return [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "user", "content": context_block + "\n\nPREGUNTA DEL CLIENTE: " + query},
    ]


# ----------------------------------------------------------------------
# Modelos de entrada (validación Pydantic)
# ----------------------------------------------------------------------
class SearchRequest(BaseModel):
    query: str = Field(min_length=3, max_length=500)
    limit: int = Field(default=5, ge=1, le=10)


class ChatRequest(BaseModel):
    query: str = Field(min_length=3, max_length=500)
    limit: int = Field(default=5, ge=1, le=10)


class IngestDocument(BaseModel):
    external_id: str = Field(min_length=1, max_length=120)
    content: str = Field(min_length=1, max_length=8000)
    metadata: dict = Field(default_factory=dict)
    source: str = Field(default="catalogo", max_length=50)


class IngestRequest(BaseModel):
    documents: list = Field(min_length=1, max_length=100)


# ----------------------------------------------------------------------
# Utilidades de seguridad
# ----------------------------------------------------------------------
_rate_store: dict = {}


def rate_limit_key(request: Request, scope: str) -> str:
    """IP del cliente (respetando proxies de Vercel) + scope del endpoint."""
    fwd = request.headers.get("x-forwarded-for", "")
    ip = (fwd.split(",")[0] if fwd else (request.client.host if request.client else "desconocida")).strip()
    return "{}:{}".format(scope, ip)


def check_rate_limit(request: Request, scope: str, limit: int) -> None:
    """Ventana deslizante: rechaza con 429 si se supera el límite. Evita abuso/fuerza bruta."""
    key = rate_limit_key(request, scope)
    now = time.time()
    hits = [t for t in _rate_store.get(key, []) if now - t < RATE_LIMIT_WINDOW_S]
    if len(hits) >= limit:
        raise HTTPException(status_code=429, detail="Demasiadas solicitudes. Espera un momento.")
    hits.append(now)
    _rate_store[key] = hits
    # Evitar crecimiento ilimitado de memoria en instancias de larga vida
    if len(_rate_store) > 10_000:
        _rate_store.clear()


def require_api_key(request: Request) -> None:
    """Autenticación por API key para endpoints administrativos (comparación en tiempo constante)."""
    if not API_KEY:
        raise HTTPException(status_code=503, detail="API_KEY no configurada en el servidor")
    provided = request.headers.get("x-api-key", "")
    if not hmac.compare_digest(provided, API_KEY):
        raise HTTPException(status_code=401, detail="API key inválida")


def provider_status() -> dict:
    """Estado de los proveedores para /api/health (sin exponer secretos)."""
    ai_ok = (AI_PROVIDER == "openai" and bool(OPENAI_API_KEY)) or (AI_PROVIDER == "ollama" and bool(AI_BASE_URL))
    return {
        "provider": AI_PROVIDER,
        "embedding_model": EMBEDDING_MODEL,
        "embedding_dim": EMBEDDING_DIM,
        "chat_model": CHAT_MODEL if AI_PROVIDER == "openai" else (CHAT_MODEL or "llama3.1"),
        "ai_configured": ai_ok,
        "supabase_configured": bool(SUPABASE_URL and SUPABASE_SERVICE_KEY),
    }


# ----------------------------------------------------------------------
# App FastAPI
# ----------------------------------------------------------------------
app = FastAPI(
    title="Chollo & Glam — Asistente IA",
    description="Búsqueda semántica y RAG sobre el catálogo. FastAPI + Supabase pgvector + OpenAI/Ollama.",
    version=SERVICE_VERSION,
    docs_url=None,   # docs deshabilitadas en producción (no exponer esquema)
    redoc_url=None,
    openapi_url=None,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=ALLOWED_ORIGINS,          # lista explícita, nunca "*"
    allow_credentials=False,
    allow_methods=["GET", "POST", "DELETE"],
    allow_headers=["Content-Type", "X-API-Key"],
)


@app.middleware("http")
async def security_headers(request: Request, call_next):
    """Cabeceras de seguridad en TODAS las respuestas (misma política que el resto del ecosistema)."""
    response = await call_next(request)
    response.headers["X-Content-Type-Options"] = "nosniff"
    response.headers["X-Frame-Options"] = "DENY"
    response.headers["Referrer-Policy"] = "no-referrer"
    response.headers["Content-Security-Policy"] = "default-src 'none'; frame-ancestors 'none'"
    response.headers["Cache-Control"] = "no-store"
    return response


@app.get("/")
def root():
    return {
        "service": SERVICE_NAME,
        "version": SERVICE_VERSION,
        "endpoints": ["/api/health", "/api/search", "/api/chat", "/api/ingest"],
        "docs": "ver README.md del repositorio",
    }


@app.get("/api/health")
def health():
    return {"ok": True, "service": SERVICE_NAME, **provider_status()}


@app.post("/api/search")
def search(payload: SearchRequest, request: Request):
    """Búsqueda semántica pura: devuelve los productos más parecidos con su score."""
    check_rate_limit(request, "search", RATE_LIMIT_MAX)
    query = payload.query.strip()
    try:
        docs = retrieve_context(query, payload.limit)
    except RuntimeError as exc:
        raise HTTPException(status_code=503, detail=str(exc))
    except Exception:
        raise HTTPException(status_code=502, detail="No se pudo completar la búsqueda en este momento")
    results = [
        {
            "external_id": d.get("external_id"),
            "score": round(float(d.get("similarity", 0.0)), 4),
            "metadata": d.get("metadata") or {},
            "excerpt": (d.get("content") or "")[:200],
        }
        for d in docs
    ]
    return {"ok": True, "query": query, "results": results}


@app.post("/api/chat")
def chat(payload: ChatRequest, request: Request):
    """RAG completo: recuperar → construir prompt con contexto → responder con LLM → citas."""
    check_rate_limit(request, "chat", RATE_LIMIT_MAX)
    query = payload.query.strip()
    try:
        context_docs = retrieve_context(query, payload.limit)
        messages = build_chat_messages(query, context_docs)
        completion = get_ai().chat.completions.create(
            model=CHAT_MODEL,
            messages=messages,
            temperature=0.2,     # bajo: respuestas de catálogo, no creatividad
            max_tokens=350,
        )
        answer = (completion.choices[0].message.content or "").strip()
    except RuntimeError as exc:
        raise HTTPException(status_code=503, detail=str(exc))
    except Exception:
        raise HTTPException(status_code=502, detail="El asistente no está disponible en este momento")
    sources = [
        {
            "n": i,
            "external_id": d.get("external_id"),
            "score": round(float(d.get("similarity", 0.0)), 4),
            "metadata": d.get("metadata") or {},
        }
        for i, d in enumerate(context_docs, start=1)
    ]
    return {"ok": True, "query": query, "answer": answer, "sources": sources}


@app.post("/api/ingest")
def ingest(payload: IngestRequest, request: Request):
    """Inserta/actualiza documentos con sus embeddings (idempotente por external_id).
    Protegido con X-API-Key. Máximo 100 documentos por lote."""
    require_api_key(request)
    check_rate_limit(request, "ingest", 10)
    rows = []
    for doc in payload.documents:
        rows.append(
            {
                "external_id": doc.external_id,
                "content": doc.content.strip(),
                "metadata": doc.metadata or {},
                "source": doc.source,
            }
        )
    texts = [r["content"] for r in rows]
    try:
        vectors = embed_texts(texts)
    except RuntimeError as exc:
        raise HTTPException(status_code=503, detail=str(exc))
    except Exception:
        raise HTTPException(status_code=502, detail="No se pudieron generar los embeddings")
    for r, v in zip(rows, vectors):
        r["embedding"] = v
    try:
        get_supabase().table("documents").upsert(rows, on_conflict="external_id").execute()
    except Exception:
        raise HTTPException(status_code=502, detail="No se pudo guardar en la base de datos")
    return {"ok": True, "ingested": len(rows)}


@app.delete("/api/documents")
def delete_documents(request: Request, source: str = "catalogo"):
    """Borra los documentos de un origen (p. ej. re-indexar todo). Protegido con X-API-Key."""
    require_api_key(request)
    check_rate_limit(request, "delete", 5)
    try:
        get_supabase().table("documents").delete().eq("source", source).execute()
    except Exception:
        raise HTTPException(status_code=502, detail="No se pudo borrar de la base de datos")
    return {"ok": True, "deleted_source": source}
