<?php
/**
 * Academic Programs Management
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
$db = new Database();

// Get academic levels
$levels = $db->fetch_all("SELECT * FROM academic_levels ORDER BY display_order ASC");

// Get programs with level info
$programs = $db->fetch_all("SELECT p.*, l.name as level_name 
                           FROM academic_programs p 
                           JOIN academic_levels l ON p.level_id = l.id 
                           ORDER BY l.display_order ASC, p.name ASC");

// Get tracks with program info
$tracks = $db->fetch_all("SELECT t.*, p.name as program_name, l.name as level_name 
                         FROM academic_tracks t 
                         JOIN academic_programs p ON t.program_id = p.id 
                         JOIN academic_levels l ON p.level_id = l.id 
                         ORDER BY t.display_order ASC");

// Organize programs by level
$programs_by_level = [];
foreach ($programs as $program) {
    $level_id = $program['level_id'];
    if (!isset($programs_by_level[$level_id])) {
        $programs_by_level[$level_id] = [];
    }
    $programs_by_level[$level_id][] = $program;
}

// Organize tracks by program
$tracks_by_program = [];
foreach ($tracks as $track) {
    $program_id = $track['program_id'];
    if (!isset($tracks_by_program[$program_id])) {
        $tracks_by_program[$program_id] = [];
    }
    $tracks_by_program[$program_id][] = $track;
}

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'level_added'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic level has been added successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'level_updated'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic level has been updated successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'level_deleted'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic level has been deleted successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'program_added'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic program has been added successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'program_updated'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic program has been updated successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'program_deleted'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic program has been deleted successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'track_added'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic track has been added successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'track_updated'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic track has been updated successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'track_deleted'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Academic track has been deleted successfully.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="academics-dashboard">
    <!-- Level Management Section -->
    <div class="panel mb-4">
        <div class="panel-header">
            <h3 class="panel-title"><i class='bx bxs-school'></i> Academic Levels</h3>
            <div class="panel-actions">
                <button type="button" class="btn btn-primary" id="add-level-btn">
                    <i class='bx bx-plus'></i> Add New Level
                </button>
            </div>
        </div>
        
        <div class="panel-body">
            <div class="table-container">
                <table class="table" id="levelsTable">
                    <thead>
                        <tr>
                            <th>Level Name</th>
                            <th>Slug</th>
                            <th>Display Order</th>
                            <th>Programs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($levels)): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="empty-state">
                                    <i class='bx bxs-school'></i>
                                    <p>No academic levels found. Add your first academic level.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($levels as $level): ?>
                            <?php $program_count = isset($programs_by_level[$level['id']]) ? count($programs_by_level[$level['id']]) : 0; ?>
                            <tr data-id="<?php echo $level['id']; ?>">
                                <td><?php echo htmlspecialchars($level['name']); ?></td>
                                <td><code><?php echo htmlspecialchars($level['slug']); ?></code></td>
                                <td><?php echo $level['display_order']; ?></td>
                                <td><?php echo $program_count; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-primary btn-sm edit-level-btn">
                                            <i class='bx bxs-edit'></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-level-btn" <?php echo $program_count > 0 ? 'disabled' : ''; ?>>
                                            <i class='bx bxs-trash'></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Programs Management Section -->
    <div class="panel mb-4">
        <div class="panel-header">
            <h3 class="panel-title"><i class='bx bxs-graduation'></i> Academic Programs</h3>
            <div class="panel-actions">
                <button type="button" class="btn btn-primary" id="add-program-btn" <?php echo empty($levels) ? 'disabled' : ''; ?>>
                    <i class='bx bx-plus'></i> Add New Program
                </button>
            </div>
        </div>
        
        <div class="panel-body">
            <?php if(empty($levels)): ?>
                <div class="alert alert-warning">
                    <i class='bx bx-info-circle'></i>
                    <span>You need to add at least one academic level before you can add programs.</span>
                </div>
            <?php elseif(empty($programs)): ?>
                <div class="empty-state">
                    <i class='bx bxs-graduation'></i>
                    <p>No academic programs found. Add your first academic program.</p>
                </div>
            <?php else: ?>
                <div class="accordion" id="programsAccordion">
                    <?php foreach($levels as $level): ?>
                        <?php if(isset($programs_by_level[$level['id']])): ?>
                            <div class="accordion-item">
                                <div class="accordion-header" id="level-<?php echo $level['id']; ?>">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $level['id']; ?>" aria-expanded="true" aria-controls="collapse-<?php echo $level['id']; ?>">
                                        <i class='bx bxs-school'></i> <?php echo htmlspecialchars($level['name']); ?> <span class="program-count">(<?php echo count($programs_by_level[$level['id']]); ?> programs)</span>
                                    </button>
                                </div>
                                
                                <div id="collapse-<?php echo $level['id']; ?>" class="accordion-collapse collapse show" aria-labelledby="level-<?php echo $level['id']; ?>" data-bs-parent="#programsAccordion">
                                    <div class="accordion-body">
                                        <div class="table-container">
                                            <table class="table program-table">
                                                <thead>
                                                    <tr>
                                                        <th>Program Name</th>
                                                        <th>Tracks</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($programs_by_level[$level['id']] as $program): ?>
                                                    <?php $track_count = isset($tracks_by_program[$program['id']]) ? count($tracks_by_program[$program['id']]) : 0; ?>
                                                    <tr data-id="<?php echo $program['id']; ?>" data-level-id="<?php echo $level['id']; ?>">
                                                        <td><?php echo htmlspecialchars($program['name']); ?></td>
                                                        <td><?php echo $track_count; ?></td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <button type="button" class="btn btn-primary btn-sm edit-program-btn">
                                                                    <i class='bx bxs-edit'></i> Edit
                                                                </button>
                                                                <button type="button" class="btn btn-success btn-sm add-track-btn">
                                                                    <i class='bx bx-plus'></i> Add Track
                                                                </button>
                                                                <button type="button" class="btn btn-danger btn-sm delete-program-btn" <?php echo $track_count > 0 ? 'disabled' : ''; ?>>
                                                                    <i class='bx bxs-trash'></i> Delete
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php if($track_count > 0): ?>
                                                    <tr class="tracks-row">
                                                        <td colspan="3">
                                                            <div class="tracks-container">
                                                                <h5>Tracks</h5>
                                                                <div class="tracks-grid">
                                                                    <?php foreach($tracks_by_program[$program['id']] as $track): ?>
                                                                    <div class="track-card" data-id="<?php echo $track['id']; ?>">
                                                                        <div class="track-header">
                                                                            <h6><?php echo htmlspecialchars($track['name']); ?></h6>
                                                                            <?php if(!empty($track['code'])): ?>
                                                                            <span class="track-code"><?php echo htmlspecialchars($track['code']); ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="track-actions">
                                                                            <button type="button" class="btn btn-primary btn-sm edit-track-btn">
                                                                                <i class='bx bxs-edit'></i> Edit
                                                                            </button>
                                                                            <button type="button" class="btn btn-danger btn-sm delete-track-btn">
                                                                                <i class='bx bxs-trash'></i> Delete
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Level Modal -->
<div class="modal" id="levelModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="levelModalTitle">Add New Academic Level</h3>
                <button type="button" class="modal-close" id="levelModalClose">&times;</button>
            </div>
            <div class="modal-body">
                <form id="levelForm" action="academics-process.php" method="post">
                    <input type="hidden" id="levelId" name="level_id" value="0">
                    <input type="hidden" name="action" value="save_level">
                    
                    <div class="form-group">
                        <label for="levelName">Level Name</label>
                        <input type="text" id="levelName" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="levelSlug">Level Slug <small>(for URLs)</small></label>
                        <input type="text" id="levelSlug" name="slug" class="form-control" required>
                        <small class="form-text">Used in URLs. Only letters, numbers, and dashes. Example: "senior-high"</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="levelDescription">Description</label>
                        <textarea id="levelDescription" name="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="levelOrder">Display Order</label>
                        <input type="number" id="levelOrder" name="display_order" class="form-control" value="0" min="0">
                        <small class="form-text">Lower numbers will appear first.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="levelCancelBtn">Cancel</button>
                <button type="submit" form="levelForm" class="btn btn-primary">Save Level</button>
            </div>
        </div>
    </div>
</div>

<!-- Program Modal -->
<div class="modal" id="programModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="programModalTitle">Add New Academic Program</h3>
                <button type="button" class="modal-close" id="programModalClose">&times;</button>
            </div>
            <div class="modal-body">
                <form id="programForm" action="academics-process.php" method="post">
                    <input type="hidden" id="programId" name="program_id" value="0">
                    <input type="hidden" name="action" value="save_program">
                    
                    <div class="form-group">
                        <label for="programLevelId">Academic Level</label>
                        <select id="programLevelId" name="level_id" class="form-control" required>
                            <option value="">-- Select Level --</option>
                            <?php foreach($levels as $level): ?>
                            <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="programName">Program Name</label>
                        <input type="text" id="programName" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="programDescription">Description</label>
                        <textarea id="programDescription" name="description" class="form-control" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="programCancelBtn">Cancel</button>
                <button type="submit" form="programForm" class="btn btn-primary">Save Program</button>
            </div>
        </div>
    </div>
</div>

<!-- Track Modal -->
<div class="modal" id="trackModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="trackModalTitle">Add New Academic Track</h3>
                <button type="button" class="modal-close" id="trackModalClose">&times;</button>
            </div>
            <div class="modal-body">
                <form id="trackForm" action="academics-process.php" method="post">
                    <input type="hidden" id="trackId" name="track_id" value="0">
                    <input type="hidden" id="trackProgramId" name="program_id" value="0">
                    <input type="hidden" name="action" value="save_track">
                    
                    <div class="form-group">
                        <label for="trackName">Track Name</label>
                        <input type="text" id="trackName" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="trackCode">Track Code <small>(optional)</small></label>
                        <input type="text" id="trackCode" name="code" class="form-control">
                        <small class="form-text">Example: STEM, ABM, HUMSS</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="trackDescription">Description</label>
                        <textarea id="trackDescription" name="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="trackOrder">Display Order</label>
                        <input type="number" id="trackOrder" name="display_order" class="form-control" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="trackCancelBtn">Cancel</button>
                <button type="submit" form="trackForm" class="btn btn-primary">Save Track</button>
            </div>
        </div>
    </div>
</div>

<style>
    .academics-dashboard {
        margin-bottom: 30px;
    }
    
    .accordion-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 15px;
        overflow: hidden;
    }
    
    .accordion-button {
        background-color: #f8f9fa;
        padding: 15px;
        font-weight: 600;
        font-size: 16px;
        border: none;
        width: 100%;
        text-align: left;
        display: flex;
        align-items: center;
        position: relative;
        cursor: pointer;
    }
    
    .accordion-button i {
        margin-right: 10px;
        font-size: 20px;
        color: var(--primary-color);
    }
    
    .accordion-button::after {
        content: '\e9c5';
        font-family: 'boxicons';
        position: absolute;
        right: 15px;
        transition: transform 0.3s;
        font-size: 20px;
    }
    
    .accordion-button.collapsed::after {
        transform: rotate(180deg);
    }
    
    .program-count {
        color: #6c757d;
        font-size: 14px;
        margin-left: 10px;
    }
    
    .accordion-collapse {
        border-top: 1px solid #dee2e6;
    }
    
    .accordion-body {
        padding: 15px;
    }
    
    .program-table {
        margin-bottom: 0;
    }
    
    .tracks-row {
        background-color: #f8f9fa;
    }
    
    .tracks-container {
        padding: 15px;
    }
    
    .tracks-container h5 {
        font-size: 16px;
        margin-bottom: 15px;
        color: #495057;
    }
    
    .tracks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .track-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        padding: 15px;
    }
    
    .track-header {
        margin-bottom: 10px;
    }
    
    .track-header h6 {
        margin: 0 0 5px;
        font-size: 15px;
        font-weight: 600;
    }
    
    .track-code {
        display: inline-block;
        background-color: var(--primary-light);
        color: white;
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 50px;
    }
    
    .track-actions {
        display: flex;
        gap: 5px;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Level Modal
        const levelModal = document.getElementById('levelModal');
        const levelModalTitle = document.getElementById('levelModalTitle');
        const levelForm = document.getElementById('levelForm');
        const levelId = document.getElementById('levelId');
        const levelName = document.getElementById('levelName');
        const levelSlug = document.getElementById('levelSlug');
        const levelDescription = document.getElementById('levelDescription');
        const levelOrder = document.getElementById('levelOrder');
        const levelModalClose = document.getElementById('levelModalClose');
        const levelCancelBtn = document.getElementById('levelCancelBtn');
        
        // Program Modal
        const programModal = document.getElementById('programModal');
        const programModalTitle = document.getElementById('programModalTitle');
        const programForm = document.getElementById('programForm');
        const programId = document.getElementById('programId');
        const programLevelId = document.getElementById('programLevelId');
        const programName = document.getElementById('programName');
        const programDescription = document.getElementById('programDescription');
        const programModalClose = document.getElementById('programModalClose');
        const programCancelBtn = document.getElementById('programCancelBtn');
        
        // Track Modal
        const trackModal = document.getElementById('trackModal');
        const trackModalTitle = document.getElementById('trackModalTitle');
        const trackForm = document.getElementById('trackForm');
        const trackId = document.getElementById('trackId');
        const trackProgramId = document.getElementById('trackProgramId');
        const trackName = document.getElementById('trackName');
        const trackCode = document.getElementById('trackCode');
        const trackDescription = document.getElementById('trackDescription');
        const trackOrder = document.getElementById('trackOrder');
        const trackModalClose = document.getElementById('trackModalClose');
        const trackCancelBtn = document.getElementById('trackCancelBtn');
        
        // Add Level Button
        const addLevelBtn = document.getElementById('add-level-btn');
        if (addLevelBtn) {
            addLevelBtn.addEventListener('click', function() {
                levelModalTitle.textContent = 'Add New Academic Level';
                levelId.value = '0';
                levelForm.reset();
                openModal(levelModal);
            });
        }
        
        // Edit Level Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-level-btn') || 
                e.target.parentElement.classList.contains('edit-level-btn')) {
                
                const btn = e.target.classList.contains('edit-level-btn') ? 
                            e.target : e.target.parentElement;
                const row = btn.closest('tr');
                const id = row.dataset.id;
                
                // Fetch level data via AJAX
                fetch(`academics-process.php?action=get_level&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            levelModalTitle.textContent = 'Edit Academic Level';
                            levelId.value = data.level.id;
                            levelName.value = data.level.name;
                            levelSlug.value = data.level.slug;
                            levelDescription.value = data.level.description;
                            levelOrder.value = data.level.display_order;
                            openModal(levelModal);
                        } else {
                            alert('Failed to load level data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading level data');
                    });
            }
        });
        
        // Delete Level Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-level-btn') || 
                e.target.parentElement.classList.contains('delete-level-btn')) {
                
                const btn = e.target.classList.contains('delete-level-btn') ? 
                            e.target : e.target.parentElement;
                
                if (btn.disabled) {
                    alert('Cannot delete this level because it has programs associated with it.');
                    return;
                }
                
                const row = btn.closest('tr');
                const id = row.dataset.id;
                const name = row.cells[0].textContent;
                
                if (confirm(`Are you sure you want to delete the academic level "${name}"?`)) {
                    // Submit deletion via form
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'academics-process.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_level';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'level_id';
                    idInput.value = id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
        
        // Add Program Button
        const addProgramBtn = document.getElementById('add-program-btn');
        if (addProgramBtn) {
            addProgramBtn.addEventListener('click', function() {
                programModalTitle.textContent = 'Add New Academic Program';
                programId.value = '0';
                programForm.reset();
                openModal(programModal);
            });
        }
        
        // Edit Program Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-program-btn') || 
                e.target.parentElement.classList.contains('edit-program-btn')) {
                
                const btn = e.target.classList.contains('edit-program-btn') ? 
                            e.target : e.target.parentElement;
                const row = btn.closest('tr');
                const id = row.dataset.id;
                const levelId = row.dataset.levelId;
                
                // Fetch program data via AJAX
                fetch(`academics-process.php?action=get_program&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            programModalTitle.textContent = 'Edit Academic Program';
                            programId.value = data.program.id;
                            programLevelId.value = data.program.level_id;
                            programName.value = data.program.name;
                            programDescription.value = data.program.description;
                            openModal(programModal);
                        } else {
                            alert('Failed to load program data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading program data');
                    });
            }
        });
        
        // Delete Program Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-program-btn') || 
                e.target.parentElement.classList.contains('delete-program-btn')) {
                
                const btn = e.target.classList.contains('delete-program-btn') ? 
                            e.target : e.target.parentElement;
                
                if (btn.disabled) {
                    alert('Cannot delete this program because it has tracks associated with it.');
                    return;
                }
                
                const row = btn.closest('tr');
                const id = row.dataset.id;
                const name = row.cells[0].textContent;
                
                if (confirm(`Are you sure you want to delete the academic program "${name}"?`)) {
                    // Submit deletion via form
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'academics-process.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_program';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'program_id';
                    idInput.value = id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
        
        // Add Track Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-track-btn') || 
                e.target.parentElement.classList.contains('add-track-btn')) {
                
                const btn = e.target.classList.contains('add-track-btn') ? 
                            e.target : e.target.parentElement;
                const row = btn.closest('tr');
                const programId = row.dataset.id;
                
                trackModalTitle.textContent = 'Add New Academic Track';
                trackId.value = '0';
                trackProgramId.value = programId;
                trackForm.reset();
                trackOrder.value = '0';
                openModal(trackModal);
            }
        });
        
        // Edit Track Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-track-btn') || 
                e.target.parentElement.classList.contains('edit-track-btn')) {
                
                const btn = e.target.classList.contains('edit-track-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.track-card');
                const id = card.dataset.id;
                
                // Fetch track data via AJAX
                fetch(`academics-process.php?action=get_track&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            trackModalTitle.textContent = 'Edit Academic Track';
                            trackId.value = data.track.id;
                            trackProgramId.value = data.track.program_id;
                            trackName.value = data.track.name;
                            trackCode.value = data.track.code;
                            trackDescription.value = data.track.description;
                            trackOrder.value = data.track.display_order;
                            openModal(trackModal);
                        } else {
                            alert('Failed to load track data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading track data');
                    });
            }
        });
        
        // Delete Track Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-track-btn') || 
                e.target.parentElement.classList.contains('delete-track-btn')) {
                
                const btn = e.target.classList.contains('delete-track-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.track-card');
                const id = card.dataset.id;
                const name = card.querySelector('h6').textContent;
                
                if (confirm(`Are you sure you want to delete the academic track "${name}"?`)) {
                    // Submit deletion via form
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'academics-process.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_track';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'track_id';
                    idInput.value = id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
        
        // Auto-generate slug from name
        if (levelName && levelSlug) {
            levelName.addEventListener('input', function() {
                levelSlug.value = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '') // Remove special chars except spaces and dashes
                    .replace(/\s+/g, '-') // Replace spaces with dashes
                    .replace(/-+/g, '-'); // Replace multiple dashes with single dash
            });
        }
        
        // Modal close buttons
        [levelModalClose, levelCancelBtn].forEach(btn => {
            if (btn) btn.addEventListener('click', () => closeModal(levelModal));
        });
        
        [programModalClose, programCancelBtn].forEach(btn => {
            if (btn) btn.addEventListener('click', () => closeModal(programModal));
        });
        
        [trackModalClose, trackCancelBtn].forEach(btn => {
            if (btn) btn.addEventListener('click', () => closeModal(trackModal));
        });
        
        // Modal utility functions
        function openModal(modal) {
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal(modal) {
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === levelModal) closeModal(levelModal);
            if (e.target === programModal) closeModal(programModal);
            if (e.target === trackModal) closeModal(trackModal);
        });
        
        // Bootstrap-like accordion functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('accordion-button')) {
                e.target.classList.toggle('collapsed');
                
                const target = e.target.getAttribute('data-bs-target');
                const content = document.querySelector(target);
                
                if (content) {
                    content.classList.toggle('show');
                }
            }
        });
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Academic Programs';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>