<?php
/**
 * Basketball Bingo - Fetch Bingo Fields API
 * Returns HTML for 25 random bingo fields, with preference for team-specific fields
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/settings.php';

// Headers
header('Content-Type: text/html; charset=utf-8');

// Get game ID from request
$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;

try {
    // Get team ID from the game
    $teamId = null;
    if ($gameId) {
        $stmt = $pdo->prepare("SELECT team_id FROM games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($game) {
            $teamId = $game['team_id'];
        }
    }
    
    // Fetch fields: prioritize team-specific fields, then fill with standard fields
    $fields = [];
    
    // First try to get team-specific fields
    if ($teamId) {
        $stmt = $pdo->prepare("
            SELECT id, description, category 
            FROM bingo_fields 
            WHERE team_id = ? AND approved = 1 
            ORDER BY RAND() 
            LIMIT 15
        ");
        $stmt->execute([$teamId]);
        $teamFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields = array_merge($fields, $teamFields);
    }
    
    // Get remaining fields from standard pool
    $neededFields = 25 - count($fields);
    if ($neededFields > 0) {
        $excludeIds = array_map(function($field) { return $field['id']; }, $fields);
        $excludePlaceholders = count($excludeIds) > 0 ? implode(',', array_fill(0, count($excludeIds), '?')) : '0';
        
        $sql = "
            SELECT id, description, category 
            FROM bingo_fields 
            WHERE is_standard = 1 AND approved = 1 ";
        
        if (count($excludeIds) > 0) {
            $sql .= "AND id NOT IN ($excludePlaceholders) ";
        }
        
        $sql .= "ORDER BY RAND() LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        
        $params = $excludeIds;
        $params[] = $neededFields;
        
        $stmt->execute($params);
        $standardFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields = array_merge($fields, $standardFields);
    }
    
    // Shuffle the fields to mix team-specific and standard fields
    shuffle($fields);
    
    // Output HTML
    $output = '';
    foreach ($fields as $field) {
        // Create CSS classes based on category
        $categoryClass = '';
        if (isset($field['category'])) {
            $categoryClass = 'category-' . strtolower($field['category']);
        }
        
        $output .= '<div class="bingo-cell ' . $categoryClass . '" data-field-id="' . $field['id'] . '">';
        $output .= '<span>' . htmlspecialchars($field['description']) . '</span>';
        $output .= '</div>';
    }
    
    echo $output;
    
} catch (PDOException $e) {
    // Log error
    error_log("Error fetching bingo fields: " . $e->getMessage());
    
    // Return error message
    echo '<div class="error-message">Fehler beim Laden der Bingo-Felder. Bitte versuche es erneut.</div>';
}