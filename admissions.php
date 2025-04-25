<?php
$page_title = 'Admissions';
$page_description = 'Information about enrollment policies, procedures, and requirements at St. Raphaela Mary School.';

include 'includes/header.php';

// Fetch admission data from database
$db = db_connect();
$admission_policies = $db->fetch_all("SELECT * FROM admission_policies ORDER BY display_order ASC");
$student_types = $db->fetch_all("SELECT * FROM student_types ORDER BY display_order ASC");
$age_requirements = $db->fetch_all("SELECT * FROM age_requirements ORDER BY display_order ASC");
$enrollment_procedures = $db->fetch_all("SELECT * FROM enrollment_procedures ORDER BY display_order ASC");
$non_readmission_grounds = $db->fetch_all("SELECT * FROM non_readmission_grounds ORDER BY display_order ASC");

// Get contact information for the enrollment section
$contact_info = $db->fetch_row("SELECT * FROM contact_information LIMIT 1");
?>

<section class="header">
    <div class="line-title1">
        <p>ST. RAPHAELA MARY SCHOOL</p>
    </div>
    <div class="line-title2">
        <h1>ENROLLMENT POLICIES AND PROCEDURES</h1>
    </div>
</section>

<section class="admission-policies">
    <div class="admis-header">
        ADMISSION POLICIES
    </div>
    <div class="details-sec1">
        <ol>
            <?php 
            // Split the content by line breaks and display as list items
            if (!empty($admission_policies)) {
                $policy_content = explode("\n", $admission_policies[0]['content']);
                foreach ($policy_content as $policy_item) {
                    if (!empty(trim($policy_item))) {
                        echo "<li>" . trim($policy_item) . "</li>";
                    }
                }
            }
            ?>
        </ol>
    </div>
</section>

<?php 
// Display student types (new students, transferees, old students)
foreach ($student_types as $type): 
    $section_class = strtolower(str_replace(' ', '-', $type['name']));
?>
<section class="<?php echo $section_class; ?>">
    <div class="<?php echo substr($section_class, 0, 2); ?>-header">
        <?php echo strtoupper($type['name']); ?>
    </div>
    <div class="details-sec<?php echo $type['display_order']; ?>">
        <?php 
        // Parse the requirements and format as ordered lists
        echo format_requirements_list($type['requirements']);
        ?>
    </div>
</section>
<?php endforeach; ?>

<section class="probation">
    <div class="pbt-header">
        PROBATION
    </div>
    <div class="details-sec5">
        <ol>
            <li>May be academic or conduct-related.</li>
            <li>Students with past failures or behavioral concerns may be admitted on probationary status, requiring:
                <ol>
                    <li>Minimum 80% in academics and conduct</li>
                    <li>Recommendation from the Academic/Discipline Committee</li>
                </ol>
            </li>
        </ol>

        <div class="pbt-box">
            <p>
                A quarterly evaluation will be conducted by the Committees on Academic Performance/Discipline to assess if the student had successfully met the condition/s imposed, which will mean that the student will be taken out of such status; if on the last quarter the student still had not met the academic/conduct requirement(s), a "C-" or its equivalent will be given and he/she shall be recommended for transfer without Certificate of Good Moral Character.
            </p>
        </div>
    </div>
</section>

<section class="gnr">
    <div class="gnr-header">
        GROUNDS FOR NON-READMISSION
    </div>
    <div class="details-sec5">
        <ol>
            <?php foreach ($non_readmission_grounds as $ground): ?>
                <li><?php echo $ground['description']; ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<hr class="hr1"> 

<section class="admission-requirements">
    <div class="aq-header">
        ADMISSION REQUIREMENTS
    </div>

    <div class="ad-header">
        Age Requirements
    </div>
    <hr class="hr2">

    <div class="age-req">
        <?php foreach ($age_requirements as $req): ?>
            <h4><?php echo $req['grade_level']; ?></h4>
            <?php echo format_requirements_list($req['requirements']); ?>
        <?php endforeach; ?>

        <div class="ad-header">
            Admission Requirements      
        </div>
        <hr class="hr2">

        <div class="adm-req">
            <p>New students are required to submit the following requirements upon admission:</p>
            <ol>
                <li>Original Report Card</li>
                <li>Certificate of Good Moral Character</li>
                <li>Photocopy of PSA Birth Certificate</li>
                <li>Baptismal Certificate (if available)</li>
            </ol>
        </div>
    </div>
</section>

<hr class="hr1">

<section class="enr-procedure">
    <div class="ep-header1">
        ENROLLMENT PROCEDURE
    </div>

    <?php foreach ($enrollment_procedures as $procedure): ?>
        <div class="ep-header2">
            FOR <?php echo strtoupper($procedure['student_type']); ?>
        </div>
        <?php echo format_requirements_list($procedure['steps']); ?>
    <?php endforeach; ?>
    
    <div class="online-enrollment">
        <h3>ONLINE ENROLLMENT OPTION</h3>
        <p>You can also register online for SY 2025-2026 by clicking the link below:</p>
        <div class="enroll-btn">
            <a href="https://bit.ly/SRMSEnroll_SY2025-2026" target="_blank">ENROLL ONLINE</a>
        </div>
        <p>For inquiries:</p>
        <ul>
            <li>Call: <?php echo $contact_info['phone']; ?></li>
            <li>Email: <?php echo $contact_info['email']; ?></li>
            <li>Visit our Facebook page: <a href="https://web.facebook.com/srms.page" target="_blank">srms.page</a></li>
        </ul>
    </div>
</section>

<?php include 'includes/footer.php'; ?>