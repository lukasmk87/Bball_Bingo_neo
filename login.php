<?php
/**
 * Basketball Bingo - Login Page
 * Modern responsive login form with improved security
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';

// Set page title
$pageTitle = "Login";

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Process login form
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST["login"]);
    $password = $_POST["password"];
    
    if (empty($login) || empty($password)) {
        $error = "Bitte alle Felder ausfüllen.";
    } else {
        try {
            // Find user by username or email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND blocked = 0");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'is_admin' => $user['is_admin']
                ];
                
                // Update last_login timestamp
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect to home or intended page
                $redirect = isset($_SESSION['redirect_after_login']) ? 
                           $_SESSION['redirect_after_login'] : 'index.php';
                unset($_SESSION['redirect_after_login']);
                
                header("Location: $redirect");
                exit;
            } else {
                $error = "Ungültige Anmeldedaten. Bitte versuchen Sie es erneut.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="auth-form-container">
        <h2>Anmelden</h2>
        
        <form method="post" action="login.php" class="auth-form">
            <div class="form-group">
                <label for="login">Benutzername oder E-Mail</label>
                <input type="text" id="login" name="login" class="form-control" required 
                       value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Angemeldet bleiben</label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Anmelden</button>
            </div>
        </form>
        
        <div class="auth-links">
            <a href="forgot_password.php">Passwort vergessen?</a>
            <span class="separator">|</span>
            <a href="register.php">Neues Konto erstellen</a>
        </div>
        
        <div class="auth-guest">
            <p>Oder spiele direkt ohne Anmeldung:</p>
            <a href="guest.php" class="btn btn-secondary btn-block">Als Gast spielen</a>
        </div>
    </div>
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

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input {
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

.separator {
    margin: 0 0.5rem;
    color: #ccc;
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