<?php
/**
 * Basketball Bingo - Admin Dashboard
 * Overview of system statistics and quick actions
 */
require_once 'header.php';

// Gather statistics
try {
    // User statistics
    $userStats = $pdo->query("
        SELECT
            COUNT(*) as total_users,
            SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as total_admins,
            SUM(CASE WHEN blocked = 1 THEN 1 ELSE 0 END) as blocked_users,
            SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_users
        FROM users
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Content statistics
    $contentStats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM clubs) as total_clubs,
            (SELECT COUNT(*) FROM teams) as total_teams,
            (SELECT COUNT(*) FROM games) as total_games,
            (SELECT COUNT(*) FROM bingo_fields) as total_fields
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Game statistics
    $gameStats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM games WHERE time > NOW()) as upcoming_games,
            (SELECT COUNT(*) FROM scoreboard) as total_matches,
            (SELECT SUM(bingos) FROM scoreboard) as total_bingos
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Support statistics
    $supportStats = $pdo->query("
        SELECT
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets
        FROM tickets
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Recent tickets
    $recentTickets = $pdo->query("
        SELECT t.id, t.subject, t.status, t.created_at, u.username
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activities (combine recent registrations, games, etc.)
    $recentUsers = $pdo->query("
        SELECT id, username, 'user' as type, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $recentGames = $pdo->query("
        SELECT g.id, CONCAT(t.name, ' vs ', g.opponent) as name, 'game' as type, g.created_at
        FROM games g
        JOIN teams t ON g.team_id = t.id
        ORDER BY g.created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and sort recent activities
    $recentActivities = array_merge($recentUsers, $recentGames);
    usort($recentActivities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recentActivities = array_slice($recentActivities, 0, 5);
    
} catch (PDOException $e) {
    error_log("Dashboard statistics error: " . $e->getMessage());
    $error = "Fehler beim Laden der Statistiken. Bitte versuchen Sie es später erneut.";
}
?>

<h1>Admin Dashboard</h1>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php else: ?>

<div class="dashboard-stats">
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($userStats['total_users']); ?></div>
                <div class="stat-label">Benutzer</div>
            </div>
            <div class="stat-footer">
                <span class="stat-badge"><?php echo number_format($userStats['active_users']); ?> aktiv</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-building"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($contentStats['total_clubs']); ?></div>
                <div class="stat-label">Vereine</div>
            </div>
            <div class="stat-footer">
                <span class="stat-badge"><?php echo number_format($contentStats['total_teams']); ?> Teams</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($contentStats['total_games']); ?></div>
                <div class="stat-label">Spiele</div>
            </div>
            <div class="stat-footer">
                <span class="stat-badge"><?php echo number_format($gameStats['upcoming_games']); ?> anstehend</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-grid-3x3"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($contentStats['total_fields']); ?></div>
                <div class="stat-label">Bingo-Felder</div>
            </div>
        </div>
    </div>
    
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-trophy"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($gameStats['total_matches']); ?></div>
                <div class="stat-label">Gespielte Matches</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-grid"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($gameStats['total_bingos']); ?></div>
                <div class="stat-label">Erzielte Bingos</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-ticket"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($supportStats['total_tickets']); ?></div>
                <div class="stat-label">Support-Tickets</div>
            </div>
            <div class="stat-footer">
                <span class="stat-badge badge-<?php echo $supportStats['open_tickets'] > 0 ? 'danger' : 'success'; ?>">
                    <?php echo number_format($supportStats['open_tickets']); ?> offen
                </span>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-panels">
    <div class="panel-row">
        <div class="dashboard-panel">
            <div class="panel-header">
                <h3>Neueste Tickets</h3>
                <a href="tickets.php" class="btn btn-sm">Alle anzeigen</a>
            </div>
            <div class="panel-body">
                <?php if (count($recentTickets) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Betreff</th>
                            <th>Benutzer</th>
                            <th>Status</th>
                            <th>Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                        <tr>
                            <td><a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>"><?php echo htmlspecialchars($ticket['subject']); ?></a></td>
                            <td><?php echo htmlspecialchars($ticket['username'] ?: 'Gast'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $ticket['status'] == 'open' ? 'danger' : ($ticket['status'] == 'pending' ? 'warning' : 'success'); ?>">
                                    <?php echo $ticket['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="empty-state">Keine Tickets vorhanden</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-panel">
            <div class="panel-header">
                <h3>Neueste Aktivitäten</h3>
            </div>
            <div class="panel-body">
                <?php if (count($recentActivities) > 0): ?>
                <ul class="activity-list">
                    <?php foreach ($recentActivities as $activity): ?>
                    <li class="activity-item">
                        <div class="activity-icon">
                            <?php if ($activity['type'] == 'user'): ?>
                            <i class="bi bi-person-plus"></i>
                            <?php elseif ($activity['type'] == 'game'): ?>
                            <i class="bi bi-calendar-plus"></i>
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?php if ($activity['type'] == 'user'): ?>
                                Neuer Benutzer: <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                <?php elseif ($activity['type'] == 'game'): ?>
                                Neues Spiel: <strong><?php echo htmlspecialchars($activity['name']); ?></strong>
                                <?php endif; ?>
                            </div>
                            <div class="activity-time"><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="empty-state">Keine Aktivitäten vorhanden</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="panel-row">
        <div class="dashboard-panel">
            <div class="panel-header">
                <h3>Schnellaktionen</h3>
            </div>
            <div class="panel-body">
                <div class="quick-actions">
                    <a href="add_user.php" class="quick-action-btn">
                        <i class="bi bi-person-plus"></i>
                        <span>Benutzer hinzufügen</span>
                    </a>
                    <a href="add_club.php" class="quick-action-btn">
                        <i class="bi bi-building-add"></i>
                        <span>Verein hinzufügen</span>
                    </a>
                    <a href="add_team.php" class="quick-action-btn">
                        <i class="bi bi-people-fill"></i>
                        <span>Team hinzufügen</span>
                    </a>
                    <a href="add_game.php" class="quick-action-btn">
                        <i class="bi bi-calendar-plus"></i>
                        <span>Spiel hinzufügen</span>
                    </a>
                    <a href="site_settings.php" class="quick-action-btn">
                        <i class="bi bi-sliders"></i>
                        <span>Einstellungen</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-stats {
    margin-bottom: 2rem;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
    padding: 1.25rem;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.stat-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.75rem;
    opacity: 0.15;
    color: var(--color-primary);
}

.stat-content {
    margin-top: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 600;
    color: var(--color-primary);
    line-height: 1.2;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--color-muted);
    margin-top: 0.25rem;
}

.stat-footer {
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.stat-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    background: rgba(0,0,0,0.05);
}

.dashboard-panels {
    margin-bottom: 2rem;
}

.panel-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.dashboard-panel {
    background: white;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.panel-header {
    padding: 1rem 1.5rem;
    background: rgba(0,0,0,0.02);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.panel-body {
    padding: 1.5rem;
}

.activity-list {
    list-style: none;
    padding: 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    margin-right: 1rem;
    background: rgba(0,0,0,0.05);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-content {
    flex: 1;
}

.activity-time {
    font-size: 0.8rem;
    color: var(--color-muted);
    margin-top: 0.25rem;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.25rem 1rem;
    background: rgba(0,0,0,0.02);
    border-radius: var(--border-radius-md);
    text-align: center;
    text-decoration: none;
    color: var(--color-primary);
    transition: all 0.2s ease;
}

.quick-action-btn:hover {
    background: rgba(0,0,0,0.05);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.quick-action-btn i {
    font-size: 1.75rem;
    margin-bottom: 0.75rem;
}

.empty-state {
    text-align: center;
    padding: 1.5rem;
    color: var(--color-muted);
    font-style: italic;
}

@media (max-width: 768px) {
    .panel-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php endif; ?>

<?php require_once 'footer.php'; ?>