-- Case Management System Database Schema Additions
-- For Metcalf Legal Platform - Chunk 4

-- Case Categories and Types
CREATE TABLE `case_categories` (
  `category_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  `category_description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `case_types` (
  `type_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT(20) UNSIGNED NOT NULL,
  `type_name` VARCHAR(100) NOT NULL,
  `type_description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`type_id`),
  FOREIGN KEY (`category_id`) REFERENCES `case_categories`(`category_id`) ON DELETE CASCADE,
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Case Assignments and Team
CREATE TABLE `case_assignments` (
  `assignment_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `role` VARCHAR(50) NOT NULL, -- e.g., lead_attorney, associate, paralegal
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` BIGINT(20) UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`assignment_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`case_id`) ON DELETE CASCADE,
  KEY `user_id` (`user_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Case Notes and Comments
CREATE TABLE `case_notes` (
  `note_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `note_content` TEXT NOT NULL,
  `is_private` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`note_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`case_id`) ON DELETE CASCADE,
  KEY `user_id` (`user_id`),
  KEY `is_private` (`is_private`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deadlines and Reminders
CREATE TABLE `case_deadlines` (
  `deadline_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `deadline_date` DATETIME NOT NULL,
  `priority` VARCHAR(20) NOT NULL DEFAULT 'normal', -- low, normal, high, urgent
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, completed, missed
  `created_by` BIGINT(20) UNSIGNED NOT NULL,
  `assigned_to` BIGINT(20) UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`deadline_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`case_id`) ON DELETE CASCADE,
  KEY `deadline_date` (`deadline_date`),
  KEY `status` (`status`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `deadline_reminders` (
  `reminder_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `deadline_id` BIGINT(20) UNSIGNED NOT NULL,
  `reminder_time` DATETIME NOT NULL,
  `reminder_type` VARCHAR(20) NOT NULL, -- email, sms, notification
  `is_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`reminder_id`),
  FOREIGN KEY (`deadline_id`) REFERENCES `case_deadlines`(`deadline_id`) ON DELETE CASCADE,
  KEY `reminder_time` (`reminder_time`),
  KEY `is_sent` (`is_sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Case Status and Workflow
CREATE TABLE `case_statuses` (
  `status_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  `status_description` TEXT NULL,
  `status_order` INT NOT NULL DEFAULT 0,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `case_workflows` (
  `workflow_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_name` VARCHAR(100) NOT NULL,
  `workflow_description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`workflow_id`),
  UNIQUE KEY `workflow_name` (`workflow_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `workflow_steps` (
  `step_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
  `status_id` BIGINT(20) UNSIGNED NOT NULL,
  `step_order` INT NOT NULL,
  `requirements` JSON NULL,
  `auto_actions` JSON NULL,
  PRIMARY KEY (`step_id`),
  FOREIGN KEY (`workflow_id`) REFERENCES `case_workflows`(`workflow_id`) ON DELETE CASCADE,
  FOREIGN KEY (`status_id`) REFERENCES `case_statuses`(`status_id`) ON DELETE CASCADE,
  UNIQUE KEY `workflow_step` (`workflow_id`, `step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `case_status_history` (
  `history_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` BIGINT(20) UNSIGNED NOT NULL,
  `status_id` BIGINT(20) UNSIGNED NOT NULL,
  `changed_by` BIGINT(20) UNSIGNED NOT NULL,
  `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT NULL,
  PRIMARY KEY (`history_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`case_id`) ON DELETE CASCADE,
  FOREIGN KEY (`status_id`) REFERENCES `case_statuses`(`status_id`) ON DELETE CASCADE,
  KEY `changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Data: Default Case Statuses
INSERT INTO `case_statuses` (`status_name`, `status_description`, `status_order`, `is_system`) VALUES
('New', 'Case has been created but not yet processed', 10, 1),
('In Progress', 'Case is actively being worked on', 20, 1),
('Pending Client', 'Waiting for client input or action', 30, 1),
('Pending Court', 'Waiting for court decision or scheduling', 40, 1),
('Pending Third Party', 'Waiting for response from third party', 50, 1),
('On Hold', 'Case temporarily paused', 60, 1),
('Resolved', 'Case has been resolved successfully', 70, 1),
('Closed', 'Case is completed and closed', 80, 1);

-- Initial Data: Default Case Categories
INSERT INTO `case_categories` (`category_name`, `category_description`) VALUES
('Personal Injury', 'Cases involving physical or psychological injury'),
('Family Law', 'Cases involving family relationships'),
('Criminal Defense', 'Cases involving criminal charges'),
('Estate Planning', 'Cases involving wills, trusts, and estates'),
('Business Law', 'Cases involving business matters'),
('Real Estate', 'Cases involving property and real estate');

-- Initial Data: Sample Case Types
INSERT INTO `case_types` (`category_id`, `type_name`, `type_description`) VALUES
(1, 'Auto Accident', 'Cases involving motor vehicle accidents'),
(1, 'Medical Malpractice', 'Cases involving medical negligence'),
(1, 'Slip and Fall', 'Cases involving premises liability'),
(2, 'Divorce', 'Cases involving marital dissolution'),
(2, 'Child Custody', 'Cases involving child custody disputes'),
(2, 'Adoption', 'Cases involving adoption proceedings'),
(3, 'DUI Defense', 'Cases involving driving under influence charges'),
(3, 'Felony Defense', 'Cases involving felony criminal charges'),
(3, 'Misdemeanor Defense', 'Cases involving misdemeanor criminal charges');

-- Initial Data: Default Workflow
INSERT INTO `case_workflows` (`workflow_name`, `workflow_description`, `is_active`) VALUES
('Standard Case Flow', 'Default workflow for most case types', 1);

-- Add workflow steps
INSERT INTO `workflow_steps` (`workflow_id`, `status_id`, `step_order`, `requirements`, `auto_actions`)
SELECT 
    1, -- workflow_id for 'Standard Case Flow'
    status_id,
    status_order / 10, -- Convert order to step number
    NULL, -- No specific requirements
    NULL  -- No auto actions
FROM `case_statuses`
ORDER BY status_order;