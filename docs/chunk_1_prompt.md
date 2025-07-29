
// CHUNK 1 of 7: CORE FOUNDATION & ARCHITECTURAL SKELETON
// TASK: Establish the project's foundational technical architecture.
// DELIVERABLES: 1) metcalf-legal-theme.zip, 2) metcalf-legal-system.zip

[SYSTEM INSTRUCTION START]
Execute the construction of the project's core architecture.

**1. Project Scaffolding:**
*   Generate a custom WordPress plugin named "**Metcalf Legal System**". The primary plugin file must be `metcalf-legal-system.php`. This plugin will serve as the central controller for all custom functionality.
*   Generate a minimalist, performance-oriented custom WordPress theme named "**Metcalf Legal Theme**". It must be a "starter theme" containing only the essential files (`style.css`, `index.php`, `functions.php`) and be explicitly designed for full control via the Elementor Pro page builder.

**2. Multi-Database Architecture Implementation:**
*   Within the "Metcalf Legal System" plugin, create a plugin activation hook (`register_activation_hook`).
*   This hook must trigger a function that programmatically creates four (4) new, separate MySQL databases. The database names must be exactly: `wp_metcalf_portal`, `wp_metcalf_cases`, `wp_metcalf_docs`, `wp_metcalf_analytics`.
*   Following database creation, the script must execute the `CREATE TABLE` statements provided in the `[DATABASE_SCHEMAS]` block below to define the complete table structure for each new database. Ensure all primary keys, foreign keys, indexes, and data types are implemented precisely as specified.

**3. WordPress Custom Post Type (CPT) Registration:**
*   Within the "Metcalf Legal System" plugin, create a separate file `cpt-registration.php` and include it in the main plugin file.
*   In this file, register the following custom post types with the specified parameters:
    *   `attorneys`: Supports 'title', 'editor', 'thumbnail'.
    *   `practice_areas`: Supports 'title', 'editor', 'thumbnail', 'excerpt'.
    *   `case_results`: Supports 'title', 'editor'.
    *   `media_mentions`: Supports 'title', 'editor'.

**4. Security & Database Abstraction:**
*   Implement a custom database class within the plugin to handle all interactions with the new databases. This class must be an abstraction layer that uses the global `$wpdb` object but directs queries to the appropriate custom database. All queries MUST use `$wpdb->prepare()` to prevent SQL injection vulnerabilities.

**5. Deliverables:**
*   Provide a downloadable zip file named `metcalf-legal-theme.zip`.
*   Provide a downloadable zip file named `metcalf-legal-system.zip`. The plugin within this zip file must be fully functional and execute the complete database and CPT setup upon activation in a WordPress environment.

[DATABASE_SCHEMAS START]

-- Database: wp_metcalf_portal
CREATE TABLE `client_users` (
  `portal_user_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wp_user_id` BIGINT(20) UNSIGNED NOT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `two_factor_secret` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`portal_user_id`),
  UNIQUE KEY `wp_user_id` (`wp_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `secure_messages` (
  `message_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `sender_id` BIGINT(20) UNSIGNED NOT NULL,
  `recipient_id` BIGINT(20) UNSIGNED NOT NULL,
  `message_content` TEXT NOT NULL,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Database: wp_metcalf_cases
CREATE TABLE `cases` (
  `case_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_portal_user_id` BIGINT(20) UNSIGNED NOT NULL,
  `lead_attorney_wp_user_id` BIGINT(20) UNSIGNED NOT NULL,
  `case_title` VARCHAR(255) NOT NULL,
  `case_status` VARCHAR(50) NOT NULL DEFAULT 'open', -- e.g., open, pending, closed
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`case_id`),
  KEY `client_portal_user_id` (`client_portal_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `case_timeline` (
  `timeline_event_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `event_title` VARCHAR(255) NOT NULL,
  `event_description` TEXT NULL,
  `event_date` DATE NOT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`timeline_event_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`case_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Database: wp_metcalf_docs
CREATE TABLE `documents` (
  `doc_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `uploader_user_id` BIGINT(20) UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(1024) NOT NULL,
  `file_hash_sha256` VARCHAR(64) NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `document_access_log` (
  `log_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_id` BIGINT(20) UNSIGNED NOT NULL,
  `viewer_user_id` BIGINT(20) UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL, -- e.g., view, download, sign
  `accessed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  FOREIGN KEY (`doc_id`) REFERENCES `documents`(`doc_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Database: wp_metcalf_analytics
CREATE TABLE `client_satisfaction_surveys` (
  `survey_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `client_portal_user_id` BIGINT(20) UNSIGNED NOT NULL,
  `overall_rating` TINYINT(2) NOT NULL, -- Scale of 1-10
  `comments` TEXT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`survey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

[DATABASE_SCHEMAS END]
[SYSTEM INSTRUCTION END]
