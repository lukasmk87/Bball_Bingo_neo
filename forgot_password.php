<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';

// Set page title
$pageTitle = "Passwort vergessen";

// Check for Composer and Symfony Mailer
$hasMailer = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $hasMailer = true;
}

// Process form submission
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Bitte geben Sie Ihre E-Mail-Adresse ein.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Ungültige E-Mail-Adresse.";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                if ($hasMailer) {
                    // Email functionality would go here
                    // This is currently disabled until Composer is set up
                } else {
                    // Temporary solution - generate a new password directly
                    $newPassword = bin2hex(random_bytes(4)); // 8 characters
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $user['id']]);
                    
                    $message = "Temporäres Passwort für {$user['username']}: <strong>{$newPassword}</strong><br>Bitte ändern Sie es nach dem Login.";
                }
            } else {
                // Don't reveal that the email doesn't exist
                $message = "Falls ein Konto mit dieser E-Mail existiert, wurden Anweisungen zum Zurücksetzen des Passworts gesendet.";
            }
        } catch (PDOException $e) {
            error_log("Database error during password reset: " . $e->getMessage());
            $error = "Ein Datenbankfehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php else: ?>
    <div class="auth-form-container">
        <h2>Passwort vergessen</h2>
        <p class="auth-intro">Geben Sie Ihre E-Mail-Adresse ein, um ein neues Passwort zu erhalten.</p>
        
        <form method="post" action="forgot_password.php" class="auth-form">
            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Passwort zurücksetzen</button>
            </div>
        </form>
        
        <div class="auth-links">
            <a href="login.php">Zurück zum Login</a>
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
/* Rest of CSS remains the same */
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>