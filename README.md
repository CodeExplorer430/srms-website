# St. Raphaela Mary School Website

A comprehensive school management and information system for St. Raphaela Mary School, designed to provide a modern web presence with dynamic content management capabilities.

## Project Overview

This project is a custom-developed website for St. Raphaela Mary School, offering a complete web solution for the school's online presence. The system features a content management system, dynamic page creation, faculty directory, news management, and student information display.

## Features

- **Responsive Design**: Fully responsive layout that works seamlessly across desktops, tablets, and mobile devices
- **Dynamic Content Management**: Backend system for managing site content
- **User Authentication**: Secure login system with role-based access control
- **School Information Pages**: Dedicated sections for:
  - About the school (mission, vision, philosophy)
  - Academic programs (Preschool to Senior High School)
  - Admissions information and enrollment procedures
  - Faculty and staff directory
  - News and announcements
  - Contact information
- **Interactive Elements**: Slideshow, navigation menus, and mobile-friendly interfaces
- **Admission Management**: Online enrollment information and requirements
- **News Management**: System for creating and publishing school news and announcements

## Technologies Used

- **Frontend**:
  - HTML5
  - CSS3 
  - JavaScript (ES6+)
  - Responsive design with mobile-first approach
  - Box Icons library

- **Backend**:
  - PHP (implied from database structure)
  - MySQL/MariaDB database

- **Database**:
  - Normalized relational database design
  - Transaction support
  - Referential integrity

## Installation

### Prerequisites

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7/MariaDB 10.3 or higher

### Setup Instructions

1. **Clone or upload the project files to your web server**

2. **Database Setup**:
   ```bash
   # Create the database
   mysql -u username -p < srms_database_schema.sql
   
   # Populate with initial data
   mysql -u username -p < srms_database_population.sql
   ```

3. **Configure Database Connection**:
   - Create or edit the database configuration file (e.g., `config.php`)
   - Update with your database credentials

   ```php
   <?php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'srms_database');
   
   $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
   
   if($conn === false) {
       die("ERROR: Could not connect. " . mysqli_connect_error());
   }
   ?>
   ```

4. **Set Proper Permissions**:
   ```bash
   # For Linux/Unix systems
   chmod 755 -R /path/to/website
   chmod 644 /path/to/website/config.php
   ```

5. **Configure Web Server**:
   - Set the document root to the project's public directory
   - Ensure proper URL rewriting for clean URLs

## File Structure

```
srms-website/
│
├── css/
│   └── styles.css              # Main stylesheet
│
├── js/
│   └── main.js                 # Main JavaScript functionality
│
├── images/                     # Image assets directory
│
├── includes/                   # PHP includes/components
│   ├── config.php              # Database configuration
│   ├── header.php              # Common header
│   ├── footer.php              # Common footer
│   └── db.php                  # Database connection
│
├── admin/                      # Admin panel
│
├── database/
│   ├── srms_database_schema.sql    # Database structure
│   └── srms_database_population.sql # Initial data
│
├── pages/                      # Main site pages
│   ├── home.php
│   ├── about.php
│   ├── admissions.php
│   ├── academics.php
│   ├── faculty.php
│   ├── news.php
│   └── contact.php
│
└── index.php                   # Main entry point
```

## Content Management System

The website includes a content management system for authorized users with the following roles:

- **Administrator**: Full access to all website features and settings
- **Editor**: Ability to create and edit content, news, and basic information
- **Content Manager**: Limited access for specific content updates

Login credentials for the initial admin account:
- Username: admin
- Password: password (should be changed immediately after first login)

## Maintenance and Updates

To update website content:

1. **News and Announcements**:
   - Log in to the admin panel
   - Navigate to the News section
   - Create or edit news articles as needed

2. **Faculty Information**:
   - Access the Faculty management section
   - Add, edit or remove faculty members

3. **Admission Information**:
   - Update through the Admissions management section

## Backup Procedures

It's recommended to perform regular backups of both the database and file system:

```bash
# Database backup
mysqldump -u username -p srms_database > backup_$(date +%Y%m%d).sql

# File system backup
tar -czvf website_backup_$(date +%Y%m%d).tar.gz /path/to/website
```

## Security Considerations

- Change default admin credentials immediately after installation
- Ensure server and PHP configurations follow security best practices
- Keep all dependencies and systems updated
- Implement regular security audits
- Use HTTPS for all connections

## Future Development

Potential enhancements for future versions:

- Student portal integration
- Online application submission
- Events calendar system
- Gallery management
- Document repository for school forms

## Credits

Developed by Miguel Harvey N. Velasco as a freelance project for St. Raphaela Mary School.

## Contact Information

For technical support or inquiries about this system, please contact:

miguel.velasco.dev@gmail.com

---

© 2025 St. Raphaela Mary School. All rights reserved.