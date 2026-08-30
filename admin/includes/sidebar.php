<?php
// Standard admin sidebar - included in all admin pages
// Set $activePage before including this file
$currentPage = $activePage ?? 'panel';
?>
<div class="col-md-2 sidebar">
    <div class="text-center mb-4">
        <a href="panel.php" class="text-decoration-none">
            <h4 class="text-white fw-bold">Chollo &amp; Glam</h4>
            <small class="text-muted">Panel Admin</small>
        </a>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'panel' ? 'active' : '' ?>" href="panel.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'producto' ? 'active' : '' ?>" href="panel.php">
                <i class="bi bi-box-seam me-2"></i> Productos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'categoria' ? 'active' : '' ?>" href="gestionar_categorias.php">
                <i class="bi bi-tags me-2"></i> Categorías
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'coleccion' ? 'active' : '' ?>" href="coleccion.php">
                <i class="bi bi-collection me-2"></i> Colecciones
            </a>
        </li>
        <li class="nav-item mt-3">
            <small class="text-muted px-3 text-uppercase" style="font-size:.65rem;letter-spacing:2px">Marketing</small>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'oferta' ? 'active' : '' ?>" href="oferta_flash.php">
                <i class="bi bi-lightning me-2"></i> Ofertas Flash
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'testimonio' ? 'active' : '' ?>" href="testimonio.php">
                <i class="bi bi-star me-2"></i> Testimonios
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'instagram' ? 'active' : '' ?>" href="instagram.php">
                <i class="bi bi-instagram me-2"></i> Instagram
            </a>
        </li>
    </ul>
    <hr class="border-secondary">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="../index.php" target="_blank">
                <i class="bi bi-shop me-2"></i> Ver Tienda
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="logout.php">
                <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
            </a>
        </li>
    </ul>
</div>
