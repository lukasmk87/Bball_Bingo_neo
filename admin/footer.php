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
</body>
</html>