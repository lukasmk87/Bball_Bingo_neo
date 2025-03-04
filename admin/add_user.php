<?php
/**
 * Basketball Bingo - Add User
 */
require_once 'header.php';

$message = '';
$error = '';
$formData = [
    'username' => '',
    'email' => '',
    'is_admin' => false
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Save form data for re-populating the form
    $formData['username'] = $username;
    $formData['email'] = $email;
    $formData['is_admin'] = $is_admin;
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Benutzername ist erforderlich.";
    }
    
    if (empty($email)) {
        $errors[] = "E-Mail ist erforderlich.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ungültige E-Mail-Adresse.";
    }
    
    if (empty($password)) {
        $errors[] = "Passwort ist erforderlich.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Passwort muss mindestens 8 Zeichen lang sein.";
    }
    
    // Check if username or email already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Benutzername oder E-Mail ist bereits vergeben.";
        }
    } catch (PDOException $e) {
        $errors[] = "Fehler bei der Datenbankabfrage: " . $e->getMessage();
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashedPassword, $is_admin])) {
                $message = "Benutzer wurde erfolgreich erstellt.";
                // Reset form
                $formData = [
                    'username' => '',
                    'email' => '',
                    'is_admin' => false
                ];
            } else {
                $error = "Fehler beim Erstellen des Benutzers.";
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
    <h1>Benutzer hinzufügen</h1>
    <a href="users.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Zurück zur Liste
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Neuen Benutzer erstellen</h3>
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
        
        <form method="post" action="add_user.php" class="admin-form">
            <div class="form-group">
                <label for="username">Benutzername</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($formData['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small>Mindestens 8 Zeichen</small>
            </div>
            
            <div class="form-check">
                <input type="checkbox" id="is_admin" name="is_admin" class="form-check-input" <?php echo $formData['is_admin'] ? 'checked' : ''; ?>>
                <label for="is_admin" class="form-check-label">Administrator-Rechte gewähren</label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Benutzer erstellen</button>
                <a href="users.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<style>
.admin-form {
    max-width: 600px;
}

.form-check {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.form-check-input {
    margin-right: 0.5rem;
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