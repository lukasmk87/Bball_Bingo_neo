<?php
/**
 * Basketball Bingo - Header Template
 * Modern responsive header with improved navigation
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get settings from database
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/settings.php';

// Get theme settings
$bg_color = getSetting($pdo, 'site_bg_color', '#f4f4f4');
$text_color = getSetting($pdo, 'site_text_color', '#333333');
$header_bg_color = getSetting($pdo, 'site_header_bg_color', '#333333');
$link_color = getSetting($pdo, 'site_link_color', '#ffffff');

// Get site version
$site_version = getSetting($pdo, 'site_version', APP_VERSION);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);
$isAdmin = $isLoggedIn && isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] == 1;

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Basketball Bingo - Ein interaktives Bingo-Spiel mit Basketball-Elementen">
    <title><?php echo APP_NAME; ?> - <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Basketball Bingo'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($currentPage === 'bingo.php'): ?>
    <link rel="stylesheet" href="/assets/css/bingo.css">
    <?php endif; ?>
    
    <!-- Inline dynamic styles -->
    <style>
        :root {
            --color-bg: <?php echo $bg_color; ?>;
            --color-text: <?php echo $text_color; ?>;
            --color-header-bg: <?php echo $header_bg_color; ?>;
            --color-link: <?php echo $link_color; ?>;
        }
    </style>
</head>
<body class="<?php echo $currentPage === 'bingo.php' ? 'game-page' : ''; ?>">
    <header class="<?php echo $isLoggedIn ? 'loggedin' : ''; ?>">
        <div class="logo">
            <a href="index.php">
                <img src="/assets/images/logo.png" alt="<?php echo APP_NAME; ?> Logo">
                <span><?php echo APP_NAME; ?></span>
            </a>
        </div>
        
        <div class="hamburger" aria-label="Menü öffnen">&#9776;</div>
        
        <nav>
            <ul id="navLinks">
                <li><a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a></li>
                
                <?php if (!$isLoggedIn): ?>
                    <li><a href="login.php" class="<?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">Login</a></li>
                    <li><a href="register.php" class="<?php echo $currentPage === 'register.php' ? 'active' : ''; ?>">Registrieren</a></li>
                    <li><a href="guest.php">Als Gast spielen</a></li>
                <?php else: ?>
                    <li><a href="pages/game-selection.php" class="<?php echo $currentPage === 'game-selection.php' ? 'active' : ''; ?>">Spiel starten</a></li>
                    <li><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user']['username']); ?>)</a></li>
                <?php endif; ?>
                
                <li><a href="scoreboard.php" class="<?php echo $currentPage === 'scoreboard.php' ? 'active' : ''; ?>">Bestenliste</a></li>
                <li><a href="suggestions.php" class="<?php echo $currentPage === 'suggestions.php' ? 'active' : ''; ?>">Vorschläge</a></li>
                <li><a href="support.php" class="<?php echo $currentPage === 'support.php' ? 'active' : ''; ?>">Support</a></li>
                <li><a href="anleitung.php" class="<?php echo $currentPage === 'anleitung.php' ? 'active' : ''; ?>">Anleitung</a></li>
                
                <?php if ($isAdmin): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">Admin</a>
                        <ul class="dropdown-menu">
                            <li><a href="/admin/dashboard.php">Dashboard</a></li>
                            <li><a href="/admin/users.php">Benutzer</a></li>
                            <li><a href="/admin/clubs.php">Vereine</a></li>
                            <li><a href="/admin/teams.php">Teams</a></li>
                            <li><a href="/admin/games.php">Spiele</a></li>
                            <li><a href="/admin/bingofields.php">Bingo Felder</a></li>
                            <li><a href="/admin/site_settings.php">Einstellungen</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    
    <main class="container">
        <?php if (isset($pageTitle)): ?>
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <?php endif; ?>