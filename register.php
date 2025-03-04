<?php
/**
 * Basketball Bingo - Registration Page
 * Modern responsive registration form with validation
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';

// Set page title
$pageTitle = "Registrierung";

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Process registration form
$message = "";
$error = "";
$formData = [
    'username' => '',
    'email' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];
    
    // Save valid form data for re-populating the form
    $formData['username'] = $username;
    $formData['email'] = $email;
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Benutzername ist erforderlich.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Benutzername muss zwischen 3 und 50 Zeichen lang sein.";
    }
    
    if (empty($email)) {
        $errors[] = "E-Mail-Adresse ist erforderlich.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ungültige E-Mail-Adresse.";
    }
    
    if (empty($password)) {
        $errors[] = "Passwort ist erforderlich.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Passwort muss mindestens 8 Zeichen lang sein.";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwörter stimmen nicht überein.";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Benutzername oder E-Mail-Adresse bereits vergeben.";
            }
        } catch (PDOException $e) {
            error_log("Database error during registration: " . $e->getMessage());
            $errors[] = "Ein Datenbankfehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    }
    
    // If there are no errors, create the user
    if (empty($errors)) {
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $result = $stmt->execute([$username, $email, $hashedPassword]);
            
            if ($result) {
                $message = "Registrierung erfolgreich! Sie können sich jetzt anmelden.";
                $formData['username'] = '';
                $formData['email'] = '';
            } else {
                $error = "Fehler bei der Registrierung. Bitte versuchen Sie es erneut.";
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
            <p><a href="login.php">Zum Login</a></p>
        </div>
    <?php else: ?>
    
    <div class="auth-form-container">
        <h2>Neues Konto erstellen</h2>
        
        <form method="post" action="register.php" class="auth-form">
            <div class="form-group">
                <label for="username">Benutzername</label>
                <input type="text" id="username" name="username" class="form-control" required
                       value="<?php echo htmlspecialchars($formData['username']); ?>">
                <small>3-50 Zeichen, nur Buchstaben, Zahlen und Unterstriche.</small>
            </div>
            
            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?php echo htmlspecialchars($formData['email']); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small>Mindestens 8 Zeichen</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Passwort wiederholen</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="form-group terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">Ich akzeptiere die <a href="datenschutz.php" target="_blank">Datenschutzbestimmungen</a></label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Registrieren</button>
            </div>
        </form>
        
        <div class="auth-links">
            <span>Bereits registriert?</span>
            <a href="login.php">Anmelden</a>
        </div>
        
        <div class="auth-guest">
            <p>Oder spiele direkt ohne Anmeldung:</p>
            <a href="guest.php" class="btn btn-secondary btn-block">Als Gast spielen</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.auth-container {
    max-width: 500px;
    margin: 2rem auto;
}

.auth-form-container {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.auth-form-container h2 {
    text-align: center;
    margin-bottom: 1.5rem;
}

.auth-form .form-group {
    margin-bottom: 1.25rem;
}

.auth-form small {
    display: block;
    margin-top: 0.25rem;
    color: #6c757d;
    font-size: 0.85rem;
}

.terms {
    display: flex;
    align-items: center;
}

.terms input {
    margin-right: 0.5rem;
}

.btn-block {
    width: 100%;
}

.auth-links {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 0.9rem;
}

.auth-guest {
    margin-top: 2rem;
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.alert {
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border-radius: var(--border-radius-sm);
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