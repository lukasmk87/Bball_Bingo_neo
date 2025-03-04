<?php
/**
 * Basketball Bingo - Support Ticket Form
 * Modern responsive support ticket submission form
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';

// Set page title
$pageTitle = "Support";

// Get user info if logged in
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$userId = $user ? $user['id'] : null;

// Process form submission
$message = "";
$error = "";
$formData = [
    'subject' => '',
    'message' => '',
    'email' => $user ? $user['email'] : ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $subject = trim($_POST['subject']);
    $messageText = trim($_POST['message']);
    $email = isset($_POST['email']) ? trim($_POST['email']) : ($user ? $user['email'] : '');
    
    // Save form data for re-populating the form
    $formData['subject'] = $subject;
    $formData['message'] = $messageText;
    $formData['email'] = $email;
    
    // Validate inputs
    $errors = [];
    
    if (empty($subject)) {
        $errors[] = "Betreff ist erforderlich.";
    }
    
    if (empty($messageText)) {
        $errors[] = "Nachricht ist erforderlich.";
    }
    
    if (!$user && empty($email)) {
        $errors[] = "E-Mail-Adresse ist erforderlich für Gäste.";
    } elseif (!$user && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ungültige E-Mail-Adresse.";
    }
    
    // If there are no errors, create the ticket
    if (empty($errors)) {
        try {
            // Insert ticket into database
            $stmt = $pdo->prepare("
                INSERT INTO tickets (user_id, subject, message, status, priority, created_at) 
                VALUES (?, ?, ?, 'open', 'medium', NOW())
            ");
            $result = $stmt->execute([$userId, $subject, $messageText]);
            
            if ($result) {
                $ticketId = $pdo->lastInsertId();
                
                // If guest, store email in a custom field or additional table
                if (!$user && !empty($email)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO ticket_responses (ticket_id, message, is_admin, created_at) 
                        VALUES (?, ?, 0, NOW())
                    ");
                    $stmt->execute([$ticketId, "Kontakt-E-Mail: " . $email]);
                }
                
                $message = "Vielen Dank für Ihre Anfrage! Ihr Ticket wurde erfolgreich erstellt.";
                $formData['subject'] = '';
                $formData['message'] = '';
            } else {
                $error = "Fehler beim Erstellen des Tickets. Bitte versuchen Sie es erneut.";
            }
        } catch (PDOException $e) {
            error_log("Support ticket error: " . $e->getMessage());
            $error = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="support-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php else: ?>
    
    <div class="support-intro">
        <h2>Wie können wir helfen?</h2>
        <p>Haben Sie Fragen, Probleme oder Feedback zum Basketball Bingo? Wir helfen Ihnen gerne weiter. Füllen Sie einfach das untenstehende Formular aus.</p>
    </div>
    
    <div class="support-content">
        <div class="support-form-container">
            <form method="post" action="support.php" class="support-form">
                <?php if (!$user): ?>
                    <div class="form-group">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($formData['email']); ?>">
                        <small>Wir benötigen Ihre E-Mail-Adresse, um Sie zu kontaktieren.</small>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="subject">Betreff</label>
                    <input type="text" id="subject" name="subject" class="form-control" required
                           value="<?php echo htmlspecialchars($formData['subject']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="message">Nachricht</label>
                    <textarea id="message" name="message" class="form-control" rows="6" required><?php echo htmlspecialchars($formData['message']); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Ticket absenden</button>
                </div>
            </form>
        </div>
        
        <div class="support-faq">
            <h3>Häufig gestellte Fragen</h3>
            
            <div class="faq-item">
                <h4>Wie spiele ich Basketball Bingo?</h4>
                <p>Wählen Sie einen Verein, ein Team und ein Spiel aus. Während des Spiels klicken Sie auf die Felder, wenn das entsprechende Ereignis eintritt. Eine vollständige Anleitung finden Sie <a href="anleitung.php">hier</a>.</p>
            </div>
            
            <div class="faq-item">
                <h4>Muss ich mich registrieren, um zu spielen?</h4>
                <p>Nein, Sie können auch als Gast spielen. Allerdings werden Ihre Ergebnisse nicht dauerhaft gespeichert.</p>
            </div>
            
            <div class="faq-item">
                <h4>Wie werden Bingos berechnet?</h4>
                <p>Ein Bingo wird erzielt, wenn Sie eine komplette Reihe, Spalte oder Diagonale aktiviert haben.</p>
            </div>
            
            <div class="faq-item">
                <h4>Kann ich mein eigenes Bingo-Feld erstellen?</h4>
                <p>Ja, registrierte Benutzer können Vorschläge für neue Bingo-Felder einreichen. Diese werden nach Überprüfung freigeschaltet.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.support-container {
    max-width: 1000px;
    margin: 0 auto;
}

.support-intro {
    margin-bottom: 2rem;
    text-align: center;
}

.support-content {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 2rem;
}

.support-form-container {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
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

.form-actions {
    margin-top: 2rem;
}

.support-faq {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.support-faq h3 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--color-accent);
}

.faq-item {
    margin-bottom: 1.5rem;
}

.faq-item h4 {
    margin-bottom: 0.5rem;
    color: var(--color-accent);
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

small {
    display: block;
    margin-top: 0.25rem;
    color: #6c757d;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .support-content {
        grid-template-columns: 1fr;
    }
    
    .support-faq {
        order: -1;
        margin-bottom: 2rem;
    }
}
</style>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>