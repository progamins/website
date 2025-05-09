<link rel="stylesheet" href="././assets/footer.css">
<!-- Footer Section -->
<footer class="pie-sitio">
    <div class="contenedor">
        <!-- Main Footer Content -->
        <div class="contenido-pie">
            <!-- Company Information -->
            <div class="columna-pie">
                <a href="index.php" class="enlace-logo-pie">
                    <img src="uploads/productos/haruki_logo.png" alt="Chollo & Glam" class="logo-pie">
                </a>
                <p class="descripcion-pie">
                    Descubre nuestra colección exclusiva de joyas y accesorios inspirados en la cultura peruana.
                    Artesanía de calidad con envíos a todo el mundo.
                </p>
                <div class="redes-pie">
                    <a href="https://facebook.com/" class="enlace-social" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://instagram.com/" class="enlace-social" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://pinterest.com/" class="enlace-social" aria-label="Pinterest">
                        <i class="fab fa-pinterest-p"></i>
                    </a>
                    <a href="https://tiktok.com/" class="enlace-social" aria-label="TikTok">
                        <i class="fab fa-tiktok"></i>
                    </a>
                </div>
            </div>

            <!-- Shop Links -->
            <div class="columna-pie">
                <h3>Comprar</h3>
                <a href="categoria.php?cat=novedades" class="enlace-pie">Novedades</a>
                <a href="categoria.php?cat=ofertas" class="enlace-pie">Ofertas</a>
                <a href="categoria.php?cat=bestsellers" class="enlace-pie">Más Vendidos</a>
                <a href="colecciones.php" class="enlace-pie">Colecciones</a>
                <a href="regalos.php" class="enlace-pie">Ideas para Regalos</a>
            </div>

            <!-- Support Links -->
            <div class="columna-pie">
                <h3>Ayuda</h3>
                <a href="contacto.php" class="enlace-pie">Contacto</a>
                <a href="devoluciones.php" class="enlace-pie">Devoluciones</a>
                <a href="envios.php" class="enlace-pie">Envíos</a>
                <a href="preguntas-frecuentes.php" class="enlace-pie">Preguntas Frecuentes</a>
                <a href="seguimiento.php" class="enlace-pie">Seguimiento de Pedido</a>
            </div>

            <!-- About Links -->
            <div class="columna-pie">
                <h3>Sobre Nosotros</h3>
                <a href="nuestra-historia.php" class="enlace-pie">Nuestra Historia</a>
                <a href="materiales.php" class="enlace-pie">Materiales</a>
                <a href="sostenibilidad.php" class="enlace-pie">Sostenibilidad</a>
                <a href="blog.php" class="enlace-pie">Blog</a>
                <a href="trabaja-con-nosotros.php" class="enlace-pie">Trabaja con Nosotros</a>
            </div>

            <!-- Payment Methods and Delivery -->
            <div class="columna-pie">
                <h3>Métodos de Pago</h3>
                <div class="metodos-pago">
                    <i class="fab fa-cc-visa" aria-label="Visa"></i>
                    <i class="fab fa-cc-mastercard" aria-label="Mastercard"></i>
                    <i class="fab fa-cc-paypal" aria-label="PayPal"></i>
                    <i class="fab fa-cc-apple-pay" aria-label="Apple Pay"></i>
                    <i class="fab fa-cc-amazon-pay" aria-label="Amazon Pay"></i>
                </div>

                <h3 class="titulo-envio">Envíos</h3>
                <div class="metodos-envio">
                    <img src="uploads/icons/correos.png" alt="Correos" class="logo-envio">
                    <img src="uploads/icons/dhl.png" alt="DHL" class="logo-envio">
                    <img src="uploads/icons/seur.png" alt="SEUR" class="logo-envio">
                </div>
            </div>
        </div>

        <!-- Newsletter Section -->
        <div class="boletin-pie">
            <h3>Suscríbete a nuestra newsletter</h3>
            <p>Recibe las últimas novedades y ofertas exclusivas directamente en tu correo</p>
            <form class="formulario-boletin" action="newsletter-signup.php" method="post">
                <input type="email" name="email" placeholder="Tu correo electrónico" required>
                <button type="submit">Suscribirme <i class="fas fa-paper-plane"></i></button>
            </form>
        </div>

        <!-- Footer Bottom -->
        <div class="pie-inferior">
            <div class="copyright">
                &copy; 2025 Chollo & Glam. Todos los derechos reservados.
            </div>
            <div class="enlaces-pie">
                <a href="privacidad.php" class="enlace-pie-inferior">Política de Privacidad</a>
                <a href="cookies.php" class="enlace-pie-inferior">Política de Cookies</a>
                <a href="terminos.php" class="enlace-pie-inferior">Términos y Condiciones</a>
            </div>
            <div class="selector-idioma">
                <select name="language" id="language-select">
                    <option value="es">Español</option>
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                </select>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<a href="#" class="volver-arriba" aria-label="Volver arriba">
    <i class="fas fa-chevron-up"></i>
</a>

<!-- Actualiza el script para el botón volver arriba -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Funcionalidad del botón de volver arriba
        const botonVolverArriba = document.querySelector('.volver-arriba');

        if (botonVolverArriba) {
            // Mostrar botón cuando la página se desplaza
            window.addEventListener('scroll', function () {
                if (window.pageYOffset > 300) {
                    botonVolverArriba.classList.add('mostrar');
                } else {
                    botonVolverArriba.classList.remove('mostrar');
                }
            });

            // Desplazamiento suave hacia arriba al hacer clic
            botonVolverArriba.addEventListener('click', function (e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    });
</script>