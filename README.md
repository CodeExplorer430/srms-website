# St. Raphaela Mary School Website

A comprehensive school management and information system for St. Raphaela Mary School, designed to provide a modern web presence with dynamic content management capabilities.

## Project Overview

This project is a custom-developed website for St. Raphaela Mary School, offering a complete web solution for the school's online presence. The system features a content management system, dynamic page creation, faculty directory, news management, and student information display.

## Features

- **Responsive Design**: Fully responsive layout that works seamlessly across desktops, tablets, and mobile devices
- **Dynamic Content Management**: Backend system for managing site content
- **User Authentication**: Secure login system with role-based access control
- **Cross-Platform Compatibility**: Works seamlessly across Windows and Linux environments with automatic server detection
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
- **Media Library**: Comprehensive media management with cross-platform file handling

## Technologies Used

- **Frontend**:
  - HTML5
  - CSS3 
  - JavaScript (ES6+)
  - Responsive design with mobile-first approach
  - Box Icons library

- **Backend**:
  - PHP 7.4+
  - MySQL/MariaDB database
  - Environment-aware configuration system

- **Development Environments**:
  - Windows: WAMP Server or XAMPP
  - Linux: XAMPP or LAMP stack

## Installation

### Prerequisites

- Web server (Apache)
- PHP 7.4 or higher
- MySQL 5.7/MariaDB 10.3 or higher
- WAMP or XAMPP (for Windows) or XAMPP or LAMP (for Linux)

### Setup Instructions

1. **Clone or upload the project files to your web server**

2. **Database Setup**:
   ```bash
   # Create the database
   mysql -u username -p < database/srms_database_schema.sql
   
   # Populate with initial data
   mysql -u username -p < database/srms_database_population.sql
   ```

3. **Configure Environment Settings**:
   - Copy `environment-sample.php` to `environment.php`
   - Update database configuration based on your environment:

   ```php
   <?php
   // Enhanced environment detection and configuration
   
   // Detect operating system
   define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
   
   // Define OS-specific constants
   define('DS', DIRECTORY_SEPARATOR); // Will be \ on Windows, / on Linux
   
   // Detect server software (XAMPP vs WAMP vs LAMP)
   function detect_server_type() {
       // Default to XAMPP
       $server_type = 'XAMPP';
       
       if (IS_WINDOWS) {
           // Check for WAMP-specific paths
           if (file_exists('C:/wamp/www') || file_exists('C:/wamp64/www') || 
               strpos($_SERVER['DOCUMENT_ROOT'], 'wamp') !== false) {
               $server_type = 'WAMP';
           }
       } else {
           // On Linux, check for LAMP-specific configuration
           if (file_exists('/etc/apache2/sites-available') && !file_exists('/opt/lampp')) {
               $server_type = 'LAMP';
           }
       }
       
       return $server_type;
   }
   
   // Get server type
   $server_type = detect_server_type();
   define('SERVER_TYPE', $server_type);
   
   // Set environment-specific database configurations
   if (IS_WINDOWS) {
       if ($server_type === 'WAMP') {
           // Windows (WAMP) configuration
           define('DB_SERVER', 'localhost');
           define('DB_USERNAME', 'root');
           define('DB_PORT', '3308'); // Common WAMP MySQL port
           define('DB_PASSWORD', '');
           define('DB_NAME', 'srms_database');
       } else {
           // Windows (XAMPP) configuration
           define('DB_SERVER', 'localhost');
           define('DB_USERNAME', 'root');
           define('DB_PORT', '3306'); // Default XAMPP MySQL port
           define('DB_PASSWORD', '');
           define('DB_NAME', 'srms_database');
       }
   } else {
       if ($server_type === 'LAMP') {
           // Linux (Traditional LAMP) configuration
           define('DB_SERVER', 'localhost');
           define('DB_USERNAME', 'root');
           define('DB_PORT', '3306');
           define('DB_PASSWORD', '');
           define('DB_NAME', 'srms_database');
       } else {
           // Linux (XAMPP) configuration
           define('DB_SERVER', 'localhost');
           define('DB_USERNAME', 'root');
           define('DB_PORT', '3306');
           define('DB_PASSWORD', '');
           define('DB_NAME', 'srms_database');
       }
   }
   
   // Common configurations
   define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/srms-website");
   define('ADMIN_EMAIL', 'admin@srms.edu.ph');
   
   // Set proper timezone
   date_default_timezone_set('Asia/Manila');
   ?>
   ```

4. **Set Proper Permissions**:
   - For Windows (WAMP/XAMPP):
     - No special permissions required
   
   - For Linux (XAMPP/LAMP):
     ```bash
     sudo chmod -R 755 /path/to/srms-website
     sudo chmod -R 777 /path/to/srms-website/assets/images
     sudo chmod -R 777 /path/to/srms-website/assets/uploads
     ```

5. **Run Environment Check**:
   - Navigate to `http://localhost/srms-website/admin/check-environment.php`
   - Ensure all checks pass (database connection, directory permissions, etc.)
   - Fix any reported issues before proceeding

6. **Setup Directories**:
   - Navigate to `http://localhost/srms-website/admin/maintenance/setup-directories.php`
   - This will create required directories and placeholder images

## File Structure

```
srms-website/
│
├── admin/                      # Admin panel
│   ├── ajax/                   # AJAX handlers
│   │   ├── bulk-upload.php     # Bulk media upload handler
│   │   └── upload-media.php    # Single media upload handler
│   ├── maintenance/            # Maintenance tools
│   │   └── setup-directories.php # Directory setup utility
│   ├── check-environment.php   # Environment diagnostic tool
│   └── media-manager.php       # Media library management
│
├── assets/                     # Assets directory
│   ├── css/                    # CSS files
│   ├── js/                     # JavaScript files
│   └── images/                 # Image directories
│       ├── news/               # News images
│       ├── events/             # Event images
│       ├── campus/             # Campus images
│       ├── facilities/         # Facilities images
│       └── promotional/        # Promotional images
│
├── database/                   # Database files
│
├── includes/                   # PHP includes/components
│   ├── config.php              # Configuration loader
│   ├── db.php                  # Database class
│   └── functions.php           # Common functions
│
├── .htaccess                   # Apache configuration
├── environment.php             # Environment detection & configuration
└── index.php                   # Main entry point
```

## Cross-Platform Compatibility

This project is designed to work seamlessly across multiple server environments:

### Key Cross-Platform Features

- **Auto Server Detection**: Automatically detects the server type (WAMP, XAMPP, LAMP) and configures settings appropriately
- **Path Handling**: Uses platform-neutral path separators and normalizes file paths
- **Database Configuration**: Adapts database connection parameters based on the detected environment
- **File Permissions**: Applies appropriate permissions for each operating system
- **Directory Structure**: Standardized directory structure works on all platforms

### Windows Environment Support

- **WAMP**: Full support with MySQL typically on port 3308
- **XAMPP**: Full support with MySQL typically on port 3306
- Backslash (`\`) handled for directory separators
- Accommodates case-insensitive filesystem

### Linux Environment Support

- **LAMP**: Full support for traditional LAMP setup
- **XAMPP**: Full support for Linux XAMPP installation
- Forward slash (`/`) used for directory separators
- Handles case-sensitive filesystem requirements
- Proper file permission management

## Content Management System

The website includes a content management system for authorized users with the following roles:

- **Administrator**: Full access to all website features and settings
- **Editor**: Ability to create and edit content, news, and basic information
- **Content Manager**: Limited access for specific content updates

Login credentials for the initial admin account:
- Username: admin
- Password: password (should be changed immediately after first login)

### Admin Panel Notes

When working with the administrative interface:

- Use proper relative paths when accessing resources across directories
- Remember that admin scripts require session authentication
- The `setup-directories.php` utility requires proper permissions to create directories
- Media management functions have robust error handling for cross-platform compatibility

## Troubleshooting

### Common Issues

1. **Database Connection Errors**:
   - Verify MySQL is running on the correct port (3308 for WAMP, 3306 for XAMPP/LAMP)
   - Check database credentials in environment.php
   - Ensure database exists and has been populated

2. **File Permission Issues (Linux)**:
   - Run `sudo chmod -R 755 /path/to/website` to set correct permissions
   - For upload directories: `sudo chmod -R 777 /path/to/website/assets/images`

3. **Image Upload Problems**:
   - Check PHP upload_max_filesize and post_max_size in php.ini
   - Verify directory permissions
   - Use check-environment.php to diagnose issues

4. **Cross-Platform Path Issues**:
   - Use the provided file_exists_with_alternatives() function for file operations
   - Always normalize paths with str_replace(['\\', '/'], DS, $path)
   - Use forward slashes in HTML/CSS/JavaScript paths

5. **PHP Variable Initialization Issues**:
   - Always initialize variables before use in PHP files
   - For conditional displays, set default values at the top of scripts
   - Use the check-environment.php tool to scan for common PHP errors

## Debugging and Development

For development environments, you can enable more detailed error reporting:

1. Create a `.env.development` file in the project root
2. Set `DEBUG_MODE=true` in this file
3. The application will show detailed error messages when in development mode

When troubleshooting issues:
- Check the PHP error logs
- Use browser developer tools to inspect network requests
- The application logs specific errors to `/path/to/srms-website/logs/error.log`

## Maintenance and Updates

To update website content:

1. **News and Announcements**:
   - Log in to the admin panel
   - Navigate to the News section
   - Create or edit news articles as needed

2. **Faculty Information**:
   - Access the Faculty management section
   - Add, edit or remove faculty members

3. **Media Management**:
   - Use the Media Library to upload and manage images
   - Bulk upload is available for multiple files

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
- Enhanced cross-platform compatibility for additional environments

## Credits

Developed by Miguel Harvey N. Velasco as a freelance project for St. Raphaela Mary School.

## Contact Information

For technical support or inquiries about this system, please contact:

miguel.velasco.dev@gmail.com

---

© 2025 St. Raphaela Mary School. All rights reserved.