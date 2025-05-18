-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: May 18, 2025 at 04:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `srms_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_levels`
--

CREATE TABLE `academic_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_levels`
--

INSERT INTO `academic_levels` (`id`, `name`, `slug`, `display_order`) VALUES
(1, 'Preschool', 'preschool', 1),
(2, 'Elementary', 'elementary', 2),
(3, 'Junior High School', 'junior-high', 3),
(4, 'Senior High School', 'senior-high', 4);

-- --------------------------------------------------------

--
-- Table structure for table `academic_programs`
--

CREATE TABLE `academic_programs` (
  `id` int(11) NOT NULL,
  `level_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_programs`
--

INSERT INTO `academic_programs` (`id`, `level_id`, `name`, `description`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Nursery', 'Our Nursery program is designed for children 4 years old by August.', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(2, 1, 'Kindergarten', 'Our Kindergarten program is designed for children 5 years old on or before August.', 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(3, 2, 'Elementary Education', 'Elementary education at St. Raphaela Mary School provides a strong foundation for academic excellence and character development.', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(4, 3, 'Junior High School', 'Our Junior High School program builds on the elementary foundation and prepares students for Senior High School.', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(5, 4, 'Senior High School', 'St. Raphaela Mary School offers a vibrant and holistic Senior High School program designed to develop well-rounded, academically driven, and socially responsible individuals. Our Senior High School curriculum is crafted to provide students with a strong foundation for both their future careers and higher education. Students are empowered to become global citizens, with an emphasis on values, character development, and academic excellence.', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `academic_tracks`
--

CREATE TABLE `academic_tracks` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_tracks`
--

INSERT INTO `academic_tracks` (`id`, `program_id`, `name`, `code`, `description`, `display_order`) VALUES
(1, 5, 'Accountancy, Business and Management', 'ABM', 'The ABM strand prepares students for college courses in business and management.', 1),
(2, 5, 'Humanities, and Social Sciences', 'HUMSS', 'The HUMSS strand is designed for students who intend to take up journalism, communication arts, liberal arts, education, and other social science-related courses in college.', 2),
(3, 5, 'General Academic Strand', 'GAS', 'The GAS strand is for students who are still undecided on their college course and career path.', 3);

-- --------------------------------------------------------

--
-- Table structure for table `admission_policies`
--

CREATE TABLE `admission_policies` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_policies`
--

INSERT INTO `admission_policies` (`id`, `title`, `content`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Admission Policies', 'Admission is a privilege and not a right, and is discretionary upon the school, which is not charged with the legal responsibility of providing education to those who do not satisfy its admission requirements (Revised Manual of Regulations for Private Schools, Sec.117).\n\nPrivate schools have the right to impose other rules and regulations for the admission of students aside from the entrance examination.\n\nEvery school has a right to determine which applicants it shall accept for enrollment.  It has a right to judge the fitness of students seeking admission and re-admission.  A student\'s failure to satisfy the academic standard the school sets shall be a legal ground for its refusal to re-admit him.', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `age_requirements`
--

CREATE TABLE `age_requirements` (
  `id` int(11) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `requirements` text NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `age_requirements`
--

INSERT INTO `age_requirements` (`id`, `grade_level`, `requirements`, `display_order`) VALUES
(1, 'Nursery', '4 years old by August 2025', 1),
(2, 'Kindergarten', '5 years old on or before August 2025', 2),
(3, 'Grade 1', '1. Kinder completer or;\n2. PEPT Passer for Kinder Level or;\n3. 6 years old and above by August 2025 but not Kinder Completer who assessed Grade 1-ready as per ECD checklist may also pre-register (DO 47, s. 2016)', 3),
(4, 'Grade 7', '1. Grade 6 completer or;\n2. PEPT Passer for Grade 6 or;\n3. ALS A&E Elementary Passer', 4);

-- --------------------------------------------------------

--
-- Table structure for table `contact_information`
--

CREATE TABLE `contact_information` (
  `id` int(11) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `map_embed_code` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_information`
--

INSERT INTO `contact_information` (`id`, `address`, `phone`, `email`, `map_embed_code`, `updated_at`) VALUES
(1, '#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City', '8253-3801/0920 832 7705', 'srmseduc@gmail.com', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.3913911856266!2d121.01485707385635!3d14.690444885806619!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b1337471c805%3A0xad9496dd342ff7be!2sST.%20RAPHAELA%20MARY%20SCHOOL!5e0!3m2!1sen!2sph!4v1742567836354!5m2!1sen!2sph\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', '2025-04-24 21:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_submissions`
--

INSERT INTO `contact_submissions` (`id`, `name`, `email`, `phone`, `subject`, `message`, `submission_date`, `status`, `ip_address`) VALUES
(1, 'Maria Santos', 'maria.santos@example.com', '09123456789', 'Enrollment Inquiry', 'Hello, I would like to inquire about the enrollment process for my son who will be entering Grade 7 next school year. What are the requirements and deadlines? Thank you.', '2025-04-24 21:11:15', 'read', '192.168.1.1'),
(2, 'Juan Dela Cruz', 'juan.delacruz@example.com', '09987654321', 'Tuition Fee Inquiry', 'Good day! I would like to know the tuition fee for Senior High School. Is it true that it\'s free? Thank you.', '2025-04-24 21:11:15', 'replied', '192.168.1.2'),
(3, 'Ana Garcia', 'ana.garcia@example.com', '09456789123', 'School Tour Request', 'Hi! I am interested in enrolling my daughter to your school. Is it possible to schedule a school tour? Thank you very much.', '2025-04-24 21:11:15', 'new', '192.168.1.3');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_procedures`
--

CREATE TABLE `enrollment_procedures` (
  `id` int(11) NOT NULL,
  `student_type` varchar(50) NOT NULL,
  `steps` text NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment_procedures`
--

INSERT INTO `enrollment_procedures` (`id`, `student_type`, `steps`, `display_order`) VALUES
(1, 'Old Students', '1. Present the Report Card at the at the Registrar\'s Office to get Learner\'s Registration Form.\n2. Check, update, complete and sign the details of the Learner\'s Registration Form.\n3. Give the accomplished Registration Form at the Registrar\'s Office.\n4. Proceed to Room 103 to order school uniform and books.\n5. Go to the cashier to pay the Tuition Fee, Books and Uniform.', 1),
(2, 'New Students', '1. Secure and fill out the Application Form at the Information Desk.\n2. Pay Entrance Examination Fee at the Cashier.\n3. Submit the Accomplished Application Form and Requirements to the Guidance Office.\n4. Take Entrance Examination.\n5. Wait for about 30 minutes for the Result.\n6. Undergo Principal\'s Interview. Once passed, the application will be endorsed to the Registrar\'s Office.\n7. Go to the Registrar\'s Office for Registration.\n8. Proceed to Room 103 to order school uniform and books.\n9. Go to the cashier to pay the Tuition Fee, Books and Uniform.', 2);

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `name`, `description`, `image`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'LIBRARY', 'Our school library is a welcoming space designed to inspire lifelong learning. With a wide array of resources and services, it plays an essential role in supporting the academic and personal growth of every student.', '/images/School_Library.jpg', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(2, 'GYMNASIUM', 'With top-tier facilities and a wide range of activities, our gymnasium is dedicated to fostering a passion for sports and wellness in all of our students. It\'s a place where students can grow stronger, work as a team, and develop skills that will last a lifetime.', '/images/School_Gymnasium.jpg', 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(3, 'CANTEEN', 'Our school canteen is not just about food; it\'s about creating a positive and healthy environment where students can enjoy nutritious meals, interact with friends, and recharge for the rest of their day.', '/images/School_Canteen.jpg', 3, '2025-04-24 21:11:15', '2025-04-24 21:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `qualifications` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `name`, `position`, `category_id`, `photo`, `qualifications`, `bio`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Ms. Juna C. Quevedo', 'School Directress', 1, NULL, '', NULL, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(2, 'Mr. Julius I. Idello, LPT, MA', 'Principal', 1, NULL, 'LPT, MA', NULL, 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(3, 'Mr. Ronan T. Paguntalan, LPT', 'Vice Principal', 1, NULL, 'LPT', NULL, 3, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(4, 'Ms. Jeanalyn L. Sangatanan, LPT', 'Academic Coordinator', 1, NULL, 'LPT', NULL, 4, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(5, 'Ms. Jessa A. Ginga, LPT', 'Team Leader & Grade School Coordinator', 1, NULL, 'LPT', NULL, 5, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(6, 'Mr. Brando H. Bernardino, LPT', 'Student Activity Coordinator', 2, NULL, 'LPT', NULL, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(7, 'Ms. Jelyn G. Suicon, LPT', 'Junior High School Coordinator', 2, NULL, 'LPT', NULL, 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(8, 'Mr. Joel F. Tobias, LPT', 'Sports Coordinator', 2, NULL, 'LPT', NULL, 3, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(9, 'Mr. Jherryl D. Arangorin', 'IT Specialist and Senior High School Coordinator', 2, NULL, '', NULL, 4, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(10, 'Ms. Catherine T. Abaño', 'Teacher', 3, NULL, '', NULL, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(11, 'Ms. Celia L. Bulan, LPT', 'Teacher', 3, NULL, 'LPT', NULL, 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(12, 'Ms. Nica Joy G. Galimba, LPT', 'Teacher', 3, NULL, 'LPT', NULL, 3, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(13, 'Ms. Ynissa R. Magnawa, LPT', 'Teacher', 3, NULL, 'LPT', NULL, 4, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(14, 'Ms. Nicole D. Fedillaga', 'Teacher', 3, NULL, '', NULL, 5, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(15, 'Ms. Via Bhebs C. Danielles, LPT', 'Teacher', 3, NULL, 'LPT', NULL, 6, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(16, 'Ms. Kristine Mae U. Catindig', 'Teacher', 3, NULL, '', NULL, 7, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(17, 'Ms. Mariden P. Catampongan, LPT', 'Teacher', 3, NULL, 'LPT', NULL, 8, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(18, 'Mr. Jimmy F. Gordora, Jr., LPT', 'Teacher', 3, NULL, 'LPT', NULL, 9, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(19, 'Mr. Anthony A. Belardo, LPT', 'Teacher-Librarian', 3, NULL, 'LPT', NULL, 10, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(20, 'Mr. Alvin F. Palma', 'Guidance Associate', 4, NULL, '', NULL, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(21, 'Ms. Myrna P. Arevalo, RN', 'Registrar/Nurse', 4, NULL, 'RN', NULL, 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(22, 'Ms. Joevelyn B. Benbinuto', 'Teacher-Aide', 4, NULL, '', NULL, 3, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(23, 'Ms. Joelita C. Quevedo', 'Finance Officer', 5, NULL, '', NULL, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(24, 'Ms. Mariela Bartolay', 'Cashier', 5, NULL, '', NULL, 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(25, 'Mr. Rommel Romero', 'Security & Maintenance Personnel', 6, NULL, '', NULL, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(26, 'Mr. Jayson Marino', 'Maintenance Personnel', 6, NULL, '', NULL, 2, '2025-04-24 21:11:15', '2025-04-24 21:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_categories`
--

CREATE TABLE `faculty_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculty_categories`
--

INSERT INTO `faculty_categories` (`id`, `name`, `display_order`) VALUES
(1, 'School Administration', 1),
(2, 'Coordinators & Specialists', 2),
(3, 'Teachers', 3),
(4, 'Student Services & Support Staff', 4),
(5, 'Finance & Administrative Staff', 5),
(6, 'Maintenance & Security Personnel', 6);

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('image','document','video') NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `dimensions` varchar(20) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `navigation`
--

CREATE TABLE `navigation` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `navigation`
--

INSERT INTO `navigation` (`id`, `name`, `url`, `parent_id`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'HOME', '/index.php', NULL, 1, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59'),
(2, 'ADMISSIONS', '/admissions.php', NULL, 2, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59'),
(3, 'ABOUT SRMS', '/about.php', NULL, 3, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59'),
(4, 'ACADEMICS', '#', NULL, 4, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(5, 'NEWS', '/news.php', NULL, 5, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59'),
(6, 'CONTACT', '/contact.php', NULL, 6, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59'),
(7, 'ALUMNI', '/alumni.php', 3, 1, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59'),
(8, 'FACULTY', '/faculty.php', 3, 2, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59'),
(9, 'PRESCHOOL', '#', 4, 1, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(10, 'ELEMENTARY', '#', 4, 2, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(11, 'JUNIOR HIGH', '#', 4, 3, 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(12, 'SENIOR HIGH', '/academics/senior-high.php', 4, 4, 1, '2025-04-24 21:11:15', '2025-04-24 23:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `summary` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `published_date` datetime NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `slug`, `content`, `summary`, `image`, `published_date`, `author_id`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(1, 'SENIOR HIGH IS FREE!', 'senior-high-is-free', 'HERE IN ST. RAPHAELA MARY SCHOOL, SENIOR HIGH IS FREE!\r\n\r\nYes! whether you are from a PUBLIC school or a PRIVATE school, when you enroll here in SRMS for SENIOR HIGH, it\'s FREE!\r\n\r\nPlus, incoming Grade 11 students from public schools will receive FREE school and P.E. uniform with FREE ID with lace and CASH incentive! What are you waiting for? Enroll now and be part of a community that will give you the best academic experience ever.\r\n\r\nENROLL NOW!\r\n\r\nTalk to us: 8253-3801 or 09208327705.\r\nEmail us: srmseduc@gmail.com\r\nVisit the official Facebook page: srms.page\r\n\r\nRegister online:\r\nClick the link: : https://bit.ly/SRMSEnroll_SY2025-2026', 'HERE IN ST. RAPHAELA MARY SCHOOL, SENIOR HIGH IS FREE!', '/assets/images/promotional/senior-high-free-1746261609.jpg', '2025-04-04 23:05:00', 1, 'published', 1, '2025-04-24 21:11:15', '2025-05-03 08:49:14'),
(2, 'First Friday Mass Reminder', 'first-friday-mass-reminder', 'Tomorrow is our First Friday Mass, and to make this event meaningful, kindly follow our regular time-in at 7:20am to prepare for our encounter with God.\r\n\r\nPlease do bring school supplies for the Mayflower catechism!\r\n\r\nThank you for your generosity, and God bless!\r\n\r\nSee you, Raphaelians!', 'Listen up, Raphaelians!', '/assets/images/news/announcement-06-1746261476.jpg', '2025-04-03 18:39:00', 1, 'published', 1, '2025-04-24 21:11:15', '2025-05-03 08:48:58'),
(3, 'Last Club Meeting Announcement', 'last-club-meeting', 'Heads up, Raphaelians, It\'s our LAST Club Meeting day tomorrow, April 2 from 8:30AM - 11AM and all are expected to come to a celebration of skills and talents! Exciting activities are in store for you!\r\n\r\nSo see you tomorrow, Raphaelians!', 'Heads up, Raphaelians, It\'s our LAST Club Meeting day tomorrow, April 2 from 8:30AM - 11AM', '/assets/images/news/announcement-07-1746261476.jpg', '2025-04-01 17:43:00', 1, 'published', 1, '2025-04-24 21:11:15', '2025-05-03 08:48:47'),
(4, 'Support Mr. and Ms. SRMS Candidates', 'support-candidates', 'LET\\\'S SUPPORT OUR CANDIDATES FOR MR. AND MS. & LITTLE MR. AND MS. SRMS 2025, RAPHAELIANS!\\r\\n\\r\\nREAD THE POST BELOW TO KNOW HOW?', 'LET\'S SUPPORT OUR CANDIDATES FOR MR. AND MS. & LITTLE MR. AND MS. SRMS 2025, RAPHAELIANS!', '/assets/images/events/event-01-1746261525.jpg', '2025-03-11 18:06:00', 1, 'published', 1, '2025-04-24 21:11:15', '2025-05-03 08:47:34'),
(5, 'Club Meeting Reminder', 'club-meeting-reminder', 'Hello, Raphaelians!\r\n\r\nPlease be reminded of our club meeting tomorrow, March 12, 2025\r\nPlease bring the following: extra shirt, bottled water, towel, and snacks.\r\nFor the guitarist club, kindly bring your guitar.\r\nThank you, and God bless!', 'Hello, Raphaelians!', '/assets/images/news/announcement-01-1746261476.jpg', '2025-03-11 16:18:00', 1, 'published', 1, '2025-04-24 21:11:15', '2025-05-03 08:47:00'),
(6, 'Mr. and Ms. SRMS Nationalism Theme', 'mr-ms-srms-nationalism', 'MR. AND MS. SRMS & LITTLE MR. AND MS. SRMS 2025 VOICE OUT NATIONALISM\r\n\r\nThe Mr. and Ms. SRMS & Little Mr. and Ms. SRMS is more than simply a fundraising activity for the school; it is also a tool for developing our young people\'s social consciousness and a forum for them to share their thoughts on urgent issues affecting their generation. Thus, they become ambassadors of hope for our society.\r\n\r\nFrom advocates of peace last year to agents of nationalism, the flagship of our candidates for the Mr. and Ms. SRMS & Little Mr. and Ms. SRMS this year with the theme, \'Piliin ang Pilipinas.\'.\r\n\r\nA timely and relevant theme to remind us that we are a nation blessed with rich culture, stunning nature, and beautiful people. Our country is God\'s gift to us, and there is a need to protect and preserve its grandeur so that we can achieve the prosperity and progress that we deserve.\r\n\r\nOur dreams can only happen if we as a people would make a stand to choose our country more, setting aside our differences and personal interests, which hinder us from becoming the great nation we once were.\r\n\r\nWith this in mind, the twenty-two (22) candidates of our school-based pageant will hopefully inspire you to love our country more and uphold our culture, reflected in the different symbols that they will creatively embody in this journey.\r\n\r\nSupport our candidates in their quest to become the voice that tells us, Choose Philippines, piliin ang Pilipinas!', 'MR. AND MS. SRMS & LITTLE MR. AND MS. SRMS 2025 VOICE OUT NATIONALISM', '/assets/images/events/event-03-1746261525.jpg', '2025-03-09 08:11:00', 1, 'published', 0, '2025-04-24 21:11:15', '2025-05-03 08:48:06'),
(16, 'Sample Article', 'sample-article', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc laoreet lacus quis ipsum accumsan eleifend. Aenean eleifend libero a velit tincidunt varius. Vivamus malesuada luctus sapien, eget ornare purus. Cras elementum metus pretium lacus lobortis, vitae sollicitudin ipsum molestie. Nulla ac iaculis metus. Aliquam erat volutpat. Vivamus lacus nulla, convallis sed dui in, maximus porttitor nisi. Nunc posuere, elit at consectetur faucibus, quam est vehicula justo, sed tincidunt nulla est non leo. Sed felis magna, placerat et rutrum quis, suscipit vel nulla. Curabitur tincidunt, elit placerat sodales fermentum, lorem leo sagittis nunc, non hendrerit ex lorem et odio. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed tellus orci, vehicula vitae odio quis, consectetur sagittis est. Praesent eget mattis risus. Mauris vitae pharetra metus.\r\n\r\nNullam volutpat lobortis nisl, nec congue tortor. Donec efficitur condimentum nibh. Cras viverra ut felis tempus commodo. Praesent sagittis massa aliquet suscipit bibendum. Duis nec gravida quam. Sed risus ex, congue sit amet urna sed, aliquam consequat orci. Aliquam lectus nisl, mattis posuere dui non, aliquam condimentum tellus. Sed ut consectetur neque. Phasellus rutrum justo et nisi commodo, ut mattis dolor consequat. Aliquam sodales justo vitae magna sagittis, ut condimentum mi dapibus. Vestibulum vitae rutrum ante. Nulla tristique faucibus velit, ut porta dolor sodales quis. In ac lorem maximus, laoreet purus ut, feugiat arcu. Vestibulum maximus, tellus non ullamcorper molestie, lorem nulla aliquet erat, eu ullamcorper turpis dolor id magna. Aliquam mattis nec est in congue. Phasellus vehicula nisi nulla, vitae dapibus ex sollicitudin ut.\r\n\r\nAenean efficitur, ante sit amet pellentesque tristique, magna erat tempus risus, vitae congue ipsum lectus sed nisi. Aliquam blandit efficitur nisi. Nullam id vulputate libero. Ut at mi blandit, convallis nisi nec, ultrices sem. Mauris hendrerit, ante et scelerisque lacinia, metus tortor sagittis lorem, at accumsan tellus arcu in ante. Pellentesque semper eros sed justo laoreet, at elementum risus pellentesque. Nulla sit amet vehicula sem. Praesent bibendum urna non lacus pharetra vestibulum. Aliquam eu erat eget est mollis ultrices. Nunc felis dui, pellentesque vel varius eu, volutpat ut urna. Morbi non metus eu risus pulvinar fermentum. Integer ut felis eu erat accumsan vestibulum eu nec nunc. Suspendisse id rutrum erat. Vestibulum pellentesque, nisi quis laoreet vulputate, enim massa tempor turpis, ut feugiat orci magna ut tortor. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Fusce vel magna lorem.\r\n\r\nVivamus ornare, massa quis scelerisque lobortis, sapien turpis gravida mauris, ut scelerisque lectus velit nec lectus. Donec bibendum tortor nulla, sed hendrerit sem iaculis ut. Curabitur dictum interdum turpis, feugiat consequat erat porttitor id. Cras mollis, sem nec pretium pellentesque, magna nibh tempus lorem, non maximus urna quam a dui. Aliquam egestas, lorem a convallis maximus, ante nunc iaculis nulla, nec convallis libero dolor nec urna. Fusce dictum blandit fermentum. Vivamus non vulputate libero, ut pharetra nunc.\r\n\r\nSed sit amet odio quis velit convallis pharetra sit amet a erat. Quisque tincidunt odio sed bibendum hendrerit. Praesent posuere, dolor at varius sagittis, quam tellus faucibus lectus, nec suscipit nisi eros ac neque. Nullam sodales, nisl eleifend rhoncus gravida, nibh arcu finibus ligula, non sagittis nisl elit lacinia ipsum. Nunc a enim eu arcu tincidunt consectetur. In dignissim id ligula ut imperdiet. Sed vel est eu dui feugiat maximus. Phasellus nec lectus non orci aliquet malesuada. Nullam tristique eleifend condimentum. Mauris posuere metus lectus, vitae dictum lacus viverra vitae.', 'Sample Article for Testing', '/assets/images/news/pexels-olly-834863-1747475794.jpg', '2025-05-17 22:01:00', 7, 'published', 0, '2025-05-17 14:02:17', '2025-05-17 14:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `non_readmission_grounds`
--

CREATE TABLE `non_readmission_grounds` (
  `id` int(11) NOT NULL,
  `description` text NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `non_readmission_grounds`
--

INSERT INTO `non_readmission_grounds` (`id`, `description`, `display_order`) VALUES
(1, 'Conduct grade below 75%', 1),
(2, 'Habitual late payments', 2),
(3, 'Involvement in fraternities/gangs', 3),
(4, 'Frequent unexcused absences (20% of school year)', 4),
(5, 'Unauthorized summer classes', 5),
(6, 'Accumulating 3 suspensions', 6),
(7, 'Poor academic/behavioral performance', 7);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(100) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Home', 'home', '<section class=\"enr\"><h6>ST. RAPHAELA MARY SCHOOL</h6><p>Welcome Raphaelians!</p></section>', 'SRMS - Home', 'Welcome to the official website of St. Raphaela Mary School', 'published', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(2, 'About SRMS', 'about', '<div class=\"about-header\"><h2>About SMRS</h2></div><h3>Welcome to St. Raphaela Mary School!</h3>', 'About SRMS', 'Learn about the history, mission, vision, and philosophy of St. Raphaela Mary School', 'published', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(3, 'Admissions', 'admissions', '<section class=\"header\"><div class=\"line-title1\"><p>ST. RAPHAELA MARY SCHOOL</p></div><div class=\"line-title2\"><h1>ENROLLMENT POLICIES AND PROCEDURES</h1></div></section>', 'Admissions - SRMS', 'Information about enrollment policies, procedures, and requirements at St. Raphaela Mary School', 'published', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(4, 'Contact', 'contact', '<section><h1>CONTACT US</h1></section>', 'Contact SRMS', 'Get in touch with St. Raphaela Mary School for inquiries or concerns', 'published', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(5, 'Faculty and Staff', 'faculty', '<section class=\"main-head\"><h1>FACULTY AND PERSONNEL ROSTER</h1></section>', 'Faculty and Staff - SRMS', 'Meet the dedicated faculty and staff of St. Raphaela Mary School', 'published', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(6, 'Senior High School', 'senior-high', '<div class=\"lvl-grade\"><h1>Senior High School</h1></div>', 'Senior High School - SRMS', 'Information about the Senior High School program at St. Raphaela Mary School', 'published', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(7, 'News', 'news', '<section class=\"main-title\"><h2>SMRS NEWS UPDATES</h2></section>', 'News and Updates - SRMS', 'Stay updated with the latest news and announcements from St. Raphaela Mary School', 'published', 1, '2025-04-24 21:11:15', '2025-04-24 21:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `school_goals`
--

CREATE TABLE `school_goals` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_goals`
--

INSERT INTO `school_goals` (`id`, `title`, `description`, `display_order`) VALUES
(1, 'Faith Formation', 'To foster a deep and abiding faith in God, rooted in the teachings of the Christian Church, and to encourage students to live out their faith in their daily lives.', 1),
(2, 'Academic Excellence', 'To provide a challenging and stimulating academic environment that enables students to reach their full intellectual potential and develop a lifelong love of learning.', 2),
(3, 'Stakeholder Involvement', 'To actively involve parents, teachers, and the wider community in the educational process, creating a collaborative partnership that supports student success.', 3),
(4, 'Training Place of Competence', 'To equip students with the knowledge, skills, and values necessary to succeed in their chosen fields and to become responsible and contributing members of society.', 4),
(5, 'Holistic Development', 'To promote the intellectual, emotional, social, and spiritual development of each student, fostering their growth into well-rounded, compassionate, and responsible individuals.', 5);

-- --------------------------------------------------------

--
-- Table structure for table `school_information`
--

CREATE TABLE `school_information` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `philosophy` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_information`
--

INSERT INTO `school_information` (`id`, `name`, `logo`, `mission`, `vision`, `philosophy`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'St. Raphaela Mary School', '/assets/images/branding/logo-primary-1747496572.png', 'Inspired by the words of our school patroness, St. Raphaela Mary, to give our whole heart to God, SRMS is committed to:\r\n\r\nForm the hearts with the love of Christ;\r\nForm the minds with relevant academic programs and services;\r\nForm the whole person with significant holistic development programs;\r\nForm the community with meaningful and effective avenues for competence; and\r\nForm the future with innovative technological advancement.', 'St. Raphaela Mary School with the support of its stakeholders is envisioned as a formative educational institution committed to form the hearts of the young by providing them academic excellence and meaningful Christian formation molding them to become competent, compassionate and Christ-centered servant leaders in the future.', 'At St. Raphaela Mary School, we believe in fostering the holistic development of each student, nurturing their intellectual, spiritual, moral, and social growth from preschool through senior high school. Our Catholic faith forms the cornerstone of our educational approach, providing a framework for critical thinking, ethical decision-making, and a commitment to service. We strive to cultivate academically excellent students who are also compassionate, responsible, and engaged citizens of the world.\r\n\r\nOur educational process centers on the student, with teachers acting as mentors and guides, fostering a supportive and challenging learning environment. We equip students with the knowledge, skills, and values necessary to navigate the complexities of life, empowering them to become confident and creative problem-solvers. We believe in providing a safe and inclusive community where every individual feels valued and respected, enabling them to reach their full potential.\r\n\r\nThrough a rich and diverse curriculum, we aim to inspire a lifelong love of learning and a commitment to personal growth. We encourage students to explore their talents, pursue their passions, and develop a strong sense of self-awareness and purpose. Ultimately, our goal is to graduate students who are not only academically prepared but also morally upright, spiritually grounded, and ready to contribute meaningfully to society.', 'srmseduc@gmail.com', '8253-3801/0920 832 7705', '#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City', '2025-04-24 21:11:15', '2025-05-17 15:45:08');

-- --------------------------------------------------------

--
-- Table structure for table `slideshow`
--

CREATE TABLE `slideshow` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `slideshow`
--

INSERT INTO `slideshow` (`id`, `image`, `caption`, `link`, `display_order`, `is_active`) VALUES
(1, '/assets/images/campus/hero-main.jpg', 'St. Raphaela Mary School Campus', NULL, 1, 1),
(2, '/assets/images/promotional/senior-high-free.jpg', 'Free Senior High School Education', 'admissions.php', 2, 1),
(3, '/assets/images/campus/overview.jpg', 'School Activities', NULL, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `social_media`
--

CREATE TABLE `social_media` (
  `id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_media`
--

INSERT INTO `social_media` (`id`, `platform`, `url`, `icon`, `display_order`, `is_active`) VALUES
(1, 'Facebook', 'https://web.facebook.com/srms.page', 'bx bxl-facebook-circle', 1, 1),
(2, 'Instagram', '#', 'bx bxl-instagram-alt', 2, 1),
(3, 'Twitter', '#', 'bx bxl-twitter', 3, 1),
(4, 'LinkedIn', '#', 'bx bxl-linkedin-square', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_types`
--

CREATE TABLE `student_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `requirements` text NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_types`
--

INSERT INTO `student_types` (`id`, `name`, `requirements`, `display_order`) VALUES
(1, 'New Students', '1. Must have passed the previous year with at least 80% average and Conduct grade of 80%.\n2. Must submit:\n   a. Report Card\n   b. Good Moral Certificate\n   c. PSA Birth Certificate (original + photocopy)\n   d. Baptismal Certificate (if available)\n3. Must pass entrance exam and interview.', 1),
(2, 'Transferees', '1. Must fulfill the requirements stated in nos. 1-4 on new students.\n2. Must be a regular student, i.e., no back subjects.', 2),
(3, 'Old Students', '1. Must have passed all subjects with minimum 80%, including Conduct.', 3);

-- --------------------------------------------------------

--
-- Table structure for table `submission_replies`
--

CREATE TABLE `submission_replies` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `reply_content` text NOT NULL,
  `reply_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','editor','content_manager') NOT NULL DEFAULT 'editor',
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@srms.edu.ph', 'admin', 1, '2025-04-25 05:11:15', '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(2, 'content_editor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'content@srms.edu.ph', 'editor', 1, '2025-04-25 05:11:15', '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(3, 'jidello', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jidello@srms.edu.ph', 'editor', 1, '2025-04-25 05:11:15', '2025-04-24 21:11:15', '2025-04-24 21:11:15'),
(4, 'jquevedo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jquevedo@srms.edu.ph', 'admin', 0, '2025-04-25 05:11:15', '2025-04-24 21:11:15', '2025-04-27 03:38:50'),
(7, 'admin1', '$2a$12$CD/0iENYIDhWcCE94OgGh.cvTWXGgoDuemxbwzeC9GhuJsXL6j/OC', 'admin1@srms.edu.ph', 'admin', 1, '2025-05-18 01:35:24', '2025-04-24 22:23:11', '2025-05-17 17:35:24'),
(9, 'rodneybagay', '$2y$10$o0fkR58GY6z3jZDKwDmLT.pVQiAVWwvmsBf987N1L3B4KBCtDZRE.', 'rodneybagay@srms.edu.ph', 'editor', 1, NULL, '2025-05-06 07:30:19', '2025-05-06 07:30:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_levels`
--
ALTER TABLE `academic_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `academic_programs`
--
ALTER TABLE `academic_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_academic_programs_level` (`level_id`);

--
-- Indexes for table `academic_tracks`
--
ALTER TABLE `academic_tracks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `admission_policies`
--
ALTER TABLE `admission_policies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `age_requirements`
--
ALTER TABLE `age_requirements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_information`
--
ALTER TABLE `contact_information`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrollment_procedures`
--
ALTER TABLE `enrollment_procedures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_faculty_category` (`category_id`);

--
-- Indexes for table `faculty_categories`
--
ALTER TABLE `faculty_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `navigation`
--
ALTER TABLE `navigation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_navigation_parent` (`parent_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_news_published_date` (`published_date`);

--
-- Indexes for table `non_readmission_grounds`
--
ALTER TABLE `non_readmission_grounds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `school_goals`
--
ALTER TABLE `school_goals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school_information`
--
ALTER TABLE `school_information`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `slideshow`
--
ALTER TABLE `slideshow`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `social_media`
--
ALTER TABLE `social_media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_types`
--
ALTER TABLE `student_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `submission_replies`
--
ALTER TABLE `submission_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_levels`
--
ALTER TABLE `academic_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `academic_programs`
--
ALTER TABLE `academic_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `academic_tracks`
--
ALTER TABLE `academic_tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admission_policies`
--
ALTER TABLE `admission_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `age_requirements`
--
ALTER TABLE `age_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_information`
--
ALTER TABLE `contact_information`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `enrollment_procedures`
--
ALTER TABLE `enrollment_procedures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `faculty_categories`
--
ALTER TABLE `faculty_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `navigation`
--
ALTER TABLE `navigation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `non_readmission_grounds`
--
ALTER TABLE `non_readmission_grounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `school_goals`
--
ALTER TABLE `school_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_information`
--
ALTER TABLE `school_information`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `slideshow`
--
ALTER TABLE `slideshow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `social_media`
--
ALTER TABLE `social_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_types`
--
ALTER TABLE `student_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submission_replies`
--
ALTER TABLE `submission_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_programs`
--
ALTER TABLE `academic_programs`
  ADD CONSTRAINT `academic_programs_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `academic_tracks`
--
ALTER TABLE `academic_tracks`
  ADD CONSTRAINT `academic_tracks_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `faculty_categories` (`id`);

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `navigation`
--
ALTER TABLE `navigation`
  ADD CONSTRAINT `navigation_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `navigation` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `submission_replies`
--
ALTER TABLE `submission_replies`
  ADD CONSTRAINT `submission_replies_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `contact_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_replies_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
