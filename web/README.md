# Chollo & Glam · Web (Astro + Tailwind + SQLite)

Reestructuración moderna de la tienda **Chollo & Glam** (joyería y artesanía peruana). Reemplaza el antiguo proyecto PHP procedural + MySQL por una aplicación SSR en **Astro 7** con **Tailwind CSS v4** y base de datos **SQLite** (better-sqlite3), con panel de administración renovado y **separado del frontend** (login y diseño propios).

Diseño del frontend: paleta elegante estilo boutique peruana (crema cálido, dorado, terracota y verde andino) con tipografías **Cormorant Garamond** (títulos) + **Jost** (cuerpo).

## Requisitos
- Node.js >= 22

## Instalación y arranque
```bash
npm install
node scripts/seed.mjs   # crea/reinicia data/chollo.db con datos de ejemplo
npm run dev             # servidor de desarrollo en http://localhost:4321
```
Para producción:
```bash
npm run build   # genera dist/
npm run preview
```

## Cuentas de prueba (creadas por el seed)
| Rol       | Email                      | Contraseña     |
|-----------|----------------------------|----------------|
| Admin     | admin@cholloyglam.com      | Admin123!      |
| Moderador | moderador@cholloyglam.com  | Moderador123!  |
| Cliente   | maria@email.com            | Cliente123!    |

## Panel de administración
Accede en **`/admin`** — tiene **login y sesión propios** (`/admin/login`, cookie `cg_admin_session`), completamente separados del login de clientes y con diseño oscuro independiente. Roles: admin y moderador.
- **Dashboard**: estadísticas, gráfico de ventas por mes (Chart.js), productos sin stock, notificaciones e ingresos netos.
- **Productos**: CRUD completo con subida de imágenes, activar/desactivar, destacado, stock.
- **Categorías / Colecciones**: gestión con imágenes.
- **Ofertas Flash**: creación con selección múltiple de productos y cuenta regresiva.
- **Pedidos**: cambio de estado (pendiente → entregado/cancelado) y detalle con envío.
- **Pagos**: gestión profesional de transacciones — registrar, aprobar, fallar y reembolsar pagos; filtros por estado/método, estadísticas (cobrado, pendiente, fallido, reembolsado e ingresos netos). Los pedidos muestran su estado de pago (pendiente / pagado / parcial / fallido / reembolsado).
- **Usuarios y roles**: solo admin (admin / moderador / cliente), activar/desactivar.
- **Moderación**: aprobar/rechazar valoraciones pendientes (recalcula la media del producto).
- **Mensajes y Notificaciones**: bandeja de contacto y avisos no leídos.
- **Configuración del sitio**: cambiar el **logo** (subida de imagen) y gestionar el **carrusel de banners** de la portada (añadir/quitar banners de vídeo o imagen, con enlace opcional y guía de tamaños).

## Estructura
- `src/pages/` — rutas públicas y de admin + endpoints de API (`/api/*`).
- `src/lib/db.ts` — capa de datos (consultas tipadas) sobre SQLite.
- `src/lib/pagos.ts` — servicio de pagos (registrar/aprobar/fallar/reembolsar + recálculo del estado del pedido).
- `src/lib/auth.ts` — sesiones por cookie (httpOnly) + bcrypt.
- `src/lib/schema.sql` — esquema mejorado (índices, CHECK, triggers, moderación, roles y trazabilidad de pagos).
- `src/components/` — Layout, Navbar, Footer, ProductCard, FlashTimer y componentes de admin.
- `data/chollo.db` — base de datos SQLite (se genera con el seed).
- `public/uploads/` — imágenes y vídeos copiados del proyecto anterior.

## Notas de seguridad
Astro 7 activa protección CSRF en formularios POST; en pruebas con `curl` añade la cabecera `Origin: http://localhost:4321` o usa un navegador.