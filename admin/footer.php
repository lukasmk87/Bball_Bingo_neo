<footer class="admin-footer">
                <p>&copy; <?php echo date('Y'); ?> Basketball Bingo Admin | Version: <?php echo htmlspecialchars($siteVersion); ?></p>
                <?php if ($debugMode): ?>
                <p class="debug-info">
                    Page generated in: <?php echo round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2); ?> ms
                </p>
                <?php endif; ?>
            </footer>
        </div>
    </div>
    
    <!-- Core JavaScript -->
    <script src="../assets/js/core.js"></script>
    
    <!-- Admin-spezifische Scripts -->
    <?php if (file_exists("js/{$currentPage}.js")): ?>
    <script src="js/<?php echo $currentPage; ?>"></script>
    <?php endif; ?>
</body>
</html>