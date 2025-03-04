<?php
/**
 * Basketball Bingo - User Management
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
    $whereClause = "WHERE username LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $pdo->prepare("
    SELECT id, username, email, is_admin, blocked, last_login, created_at 
    FROM users 
    $whereClause 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-between align-center">
    <h1>Benutzerverwaltung</h1>
    <a href="add_user.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Benutzer hinzufügen
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-between align-center">
            <h3>Benutzerliste</h3>
            <form action="users.php" method="get" class="search-form">
                <div class="search-group">
                    <input type="text" name="search" placeholder="Suche..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-sm"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($users) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Benutzername</th>
                            <th>E-Mail</th>
                            <th>Admin</th>
                            <th>Status</th>
                            <th>Letzte Anmeldung</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge badge-info">Admin</span>
                                    <?php else: ?>
                                        <span class="badge">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['blocked']): ?>
                                        <span class="badge badge-danger">Gesperrt</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Aktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['blocked']): ?>
                                            <a href="unblock_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success btn-icon" title="Entsperren" onclick="return confirm('Benutzer entsperren?')">
                                                <i class="bi bi-unlock"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="block_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning btn-icon" title="Sperren" onclick="return confirm('Benutzer sperren?')">
                                                <i class="bi bi-lock"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="scoreboard.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info btn-icon" title="Ergebnisse anzeigen">
                                            <i class="bi bi-list-ol"></i>
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
                <i class="bi bi-people"></i>
                <p>Keine Benutzer gefunden</p>
                <?php if (!empty($search)): ?>
                    <a href="users.php" class="btn btn-sm">Suche zurücksetzen</a>
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