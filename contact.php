<?php
$page_title = 'Contact Us';
$page_description = 'Get in touch with St. Raphaela Mary School. Contact information, location, and online inquiry form.';

include 'includes/header.php';
// Include the MailService class
require_once 'includes/MailService.php';

$db = db_connect();
$contact_info = $db->fetch_row("SELECT * FROM contact_information LIMIT 1");

// Form processing
$form_submitted = false;
$form_errors = [];
$form_success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_submitted = true;
    
    // Validate inputs
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if(empty($name)) {
        $form_errors[] = 'Name is required';
    }
    
    if(empty($email)) {
        $form_errors[] = 'Email is required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_errors[] = 'Please provide a valid email address';
    }
    
    if(empty($subject)) {
        $form_errors[] = 'Subject is required';
    }
    
    if(empty($message)) {
        $form_errors[] = 'Message is required';
    }
    
    // Process if no errors
    if(empty($form_errors)) {
        $name = $db->escape($name);
        $email = $db->escape($email);
        $phone = $db->escape($phone);
        $subject = $db->escape($subject);
        $message = $db->escape($message);
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $sql = "INSERT INTO contact_submissions (name, email, phone, subject, message, status, ip_address) 
                VALUES ('$name', '$email', '$phone', '$subject', '$message', 'new', '$ip')";
        
        if($db->query($sql)) {
            $form_success = true;
            
            // Send email notification using our MailService class
            $emailResult = MailService::sendContactNotification($name, $email, $phone, $subject, $message);
            
            // If email fails, log it but don't show error to user
            if (!$emailResult['success']) {
                error_log("Email notification failed: " . $emailResult['message']);
            }
        } else {
            $form_errors[] = 'An error occurred. Please try again later.';
        }
    }
}
?>

<section>
    <h1>CONTACT US</h1>
    <div class="grid-area">
        <div class="map-location">
            <?php echo $contact_info['map_embed_code']; ?>
        </div>

        <div class="contact-info">
            <ul>
                <li>
                    <i class='bx bxs-home'></i>
                    <p><?php echo $contact_info['address']; ?></p>
                </li>
                <li>
                    <i class='bx bxs-phone'></i>
                    <p><?php echo $contact_info['phone']; ?></p>
                </li>
                <li>
                    <i class='bx bxs-envelope'></i>
                    <p><?php echo $contact_info['email']; ?></p>
                </li>
                <li>
                    <i class='bx bxs-time'></i>
                    <p>Office Hours: Monday to Friday, 8:00 AM - 5:00 PM</p>
                </li>
            </ul>
        </div>
    </div>

    <div class="contact-form-container">
        <h2>SEND US A MESSAGE</h2>
        
        <?php if($form_submitted && $form_success): ?>
        <div class="success-message">
            <p>Thank you for your message! We will get back to you as soon as possible.</p>
        </div>
        <?php elseif($form_submitted && !empty($form_errors)): ?>
        <div class="error-message">
            <ul>
                <?php foreach($form_errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form class="contact-form" action="contact.php" method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Send Message</button>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if($form_submitted && $form_success): ?>
        // Get the form element
        const contactForm = document.querySelector('.contact-form');
        
        // Reset all form fields
        contactForm.reset();
        
        // Scroll to the success message for better visibility
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Set focus to the first form field after a short delay
            setTimeout(function() {
                const firstInput = contactForm.querySelector('input:not([type="submit"])');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 1000);
        }
        <?php endif; ?>
    });
</script>
</section>

<?php include 'includes/footer.php'; ?>