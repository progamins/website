<?php
// Incluir la conexión a la base de datos si no está ya incluida
include_once 'includes/db.php';

// Función para obtener los productos en oferta flash con parámetros preparados para mayor seguridad
function obtener_productos_oferta_flash()
{
  global $conexion;

  $sql = "SELECT p.* FROM productos p
          INNER JOIN productos_oferta_flash pof ON p.id = pof.producto_id
          INNER JOIN ofertas_flash o ON pof.oferta_id = o.id
          WHERE o.tiempo_fin > NOW()";

  return obtener_registros($sql);
}

// Función para obtener productos destacados con parámetros preparados
function obtener_productos_destacados($limite = 4)
{
  global $conexion;

  $sql = "SELECT * FROM productos WHERE destacado = 1 ORDER BY id DESC LIMIT ?";

  return obtener_registros($sql, [$limite]);
}

// Función para obtener productos por categoría
function obtener_productos_por_categoria($nombre_categoria, $limite = null)
{
  $sql = "SELECT p.* FROM productos p 
          JOIN categorias c ON p.categoria_id = c.id 
          WHERE c.nombre = ?";
  if ($limite) {
    $sql .= " LIMIT ?";
    return obtener_registros($sql, [$nombre_categoria, $limite]);
  }
  return obtener_registros($sql, [$nombre_categoria]);
}

// Función para obtener todas las categorías disponibles
function obtener_categorias()
{
  global $conexion;

  $sql = "SELECT DISTINCT tipo AS categoria FROM productos ORDER BY tipo";

  return obtener_registros($sql);
}

// Obtener productos para mostrar en la sección de ofertas flash
$productos_oferta = obtener_productos_oferta_flash();
$productos_a_mostrar = [];

if (count($productos_oferta) < 4) {
  $productos_destacados = obtener_productos_destacados(4 - count($productos_oferta));
  $productos_a_mostrar = array_merge($productos_oferta, $productos_destacados);
} else {
  $productos_a_mostrar = array_slice($productos_oferta, 0, 4);
}

// Obtener el tiempo restante para la oferta flash
$oferta_flash = obtener_registro("SELECT * FROM ofertas_flash WHERE tiempo_fin > NOW() ORDER BY tiempo_fin ASC LIMIT 1");
if ($oferta_flash) {
  $tiempo_fin = strtotime($oferta_flash['tiempo_fin']);
  $tiempo_actual = time();
  $tiempo_restante = max(0, $tiempo_fin - $tiempo_actual);
} else {
  // Si no hay ofertas activas, mostrar un tiempo predeterminado (5 horas)
  $tiempo_restante = 5 * 3600 + 32 * 60 + 18;
}

$horas = floor($tiempo_restante / 3600);
$minutos = floor(($tiempo_restante % 3600) / 60);
$segundos = $tiempo_restante % 60;

// Obtener colecciones para mostrar
$colecciones = obtener_registros("SELECT * FROM colecciones ORDER BY id DESC LIMIT 3");

// Obtener instagram feed
$instagram_posts = obtener_registros("SELECT * FROM instagram_feed ORDER BY id DESC LIMIT 6");

// Obtener categorías destacadas para el nuevo banner de categorías
$categorias_destacadas = obtener_registros("SELECT * FROM categorias WHERE activa = 1 ORDER BY id LIMIT 6");
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description"
    content="Chollo & Glam - Tienda premium de joyas y accesorios de alta calidad inspirados en la cultura peruana. Exclusividad, elegancia y envío gratuito en pedidos superiores a 30€.">
  <meta name="keywords" content="joyas premium, accesorios de lujo, Perú, artesanía exclusiva, ofertas, descuentos">
  <meta name="theme-color" content="#f8f3eb">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <title>Chollo & Glam | Joyas y Accesorios de Lujo</title>

  <!-- Precargar fuentes críticas para mejorar rendimiento -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Raleway:wght@300;400;500;600&display=swap"
    rel="stylesheet">

  <!-- Favicon y iconos para distintas plataformas -->
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16x16.png">
  <link rel="manifest" href="site.webmanifest">

  <!-- Estilos CSS con versiones para caché -->
  <link rel="stylesheet" href="assets/css/normalize.css?v=1.2">
  <link rel="stylesheet" href="assets/nav.css">
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="musica.css">
  <link rel="stylesheet" href="css/footer">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Carga adelantada de scripts críticos -->
  <link rel="preload" href="assets/js/main.js" as="script">

  <!-- Structured data para SEO -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "JewelryStore",
    "name": "Chollo & Glam",
    "description": "Tienda premium de joyas y accesorios inspirados en la cultura peruana",
    "url": "https://cholloyglam.com",
    "telephone": "+34 000 000 000",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Calle Principal 123",
      "addressLocality": "Sullana",
      "addressRegion": "Piura",
      "postalCode": "20001",
      "addressCountry": "PE"
    },
    "openingHours": "Mo-Sa 10:00-20:00",
    "priceRange": "€€€",
    "sameAs": [
      "https://www.instagram.com/cholloyglam",
      "https://www.facebook.com/cholloyglam"
    ]
  }
  </script>
</head>

<body>
  <!-- Incluir navegación -->
  <?php include 'includes/nav.php'; ?>

  <!-- Hero Banner Mejorado -->
  <section class="hero-banner">
    <div class="container">
      <div class="hero-content">
        <p class="hero-subtitle">Coleccion Exclusiva 2025</p>
        <h1 class="hero-title">Lujo & <span>Exclusividad</span></h1>
        <p class="hero-description">Descubra nuestra coleccion de edicion limitada con piezas artesanales inspiradas en la cultura peruana. Envio gratis en pedidos superiores a 30 euros.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
          <a href="colecciones.php" class="cta-button">EXPLORAR COLECCION <i class="fas fa-arrow-right"></i></a>
          <a href="novedades.php" class="cta-button" style="background:transparent;border:2px solid rgba(200,162,85,0.5);box-shadow:none">VER NOVEDADES</a>
        </div>
      </div>
    </div>
  </section>

  <main class="container main-content">
    <!-- Banner de Experiencia de Compra Mejorado -->
    <section class="shopping-experience-banner">
      <div class="experience-items">
        <div class="experience-item">
          <i class="fas fa-certificate" aria-hidden="true"></i>
          <span>Certificación de Autenticidad</span>
        </div>
        <div class="experience-item">
          <i class="fas fa-shield-alt" aria-hidden="true"></i>
          <span>Garantía Premium</span>
        </div>
        <div class="experience-item">
          <i class="fas fa-credit-card" aria-hidden="true"></i>
          <span>Pago Seguro</span>
        </div>
        <div class="experience-item">
          <i class="fas fa-truck" aria-hidden="true"></i>
          <span>Envío Express Gratuito</span>
        </div>
      </div>
      <a href="info.php" class="info-link">Más información <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </section>

    <!-- Categorías Destacadas - NUEVA SECCIÓN -->
    <section class="featured-categories">
      <div class="section-header">
        <h2>Categorías Exclusivas</h2>
        <a href="categorias.php" class="view-all">Ver todas <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
      </div>
      <div class="categories-grid">
        <?php foreach ($categorias_destacadas as $categoria): ?>
          <a href="productos.php?categoria=<?php echo urlencode($categoria['nombre']); ?>" class="category-card">
            <div class="category-image">
              <img src="admin/<?php echo htmlspecialchars($categoria['imagen']); ?>"
                alt="<?php echo htmlspecialchars($categoria['nombre']); ?>" width="150" height="150" loading="lazy">
            </div>
            <h3 class="category-name"><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Collection Showcase Mejorado -->
    <section class="collections-showcase">
      <div class="section-header">
        <h2>Nuestras Colecciones Exclusivas</h2>
        <a href="colecciones.php" class="view-all">Ver todas <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
      </div>
      <div class="collections-grid">
        <?php foreach ($colecciones as $coleccion): ?>
          <div class="collection-card">
            <img src="<?php echo htmlspecialchars($coleccion['imagen']); ?>"
              alt="<?php echo htmlspecialchars($coleccion['nombre']); ?>" width="300" height="200" loading="lazy"
              class="collection-image">
            <div class="collection-overlay">
              <h3><?php echo htmlspecialchars($coleccion['nombre']); ?></h3>
              <a href="colecciones.php?id=<?php echo strtolower(str_replace(' ', '-', $coleccion['nombre'])); ?>"
                class="collection-link">Explorar <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Flash Deals Mejorado -->
    <section class="flash-deals">
      <div class="section-header premium">
        <div class="flash-title">
          <i class="fas fa-bolt" aria-hidden="true"></i>
          <h2>Ofertas Exclusivas Limitadas</h2>
        </div>
        <div class="flash-timer">
          <span>Finaliza en:</span>
          <div class="countdown" aria-live="polite">
            <div class="countdown-item">
              <span id="hours"><?php echo str_pad($horas, 2, '0', STR_PAD_LEFT); ?></span>
              <span>hrs</span>
            </div>
            <div class="countdown-item">
              <span id="minutes"><?php echo str_pad($minutos, 2, '0', STR_PAD_LEFT); ?></span>
              <span>min</span>
            </div>
            <div class="countdown-item">
              <span id="seconds"><?php echo str_pad($segundos, 2, '0', STR_PAD_LEFT); ?></span>
              <span>seg</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Product Grid con mejoras profesionales -->
      <div class="product-grid premium">
        <?php if (empty($productos_a_mostrar)): ?>
          <div class="no-products">
            <p>Estamos renovando nuestro catálogo de ofertas exclusivas.</p>
            <p>Regrese pronto para descubrir nuestras nuevas colecciones limitadas.</p>
          </div>
        <?php else: ?>
          <?php foreach ($productos_a_mostrar as $producto): ?>
            <article class="product-card">
              <?php if (isset($producto['precio_original']) && $producto['precio_original'] && $producto['precio_actual'] < $producto['precio_original']): ?>
                <?php
                $descuento = round(100 - ($producto['precio_actual'] * 100 / $producto['precio_original']));
                echo '<div class="product-badge">-' . $descuento . '%</div>';
                ?>
              <?php elseif (isset($producto['etiqueta']) && $producto['etiqueta']): ?>
                <div class="product-badge <?php echo strtolower(htmlspecialchars($producto['etiqueta'])); ?>">
                  <?php echo htmlspecialchars($producto['etiqueta']); ?>
                </div>
              <?php endif; ?>

              <button class="product-wishlist" aria-label="Añadir a favoritos"
                data-product-id="<?php echo isset($producto['id']) ? $producto['id'] : ''; ?>">
                <i class="far fa-heart"></i>
              </button>

              <a href="producto.php?id=<?php echo isset($producto['id']) ? $producto['id'] : ''; ?>"
                class="product-image-container">
                <?php if (isset($producto['imagen']) && $producto['imagen']): ?>
                  <img src="<?php echo htmlspecialchars($producto['imagen']); ?>"
                    alt="<?php echo isset($producto['nombre']) ? htmlspecialchars($producto['nombre']) : 'Producto'; ?>"
                    width="250" height="250" loading="lazy" class="product-image">
                <?php else: ?>
                  <div class="product-image-placeholder">Imagen no disponible</div>
                <?php endif; ?>
              </a>

              <div class="product-info">
                <div class="product-category">
                  <?php echo isset($producto['tipo']) ? htmlspecialchars($producto['tipo']) : 'Exclusivo'; ?>
                </div>
                <h3 class="product-title">
                  <a href="producto.php?id=<?php echo isset($producto['id']) ? $producto['id'] : ''; ?>">
                    <?php echo isset($producto['nombre']) ? htmlspecialchars($producto['nombre']) : 'Producto exclusivo'; ?>
                  </a>
                </h3>
                <div class="product-rating">
                  <div class="rating-stars" aria-label="Valoración: 4.5 de 5 estrellas">
                    <?php
                    // Mostrar estrellas basadas en valoración real
                    $valoracion = isset($producto['valoracion']) ? $producto['valoracion'] : 4.5;
                    for ($i = 1; $i <= 5; $i++) {
                      if ($i <= floor($valoracion)) {
                        echo '<i class="fas fa-star"></i>';
                      } elseif ($i - 0.5 <= $valoracion) {
                        echo '<i class="fas fa-star-half-alt"></i>';
                      } else {
                        echo '<i class="far fa-star"></i>';
                      }
                    }
                    ?>
                  </div>
                  <span
                    class="rating-count">(<?php echo isset($producto['num_valoraciones']) ? $producto['num_valoraciones'] : rand(10, 200); ?>)</span>
                </div>
                <div class="product-price">
                  <span
                    class="price-current"><?php echo number_format(isset($producto['precio_actual']) ? $producto['precio_actual'] : 0, 2, ',', '.'); ?>€</span>
                  <?php if (isset($producto['precio_original']) && $producto['precio_original'] && $producto['precio_actual'] < $producto['precio_original']): ?>
                    <span
                      class="price-original"><?php echo number_format($producto['precio_original'], 2, ',', '.'); ?>€</span>
                  <?php endif; ?>
                </div>
                <button class="add-to-cart-btn"
                  data-product-id="<?php echo isset($producto['id']) ? $producto['id'] : ''; ?>">
                  <i class="fas fa-shopping-cart"></i> Añadir al carrito
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- Colección Premium - MEJORADA -->
    <section class="premium-collection">
      <div class="section-header">
        <h2>Colección Premium</h2>
        <a href="colecciones.php?premium=1" class="view-all">Ver colección completa <i class="fas fa-chevron-right"
            aria-hidden="true"></i></a>
      </div>
      <div class="premium-banner">
        <div class="premium-content">
          <h3>Diseños Exclusivos</h3>
          <p>Descubra nuestra colección más selecta, creada con los materiales más finos y técnicas tradicionales
            peruanas.</p>
          <a href="colecciones.php?premium=1" class="btn btn-premium">Explorar</a>
        </div>
      </div>
      <div class="premium-grid">
        <?php
        // Obtener productos premium (categoría 'premium')
        $productos_premium = obtener_productos_por_categoria('premium', 2);
        foreach ($productos_premium as $producto):
          ?>
          <article class="premium-card">
            <div class="premium-image">
              <img src="<?php echo htmlspecialchars($producto['imagen']); ?>"
                alt="<?php echo htmlspecialchars($producto['nombre']); ?>" width="400" height="300" loading="lazy">
            </div>
            <div class="premium-content">
              <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
              <p><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 120)) . '...'; ?></p>
              <div class="premium-price">
                <span class="current-price"><?php echo number_format($producto['precio_actual'], 2, ',', '.'); ?>€</span>
                <?php if (isset($producto['precio_original']) && $producto['precio_original'] && $producto['precio_actual'] < $producto['precio_original']): ?>
                  <span
                    class="original-price"><?php echo number_format($producto['precio_original'], 2, ',', '.'); ?>€</span>
                <?php endif; ?>
              </div>
              <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary">Ver detalles</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Instagram Feed Mejorado -->
    <section class="instagram-feed">
      <div class="section-header">
        <h2>Descubra Nuestro Estilo</h2>
        <a href="https://www.instagram.com/cholloyglam" target="_blank" rel="noopener"
          class="instagram-link">@cholloyglam <i class="fab fa-instagram"></i></a>
      </div>
      <div class="instagram-grid">
        <?php foreach ($instagram_posts as $post): ?>
          <a href="<?php echo htmlspecialchars($post['url'] ?? 'https://www.instagram.com/cholloyglam'); ?>"
            target="_blank" rel="noopener" class="instagram-item">
            <img src="<?php echo htmlspecialchars($post['imagen']); ?>" alt="Post de Instagram - Chollo & Glam"
              width="200" height="200" loading="lazy" class="instagram-image">
            <div class="instagram-overlay">
              <i class="fab fa-instagram"></i>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Newsletter Mejorado -->
    <section class="newsletter premium">
      <div class="newsletter-content">
        <h2 class="newsletter-title">Únase a Nuestro Club Exclusivo</h2>
        <p class="newsletter-text">Suscríbase ahora y reciba un 15% de descuento en su primera compra, acceso a ventas
          privadas y colecciones exclusivas.</p>
        <form class="newsletter-form" id="newsletter-form" method="post" action="procesar-newsletter.php" novalidate>
          <div class="form-group">
            <input type="email" name="email" id="newsletter-email" placeholder="Su correo electrónico"
              aria-label="Su correo electrónico" required pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$">
            <div class="form-error" id="newsletter-email-error"></div>
          </div>
          <button type="submit">Suscribirse <i class="fas fa-paper-plane"></i></button>
        </form>
      </div>
    </section>
  </main>

  <!-- Incluir footer -->
  <?php include 'includes/footer.php'; ?>

  <!-- Scripts con defer para mejor rendimiento -->
  <script src="assets/js/main.js" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Variables para el contador
      const horasElement = document.getElementById('hours');
      const minutosElement = document.getElementById('minutes');
      const segundosElement = document.getElementById('seconds');

      // Inicializar valores
      let horas = <?php echo $horas; ?>;
      let minutos = <?php echo $minutos; ?>;
      let segundos = <?php echo $segundos; ?>;

      // Iniciar contador
      const contadorInterval = setInterval(function () {
        if (segundos > 0) {
          segundos--;
        } else {
          if (minutos > 0) {
            minutos--;
            segundos = 59;
          } else {
            if (horas > 0) {
              horas--;
              minutos = 59;
              segundos = 59;
            } else {
              clearInterval(contadorInterval);
              // Usar notificación en lugar de alert para mejor experiencia
              mostrarNotificacion('¡Las ofertas exclusivas han finalizado! Próximamente nuevas colecciones.');
              // Recargar después de 3 segundos
              setTimeout(() => {
                location.reload();
              }, 3000);
              return;
            }
          }
        }

        // Actualizar elementos HTML con comprobación para evitar errores si no existen
        if (horasElement) horasElement.textContent = horas.toString().padStart(2, '0');
        if (minutosElement) minutosElement.textContent = minutos.toString().padStart(2, '0');
        if (segundosElement) segundosElement.textContent = segundos.toString().padStart(2, '0');
      }, 1000);

      // Gestión de favoritos mejorada
      const wishlistButtons = document.querySelectorAll('.product-wishlist');
      wishlistButtons.forEach(button => {
        button.addEventListener('click', function (e) {
          e.preventDefault(); // Prevenir comportamiento predeterminado
          const icon = this.querySelector('i');
          const productId = this.getAttribute('data-product-id');

          if (!productId) {
            console.error('Error: No se encontró ID de producto');
            return;
          }

          icon.classList.toggle('far');
          icon.classList.toggle('fas');

          // Guardar en localStorage
          const favoritos = JSON.parse(localStorage.getItem('favoritos') || '[]');

          if (icon.classList.contains('fas')) {
            // Añadir a favoritos si no existe
            if (!favoritos.includes(productId)) {
              favoritos.push(productId);
              mostrarNotificacion('Producto añadido a favoritos');
            }
          } else {
            // Eliminar de favoritos
            const index = favoritos.indexOf(productId);
            if (index > -1) {
              favoritos.splice(index, 1);
              mostrarNotificacion('Producto eliminado de favoritos');
            }
          }

          localStorage.setItem('favoritos', JSON.stringify(favoritos));
        });
      });

      // Cargar estado de favoritos desde localStorage
      function cargarFavoritos() {
        const favoritos = JSON.parse(localStorage.getItem('favoritos') || '[]');
        wishlistButtons.forEach(button => {
          const productId = button.getAttribute('data-product-id');
          if (favoritos.includes(productId)) {
            const icon = button.querySelector('i');
            icon.classList.remove('far');
            icon.classList.add('fas');
          }
        });
      }

      // Llamar a la función al cargar la página
      cargarFavoritos();

      // Gestión del carrito mejorada
      const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
      addToCartButtons.forEach(button => {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          const productId = this.getAttribute('data-product-id');

          if (!productId) {
            console.error('Error: No se encontró ID de producto');
            return;
          }

          // Lógica para añadir al carrito
          const carrito = JSON.parse(localStorage.getItem('carrito') || '{}');
          if (carrito[productId]) {
            carrito[productId]++;
          } else {
            carrito[productId] = 1;
          }
          localStorage.setItem('carrito', JSON.stringify(carrito));

          // Actualizar contador del carrito
          actualizarContadorCarrito();

          // Animación y notificación
          this.classList.add('added');
          setTimeout(() => {
            this.classList.remove('added');
          }, 1000);

          mostrarNotificacion('Producto añadido al carrito');
        });
      });

      // Función para actualizar contador del carrito
      function actualizarContadorCarrito() {
        const carrito = JSON.parse(localStorage.getItem('carrito') || '{}');
        const total = Object.values(carrito).reduce((sum, cantidad) => sum + cantidad, 0);

        // Buscar el elemento del contador (ajustar según estructura)
        const contador = document.querySelector('.cart-count');
        if (contador) {
          contador.textContent = total;
          contador.style.display = total > 0 ? 'flex' : 'none';
        }
      }

      // Llamar a la función al cargar la página
      actualizarContadorCarrito();

      // Función para mostrar notificaciones mejorada
      function mostrarNotificacion(mensaje) {
        // Comprobar si ya existe una notificación
        const notificacionExistente = document.querySelector('.notificacion');
        if (notificacionExistente) {
          // Si existe, eliminarla primero
          document.body.removeChild(notificacionExistente);
        }

        const notificacion = document.createElement('div');
        notificacion.className = 'notificacion';
        notificacion.innerHTML = `
          <div class="notificacion-contenido">
            <i class="fas fa-check-circle"></i>
            <span>${mensaje}</span>
          </div>
          <button class="cerrar-notificacion" aria-label="Cerrar notificación">
            <i class="fas fa-times"></i>
          </button>
        `;
        document.body.appendChild(notificacion);

        // Botón para cerrar manualmente
        const cerrarBtn = notificacion.querySelector('.cerrar-notificacion');
        if (cerrarBtn) {
          cerrarBtn.addEventListener('click', () => {
            notificacion.classList.remove('mostrar');
            setTimeout(() => {
              if (document.body.contains(notificacion)) {
                document.body.removeChild(notificacion);
              }
            }, 300);
          }, 3000);
        }

        // Gestión de música de fondo con mejor UX
        const permissionModal = document.getElementById('music-permission-modal');
        const allowMusicBtn = document.getElementById('allow-music');
        const denyMusicBtn = document.getElementById('deny-music');
        const backgroundMusic = document.getElementById('background-music');
        const musicControl = document.getElementById('music-control');
        const musicIcon = document.getElementById('music-icon');

        // Comprobar preferencia guardada
        const musicPreference = localStorage.getItem('musicPreference');

        if (musicPreference === null) {
          // Mostrar el modal si no hay preferencia guardada
          permissionModal.style.display = 'block';
        } else if (musicPreference === 'allowed') {
          // Si el usuario permitió la música anteriormente, mostrar controles
          musicControl.style.display = 'flex';
          // No reproducir automáticamente para evitar problemas con políticas de navegadores
        }

        // Cuando el usuario permite la música
        allowMusicBtn.addEventListener('click', function () {
          localStorage.setItem('musicPreference', 'allowed');
          permissionModal.style.display = 'none';
          musicControl.style.display = 'flex';

          // Intentar reproducir (dependerá de políticas del navegador)
          backgroundMusic.volume = 0.3; // Volumen moderado
          const playPromise = backgroundMusic.play();
          if (playPromise !== undefined) {
            playPromise.catch(error => {
              console.log('Reproducción automática bloqueada por el navegador:', error);
              // Mostrar mensaje para que el usuario interactúe
              mostrarNotificacion('Haga clic en el icono de música para iniciar la experiencia completa');
            });
          }
        });

        // Cuando el usuario rechaza la música
        denyMusicBtn.addEventListener('click', function () {
          localStorage.setItem('musicPreference', 'denied');
          permissionModal.style.display = 'none';
        });

        // Toggle para pausar/reproducir música con mejor feedback
        musicControl.addEventListener('click', function () {
          musicControl.classList.add('music-animate');

          setTimeout(() => {
            musicControl.classList.remove('music-animate');
          }, 1000);

          if (backgroundMusic.paused) {
            backgroundMusic.play()
              .then(() => {
                musicIcon.className = 'fas fa-music';
                mostrarNotificacion('Música ambiental activada');
              })
              .catch(error => {
                console.error('Error al reproducir música:', error);
                mostrarNotificacion('No se pudo reproducir la música. Intente más tarde.');
              });
          } else {
            backgroundMusic.pause();
            musicIcon.className = 'fas fa-volume-mute';
            mostrarNotificacion('Música ambiental desactivada');
          }
        });

        // Mejorar funcionalidad del botón volver arriba
        const backToTopButton = document.querySelector('.back-to-top');

        if (backToTopButton) {
          // Ocultar inicialmente el botón
          backToTopButton.classList.add('hidden');

          window.addEventListener('scroll', () => {
            // Mostrar botón después de desplazar 300px
            if (window.pageYOffset > 300) {
              backToTopButton.classList.add('show');
              backToTopButton.classList.remove('hidden');
            } else {
              backToTopButton.classList.remove('show');
              backToTopButton.classList.add('hidden');
            }
          });

          backToTopButton.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({
              top: 0,
              behavior: 'smooth'
            });
          });
        }

        // Validación del formulario de newsletter
        const newsletterForm = document.getElementById('newsletter-form');
        const emailInput = document.getElementById('newsletter-email');
        const emailError = document.getElementById('newsletter-email-error');

        if (newsletterForm) {
          newsletterForm.addEventListener('submit', function (e) {
            let isValid = true;

            // Validar email
            if (!emailInput.validity.valid) {
              isValid = false;
              emailError.textContent = 'Por favor, introduzca un correo electrónico válido.';
              emailError.style.display = 'block';
              emailInput.classList.add('error');
            } else {
              emailError.textContent = '';
              emailError.style.display = 'none';
              emailInput.classList.remove('error');
            }

            // Prevenir envío si no es válido
            if (!isValid) {
              e.preventDefault();
            } else {
              // Añadir un efecto visual cuando el formulario es válido
              newsletterForm.classList.add('submitted');
              // Mostrar notificación de éxito
              mostrarNotificacion('¡Gracias por suscribirse! Pronto recibirá nuestras novedades exclusivas.');
            }
          });

          // Validación en tiempo real
          emailInput.addEventListener('input', function () {
            if (this.validity.valid) {
              emailError.textContent = '';
              emailError.style.display = 'none';
              this.classList.remove('error');
            }
          });
        }

        // Lazy load imágenes
        if ('IntersectionObserver' in window) {
          const lazyImages = document.querySelectorAll('img[loading="lazy"]');

          const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                const img = entry.target;
                const src = img.getAttribute('data-src');

                if (src) {
                  img.src = src;
                  img.removeAttribute('data-src');
                }

                observer.unobserve(img);
              }
            });
          });

          lazyImages.forEach(image => {
            // Solo observar si tiene atributo data-src
            if (image.getAttribute('data-src')) {
              imageObserver.observe(image);
            }
          });
        }

        // Optimización de rendimiento para animaciones
        const optimizeAnimations = () => {
          // Detectar si el navegador prefiere animaciones reducidas
          const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

          if (prefersReducedMotion) {
            // Aplicar clase al body para desactivar animaciones
            document.body.classList.add('reduce-motion');
          }
        };

        // Ejecutar optimización
        optimizeAnimations();

        // Detectar cambios en la preferencia durante la sesión
        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', optimizeAnimations);

        // Animaciones de entrada para elementos clave
        const animateElements = () => {
          const elements = document.querySelectorAll('.product-card, .collection-card, .premium-card, .category-card');

          elements.forEach((element, index) => {
            // Detectar si el elemento está en el viewport
            const position = element.getBoundingClientRect();

            // Si el elemento es visible
            if (position.top < window.innerHeight && position.bottom >= 0) {
              setTimeout(() => {
                element.classList.add('animate-in');
              }, 100 * (index % 6)); // Escalonar animaciones en grupos de 6
            }
          });
        };

        // Ejecutar animaciones al cargar
        window.addEventListener('load', animateElements);

        // Y también al hacer scroll
        window.addEventListener('scroll', animateElements);

        // Mejorar accesibilidad
        const mejorarAccesibilidad = () => {
          // Asegurar que todos los enlaces tengan texto descriptivo
          document.querySelectorAll('a').forEach(enlace => {
            if (!enlace.textContent.trim() && !enlace.getAttribute('aria-label')) {
              const contenidoImagen = enlace.querySelector('img');
              if (contenidoImagen && contenidoImagen.alt) {
                enlace.setAttribute('aria-label', contenidoImagen.alt);
              }
            }
          });

          // Asegurar que todos los botones tengan descripciones
          document.querySelectorAll('button').forEach(boton => {
            if (!boton.textContent.trim() && !boton.getAttribute('aria-label')) {
              if (boton.querySelector('i.fa, i.fas, i.far, i.fab')) {
                // Intentar inferir propósito basado en clase del ícono
                const icono = boton.querySelector('i');
                if (icono.classList.contains('fa-heart') || icono.classList.contains('fa-heart-o')) {
                  boton.setAttribute('aria-label', 'Añadir a favoritos');
                } else if (icono.classList.contains('fa-shopping-cart')) {
                  boton.setAttribute('aria-label', 'Añadir al carrito');
                }
              }
            }
          });
        };

        // Ejecutar mejoras de accesibilidad
        mejorarAccesibilidad();
      });

    // Evento para cuando la ventana termina de cargar (incluyendo imágenes y recursos)
    window.addEventListener('load', function () {
      // Ocultar indicadores de carga si los hubiera
      const preloader = document.getElementById('preloader');
      if (preloader) {
        preloader.classList.add('fade-out');
        setTimeout(() => {
          preloader.style.display = 'none';
        }, 500);
      }

      // Iniciar efecto de hover en colecciones destacadas
      document.querySelectorAll('.collection-card').forEach(card => {
        card.addEventListener('mouseenter', function () {
          this.classList.add('hover');
        });

        card.addEventListener('mouseleave', function () {
          this.classList.remove('hover');
        });
      });
    });
  </script>
</body>

</html>