# Chollo & Glam · Web (Astro + Tailwind + SQLite)

Reestructuración moderna de la tienda **Chollo & Glam** (joyería y artesanía peruana). Reemplaza el antiguo proyecto PHP procedural + MySQL por una aplicación SSR en **Astro 7** con **Tailwind CSS v4** y base de datos **SQLite** (better-sqlite3), con panel de administración renovado y **separado del frontend** (login y diseño propios).

Diseño del frontend: identidad editorial **marfil / vino / oro champán** (`#F5F0E8`, `#4A2029`, `#C5A46D`) con tipografías **Cormorant Garamond** (títulos) + **DM Sans** (interfaz).

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
- `src/lib/auth.ts` — sesiones por cookie (httpOnly) + bcrypt + recuperación de contraseña.
- `src/lib/pagos.ts` — servicio de pagos (registrar/aprobar/fallar/reembolsar + recálculo del estado del pedido y notificación al cliente).
- `src/lib/notificaciones.ts` — centro de notificaciones del cliente (scoping por usuario + deduplicación).
- `src/lib/email.ts` — integración de correo desacoplada (Resend; degradación honesta sin configurar).
- `src/lib/migrations.ts` — migraciones versionadas e idempotentes.
- `src/lib/schema.sql` — esquema mejorado (índices, CHECK, triggers, moderación, roles y trazabilidad de pagos).
- `src/components/` — Layout, Navbar (con menú de cuenta y campana), Footer, ProductCard, FlashTimer y componentes de admin.
- `data/chollo.db` — base de datos SQLite (se genera con el seed).
- `public/uploads/` — imágenes y vídeos copiados del proyecto anterior.

## Notas de seguridad
Astro 7 activa protección CSRF en formularios POST; en pruebas con `curl` añade la cabecera `Origin: http://localhost:4321` o usa un navegador.

## Cuentas de cliente
- **Registro** con validación en servidor y hash bcrypt (coste 12).
- **Sesiones** en BD (tabla `sesiones`) con cookie `cg_session` HttpOnly + SameSite=Lax + Secure (producción). "Recordarme" extiende la sesión a 30 días.
- **Recuperación de contraseña**: token de un solo uso (hash SHA-256 en BD, caduca en 1 h). Al completarla se cierran todas las sesiones del usuario y se emite un aviso de seguridad.
- **Perfil** (`/cuenta`): edición de datos personales y cambio de contraseña (requiere contraseña actual; conserva la sesión en uso y cierra el resto).

## Centro de notificaciones (cliente)
- Tabla `notificaciones_usuario` (migración v1): una notificación por usuario y evento, con deduplicación por `dedup_key`.
- Eventos reales que generan notificaciones: pedido confirmado, cambio de estado del pedido, pago confirmado/fallido/reembolsado, avisos de seguridad y bienvenida.
- Interfaz: campana en la cabecera con contador, panel desplegable accesible (Escape/clic exterior), marcar una o todas como leídas, página completa en `/notificaciones`.
- APIs con scoping estricto por usuario: `GET /api/notificaciones/mias`, `POST /api/notificaciones/leer`.

## Migraciones de base de datos
`src/lib/migrations.ts` contiene migraciones **versionadas e idempotentes** que se aplican automáticamente al abrir la BD (seguro en serverless: la BD se copia a `/tmp` en Vercel). Nunca destruyen datos.

## Pruebas funcionales (QA)
Con el servidor corriendo (`npm run preview`):
```bash
node scripts/crear-qa-admin.mjs   # crea/restablece el admin de QA (solo desarrollo)
node scripts/qa-funcional.mjs     # auth, notificaciones, comercio, interfaz
node dist/server/entry.mjs        # servidor en foreground (para capturar el token de reset en el log)
node scripts/qa-funcional-2.mjs <ruta-del-log>   # recuperación completa, admin→notificaciones, rate limit, logout
node scripts/limpiar-qa.mjs       # elimina todos los datos de prueba y restaura el stock
```

## Configuración pendiente (correo electrónico)
El flujo de recuperación de contraseña está **completo en backend**, pero la tienda **no tiene proveedor de correo configurado**, por lo que no se envían correos todavía:
1. Crea una cuenta en [Resend](https://resend.com) (u otro proveedor) y obtén tu API key.
2. En Vercel → Settings → Environment Variables, añade `RESEND_API_KEY` y `EMAIL_FROM` (remitente verificado).
3. Opcionalmente `PUBLIC_SITE_URL` si el dominio difiere del de despliegue.

Sin esa configuración, la página de recuperación informa honestamente de que la entrega no está activa y el enlace se registra en el log del servidor (útil en desarrollo). Nunca se afirma un envío que no ocurrió.

**Verificación de correo y push del navegador**: pendientes de proveedor de correo / service worker. No se muestran controles falsos.