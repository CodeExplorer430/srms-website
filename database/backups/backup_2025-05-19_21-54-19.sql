-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: srms_database
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_levels`
--

DROP TABLE IF EXISTS `academic_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_levels`
--

LOCK TABLES `academic_levels` WRITE;
/*!40000 ALTER TABLE `academic_levels` DISABLE KEYS */;
INSERT INTO `academic_levels` VALUES (1,'Preschool','preschool',1),(2,'Elementary','elementary',2),(3,'Junior High School','junior-high',3),(4,'Senior High School','senior-high',4);
/*!40000 ALTER TABLE `academic_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_programs`
--

DROP TABLE IF EXISTS `academic_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_academic_programs_level` (`level_id`),
  CONSTRAINT `academic_programs_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_programs`
--

LOCK TABLES `academic_programs` WRITE;
/*!40000 ALTER TABLE `academic_programs` DISABLE KEYS */;
INSERT INTO `academic_programs` VALUES (1,1,'Nursery','Our Nursery program is designed for children 4 years old by August.',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(2,1,'Kindergarten','Our Kindergarten program is designed for children 5 years old on or before August.',2,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(3,2,'Elementary Education','Elementary education at St. Raphaela Mary School provides a strong foundation for academic excellence and character development.',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(4,3,'Junior High School','Our Junior High School program builds on the elementary foundation and prepares students for Senior High School.',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(5,4,'Senior High School','St. Raphaela Mary School offers a vibrant and holistic Senior High School program designed to develop well-rounded, academically driven, and socially responsible individuals. Our Senior High School curriculum is crafted to provide students with a strong foundation for both their future careers and higher education. Students are empowered to become global citizens, with an emphasis on values, character development, and academic excellence.',1,'2025-04-24 21:11:15','2025-04-24 21:11:15');
/*!40000 ALTER TABLE `academic_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_tracks`
--

DROP TABLE IF EXISTS `academic_tracks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `academic_tracks_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_tracks`
--

LOCK TABLES `academic_tracks` WRITE;
/*!40000 ALTER TABLE `academic_tracks` DISABLE KEYS */;
INSERT INTO `academic_tracks` VALUES (1,5,'Accountancy, Business and Management','ABM','The ABM strand prepares students for college courses in business and management.',1),(2,5,'Humanities, and Social Sciences','HUMSS','The HUMSS strand is designed for students who intend to take up journalism, communication arts, liberal arts, education, and other social science-related courses in college.',2),(3,5,'General Academic Strand','GAS','The GAS strand is for students who are still undecided on their college course and career path.',3);
/*!40000 ALTER TABLE `academic_tracks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_policies`
--

DROP TABLE IF EXISTS `admission_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_policies`
--

LOCK TABLES `admission_policies` WRITE;
/*!40000 ALTER TABLE `admission_policies` DISABLE KEYS */;
INSERT INTO `admission_policies` VALUES (1,'Admission Policies','Admission is a privilege and not a right, and is discretionary upon the school, which is not charged with the legal responsibility of providing education to those who do not satisfy its admission requirements (Revised Manual of Regulations for Private Schools, Sec.117).\n\nPrivate schools have the right to impose other rules and regulations for the admission of students aside from the entrance examination.\n\nEvery school has a right to determine which applicants it shall accept for enrollment.  It has a right to judge the fitness of students seeking admission and re-admission.  A student\'s failure to satisfy the academic standard the school sets shall be a legal ground for its refusal to re-admit him.',1,'2025-04-24 21:11:15','2025-04-24 21:11:15');
/*!40000 ALTER TABLE `admission_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `age_requirements`
--

DROP TABLE IF EXISTS `age_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `age_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_level` varchar(50) NOT NULL,
  `requirements` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `age_requirements`
--

LOCK TABLES `age_requirements` WRITE;
/*!40000 ALTER TABLE `age_requirements` DISABLE KEYS */;
INSERT INTO `age_requirements` VALUES (1,'Nursery','4 years old by August 2025',1),(2,'Kindergarten','5 years old on or before August 2025',2),(3,'Grade 1','1. Kinder completer or;\n2. PEPT Passer for Kinder Level or;\n3. 6 years old and above by August 2025 but not Kinder Completer who assessed Grade 1-ready as per ECD checklist may also pre-register (DO 47, s. 2016)',3),(4,'Grade 7','1. Grade 6 completer or;\n2. PEPT Passer for Grade 6 or;\n3. ALS A&E Elementary Passer',4);
/*!40000 ALTER TABLE `age_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_information`
--

DROP TABLE IF EXISTS `contact_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` text NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `map_embed_code` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_information`
--

LOCK TABLES `contact_information` WRITE;
/*!40000 ALTER TABLE `contact_information` DISABLE KEYS */;
INSERT INTO `contact_information` VALUES (1,'#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City','8253-3801/0920 832 7705','srmseduc@gmail.com','<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.3913911856266!2d121.01485707385635!3d14.690444885806619!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b1337471c805%3A0xad9496dd342ff7be!2sST.%20RAPHAELA%20MARY%20SCHOOL!5e0!3m2!1sen!2sph!4v1742567836354!5m2!1sen!2sph\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>','2025-04-24 21:11:15');
/*!40000 ALTER TABLE `contact_information` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_submissions`
--

DROP TABLE IF EXISTS `contact_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_submissions`
--

LOCK TABLES `contact_submissions` WRITE;
/*!40000 ALTER TABLE `contact_submissions` DISABLE KEYS */;
INSERT INTO `contact_submissions` VALUES (1,'Maria Santos','maria.santos@example.com','09123456789','Enrollment Inquiry','Hello, I would like to inquire about the enrollment process for my son who will be entering Grade 7 next school year. What are the requirements and deadlines? Thank you.','2025-04-24 21:11:15','read','192.168.1.1'),(2,'Juan Dela Cruz','juan.delacruz@example.com','09987654321','Tuition Fee Inquiry','Good day! I would like to know the tuition fee for Senior High School. Is it true that it\'s free? Thank you.','2025-04-24 21:11:15','replied','192.168.1.2'),(3,'Ana Garcia','ana.garcia@example.com','09456789123','School Tour Request','Hi! I am interested in enrolling my daughter to your school. Is it possible to schedule a school tour? Thank you very much.','2025-04-24 21:11:15','new','192.168.1.3');
/*!40000 ALTER TABLE `contact_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollment_procedures`
--

DROP TABLE IF EXISTS `enrollment_procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment_procedures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_type` varchar(50) NOT NULL,
  `steps` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollment_procedures`
--

LOCK TABLES `enrollment_procedures` WRITE;
/*!40000 ALTER TABLE `enrollment_procedures` DISABLE KEYS */;
INSERT INTO `enrollment_procedures` VALUES (1,'Old Students','1. Present the Report Card at the at the Registrar\'s Office to get Learner\'s Registration Form.\n2. Check, update, complete and sign the details of the Learner\'s Registration Form.\n3. Give the accomplished Registration Form at the Registrar\'s Office.\n4. Proceed to Room 103 to order school uniform and books.\n5. Go to the cashier to pay the Tuition Fee, Books and Uniform.',1),(2,'New Students','1. Secure and fill out the Application Form at the Information Desk.\n2. Pay Entrance Examination Fee at the Cashier.\n3. Submit the Accomplished Application Form and Requirements to the Guidance Office.\n4. Take Entrance Examination.\n5. Wait for about 30 minutes for the Result.\n6. Undergo Principal\'s Interview. Once passed, the application will be endorsed to the Registrar\'s Office.\n7. Go to the Registrar\'s Office for Registration.\n8. Proceed to Room 103 to order school uniform and books.\n9. Go to the cashier to pay the Tuition Fee, Books and Uniform.',2);
/*!40000 ALTER TABLE `enrollment_procedures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,'LIBRARY','Our school library is a welcoming space designed to inspire lifelong learning. With a wide array of resources and services, it plays an essential role in supporting the academic and personal growth of every student.','/assets/images/facilities/library.jpg',1,'2025-04-24 21:11:15','2025-05-18 16:24:01'),(2,'GYMNASIUM','With top-tier facilities and a wide range of activities, our gymnasium is dedicated to fostering a passion for sports and wellness in all of our students. It\'s a place where students can grow stronger, work as a team, and develop skills that will last a lifetime.','/assets/images/facilities/gymnasium.jpg',2,'2025-04-24 21:11:15','2025-05-18 16:24:13'),(3,'CANTEEN','Our school canteen is not just about food; it\'s about creating a positive and healthy environment where students can enjoy nutritious meals, interact with friends, and recharge for the rest of their day.','/assets/images/facilities/canteen.jpg',3,'2025-04-24 21:11:15','2025-05-18 16:24:23');
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculty`
--

DROP TABLE IF EXISTS `faculty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `qualifications` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_faculty_category` (`category_id`),
  CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `faculty_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculty`
--

LOCK TABLES `faculty` WRITE;
/*!40000 ALTER TABLE `faculty` DISABLE KEYS */;
INSERT INTO `faculty` VALUES (1,'Ms. Juna C. Quevedo','School Directress',1,NULL,'',NULL,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(2,'Mr. Julius I. Idello, LPT, MA','Principal',1,NULL,'LPT, MA',NULL,2,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(3,'Mr. Ronan T. Paguntalan, LPT','Vice Principal',1,NULL,'LPT',NULL,3,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(4,'Ms. Jeanalyn L. Sangatanan, LPT','Academic Coordinator',1,NULL,'LPT',NULL,4,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(5,'Ms. Jessa A. Ginga, LPT','Team Leader & Grade School Coordinator',1,NULL,'LPT',NULL,5,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(6,'Mr. Brando H. Bernardino, LPT','Student Activity Coordinator',2,NULL,'LPT',NULL,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(7,'Ms. Jelyn G. Suicon, LPT','Junior High School Coordinator',2,NULL,'LPT',NULL,2,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(8,'Mr. Joel F. Tobias, LPT','Sports Coordinator',2,NULL,'LPT',NULL,3,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(9,'Mr. Jherryl D. Arangorin','IT Specialist and Senior High School Coordinator',2,NULL,'',NULL,4,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(10,'Ms. Catherine T. Abaño','Teacher',3,NULL,'',NULL,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(11,'Ms. Celia L. Bulan, LPT','Teacher',3,NULL,'LPT',NULL,2,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(12,'Ms. Nica Joy G. Galimba, LPT','Teacher',3,NULL,'LPT',NULL,3,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(13,'Ms. Ynissa R. Magnawa, LPT','Teacher',3,NULL,'LPT',NULL,4,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(14,'Ms. Nicole D. Fedillaga','Teacher',3,NULL,'',NULL,5,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(15,'Ms. Via Bhebs C. Danielles, LPT','Teacher',3,NULL,'LPT',NULL,6,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(16,'Ms. Kristine Mae U. Catindig','Teacher',3,NULL,'',NULL,7,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(17,'Ms. Mariden P. Catampongan, LPT','Teacher',3,NULL,'LPT',NULL,8,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(18,'Mr. Jimmy F. Gordora, Jr., LPT','Teacher',3,NULL,'LPT',NULL,9,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(19,'Mr. Anthony A. Belardo, LPT','Teacher-Librarian',3,NULL,'LPT',NULL,10,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(20,'Mr. Alvin F. Palma','Guidance Associate',4,NULL,'',NULL,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(21,'Ms. Myrna P. Arevalo, RN','Registrar/Nurse',4,NULL,'RN',NULL,2,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(22,'Ms. Joevelyn B. Benbinuto','Teacher-Aide',4,NULL,'',NULL,3,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(23,'Ms. Joelita C. Quevedo','Finance Officer',5,NULL,'',NULL,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(24,'Ms. Mariela Bartolay','Cashier',5,NULL,'',NULL,2,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(25,'Mr. Rommel Romero','Security & Maintenance Personnel',6,NULL,'',NULL,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(26,'Mr. Jayson Marino','Maintenance Personnel',6,NULL,'',NULL,2,'2025-04-24 21:11:15','2025-04-24 21:11:15');
/*!40000 ALTER TABLE `faculty` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculty_categories`
--

DROP TABLE IF EXISTS `faculty_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculty_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculty_categories`
--

LOCK TABLES `faculty_categories` WRITE;
/*!40000 ALTER TABLE `faculty_categories` DISABLE KEYS */;
INSERT INTO `faculty_categories` VALUES (1,'School Administration',1),(2,'Coordinators & Specialists',2),(3,'Teachers',3),(4,'Student Services & Support Staff',4),(5,'Finance & Administrative Staff',5),(6,'Maintenance & Security Personnel',6);
/*!40000 ALTER TABLE `faculty_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('image','document','video') NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `dimensions` varchar(20) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `navigation`
--

DROP TABLE IF EXISTS `navigation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `navigation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nav_order` (`display_order`,`parent_id`),
  KEY `idx_navigation_parent` (`parent_id`),
  CONSTRAINT `navigation_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `navigation` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `navigation`
--

LOCK TABLES `navigation` WRITE;
/*!40000 ALTER TABLE `navigation` DISABLE KEYS */;
INSERT INTO `navigation` VALUES (1,'HOME','/index.php',NULL,1,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(2,'ADMISSIONS','/admissions.php',NULL,2,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(3,'ABOUT SRMS','/about.php',NULL,3,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(4,'ACADEMICS','#',NULL,4,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(5,'NEWS','/news.php',NULL,5,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(6,'CONTACT','/contact.php',NULL,6,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(7,'ALUMNI','/alumni.php',3,1,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(8,'FACULTY','/faculty.php',3,2,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(9,'PRESCHOOL','#',4,1,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(10,'ELEMENTARY','#',4,2,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(11,'JUNIOR HIGH','#',4,3,1,'2025-05-18 19:22:06','2025-05-18 19:22:06'),(12,'SENIOR HIGH','/academics/senior-high.php',4,4,1,'2025-05-18 19:22:06','2025-05-18 19:22:06');
/*!40000 ALTER TABLE `navigation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `navigation_backup`
--

DROP TABLE IF EXISTS `navigation_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `navigation_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `navigation_backup`
--

LOCK TABLES `navigation_backup` WRITE;
/*!40000 ALTER TABLE `navigation_backup` DISABLE KEYS */;
INSERT INTO `navigation_backup` VALUES (1,'HOME','/index.php',NULL,1,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(2,'ADMISSIONS','/admissions.php',NULL,2,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(3,'ABOUT SRMS','/about.php',NULL,3,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(4,'ACADEMICS','#',NULL,4,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(5,'NEWS','/news.php',NULL,5,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(6,'CONTACT','/contact.php',NULL,6,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(7,'ALUMNI','/alumni.php',3,1,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(8,'FACULTY','/faculty.php',3,2,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(9,'PRESCHOOL','#',4,1,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(10,'ELEMENTARY','#',4,2,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(11,'JUNIOR HIGH','#',4,3,1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(12,'SENIOR HIGH','/academics/senior-high.php',4,4,1,'2025-04-24 21:11:15','2025-04-24 23:51:59'),(13,'TEST','#',NULL,7,1,'2025-05-18 19:15:14','2025-05-18 19:15:14');
/*!40000 ALTER TABLE `navigation_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  KEY `idx_news_published_date` (`published_date`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'SENIOR HIGH IS FREE!','senior-high-is-free','HERE IN ST. RAPHAELA MARY SCHOOL, SENIOR HIGH IS FREE!\r\n\r\nYes! whether you are from a PUBLIC school or a PRIVATE school, when you enroll here in SRMS for SENIOR HIGH, it\'s FREE!\r\n\r\nPlus, incoming Grade 11 students from public schools will receive FREE school and P.E. uniform with FREE ID with lace and CASH incentive! What are you waiting for? Enroll now and be part of a community that will give you the best academic experience ever.\r\n\r\nENROLL NOW!\r\n\r\nTalk to us: 8253-3801 or 09208327705.\r\nEmail us: srmseduc@gmail.com\r\nVisit the official Facebook page: srms.page\r\n\r\nRegister online:\r\nClick the link: : https://bit.ly/SRMSEnroll_SY2025-2026','HERE IN ST. RAPHAELA MARY SCHOOL, SENIOR HIGH IS FREE!','/assets/images/promotional/senior-high-free.jpg','2025-04-04 23:05:00',1,'published',1,'2025-04-24 21:11:15','2025-05-18 19:57:58','general'),(2,'First Friday Mass Reminder','first-friday-mass-reminder','Tomorrow is our First Friday Mass, and to make this event meaningful, kindly follow our regular time-in at 7:20am to prepare for our encounter with God.\r\n\r\nPlease do bring school supplies for the Mayflower catechism!\r\n\r\nThank you for your generosity, and God bless!\r\n\r\nSee you, Raphaelians!','Listen up, Raphaelians!','/assets/images/news/announcement-06.jpg','2025-04-03 18:39:00',1,'published',1,'2025-04-24 21:11:15','2025-05-18 20:05:17','events'),(3,'Last Club Meeting Announcement','last-club-meeting','Heads up, Raphaelians, It\'s our LAST Club Meeting day tomorrow, April 2 from 8:30AM - 11AM and all are expected to come to a celebration of skills and talents! Exciting activities are in store for you!\r\n\r\nSo see you tomorrow, Raphaelians!','Heads up, Raphaelians, It\'s our LAST Club Meeting day tomorrow, April 2 from 8:30AM - 11AM','/assets/images/news/announcement-07.jpg','2025-04-01 17:43:00',1,'published',1,'2025-04-24 21:11:15','2025-05-18 20:05:17','announcement'),(4,'Support Mr. and Ms. SRMS Candidates','support-candidates','LET\'S SUPPORT OUR CANDIDATES FOR MR. AND MS. & LITTLE MR. AND MS. SRMS 2025, RAPHAELIANS! READ THE POST BELOW TO KNOW HOW?','LET\'S SUPPORT OUR CANDIDATES FOR MR. AND MS. & LITTLE MR. AND MS. SRMS 2025, RAPHAELIANS!','/assets/images/events/event-01.jpg','2025-03-11 18:06:00',1,'published',1,'2025-04-24 21:11:15','2025-05-18 19:57:58','general'),(5,'Club Meeting Reminder','club-meeting-reminder','Hello, Raphaelians!\r\n\r\nPlease be reminded of our club meeting tomorrow, March 12, 2025\r\nPlease bring the following: extra shirt, bottled water, towel, and snacks.\r\nFor the guitarist club, kindly bring your guitar.\r\nThank you, and God bless!','Hello, Raphaelians!','/assets/images/news/announcement-01.jpg','2025-03-11 16:18:00',1,'published',1,'2025-04-24 21:11:15','2025-05-18 19:57:58','general'),(6,'Mr. and Ms. SRMS Nationalism Theme','mr-ms-srms-nationalism','MR. AND MS. SRMS & LITTLE MR. AND MS. SRMS 2025 VOICE OUT NATIONALISM\r\n\r\nThe Mr. and Ms. SRMS & Little Mr. and Ms. SRMS is more than simply a fundraising activity for the school; it is also a tool for developing our young people\'s social consciousness and a forum for them to share their thoughts on urgent issues affecting their generation. Thus, they become ambassadors of hope for our society.\r\n\r\nFrom advocates of peace last year to agents of nationalism, the flagship of our candidates for the Mr. and Ms. SRMS & Little Mr. and Ms. SRMS this year with the theme, \'Piliin ang Pilipinas.\'.\r\n\r\nA timely and relevant theme to remind us that we are a nation blessed with rich culture, stunning nature, and beautiful people. Our country is God\'s gift to us, and there is a need to protect and preserve its grandeur so that we can achieve the prosperity and progress that we deserve.\r\n\r\nOur dreams can only happen if we as a people would make a stand to choose our country more, setting aside our differences and personal interests, which hinder us from becoming the great nation we once were.\r\n\r\nWith this in mind, the twenty-two (22) candidates of our school-based pageant will hopefully inspire you to love our country more and uphold our culture, reflected in the different symbols that they will creatively embody in this journey.\r\n\r\nSupport our candidates in their quest to become the voice that tells us, Choose Philippines, piliin ang Pilipinas!','MR. AND MS. SRMS & LITTLE MR. AND MS. SRMS 2025 VOICE OUT NATIONALISM','/assets/images/events/event-03.jpg','2025-03-09 08:11:00',1,'published',0,'2025-04-24 21:11:15','2025-05-18 19:57:58','general'),(16,'Sample Article','sample-article','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc laoreet lacus quis ipsum accumsan eleifend. Aenean eleifend libero a velit tincidunt varius. Vivamus malesuada luctus sapien, eget ornare purus. Cras elementum metus pretium lacus lobortis, vitae sollicitudin ipsum molestie. Nulla ac iaculis metus. Aliquam erat volutpat. Vivamus lacus nulla, convallis sed dui in, maximus porttitor nisi. Nunc posuere, elit at consectetur faucibus, quam est vehicula justo, sed tincidunt nulla est non leo. Sed felis magna, placerat et rutrum quis, suscipit vel nulla. Curabitur tincidunt, elit placerat sodales fermentum, lorem leo sagittis nunc, non hendrerit ex lorem et odio. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed tellus orci, vehicula vitae odio quis, consectetur sagittis est. Praesent eget mattis risus. Mauris vitae pharetra metus.\r\n\r\nNullam volutpat lobortis nisl, nec congue tortor. Donec efficitur condimentum nibh. Cras viverra ut felis tempus commodo. Praesent sagittis massa aliquet suscipit bibendum. Duis nec gravida quam. Sed risus ex, congue sit amet urna sed, aliquam consequat orci. Aliquam lectus nisl, mattis posuere dui non, aliquam condimentum tellus. Sed ut consectetur neque. Phasellus rutrum justo et nisi commodo, ut mattis dolor consequat. Aliquam sodales justo vitae magna sagittis, ut condimentum mi dapibus. Vestibulum vitae rutrum ante. Nulla tristique faucibus velit, ut porta dolor sodales quis. In ac lorem maximus, laoreet purus ut, feugiat arcu. Vestibulum maximus, tellus non ullamcorper molestie, lorem nulla aliquet erat, eu ullamcorper turpis dolor id magna. Aliquam mattis nec est in congue. Phasellus vehicula nisi nulla, vitae dapibus ex sollicitudin ut.\r\n\r\nAenean efficitur, ante sit amet pellentesque tristique, magna erat tempus risus, vitae congue ipsum lectus sed nisi. Aliquam blandit efficitur nisi. Nullam id vulputate libero. Ut at mi blandit, convallis nisi nec, ultrices sem. Mauris hendrerit, ante et scelerisque lacinia, metus tortor sagittis lorem, at accumsan tellus arcu in ante. Pellentesque semper eros sed justo laoreet, at elementum risus pellentesque. Nulla sit amet vehicula sem. Praesent bibendum urna non lacus pharetra vestibulum. Aliquam eu erat eget est mollis ultrices. Nunc felis dui, pellentesque vel varius eu, volutpat ut urna. Morbi non metus eu risus pulvinar fermentum. Integer ut felis eu erat accumsan vestibulum eu nec nunc. Suspendisse id rutrum erat. Vestibulum pellentesque, nisi quis laoreet vulputate, enim massa tempor turpis, ut feugiat orci magna ut tortor. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Fusce vel magna lorem.\r\n\r\nVivamus ornare, massa quis scelerisque lobortis, sapien turpis gravida mauris, ut scelerisque lectus velit nec lectus. Donec bibendum tortor nulla, sed hendrerit sem iaculis ut. Curabitur dictum interdum turpis, feugiat consequat erat porttitor id. Cras mollis, sem nec pretium pellentesque, magna nibh tempus lorem, non maximus urna quam a dui. Aliquam egestas, lorem a convallis maximus, ante nunc iaculis nulla, nec convallis libero dolor nec urna. Fusce dictum blandit fermentum. Vivamus non vulputate libero, ut pharetra nunc.\r\n\r\nSed sit amet odio quis velit convallis pharetra sit amet a erat. Quisque tincidunt odio sed bibendum hendrerit. Praesent posuere, dolor at varius sagittis, quam tellus faucibus lectus, nec suscipit nisi eros ac neque. Nullam sodales, nisl eleifend rhoncus gravida, nibh arcu finibus ligula, non sagittis nisl elit lacinia ipsum. Nunc a enim eu arcu tincidunt consectetur. In dignissim id ligula ut imperdiet. Sed vel est eu dui feugiat maximus. Phasellus nec lectus non orci aliquet malesuada. Nullam tristique eleifend condimentum. Mauris posuere metus lectus, vitae dictum lacus viverra vitae.','Sample Article for Testing','/assets/images/news/pexels-edmond-dantes-4342352-1747581624.jpg','2025-05-17 22:01:00',7,'published',0,'2025-05-17 14:02:17','2025-05-18 19:57:58','general');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `non_readmission_grounds`
--

DROP TABLE IF EXISTS `non_readmission_grounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `non_readmission_grounds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `non_readmission_grounds`
--

LOCK TABLES `non_readmission_grounds` WRITE;
/*!40000 ALTER TABLE `non_readmission_grounds` DISABLE KEYS */;
INSERT INTO `non_readmission_grounds` VALUES (1,'Conduct grade below 75%',1),(2,'Habitual late payments',2),(3,'Involvement in fraternities/gangs',3),(4,'Frequent unexcused absences (20% of school year)',4),(5,'Unauthorized summer classes',5),(6,'Accumulating 3 suspensions',6),(7,'Poor academic/behavioral performance',7);
/*!40000 ALTER TABLE `non_readmission_grounds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offer_box`
--

DROP TABLE IF EXISTS `offer_box`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offer_box` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offer_box`
--

LOCK TABLES `offer_box` WRITE;
/*!40000 ALTER TABLE `offer_box` DISABLE KEYS */;
/*!40000 ALTER TABLE `offer_box` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_content`
--

DROP TABLE IF EXISTS `page_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_key` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_key` (`page_key`),
  KEY `last_updated_by` (`last_updated_by`),
  CONSTRAINT `page_content_ibfk_1` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_content`
--

LOCK TABLES `page_content` WRITE;
/*!40000 ALTER TABLE `page_content` DISABLE KEYS */;
INSERT INTO `page_content` VALUES (1,'home','Home','<section class=\"enr\"><h6>ST. RAPHAELA MARY SCHOOL</h6><p>Welcome Raphaelians!</p></section>','Welcome to the official website of St. Raphaela Mary School','2025-05-18 09:04:18',1),(2,'about','About SRMS','<div class=\"about-header\"><h2>About SMRS</h2></div><h3>Welcome to St. Raphaela Mary School!</h3>','Learn about the history, mission, vision, and philosophy of St. Raphaela Mary School','2025-05-18 09:04:18',1),(3,'admissions','Admissions','<section class=\"header\"><div class=\"line-title1\"><p>ST. RAPHAELA MARY SCHOOL</p></div><div class=\"line-title2\"><h1>ENROLLMENT POLICIES AND PROCEDURES</h1></div></section>','Information about enrollment policies, procedures, and requirements at St. Raphaela Mary School','2025-05-18 09:04:18',1),(4,'contact','Contact','<section><h1>CONTACT US</h1></section>','Get in touch with St. Raphaela Mary School for inquiries or concerns','2025-05-18 09:04:18',1),(5,'faculty','Faculty and Staff','<section class=\"main-head\"><h1>FACULTY AND PERSONNEL ROSTER</h1></section>','Meet the dedicated faculty and staff of St. Raphaela Mary School','2025-05-18 09:04:18',1),(6,'senior-high','Senior High School','<div class=\"lvl-grade\"><h1>Senior High School</h1></div>','Information about the Senior High School program at St. Raphaela Mary School','2025-05-18 09:04:18',1),(7,'news','News','<section class=\"main-title\"><h2>SMRS NEWS UPDATES</h2></section>','Stay updated with the latest news and announcements from St. Raphaela Mary School','2025-05-18 09:04:18',1),(8,'alumni','Alumni Association','','Connect with the St. Raphaela Mary School alumni community. Discover alumni events, achievements, and ways to stay connected.','2025-05-18 09:06:36',1);
/*!40000 ALTER TABLE `page_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_sections`
--

DROP TABLE IF EXISTS `page_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `section_key` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_id` (`page_id`,`section_key`),
  CONSTRAINT `page_sections_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page_content` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_sections`
--

LOCK TABLES `page_sections` WRITE;
/*!40000 ALTER TABLE `page_sections` DISABLE KEYS */;
INSERT INTO `page_sections` VALUES (1,1,'main_content','Home','<section class=\"enr\"><h6>ST. RAPHAELA MARY SCHOOL</h6><p>Welcome Raphaelians!</p></section>',0),(2,2,'main_content','About SRMS','<div class=\"about-header\"><h2>About SMRS</h2></div><h3>Welcome to St. Raphaela Mary School!</h3>',0),(3,3,'main_content','Admissions','<section class=\"header\"><div class=\"line-title1\"><p>ST. RAPHAELA MARY SCHOOL</p></div><div class=\"line-title2\"><h1>ENROLLMENT POLICIES AND PROCEDURES</h1></div></section>',0),(4,4,'main_content','Contact','<section><h1>CONTACT US</h1></section>',0),(5,5,'main_content','Faculty and Staff','<section class=\"main-head\"><h1>FACULTY AND PERSONNEL ROSTER</h1></section>',0),(6,6,'main_content','Senior High School','<div class=\"lvl-grade\"><h1>Senior High School</h1></div>',0),(7,7,'main_content','News','<section class=\"main-title\"><h2>SMRS NEWS UPDATES</h2></section>',0),(8,8,'welcome','Welcome','The St. Raphaela Mary School Alumni Association connects graduates from all generations, fostering lifelong relationships and supporting our alma mater. We invite all alumni to stay connected, participate in events, and give back to the school community.',1),(9,8,'benefits','Alumni Benefits','- **Networking Opportunities:** Connect with fellow graduates for professional development and social connections\n- **School Events:** Special invitations to school functions and reunions\n- **Giving Back:** Opportunities to mentor current students and support scholarship programs\n- **Recognition:** Celebrate alumni achievements and contributions to society',2),(10,8,'events','Upcoming Alumni Events','**Annual Homecoming 2025**\nDate: July 15, 2025\nLocation: SRMS Gymnasium\nJoin us for a day of reminiscing, reconnecting, and celebrating your Raphaelian roots. Special recognition for milestone anniversary batches (Classes of 1975, 1985, 1995, 2005, 2015).\n\n**Career Mentorship Day**\nDate: September 5, 2025\nLocation: SRMS Auditorium\nShare your professional journey and insights with current senior high school students. Help shape the future of our younger Raphaelians!',3),(11,8,'registration','Join the Alumni Network','We\'re building a comprehensive database of our alumni. Please take a moment to register or update your information.',4),(12,8,'contact','Contact the Alumni Association','For inquiries about alumni activities, reunions, or how to get involved:\n\n**Email:** alumni@srms.edu.ph\n**Social Media:** Follow us on Facebook',5);
/*!40000 ALTER TABLE `page_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(100) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'Home','home','<section class=\"enr\"><h6>ST. RAPHAELA MARY SCHOOL</h6><p>Welcome Raphaelians!</p></section>','SRMS - Home','Welcome to the official website of St. Raphaela Mary School','published',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(2,'About SRMS','about','<div class=\"about-header\"><h2>About SMRS</h2></div><h3>Welcome to St. Raphaela Mary School!</h3>','About SRMS','Learn about the history, mission, vision, and philosophy of St. Raphaela Mary School','published',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(3,'Admissions','admissions','<section class=\"header\"><div class=\"line-title1\"><p>ST. RAPHAELA MARY SCHOOL</p></div><div class=\"line-title2\"><h1>ENROLLMENT POLICIES AND PROCEDURES</h1></div></section>','Admissions - SRMS','Information about enrollment policies, procedures, and requirements at St. Raphaela Mary School','published',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(4,'Contact','contact','<section><h1>CONTACT US</h1></section>','Contact SRMS','Get in touch with St. Raphaela Mary School for inquiries or concerns','published',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(5,'Faculty and Staff','faculty','<section class=\"main-head\"><h1>FACULTY AND PERSONNEL ROSTER</h1></section>','Faculty and Staff - SRMS','Meet the dedicated faculty and staff of St. Raphaela Mary School','published',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(6,'Senior High School','senior-high','<div class=\"lvl-grade\"><h1>Senior High School</h1></div>','Senior High School - SRMS','Information about the Senior High School program at St. Raphaela Mary School','published',1,'2025-04-24 21:11:15','2025-04-24 21:11:15'),(7,'News','news','<section class=\"main-title\"><h2>SMRS NEWS UPDATES</h2></section>','News and Updates - SRMS','Stay updated with the latest news and announcements from St. Raphaela Mary School','published',1,'2025-04-24 21:11:15','2025-04-24 21:11:15');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_goals`
--

DROP TABLE IF EXISTS `school_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_goals`
--

LOCK TABLES `school_goals` WRITE;
/*!40000 ALTER TABLE `school_goals` DISABLE KEYS */;
INSERT INTO `school_goals` VALUES (1,'Faith Formation','To foster a deep and abiding faith in God, rooted in the teachings of the Christian Church, and to encourage students to live out their faith in their daily lives.',1),(2,'Academic Excellence','To provide a challenging and stimulating academic environment that enables students to reach their full intellectual potential and develop a lifelong love of learning.',2),(3,'Stakeholder Involvement','To actively involve parents, teachers, and the wider community in the educational process, creating a collaborative partnership that supports student success.',3),(4,'Training Place of Competence','To equip students with the knowledge, skills, and values necessary to succeed in their chosen fields and to become responsible and contributing members of society.',4),(5,'Holistic Development','To promote the intellectual, emotional, social, and spiritual development of each student, fostering their growth into well-rounded, compassionate, and responsible individuals.',5);
/*!40000 ALTER TABLE `school_goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_information`
--

DROP TABLE IF EXISTS `school_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `philosophy` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_information`
--

LOCK TABLES `school_information` WRITE;
/*!40000 ALTER TABLE `school_information` DISABLE KEYS */;
INSERT INTO `school_information` VALUES (1,'St. Raphaela Mary School','/assets/images/branding/logo-primary.png','Inspired by the words of our school patroness, St. Raphaela Mary, to give our whole heart to God, SRMS is committed to:\r\n\r\nForm the hearts with the love of Christ;\r\nForm the minds with relevant academic programs and services;\r\nForm the whole person with significant holistic development programs;\r\nForm the community with meaningful and effective avenues for competence; and\r\nForm the future with innovative technological advancement.','St. Raphaela Mary School with the support of its stakeholders is envisioned as a formative educational institution committed to form the hearts of the young by providing them academic excellence and meaningful Christian formation molding them to become competent, compassionate and Christ-centered servant leaders in the future.','At St. Raphaela Mary School, we believe in fostering the holistic development of each student, nurturing their intellectual, spiritual, moral, and social growth from preschool through senior high school. Our Catholic faith forms the cornerstone of our educational approach, providing a framework for critical thinking, ethical decision-making, and a commitment to service. We strive to cultivate academically excellent students who are also compassionate, responsible, and engaged citizens of the world.\r\n\r\nOur educational process centers on the student, with teachers acting as mentors and guides, fostering a supportive and challenging learning environment. We equip students with the knowledge, skills, and values necessary to navigate the complexities of life, empowering them to become confident and creative problem-solvers. We believe in providing a safe and inclusive community where every individual feels valued and respected, enabling them to reach their full potential.\r\n\r\nThrough a rich and diverse curriculum, we aim to inspire a lifelong love of learning and a commitment to personal growth. We encourage students to explore their talents, pursue their passions, and develop a strong sense of self-awareness and purpose. Ultimately, our goal is to graduate students who are not only academically prepared but also morally upright, spiritually grounded, and ready to contribute meaningfully to society.','srmseduc@gmail.com','8253-3801/0920 832 7705','#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City','2025-04-24 21:11:15','2025-05-18 15:40:18');
/*!40000 ALTER TABLE `school_information` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slideshow`
--

DROP TABLE IF EXISTS `slideshow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slideshow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slideshow`
--

LOCK TABLES `slideshow` WRITE;
/*!40000 ALTER TABLE `slideshow` DISABLE KEYS */;
INSERT INTO `slideshow` VALUES (1,'/assets/images/campus/hero-main.jpg','St. Raphaela Mary School Campus','',1,1),(2,'/assets/images/promotional/senior-high-free.jpg','Free Senior High School Education','admissions.php',2,1),(3,'/assets/images/campus/overview.jpg','School Activities','',3,1);
/*!40000 ALTER TABLE `slideshow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_media`
--

DROP TABLE IF EXISTS `social_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_media`
--

LOCK TABLES `social_media` WRITE;
/*!40000 ALTER TABLE `social_media` DISABLE KEYS */;
INSERT INTO `social_media` VALUES (1,'Facebook','https://web.facebook.com/srms.page','bx bxl-facebook-circle',1,1),(2,'Instagram','#','bx bxl-instagram-alt',2,1),(3,'Twitter','#','bx bxl-twitter',3,1),(4,'LinkedIn','#','bx bxl-linkedin-square',4,1);
/*!40000 ALTER TABLE `social_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_types`
--

DROP TABLE IF EXISTS `student_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `requirements` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_types`
--

LOCK TABLES `student_types` WRITE;
/*!40000 ALTER TABLE `student_types` DISABLE KEYS */;
INSERT INTO `student_types` VALUES (1,'New Students','1. Must have passed the previous year with at least 80% average and Conduct grade of 80%.\n2. Must submit:\n   a. Report Card\n   b. Good Moral Certificate\n   c. PSA Birth Certificate (original + photocopy)\n   d. Baptismal Certificate (if available)\n3. Must pass entrance exam and interview.',1),(2,'Transferees','1. Must fulfill the requirements stated in nos. 1-4 on new students.\n2. Must be a regular student, i.e., no back subjects.',2),(3,'Old Students','1. Must have passed all subjects with minimum 80%, including Conduct.',3);
/*!40000 ALTER TABLE `student_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submission_replies`
--

DROP TABLE IF EXISTS `submission_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submission_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `reply_content` text NOT NULL,
  `reply_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `submission_replies_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `contact_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submission_replies_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submission_replies`
--

LOCK TABLES `submission_replies` WRITE;
/*!40000 ALTER TABLE `submission_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `submission_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','editor','content_manager') NOT NULL DEFAULT 'editor',
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin@srms.edu.ph','admin',1,'2025-04-25 05:11:15','2025-04-24 21:11:15','2025-04-24 21:11:15'),(2,'content_editor','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','content@srms.edu.ph','editor',1,'2025-04-25 05:11:15','2025-04-24 21:11:15','2025-04-24 21:11:15'),(3,'jidello','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','jidello@srms.edu.ph','editor',1,'2025-04-25 05:11:15','2025-04-24 21:11:15','2025-04-24 21:11:15'),(4,'jquevedo','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','jquevedo@srms.edu.ph','admin',0,'2025-04-25 05:11:15','2025-04-24 21:11:15','2025-04-27 03:38:50'),(7,'admin1','$2a$12$CD/0iENYIDhWcCE94OgGh.cvTWXGgoDuemxbwzeC9GhuJsXL6j/OC','admin1@srms.edu.ph','admin',1,'2025-05-19 12:13:20','2025-04-24 22:23:11','2025-05-19 04:13:20'),(9,'rodneybagay','$2y$10$o0fkR58GY6z3jZDKwDmLT.pVQiAVWwvmsBf987N1L3B4KBCtDZRE.','rodneybagay@srms.edu.ph','editor',1,NULL,'2025-05-06 07:30:19','2025-05-06 07:30:55');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-19 21:54:20
