<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="novedades.css">
  <link rel="stylesheet" href="index.css">

  <title>Novedades | Chollo & Glam | Joyas y Accesorios Exclusivos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Raleway:wght@300;400;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
  <?php include 'includes/nav.php'; ?>

  <!-- Hero Banner con Animación -->
  <section class="hero-banner-nove">
    <!-- Video Background -->
    <div class="video-background">
      <video autoplay muted loop>
        <source src="uploads/productos/banner-video1.mp4" type="video/mp4">
        <!-- Include additional formats for better browser support -->
        <source src="your-video.webm" type="video/webm">
        <!-- Fallback message if video doesn't load -->
        Your browser does not support the video tag.
      </video>
    </div>

    <!-- Dark overlay to improve text readability -->
    <div class="video-overlay"></div>

    <!-- Content stays the same -->
    <div class="hero-content">
      <div class="novelty-badge">
        NEW COLLECTION
      </div>

      <h1 class="hero-title">Spring Collection 2025</h1>
      <p class="hero-subtitle">Experience the latest trends in fashion</p>

      <p class="hero-description">
        Our new collection drops in:
      </p>

      <div class="countdown-timer">
        <div class="countdown-item">
          <span class="count" id="days">05</span>
          <span class="unit">days</span>
        </div>
        <div class="countdown-item">
          <span class="count" id="hours">12</span>
          <span class="unit">hours</span>
        </div>
        <div class="countdown-item">
          <span class="count" id="minutes">45</span>
          <span class="unit">minutes</span>
        </div>
        <div class="countdown-item">
          <span class="count" id="seconds">30</span>
          <span class="unit">seconds</span>
        </div>
      </div>

      <p class="hero-description">Piezas exclusivas que cuentan historias únicas</p>
      <div class="hero-actions">
        <a href="#latest-collection" class="primary-btn">Explorar novedades</a>
        <button class="notify-btn"><i class="far fa-bell"></i> Notificaciones prioritarias</button>
      </div>
    </div>
  </section>


  <div class="container main-content">
    <!-- Breadcrumb mejorado -->
    <div class="breadcrumb">
      <a href="index.php">Inicio</a> <i class="fas fa-chevron-right"></i> <span>Novedades</span>
    </div>

    <!-- Filtro temporal mejorado -->
    <div class="new-arrivals-timeline">
      <div class="container">
        <div class="timeline-navigation">
          <button class="timeline-nav active" data-time="all">Todas las novedades</button>
          <button class="timeline-nav" data-time="48h">Últimas 48h</button>
          <button class="timeline-nav" data-time="week">Esta semana</button>
          <button class="timeline-nav" data-time="month">Este mes</button>
        </div>
      </div>
    </div>

    <!-- Joya Destacada - Diseño Elegante -->
    <div class="featured-new-product">
      <div class="featured-image">
        <img src="uploads/productos/estrella.png" alt="Collar Estrella Fugaz" class="product-spotlight-image">
        <div class="product-tags">
          <span class="tag new-arrival">Novedad Exclusiva</span>
          <span class="tag exclusive">Serie Limitada</span>
        </div>
      </div>
      <div class="featured-content">
        <div class="featured-label">Joya Emblemática</div>
        <h2 class="featured-title">Collar "Estrella Fugaz"</h2>
        <div class="product-rating">
          <div class="stars">
            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
              class="fas fa-star"></i><i class="fas fa-star"></i>
          </div>
          <span class="first-reviews">Primeras impresiones</span>
        </div>
        <p class="featured-description">
          Una creación única que captura la magia efímera de una estrella fugaz. Elaborado en oro rosa de 18k con
          incrustaciones de diamantes naturales y un zafiro azul central que evoca el corazón luminoso de una estrella.
        </p>
        <div class="product-story">
          <h3 class="story-title"><i class="fas fa-feather"></i> La inspiración del diseñador</h3>
          <p class="story-text">
            Nacido de la contemplación de la lluvia de estrellas Perseidas, cada collar está elaborado artesanalmente en
            nuestro taller de Barcelona. La cadena simula con precisión la estela luminosa que surge cuando un astro
            atraviesa nuestra atmósfera.
          </p>
        </div>
        <div class="product-price-container">
          <div class="product-price">
            <span class="price-current">239,99€</span>
          </div>
          <span class="limited-stock">Edición limitada: 15 unidades</span>
        </div>
        <div class="product-actions">
          <button class="action-btn add-cart-btn">Añadir al carrito</button>
          <button class="action-btn wishlist-btn"><i class="far fa-heart"></i></button>
          <button class="action-btn share-btn"><i class="fas fa-share-alt"></i></button>
        </div>
      </div>
    </div>

    <!-- Colecciones Exclusivas - Slider -->
    <div id="latest-collection" class="collection-preview-slider">
      <div class="section-header">
        <h2 class="section-title"><i class="fas fa-sparkles"></i> COLECCIONES EXCLUSIVAS</h2>
        <div class="section-controls">
          <button class="control-btn prev"><i class="fas fa-chevron-left"></i></button>
          <button class="control-btn next"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
      <div class="collection-cards">
        <!-- Colección 1 -->
        <div class="collection-card">
          <div class="collection-image">
            <img src="uploads/productos/primavera-2025.png" alt="Colección Primavera 2025">
            <div class="collection-overlay">
              <span class="collection-date">Lanzamiento reciente</span>
              <div class="collection-tag new-tag">NUEVO</div>
            </div>
          </div>
          <div class="collection-info">
            <h3 class="collection-title">Primavera Floreciente</h3>
            <p class="collection-description">La naturaleza como fuente de inspiración eterna</p>
            <div class="collection-details">
              <span class="collection-items">12 piezas exclusivas</span>
              <a href="#" class="view-collection">Explorar colección</a>
            </div>
          </div>
        </div>

        <!-- Colección 2 -->
        <div class="collection-card">
          <div class="collection-image">
            <img src="uploads/productos/minimalista.png" alt="Colección Minimalista">
            <div class="collection-overlay">
              <span class="collection-date">Edición especial</span>
              <div class="collection-tag new-tag">NUEVO</div>
            </div>
          </div>
          <div class="collection-info">
            <h3 class="collection-title">Esencia Minimalista</h3>
            <p class="collection-description">La elegancia en su forma más pura</p>
            <div class="collection-details">
              <span class="collection-items">8 diseños únicos</span>
              <a href="#" class="view-collection">Explorar colección</a>
            </div>
          </div>
        </div>

        <!-- Colección 3 -->
        <div class="collection-card">
          <div class="collection-image">
            <img src="uploads/productos/village.png" alt="Colección Vintage">
            <div class="collection-overlay">
              <span class="collection-date">Serie limitada</span>
              <div class="collection-tag new-tag">NUEVO</div>
            </div>
          </div>
          <div class="collection-info">
            <h3 class="collection-title">Encanto Vintage</h3>
            <p class="collection-description">El pasado reinterpretado con visión contemporánea</p>
            <div class="collection-details">
              <span class="collection-items">10 piezas artesanales</span>
              <a href="#" class="view-collection">Explorar colección</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Últimas 48 Horas - Presentación Mejorada -->
    <div class="latest-arrivals-section">
      <div class="section-header with-time">
        <div class="time-badge">
          <i class="far fa-clock"></i>
          <span>Últimas 48 horas</span>
        </div>
        <h2 class="section-title">Novedades recientes</h2>
      </div>

      <div class="product-grid latest-grid">
        <!-- Producto 1 -->
        <div class="product-card new-arrival-card">
          <div class="arrival-time">
            <i class="fas fa-stopwatch"></i>
            <span>Recién llegado</span>
          </div>
          <div class="product-wishlist"><i class="far fa-heart"></i></div>
          <img src="uploads/productos/pendiente_perla.png" alt="Pendientes de perla" class="product-image">
          <div class="product-info">
            <div class="product-category">Pendientes</div>
            <h3 class="product-title">Pendientes de plata con perla cultivada</h3>
            <div class="product-rating">
              <div class="rating-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
              <span class="rating-count">(8)</span>
            </div>
            <div class="product-price">
              <span class="price-current">69,99€</span>
            </div>
            <button class="add-to-cart-btn">Añadir al carrito</button>
          </div>
        </div>

        <!-- Producto 2 -->
        <div class="product-card new-arrival-card">
          <div class="arrival-time">
            <i class="fas fa-stopwatch"></i>
            <span>Hace 18h</span>
          </div>
          <div class="product-wishlist"><i class="far fa-heart"></i></div>
          <img src="uploads/productos/anillo-esmeralda.png" alt="Anillo con esmeralda" class="product-image">
          <div class="product-info">
            <div class="product-category">Anillos</div>
            <h3 class="product-title">Anillo de oro blanco con esmeralda</h3>
            <div class="product-rating">
              <div class="rating-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="far fa-star"></i>
              </div>
              <span class="rating-count">(5)</span>
            </div>
            <div class="product-price">
              <span class="price-current">249,99€</span>
            </div>
            <button class="add-to-cart-btn">Añadir al carrito</button>
          </div>
        </div>

        <!-- Producto 3 -->
        <div class="product-card new-arrival-card">
          <div class="arrival-time">
            <i class="fas fa-stopwatch"></i>
            <span>Hace 24h</span>
          </div>
          <div class="product-wishlist"><i class="far fa-heart"></i></div>
          <img src="uploads/productos/pulsera_18k.png" alt="Pulsera de oro" class="product-image">
          <div class="product-info">
            <div class="product-category">Pulseras</div>
            <h3 class="product-title">Pulsera de oro 18k con eslabones</h3>
            <div class="product-rating">
              <div class="rating-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <span class="rating-count">(7)</span>
            </div>
            <div class="product-price">
              <span class="price-current">189,99€</span>
            </div>
            <button class="add-to-cart-btn">Añadir al carrito</button>
          </div>
        </div>

        <!-- Producto 4 -->
        <div class="product-card new-arrival-card">
          <div class="arrival-time">
            <i class="fas fa-stopwatch"></i>
            <span>Hace 36h</span>
          </div>
          <div class="product-wishlist"><i class="far fa-heart"></i></div>
          <img src="uploads/productos/reloj-plata.png" alt="Reloj de plata" class="product-image">
          <div class="product-info">
            <div class="product-category">Relojes</div>
            <h3 class="product-title">Reloj de plata con esfera nácar</h3>
            <div class="product-rating">
              <div class="rating-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="far fa-star"></i>
              </div>
              <span class="rating-count">(3)</span>
            </div>
            <div class="product-price">
              <span class="price-current">129,99€</span>
            </div>
            <button class="add-to-cart-btn">Añadir al carrito</button>
          </div>
        </div>
      </div>
    </div>


    <!-- Adelantos Exclusivos - Diseño Refinado -->
    <div class="coming-soon-preview">
      <div class="section-header">
        <div class="preview-badge">
          <i class="fas fa-eye"></i>
          <span>ADELANTO EXCLUSIVO</span>
        </div>
        <h2 class="section-title">Próximamente en Chollo & Glam</h2>
      </div>

      <div class="coming-soon-slider">
        <!-- Adelanto 1 -->
        <div class="preview-item">
          <div class="preview-image">
            <img src="uploads/productos/preview-collar-turquesa.png" alt="Colección Turquesa" class="preview-img">
            <div class="preview-overlay">
              <span class="preview-date">5 días para el lanzamiento</span>
            </div>
          </div>
          <div class="preview-info">
            <h3 class="preview-title">Aguas Turquesas</h3>
            <p class="preview-desc">Una oda a las cristalinas aguas mediterráneas</p>
            <button class="pre-order-btn">
              <i class="fas fa-bell"></i> Recibir notificación
            </button>
          </div>
        </div>

        <!-- Adelanto 2 -->
        <div class="preview-item">
          <div class="preview-image">
            <img src="uploads/productos/preview-anillos-gold.png" alt="Anillos Gold Edition" class="preview-img">
            <div class="preview-overlay">
              <span class="preview-date">Lanzamiento: 5 mayo</span>
            </div>
          </div>
          <div class="preview-info">
            <h3 class="preview-title">Gold Edition</h3>
            <p class="preview-desc">Anillos de edición limitada con oro de 24k</p>
            <button class="pre-order-btn">
              <i class="fas fa-bell"></i> Recibir notificación
            </button>
          </div>
        </div>

        <!-- Adelanto Secreto -->
        <div class="preview-item">
          <div class="preview-image blurred">
            <img src="uploads/productos/preview-coleccion-secreta.png" alt="Colección Secreta" class="preview-img">
            <div class="preview-overlay mystery">
              <span class="preview-date">Lanzamiento sorpresa</span>
              <div class="mystery-badge">SECRETO</div>
            </div>
          </div>
          <div class="preview-info">
            <h3 class="preview-title">Colección Misteriosa</h3>
            <p class="preview-desc">Una sorpresa extraordinaria en preparación</p>
            <button class="pre-order-btn mystery-btn">
              <i class="fas fa-bell"></i> Lista de espera
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Lanzamientos Semanales - Refinado -->
    <div class="weekly-releases">
      <div class="section-header with-time">
        <div class="time-badge weekly">
          <i class="far fa-calendar-alt"></i>
          <span>Esta semana</span>
        </div>
        <h2 class="section-title">Joyas de la semana</h2>
      </div>

      <div class="product-grid weekly-grid">
        <!-- Joya 1 -->
        <div class="product-card weekly-card">
          <div class="arrival-time weekly-tag">
            <i class="fas fa-calendar-day"></i>
            <span>Lunes</span>
          </div>
          <div class="product-wishlist"><i class="far fa-heart"></i></div>
          <img src="uploads/productos/pendientes-largos.png" alt="Pendientes largos" class="product-image">
          <div class="product-info">
            <div class="product-category">Pendientes</div>
            <h3 class="product-title">Pendientes cascada con cristales Swarovski</h3>
            <div class="product-rating">
              <div class="rating-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
              <span class="rating-count">(9)</span>
            </div>
            <div class="product-price">
              <span class="price-current">79,99€</span>
            </div>
            <button class="add-to-cart-btn">Añadir al carrito</button>
          </div>
        </div>

        <!-- Joya 2 -->
        <div class="product-card weekly-card">
          <div class="arrival-time weekly-tag">
            <i class="fas fa-calendar-day"></i>
            <span>Martes</span>
          </div>
          <div class="product-wishlist"><i class="far fa-heart"></i></div>
          <img src="uploads/productos/tobillera-plata.png" alt="Tobillera de plata" class="product-image">
          <div class="product-info">
            <div class="product-category">Accesorios</div>
            <h3 class="product-title">Tobillera de plata con dijes artesanales</h3>
            <div class="product-rating">
              <div class="rating-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="far fa-star"></i>
              </div>
              <span class="rating-count">(6)</span>
            </div>
            <div class="product-price">
              <span class="price-current">29,99€</span>
            </div>
            <button class="add-to-cart-btn">Añadir al carrito</button>
          </div>
        </div>

        <!-- Joya 3 -->
        <div class="product-card weekly-card">
          <div class="arrival-time weekly-tag">
            <i class="fas fa-calendar-day"></i>
            <span>Miércoles</span>
          </div>
          <div class="product-wishlist"><i class="far fa-heart"></i></div>
          <img src="uploads/productos/collar-vintage.png" alt="Collar vintage" class="product-image">
          <div class="product-info">
            <div class="product-category">Collares</div>
            <h3 class="product-title">Collar vintage con medallón grabado a mano</h3>
            <div class="product-rating">
              <div class="rating-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <span class="rating-count">(11)</span>
            </div>
            <div class="product-price">
              <span class="price-current">49,99€</span>
            </div>
            <button class="add-to-cart-btn">Añadir al carrito</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Calendario de Lanzamientos - Elegante -->
    <div class="release-schedule">
      <div class="section-header">
        <h2 class="section-title"><i class="far fa-calendar-check"></i> Agenda de novedades</h2>
        <p class="section-subtitle">Mantente al día con nuestros exclusivos lanzamientos</p>
      </div>

      <div class="schedule-timeline">
        <div class="timeline-event past">
          <div class="event-date">
            <span class="event-day">12</span>
            <span class="event-month">ABR</span>
          </div>
          <div class="event-content">
            <h3 class="event-title">Primavera Floreciente</h3>
            <span class="event-status">Disponible ahora</span>
          </div>
        </div>

        <div class="timeline-event past">
          <div class="event-date">
            <span class="event-day">15</span>
            <span class="event-month">ABR</span>
          </div>
          <div class="event-content">
            <h3 class="event-title">Esencia Minimalista</h3>
            <span class="event-status">Disponible ahora</span>
          </div>
        </div>

        <div class="timeline-event active">
          <div class="event-date">
            <span class="event-day">25</span>
            <span class="event-month">ABR</span>
          </div>
          <div class="event-content">
            <h3 class="event-title">Aguas Turquesas</h3>
            <div class="event-countdown">
              <span class="countdown-text">En 5 días</span>
              <button class="notify-event-btn">Recordatorio</button>
            </div>
          </div>
        </div>

        <div class="timeline-event">
          <div class="event-date">
            <span class="event-day">5</span>
            <span class="event-month">MAY</span>
          </div>
          <div class="event-content">
            <h3 class="event-title">Gold Edition</h3>
            <div class="event-countdown">
              <span class="countdown-text">En 2 semanas</span>
              <button class="notify-event-btn">Recordatorio</button>
            </div>
          </div>
        </div>

        <div class="timeline-event mystery-event">
          <div class="event-date">
            <span class="event-day">??</span>
            <span class="event-month">MAY</span>
          </div>
          <div class="event-content">
            <h3 class="event-title">Colección Sorpresa</h3>
            <div class="event-countdown">
              <span class="countdown-text">Fecha por revelar</span>
              <button class="notify-event-btn mystery-notify">Lista VIP</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Newsletter Elegante -->
    <div class="newsletter novelty-newsletter">
      <div class="newsletter-decoration">
        <i class="fas fa-gem gem-1"></i>
        <i class="fas fa-star star-1"></i>
        <i class="fas fa-gem gem-2"></i>
        <i class="fas fa-star star-2"></i>
      </div>
      <div class="newsletter-content">
        <h2 class="newsletter-title">Acceso privilegiado</h2>
        <p class="newsletter-text">Forma parte de nuestro círculo exclusivo y descubre nuestras colecciones antes que
          nadie. Recibe invitaciones a eventos privados y acceso prioritario a ediciones limitadas.</p>
        <form class="newsletter-form">
          <input type="email" placeholder="Tu correo electrónico" required>
          <button type="submit">Unirme al círculo exclusivo <i class="fas fa-paper-plane"></i></button>
        </form>
        <div class="newsletter-options">
          <label class="option-checkbox">
            <input type="checkbox" checked>
            <span class="checkmark"></span>
            Lanzamientos exclusivos
          </label>
          <label class="option-checkbox">
            <input type="checkbox" checked>
            <span class="checkmark"></span>
            Ediciones limitadas
          </label>
          <label class="option-checkbox">
            <input type="checkbox">
            <span class="checkmark"></span>
            Eventos privados
          </label>
        </div>
      </div>
    </div>
  </div>
  <?php include 'includes/footer.php'; ?>
</body>

</html>