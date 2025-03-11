<?php
/**
 * Basketball Bingo - Settings Manager
 * Einfache Kompatibilitätsschicht, die die Settings-Klasse nutzt
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/Settings.php';

/**
 * Legacy Funktion zum Abrufen einer Einstellung
 * In neuen Dateien sollte Settings::get() verwendet werden
 * 
 * @param PDO $pdo - Datenbankverbindung (wird nicht mehr verwendet)
 * @param string $name - Name der Einstellung
 * @param mixed $default - Standardwert falls Einstellung nicht gefunden
 * @return mixed - Einstellungswert oder Standardwert falls nicht gefunden
 */
function getSetting($pdo, string $name, $default = null) {
    return Settings::get($name, $default);
}

/**
 * Legacy Funktion zum Setzen einer Einstellung
 * In neuen Dateien sollte Settings::set() verwendet werden
 * 
 * @param PDO $pdo - Datenbankverbindung (wird nicht mehr verwendet)
 * @param string $name - Name der Einstellung
 * @param mixed $value - Einstellungswert
 * @return bool - Erfolgsstatus
 */
function setSetting($pdo, string $name, $value) {
    return Settings::set($name, $value);
}

/**
 * Legacy Funktion zum Abrufen aller Einstellungen
 * In neuen Dateien sollte Settings::getAll() verwendet werden
 * 
 * @param PDO $pdo - Datenbankverbindung (wird nicht mehr verwendet)
 * @return array - Alle Einstellungen
 */
function getAllSettings($pdo) {
    return Settings::getAll();
}

/**
 * Legacy Funktion zur Prüfung des Debug-Modus
 * In neuen Dateien sollte Settings::isDebugMode() verwendet werden
 * 
 * @param PDO $pdo - Datenbankverbindung (wird nicht mehr verwendet)
 * @return bool - Ob der Debug-Modus aktiviert ist
 */
function isDebugMode($pdo) {
    return Settings::isDebugMode();
}

/**
 * Legacy Funktion zum Abrufen der SMTP-Konfiguration
 * In neuen Dateien sollte Settings::getSmtpConfig() verwendet werden
 * 
 * @param PDO $pdo - Datenbankverbindung (wird nicht mehr verwendet)
 * @return array - SMTP-Konfiguration
 */
function getSmtpConfig($pdo) {
    return Settings::getSmtpConfig();
}