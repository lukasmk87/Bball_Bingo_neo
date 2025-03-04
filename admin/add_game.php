<?php
/**
 * Basketball Bingo - Add Game
 */
require_once 'header.php';

// Fetch teams for dropdown
try {
    $stmtTeams = $pdo->query("
        SELECT t.id, t.name, c.name AS club_name 
        FROM teams t 
        LEFT JOIN clubs c ON t.club_id = c.id 
        WHERE t.blocked = 0 
        ORDER BY t.name
    ");
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teams: " . $e->getMessage());
    $teams = [];
}

$message = '';
$error = '';
$formData = [
    'team_id' => '',
    'opponent' => '',
    'time' => '',
    'location' => '',
    'status' => 'scheduled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $teamId = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
    $opponent = trim($_POST['opponent']);
    $time = trim($_POST['time']);
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'scheduled';
    
    // Format datetime properly
    $time = str_replace('T', ' ', $time);
    
    // Save form data for re-populating the form
    $formData = [
        'team_id' => $teamId,
        'opponent' => $opponent,
        'time' => $time,
        'location' => $location,
        'status' => $status
    ];
    
    // Validate inputs
    $errors = [];
    
    if (empty($teamId)) {
        $errors[] = "Team ist erforderlich.";
    }
    
    if (empty($opponent)) {
        $errors[] = "Gegner ist erforderlich.";
    }
    
    if (empty($time)) {
        $errors[] = "Datum und Uhrzeit sind erforderlich.";
    }
    
    // If no errors, create the game
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO games (team_id, opponent, time, location, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$teamId, $opponent, $time, $location, $status])) {
                $gameId = $pdo->lastInsertId();
                $message = "Spiel wurde erfolgreich erstellt.";
                // Reset form
                $formData = [
                    'team_id' => '',
                    'opponent' => '',
                    'time' => '',
                    'location' => '',
                    'status' => 'scheduled'
                ];
            } else {
                $error = "Fehler beim Erstellen des Spiels.";
            }
        } catch (PDOException $e) {
            $error = "Datenbankfehler: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Format time for datetime-local input
$formattedTime = !empty($formData['time']) ? str_replace(' ', 'T', $formData['time']) : '';
?>

<div class="d-flex justify-between align-center">
    <h1>Spiel hinzufügen</h1>
    <a href="games.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Zurück zur Liste
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Neues Spiel erstellen</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="add_game.php" class="admin-form">
            <div class="form-group">
                <label for="team_id">Team*</label>
                <select id="team_id" name="team_id" class="form-control" required>
                    <option value="">-- Team auswählen --</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>" <?php echo $formData['team_id'] == $team['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($team['name']); ?>
                            <?php if ($team['club_name']): ?>
                                (<?php echo htmlspecialchars($team['club_name']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($teams)): ?>
                    <small>Keine Teams verfügbar. <a href="add_team.php">Team hinzufügen</a></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="opponent">Gegner*</label>
                <input type="text" id="opponent" name="opponent" class="form-control" value="<?php echo htmlspecialchars($formData['opponent']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="time">Datum und Uhrzeit*</label>
                <input type="datetime-local" id="time" name="time" class="form-control" value="<?php echo htmlspecialchars($formattedTime); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="location">Ort (optional)</label>
                <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($formData['location']); ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="scheduled" <?php echo $formData['status'] == 'scheduled' ? 'selected' : ''; ?>>Geplant</option>
                    <option value="live" <?php echo $formData['status'] == 'live' ? 'selected' : ''; ?>>Live</option>
                    <option value="finished" <?php echo $formData['status'] == 'finished' ? 'selected' : ''; ?>>Beendet</option>
                    <option value="cancelled" <?php echo $formData['status'] == 'cancelled' ? 'selected' : ''; ?>>Abgesagt</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Spiel erstellen</button>
                <a href="games.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<style>
.admin-form {
    max-width: 600px;
}

.form-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
}

.alert {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: var(--border-radius-md);
}

.alert-success {
    background-color: rgba(46, 125, 50, 0.1);
    color: var(--color-success);
    border: 1px solid rgba(46, 125, 50, 0.2);
}

.alert-danger {
    background-color: rgba(198, 40, 40, 0.1);
    color: var(--color-danger);
    border: 1px solid rgba(198, 40, 40, 0.2);
}

small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.8rem;
    color: var(--color-muted);
}
</style>

<?php require_once 'footer.php'; ?>