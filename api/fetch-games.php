<?php
/**
 * Basketball Bingo - Fetch Games API
 * Returns JSON with HTML options for games dropdown based on selected team
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/settings.php';

// Headers
header('Content-Type: application/json; charset=utf-8');

// Get team ID from request
$teamId = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;

try {
    if ($teamId > 0) {
        // Fetch games for the selected team
        // Only show games that are not more than 3 hours in the past
        $stmt = $pdo->prepare("
            SELECT id, opponent, time, location, status
            FROM games 
            WHERE team_id = ? AND time >= DATE_SUB(NOW(), INTERVAL 3 HOUR) 
            ORDER BY time ASC
        ");
        $stmt->execute([$teamId]);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build HTML options
        $options = '<option value="">-- Spiel auswählen --</option>';
        
        if (count($games) > 0) {
            foreach ($games as $game) {
                // Format the date and time for display
                $gameTime = date('d.m.Y H:i', strtotime($game['time']));
                $statusText = '';
                
                if ($game['status'] === 'live') {
                    $statusText = ' (LIVE)';
                } elseif ($game['status'] === 'finished') {
                    $statusText = ' (Beendet)';
                }
                
                $locationText = !empty($game['location']) ? ' in ' . htmlspecialchars($game['location']) : '';
                
                $options .= '<option value="' . $game['id'] . '">Gegner: ' . 
                    htmlspecialchars($game['opponent']) . $statusText . ' - ' . 
                    $gameTime . $locationText . '</option>';
            }
        } else {
            $options .= '<option value="" disabled>Keine aktuellen Spiele verfügbar</option>';
        }
        
        // Return success with options
        echo json_encode([
            'status' => 'success',
            'options' => $options,
            'games_count' => count($games)
        ]);
    } else {
        // Return error
        echo json_encode([
            'status' => 'error',
            'message' => 'Ungültige Team-ID',
            'options' => '<option value="">-- Spiel auswählen --</option>'
        ]);
    }
} catch (PDOException $e) {
    // Log error
    error_log("Error fetching games: " . $e->getMessage());
    
    // Return error
    echo json_encode([
        'status' => 'error',
        'message' => 'Datenbankfehler beim Laden der Spiele',
        'options' => '<option value="">Fehler beim Laden der Spiele</option>'
    ]);
}