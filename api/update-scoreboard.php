<?php
/**
 * Basketball Bingo - Update Scoreboard API
 * Records game results to the scoreboard
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/settings.php';

// Headers
header('Content-Type: application/json; charset=utf-8');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get user info
$userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
$username = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : 'Gast';
$gameId = isset($_SESSION['game_id']) ? $_SESSION['game_id'] : null;

// Get POST data
$activeFields = isset($_POST['active_fields']) ? (int)$_POST['active_fields'] : 0;
$bingos = isset($_POST['bingos']) ? (int)$_POST['bingos'] : 0;
$winRate = isset($_POST['win_rate']) ? (float)$_POST['win_rate'] : 0;
$fieldRate = isset($_POST['field_rate']) ? (float)$_POST['field_rate'] : 0;
$bingoLog = isset($_POST['bingo_log']) ? $_POST['bingo_log'] : null;
$quartersPlayed = isset($_POST['quarters_played']) ? (int)$_POST['quarters_played'] : 4;

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get game details
    $gameDetails = null;
    if ($gameId) {
        $stmt = $pdo->prepare("
            SELECT g.id, g.opponent, g.time, t.name AS team_name
            FROM games g
            LEFT JOIN teams t ON g.team_id = t.id
            WHERE g.id = ?
        ");
        $stmt->execute([$gameId]);
        $gameDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Prepare game details as JSON
    $gameDetailsJson = $gameDetails ? json_encode([
        'team' => $gameDetails['team_name'],
        'opponent' => $gameDetails['opponent'],
        'time' => $gameDetails['time']
    ]) : null;
    
    // Prepare event log as JSON
    $eventLogJson = $bingoLog ? $bingoLog : null;
    
    // Insert into scoreboard
    $stmt = $pdo->prepare("
        INSERT INTO scoreboard 
        (user_id, username, game_id, activated_fields, bingos, win_rate, field_rate, quarters_played, game_details, event_log) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $username,
        $gameId,
        $activeFields,
        $bingos,
        $winRate,
        $fieldRate,
        $quartersPlayed,
        $gameDetailsJson,
        $eventLogJson
    ]);
    
    $scoreboardId = $pdo->lastInsertId();
    
    // Process bingo log entries
    if ($bingoLog && $gameId) {
        $bingoLogData = json_decode($bingoLog, true);
        
        if (is_array($bingoLogData)) {
            foreach ($bingoLogData as $entry) {
                // Check if this is a valid bingo entry
                if (isset($entry['quarter']) && isset($entry['combination'])) {
                    $quarter = (int)$entry['quarter'];
                    $winningFields = json_encode($entry['combination']);
                    $winningType = null;
                    
                    // Determine winning type (row, column, diagonal)
                    $firstIndex = $entry['combination'][0];
                    $lastIndex = $entry['combination'][count($entry['combination']) - 1];
                    
                    if ($lastIndex - $firstIndex === 4) {
                        $winningType = 'row';
                    } elseif ($lastIndex - $firstIndex === 20) {
                        $winningType = 'column';
                    } else {
                        $winningType = 'diagonal';
                    }
                    
                    // Insert into bingo_log
                    $stmt = $pdo->prepare("
                        INSERT INTO bingo_log 
                        (user_id, game_id, quarter, winning_fields, winning_type) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $userId,
                        $gameId,
                        $quarter,
                        $winningFields,
                        $winningType
                    ]);
                }
            }
        }
    }
    
    // Update global stats
    $stmt = $pdo->prepare("
        UPDATE global_stats 
        SET games_played = games_played + 1, 
            total_bingos = total_bingos + ? 
        WHERE id = 1
    ");
    $stmt->execute([$bingos]);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Spielergebnis erfolgreich gespeichert',
        'scoreboard_id' => $scoreboardId
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("Error updating scoreboard: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Fehler beim Speichern des Spielergebnisses'
    ]);
}