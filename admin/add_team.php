<?php
/**
 * Basketball Bingo - Add Team
 */
require_once 'header.php';

// Fetch clubs for dropdown
try {
    $stmtClubs = $pdo->query("SELECT id, name FROM clubs WHERE blocked = 0 ORDER BY name");
    $clubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching clubs: " . $e->getMessage());
    $clubs = [];
}

$message = '';
$error = '';
$formData = [
    'name' => '',
    'club_id' => '',
    'logo' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $clubId = !empty($_POST['club_id']) ? intval($_POST['club_id']) : null;
    $logo = !empty($_POST['logo']) ? trim($_POST['logo']) : null;
    
    // Save form data for re-populating the form
    $formData['name'] = $name;
    $formData['club_id'] = $clubId;
    $formData['logo'] = $logo;
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Teamname ist erforderlich.";
    }
    
    // Check if team name already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ein Team mit diesem Namen existiert bereits.";
        }
    } catch (PDOException $e) {
        $errors[] = "Fehler bei der Datenbankabfrage: " . $e->getMessage();
    }
    
    // If no errors, create the team
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO teams (name, club_id, logo) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $clubId, $logo])) {
                $teamId = $pdo->lastInsertId();
                $message = "Team wurde erfolgreich erstellt.";
                // Reset form
                $formData = [
                    'name' => '',
                    'club_id' => '',
                    'logo' => ''
                ];
            } else {
                $error = "Fehler beim Erstellen des Teams.";
            }
        } catch (PDOException $e) {
            $error = "Datenbankfehler: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<div class="d-flex justify-between align-center">
    <h1>Team hinzufügen</h1>
    <a href="teams.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Zurück zur Liste
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Neues Team erstellen</h3>
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
        
        <form method="post" action="add_team.php" class="admin-form">
            <div class="form-group">
                <label for="name">Teamname*</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="club_id">Verein</label>
                <select id="club_id" name="club_id" class="form-control">
                    <option value="">-- Kein Verein auswählen --</option>
                    <?php foreach ($clubs as $club): ?>
                        <option value="<?php echo $club['id']; ?>" <?php echo $formData['club_id'] == $club['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($club['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($clubs)): ?>
                    <small>Keine Vereine verfügbar. <a href="add_club.php">Verein hinzufügen</a></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="logo">Logo URL (optional)</label>
                <input type="text" id="logo" name="logo" class="form-control" value="<?php echo htmlspecialchars($formData['logo']); ?>">
                <small>URL zum Teamlogo (optional)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Team erstellen</button>
                <a href="teams.php" class="btn btn-secondary">Abbrechen</a>
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