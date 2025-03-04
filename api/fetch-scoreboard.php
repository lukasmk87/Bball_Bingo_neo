<?php
/**
 * Basketball Bingo - Fetch Scoreboard API
 * Returns JSON with scoreboard entries for display
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/settings.php';

// Headers
header('Content-Type: application/json; charset=utf-8');

// Get optional filters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : null;

try {
    // Build query
    $query = "
        SELECT s.id, s.username, s.activated_fields, s.bingos, s.win_rate, s.field_rate,
               s.quarters_played, s.game_details, s.created_at,
               g.opponent, g.time, t.name AS team_name
        FROM scoreboard s
        LEFT JOIN games g ON s.game_id = g.id
        LEFT JOIN teams t ON g.team_id = t.id
        WHERE 1=1 
    ";
    
    $params = [];
    
    // Add filters if specified
    if ($userId) {
        $query .= " AND s.user_id = ?";
        $params[] = $userId;
    }
    
    if ($gameId) {
        $query .= " AND s.game_id = ?";
        $params[] = $gameId;
    }
    
    // Add sorting and limit
    $query .= " ORDER BY s.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format entries for display
    $formattedEntries = [];
    foreach ($entries as $entry) {
        // Parse game details if available
        $gameDetails = null;
        if (!empty($entry['game_details'])) {
            $gameDetails = json_decode($entry['game_details'], true);
        }
        
        // Format date
        $createdDate = date('d.m.Y H:i', strtotime($entry['created_at']));
        
        $formattedEntries[] = [
            'id' => $entry['id'],
            'username' => $entry['username'],
            'team' => $entry['team_name'] ?? ($gameDetails['team'] ?? 'Unbekannt'),
            'opponent' => $entry['opponent'] ?? ($gameDetails['opponent'] ?? 'Unbekannt'),
            'gameTime' => $entry['time'] ? date('d.m.Y H:i', strtotime($entry['time'])) : 
                          (isset($gameDetails['time']) ? date('d.m.Y H:i', strtotime($gameDetails['time'])) : 'Unbekannt'),
            'activatedFields' => $entry['activated_fields'],
            'bingos' => $entry['bingos'],
            'winRate' => $entry['win_rate'],
            'fieldRate' => $entry['field_rate'],
            'quartersPlayed' => $entry['quarters_played'],
            'createdAt' => $createdDate
        ];
    }
    
    // Return success with entries
    echo json_encode([
        'status' => 'success',
        'entries' => $formattedEntries,
        'count' => count($formattedEntries)
    ]);
    
} catch (PDOException $e) {
    // Log error
    error_log("Error fetching scoreboard: " . $e->getMessage());
    
    // Return error
    echo json_encode([
        'status' => 'error',
        'message' => 'Fehler beim Laden der Bestenliste',
        'entries' => []
    ]);
}