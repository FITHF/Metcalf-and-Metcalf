<?php
/**
 * Case Manager Class
 *
 * Handles all case management operations for the Metcalf Legal Platform
 *
 * @package Metcalf_Legal_System
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Case Manager Class
 *
 * @since 1.0.0
 */
class Metcalf_Case_Manager {

    /**
     * Database abstraction instance
     *
     * @var Metcalf_Database_Abstraction
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        global $metcalf_db;
        $this->db = $metcalf_db;
        
        // Register activation hook for database tables
        add_action( 'metcalf_legal_system_activate', array( $this, 'create_tables' ) );
        
        // Register hooks for case management
        add_action( 'init', array( $this, 'register_hooks' ) );
    }

    /**
     * Register hooks and filters
     *
     * @return void
     */
    public function register_hooks() {
        // Add any necessary hooks here
    }

    /**
     * Create database tables for case management
     *
     * @return void
     */
    public function create_tables() {
        // Get the SQL file content
        $sql_file = plugin_dir_path( __FILE__ ) . 'case_management_schema.sql';
        
        if ( file_exists( $sql_file ) ) {
            $sql = file_get_contents( $sql_file );
            
            // Split SQL by semicolon
            $statements = explode( ';', $sql );
            
            // Execute each statement
            foreach ( $statements as $statement ) {
                $statement = trim( $statement );
                if ( ! empty( $statement ) ) {
                    $this->db->query( $statement );
                }
            }
        }
    }

    /**
     * Get all cases with optional filtering
     *
     * @param array $args Query arguments
     * @return array Array of case objects
     */
    public function get_cases( $args = array() ) {
        $defaults = array(
            'client_id'    => 0,
            'attorney_id'  => 0,
            'status'       => '',
            'category'     => 0,
            'type'         => 0,
            'search'       => '',
            'orderby'      => 'created_at',
            'order'        => 'DESC',
            'limit'        => 20,
            'offset'       => 0,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Start building the query
        $query = "SELECT c.* FROM {$this->db->cases} c";
        $where = array( '1=1' );
        $params = array();
        
        // Add filters
        if ( ! empty( $args['client_id'] ) ) {
            $where[] = 'c.client_portal_user_id = %d';
            $params[] = $args['client_id'];
        }
        
        if ( ! empty( $args['attorney_id'] ) ) {
            $query .= " JOIN {$this->db->case_assignments} ca ON c.case_id = ca.case_id";
            $where[] = 'ca.user_id = %d AND ca.is_active = 1';
            $params[] = $args['attorney_id'];
        }
        
        if ( ! empty( $args['status'] ) ) {
            $where[] = 'c.case_status = %s';
            $params[] = $args['status'];
        }
        
        if ( ! empty( $args['category'] ) ) {
            $query .= " JOIN {$this->db->case_types} ct ON c.case_type_id = ct.type_id";
            $where[] = 'ct.category_id = %d';
            $params[] = $args['category'];
        }
        
        if ( ! empty( $args['type'] ) ) {
            $where[] = 'c.case_type_id = %d';
            $params[] = $args['type'];
        }
        
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(c.case_title LIKE %s OR c.case_description LIKE %s)';
            $search_term = '%' . $this->db->esc_like( $args['search'] ) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Complete the query
        $query .= ' WHERE ' . implode( ' AND ', $where );
        $query .= " ORDER BY c.{$args['orderby']} {$args['order']}";
        $query .= " LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        // Execute the query
        $cases = $this->db->get_results( $this->db->prepare( $query, $params ) );
        
        // Enhance case data with additional information
        if ( ! empty( $cases ) ) {
            foreach ( $cases as &$case ) {
                $case->timeline = $this->get_case_timeline( $case->case_id );
                $case->team = $this->get_case_team( $case->case_id );
                $case->status_info = $this->get_case_status_info( $case->case_id );
            }
        }
        
        return $cases;
    }

    /**
     * Get a single case by ID
     *
     * @param int $case_id Case ID
     * @return object|false Case object or false if not found
     */
    public function get_case( $case_id ) {
        $query = "SELECT * FROM {$this->db->cases} WHERE case_id = %d";
        $case = $this->db->get_row( $this->db->prepare( $query, $case_id ) );
        
        if ( ! $case ) {
            return false;
        }
        
        // Add additional case data
        $case->timeline = $this->get_case_timeline( $case_id );
        $case->team = $this->get_case_team( $case_id );
        $case->notes = $this->get_case_notes( $case_id );
        $case->deadlines = $this->get_case_deadlines( $case_id );
        $case->status_info = $this->get_case_status_info( $case_id );
        
        return $case;
    }

    /**
     * Create a new case
     *
     * @param array $data Case data
     * @return int|false Case ID on success, false on failure
     */
    public function create_case( $data ) {
        $defaults = array(
            'client_portal_user_id' => 0,
            'lead_attorney_wp_user_id' => 0,
            'case_title' => '',
            'case_description' => '',
            'case_type_id' => 0,
            'case_status' => 'new',
            'created_by' => get_current_user_id(),
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        // Validate required fields
        if ( empty( $data['client_portal_user_id'] ) || empty( $data['lead_attorney_wp_user_id'] ) || empty( $data['case_title'] ) ) {
            return false;
        }
        
        // Insert case
        $result = $this->db->insert(
            $this->db->cases,
            array(
                'client_portal_user_id' => $data['client_portal_user_id'],
                'lead_attorney_wp_user_id' => $data['lead_attorney_wp_user_id'],
                'case_title' => $data['case_title'],
                'case_description' => $data['case_description'],
                'case_type_id' => $data['case_type_id'],
                'case_status' => $data['case_status'],
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
        );
        
        if ( ! $result ) {
            return false;
        }
        
        $case_id = $this->db->insert_id;
        
        // Add lead attorney to case assignments
        $this->add_case_team_member( $case_id, $data['lead_attorney_wp_user_id'], 'lead_attorney', $data['created_by'] );
        
        // Add initial status to history
        $this->add_status_history( $case_id, $data['case_status'], $data['created_by'] );
        
        // Add initial timeline event
        $this->add_timeline_event(
            $case_id,
            'Case Created',
            'Case has been created and assigned to attorney.',
            current_time( 'mysql' ),
            true,
            $data['created_by']
        );
        
        return $case_id;
    }

    /**
     * Update an existing case
     *
     * @param int $case_id Case ID
     * @param array $data Case data to update
     * @return bool True on success, false on failure
     */
    public function update_case( $case_id, $data ) {
        // Get current case data
        $current_case = $this->get_case( $case_id );
        
        if ( ! $current_case ) {
            return false;
        }
        
        $update_data = array();
        $format = array();
        
        // Only update fields that are provided
        if ( isset( $data['case_title'] ) ) {
            $update_data['case_title'] = $data['case_title'];
            $format[] = '%s';
        }
        
        if ( isset( $data['case_description'] ) ) {
            $update_data['case_description'] = $data['case_description'];
            $format[] = '%s';
        }
        
        if ( isset( $data['case_type_id'] ) ) {
            $update_data['case_type_id'] = $data['case_type_id'];
            $format[] = '%d';
        }
        
        if ( isset( $data['lead_attorney_wp_user_id'] ) && $data['lead_attorney_wp_user_id'] != $current_case->lead_attorney_wp_user_id ) {
            $update_data['lead_attorney_wp_user_id'] = $data['lead_attorney_wp_user_id'];
            $format[] = '%d';
            
            // Update case assignments
            $this->update_lead_attorney( $case_id, $data['lead_attorney_wp_user_id'], get_current_user_id() );
        }
        
        if ( isset( $data['case_status'] ) && $data['case_status'] != $current_case->case_status ) {
            $update_data['case_status'] = $data['case_status'];
            $format[] = '%s';
            
            // Add status change to history
            $this->add_status_history( $case_id, $data['case_status'], get_current_user_id(), isset( $data['status_notes'] ) ? $data['status_notes'] : '' );
        }
        
        if ( empty( $update_data ) ) {
            return true; // Nothing to update
        }
        
        // Update the case
        $result = $this->db->update(
            $this->db->cases,
            $update_data,
            array( 'case_id' => $case_id ),
            $format,
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Delete/archive a case
     *
     * @param int $case_id Case ID
     * @return bool True on success, false on failure
     */
    public function delete_case( $case_id ) {
        // In a production system, we would typically archive rather than delete
        // For this implementation, we'll just mark the case as 'archived'
        return $this->update_case( $case_id, array( 'case_status' => 'archived' ) );
    }

    /**
     * Get case timeline events
     *
     * @param int $case_id Case ID
     * @return array Array of timeline events
     */
    public function get_case_timeline( $case_id ) {
        $query = "SELECT * FROM {$this->db->case_timeline} WHERE case_id = %d ORDER BY event_date ASC";
        return $this->db->get_results( $this->db->prepare( $query, $case_id ) );
    }

    /**
     * Add timeline event
     *
     * @param int $case_id Case ID
     * @param string $title Event title
     * @param string $description Event description
     * @param string $date Event date (MySQL format)
     * @param bool $is_completed Whether the event is completed
     * @param int $created_by User ID who created the event
     * @return int|false Event ID on success, false on failure
     */
    public function add_timeline_event( $case_id, $title, $description, $date, $is_completed, $created_by ) {
        $result = $this->db->insert(
            $this->db->case_timeline,
            array(
                'case_id' => $case_id,
                'event_title' => $title,
                'event_description' => $description,
                'event_date' => $date,
                'is_completed' => $is_completed ? 1 : 0,
                'created_by' => $created_by,
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%d' )
        );
        
        return $result ? $this->db->insert_id : false;
    }

    /**
     * Update timeline event
     *
     * @param int $event_id Event ID
     * @param array $data Event data
     * @return bool True on success, false on failure
     */
    public function update_timeline_event( $event_id, $data ) {
        $update_data = array();
        $format = array();
        
        if ( isset( $data['event_title'] ) ) {
            $update_data['event_title'] = $data['event_title'];
            $format[] = '%s';
        }
        
        if ( isset( $data['event_description'] ) ) {
            $update_data['event_description'] = $data['event_description'];
            $format[] = '%s';
        }
        
        if ( isset( $data['event_date'] ) ) {
            $update_data['event_date'] = $data['event_date'];
            $format[] = '%s';
        }
        
        if ( isset( $data['is_completed'] ) ) {
            $update_data['is_completed'] = $data['is_completed'] ? 1 : 0;
            $format[] = '%d';
        }
        
        if ( empty( $update_data ) ) {
            return true; // Nothing to update
        }
        
        $result = $this->db->update(
            $this->db->case_timeline,
            $update_data,
            array( 'timeline_event_id' => $event_id ),
            $format,
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Delete timeline event
     *
     * @param int $event_id Event ID
     * @return bool True on success, false on failure
     */
    public function delete_timeline_event( $event_id ) {
        $result = $this->db->delete(
            $this->db->case_timeline,
            array( 'timeline_event_id' => $event_id ),
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Get case team members
     *
     * @param int $case_id Case ID
     * @param bool $active_only Whether to get only active team members
     * @return array Array of team members
     */
    public function get_case_team( $case_id, $active_only = true ) {
        $query = "SELECT ca.*, u.display_name 
                 FROM {$this->db->case_assignments} ca
                 JOIN {$this->db->users} u ON ca.user_id = u.ID
                 WHERE ca.case_id = %d";
        
        if ( $active_only ) {
            $query .= " AND ca.is_active = 1";
        }
        
        $query .= " ORDER BY ca.role ASC, u.display_name ASC";
        
        return $this->db->get_results( $this->db->prepare( $query, $case_id ) );
    }

    /**
     * Add team member to case
     *
     * @param int $case_id Case ID
     * @param int $user_id User ID
     * @param string $role Role (lead_attorney, associate, paralegal, etc.)
     * @param int $assigned_by User ID who assigned the team member
     * @return int|false Assignment ID on success, false on failure
     */
    public function add_case_team_member( $case_id, $user_id, $role, $assigned_by ) {
        // Check if user is already assigned to this case
        $existing = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->case_assignments} WHERE case_id = %d AND user_id = %d",
                $case_id,
                $user_id
            )
        );
        
        if ( $existing ) {
            // If inactive, reactivate
            if ( ! $existing->is_active ) {
                return $this->db->update(
                    $this->db->case_assignments,
                    array(
                        'role' => $role,
                        'is_active' => 1,
                        'assigned_at' => current_time( 'mysql' ),
                        'assigned_by' => $assigned_by,
                    ),
                    array( 'assignment_id' => $existing->assignment_id ),
                    array( '%s', '%d', '%s', '%d' ),
                    array( '%d' )
                );
            }
            
            // Already active, update role if different
            if ( $existing->role != $role ) {
                return $this->db->update(
                    $this->db->case_assignments,
                    array( 'role' => $role ),
                    array( 'assignment_id' => $existing->assignment_id ),
                    array( '%s' ),
                    array( '%d' )
                );
            }
            
            return $existing->assignment_id;
        }
        
        // Add new team member
        $result = $this->db->insert(
            $this->db->case_assignments,
            array(
                'case_id' => $case_id,
                'user_id' => $user_id,
                'role' => $role,
                'assigned_at' => current_time( 'mysql' ),
                'assigned_by' => $assigned_by,
                'is_active' => 1,
            ),
            array( '%d', '%d', '%s', '%s', '%d', '%d' )
        );
        
        return $result ? $this->db->insert_id : false;
    }

    /**
     * Remove team member from case
     *
     * @param int $case_id Case ID
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function remove_case_team_member( $case_id, $user_id ) {
        // We don't actually delete, just mark as inactive
        $result = $this->db->update(
            $this->db->case_assignments,
            array( 'is_active' => 0 ),
            array( 'case_id' => $case_id, 'user_id' => $user_id ),
            array( '%d' ),
            array( '%d', '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Update lead attorney for a case
     *
     * @param int $case_id Case ID
     * @param int $attorney_id Attorney user ID
     * @param int $assigned_by User ID who made the change
     * @return bool True on success, false on failure
     */
    public function update_lead_attorney( $case_id, $attorney_id, $assigned_by ) {
        // Add as team member with lead_attorney role
        $this->add_case_team_member( $case_id, $attorney_id, 'lead_attorney', $assigned_by );
        
        // Update the case record
        $result = $this->db->update(
            $this->db->cases,
            array( 'lead_attorney_wp_user_id' => $attorney_id ),
            array( 'case_id' => $case_id ),
            array( '%d' ),
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Get case notes
     *
     * @param int $case_id Case ID
     * @param bool $include_private Whether to include private notes
     * @return array Array of notes
     */
    public function get_case_notes( $case_id, $include_private = true ) {
        $query = "SELECT cn.*, u.display_name as author_name
                 FROM {$this->db->case_notes} cn
                 JOIN {$this->db->users} u ON cn.user_id = u.ID
                 WHERE cn.case_id = %d";
        
        if ( ! $include_private ) {
            $query .= " AND cn.is_private = 0";
        }
        
        $query .= " ORDER BY cn.created_at DESC";
        
        return $this->db->get_results( $this->db->prepare( $query, $case_id ) );
    }

    /**
     * Add case note
     *
     * @param int $case_id Case ID
     * @param int $user_id User ID adding the note
     * @param string $content Note content
     * @param bool $is_private Whether the note is private
     * @return int|false Note ID on success, false on failure
     */
    public function add_case_note( $case_id, $user_id, $content, $is_private = false ) {
        $result = $this->db->insert(
            $this->db->case_notes,
            array(
                'case_id' => $case_id,
                'user_id' => $user_id,
                'note_content' => $content,
                'is_private' => $is_private ? 1 : 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%s' )
        );
        
        return $result ? $this->db->insert_id : false;
    }

    /**
     * Update case note
     *
     * @param int $note_id Note ID
     * @param string $content Updated content
     * @param bool $is_private Whether the note is private
     * @return bool True on success, false on failure
     */
    public function update_case_note( $note_id, $content, $is_private = null ) {
        $update_data = array( 'note_content' => $content );
        $format = array( '%s' );
        
        if ( $is_private !== null ) {
            $update_data['is_private'] = $is_private ? 1 : 0;
            $format[] = '%d';
        }
        
        $result = $this->db->update(
            $this->db->case_notes,
            $update_data,
            array( 'note_id' => $note_id ),
            $format,
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Delete case note
     *
     * @param int $note_id Note ID
     * @return bool True on success, false on failure
     */
    public function delete_case_note( $note_id ) {
        $result = $this->db->delete(
            $this->db->case_notes,
            array( 'note_id' => $note_id ),
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Get case deadlines
     *
     * @param int $case_id Case ID
     * @param string $status Filter by status (pending, completed, missed, all)
     * @return array Array of deadlines
     */
    public function get_case_deadlines( $case_id, $status = 'all' ) {
        $query = "SELECT cd.*, u1.display_name as created_by_name, u2.display_name as assigned_to_name
                 FROM {$this->db->case_deadlines} cd
                 JOIN {$this->db->users} u1 ON cd.created_by = u1.ID
                 LEFT JOIN {$this->db->users} u2 ON cd.assigned_to = u2.ID
                 WHERE cd.case_id = %d";
        
        $params = array( $case_id );
        
        if ( $status !== 'all' ) {
            $query .= " AND cd.status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY cd.deadline_date ASC";
        
        $deadlines = $this->db->get_results( $this->db->prepare( $query, $params ) );
        
        // Add reminders to each deadline
        if ( ! empty( $deadlines ) ) {
            foreach ( $deadlines as &$deadline ) {
                $deadline->reminders = $this->get_deadline_reminders( $deadline->deadline_id );
            }
        }
        
        return $deadlines;
    }

    /**
     * Add case deadline
     *
     * @param array $data Deadline data
     * @return int|false Deadline ID on success, false on failure
     */
    public function add_case_deadline( $data ) {
        $defaults = array(
            'case_id' => 0,
            'title' => '',
            'description' => '',
            'deadline_date' => '',
            'priority' => 'normal',
            'status' => 'pending',
            'created_by' => get_current_user_id(),
            'assigned_to' => null,
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        // Validate required fields
        if ( empty( $data['case_id'] ) || empty( $data['title'] ) || empty( $data['deadline_date'] ) ) {
            return false;
        }
        
        $result = $this->db->insert(
            $this->db->case_deadlines,
            array(
                'case_id' => $data['case_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'deadline_date' => $data['deadline_date'],
                'priority' => $data['priority'],
                'status' => $data['status'],
                'created_by' => $data['created_by'],
                'assigned_to' => $data['assigned_to'],
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
        );
        
        if ( ! $result ) {
            return false;
        }
        
        $deadline_id = $this->db->insert_id;
        
        // Add reminders if provided
        if ( ! empty( $data['reminders'] ) && is_array( $data['reminders'] ) ) {
            foreach ( $data['reminders'] as $reminder ) {
                $this->add_deadline_reminder( $deadline_id, $reminder['time'], $reminder['type'] );
            }
        }
        
        return $deadline_id;
    }

    /**
     * Update case deadline
     *
     * @param int $deadline_id Deadline ID
     * @param array $data Deadline data
     * @return bool True on success, false on failure
     */
    public function update_case_deadline( $deadline_id, $data ) {
        $update_data = array();
        $format = array();
        
        if ( isset( $data['title'] ) ) {
            $update_data['title'] = $data['title'];
            $format[] = '%s';
        }
        
        if ( isset( $data['description'] ) ) {
            $update_data['description'] = $data['description'];
            $format[] = '%s';
        }
        
        if ( isset( $data['deadline_date'] ) ) {
            $update_data['deadline_date'] = $data['deadline_date'];
            $format[] = '%s';
        }
        
        if ( isset( $data['priority'] ) ) {
            $update_data['priority'] = $data['priority'];
            $format[] = '%s';
        }
        
        if ( isset( $data['status'] ) ) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
            
            // If marked as completed, set completed_at
            if ( $data['status'] === 'completed' ) {
                $update_data['completed_at'] = current_time( 'mysql' );
                $format[] = '%s';
            }
        }
        
        if ( isset( $data['assigned_to'] ) ) {
            $update_data['assigned_to'] = $data['assigned_to'];
            $format[] = '%d';
        }
        
        if ( empty( $update_data ) ) {
            return true; // Nothing to update
        }
        
        $result = $this->db->update(
            $this->db->case_deadlines,
            $update_data,
            array( 'deadline_id' => $deadline_id ),
            $format,
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Delete case deadline
     *
     * @param int $deadline_id Deadline ID
     * @return bool True on success, false on failure
     */
    public function delete_case_deadline( $deadline_id ) {
        // Delete reminders first
        $this->db->delete(
            $this->db->deadline_reminders,
            array( 'deadline_id' => $deadline_id ),
            array( '%d' )
        );
        
        // Then delete the deadline
        $result = $this->db->delete(
            $this->db->case_deadlines,
            array( 'deadline_id' => $deadline_id ),
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Get deadline reminders
     *
     * @param int $deadline_id Deadline ID
     * @return array Array of reminders
     */
    public function get_deadline_reminders( $deadline_id ) {
        $query = "SELECT * FROM {$this->db->deadline_reminders} WHERE deadline_id = %d ORDER BY reminder_time ASC";
        return $this->db->get_results( $this->db->prepare( $query, $deadline_id ) );
    }

    /**
     * Add deadline reminder
     *
     * @param int $deadline_id Deadline ID
     * @param string $reminder_time Reminder time (MySQL format)
     * @param string $reminder_type Reminder type (email, sms, notification)
     * @return int|false Reminder ID on success, false on failure
     */
    public function add_deadline_reminder( $deadline_id, $reminder_time, $reminder_type ) {
        $result = $this->db->insert(
            $this->db->deadline_reminders,
            array(
                'deadline_id' => $deadline_id,
                'reminder_time' => $reminder_time,
                'reminder_type' => $reminder_type,
                'is_sent' => 0,
            ),
            array( '%d', '%s', '%s', '%d' )
        );
        
        return $result ? $this->db->insert_id : false;
    }

    /**
     * Mark reminder as sent
     *
     * @param int $reminder_id Reminder ID
     * @return bool True on success, false on failure
     */
    public function mark_reminder_sent( $reminder_id ) {
        $result = $this->db->update(
            $this->db->deadline_reminders,
            array(
                'is_sent' => 1,
                'sent_at' => current_time( 'mysql' ),
            ),
            array( 'reminder_id' => $reminder_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Get case status information
     *
     * @param int $case_id Case ID
     * @return object Status information
     */
    public function get_case_status_info( $case_id ) {
        // Get current status
        $case = $this->db->get_row( $this->db->prepare( "SELECT case_status FROM {$this->db->cases} WHERE case_id = %d", $case_id ) );
        
        if ( ! $case ) {
            return false;
        }
        
        // Get status details
        $status = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->db->case_statuses} WHERE status_name = %s", $case->case_status ) );
        
        // Get status history
        $history = $this->db->get_results( $this->db->prepare(
            "SELECT csh.*, cs.status_name, u.display_name as changed_by_name
             FROM {$this->db->case_status_history} csh
             JOIN {$this->db->case_statuses} cs ON csh.status_id = cs.status_id
             JOIN {$this->db->users} u ON csh.changed_by = u.ID
             WHERE csh.case_id = %d
             ORDER BY csh.changed_at DESC",
            $case_id
        ) );
        
        return (object) array(
            'current_status' => $status,
            'history' => $history,
        );
    }

    /**
     * Add status history entry
     *
     * @param int $case_id Case ID
     * @param string $status_name Status name
     * @param int $changed_by User ID who changed the status
     * @param string $notes Notes about the status change
     * @return int|false History ID on success, false on failure
     */
    public function add_status_history( $case_id, $status_name, $changed_by, $notes = '' ) {
        // Get status ID
        $status = $this->db->get_row( $this->db->prepare( "SELECT status_id FROM {$this->db->case_statuses} WHERE status_name = %s", $status_name ) );
        
        if ( ! $status ) {
            return false;
        }
        
        $result = $this->db->insert(
            $this->db->case_status_history,
            array(
                'case_id' => $case_id,
                'status_id' => $status->status_id,
                'changed_by' => $changed_by,
                'changed_at' => current_time( 'mysql' ),
                'notes' => $notes,
            ),
            array( '%d', '%d', '%d', '%s', '%s' )
        );
        
        return $result ? $this->db->insert_id : false;
    }

    /**
     * Get case categories
     *
     * @return array Array of categories
     */
    public function get_case_categories() {
        return $this->db->get_results( "SELECT * FROM {$this->db->case_categories} ORDER BY category_name ASC" );
    }

    /**
     * Get case types
     *
     * @param int $category_id Optional category ID to filter by
     * @return array Array of case types
     */
    public function get_case_types( $category_id = 0 ) {
        if ( $category_id ) {
            $query = "SELECT * FROM {$this->db->case_types} WHERE category_id = %d ORDER BY type_name ASC";
            return $this->db->get_results( $this->db->prepare( $query, $category_id ) );
        } else {
            $query = "SELECT ct.*, cc.category_name 
                     FROM {$this->db->case_types} ct
                     JOIN {$this->db->case_categories} cc ON ct.category_id = cc.category_id
                     ORDER BY cc.category_name ASC, ct.type_name ASC";
            return $this->db->get_results( $query );
        }
    }

    /**
     * Get case statuses
     *
     * @return array Array of statuses
     */
    public function get_case_statuses() {
        return $this->db->get_results( "SELECT * FROM {$this->db->case_statuses} ORDER BY status_order ASC" );
    }

    /**
     * Get workflows
     *
     * @param bool $active_only Whether to get only active workflows
     * @return array Array of workflows
     */
    public function get_workflows( $active_only = true ) {
        $query = "SELECT * FROM {$this->db->case_workflows}";
        
        if ( $active_only ) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY workflow_name ASC";
        
        $workflows = $this->db->get_results( $query );
        
        // Add steps to each workflow
        if ( ! empty( $workflows ) ) {
            foreach ( $workflows as &$workflow ) {
                $workflow->steps = $this->get_workflow_steps( $workflow->workflow_id );
            }
        }
        
        return $workflows;
    }

    /**
     * Get workflow steps
     *
     * @param int $workflow_id Workflow ID
     * @return array Array of workflow steps
     */
    public function get_workflow_steps( $workflow_id ) {
        $query = "SELECT ws.*, cs.status_name, cs.status_description
                 FROM {$this->db->workflow_steps} ws
                 JOIN {$this->db->case_statuses} cs ON ws.status_id = cs.status_id
                 WHERE ws.workflow_id = %d
                 ORDER BY ws.step_order ASC";
        
        return $this->db->get_results( $this->db->prepare( $query, $workflow_id ) );
    }

    /**
     * Get upcoming deadlines
     *
     * @param array $args Query arguments
     * @return array Array of deadlines
     */
    public function get_upcoming_deadlines( $args = array() ) {
        $defaults = array(
            'user_id' => 0,
            'days' => 7,
            'status' => 'pending',
            'limit' => 10,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $query = "SELECT cd.*, c.case_title, u1.display_name as created_by_name, u2.display_name as assigned_to_name
                 FROM {$this->db->case_deadlines} cd
                 JOIN {$this->db->cases} c ON cd.case_id = c.case_id
                 JOIN {$this->db->users} u1 ON cd.created_by = u1.ID
                 LEFT JOIN {$this->db->users} u2 ON cd.assigned_to = u2.ID
                 WHERE cd.deadline_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL %d DAY)";
        
        $params = array( $args['days'] );
        
        if ( ! empty( $args['status'] ) ) {
            $query .= " AND cd.status = %s";
            $params[] = $args['status'];
        }
        
        if ( ! empty( $args['user_id'] ) ) {
            $query .= " AND (cd.assigned_to = %d OR cd.created_by = %d)";
            $params[] = $args['user_id'];
            $params[] = $args['user_id'];
        }
        
        $query .= " ORDER BY cd.deadline_date ASC LIMIT %d";
        $params[] = $args['limit'];
        
        return $this->db->get_results( $this->db->prepare( $query, $params ) );
    }
}