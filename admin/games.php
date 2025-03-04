<?php
/**
 * Basketball Bingo - Game Management
 */
require_once 'header.php';

// Handle pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$teamId = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$whereClause = [];
$params = [];

if (!empty($search)) {
    $whereClause[] = "g.opponent LIKE ?";
    $params[] = "%$search%";
}

if ($teamId > 0) {
    $whereClause[] = "g.team_id = ?";
    $params[] = $teamId;
}

if (!empty($dateFrom)) {
    $whereClause[] = "g.time >= ?";
    $params[] = $dateFrom . " 00:00:00";
}

if (!empty($dateTo)) {
    $whereClause[] = "g.time <= ?";
    $params[] = $dateTo . " 23:59:59";
}

$whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM games g $whereSQL");
$countStmt->execute($params);
$totalGames = $countStmt->fetchColumn();
$totalPages = ceil($totalGames / $perPage);

// Get games with team name
$stmt = $pdo->prepare("
    SELECT g.*, t.name AS team_name, c.name AS club_name,
           (SELECT COUNT(*) FROM scoreboard WHERE game_id = g.id) AS play_count
    FROM games g 
    LEFT JOIN teams t ON g.team_id = t.id
    LEFT JOIN clubs c ON t.club_id = c.id
    $whereSQL 
    ORDER BY g.time DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get teams for filter dropdown
$teams = $pdo->query("SELECT t.id, t.name, c.name AS club_name 
                      FROM teams t 
                      LEFT JOIN clubs c ON t.club_id = c.id 
                      WHERE t.blocked = 0 
                      ORDER BY t.name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-between align-center">
    <h1>Spielverwaltung</h1>
    <a href="add_game.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Spiel hinzufügen
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-between align-center">
            <h3>Spielliste</h3>
            <div class="filter-toggle-btn" onclick="toggleFilterPanel()">
                <i class="bi bi-funnel"></i> Filter
            </div>
        </div>
    </div>
    
    <div id="filterPanel" class="filter-panel">
        <form action="games.php" method="get" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="team_id">Team</label>
                    <select name="team_id" id="team_id" class="form-select">
                        <option value="0">Alle Teams</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>" <?php echo $teamId == $team['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['name']); ?>
                                <?php if ($team['club_name']): ?>
                                    (<?php echo htmlspecialchars($team['club_name']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">Datum von</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">Datum bis</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="search">Gegner</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Gegner suchen..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filtern</button>
                <a href="games.php" class="btn btn-secondary">Zurücksetzen</a>
                <button type="button" class="btn btn-link" onclick="toggleFilterPanel()">Schließen</button>
            </div>
        </form>
    </div>
    
    <div class="card-body">
        <?php if (count($games) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Team</th>
                            <th>Gegner</th>
                            <th>Datum & Zeit</th>
                            <th>Ort</th>
                            <th>Ergebnis</th>
                            <th>Spiele</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $game): ?>
                            <tr>
                                <td><?php echo $game['id']; ?></td>
                                <td>
                                    <?php if ($game['team_id']): ?>
                                        <a href="teams.php?search=<?php echo urlencode($game['team_name']); ?>">
                                            <?php echo htmlspecialchars($game['team_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Kein Team</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($game['opponent']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($game['time'])); ?></td>
                                <td><?php echo htmlspecialchars($game['location'] ?? '-'); ?></td>
                                <td>
                                    <?php if (isset($game['score_home']) && isset($game['score_away'])): ?>
                                        <?php echo $game['score_home']; ?> : <?php echo $game['score_away']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $game['play_count']; ?>
                                </td>
                                <td>
                                    <?php
                                    $gameTime = strtotime($game['time']);
                                    $now = time();
                                    $status = isset($game['status']) ? $game['status'] : 'scheduled';
                                    
                                    if ($status === 'cancelled') {
                                        echo '<span class="badge badge-danger">Abgesagt</span>';
                                    } elseif ($status === 'finished') {
                                        echo '<span class="badge badge-success">Beendet</span>';
                                    } elseif ($status === 'live') {
                                        echo '<span class="badge badge-warning">Live</span>';
                                    } elseif ($gameTime < $now) {
                                        echo '<span class="badge badge-secondary">Vorbei</span>';
                                    } else {
                                        echo '<span class="badge badge-info">Geplant</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_game.php?id=<?php echo $game['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="scoreboard.php?game_id=<?php echo $game['id']; ?>" class="btn btn-sm btn-success btn-icon" title="Ergebnisse anzeigen">
                                            <i class="bi bi-trophy"></i>
                                        </a>
                                        <a href="delete_game.php?id=<?php echo $game['id']; ?>" class="btn btn-sm btn-danger btn-icon" title="Löschen" onclick="return confirm('Spiel wirklich löschen? Dies kann nicht rückgängig gemacht werden.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $teamId > 0 ? '&team_id=' . $teamId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . urlencode($dateTo) : ''; ?>" class="btn btn-sm">&laquo; Zurück</a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">Seite <?php echo $page; ?> von <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $teamId > 0 ? '&team_id=' . $teamId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . urlencode($dateTo) : ''; ?>" class="btn btn-sm">Weiter &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>Keine Spiele gefunden</p>
                <?php if (!empty($search) || $teamId > 0 || !empty($dateFrom) || !empty($dateTo)): ?>
                    <a href="games.php" class="btn btn-sm">Filter zurücksetzen</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFilterPanel() {
    const panel = document.getElementById('filterPanel');
    panel.classList.toggle('show');
}
</script>

<style>
.filter-toggle-btn {
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-sm);
    background-color: #f8f9fa;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-panel {
    padding: 1rem;
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: none;
}

.filter-panel.show {
    display: block;
}

.filter-form {
    width: 100%;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.btn-link {
    background: none;
    border: none;
    color: var(--color-primary);
    text-decoration: underline;
    cursor: pointer;
}

.text-muted {
    color: #6c757d;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'footer.php'; ?>