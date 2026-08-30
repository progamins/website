<nav class="admin-nav">
    <div class="nav-container">
        <a href="panel.php" class="nav-logo">
            <h2>Chollo & Glam</h2>
            <span>Admin</span>
        </a>
        <div class="nav-links">
            <a href="panel.php" class="nav-link"><i class="fas fa-box"></i> Productos</a>
            <a href="gestionar_categorias.php" class="nav-link"><i class="fas fa-tags"></i> Categorias</a>
            <a href="coleccion.php" class="nav-link"><i class="fas fa-layer-group"></i> Colecciones</a>
            <a href="oferta_flash.php" class="nav-link"><i class="fas fa-bolt"></i> Ofertas</a>
            <a href="testimonio.php" class="nav-link"><i class="fas fa-star"></i> Testimonios</a>
            <a href="instagram.php" class="nav-link"><i class="fab fa-instagram"></i> Instagram</a>
        </div>
        <div class="nav-right">
            <a href="../index.php" class="nav-link" target="_blank"><i class="fas fa-store"></i> Ver Tienda</a>
            <a href="logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </div>
        <button class="mobile-toggle"><i class="fas fa-bars"></i></button>
    </div>
</nav>
<style>
.admin-nav{background:#1a2332;padding:0 20px;position:sticky;top:0;z-index:1000;box-shadow:0 2px 10px rgba(0,0,0,.2)}
.admin-nav .nav-container{max-width:1400px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:60px}
.nav-logo{display:flex;align-items:baseline;gap:8px;text-decoration:none}
.nav-logo h2{color:#c8a255;font-family:'Playfair Display',serif;font-size:1.3rem;margin:0}
.nav-logo span{color:rgba(255,255,255,.5);font-size:.75rem;text-transform:uppercase;letter-spacing:.1em}
.nav-links{display:flex;gap:4px}
.nav-link{color:rgba(255,255,255,.7);text-decoration:none;padding:8px 14px;border-radius:8px;font-size:.85rem;font-weight:500;display:flex;align-items:center;gap:6px;transition:all .3s}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.1)}
.nav-link i{font-size:.9rem}
.nav-right{display:flex;gap:8px}
.nav-link.logout{color:#e74c3c}
.nav-link.logout:hover{background:rgba(231,76,60,.15)}
.mobile-toggle{display:none;background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer}
@media(max-width:992px){
  .nav-links{display:none}
  .mobile-toggle{display:block}
  .nav-links.active{display:flex;position:absolute;top:60px;left:0;right:0;background:#1a2332;flex-direction:column;padding:10px;box-shadow:0 4px 10px rgba(0,0,0,.2)}
}
</style>
<script>
document.querySelector('.mobile-toggle')?.addEventListener('click',function(){document.querySelector('.nav-links')?.classList.toggle('active')});
</script>
