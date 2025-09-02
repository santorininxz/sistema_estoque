</div>
</main>
<footer>
<p>© <?php echo date("Y"); ?> - Escola Técnica de Enfermagem. Todos os direitos reservados.</p>
</footer>

<!-- SCRIPT PARA O MENU RESPONSIVO -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const mainNav = document.querySelector('#main-nav');

        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');
            });
        }
    });
</script>
</body>
</html>