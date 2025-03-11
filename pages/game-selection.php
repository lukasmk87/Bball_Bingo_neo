<?php
/**
 * Basketball Bingo - Game Selection
 * Enhanced game selection interface with improved UX
 */
session_start();

// Include required classes
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Settings.php';

// Set page title
$pageTitle = "Spielauswahl";

// Check if user is logged in
$isLoggedIn = Auth::isLoggedIn();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Auth::verifyCSRFToken($_POST['csrf_token'])) {
        $errorMessage = "Ungültige Anfrage. Bitte versuchen Sie es erneut.";
    } else if (!empty($_POST['club']) && !empty($_POST['team']) && !empty($_POST['game'])) {
        $_SESSION['club_id'] = $_POST['club'];
        $_SESSION['team_id'] = $_POST['team'];
        $_SESSION['game_id'] = $_POST['game'];
        
        // Redirect to the bingo game
        header("Location: bingo.php");
        exit;
    } else {
        $errorMessage = "Bitte alle Felder ausfüllen.";
    }
}

// Get clubs from database
try {
    $db = Database::getInstance();
    $clubs = $db->fetchAll("SELECT id, name FROM clubs WHERE blocked = 0 ORDER BY name");
    $clubsCount = count($clubs);
} catch (Exception $e) {
    error_log("Error fetching clubs: " . $e->getMessage());
    $clubs = [];
    $clubsCount = 0;
    $errorMessage = "Fehler beim Laden der Vereine.";
}

// Generate CSRF token
$csrfToken = Auth::generateCSRFToken();

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="game-selection">
    <?php if(isset($errorMessage)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>
    
    <?php if($clubsCount > 0): ?>
        <form method="post" action="game-selection.php" class="selection-form validate">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="selection-step">
                <h2>1. Verein auswählen</h2>
                <div class="form-group">
                    <select name="club" id="club" required class="form-control" onchange="fetchTeams(this.value)" data-error-message="Bitte wählen Sie einen Verein">
                        <option value="">-- Verein auswählen --</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="selection-step">
                <h2>2. Team auswählen</h2>
                <div class="form-group">
                    <select name="team" id="team" required class="form-control" onchange="fetchGames(this.value)" disabled data-error-message="Bitte wählen Sie ein Team">
                        <option value="">-- Team auswählen --</option>
                        <!-- Teams will be loaded dynamically -->
                    </select>
                    <div class="loading-indicator" id="team-loading" style="display: none;">Lade Teams...</div>
                </div>
            </div>
            
            <div class="selection-step">
                <h2>3. Spiel auswählen</h2>
                <div class="form-group">
                    <select name="game" id="game" required class="form-control" disabled data-error-message="Bitte wählen Sie ein Spiel">
                        <option value="">-- Spiel auswählen --</option>
                        <!-- Games will be loaded dynamically -->
                    </select>
                    <div class="loading-indicator" id="game-loading" style="display: none;">Lade Spiele...</div>
                </div>
                
                <div class="games-info-box" id="games-info">
                    <p>Nur aktuelle Spiele (innerhalb von 3 Stunden) werden angezeigt.</p>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <span class="btn-text">Spiel starten</span>
                    <span class="btn-icon">🏀</span>
                </button>
                
                <?php if(!$isLoggedIn): ?>
                    <div class="guest-play-notice">
                        <p>Du spielst als Gast. <a href="/login.php">Anmelden</a> um Ergebnisse zu speichern.</p>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🏀</div>
            <h2>Keine Vereine gefunden</h2>
            <p>Momentan sind keine Vereine verfügbar. Bitte versuche es später erneut.</p>
            
            <?php if(Auth::isAdmin()): ?>
                <a href="/admin/clubs.php" class="btn btn-secondary">Vereine verwalten</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
/**
 * Fetch teams for the selected club via AJAX
 */
function fetchTeams(clubId) {
    if (!clubId) {
        const teamSelect = document.getElementById('team');
        teamSelect.innerHTML = '<option value="">-- Team auswählen --</option>';
        teamSelect.disabled = true;
        
        const gameSelect = document.getElementById('game');
        gameSelect.innerHTML = '<option value="">-- Spiel auswählen --</option>';
        gameSelect.disabled = true;
        return;
    }
    
    const teamSelect = document.getElementById('team');
    const loadingIndicator = document.getElementById('team-loading');
    
    // Show loading indicator
    loadingIndicator.style.display = 'block';
    teamSelect.disabled = true;
    
    // Fetch teams using our centralized ajaxRequest function
    ajaxRequest('/api/fetch-teams.php?club_id=' + clubId)
        .then(response => {
            teamSelect.innerHTML = response.data.options;
            teamSelect.disabled = false;
            loadingIndicator.style.display = 'none';
            
            // Reset game selection
            const gameSelect = document.getElementById('game');
            gameSelect.innerHTML = '<option value="">-- Spiel auswählen --</option>';
            gameSelect.disabled = true;
        })
        .catch(error => {
            console.error('Error loading teams:', error);
            teamSelect.innerHTML = '<option value="">Fehler beim Laden der Teams</option>';
            loadingIndicator.style.display = 'none';
            showNotification('Fehler beim Laden der Teams', 'error');
        });
}

/**
 * Fetch games for the selected team via AJAX
 */
function fetchGames(teamId) {
    if (!teamId) {
        const gameSelect = document.getElementById('game');
        gameSelect.innerHTML = '<option value="">-- Spiel auswählen --</option>';
        gameSelect.disabled = true;
        return;
    }
    
    const gameSelect = document.getElementById('game');
    const loadingIndicator = document.getElementById('game-loading');
    const infoBox = document.getElementById('games-info');
    
    // Show loading indicator
    loadingIndicator.style.display = 'block';
    gameSelect.disabled = true;
    
    // Fetch games using our centralized ajaxRequest function
    ajaxRequest('/api/fetch-games.php?team_id=' + teamId)
        .then(response => {
            gameSelect.disabled = false;
            loadingIndicator.style.display = 'none';
            
            if (response.status === 'success') {
                gameSelect.innerHTML = response.data.options;
                
                // Update info box
                if (response.data.games_count === 0) {
                    infoBox.innerHTML = '<p class="notice">Keine aktuellen Spiele gefunden.</p>';
                } else {
                    infoBox.innerHTML = `<p>${response.data.games_count} Spiel(e) gefunden.</p>`;
                }
            } else {
                gameSelect.innerHTML = '<option value="">Fehler beim Laden der Spiele</option>';
                infoBox.innerHTML = '<p class="error">Es ist ein Fehler aufgetreten.</p>';
                showNotification(response.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading games:', error);
            gameSelect.innerHTML = '<option value="">Fehler beim Laden der Spiele</option>';
            loadingIndicator.style.display = 'none';
            infoBox.innerHTML = '<p class="error">Verbindungsfehler beim Laden der Spiele.</p>';
            showNotification('Verbindungsfehler beim Laden der Spiele', 'error');
        });
}
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>