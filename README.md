# Chollo & Glam

E-commerce de joyería y artesanía peruana de diseño exclusivo. Piezas hechas a mano que fusionan tradición andina y elegancia contemporánea.

Este repositorio contiene **dos versiones** del proyecto:

| Versión | Carpeta | Tecnología | Estado |
|---------|---------|------------|--------|
| **Legacy** | raíz (`/`) | PHP procedural + MySQLi + Bootstrap | Funcional (sitio antiguo) |
| **Nueva** | [`web/`](web/README.md) | **Astro 7 (SSR) + Tailwind CSS v4 + SQLite** | **Activa / recomendada** |

> **Recomendado:** usar la nueva versión en `web/`. Contiene tienda, panel de administración con pagos, gestión de banners y logo, y un diseño boutique renovado.

---

## ✨ Características de la nueva versión (`web/`)

### Tienda (frontend)
- Carrusel de **banners** a pantalla completa (vídeo/imagen) gestionable desde el admin.
- **Ofertas flash** con cuenta regresiva, productos destacados, categorías, colecciones, testimonios e Instagram.
- Búsqueda, categorías con paginación, ficha de producto con valoraciones y **datos estructurados (SEO/JSON-LD)**.
- **Carrito** (localStorage) con **animación al añadir productos** (toast + contador), **checkout profesional** con datos de envío y métodos de pago (Tarjeta / PayPal / Transferencia), y página de confirmación.
- Cuenta de usuario: favoritos, historial de pedidos con estado y envío.
- Diseño boutique: paleta crema / dorado / terracota / verde andino, tipografías **Cormorant Garamond + Jost**, 100 % responsive.
- Moneda en **Soles (S/ PEN)**.

### Panel de administración (`/admin`) — login y diseño propios
- **Dashboard** con estadísticas e ingresos netos, gráfico de ventas (Chart.js).
- **Productos**: CRUD con subida de imágenes, stock, destacado, activar/desactivar.
- **Categorías / Colecciones / Ofertas Flash** con selección múltiple.
- **Pedidos**: estados de envío y transacciones de pago.
- **Pagos**: registrar, aprobar, fallar y reembolsar transacciones (estado automático del pedido).
- **Usuarios y roles** (admin / moderador / cliente) y **moderación** de valoraciones.
- **Configuración del sitio**: cambiar **logo** y gestionar el **carrusel de banners** con guía de tamaños.

### Base de datos (SQLite)
- Esquema normalizado con índices, `CHECK`, triggers y trazabilidad de pagos (`pagos`, `pago_estado`).
- `configuracion` (JSON) para logo y banners, `notificaciones`, `mensajes`, `sesiones`, etc.

---

## 🛠️ Tecnologías

- **Astro 7** (SSR, adapter Node) + **Tailwind CSS v4**
- **better-sqlite3** (SQLite) · **bcryptjs** · sesiones por cookie httpOnly
- Chart.js, JSON-LD (Schema.org), Open Graph

## 🚀 Ejecutar en local

```bash
cd web
npm install
node scripts/seed.mjs   # crea data/chollo.db con datos de ejemplo
npm run dev             # http://localhost:4321
```

Producción: `npm run build` → `npm run preview`.

## 🔑 Cuentas de prueba (generadas por el seed)

| Rol       | Email                      | Contraseña    |
|-----------|----------------------------|---------------|
| Admin     | admin@cholloyglam.com      | Admin123!     |
| Moderador | moderador@cholloyglam.com  | Moderador123! |
| Cliente   | maria@email.com            | Cliente123!   |

- **Tienda** → `http://localhost:4321/`
- **Admin** → `http://localhost:4321/admin` (login propio)

## 📁 Estructura (`web/`)

```
web/
├─ src/pages/          # rutas públicas + admin + endpoints /api/*
├─ src/components/     # Layout, Navbar, Footer, BannerCarousel, CartToast, ProductCard, admin…
├─ src/lib/            # db.ts (datos), auth.ts (sesiones), pagos.ts, schema.sql
├─ scripts/seed.mjs    # crea y rellena la base de datos
├─ data/               # chollo.db (SQLite, se genera con el seed)
└─ public/uploads/     # imágenes y vídeos
```

Detalles en [`web/README.md`](web/README.md).

---

## 📄 Versión legacy (PHP)

La carpeta raíz conserva el sitio original en **PHP procedural + MySQLi** (`index.php`, `admin/`, `includes/`, `setup_database.sql`). Se mantiene a modo de referencia histórica; el desarrollo activo continúa en `web/`.

---

© Chollo & Glam · Hecho con ♥ en Perú.