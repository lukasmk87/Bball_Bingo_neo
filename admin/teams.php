<?php
/**
 * Basketball Bingo - Team Management
 */
require_once 'header.php';

// Handle pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$clubId = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;
$whereClause = [];
$params = [];

if (!empty($search)) {
    $whereClause[] = "t.name LIKE ?";
    $params[] = "%$search%";
}

if ($clubId > 0) {
    $whereClause[] = "t.club_id = ?";
    $params[] = $clubId;
}

$whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM teams t $whereSQL");
$countStmt->execute($params);
$totalTeams = $countStmt->fetchColumn();
$totalPages = ceil($totalTeams / $perPage);

// Get teams with club name
$stmt = $pdo->prepare("
    SELECT t.*, c.name AS club_name, 
           (SELECT COUNT(*) FROM games WHERE team_id = t.id) AS game_count,
           (SELECT COUNT(*) FROM bingo_fields WHERE team_id = t.id) AS field_count
    FROM teams t 
    LEFT JOIN clubs c ON t.club_id = c.id
    $whereSQL 
    ORDER BY t.name ASC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get clubs for filter dropdown
$clubs = $pdo->query("SELECT id, name FROM clubs WHERE blocked = 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-between align-center">
    <h1>Teamverwaltung</h1>
    <a href="add_team.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Team hinzufügen
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-between align-center">
            <h3>Teamliste</h3>
            <form action="teams.php" method="get" class="search-form">
                <div class="filter-controls">
                    <select name="club_id" class="form-select">
                        <option value="0">Alle Vereine</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?php echo $club['id']; ?>" <?php echo $clubId == $club['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($club['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="search-group">
                        <input type="text" name="search" placeholder="Teamname suchen..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-sm"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($teams) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teamname</th>
                            <th>Verein</th>
                            <th>Spiele</th>
                            <th>Bingo-Felder</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td><?php echo $team['id']; ?></td>
                                <td><?php echo htmlspecialchars($team['name']); ?></td>
                                <td>
                                    <?php if ($team['club_id']): ?>
                                        <a href="clubs.php?search=<?php echo urlencode($team['club_name']); ?>">
                                            <?php echo htmlspecialchars($team['club_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Kein Verein</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $team['game_count']; ?></td>
                                <td><?php echo $team['field_count']; ?></td>
                                <td>
                                    <?php if ($team['blocked']): ?>
                                        <span class="badge badge-danger">Gesperrt</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Aktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($team['blocked']): ?>
                                            <a href="unblock_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-success btn-icon" title="Entsperren" onclick="return confirm('Team entsperren?')">
                                                <i class="bi bi-unlock"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="block_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-warning btn-icon" title="Sperren" onclick="return confirm('Team sperren?')">
                                                <i class="bi bi-lock"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="games.php?team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-info btn-icon" title="Spiele anzeigen">
                                            <i class="bi bi-calendar-event"></i>
                                        </a>
                                        <a href="bingofields.php?team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-secondary btn-icon" title="Bingo-Felder anzeigen">
                                            <i class="bi bi-grid-3x3"></i>
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
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $clubId > 0 ? '&club_id=' . $clubId : ''; ?>" class="btn btn-sm">&laquo; Zurück</a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">Seite <?php echo $page; ?> von <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $clubId > 0 ? '&club_id=' . $clubId : ''; ?>" class="btn btn-sm">Weiter &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <p>Keine Teams gefunden</p>
                <?php if (!empty($search) || $clubId > 0): ?>
                    <a href="teams.php" class="btn btn-sm">Filter zurücksetzen</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-form {
    width: 100%;
}

.filter-controls {
    display: flex;
    gap: 0.5rem;
}

.form-select {
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: var(--border-radius-sm);
    background-color: white;
}

.search-group {
    display: flex;
    flex: 1;
    border: 1px solid #ced4da;
    border-radius: var(--border-radius-sm);
    overflow: hidden;
}

.search-group input {
    flex: 1;
    border: none;
    padding: 0.5rem;
    outline: none;
}

.search-group button {
    background: none;
    border: none;
    padding: 0.5rem;
    color: var(--color-primary);
    cursor: pointer;
}

.text-muted {
    color: #6c757d;
}

@media (max-width: 768px) {
    .filter-controls {
        flex-direction: column;
    }
}
</style>

<?php require_once 'footer.php'; ?>