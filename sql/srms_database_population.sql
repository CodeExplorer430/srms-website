-- St. Raphaela Mary School Website Database Population Script
-- MySQL/MariaDB Script for creating mock data

USE srms_database;

-- ------------------------------------
-- Populate Users Table
-- ------------------------------------
INSERT INTO `users` (`username`, `password`, `email`, `role`, `active`, `last_login`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@srms.edu.ph', 'admin', TRUE, NOW()),
('content_editor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'content@srms.edu.ph', 'editor', TRUE, NOW()),
('jidello', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jidello@srms.edu.ph', 'editor', TRUE, NOW()),
('jquevedo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jquevedo@srms.edu.ph', 'admin', TRUE, NOW());

-- ------------------------------------
-- Populate School Information Table
-- ------------------------------------
INSERT INTO `school_information` (`name`, `logo`, `mission`, `vision`, `philosophy`, `email`, `phone`, `address`) VALUES
('ST. RAPHAELA MARY SCHOOL', '/images/SchoolLogo2.png', 
'Inspired by the words of our school patroness, St. Raphaela Mary, to give our whole heart to God, SRMS is committed to:
1. Form the hearts with the love of Christ;
2. Form the minds with relevant academic programs and services;
3. Form the whole person with significant holistic development programs;
4. Form the community with meaningful and effective avenues for competence; and
5. Form the future with innovative technological advancement.',
'St. Raphaela Mary School with the support of its stakeholders is envisioned as a formative educational institution committed to form the hearts of the young by providing them academic excellence and meaningful Christian formation molding them to become competent, compassionate and Christ-centered servant leaders in the future.',
'At St. Raphaela Mary School, we believe in fostering the holistic development of each student, nurturing their intellectual, spiritual, moral, and social growth from preschool through senior high school. Our Catholic faith forms the cornerstone of our educational approach, providing a framework for critical thinking, ethical decision-making, and a commitment to service. We strive to cultivate academically excellent students who are also compassionate, responsible, and engaged citizens of the world.

Our educational process centers on the student, with teachers acting as mentors and guides, fostering a supportive and challenging learning environment. We equip students with the knowledge, skills, and values necessary to navigate the complexities of life, empowering them to become confident and creative problem-solvers. We believe in providing a safe and inclusive community where every individual feels valued and respected, enabling them to reach their full potential.

Through a rich and diverse curriculum, we aim to inspire a lifelong love of learning and a commitment to personal growth. We encourage students to explore their talents, pursue their passions, and develop a strong sense of self-awareness and purpose. Ultimately, our goal is to graduate students who are not only academically prepared but also morally upright, spiritually grounded, and ready to contribute meaningfully to society.',
'srmseduc@gmail.com', 
'8253-3801/0920 832 7705', 
'#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City');

-- ------------------------------------
-- Populate Pages Table
-- ------------------------------------
INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_by`) VALUES
('Home', 'home', '<section class="enr"><h6>ST. RAPHAELA MARY SCHOOL</h6><p>Welcome Raphaelians!</p></section>', 'SRMS - Home', 'Welcome to the official website of St. Raphaela Mary School', 'published', 1),
('About SRMS', 'about', '<div class="about-header"><h2>About SMRS</h2></div><h3>Welcome to St. Raphaela Mary School!</h3>', 'About SRMS', 'Learn about the history, mission, vision, and philosophy of St. Raphaela Mary School', 'published', 1),
('Admissions', 'admissions', '<section class="header"><div class="line-title1"><p>ST. RAPHAELA MARY SCHOOL</p></div><div class="line-title2"><h1>ENROLLMENT POLICIES AND PROCEDURES</h1></div></section>', 'Admissions - SRMS', 'Information about enrollment policies, procedures, and requirements at St. Raphaela Mary School', 'published', 1),
('Contact', 'contact', '<section><h1>CONTACT US</h1></section>', 'Contact SRMS', 'Get in touch with St. Raphaela Mary School for inquiries or concerns', 'published', 1),
('Faculty and Staff', 'faculty', '<section class="main-head"><h1>FACULTY AND PERSONNEL ROSTER</h1></section>', 'Faculty and Staff - SRMS', 'Meet the dedicated faculty and staff of St. Raphaela Mary School', 'published', 1),
('Senior High School', 'senior-high', '<div class="lvl-grade"><h1>Senior High School</h1></div>', 'Senior High School - SRMS', 'Information about the Senior High School program at St. Raphaela Mary School', 'published', 1),
('News', 'news', '<section class="main-title"><h2>SMRS NEWS UPDATES</h2></section>', 'News and Updates - SRMS', 'Stay updated with the latest news and announcements from St. Raphaela Mary School', 'published', 1);

-- ------------------------------------
-- Populate Navigation Table
-- ------------------------------------
INSERT INTO `navigation` (`name`, `url`, `parent_id`, `display_order`, `is_active`) VALUES
('HOME', '/Home_School_index.html', NULL, 1, TRUE),
('ADMISSIONS', '/Admission_School_index.html', NULL, 2, TRUE),
('ABOUT SRMS', '/About_School_index.html', NULL, 3, TRUE),
('ACADEMICS', '#', NULL, 4, TRUE),
('NEWS', '/News_School_index.html', NULL, 5, TRUE),
('CONTACT', '/Contact_School_index.html', NULL, 6, TRUE),
('ALUMNI', '/Alumni_School_index.html', 3, 1, TRUE),
('FACULTY', '/Faculty_and_Staff_School_index.html', 3, 2, TRUE),
('PRESCHOOL', '#', 4, 1, TRUE),
('ELEMENTARY', '#', 4, 2, TRUE),
('JUNIOR HIGH', '#', 4, 3, TRUE),
('SENIOR HIGH', '/Academic_SH_School_index.html', 4, 4, TRUE);

-- ------------------------------------
-- Populate Faculty Categories Table
-- ------------------------------------
INSERT INTO `faculty_categories` (`name`, `display_order`) VALUES
('School Administration', 1),
('Coordinators & Specialists', 2),
('Teachers', 3),
('Student Services & Support Staff', 4),
('Finance & Administrative Staff', 5),
('Maintenance & Security Personnel', 6);

-- ------------------------------------
-- Populate Faculty Table
-- ------------------------------------
INSERT INTO `faculty` (`name`, `position`, `category_id`, `qualifications`, `display_order`) VALUES
('Ms. Juna C. Quevedo', 'School Directress', 1, '', 1),
('Mr. Julius I. Idello, LPT, MA', 'Principal', 1, 'LPT, MA', 2),
('Mr. Ronan T. Paguntalan, LPT', 'Vice Principal', 1, 'LPT', 3),
('Ms. Jeanalyn L. Sangatanan, LPT', 'Academic Coordinator', 1, 'LPT', 4),
('Ms. Jessa A. Ginga, LPT', 'Team Leader & Grade School Coordinator', 1, 'LPT', 5),
('Mr. Brando H. Bernardino, LPT', 'Student Activity Coordinator', 2, 'LPT', 1),
('Ms. Jelyn G. Suicon, LPT', 'Junior High School Coordinator', 2, 'LPT', 2),
('Mr. Joel F. Tobias, LPT', 'Sports Coordinator', 2, 'LPT', 3),
('Mr. Jherryl D. Arangorin', 'IT Specialist and Senior High School Coordinator', 2, '', 4),
('Ms. Catherine T. Abaño', 'Teacher', 3, '', 1),
('Ms. Celia L. Bulan, LPT', 'Teacher', 3, 'LPT', 2),
('Ms. Nica Joy G. Galimba, LPT', 'Teacher', 3, 'LPT', 3),
('Ms. Ynissa R. Magnawa, LPT', 'Teacher', 3, 'LPT', 4),
('Ms. Nicole D. Fedillaga', 'Teacher', 3, '', 5),
('Ms. Via Bhebs C. Danielles, LPT', 'Teacher', 3, 'LPT', 6),
('Ms. Kristine Mae U. Catindig', 'Teacher', 3, '', 7),
('Ms. Mariden P. Catampongan, LPT', 'Teacher', 3, 'LPT', 8),
('Mr. Jimmy F. Gordora, Jr., LPT', 'Teacher', 3, 'LPT', 9),
('Mr. Anthony A. Belardo, LPT', 'Teacher-Librarian', 3, 'LPT', 10),
('Mr. Alvin F. Palma', 'Guidance Associate', 4, '', 1),
('Ms. Myrna P. Arevalo, RN', 'Registrar/Nurse', 4, 'RN', 2),
('Ms. Joevelyn B. Benbinuto', 'Teacher-Aide', 4, '', 3),
('Ms. Joelita C. Quevedo', 'Finance Officer', 5, '', 1),
('Ms. Mariela Bartolay', 'Cashier', 5, '', 2),
('Mr. Rommel Romero', 'Security & Maintenance Personnel', 6, '', 1),
('Mr. Jayson Marino', 'Maintenance Personnel', 6, '', 2);

-- ------------------------------------
-- Populate News Table
-- ------------------------------------
INSERT INTO `news` (`title`, `slug`, `content`, `summary`, `image`, `published_date`, `author_id`, `status`, `featured`) VALUES
('SENIOR HIGH IS FREE!', 'senior-high-is-free', 'HERE IN ST. RAPHAELA MARY SCHOOL, SENIOR HIGH IS FREE! \n\nYes! whether you are from a PUBLIC school or a PRIVATE school, when you enroll here in SRMS for SENIOR HIGH, it\'s FREE!\n\nPlus, incoming Grade 11 students from public schools will receive FREE school and P.E. uniform with FREE ID with lace and CASH incentive!\n\nWhat are you waiting for? Enroll now and be part of a community that will give you the best academic experience ever.\n\nENROLL NOW!\nTalk to us: 8253-3801 or 09208327705.\nEmail us: srmseduc@gmail.com\nVisit the official Facebook page: srms.page\nRegister online:\nClick the link: : https://bit.ly/SRMSEnroll_SY2025-2026', 
'HERE IN ST. RAPHAELA MARY SCHOOL, SENIOR HIGH IS FREE!', 
'/images/School_offer_Picture2.jpg', 
'2025-04-04 23:05:00', 
1, 'published', TRUE),

('First Friday Mass Reminder', 'first-friday-mass-reminder', 'Tomorrow is our First Friday Mass, and to make this event meaningful, kindly follow our regular time-in at 7:20am to prepare for our encounter with God.\n\nPlease do bring school supplies for the Mayflower catechism!\n\nThank you for your generosity, and God bless!\n\nSee you, Raphaelians!', 
'Listen up, Raphaelians!', 
'/images/School_Annoucement6.jpg', 
'2025-04-03 18:39:00', 
1, 'published', FALSE),

('Last Club Meeting Announcement', 'last-club-meeting', 'Heads up, Raphaelians, It\'s our LAST Club Meeting day tomorrow, April 2 from 8:30AM - 11AM and all are expected to come to a celebration of skills and talents!\n\nExciting activities are in store for you!\n\nSo see you tomorrow, Raphaelians!', 
'Heads up, Raphaelians, It\'s our LAST Club Meeting day tomorrow, April 2 from 8:30AM - 11AM', 
'/images/School_Annoucement7.jpg', 
'2025-04-01 17:43:00', 
1, 'published', FALSE),

('Support Mr. and Ms. SRMS Candidates', 'support-candidates', 'LET\'S SUPPORT OUR CANDIDATES FOR MR. AND MS. & LITTLE MR. AND MS. SRMS 2025, RAPHAELIANS!\n\nREAD THE POST BELOW TO KNOW HOW?', 
'LET\'S SUPPORT OUR CANDIDATES FOR MR. AND MS. & LITTLE MR. AND MS. SRMS 2025, RAPHAELIANS!', 
'/images/School_Events1.jpg', 
'2025-03-11 18:06:00', 
1, 'published', FALSE),

('Club Meeting Reminder', 'club-meeting-reminder', 'Hello, Raphaelians!\n\nPlease be reminded of our club meeting tomorrow, March 12, 2025\nPlease bring the following: extra shirt, bottled water, towel, and snacks.\nFor the guitarist club, kindly bring your guitar.\nThank you, and God bless!', 
'Hello, Raphaelians!', 
'/images/School_Announcement1.jpg', 
'2025-03-11 16:18:00', 
1, 'published', FALSE),

('Mr. and Ms. SRMS Nationalism Theme', 'mr-ms-srms-nationalism', 'MR. AND MS. SRMS & LITTLE MR. AND MS. SRMS 2025 VOICE OUT NATIONALISM\n\nThe Mr. and Ms. SRMS & Little Mr. and Ms. SRMS is more than simply a fundraising activity for the school; it is also a tool for developing our young people\'s social consciousness and a forum for them to share their thoughts on urgent issues affecting their generation. Thus, they become ambassadors of hope for our society.\n\nFrom advocates of peace last year to agents of nationalism, the flagship of our candidates for the Mr. and Ms. SRMS & Little Mr. and Ms. SRMS this year with the theme, \'Piliin ang Pilipinas.\'.\n\nA timely and relevant theme to remind us that we are a nation blessed with rich culture, stunning nature, and beautiful people. Our country is God\'s gift to us, and there is a need to protect and preserve its grandeur so that we can achieve the prosperity and progress that we deserve.\n\nOur dreams can only happen if we as a people would make a stand to choose our country more, setting aside our differences and personal interests, which hinder us from becoming the great nation we once were.\n\nWith this in mind, the twenty-two (22) candidates of our school-based pageant will hopefully inspire you to love our country more and uphold our culture, reflected in the different symbols that they will creatively embody in this journey.\n\nSupport our candidates in their quest to become the voice that tells us, Choose Philippines, piliin ang Pilipinas!', 
'MR. AND MS. SRMS & LITTLE MR. AND MS. SRMS 2025 VOICE OUT NATIONALISM', 
'/images/School_Events3.jpg', 
'2025-03-09 08:11:00', 
1, 'published', FALSE);

-- ------------------------------------
-- Populate Facilities Table
-- ------------------------------------
INSERT INTO `facilities` (`name`, `description`, `image`, `display_order`) VALUES
('LIBRARY', 'Our school library is a welcoming space designed to inspire lifelong learning. With a wide array of resources and services, it plays an essential role in supporting the academic and personal growth of every student.', '/images/School_Library.jpg', 1),
('GYMNASIUM', 'With top-tier facilities and a wide range of activities, our gymnasium is dedicated to fostering a passion for sports and wellness in all of our students. It\'s a place where students can grow stronger, work as a team, and develop skills that will last a lifetime.', '/images/School_Gymnasium.jpg', 2),
('CANTEEN', 'Our school canteen is not just about food; it\'s about creating a positive and healthy environment where students can enjoy nutritious meals, interact with friends, and recharge for the rest of their day.', '/images/School_Canteen.jpg', 3);

-- ------------------------------------
-- Populate Academic Levels Table
-- ------------------------------------
INSERT INTO `academic_levels` (`name`, `slug`, `display_order`) VALUES
('Preschool', 'preschool', 1),
('Elementary', 'elementary', 2),
('Junior High School', 'junior-high', 3),
('Senior High School', 'senior-high', 4);

-- ------------------------------------
-- Populate Academic Programs Table
-- ------------------------------------
INSERT INTO `academic_programs` (`level_id`, `name`, `description`, `display_order`) VALUES
(1, 'Nursery', 'Our Nursery program is designed for children 4 years old by August.', 1),
(1, 'Kindergarten', 'Our Kindergarten program is designed for children 5 years old on or before August.', 2),
(2, 'Elementary Education', 'Elementary education at St. Raphaela Mary School provides a strong foundation for academic excellence and character development.', 1),
(3, 'Junior High School', 'Our Junior High School program builds on the elementary foundation and prepares students for Senior High School.', 1),
(4, 'Senior High School', 'St. Raphaela Mary School offers a vibrant and holistic Senior High School program designed to develop well-rounded, academically driven, and socially responsible individuals. Our Senior High School curriculum is crafted to provide students with a strong foundation for both their future careers and higher education. Students are empowered to become global citizens, with an emphasis on values, character development, and academic excellence.', 1);

-- ------------------------------------
-- Populate Academic Tracks Table
-- ------------------------------------
INSERT INTO `academic_tracks` (`program_id`, `name`, `code`, `description`, `display_order`) VALUES
(5, 'Accountancy, Business and Management', 'ABM', 'The ABM strand prepares students for college courses in business and management.', 1),
(5, 'Humanities, and Social Sciences', 'HUMSS', 'The HUMSS strand is designed for students who intend to take up journalism, communication arts, liberal arts, education, and other social science-related courses in college.', 2),
(5, 'General Academic Strand', 'GAS', 'The GAS strand is for students who are still undecided on their college course and career path.', 3);

-- ------------------------------------
-- Populate Admission Policies Table
-- ------------------------------------
INSERT INTO `admission_policies` (`title`, `content`, `display_order`) VALUES
('Admission Policies', 'Admission is a privilege and not a right, and is discretionary upon the school, which is not charged with the legal responsibility of providing education to those who do not satisfy its admission requirements (Revised Manual of Regulations for Private Schools, Sec.117).\n\nPrivate schools have the right to impose other rules and regulations for the admission of students aside from the entrance examination.\n\nEvery school has a right to determine which applicants it shall accept for enrollment.  It has a right to judge the fitness of students seeking admission and re-admission.  A student\'s failure to satisfy the academic standard the school sets shall be a legal ground for its refusal to re-admit him.', 1);

-- ------------------------------------
-- Populate Student Types Table
-- ------------------------------------
INSERT INTO `student_types` (`name`, `requirements`, `display_order`) VALUES
('New Students', '1. Must have passed the previous year with at least 80% average and Conduct grade of 80%.\n2. Must submit:\n   a. Report Card\n   b. Good Moral Certificate\n   c. PSA Birth Certificate (original + photocopy)\n   d. Baptismal Certificate (if available)\n3. Must pass entrance exam and interview.', 1),
('Transferees', '1. Must fulfill the requirements stated in nos. 1-4 on new students.\n2. Must be a regular student, i.e., no back subjects.', 2),
('Old Students', '1. Must have passed all subjects with minimum 80%, including Conduct.', 3);

-- ------------------------------------
-- Populate Age Requirements Table
-- ------------------------------------
INSERT INTO `age_requirements` (`grade_level`, `requirements`, `display_order`) VALUES
('Nursery', '4 years old by August 2025', 1),
('Kindergarten', '5 years old on or before August 2025', 2),
('Grade 1', '1. Kinder completer or;\n2. PEPT Passer for Kinder Level or;\n3. 6 years old and above by August 2025 but not Kinder Completer who assessed Grade 1-ready as per ECD checklist may also pre-register (DO 47, s. 2016)', 3),
('Grade 7', '1. Grade 6 completer or;\n2. PEPT Passer for Grade 6 or;\n3. ALS A&E Elementary Passer', 4);

-- ------------------------------------
-- Populate Enrollment Procedures Table
-- ------------------------------------
INSERT INTO `enrollment_procedures` (`student_type`, `steps`, `display_order`) VALUES
('Old Students', '1. Present the Report Card at the at the Registrar\'s Office to get Learner\'s Registration Form.\n2. Check, update, complete and sign the details of the Learner\'s Registration Form.\n3. Give the accomplished Registration Form at the Registrar\'s Office.\n4. Proceed to Room 103 to order school uniform and books.\n5. Go to the cashier to pay the Tuition Fee, Books and Uniform.', 1),
('New Students', '1. Secure and fill out the Application Form at the Information Desk.\n2. Pay Entrance Examination Fee at the Cashier.\n3. Submit the Accomplished Application Form and Requirements to the Guidance Office.\n4. Take Entrance Examination.\n5. Wait for about 30 minutes for the Result.\n6. Undergo Principal\'s Interview. Once passed, the application will be endorsed to the Registrar\'s Office.\n7. Go to the Registrar\'s Office for Registration.\n8. Proceed to Room 103 to order school uniform and books.\n9. Go to the cashier to pay the Tuition Fee, Books and Uniform.', 2);

-- ------------------------------------
-- Populate Grounds for Non-Readmission Table
-- ------------------------------------
INSERT INTO `non_readmission_grounds` (`description`, `display_order`) VALUES
('Conduct grade below 75%', 1),
('Habitual late payments', 2),
('Involvement in fraternities/gangs', 3),
('Frequent unexcused absences (20% of school year)', 4),
('Unauthorized summer classes', 5),
('Accumulating 3 suspensions', 6),
('Poor academic/behavioral performance', 7);

-- ------------------------------------
-- Populate Slideshow Table
-- ------------------------------------
INSERT INTO `slideshow` (`image`, `caption`, `link`, `display_order`, `is_active`) VALUES
('/images/St.RaphaelaCoverP4.jpg', 'St. Raphaela Mary School Campus', NULL, 1, TRUE),
('/images/School_offer_Picture2.jpg', 'Free Senior High School Education', '/Admission_School_index.html', 2, TRUE),
('/images/St.Raphaela_Pic1.jpg', 'School Activities', NULL, 3, TRUE);

-- ------------------------------------
-- Populate School Goals Table
-- ------------------------------------
INSERT INTO `school_goals` (`title`, `description`, `display_order`) VALUES
('Faith Formation', 'To foster a deep and abiding faith in God, rooted in the teachings of the Christian Church, and to encourage students to live out their faith in their daily lives.', 1),
('Academic Excellence', 'To provide a challenging and stimulating academic environment that enables students to reach their full intellectual potential and develop a lifelong love of learning.', 2),
('Stakeholder Involvement', 'To actively involve parents, teachers, and the wider community in the educational process, creating a collaborative partnership that supports student success.', 3),
('Training Place of Competence', 'To equip students with the knowledge, skills, and values necessary to succeed in their chosen fields and to become responsible and contributing members of society.', 4),
('Holistic Development', 'To promote the intellectual, emotional, social, and spiritual development of each student, fostering their growth into well-rounded, compassionate, and responsible individuals.', 5);

-- ------------------------------------
-- Populate Contact Information Table
-- ------------------------------------
INSERT INTO `contact_information` (`address`, `phone`, `email`, `map_embed_code`) VALUES
('#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City', '8253-3801/0920 832 7705', 'srmseduc@gmail.com', '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.3913911856266!2d121.01485707385635!3d14.690444885806619!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b1337471c805%3A0xad9496dd342ff7be!2sST.%20RAPHAELA%20MARY%20SCHOOL!5e0!3m2!1sen!2sph!4v1742567836354!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>');

-- ------------------------------------
-- Populate Social Media Table
-- ------------------------------------
INSERT INTO `social_media` (`platform`, `url`, `icon`, `display_order`, `is_active`) VALUES
('Facebook', 'https://web.facebook.com/srms.page', 'bx bxl-facebook-circle', 1, TRUE),
('Instagram', '#', 'bx bxl-instagram-alt', 2, TRUE),
('Twitter', '#', 'bx bxl-twitter', 3, TRUE),
('LinkedIn', '#', 'bx bxl-linkedin-square', 4, TRUE);

-- ------------------------------------
-- Populate Contact Form Submissions Table (Sample Data)
-- ------------------------------------
INSERT INTO `contact_submissions` (`name`, `email`, `phone`, `subject`, `message`, `status`, `ip_address`) VALUES
('Maria Santos', 'maria.santos@example.com', '09123456789', 'Enrollment Inquiry', 'Hello, I would like to inquire about the enrollment process for my son who will be entering Grade 7 next school year. What are the requirements and deadlines? Thank you.', 'read', '192.168.1.1'),
('Juan Dela Cruz', 'juan.delacruz@example.com', '09987654321', 'Tuition Fee Inquiry', 'Good day! I would like to know the tuition fee for Senior High School. Is it true that it\'s free? Thank you.', 'replied', '192.168.1.2'),
('Ana Garcia', 'ana.garcia@example.com', '09456789123', 'School Tour Request', 'Hi! I am interested in enrolling my daughter to your school. Is it possible to schedule a school tour? Thank you very much.', 'new', '192.168.1.3');