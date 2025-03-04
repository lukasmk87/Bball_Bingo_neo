<?php
/**
 * Basketball Bingo - Homepage
 * Modern landing page with game introduction and quick access
 */

// Set page title - no need for page title in header
$pageTitle = "Basketball Bingo";

// Include header
include_once __DIR__ . '/includes/header.php';

// Get some game statistics
try {
    // Get global stats
    $globalStats = $pdo->query("SELECT games_played, total_bingos FROM global_stats WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    
    // Get upcoming games
    $upcomingGames = $pdo->query("
        SELECT g.id, g.time, g.opponent, t.name AS team_name
        FROM games g
        JOIN teams t ON g.team_id = t.id
        WHERE g.time >= NOW()
        ORDER BY g.time ASC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently log errors
    error_log("Error fetching stats for homepage: " . $e->getMessage());
    $globalStats = ['games_played' => 0, 'total_bingos' => 0];
    $upcomingGames = [];
}
?>

<div class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Basketball Bingo</h1>
        <p class="hero-tagline">Erlebe Basketball auf eine neue Art!</p>
        
        <div class="hero-cta">
            <?php if (isset($_SESSION['user'])): ?>
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

<style>
/* Additional page-specific styles */
.hero-section {
    background: linear-gradient(to right, var(--color-accent), #0c2461);
    color: white;
    padding: 4rem 2rem;
    text-align: center;
    border-radius: var(--border-radius-lg);
    margin-bottom: 2rem;
}

.hero-title {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: white;
}

.hero-tagline {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.features-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 3rem 0;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.upcoming-games-section {
    margin: 3rem 0;
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.game-card {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
}

.game-date {
    font-weight: bold;
    color: var(--color-accent);
}

.game-teams {
    margin: 1rem 0;
    font-size: 1.1rem;
}

.vs {
    margin: 0.5rem 0;
    font-size: 0.9rem;
    opacity: 0.7;
}

.how-to-play-section {
    margin: 4rem 0;
    text-align: center;
}

.steps-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.step {
    position: relative;
    padding: 2rem;
    background: white;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
}

.step-number {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: var(--color-ball);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.stats-section {
    margin: 3rem 0;
    text-align: center;
}

.stat-container {
    display: flex;
    justify-content: center;
    gap: 4rem;
}

.stat-value {
    font-size: 3rem;
    font-weight: bold;
    color: var(--color-accent);
}

.stat-label {
    font-size: 1.2rem;
    opacity: 0.8;
}

.community-section {
    margin: 4rem 0;
    text-align: center;
    padding: 2rem;
    background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
    border-radius: var(--border-radius-lg);
}

.cta-container {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
}

@media (max-width: 768px) {
    .hero-cta, .stat-container, .cta-container {
        flex-direction: column;
        align-items: center;
    }
    
    .hero-title {
        font-size: 2.2rem;
    }
    
    .hero-tagline {
        font-size: 1.2rem;
    }
    
    .stat-container {
        gap: 2rem;
    }
}
</style>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>