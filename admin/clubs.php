<?php
/**
 * Basketball Bingo - Club Management
 */
require_once 'header.php';

// Handle pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE name LIKE ?";
    $params = ["%$search%"];
}

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM clubs $whereClause");
$countStmt->execute($params);
$totalClubs = $countStmt->fetchColumn();
$totalPages = ceil($totalClubs / $perPage);

// Get clubs
$stmt = $pdo->prepare("
    SELECT c.*, (SELECT COUNT(*) FROM teams WHERE club_id = c.id) AS team_count
    FROM clubs c 
    $whereClause 
    ORDER BY c.name ASC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-between align-center">
    <h1>Vereinsverwaltung</h1>
    <a href="add_club.php" class="btn btn-primary">
        <i class="bi bi-building-add"></i> Verein hinzufügen
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-between align-center">
            <h3>Vereinsliste</h3>
            <form action="clubs.php" method="get" class="search-form">
                <div class="search-group">
                    <input type="text" name="search" placeholder="Vereinsname suchen..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-sm"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($clubs) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vereinsname</th>
                            <th>Teams</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clubs as $club): ?>
                            <tr>
                                <td><?php echo $club['id']; ?></td>
                                <td><?php echo htmlspecialchars($club['name']); ?></td>
                                <td><?php echo $club['team_count']; ?></td>
                                <td>
                                    <?php if ($club['blocked']): ?>
                                        <span class="badge badge-danger">Gesperrt</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Aktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_club.php?id=<?php echo $club['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($club['blocked']): ?>
                                            <a href="unblock_club.php?id=<?php echo $club['id']; ?>" class="btn btn-sm btn-success btn-icon" title="Entsperren" onclick="return confirm('Verein entsperren?')">
                                                <i class="bi bi-unlock"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="block_club.php?id=<?php echo $club['id']; ?>" class="btn btn-sm btn-warning btn-icon" title="Sperren" onclick="return confirm('Verein sperren?')">
                                                <i class="bi bi-lock"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="teams.php?club_id=<?php echo $club['id']; ?>" class="btn btn-sm btn-info btn-icon" title="Teams anzeigen">
                                            <i class="bi bi-people"></i>
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
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">&laquo; Zurück</a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">Seite <?php echo $page; ?> von <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">Weiter &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-building"></i>
                <p>Keine Vereine gefunden</p>
                <?php if (!empty($search)): ?>
                    <a href="clubs.php" class="btn btn-sm">Suche zurücksetzen</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-form {
    display: flex;
    align-items: center;
}

.search-group {
    display: flex;
    border: 1px solid #ced4da;
    border-radius: var(--border-radius-sm);
    overflow: hidden;
}

.search-group input {
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

.table-responsive {
    overflow-x: auto;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 1.5rem;
    gap: 1rem;
}

.pagination-info {
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 2rem 0;
}

.empty-state i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 1rem;
}
</style>

<?php require_once 'footer.php'; ?>