<?php
/**
 * Admissions Management
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

// Get admission data
$admission_policies = $db->fetch_row("SELECT * FROM admission_policies ORDER BY display_order ASC LIMIT 1");
$student_types = $db->fetch_all("SELECT * FROM student_types ORDER BY display_order ASC");
$age_requirements = $db->fetch_all("SELECT * FROM age_requirements ORDER BY display_order ASC");
$enrollment_procedures = $db->fetch_all("SELECT * FROM enrollment_procedures ORDER BY display_order ASC");
$non_readmission_grounds = $db->fetch_all("SELECT * FROM non_readmission_grounds ORDER BY display_order ASC");

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'saved'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Admissions content has been saved successfully.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-school'></i> Admissions Content</h3>
        <div class="panel-actions">
            <a href="../admissions.php" class="btn btn-light" target="_blank">
                <i class='bx bx-link-external'></i> View Admissions Page
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="tabs">
            <div class="tab-header">
                <div class="tab active" data-tab="policies">Admission Policies</div>
                <div class="tab" data-tab="student-types">Student Types</div>
                <div class="tab" data-tab="age-requirements">Age Requirements</div>
                <div class="tab" data-tab="enrollment">Enrollment Procedures</div>
                <div class="tab" data-tab="non-readmission">Non-Readmission</div>
            </div>
            
            <div class="tab-content">
                <!-- Admission Policies Tab -->
                <div class="tab-pane active" id="policies">
                    <form action="admissions-process.php" method="post">
                        <input type="hidden" name="action" value="save_policies">
                        
                        <div class="form-group">
                            <label for="admission_policies">Admission Policies</label>
                            <p class="form-text">Enter each policy on a new line. These will be displayed as a numbered list.</p>
                            <textarea id="admission_policies" name="content" class="form-control" rows="10"><?php echo isset($admission_policies['content']) ? htmlspecialchars($admission_policies['content']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Policies</button>
                    </form>
                </div>
                
                <!-- Student Types Tab -->
                <div class="tab-pane" id="student-types">
                    <div class="student-types-management">
                        <div class="action-row">
                            <button type="button" class="btn btn-primary" id="add-student-type-btn">
                                <i class='bx bx-plus'></i> Add Student Type
                            </button>
                        </div>
                        
                        <div class="student-types-list">
                            <?php if(empty($student_types)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-user-plus'></i>
                                    <p>No student types found. Add your first student type.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($student_types as $type): ?>
                                    <div class="type-card" data-id="<?php echo $type['id']; ?>">
                                        <div class="type-header">
                                            <h4><?php echo htmlspecialchars($type['name']); ?></h4>
                                            <div class="type-order">Order: <?php echo $type['display_order']; ?></div>
                                        </div>
                                        <div class="type-content">
                                            <div class="requirements-preview">
                                                <h5>Requirements:</h5>
                                                <div class="requirements-text">
                                                    <?php echo nl2br(htmlspecialchars($type['requirements'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="type-actions">
                                            <button type="button" class="btn btn-primary btn-sm edit-student-type-btn">
                                                <i class='bx bxs-edit'></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-student-type-btn">
                                                <i class='bx bxs-trash'></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Age Requirements Tab -->
                <div class="tab-pane" id="age-requirements">
                    <div class="age-requirements-management">
                        <div class="action-row">
                            <button type="button" class="btn btn-primary" id="add-age-requirement-btn">
                                <i class='bx bx-plus'></i> Add Age Requirement
                            </button>
                        </div>
                        
                        <div class="age-requirements-list">
                            <?php if(empty($age_requirements)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-time-five'></i>
                                    <p>No age requirements found. Add your first age requirement.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($age_requirements as $requirement): ?>
                                    <div class="requirement-card" data-id="<?php echo $requirement['id']; ?>">
                                        <div class="requirement-header">
                                            <h4><?php echo htmlspecialchars($requirement['grade_level']); ?></h4>
                                            <div class="requirement-order">Order: <?php echo $requirement['display_order']; ?></div>
                                        </div>
                                        <div class="requirement-content">
                                            <div class="requirements-preview">
                                                <h5>Requirements:</h5>
                                                <div class="requirements-text">
                                                    <?php echo nl2br(htmlspecialchars($requirement['requirements'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="requirement-actions">
                                            <button type="button" class="btn btn-primary btn-sm edit-age-requirement-btn">
                                                <i class='bx bxs-edit'></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-age-requirement-btn">
                                                <i class='bx bxs-trash'></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Enrollment Procedures Tab -->
                <div class="tab-pane" id="enrollment">
                    <div class="enrollment-procedures-management">
                        <div class="action-row">
                            <button type="button" class="btn btn-primary" id="add-enrollment-procedure-btn">
                                <i class='bx bx-plus'></i> Add Enrollment Procedure
                            </button>
                        </div>
                        
                        <div class="enrollment-procedures-list">
                            <?php if(empty($enrollment_procedures)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-list-check'></i>
                                    <p>No enrollment procedures found. Add your first enrollment procedure.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($enrollment_procedures as $procedure): ?>
                                    <div class="procedure-card" data-id="<?php echo $procedure['id']; ?>">
                                        <div class="procedure-header">
                                            <h4>FOR <?php echo htmlspecialchars(strtoupper($procedure['student_type'])); ?></h4>
                                            <div class="procedure-order">Order: <?php echo $procedure['display_order']; ?></div>
                                        </div>
                                        <div class="procedure-content">
                                            <div class="steps-preview">
                                                <h5>Steps:</h5>
                                                <div class="steps-text">
                                                    <?php echo nl2br(htmlspecialchars($procedure['steps'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="procedure-actions">
                                            <button type="button" class="btn btn-primary btn-sm edit-enrollment-procedure-btn">
                                                <i class='bx bxs-edit'></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-enrollment-procedure-btn">
                                                <i class='bx bxs-trash'></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Non-Readmission Grounds Tab -->
                <div class="tab-pane" id="non-readmission">
                    <div class="non-readmission-grounds-management">
                        <div class="action-row">
                            <button type="button" class="btn btn-primary" id="add-non-readmission-btn">
                                <i class='bx bx-plus'></i> Add Non-Readmission Ground
                            </button>
                        </div>
                        
                        <div class="non-readmission-list">
                            <?php if(empty($non_readmission_grounds)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-block'></i>
                                    <p>No non-readmission grounds found. Add your first non-readmission ground.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($non_readmission_grounds as $ground): ?>
                                    <div class="ground-card" data-id="<?php echo $ground['id']; ?>">
                                        <div class="ground-content">
                                            <div class="ground-description">
                                                <?php echo htmlspecialchars($ground['description']); ?>
                                            </div>
                                            <div class="ground-order">Order: <?php echo $ground['display_order']; ?></div>
                                        </div>
                                        <div class="ground-actions">
                                            <button type="button" class="btn btn-primary btn-sm edit-non-readmission-btn">
                                                <i class='bx bxs-edit'></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-non-readmission-btn">
                                                <i class='bx bxs-trash'></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Type Modal -->
<div class="modal" id="studentTypeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="studentTypeModalTitle">Add Student Type</h3>
                <button type="button" class="modal-close" id="closeStudentTypeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="studentTypeForm" action="admissions-process.php" method="post">
                    <input type="hidden" name="action" value="save_student_type">
                    <input type="hidden" id="studentTypeId" name="id" value="0">
                    
                    <div class="form-group">
                        <label for="studentTypeName">Type Name</label>
                        <input type="text" id="studentTypeName" name="name" class="form-control" required>
                        <small class="form-text">Example: New Students, Transferees, Old Students</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="studentTypeRequirements">Requirements</label>
                        <p class="form-text">Enter each requirement on a new line. These will be displayed as a numbered list.</p>
                        <textarea id="studentTypeRequirements" name="requirements" class="form-control" rows="8" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="studentTypeOrder">Display Order</label>
                        <input type="number" id="studentTypeOrder" name="display_order" class="form-control" value="0" min="0">
                        <small class="form-text">Lower numbers will appear first.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelStudentTypeBtn">Cancel</button>
                <button type="submit" form="studentTypeForm" class="btn btn-primary">Save Student Type</button>
            </div>
        </div>
    </div>
</div>

<!-- Age Requirement Modal -->
<div class="modal" id="ageRequirementModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="ageRequirementModalTitle">Add Age Requirement</h3>
                <button type="button" class="modal-close" id="closeAgeRequirementModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="ageRequirementForm" action="admissions-process.php" method="post">
                    <input type="hidden" name="action" value="save_age_requirement">
                    <input type="hidden" id="ageRequirementId" name="id" value="0">
                    
                    <div class="form-group">
                        <label for="gradeLevel">Grade Level</label>
                        <input type="text" id="gradeLevel" name="grade_level" class="form-control" required>
                        <small class="form-text">Example: Preschool, Elementary, Junior High</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="ageRequirements">Requirements</label>
                        <p class="form-text">Enter each requirement on a new line. These will be displayed as a list.</p>
                        <textarea id="ageRequirements" name="requirements" class="form-control" rows="6" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="ageRequirementOrder">Display Order</label>
                        <input type="number" id="ageRequirementOrder" name="display_order" class="form-control" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelAgeRequirementBtn">Cancel</button>
                <button type="submit" form="ageRequirementForm" class="btn btn-primary">Save Age Requirement</button>
            </div>
        </div>
    </div>
</div>

<!-- Enrollment Procedure Modal -->
<div class="modal" id="enrollmentProcedureModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="enrollmentProcedureModalTitle">Add Enrollment Procedure</h3>
                <button type="button" class="modal-close" id="closeEnrollmentProcedureModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="enrollmentProcedureForm" action="admissions-process.php" method="post">
                    <input type="hidden" name="action" value="save_enrollment_procedure">
                    <input type="hidden" id="enrollmentProcedureId" name="id" value="0">
                    
                    <div class="form-group">
                        <label for="studentType">Student Type</label>
                        <input type="text" id="studentType" name="student_type" class="form-control" required>
                        <small class="form-text">Example: New Students, Transferees, Old Students</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="enrollmentSteps">Enrollment Steps</label>
                        <p class="form-text">Enter each step on a new line. These will be displayed as a numbered list.</p>
                        <textarea id="enrollmentSteps" name="steps" class="form-control" rows="8" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="enrollmentProcedureOrder">Display Order</label>
                        <input type="number" id="enrollmentProcedureOrder" name="display_order" class="form-control" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelEnrollmentProcedureBtn">Cancel</button>
                <button type="submit" form="enrollmentProcedureForm" class="btn btn-primary">Save Enrollment Procedure</button>
            </div>
        </div>
    </div>
</div>

<!-- Non-Readmission Modal -->
<div class="modal" id="nonReadmissionModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="nonReadmissionModalTitle">Add Non-Readmission Ground</h3>
                <button type="button" class="modal-close" id="closeNonReadmissionModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="nonReadmissionForm" action="admissions-process.php" method="post">
                    <input type="hidden" name="action" value="save_non_readmission">
                    <input type="hidden" id="nonReadmissionId" name="id" value="0">
                    
                    <div class="form-group">
                        <label for="nonReadmissionDescription">Description</label>
                        <textarea id="nonReadmissionDescription" name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="nonReadmissionOrder">Display Order</label>
                        <input type="number" id="nonReadmissionOrder" name="display_order" class="form-control" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelNonReadmissionBtn">Cancel</button>
                <button type="submit" form="nonReadmissionForm" class="btn btn-primary">Save Non-Readmission Ground</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Tab Styles */
    .tabs {
        margin-top: 20px;
    }
    
    .tab-header {
        display: flex;
        overflow-x: auto;
        border-bottom: 1px solid var(--border-color);
    }
    
    .tab {
        padding: 15px 20px;
        cursor: pointer;
        white-space: nowrap;
        position: relative;
        transition: all 0.3s;
    }
    
    .tab:hover {
        background-color: #f8f9fa;
    }
    
    .tab.active {
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: var(--primary-color);
    }
    
    .tab-content {
        padding: 20px 0;
    }
    
    .tab-pane {
        display: none;
    }
    
    .tab-pane.active {
        display: block;
    }
    
    /* Card Styles */
    .action-row {
        margin-bottom: 20px;
    }
    
    .type-card, .requirement-card, .procedure-card, .ground-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        margin-bottom: 15px;
        overflow: hidden;
    }
    
    .type-header, .requirement-header, .procedure-header {
        padding: 15px;
        background-color: #f8f9fa;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .type-header h4, .requirement-header h4, .procedure-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }
    
    .type-order, .requirement-order, .procedure-order, .ground-order {
        font-size: 14px;
        color: #6c757d;
    }
    
    .type-content, .requirement-content, .procedure-content, .ground-content {
        padding: 15px;
        position: relative;
    }
    
    .ground-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .ground-description {
        flex: 1;
    }
    
    .requirements-preview, .steps-preview {
        margin-bottom: 10px;
    }
    
    .requirements-preview h5, .steps-preview h5 {
        font-size: 15px;
        margin-bottom: 10px;
    }
    
    .requirements-text, .steps-text {
        color: #6c757d;
        white-space: pre-line;
    }
    
    .type-actions, .requirement-actions, .procedure-actions, .ground-actions {
        padding: 10px 15px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 30px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 48px;
        opacity: 0.5;
        margin-bottom: 10px;
    }
    
    /* Form Text */
    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .tab-header {
            flex-wrap: wrap;
        }
        
        .tab {
            flex-grow: 1;
            text-align: center;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and panes
                tabs.forEach(t => t.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Add active class to current tab and pane
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Student Type Modal
        const studentTypeModal = document.getElementById('studentTypeModal');
        const studentTypeModalTitle = document.getElementById('studentTypeModalTitle');
        const studentTypeForm = document.getElementById('studentTypeForm');
        const studentTypeId = document.getElementById('studentTypeId');
        const studentTypeName = document.getElementById('studentTypeName');
        const studentTypeRequirements = document.getElementById('studentTypeRequirements');
        const studentTypeOrder = document.getElementById('studentTypeOrder');
        const closeStudentTypeModal = document.getElementById('closeStudentTypeModal');
        const cancelStudentTypeBtn = document.getElementById('cancelStudentTypeBtn');
        
        // Age Requirement Modal
        const ageRequirementModal = document.getElementById('ageRequirementModal');
        const ageRequirementModalTitle = document.getElementById('ageRequirementModalTitle');
        const ageRequirementForm = document.getElementById('ageRequirementForm');
        const ageRequirementId = document.getElementById('ageRequirementId');
        const gradeLevel = document.getElementById('gradeLevel');
        const ageRequirements = document.getElementById('ageRequirements');
        const ageRequirementOrder = document.getElementById('ageRequirementOrder');
        const closeAgeRequirementModal = document.getElementById('closeAgeRequirementModal');
        const cancelAgeRequirementBtn = document.getElementById('cancelAgeRequirementBtn');
        
        // Enrollment Procedure Modal
        const enrollmentProcedureModal = document.getElementById('enrollmentProcedureModal');
        const enrollmentProcedureModalTitle = document.getElementById('enrollmentProcedureModalTitle');
        const enrollmentProcedureForm = document.getElementById('enrollmentProcedureForm');
        const enrollmentProcedureId = document.getElementById('enrollmentProcedureId');
        const studentType = document.getElementById('studentType');
        const enrollmentSteps = document.getElementById('enrollmentSteps');
        const enrollmentProcedureOrder = document.getElementById('enrollmentProcedureOrder');
        const closeEnrollmentProcedureModal = document.getElementById('closeEnrollmentProcedureModal');
        const cancelEnrollmentProcedureBtn = document.getElementById('cancelEnrollmentProcedureBtn');
        
        // Non-Readmission Modal
        const nonReadmissionModal = document.getElementById('nonReadmissionModal');
        const nonReadmissionModalTitle = document.getElementById('nonReadmissionModalTitle');
        const nonReadmissionForm = document.getElementById('nonReadmissionForm');
        const nonReadmissionId = document.getElementById('nonReadmissionId');
        const nonReadmissionDescription = document.getElementById('nonReadmissionDescription');
        const nonReadmissionOrder = document.getElementById('nonReadmissionOrder');
        const closeNonReadmissionModal = document.getElementById('closeNonReadmissionModal');
        const cancelNonReadmissionBtn = document.getElementById('cancelNonReadmissionBtn');
        
        // Add Student Type Button
        const addStudentTypeBtn = document.getElementById('add-student-type-btn');
        if (addStudentTypeBtn) {
            addStudentTypeBtn.addEventListener('click', function() {
                studentTypeModalTitle.textContent = 'Add Student Type';
                studentTypeId.value = '0';
                studentTypeForm.reset();
                openModal(studentTypeModal);
            });
        }
        
        // Edit Student Type Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-student-type-btn') || 
                e.target.parentElement.classList.contains('edit-student-type-btn')) {
                
                const btn = e.target.classList.contains('edit-student-type-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.type-card');
                const id = card.dataset.id;
                
                // Fetch student type data via AJAX
                fetch(`admissions-process.php?action=get_student_type&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            studentTypeModalTitle.textContent = 'Edit Student Type';
                            studentTypeId.value = data.data.id;
                            studentTypeName.value = data.data.name;
                            studentTypeRequirements.value = data.data.requirements;
                            studentTypeOrder.value = data.data.display_order;
                            openModal(studentTypeModal);
                        } else {
                            alert('Failed to load student type data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading student type data');
                    });
            }
        });
        
        // Delete Student Type Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-student-type-btn') || 
                e.target.parentElement.classList.contains('delete-student-type-btn')) {
                
                const btn = e.target.classList.contains('delete-student-type-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.type-card');
                const id = card.dataset.id;
                const name = card.querySelector('h4').textContent;
                
                if (confirm(`Are you sure you want to delete the student type "${name}"?`)) {
                    window.location.href = `admissions-process.php?action=delete_student_type&id=${id}`;
                }
            }
        });
        
        // Add Age Requirement Button
        const addAgeRequirementBtn = document.getElementById('add-age-requirement-btn');
        if (addAgeRequirementBtn) {
            addAgeRequirementBtn.addEventListener('click', function() {
                ageRequirementModalTitle.textContent = 'Add Age Requirement';
                ageRequirementId.value = '0';
                ageRequirementForm.reset();
                openModal(ageRequirementModal);
            });
        }
        
        // Edit Age Requirement Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-age-requirement-btn') || 
                e.target.parentElement.classList.contains('edit-age-requirement-btn')) {
                
                const btn = e.target.classList.contains('edit-age-requirement-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.requirement-card');
                const id = card.dataset.id;
                
                // Fetch age requirement data via AJAX
                fetch(`admissions-process.php?action=get_age_requirement&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ageRequirementModalTitle.textContent = 'Edit Age Requirement';
                            ageRequirementId.value = data.data.id;
                            gradeLevel.value = data.data.grade_level;
                            ageRequirements.value = data.data.requirements;
                            ageRequirementOrder.value = data.data.display_order;
                            openModal(ageRequirementModal);
                        } else {
                            alert('Failed to load age requirement data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading age requirement data');
                    });
            }
        });
        
        // Delete Age Requirement Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-age-requirement-btn') || 
                e.target.parentElement.classList.contains('delete-age-requirement-btn')) {
                
                const btn = e.target.classList.contains('delete-age-requirement-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.requirement-card');
                const id = card.dataset.id;
                const name = card.querySelector('h4').textContent;
                
                if (confirm(`Are you sure you want to delete the age requirement for "${name}"?`)) {
                    window.location.href = `admissions-process.php?action=delete_age_requirement&id=${id}`;
                }
            }
        });
        
        // Add Enrollment Procedure Button
        const addEnrollmentProcedureBtn = document.getElementById('add-enrollment-procedure-btn');
        if (addEnrollmentProcedureBtn) {
            addEnrollmentProcedureBtn.addEventListener('click', function() {
                enrollmentProcedureModalTitle.textContent = 'Add Enrollment Procedure';
                enrollmentProcedureId.value = '0';
                enrollmentProcedureForm.reset();
                openModal(enrollmentProcedureModal);
            });
        }
        
        // Edit Enrollment Procedure Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-enrollment-procedure-btn') || 
                e.target.parentElement.classList.contains('edit-enrollment-procedure-btn')) {
                
                const btn = e.target.classList.contains('edit-enrollment-procedure-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.procedure-card');
                const id = card.dataset.id;
                
                // Fetch enrollment procedure data via AJAX
                fetch(`admissions-process.php?action=get_enrollment_procedure&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            enrollmentProcedureModalTitle.textContent = 'Edit Enrollment Procedure';
                            enrollmentProcedureId.value = data.data.id;
                            studentType.value = data.data.student_type;
                            enrollmentSteps.value = data.data.steps;
                            enrollmentProcedureOrder.value = data.data.display_order;
                            openModal(enrollmentProcedureModal);
                        } else {
                            alert('Failed to load enrollment procedure data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading enrollment procedure data');
                    });
            }
        });
        
        // Delete Enrollment Procedure Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-enrollment-procedure-btn') || 
                e.target.parentElement.classList.contains('delete-enrollment-procedure-btn')) {
                
                const btn = e.target.classList.contains('delete-enrollment-procedure-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.procedure-card');
                const id = card.dataset.id;
                
                if (confirm(`Are you sure you want to delete this enrollment procedure?`)) {
                    window.location.href = `admissions-process.php?action=delete_enrollment_procedure&id=${id}`;
                }
            }
        });
        
        // Add Non-Readmission Ground Button
        const addNonReadmissionBtn = document.getElementById('add-non-readmission-btn');
        if (addNonReadmissionBtn) {
            addNonReadmissionBtn.addEventListener('click', function() {
                nonReadmissionModalTitle.textContent = 'Add Non-Readmission Ground';
                nonReadmissionId.value = '0';
                nonReadmissionForm.reset();
                openModal(nonReadmissionModal);
            });
        }
        
        // Edit Non-Readmission Ground Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-non-readmission-btn') || 
                e.target.parentElement.classList.contains('edit-non-readmission-btn')) {
                
                const btn = e.target.classList.contains('edit-non-readmission-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.ground-card');
                const id = card.dataset.id;
                
                // Fetch non-readmission ground data via AJAX
                fetch(`admissions-process.php?action=get_non_readmission&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            nonReadmissionModalTitle.textContent = 'Edit Non-Readmission Ground';
                            nonReadmissionId.value = data.data.id;
                            nonReadmissionDescription.value = data.data.description;
                            nonReadmissionOrder.value = data.data.display_order;
                            openModal(nonReadmissionModal);
                        } else {
                            alert('Failed to load non-readmission ground data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading non-readmission ground data');
                    });
            }
        });
        
        // Delete Non-Readmission Ground Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-non-readmission-btn') || 
                e.target.parentElement.classList.contains('delete-non-readmission-btn')) {
                
                const btn = e.target.classList.contains('delete-non-readmission-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.ground-card');
                const id = card.dataset.id;
                
                if (confirm(`Are you sure you want to delete this non-readmission ground?`)) {
                    window.location.href = `admissions-process.php?action=delete_non_readmission&id=${id}`;
                }
            }
        });
        
        // Close Modal Buttons
        [closeStudentTypeModal, cancelStudentTypeBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', function() {
                    closeModal(studentTypeModal);
                });
            }
        });
        
        [closeAgeRequirementModal, cancelAgeRequirementBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', function() {
                    closeModal(ageRequirementModal);
                });
            }
        });
        
        [closeEnrollmentProcedureModal, cancelEnrollmentProcedureBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', function() {
                    closeModal(enrollmentProcedureModal);
                });
            }
        });
        
        [closeNonReadmissionModal, cancelNonReadmissionBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', function() {
                    closeModal(nonReadmissionModal);
                });
            }
        });
        
        // Modal Utility Functions
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
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === studentTypeModal) closeModal(studentTypeModal);
            if (e.target === ageRequirementModal) closeModal(ageRequirementModal);
            if (e.target === enrollmentProcedureModal) closeModal(enrollmentProcedureModal);
            if (e.target === nonReadmissionModal) closeModal(nonReadmissionModal);
        });
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Admissions Management';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>