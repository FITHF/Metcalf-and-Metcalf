# Case Management System for Metcalf Legal Platform

This directory contains the implementation of Chunk 4: Case Management System for the Metcalf Legal Platform. The Case Management System provides comprehensive functionality for creating, tracking, and managing legal cases, including timeline management, team assignments, deadlines, and workflow automation.

## Overview

The Case Management System builds upon the existing foundation (Chunks 1-3) to provide a robust solution for law firms to manage their cases efficiently. It includes both backend PHP components for WordPress and frontend React components for the client portal.

### Key Features

1. **Complete Case Creation and Editing Interface**
   - Create and edit cases with detailed information
   - Assign cases to attorneys and clients
   - Categorize cases by type and status

2. **Interactive Case Timeline**
   - Track case milestones and events
   - Visual timeline representation
   - Mark events as completed

3. **Case Status Management and Workflow Automation**
   - Predefined and custom case statuses
   - Workflow steps with requirements
   - Status history tracking

4. **Attorney Assignment and Collaboration**
   - Assign team members to cases
   - Define roles for team members
   - Track team member activity

5. **Case Search and Filtering**
   - Advanced search capabilities
   - Filter by status, category, type, etc.
   - Sort and organize cases

6. **Deadline Tracking and Reminder System**
   - Create and manage case deadlines
   - Set reminders for important dates
   - Prioritize deadlines by urgency

## Directory Structure

```
development/
├── class-case-manager.php                # Core case management functionality
├── class-case-api-endpoints.php          # REST API endpoints for case management
├── class-case-management-integration.php # Integration with WordPress plugin
├── case_management_schema.sql            # Database schema for case management
├── templates/                            # WordPress admin templates
│   └── cases-page.php                    # Cases listing page template
├── CaseManagement.jsx                    # Main React component for case management
├── CaseTimeline.jsx                      # React component for case timeline
├── CaseDeadlines.jsx                     # React component for case deadlines
└── caseService.js                        # JavaScript service for API communication
```

## Installation and Integration

To integrate the Case Management System with the existing Metcalf Legal System:

1. Copy all PHP files to the Metcalf Legal System plugin directory
2. Copy the SQL schema file to the plugin directory
3. Copy React components to the client portal source directory
4. Include the integration class in the main plugin file

### Integration Code

Add the following code to the main plugin file (`metcalf-legal-system.php`):

```php
// Include Case Management System
require_once plugin_dir_path( __FILE__ ) . 'includes/class-case-management-integration.php';
```

## Database Schema

The Case Management System adds the following tables to the `wp_metcalf_cases` database:

- `case_categories` - Categories for cases
- `case_types` - Types of cases within categories
- `case_assignments` - Team member assignments to cases
- `case_notes` - Notes and comments on cases
- `case_deadlines` - Deadlines for case-related tasks
- `deadline_reminders` - Reminders for deadlines
- `case_statuses` - Available case statuses
- `case_workflows` - Workflow definitions for cases
- `workflow_steps` - Steps within workflows
- `case_status_history` - History of status changes

## API Endpoints

The Case Management System provides the following REST API endpoints:

### Case Management
- `GET /wp-json/metcalf/v1/cases` - List cases with filtering
- `POST /wp-json/metcalf/v1/cases` - Create new case
- `GET /wp-json/metcalf/v1/cases/{id}` - Get case details
- `PUT /wp-json/metcalf/v1/cases/{id}` - Update case
- `DELETE /wp-json/metcalf/v1/cases/{id}` - Delete/archive case

### Timeline Management
- `GET /wp-json/metcalf/v1/cases/{id}/timeline` - Get case timeline
- `POST /wp-json/metcalf/v1/cases/{id}/timeline` - Add timeline event
- `PUT /wp-json/metcalf/v1/cases/{id}/timeline/{event_id}` - Update timeline event
- `DELETE /wp-json/metcalf/v1/cases/{id}/timeline/{event_id}` - Delete timeline event

### Team & Assignments
- `GET /wp-json/metcalf/v1/cases/{id}/team` - Get case team
- `POST /wp-json/metcalf/v1/cases/{id}/team` - Add team member
- `PUT /wp-json/metcalf/v1/cases/{id}/team/{user_id}` - Update team member role
- `DELETE /wp-json/metcalf/v1/cases/{id}/team/{user_id}` - Remove team member

### Notes & Comments
- `GET /wp-json/metcalf/v1/cases/{id}/notes` - Get case notes
- `POST /wp-json/metcalf/v1/cases/{id}/notes` - Add case note
- `PUT /wp-json/metcalf/v1/cases/{id}/notes/{note_id}` - Update case note
- `DELETE /wp-json/metcalf/v1/cases/{id}/notes/{note_id}` - Delete case note

### Deadlines & Reminders
- `GET /wp-json/metcalf/v1/cases/{id}/deadlines` - Get case deadlines
- `POST /wp-json/metcalf/v1/cases/{id}/deadlines` - Add deadline
- `PUT /wp-json/metcalf/v1/cases/{id}/deadlines/{deadline_id}` - Update deadline
- `DELETE /wp-json/metcalf/v1/cases/{id}/deadlines/{deadline_id}` - Delete deadline
- `POST /wp-json/metcalf/v1/cases/{id}/deadlines/{deadline_id}/complete` - Mark as complete
- `GET /wp-json/metcalf/v1/deadlines/upcoming` - Get upcoming deadlines

### Status & Workflow
- `PUT /wp-json/metcalf/v1/cases/{id}/status` - Update case status
- `GET /wp-json/metcalf/v1/cases/{id}/status/history` - Get status history

### Reference Data
- `GET /wp-json/metcalf/v1/cases/categories` - Get case categories
- `GET /wp-json/metcalf/v1/cases/types` - Get case types
- `GET /wp-json/metcalf/v1/cases/statuses` - Get available statuses

## React Components

The Case Management System includes the following React components:

- `CaseManagement.jsx` - Main component for case management
- `CaseTimeline.jsx` - Component for case timeline
- `CaseDeadlines.jsx` - Component for case deadlines
- `caseService.js` - Service for API communication

To integrate these components with the client portal:

1. Copy the components to the client portal source directory
2. Import the components in the appropriate places
3. Add routes for the components in the client portal router

### Example Router Integration

```jsx
import CaseManagement from './components/Cases/CaseManagement';

// In your router configuration
<Route path="/cases" element={<CaseManagement />} />
<Route path="/cases/:id" element={<CaseManagement />} />
```

## Security Considerations

The Case Management System implements the following security measures:

1. **Authentication** - All API endpoints require JWT authentication
2. **Authorization** - Role-based access control for all operations
3. **Input Validation** - Thorough validation of all input data
4. **SQL Injection Prevention** - Prepared statements for all database queries
5. **Audit Logging** - Comprehensive logging of all actions
6. **Rate Limiting** - Protection against abuse

## Next Steps

After implementing the Case Management System, the next phase will be Chunk 5: Document Vault & File Management, which will build upon this foundation to provide secure document storage, version control, and sharing capabilities.

## Dependencies

The Case Management System depends on the following components from previous chunks:

1. **Database Abstraction Layer** (Chunk 1)
2. **Authentication System** (Chunk 2)
3. **Security Middleware** (Chunk 2)
4. **Client Portal Frontend** (Chunk 3)

## Testing

To test the Case Management System:

1. Activate the Metcalf Legal System plugin
2. Navigate to the Cases menu in the WordPress admin
3. Create a new case and verify all functionality
4. Access the client portal and verify case management functionality
5. Test all API endpoints using a tool like Postman

## Troubleshooting

If you encounter issues with the Case Management System:

1. Check the WordPress error log for PHP errors
2. Check the browser console for JavaScript errors
3. Verify that all database tables were created correctly
4. Ensure that the JWT authentication is working properly
5. Verify that all API endpoints are accessible