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