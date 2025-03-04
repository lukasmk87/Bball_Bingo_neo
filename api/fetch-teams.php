<?php
/**
 * Basketball Bingo - Fetch Teams API
 * Returns HTML options for teams dropdown based on selected club
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/settings.php';

// Headers
header('Content-Type: text/html; charset=utf-8');

// Get club ID from request
$clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

try {
    if ($clubId > 0) {
        // Fetch teams for the selected club
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM teams 
            WHERE club_id = ? AND blocked = 0 
            ORDER BY name
        ");
        $stmt->execute([$clubId]);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Output HTML options
        echo '<option value="">-- Team auswählen --</option>';
        
        if (count($teams) > 0) {
            foreach ($teams as $team) {
                echo '<option value="' . $team['id'] . '">' . htmlspecialchars($team['name']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Keine Teams für diesen Verein verfügbar</option>';
        }
    } else {
        echo '<option value="">-- Team auswählen --</option>';
    }
} catch (PDOException $e) {
    // Log error
    error_log("Error fetching teams: " . $e->getMessage());
    
    // Return error message
    echo '<option value="">Fehler beim Laden der Teams</option>';
}