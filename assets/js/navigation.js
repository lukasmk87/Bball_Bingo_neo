<?php
/**
 * Basketball Bingo - Suggestions Form
 * Modern responsive form for submitting suggestions
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';

// Set page title
$pageTitle = "Vorschläge";

// Get user info if logged in
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$userId = $user ? $user['id'] : null;

// Fetch clubs and teams for dropdown menus
try {
    // Get clubs
    $stmtClubs = $pdo->query("SELECT id, name FROM clubs WHERE blocked = 0 ORDER BY name");
    $clubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
    
    // Get teams
    $stmtTeams = $pdo->query("SELECT id, name FROM teams WHERE blocked = 0 ORDER BY name");
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching clubs/teams: " . $e->getMessage());
    $clubs = [];
    $teams = [];
}

// Process form submission
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $type = $_POST['type'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($type)) {
        $errors[] = "Bitte wählen Sie eine Kategorie.";
    }
    
    // Type-specific validation
    if ($type === 'club') {
        $name = trim($_POST['club_name']);
        if (empty($name)) {
            $errors[] = "Vereinsname ist erforderlich.";
        }
    } elseif ($type === 'team') {
        $name = trim($_POST['team_name']);
        $clubId = isset($_POST['club_id']) ? $_POST['club_id'] : '';
        if (empty($name)) {
            $errors[] = "Teamname ist erforderlich.";
        }
        if (empty($clubId)) {
            $errors[] = "Bitte wählen Sie einen Verein.";
        }
    } elseif ($type === 'field') {
        $name = trim($_POST['field_description']);
        $teamId = isset($_POST['field_team_id']) ? $_POST['field_team_id'] : null;
        if (empty($name)) {
            $errors[] = "Bingo-Feld Beschreibung ist erforderlich.";
        }
    } elseif ($type === 'game') {
        $name = trim($_POST['opponent']);
        $teamId = isset($_POST['selected_team']) ? $_POST['selected_team'] : '';
        $gameTime = isset($_POST['time']) ? $_POST['time'] : '';
        if (empty($name)) {
            $errors[] = "Gegner ist erforderlich.";
        }
        if (empty($teamId)) {
            $errors[] = "Bitte wählen Sie ein Team.";
        }
        if (empty($gameTime)) {
            $errors[] = "Datum und Uhrzeit sind erforderlich.";
        }
    }
    
    // If there are no errors, save the suggestion
    if (empty($errors)) {
        try {
            // Prepare additional data as JSON if needed
            $additionalData = [];
            
            if ($type === 'team') {
                $additionalData['club_id'] = $clubId;
            } elseif ($type === 'field') {
                $additionalData['team_id'] = $teamId;
                $additionalData['is_standard'] = isset($_POST['is_standard']) ? 1 : 0;
            } elseif ($type === 'game') {
                $additionalData['team_id'] = $teamId;
                $additionalData['time'] = $gameTime;
            }
            
            // Insert suggestion into database
            $stmt = $pdo->prepare("
                INSERT INTO suggestions (user_id, type, name, description, team_id, approved, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $suggestionTeamId = null;
            if ($type === 'field' || $type === 'game') {
                $suggestionTeamId = $teamId;
            }
            
            $result = $stmt->execute([
                $userId, 
                $type, 
                $name, 
                !empty($additionalData) ? json_encode($additionalData) : $description,
                $suggestionTeamId
            ]);
            
            if ($result) {
                $message = "Vielen Dank für Ihren Vorschlag! Er wird von unserem Team geprüft.";
            } else {
                $error = "Fehler beim Speichern des Vorschlags. Bitte versuchen Sie es erneut.";
            }
        } catch (PDOException $e) {
            error_log("Suggestion error: " . $e->getMessage());
            $error = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="suggestions-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="suggestions-intro">
        <h2>Ihre Vorschläge</h2>
        <p>Helfen Sie uns, Basketball Bingo zu verbessern! Reichen Sie Vorschläge für neue Vereine, Teams, Bingo-Felder oder Spiele ein.</p>
    </div>
    
    <div class="suggestions-form-container">
        <form method="post" action="suggestions.php" id="suggestionForm" class="suggestions-form">
            <div class="form-group">
                <label for="type">Kategorie:</label>
                <select name="type" id="type" class="form-control" onchange="toggleFields()" required>
                    <option value="club">Verein</option>
                    <option value="team">Team</option>
                    <option value="field">Bingo Feld</option>
                    <option value="game">Spiel</option>
                </select>
            </div>
            
            <!-- Felder für Vereins-Vorschlag -->
            <div id="clubFields" class="dynamic-fields">
                <div class="form-group">
                    <label for="club_name">Vereinsname:</label>
                    <input type="text" name="club_name" id="club_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="club_description">Beschreibung (optional):</label>
                    <textarea name="description" id="club_description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            
            <!-- Felder für Team-Vorschlag -->
            <div id="teamFields" class="dynamic-fields" style="display:none;">
                <div class="form-group">
                    <label for="team_name">Teamname:</label>
                    <input type="text" name="team_name" id="team_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="club_id">Verein auswählen:</label>
                    <select name="club_id" id="club_id" class="form-control">
                        <option value="">-- Verein auswählen --</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Felder für Bingo-Feld-Vorschlag -->
            <div id="fieldFields" class="dynamic-fields" style="display:none;">
                <div class="form-group">
                    <label for="field_description">Bingo-Feld Beschreibung:</label>
                    <input type="text" name="field_description" id="field_description" class="form-control">
                    <small>Beschreiben Sie ein Ereignis, das während eines Basketballspiels eintreten kann.</small>
                </div>
                <div class="form-group">
                    <label for="field_team_id">Team (optional):</label>
                    <select name="field_team_id" id="field_team_id" class="form-control">
                        <option value="">Kein Team (allgemeines Feld)</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Wählen Sie ein Team, falls dieses Feld nur für bestimmte Teams gelten soll.</small>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_standard" id="is_standard" value="1">
                    <label for="is_standard">Als Standardfeld vorschlagen</label>
                    <small>Standardfelder können in allen Spielen erscheinen.</small>
                </div>
            </div>
            
            <!-- Felder für Spiel-Vorschlag -->
            <div id="gameFields" class="dynamic-fields" style="display:none;">
                <div class="form-group">
                    <label for="selected_team">Team auswählen:</label>
                    <select name="selected_team" id="selected_team" class="form-control">
                        <option value="">-- Team auswählen --</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="opponent">Gegner:</label>
                    <input type="text" name="opponent" id="opponent" class="form-control">
                </div>
                <div class="form-group">
                    <label for="time">Datum und Uhrzeit:</label>
                    <input type="datetime-local" name="time" id="time" class="form-control">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Vorschlag senden</button>
            </div>
        </form>
    </div>
    
    <?php if (!$user): ?>
        <div class="guest-notice">
            <p><strong>Hinweis:</strong> Als registrierter Benutzer können Sie Ihre eingereichten Vorschläge verfolgen und darüber benachrichtigt werden, wenn sie genehmigt wurden.</p>
            <div class="guest-actions">
                <a href="login.php" class="btn btn-secondary">Anmelden</a>
                <a href="register.php" class="btn btn-outline">Registrieren</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Funktion zum Umschalten der Formularfelder basierend auf der ausgewählten Kategorie
function toggleFields() {
    var type = document.getElementById('type').value;
    
    // Alle Bereiche zunächst ausblenden
    document.getElementById('clubFields').style.display = 'none';
    document.getElementById('teamFields').style.display = 'none';
    document.getElementById('fieldFields').style.display = 'none';
    document.getElementById('gameFields').style.display = 'none';
    
    // Required-Attribute zurücksetzen
    document.getElementById('club_name').required = false;
    document.getElementById('team_name').required = false;
    document.getElementById('club_id').required = false;
    document.getElementById('field_description').required = false;
    document.getElementById('selected_team').required = false;
    document.getElementById('opponent').required = false;
    document.getElementById('time').required = false;
    
    if (type === 'club') {
        document.getElementById('clubFields').style.display = 'block';
        document.getElementById('club_name').required = true;
    } else if (type === 'team') {
        document.getElementById('teamFields').style.display = 'block';
        document.getElementById('team_name').required = true;
        document.getElementById('club_id').required = true;
    } else if (type === 'field') {
        document.getElementById('fieldFields').style.display = 'block';
        document.getElementById('field_description').required = true;
    } else if (type === 'game') {
        document.getElementById('gameFields').style.display = 'block';
        document.getElementById('selected_team').required = true;
        document.getElementById('opponent').required = true;
        document.getElementById('time').required = true;
    }
}

// Initialize the form
document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
});
</script>

<style>
.suggestions-container {
    max-width: 800px;
    margin: 0 auto;
}

.suggestions-intro {
    margin-bottom: 2rem;
    text-align: center;
}

.suggestions-form-container {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: var(--border-radius-sm);
}

textarea.form-control {
    resize: vertical;
}

.checkbox-group {
    display: flex;
    align-items: center;
}

.checkbox-group input {
    margin-right: 0.5rem;
}

.checkbox-group label {
    margin-bottom: 0;
}

.dynamic-fields {
    margin-top: 1.5rem;
    border-top: 1px solid #eee;
    padding-top: 1.5rem;
}

.form-actions {
    margin-top: 2rem;
}

.guest-notice {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: var(--border-radius-md);
    border-left: 4px solid var(--color-accent);
}

.guest-actions {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--color-accent);
    color: var(--color-accent);
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-sm);
    transition: all 0.2s ease;
}

.btn-outline:hover {
    background: var(--color-accent);
    color: white;
}

small {
    display: block;
    margin-top: 0.25rem;
    color: #6c757d;
    font-size: 0.85rem;
}

.alert {
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: var(--border-radius-md);
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
</style>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>