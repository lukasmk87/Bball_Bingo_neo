<?php
/**
 * Basketball Bingo - Fetch Teams API
 * Returns HTML options for teams dropdown based on selected club
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ApiResponse.php';

// Get club ID from request
$clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

// Validate input
$validation = ApiResponse::validateInput([
    'club_id' => 'required|integer'
], ['club_id' => $clubId]);

if ($validation !== true) {
    ApiResponse::error('Ungültige Club-ID');
}

try {
    $db = Database::getInstance();
    
    if ($clubId > 0) {
        // Fetch teams for the selected club
        $teams = $db->fetchAll(
            "SELECT id, name FROM teams WHERE club_id = ? AND blocked = 0 ORDER BY name",
            [$clubId]
        );
        
        // Output HTML options
        $options = '<option value="">-- Team auswählen --</option>';
        
        if (count($teams) > 0) {
            foreach ($teams as $team) {
                $options .= '<option value="' . $team['id'] . '">' . htmlspecialchars($team['name']) . '</option>';
            }
        } else {
            $options .= '<option value="" disabled>Keine Teams für diesen Verein verfügbar</option>';
        }
        
        // Return success with HTML options
        ApiResponse::success([
            'options' => $options,
            'teams_count' => count($teams)
        ]);
    } else {
        // Return default empty options
        ApiResponse::success([
            'options' => '<option value="">-- Team auswählen --</option>',
            'teams_count' => 0
        ]);
    }
} catch (Exception $e) {
    // Log error
    error_log("Error fetching teams: " . $e->getMessage());
    
    // Return error
    ApiResponse::error('Fehler beim Laden der Teams', 500);
}