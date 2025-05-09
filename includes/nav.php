<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chollo & Glam</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Raleway:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <!-- Navigation CSS -->
    <link rel="stylesheet" href="././assets/nav.css">
    <!-- Main CSS -->
</head>

<body>

    <!-- Top Banner with Promotions -->
    <div class="top-banner">
        <div class="container">
            <div class="top-banner-slider">
                <div class="top-banner-item">
                    <i class="fas fa-gem"></i>
                    <span>Envío gratis en pedidos superiores a 30€ • Oferta exclusiva</span>
                </div>
                <div class="top-banner-item">
                    <i class="fas fa-sync-alt"></i>
                    <span>Garantía de devolución • En un plazo de 30 días</span>
                </div>
                <div class="top-banner-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Descarga nuestra app y obtén 10% de descuento</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav" aria-label="Navegación principal">
        <div class="container nav-container">
            <div class="logo">
                <a href="index.php" aria-label="Página de inicio">
                    <img src="uploads/productos/haruki_logo.png" alt="Chollo & Glam" class="logo-image">
                </a>
            </div>

            <div class="search-bar">
                <form action="search.php" method="get">
                    <input type="text" name="query" placeholder="Buscar joyas y accesorios..."
                        aria-label="Buscar en la tienda">
                    <button type="submit" aria-label="Buscar"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="nav-links">
                <a href="novedades.php" class="nav-link">
                    <i class="fas fa-fire"></i>
                    <span>Novedades</span>
                </a>
                <a href="favoritos.php" class="nav-link">
                    <i class="far fa-heart"></i>
                    <span>Favoritos</span>
                </a>
                <a href="mi-cuenta.php" class="nav-link">
                    <i class="far fa-user"></i>
                    <span>Mi Cuenta</span>
                </a>
                <a href="carrito.php" class="nav-link cart">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Carrito</span>
                    <span class="cart-count" aria-label="0 artículos en el carrito">0</span>
                </a>
            </div>

            <button class="mobile-menu-toggle" aria-expanded="false" aria-label="Abrir menú">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Categories Horizontal Scroll -->
    <div class="categories-nav">
        <div class="container">
            <div class="categories-scroll" role="tablist">
                <div class="category-item active" role="tab" aria-selected="true" tabindex="0">Todos</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Collares</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Pendientes</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Pulseras</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Anillos</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Relojes</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Oro</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Plata</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Piedras</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Personalizados</div>
                <div class="category-item" role="tab" aria-selected="false" tabindex="0">Ofertas</div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" aria-hidden="true">
        <div class="mobile-menu-header">
            <img src="uploads/productos/haruki_logo.png" alt="Chollo & Glam" class="logo-image" width="120">
            <button class="mobile-menu-close" aria-label="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mobile-menu-links">
            <a href="index.php" class="mobile-menu-link">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>
            <a href="novedades.php" class="mobile-menu-link">
                <i class="fas fa-fire"></i>
                <span>Novedades</span>
            </a>
            <a href="favoritos.php" class="mobile-menu-link">
                <i class="far fa-heart"></i>
                <span>Favoritos</span>
            </a>
            <a href="mi-cuenta.php" class="mobile-menu-link">
                <i class="far fa-user"></i>
                <span>Mi Cuenta</span>
            </a>
            <a href="carrito.php" class="mobile-menu-link">
                <i class="fas fa-shopping-bag"></i>
                <span>Carrito</span>
            </a>
        </div>
        <div class="mobile-categories">
            <h3>Categorías</h3>
            <div class="mobile-categories-list">
                <a href="categoria.php?cat=todos" class="mobile-category">Todos</a>
                <a href="categoria.php?cat=collares" class="mobile-category">Collares</a>
                <a href="categoria.php?cat=pendientes" class="mobile-category">Pendientes</a>
                <a href="categoria.php?cat=pulseras" class="mobile-category">Pulseras</a>
                <a href="categoria.php?cat=anillos" class="mobile-category">Anillos</a>
                <a href="categoria.php?cat=relojes" class="mobile-category">Relojes</a>
                <a href="categoria.php?cat=oro" class="mobile-category">Oro</a>
                <a href="categoria.php?cat=plata" class="mobile-category">Plata</a>
                <a href="categoria.php?cat=piedras" class="mobile-category">Piedras</a>
                <a href="categoria.php?cat=personalizados" class="mobile-category">Personalizados</a>
                <a href="categoria.php?cat=ofertas" class="mobile-category">Ofertas</a>
            </div>
        </div>
    </div>

    <!-- Menu overlay for mobile -->
    <div class="menu-overlay"></div>

    <!-- Main content starts here -->
    <main id="main-content">
        <!-- Your main content goes here -->
    </main>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mobile menu toggle
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileMenu = document.querySelector('.mobile-menu');
            const menuOverlay = document.querySelector('.menu-overlay');
            const menuClose = document.querySelector('.mobile-menu-close');

            if (menuToggle && mobileMenu) {
                menuToggle.addEventListener('click', function () {
                    mobileMenu.classList.add('active');
                    menuOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    menuToggle.setAttribute('aria-expanded', 'true');
                    mobileMenu.setAttribute('aria-hidden', 'false');
                });

                if (menuClose) {
                    menuClose.addEventListener('click', closeMenu);
                }

                menuOverlay.addEventListener('click', closeMenu);

                function closeMenu() {
                    mobileMenu.classList.remove('active');
                    menuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                    menuToggle.setAttribute('aria-expanded', 'false');
                    mobileMenu.setAttribute('aria-hidden', 'true');
                }
            }

            // Category item selection
            const categoryItems = document.querySelectorAll('.category-item');
            if (categoryItems) {
                categoryItems.forEach(item => {
                    item.addEventListener('click', function () {
                        categoryItems.forEach(i => {
                            i.classList.remove('active');
                            i.setAttribute('aria-selected', 'false');
                        });
                        this.classList.add('active');
                        this.setAttribute('aria-selected', 'true');
                    });

                    // Handle keyboard navigation
                    item.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.click();
                        }
                    });
                });
            }

            // Banner slider (if needed)
            // This is a placeholder for banner slider functionality
            // You can implement automatic sliding or manual controls here
        });
    </script>
</body>

</html>