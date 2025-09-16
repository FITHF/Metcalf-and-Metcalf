# Chunk 4: Case Management System - Implementation Plan

## Overview
The Case Management System will build upon the existing foundation to provide comprehensive case management capabilities for the Metcalf Legal Platform. This system will allow attorneys and staff to create, track, and manage legal cases while providing clients with visibility into their case status and progress.

## Components & Features

### 1. Case Creation and Editing Interface

#### Backend Requirements
- Create new API endpoints for case CRUD operations
- Implement validation for case data
- Add support for case metadata and custom fields
- Implement case history tracking

#### Frontend Components
- `CaseForm.jsx` - Form for creating and editing cases
- `CaseDetails.jsx` - Detailed view of case information
- `CaseMetadata.jsx` - Component for managing case metadata
- `CaseHistory.jsx` - Component showing case change history

### 2. Interactive Case Timeline

#### Backend Requirements
- Extend the existing case_timeline table with additional fields
- Create API endpoints for timeline management
- Implement milestone tracking logic

#### Frontend Components
- `CaseTimeline.jsx` - Visual timeline component
- `MilestoneEditor.jsx` - Interface for creating/editing milestones
- `TimelineControls.jsx` - Controls for timeline navigation
- `MilestoneDetails.jsx` - Detailed view of milestone information

### 3. Case Status Management and Workflow Automation

#### Backend Requirements
- Create a workflow engine for case status transitions
- Implement status-based notifications
- Add API endpoints for status updates
- Create automation rules system

#### Frontend Components
- `CaseStatus.jsx` - Status display and update component
- `WorkflowEditor.jsx` - Interface for configuring workflows
- `AutomationRules.jsx` - Component for setting up automation rules
- `StatusHistory.jsx` - History of status changes

### 4. Attorney Assignment and Collaboration

#### Backend Requirements
- Create attorney-case relationship management
- Implement collaboration features (notes, comments)
- Add notification system for assignments
- Create API endpoints for assignment operations

#### Frontend Components
- `AttorneyAssignment.jsx` - Interface for assigning attorneys
- `CollaborationTools.jsx` - Tools for team collaboration
- `CaseNotes.jsx` - Component for adding/viewing case notes
- `TeamView.jsx` - View showing all team members on a case

### 5. Case Search and Filtering

#### Backend Requirements
- Implement advanced search functionality
- Create filters for case attributes
- Add sorting options
- Optimize database queries for performance

#### Frontend Components
- `CaseSearch.jsx` - Search interface with autocomplete
- `FilterControls.jsx` - UI for applying filters
- `SearchResults.jsx` - Display of search results
- `SavedSearches.jsx` - Management of saved searches

### 6. Deadline Tracking and Reminder System

#### Backend Requirements
- Create deadline management system
- Implement reminder notifications
- Add calendar integration
- Create API endpoints for deadline operations

#### Frontend Components
- `DeadlineManager.jsx` - Interface for managing deadlines
- `ReminderSettings.jsx` - Configuration for reminders
- `DeadlineCalendar.jsx` - Calendar view of deadlines
- `UpcomingDeadlines.jsx` - List of approaching deadlines

## Database Schema Additions

### 1. Case Categories and Types
```sql
-- Add to wp_metcalf_cases database
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
```

### 2. Case Assignments and Team
```sql
-- Add to wp_metcalf_cases database
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
```

### 3. Case Notes and Comments
```sql
-- Add to wp_metcalf_cases database
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
```

### 4. Deadlines and Reminders
```sql
-- Add to wp_metcalf_cases database
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
```

### 5. Case Status and Workflow
```sql
-- Add to wp_metcalf_cases database
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
```

## API Endpoints

### Case Management
```
GET    /wp-json/metcalf/v1/cases                      # List cases with filtering
POST   /wp-json/metcalf/v1/cases                      # Create new case
GET    /wp-json/metcalf/v1/cases/{id}                 # Get case details
PUT    /wp-json/metcalf/v1/cases/{id}                 # Update case
DELETE /wp-json/metcalf/v1/cases/{id}                 # Delete/archive case
GET    /wp-json/metcalf/v1/cases/categories           # Get case categories
GET    /wp-json/metcalf/v1/cases/types                # Get case types
GET    /wp-json/metcalf/v1/cases/statuses             # Get available statuses
```

### Timeline Management
```
GET    /wp-json/metcalf/v1/cases/{id}/timeline        # Get case timeline
POST   /wp-json/metcalf/v1/cases/{id}/timeline        # Add timeline event
PUT    /wp-json/metcalf/v1/cases/{id}/timeline/{event_id} # Update timeline event
DELETE /wp-json/metcalf/v1/cases/{id}/timeline/{event_id} # Delete timeline event
```

### Team & Assignments
```
GET    /wp-json/metcalf/v1/cases/{id}/team            # Get case team
POST   /wp-json/metcalf/v1/cases/{id}/team            # Add team member
DELETE /wp-json/metcalf/v1/cases/{id}/team/{user_id}  # Remove team member
PUT    /wp-json/metcalf/v1/cases/{id}/team/{user_id}  # Update team member role
```

### Notes & Comments
```
GET    /wp-json/metcalf/v1/cases/{id}/notes           # Get case notes
POST   /wp-json/metcalf/v1/cases/{id}/notes           # Add case note
PUT    /wp-json/metcalf/v1/cases/{id}/notes/{note_id} # Update case note
DELETE /wp-json/metcalf/v1/cases/{id}/notes/{note_id} # Delete case note
```

### Deadlines & Reminders
```
GET    /wp-json/metcalf/v1/cases/{id}/deadlines       # Get case deadlines
POST   /wp-json/metcalf/v1/cases/{id}/deadlines       # Add deadline
PUT    /wp-json/metcalf/v1/cases/{id}/deadlines/{deadline_id} # Update deadline
DELETE /wp-json/metcalf/v1/cases/{id}/deadlines/{deadline_id} # Delete deadline
POST   /wp-json/metcalf/v1/cases/{id}/deadlines/{deadline_id}/complete # Mark as complete
GET    /wp-json/metcalf/v1/deadlines/upcoming         # Get upcoming deadlines
```

### Workflow Management
```
GET    /wp-json/metcalf/v1/workflows                  # Get available workflows
GET    /wp-json/metcalf/v1/workflows/{id}             # Get workflow details
POST   /wp-json/metcalf/v1/workflows                  # Create workflow
PUT    /wp-json/metcalf/v1/workflows/{id}             # Update workflow
DELETE /wp-json/metcalf/v1/workflows/{id}             # Delete workflow
PUT    /wp-json/metcalf/v1/cases/{id}/status          # Update case status
GET    /wp-json/metcalf/v1/cases/{id}/status/history  # Get status history
```

## Implementation Strategy

1. **Database First**: Implement the database schema changes first
2. **Backend API**: Create the PHP classes and API endpoints
3. **Frontend Components**: Develop the React components
4. **Integration**: Connect frontend to backend
5. **Testing**: Comprehensive testing of all features
6. **Documentation**: Update API documentation and user guides

## Security Considerations

- Ensure all API endpoints have proper authentication and authorization
- Implement role-based access control for case data
- Add audit logging for all case modifications
- Encrypt sensitive case information
- Implement data validation for all inputs
- Add rate limiting for API endpoints