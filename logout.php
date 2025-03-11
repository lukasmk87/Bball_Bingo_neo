<?php
/**
 * Basketball Bingo - Logout
 * Beendet die Benutzersession und leitet zur Startseite weiter
 */
session_start();
require_once __DIR__ . '/includes/Auth.php';

// Benutzer abmelden
Auth::logout();

// Zur Startseite weiterleiten
header('Location: index.php');
exit;