<?php
/**
 * Basketball Bingo - Database Connection
 * Initialisiert die Datenbankverbindung über die Database Klasse
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/Database.php';

/**
 * Legacy Funktion für Abwärtskompatibilität
 * In neuen Dateien sollte Database::getInstance() verwendet werden
 * 
 * @return PDO Die Datenbankverbindung
 * @throws PDOException Bei Verbindungsfehlern
 */
function getDbConnection() {
    static $pdo = null;
    
    // Bestehende Verbindung zurückgeben
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // Neue Instance über Database Klasse holen
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        return $pdo;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new PDOException("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    }
}

// Initialisiere die Datenbankverbindung
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    // Fehler entsprechend dem Kontext behandeln
    if (php_sapi_name() === 'cli') {
        // Kommandozeile
        echo "Datenbankfehler: " . $e->getMessage() . PHP_EOL;
        exit(1);
    } else {
        // Webanfrage
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
            // Admin-Bereich - detaillierten Fehler anzeigen
            die("Datenbankfehler: " . htmlspecialchars($e->getMessage()));
        } else {
            // Öffentlicher Bereich - freundliche Nachricht anzeigen
            die("Es ist ein Datenbankfehler aufgetreten. Bitte versuchen Sie es später erneut.");
        }
    }
}