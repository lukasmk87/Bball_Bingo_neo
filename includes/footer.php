<?php
/**
 * Basketball Bingo - Footer Template
 * Enhanced footer with statistics and debug information
 */

// Load settings if not already loaded
if (!class_exists('Settings')) {
    require_once __DIR__ . '/Settings.php';
}

// Get site version
$site_version = Settings::get('site_version', APP_VERSION);

// Get global statistics
try {
    $db = Database::getInstance();
    $globalStats = $db->fetch("SELECT page_views, games_played FROM global_stats WHERE id = 1");
    
    // Update page views counter
    $db->execute("UPDATE global_stats SET page_views = page_views + 1 WHERE id = 1");
} catch (Exception $e) {
    // Handle error silently
    error_log("Error fetching global stats: " . $e->getMessage());
    $globalStats = ['page_views' => '0', 'games_played' => '0'];
}

// Check if debug mode is enabled
$debugMode = Settings::isDebugMode();
?>
    </main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Basketball Bingo</h3>
                <p>&copy; <?php echo date('Y'); ?> CrossOver Podcast | Version: <?php echo htmlspecialchars($site_version); ?></p>
                <p><a href="https://crossoverpodcast.de" target="_blank" rel="noopener">Zum Podcast</a></p>
            </div>
            
            <div class="footer-section">
                <h3>Links</h3>
                <ul>
                    <li><a href="anleitung.php">Anleitung</a></li>
                    <li><a href="support.php">Support</a></li>
                    <li><a href="datenschutz.php">Datenschutz</a></li>
                    <li><a href="impressum.php">Impressum</a></li>
                </ul>
            </div>
            
            <div class="footer-section statistics">
                <h3>Statistiken</h3>
                <p>Seitenaufrufe: <?php echo number_format($globalStats['page_views']); ?></p>
                <p>Gespielte Spiele: <?php echo number_format($globalStats['games_played']); ?></p>
            </div>
        </div>
        
        <?php if ($debugMode): ?>
        <div class="debug-info">
            <p>Debug Mode: Active</p>
            <p>Page generated in: <?php echo round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2); ?> ms</p>
            <?php if (function_exists('memory_get_usage')): ?>
            <p>Memory usage: <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </footer>
    
    <!-- Scripts -->
    <script src="/assets/js/core.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'bingo.php'): ?>
    <script src="/assets/js/bingo.js"></script>
    <?php endif; ?>
    
    <?php if ($debugMode): ?>
    <!-- Debug information -->
    <div class="debug-indicator">Debug Mode</div>
    <?php endif; ?>
</body>
</html>