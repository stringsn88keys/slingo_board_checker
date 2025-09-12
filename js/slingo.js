// Slingo Board Checker JavaScript
class SlingoBoardChecker {
    constructor() {
        this.board = this.generateRandomBoard();
        this.coveredPositions = new Set();
        this.drawRows = [];
        this.maxDrawRows = 3;
        this.minDrawRows = 1;
        
        this.initializeBoard();
        this.initializeDrawConfiguration();
        this.bindEvents();
    }

    // Generate a random 5x5 Slingo board
    generateRandomBoard() {
        const board = [];
        for (let row = 0; row < 5; row++) {
            board[row] = [];
            for (let col = 0; col < 5; col++) {
                // Generate numbers 1-75 for each column
                const min = col * 15 + 1;
                const max = (col + 1) * 15;
                board[row][col] = Math.floor(Math.random() * (max - min + 1)) + min;
            }
        }
        return board;
    }

    // Initialize the 5x5 board display
    initializeBoard() {
        const boardContainer = document.getElementById('slingo-board');
        boardContainer.innerHTML = '';

        for (let row = 0; row < 5; row++) {
            for (let col = 0; col < 5; col++) {
                const cell = document.createElement('div');
                cell.className = 'slingo-cell';
                cell.dataset.row = row;
                cell.dataset.col = col;
                // Don't display numbers - just show position coordinates
                cell.textContent = `${row + 1},${col + 1}`;
                cell.title = `Row ${row + 1}, Column ${col + 1}`;
                cell.addEventListener('click', () => this.toggleCell(row, col));
                boardContainer.appendChild(cell);
            }
        }
    }

    // Toggle cell coverage state
    toggleCell(row, col) {
        const position = `${row},${col}`;
        const cell = document.querySelector(`[data-row="${row}"][data-col="${col}"]`);
        
        if (this.coveredPositions.has(position)) {
            this.coveredPositions.delete(position);
            cell.classList.remove('covered');
        } else {
            this.coveredPositions.add(position);
            cell.classList.add('covered');
        }
    }

    // Initialize draw configuration with one default row
    initializeDrawConfiguration() {
        this.addDrawRow();
        this.updateRowControls();
    }

    // Add a new draw row
    addDrawRow() {
        if (this.drawRows.length >= this.maxDrawRows) return;

        const rowIndex = this.drawRows.length;
        this.drawRows.push(['none', 'none', 'none', 'none', 'none']);

        const drawRowsContainer = document.getElementById('draw-rows');
        const rowElement = document.createElement('div');
        rowElement.className = 'draw-row';
        rowElement.innerHTML = `
            <h3>Draw Row ${rowIndex + 1}</h3>
            <div class="draw-positions">
                ${this.createPositionSelectors(rowIndex)}
            </div>
        `;
        drawRowsContainer.appendChild(rowElement);

        this.updateRowControls();
    }

    // Create position selectors for a draw row
    createPositionSelectors(rowIndex) {
        return Array.from({length: 5}, (_, col) => `
            <div class="position-selector">
                <label>Col ${col + 1}</label>
                <select data-row="${rowIndex}" data-col="${col}">
                    <option value="none">None</option>
                    <option value="wild">Wild</option>
                    <option value="super_wild">Super Wild</option>
                </select>
            </div>
        `).join('');
    }

    // Remove the last draw row
    removeDrawRow() {
        if (this.drawRows.length <= this.minDrawRows) return;

        this.drawRows.pop();
        const drawRowsContainer = document.getElementById('draw-rows');
        drawRowsContainer.removeChild(drawRowsContainer.lastChild);
        this.updateRowControls();
    }

    // Update row control button states
    updateRowControls() {
        const addBtn = document.getElementById('add-row');
        const removeBtn = document.getElementById('remove-row');
        
        addBtn.disabled = this.drawRows.length >= this.maxDrawRows;
        removeBtn.disabled = this.drawRows.length <= this.minDrawRows;
    }

    // Update draw option for a specific position
    updateDrawOption(row, col, value) {
        if (this.drawRows[row]) {
            this.drawRows[row][col] = value;
        }
    }

    // Reset the entire board
    resetBoard() {
        this.coveredPositions.clear();
        this.board = this.generateRandomBoard();
        this.initializeBoard();
        this.clearWildMarkers();
        this.hideResults();
    }

    // Hide results section
    hideResults() {
        const resultsSection = document.getElementById('results');
        resultsSection.style.display = 'none';
    }

    // Clear all wild markers from the board
    clearWildMarkers() {
        const cells = document.querySelectorAll('.slingo-cell');
        cells.forEach(cell => {
            cell.classList.remove('wild-marker', 'super-wild-marker');
        });
    }

    // Show wild markers on the board
    showWildMarkers(wildPlacements, superWildPlacements) {
        // Clear existing markers first
        this.clearWildMarkers();

        // Add wild markers
        if (wildPlacements) {
            wildPlacements.forEach(placement => {
                const row = placement.row - 1; // Convert to 0-based index
                const col = placement.column - 1;
                const cell = document.querySelector(`[data-row="${row}"][data-col="${col}"]`);
                if (cell) {
                    cell.classList.add('wild-marker');
                }
            });
        }

        // Add super wild markers
        if (superWildPlacements) {
            superWildPlacements.forEach(placement => {
                const row = placement.row - 1; // Convert to 0-based index
                const col = placement.column - 1;
                const cell = document.querySelector(`[data-row="${row}"][data-col="${col}"]`);
                if (cell) {
                    cell.classList.add('super-wild-marker');
                }
            });
        }
    }

    // Show results section
    showResults() {
        const resultsSection = document.getElementById('results');
        resultsSection.style.display = 'block';
    }

    // Submit analysis to backend
    async submitAnalysis() {
        const submitBtn = document.getElementById('submit-analysis');
        const resultsContent = document.getElementById('results-content');
        
        // Clear any existing wild markers
        this.clearWildMarkers();
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span>Analyzing...';
        
        // Prepare data payload
        const payload = {
            board_state: {
                covered_positions: Array.from(this.coveredPositions).map(pos => 
                    pos.split(',').map(Number)
                ),
                board_numbers: this.board
            },
            draws: this.drawRows.map((positions, index) => ({
                row: index + 1,
                positions: positions
            }))
        };

        try {
            const response = await fetch('api/analyze.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                this.displayResults(data);
            } else {
                throw new Error(data.message || 'Analysis failed');
            }
        } catch (error) {
            console.error('Error:', error);
            resultsContent.innerHTML = `
                <div class="alert alert-error">
                    <h3>Error</h3>
                    <p>Failed to analyze board: ${error.message}</p>
                </div>
            `;
            this.showResults();
        } finally {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Get Optimal Strategy';
        }
    }

    // Display analysis results
    displayResults(data) {
        const resultsContent = document.getElementById('results-content');
        
        let html = '';
        
        // Display optimal selections
        if (data.optimal_selections && data.optimal_selections.length > 0) {
            // Show wild markers on the board for the first recommendation
            const firstSelection = data.optimal_selections[0];
            this.showWildMarkers(firstSelection.wild_placements, firstSelection.super_wild_placements);
            
            html += '<div class="optimal-selections">';
            data.optimal_selections.forEach((selection, index) => {
                html += `
                    <div class="optimal-selection">
                        <h3>Recommendation ${index + 1}</h3>
                        <div class="expected-score">Expected Score: ${selection.expected_score}</div>
                        <div class="reasoning">${selection.reasoning}</div>
                        <div class="wild-placement">
                            <strong>Wild Card Placements:</strong>
                            <ul>
                                ${selection.wild_placements ? selection.wild_placements.map(placement => 
                                    `<li>Column ${placement.column} Wild → Row ${placement.row}</li>`
                                ).join('') : ''}
                                ${selection.super_wild_placements ? selection.super_wild_placements.map(placement => 
                                    `<li>Super Wild → Row ${placement.row}, Column ${placement.column}</li>`
                                ).join('') : ''}
                            </ul>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        // Display analysis summary
        if (data.analysis) {
            html += `
                <div class="analysis-summary">
                    <h4>Board Analysis</h4>
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-value">${data.analysis.current_slingos || 0}</div>
                            <div class="stat-label">Current Slingos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${data.analysis.potential_slingos || 0}</div>
                            <div class="stat-label">Potential Slingos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${data.analysis.covered_cells || this.coveredPositions.size}</div>
                            <div class="stat-label">Covered Cells</div>
                        </div>
                    </div>
                </div>
            `;
        }

        resultsContent.innerHTML = html;
        this.showResults();
    }

    // Bind event listeners
    bindEvents() {
        // Board reset
        document.getElementById('reset-board').addEventListener('click', () => {
            this.resetBoard();
        });

        // Draw row controls
        document.getElementById('add-row').addEventListener('click', () => {
            this.addDrawRow();
        });

        document.getElementById('remove-row').addEventListener('click', () => {
            this.removeDrawRow();
        });

        // Draw option changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('select[data-row][data-col]')) {
                const row = parseInt(e.target.dataset.row);
                const col = parseInt(e.target.dataset.col);
                this.updateDrawOption(row, col, e.target.value);
            }
        });

        // Submit analysis
        document.getElementById('submit-analysis').addEventListener('click', () => {
            this.submitAnalysis();
        });
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SlingoBoardChecker();
});
