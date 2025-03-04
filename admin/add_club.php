<?php
/**
 * Basketball Bingo - Add Club
 */
require_once 'header.php';

$message = '';
$error = '';
$formData = [
    'name' => '',
    'website' => '',
    'logo' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $website = !empty($_POST['website']) ? trim($_POST['website']) : null;
    $logo = !empty($_POST['logo']) ? trim($_POST['logo']) : null;
    
    // Save form data for re-populating the form
    $formData['name'] = $name;
    $formData['website'] = $website;
    $formData['logo'] = $logo;
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Vereinsname ist erforderlich.";
    }
    
    // Check if club name already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clubs WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ein Verein mit diesem Namen existiert bereits.";
        }
    } catch (PDOException $e) {
        $errors[] = "Fehler bei der Datenbankabfrage: " . $e->getMessage();
    }
    
    // If no errors, create the club
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO clubs (name, website, logo) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $website, $logo])) {
                $clubId = $pdo->lastInsertId();
                $message = "Verein wurde erfolgreich erstellt.";
                // Reset form
                $formData = [
                    'name' => '',
                    'website' => '',
                    'logo' => ''
                ];
            } else {
                $error = "Fehler beim Erstellen des Vereins.";
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
    <h1>Verein hinzufügen</h1>
    <a href="clubs.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Zurück zur Liste
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Neuen Verein erstellen</h3>
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
        
        <form method="post" action="add_club.php" class="admin-form">
            <div class="form-group">
                <label for="name">Vereinsname*</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="website">Website (optional)</label>
                <input type="url" id="website" name="website" class="form-control" value="<?php echo htmlspecialchars($formData['website']); ?>" placeholder="https://www.example.com">
            </div>
            
            <div class="form-group">
                <label for="logo">Logo URL (optional)</label>
                <input type="text" id="logo" name="logo" class="form-control" value="<?php echo htmlspecialchars($formData['logo']); ?>">
                <small>URL zum Vereinslogo (optional)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Verein erstellen</button>
                <a href="clubs.php" class="btn btn-secondary">Abbrechen</a>
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