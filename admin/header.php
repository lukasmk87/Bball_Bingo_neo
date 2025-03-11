<?php
/**
 * Basketball Bingo - Admin Header
 * Header-Template für den Admin-Bereich
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aktuelle Seite für Hervorhebung im Menü ermitteln
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Basketball Bingo</title>
    <link rel="stylesheet" href="style.css">
    <!-- Modern Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Dashboard</h2>
                <div class="admin-user">
                    <span class="user-icon"><i class="bi bi-person-circle"></i></span>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Übersicht
                </a></li>
                <li><a href="users.php" class="<?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i> Benutzerverwaltung
                </a></li>
                <li><a href="clubs.php" class="<?php echo $currentPage == 'clubs.php' ? 'active' : ''; ?>">
                    <i class="bi bi-building"></i> Vereine
                </a></li>
                <li><a href="teams.php" class="<?php echo $currentPage == 'teams.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Teams
                </a></li>
                <li><a href="games.php" class="<?php echo $currentPage == 'games.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-event"></i> Spiele
                </a></li>
                <li><a href="bingofields.php" class="<?php echo $currentPage == 'bingofields.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-3x3"></i> Bingo Felder
                </a></li>
                <li><a href="suggestions.php" class="<?php echo $currentPage == 'suggestions.php' ? 'active' : ''; ?>">
                    <i class="bi bi-lightbulb"></i> Vorschläge
                </a></li>
                <li><a href="tickets.php" class="<?php echo $currentPage == 'tickets.php' ? 'active' : ''; ?>">
                    <i class="bi bi-ticket"></i> Support Tickets
                </a></li>
                <li><a href="statistics.php" class="<?php echo $currentPage == 'statistics.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart"></i> Statistiken
                </a></li>
                <li><a href="site_settings.php" class="<?php echo $currentPage == 'site_settings.php' ? 'active' : ''; ?>">
                    <i class="bi bi-gear"></i> Einstellungen
                </a></li>
                <li class="divider"></li>
                <li><a href="../index.php">
                    <i class="bi bi-house"></i> Zum Frontend
                </a></li>
                <li><a href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a></li>
            </ul>
        </div>
        <div class="content">
            <?php if ($debugMode): ?>
                <div class="debug-indicator">Debug Mode</div>
            <?php endif; ?> Administratorzugriff prüfen
require_once __DIR__ . '/../includes/Auth.php';
Auth::requireAdmin();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Settings.php';

// Debug-Modus prüfen
$debugMode = Settings::isDebugMode();

// Version der Website abrufen
$siteVersion = Settings::get('site_version', '1.0.0');

//