<?php
/**
 * Basketball Bingo - Homepage
 * Modern landing page with game introduction and quick access
 */

// Set page title - no need for page title in header
$pageTitle = "Basketball Bingo";

// Include required classes
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/includes/Auth.php';

// Get some game statistics
try {
    $db = Database::getInstance();
    
    // Get global stats
    $globalStats = $db->fetch("SELECT games_played, total_bingos FROM global_stats WHERE id = 1");
    
    // Get upcoming games
    $upcomingGames = $db->fetchAll("
        SELECT g.id, g.time, g.opponent, t.name AS team_name
        FROM games g
        JOIN teams t ON g.team_id = t.id
        WHERE g.time >= NOW()
        ORDER BY g.time ASC
        LIMIT 3
    ");
} catch (Exception $e) {
    // Silently log errors
    error_log("Error fetching stats for homepage: " . $e->getMessage());
    $globalStats = ['games_played' => 0, 'total_bingos' => 0];
    $upcomingGames = [];
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Basketball Bingo</h1>
        <p class="hero-tagline">Erlebe Basketball auf eine neue Art!</p>
        
        <div class="hero-cta">
            <?php if (Auth::isLoggedIn()): ?>
                <a href="/pages/game-selection.php" class="btn btn-primary btn-large">Spiel starten</a>
            <?php else: ?>
                <a href="/pages/game-selection.php" class="btn btn-primary">Als Gast spielen</a>
                <a href="/login.php" class="btn btn-secondary">Anmelden</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="features-section">
    <div class="feature-card">
        <div class="feature-icon">🏀</div>
        <h3>Basketball + Bingo</h3>
        <p>Kombiniere den Spaß von Bingo mit der Spannung des Basketballs in einem interaktiven Spiel.</p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">🎮</div>
        <h3>Einfach zu spielen</h3>
        <p>Wähle ein Spiel, markiere Ereignisse während des Spiels und erziele Bingos für Punkte.</p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">🏆</div>
        <h3>Bestenliste</h3>
        <p>Vergleiche deine Leistung mit anderen Spielern und erreiche den Highscore.</p>
    </div>
</div>

<?php if (count($upcomingGames) > 0): ?>
<div class="upcoming-games-section">
    <h2>Anstehende Spiele</h2>
    <div class="games-grid">
        <?php foreach ($upcomingGames as $game): ?>
            <div class="game-card">
                <div class="game-date"><?php echo date('d.m.Y', strtotime($game['time'])); ?></div>
                <div class="game-time"><?php echo date('H:i', strtotime($game['time'])); ?> Uhr</div>
                <div class="game-teams">
                    <div class="team home"><?php echo htmlspecialchars($game['team_name']); ?></div>
                    <div class="vs">vs</div>
                    <div class="team away"><?php echo htmlspecialchars($game['opponent']); ?></div>
                </div>
                <a href="/pages/game-selection.php" class="btn btn-secondary btn-sm">Bingo erstellen</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="how-to-play-section">
    <h2>So wird gespielt</h2>
    
    <div class="steps-container">
        <div class="step">
            <div class="step-number">1</div>
            <h3>Wähle dein Spiel</h3>
            <p>Suche dir einen Verein, ein Team und ein aktuelles Spiel aus.</p>
        </div>
        
        <div class="step">
            <div class="step-number">2</div>
            <h3>Markiere Ereignisse</h3>
            <p>Klicke auf ein Feld, wenn das beschriebene Ereignis während des Spiels eintritt.</p>
        </div>
        
        <div class="step">
            <div class="step-number">3</div>
            <h3>Erziele Bingos</h3>
            <p>Vervollständige eine Reihe, Spalte oder Diagonale, um ein Bingo zu erzielen!</p>
        </div>
        
        <div class="step">
            <div class="step-number">4</div>
            <h3>Sammle Punkte</h3>
            <p>Mit jedem Viertel wird das Spielfeld neu generiert - sammle möglichst viele Bingos!</p>
        </div>
    </div>
    
    <div class="cta-container">
        <a href="/anleitung.php" class="btn btn-secondary">Ausführliche Anleitung</a>
    </div>
</div>

<div class="stats-section">
    <div class="stat-container">
        <div class="stat-item">
            <div class="stat-value"><?php echo number_format($globalStats['games_played']); ?></div>
            <div class="stat-label">Gespielte Spiele</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-value"><?php echo number_format($globalStats['total_bingos']); ?></div>
            <div class="stat-label">Erzielte Bingos</div>
        </div>
    </div>
</div>

<div class="community-section">
    <h2>Teil der Community werden</h2>
    <p>Registriere dich, um deine Ergebnisse zu speichern, in der Bestenliste zu erscheinen und Vorschläge für neue Bingo-Felder einzureichen!</p>
    
    <div class="cta-container">
        <a href="/register.php" class="btn btn-primary">Jetzt registrieren</a>
        <a href="https://crossoverpodcast.de" class="btn btn-outline" target="_blank">Zum CrossOver Podcast</a>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>