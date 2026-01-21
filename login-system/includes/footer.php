    <footer class="footer mt-auto py-3" style="background-color: #6b705c; color: #ffffff;">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Sistema de Login. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo isset($isSubPage) ? '../assets/js/script.js' : 'assets/js/script.js'; ?>"></script>
</body>
</html>
