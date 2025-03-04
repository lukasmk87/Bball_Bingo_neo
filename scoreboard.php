<?php
/**
 * Basketball Bingo - Scoreboard/Leaderboard
 * Modern responsive design for displaying game results
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings.php';

// Set page title
$pageTitle = "Bestenliste";

// Get filter parameters
$timeFrame = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'all';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

// Build query
try {
    $query = "
        SELECT s.id, s.username, s.activated_fields, s.bingos, s.win_rate, s.field_rate,
               s.quarters_played, s.created_at, s.user_id,
               g.opponent, g.time, t.name AS team_name
        FROM scoreboard s
        LEFT JOIN games g ON s.game_id = g.id
        LEFT JOIN teams t ON g.team_id = t.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add time frame filter
    if ($timeFrame === 'today') {
        $query .= " AND DATE(s.created_at) = CURDATE()";
    } elseif ($timeFrame === 'week') {
        $query .= " AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($timeFrame === 'month') {
        $query .= " AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
    }
    
    // Add user filter
    if ($userId) {
        $query .= " AND s.user_id = ?";
        $params[] = $userId;
    }
    
    // Add game filter
    if ($gameId) {
        $query .= " AND s.game_id = ?";
        $params[] = $gameId;
    }
    
    // Add sorting
    if ($sortBy === 'bingos') {
        $query .= " ORDER BY s.bingos DESC, s.win_rate DESC";
    } elseif ($sortBy === 'rate') {
        $query .= " ORDER BY s.win_rate DESC, s.bingos DESC";
    } elseif ($sortBy === 'fields') {
        $query .= " ORDER BY s.activated_fields DESC";
    } else {
        $query .= " ORDER BY s.created_at DESC";
    }
    
    // Get total count for pagination
    $countQuery = str_replace("SELECT s.id, s.username", "SELECT COUNT(*) as total", $query);
    $countQuery = preg_replace('/ORDER BY.*$/', '', $countQuery);
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalResults / $perPage);
    
    // Ensure valid page number
    if ($page < 1) $page = 1;
    if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
    
    // Add pagination
    $query .= " LIMIT " . (($page - 1) * $perPage) . ", " . $perPage;
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching scoreboard: " . $e->getMessage());
    $entries = [];
    $totalResults = 0;
    $totalPages = 0;
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="scoreboard-container">
    <div class="scoreboard-filters">
        <form action="scoreboard.php" method="get" class="filter-form">
            <div class="filter-group">
                <label for="timeframe">Zeitraum:</label>
                <select name="timeframe" id="timeframe" onchange="this.form.submit()">
                    <option value="all" <?php echo $timeFrame === 'all' ? 'selected' : ''; ?>>Alle Zeiten</option>
                    <option value="today" <?php echo $timeFrame === 'today' ? 'selected' : ''; ?>>Heute</option>
                    <option value="week" <?php echo $timeFrame === 'week' ? 'selected' : ''; ?>>Diese Woche</option>
                    <option value="month" <?php echo $timeFrame === 'month' ? 'selected' : ''; ?>>Dieser Monat</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort">Sortieren nach:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="date" <?php echo $sortBy === 'date' ? 'selected' : ''; ?>>Neueste zuerst</option>
                    <option value="bingos" <?php echo $sortBy === 'bingos' ? 'selected' : ''; ?>>Meiste Bingos</option>
                    <option value="rate" <?php echo $sortBy === 'rate' ? 'selected' : ''; ?>>Beste Gewinnrate</option>
                    <option value="fields" <?php echo $sortBy === 'fields' ? 'selected' : ''; ?>>Meiste aktiv. Felder</option>
                </select>
            </div>
            
            <?php if ($userId): ?>
                <a href="scoreboard.php?timeframe=<?php echo $timeFrame; ?>&sort=<?php echo $sortBy; ?>" class="btn btn-sm">Filter zurücksetzen</a>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if (count($entries) > 0): ?>
        <div class="scoreboard-results">
            <div class="result-count">
                <?php echo $totalResults; ?> Ergebnisse gefunden
            </div>
            
            <div class="scoreboard-table-container">
                <table class="scoreboard-table">
                    <thead>
                        <tr>
                            <th>Spieler</th>
                            <th>Team</th>
                            <th>Gegner</th>
                            <th>Datum</th>
                            <th class="text-center">Aktivierte Felder</th>
                            <th class="text-center">Bingos</th>
                            <th class="text-center">Gewinnrate</th>
                            <th class="text-center">Feldrate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td>
                                    <a href="scoreboard.php?user_id=<?php echo $entry['user_id']; ?>&timeframe=<?php echo $timeFrame; ?>&sort=<?php echo $sortBy; ?>">
                                        <?php echo htmlspecialchars($entry['username']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($entry['team_name'] ?? 'Unbekannt'); ?></td>
                                <td><?php echo htmlspecialchars($entry['opponent'] ?? 'Unbekannt'); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($entry['created_at'])); ?></td>
                                <td class="text-center"><?php echo $entry['activated_fields']; ?></td>
                                <td class="text-center"><?php echo $entry['bingos']; ?></td>
                                <td class="text-center"><?php echo number_format($entry['win_rate'], 1); ?>%</td>
                                <td class="text-center"><?php echo number_format($entry['field_rate'], 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="scoreboard.php?page=<?php echo $page - 1; ?>&timeframe=<?php echo $timeFrame; ?>&sort=<?php echo $sortBy; ?><?php echo $userId ? '&user_id=' . $userId : ''; ?>" class="page-link">&laquo; Zurück</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Seite <?php echo $page; ?> von <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="scoreboard.php?page=<?php echo $page + 1; ?>&timeframe=<?php echo $timeFrame; ?>&sort=<?php echo $sortBy; ?><?php echo $userId ? '&user_id=' . $userId : ''; ?>" class="page-link">Weiter &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🏆</div>
            <h2>Keine Ergebnisse gefunden</h2>
            <p>Es wurden keine Einträge gefunden, die den Filterkriterien entsprechen.</p>
            <?php if ($userId || $gameId): ?>
                <a href="scoreboard.php" class="btn btn-secondary">Filter zurücksetzen</a>
            <?php else: ?>
                <a href="pages/game-selection.php" class="btn btn-primary">Jetzt spielen</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.scoreboard-container {
    max-width: 100%;
    overflow-x: auto;
}

.scoreboard-filters {
    margin-bottom: 1.5rem;
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.result-count {
    margin-bottom: 1rem;
    font-weight: 500;
}

.scoreboard-table-container {
    overflow-x: auto;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-md);
}

.scoreboard-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.scoreboard-table th,
.scoreboard-table td {
    padding: 0.75rem 1rem;
    border: 1px solid #e0e0e0;
}

.scoreboard-table th {
    background-color: var(--color-accent);
    color: white;
    font-weight: 600;
    text-align: left;
}

.scoreboard-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.scoreboard-table tbody tr:hover {
    background-color: #f1f1f1;
}

.text-center {
    text-align: center;
}

.pagination {
    margin-top: 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
}

.page-link {
    padding: 0.5rem 1rem;
    background: white;
    border-radius: var(--border-radius-sm);
    box-shadow: var(--shadow-sm);
}

.page-info {
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    background: white;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .scoreboard-table th,
    .scoreboard-table td {
        padding: 0.5rem;
    }
    
    .scoreboard-table {
        font-size: 0.9rem;
    }
}
</style>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>