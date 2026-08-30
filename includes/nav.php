<!-- Top Banner -->
<div class="top-banner">
    <div class="container">
        <div class="top-banner-slider">
            <div class="top-banner-item">
                <i class="fas fa-gem"></i>
                <span>Envío gratis en pedidos superiores a 30€</span>
            </div>
            <div class="top-banner-item">
                <i class="fas fa-sync-alt"></i>
                <span>Garantía de devolución 30 días</span>
            </div>
            <div class="top-banner-item">
                <i class="fas fa-mobile-alt"></i>
                <span>10% descuento con nuestra app</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Navigation -->
<nav class="main-nav">
    <div class="container nav-container">
        <div class="logo">
            <a href="index.php">
                <img src="uploads/productos/haruki_logo.png" alt="Chollo & Glam" class="logo-image">
            </a>
        </div>
        <div class="search-bar">
            <form action="search.php" method="get">
                <input type="text" name="query" placeholder="Buscar joyas y accesorios...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="nav-links">
            <a href="novedades.php" class="nav-link"><i class="fas fa-fire"></i><span>Novedades</span></a>
            <a href="favoritos.php" class="nav-link"><i class="far fa-heart"></i><span>Favoritos</span></a>
            <a href="cuenta.php" class="nav-link"><i class="far fa-user"></i><span>Mi Cuenta</span></a>
            <a href="carrito.php" class="nav-link cart"><i class="fas fa-shopping-bag"></i><span>Carrito</span><span class="cart-count">0</span></a>
        </div>
        <button class="mobile-menu-toggle" aria-label="Abrir menú"><i class="fas fa-bars"></i></button>
    </div>
</nav>

<!-- Categories Scroll -->
<div class="categories-nav">
    <div class="container">
        <div class="categories-scroll">
            <div class="category-item active">Todos</div>
            <div class="category-item">Collares</div>
            <div class="category-item">Aretes</div>
            <div class="category-item">Pulseras</div>
            <div class="category-item">Anillos</div>
            <div class="category-item">Premium</div>
            <div class="category-item">Ofertas</div>
        </div>
    </div>
</div>

<!-- Mobile Menu -->
<div class="mobile-menu">
    <div class="mobile-menu-header">
        <img src="uploads/productos/haruki_logo.png" alt="Chollo & Glam" class="logo-image" width="120">
        <button class="mobile-menu-close" aria-label="Cerrar menú"><i class="fas fa-times"></i></button>
    </div>
    <div class="mobile-menu-links">
        <a href="index.php" class="mobile-menu-link"><i class="fas fa-home"></i> Inicio</a>
        <a href="novedades.php" class="mobile-menu-link"><i class="fas fa-fire"></i> Novedades</a>
        <a href="favoritos.php" class="mobile-menu-link"><i class="far fa-heart"></i> Favoritos</a>
        <a href="cuenta.php" class="mobile-menu-link"><i class="far fa-user"></i> Mi Cuenta</a>
        <a href="carrito.php" class="mobile-menu-link"><i class="fas fa-shopping-bag"></i> Carrito</a>
    </div>
    <div class="mobile-categories">
        <h3>Categorías</h3>
        <div class="mobile-categories-list">
            <a href="categoria.php?cat=collares" class="mobile-category">Collares</a>
            <a href="categoria.php?cat=aretes" class="mobile-category">Aretes</a>
            <a href="categoria.php?cat=pulseras" class="mobile-category">Pulseras</a>
            <a href="categoria.php?cat=anillos" class="mobile-category">Anillos</a>
            <a href="categoria.php?cat=ofertas" class="mobile-category">Ofertas</a>
        </div>
    </div>
</div>

<div class="menu-overlay"></div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var toggle = document.querySelector(".mobile-menu-toggle");
    var menu = document.querySelector(".mobile-menu");
    var overlay = document.querySelector(".menu-overlay");
    var closeBtn = document.querySelector(".mobile-menu-close");
    if (toggle && menu) {
        toggle.addEventListener("click", function() {
            menu.classList.add("active");
            overlay.classList.add("active");
            document.body.style.overflow = "hidden";
        });
        function closeMenu() {
            menu.classList.remove("active");
            overlay.classList.remove("active");
            document.body.style.overflow = "";
        }
        if (closeBtn) closeBtn.addEventListener("click", closeMenu);
        overlay.addEventListener("click", closeMenu);
    }
    document.querySelectorAll(".category-item").forEach(function(item) {
        item.addEventListener("click", function() {
            document.querySelectorAll(".category-item").forEach(function(i) { i.classList.remove("active"); });
            this.classList.add("active");
        });
    });
});
</script>