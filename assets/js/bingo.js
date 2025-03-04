// Global variables
let quarter = 1;
let cumulativeActivatedFields = 0;
let cumulativeBingos = 0;
let bingoAchieved = false;

// Initialize when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeGame();
});

// Initialize the game
function initializeGame() {
    // Setup click handlers for all cells
    document.querySelectorAll('.bingo-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            toggleActive(this);
        });
    });
    
    // Setup button handlers
    document.getElementById('next-quarter').addEventListener('click', nextQuarter);
    document.getElementById('fullscreen-btn').addEventListener('click', toggleFullScreen);
    
    // Load game state if available
    loadGameState();
    
    // Ensure correct grid layout
    fixGridLayout();
    
    // Update statistics
    updateStats();
}

// Fix grid layout
function fixGridLayout() {
    const board = document.getElementById('bingo-board');
    const cells = board.querySelectorAll('.bingo-cell');
    
    // Ensure we have exactly 25 cells
    if (cells.length !== 25) {
        console.error(`Expected 25 bingo cells, found ${cells.length}`);
    }
    
    // Force grid layout
    board.style.display = 'grid';
    board.style.gridTemplateColumns = 'repeat(5, 1fr)';
    board.style.gridTemplateRows = 'repeat(5, 1fr)';
    board.style.aspectRatio = '1/1';
}

// Toggle active state of a cell
function toggleActive(cell) {
    cell.classList.toggle('active');
    checkBingo();
    updateStats();
    saveGameState();
}

// Check if there's a bingo on the board
function checkBingo() {
    if (bingoAchieved) return;
    
    const cells = document.querySelectorAll('.bingo-cell');
    const board = Array.from(cells).map(cell => cell.classList.contains('active'));
    
    // Check rows
    for (let r = 0; r < 5; r++) {
        if (board[r*5] && board[r*5+1] && board[r*5+2] && board[r*5+3] && board[r*5+4]) {
            bingoAchieved = true;
            highlightWinningCells([r*5, r*5+1, r*5+2, r*5+3, r*5+4]);
            showBingoBanner();
            cumulativeBingos++;
            saveGameState();
            return;
        }
    }
    
    // Check columns
    for (let c = 0; c < 5; c++) {
        if (board[c] && board[c+5] && board[c+10] && board[c+15] && board[c+20]) {
            bingoAchieved = true;
            highlightWinningCells([c, c+5, c+10, c+15, c+20]);
            showBingoBanner();
            cumulativeBingos++;
            saveGameState();
            return;
        }
    }
    
    // Check diagonals
    if (board[0] && board[6] && board[12] && board[18] && board[24]) {
        bingoAchieved = true;
        highlightWinningCells([0, 6, 12, 18, 24]);
        showBingoBanner();
        cumulativeBingos++;
        saveGameState();
        return;
    }
    
    if (board[4] && board[8] && board[12] && board[16] && board[20]) {
        bingoAchieved = true;
        highlightWinningCells([4, 8, 12, 16, 20]);
        showBingoBanner();
        cumulativeBingos++;
        saveGameState();
        return;
    }
}

// Highlight winning cells
function highlightWinningCells(indices) {
    const cells = document.querySelectorAll('.bingo-cell');
    indices.forEach(index => {
        cells[index].classList.add('bingo-win');
    });
}

// Show bingo banner
function showBingoBanner() {
    const board = document.getElementById('bingo-board');
    const banner = document.createElement('div');
    banner.id = 'bingo-banner';
    banner.innerText = "BINGO!";
    board.appendChild(banner);
}

// Update game statistics
function updateStats() {
    const activeFields = document.querySelectorAll('.bingo-cell.active').length;
    const statsEl = document.getElementById('game-stats');
    
    if (statsEl) {
        const statsHTML = `
            <div class="stat">
                <span class="stat-label">Aktivierte Felder:</span>
                <span class="stat-value">${activeFields + cumulativeActivatedFields}</span>
            </div>
            <div class="stat">
                <span class="stat-label">Bingos:</span>
                <span class="stat-value">${cumulativeBingos}</span>
            </div>
        `;
        statsEl.innerHTML = statsHTML;
    }
}

// Move to next quarter
function nextQuarter() {
    cumulativeActivatedFields += document.querySelectorAll('.bingo-cell.active').length;
    
    if (quarter < 4) {
        quarter++;
        saveGameState();
        window.location.reload();
    } else {
        // Game over - show end game dialog
        showEndGameDialog();
    }
}

// Show end game dialog
function showEndGameDialog() {
    const activeFields = document.querySelectorAll('.bingo-cell.active').length + cumulativeActivatedFields;
    const winRate = (cumulativeBingos / quarter) * 100;
    const fieldRate = (activeFields / (25 * quarter)) * 100;
    
    const overlay = document.createElement('div');
    overlay.id = 'endGameOverlay';
    
    overlay.innerHTML = `
        <div class="end-game-dialog">
            <h3>Spiel beendet!</h3>
            <div class="final-stats">
                <div class="stat-item">
                    <div class="stat-value">${cumulativeBingos}</div>
                    <div class="stat-label">Bingos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${activeFields}</div>
                    <div class="stat-label">Aktivierte Felder</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${winRate.toFixed(1)}%</div>
                    <div class="stat-label">Gewinnquote</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${fieldRate.toFixed(1)}%</div>
                    <div class="stat-label">Feldquote</div>
                </div>
            </div>
            <button id="submitScore" class="btn btn-primary">Ergebnis speichern</button>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    document.getElementById('submitScore').addEventListener('click', function() {
        submitScore(activeFields, cumulativeBingos, winRate, fieldRate);
    });
}

// Submit score to server
function submitScore(activeFields, bingos, winRate, fieldRate) {
    const formData = new FormData();
    formData.append('active_fields', activeFields);
    formData.append('bingos', bingos);
    formData.append('win_rate', winRate.toFixed(1));
    formData.append('field_rate', fieldRate.toFixed(1));
    
    fetch('update_scoreboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Clear game state
        sessionStorage.removeItem('bingoGameState');
        // Redirect to scoreboard
        window.location.href = 'scoreboard.php';
    })
    .catch(error => {
        console.error('Error submitting score:', error);
        alert('Fehler beim Speichern des Ergebnisses. Bitte versuche es erneut.');
    });
}

// Toggle fullscreen mode
function toggleFullScreen() {
    const board = document.getElementById('bingo-board');
    
    if (!document.fullscreenElement) {
        if (board.requestFullscreen) {
            board.requestFullscreen();
        } else if (board.webkitRequestFullscreen) {
            board.webkitRequestFullscreen();
        } else if (board.msRequestFullscreen) {
            board.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
}

// Save game state to sessionStorage
function saveGameState() {
    const activeIndices = [];
    document.querySelectorAll('.bingo-cell.active').forEach((cell, index) => {
        activeIndices.push(index);
    });
    
    const gameState = {
        quarter: quarter,
        cumulativeActivatedFields: cumulativeActivatedFields,
        cumulativeBingos: cumulativeBingos,
        bingoAchieved: bingoAchieved,
        activeIndices: activeIndices
    };
    
    sessionStorage.setItem('bingoGameState', JSON.stringify(gameState));
}

// Load game state from sessionStorage
function loadGameState() {
    const savedState = sessionStorage.getItem('bingoGameState');
    if (!savedState) return;
    
    try {
        const gameState = JSON.parse(savedState);
        
        quarter = gameState.quarter || 1;
        document.getElementById('quarter').textContent = quarter;
        
        cumulativeActivatedFields = gameState.cumulativeActivatedFields || 0;
        cumulativeBingos = gameState.cumulativeBingos || 0;
        bingoAchieved = gameState.bingoAchieved || false;
        
        // Restore active cells
        const cells = document.querySelectorAll('.bingo-cell');
        if (gameState.activeIndices && Array.isArray(gameState.activeIndices)) {
            gameState.activeIndices.forEach(index => {
                if (cells[index]) {
                    cells[index].classList.add('active');
                }
            });
        }
        
        // Recheck for bingo
        if (bingoAchieved) {
            showBingoBanner();
        }
    } catch (e) {
        console.error('Error loading game state:', e);
    }
}