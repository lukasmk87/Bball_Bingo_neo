<?php
session_start();
include 'db.php';
include 'header.php';

// Check if a game was selected
if (!isset($_SESSION['game_id'])) {
    echo "<div class='container error-message'>";
    echo "<p>Kein Spiel ausgewählt. Bitte wähle zuerst ein Team und Spiel aus.</p>";
    echo "<p><a href='game.php' class='btn btn-primary'>Zur Spielauswahl</a></p>";
    echo "</div>";
    include 'footer.php';
    exit;
}

// Get game details
try {
    $stmt = $pdo->prepare("SELECT g.*, t.name AS team_name 
                          FROM games g 
                          JOIN teams t ON g.team_id = t.id 
                          WHERE g.id = ?");
    $stmt->execute([$_SESSION['game_id']]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        echo "<div class='container error-message'>";
        echo "<p>Spiel nicht gefunden.</p>";
        echo "<p><a href='game.php' class='btn btn-primary'>Zur Spielauswahl</a></p>";
        echo "</div>";
        include 'footer.php';
        exit;
    }

    // Get bingo fields
    $teamId = $game['team_id'];
    
    // Get a mix of team-specific and standard fields
    $stmt = $pdo->prepare("
        SELECT id, description 
        FROM bingo_fields 
        WHERE (team_id = ? OR is_standard = 1) AND approved = 1 
        ORDER BY RAND() 
        LIMIT 25
    ");
    $stmt->execute([$teamId]);
    $bingoFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If we don't have enough fields, get more standard fields
    if (count($bingoFields) < 25) {
        $needed = 25 - count($bingoFields);
        $stmt = $pdo->prepare("
            SELECT id, description 
            FROM bingo_fields 
            WHERE is_standard = 1 AND approved = 1 
            ORDER BY RAND() 
            LIMIT ?
        ");
        $stmt->execute([$needed]);
        $moreFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $bingoFields = array_merge($bingoFields, $moreFields);
    }
    
    // Ensure exactly 25 fields
    $bingoFields = array_slice($bingoFields, 0, 25);
    
} catch (PDOException $e) {
    echo "<div class='container error-message'>";
    echo "<p>Datenbankfehler: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    include 'footer.php';
    exit;
}
?>

<div class="container">
    <h1>Bingo Spiel - Viertel: <span id="quarter">1</span></h1>
    
    <div class="game-info">
        <div class="team-info">
            <span class="team home"><?php echo htmlspecialchars($game['team_name']); ?></span>
            <span class="vs">vs</span>
            <span class="team away"><?php echo htmlspecialchars($game['opponent']); ?></span>
        </div>
        <div class="game-date"><?php echo date('d.m.Y H:i', strtotime($game['time'])); ?></div>
    </div>
    
    <div id="game-stats" class="game-stats">
        <div class="stat">
            <span class="stat-label">Aktivierte Felder:</span>
            <span class="stat-value">0</span>
        </div>
        <div class="stat">
            <span class="stat-label">Bingos:</span>
            <span class="stat-value">0</span>
        </div>
    </div>
    
    <div id="bingo-board" class="bingo-board">
        <?php foreach ($bingoFields as $field): ?>
            <div class="bingo-cell" data-field-id="<?php echo $field['id']; ?>">
                <?php echo htmlspecialchars($field['description']); ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="actions">
        <button id="next-quarter" class="btn btn-primary">Nächstes Viertel</button>
        <button id="fullscreen-btn" class="btn btn-secondary">Vollbild</button>
    </div>
</div>

<link rel="stylesheet" href="assets/css/bingo.css">
<script src="assets/js/bingo.js"></script>

<?php include 'footer.php'; ?>