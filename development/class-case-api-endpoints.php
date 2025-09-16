<?php
/**
 * Case API Endpoints Class
 *
 * Handles all case management API endpoints for the Metcalf Legal Platform
 *
 * @package Metcalf_Legal_System
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Case API Endpoints Class
 *
 * @since 1.0.0
 */
class Metcalf_Case_API_Endpoints {

    /**
     * Case Manager instance
     *
     * @var Metcalf_Case_Manager
     */
    private $case_manager;

    /**
     * Auth Manager instance
     *
     * @var Metcalf_Auth_Manager
     */
    private $auth_manager;

    /**
     * Security Middleware instance
     *
     * @var Metcalf_Security_Middleware
     */
    private $security;

    /**
     * Constructor
     *
     * @param Metcalf_Case_Manager $case_manager Case Manager instance
     * @param Metcalf_Auth_Manager $auth_manager Auth Manager instance
     * @param Metcalf_Security_Middleware $security Security Middleware instance
     */
    public function __construct( $case_manager, $auth_manager, $security ) {
        $this->case_manager = $case_manager;
        $this->auth_manager = $auth_manager;
        $this->security = $security;
        
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register API routes
     *
     * @return void
     */
    public function register_routes() {
        // Case Management
        register_rest_route( 'metcalf/v1', '/cases', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_cases' ),
                'permission_callback' => array( $this, 'check_cases_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_case' ),
                'permission_callback' => array( $this, 'check_create_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_case' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_case' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_case' ),
                'permission_callback' => array( $this, 'check_delete_case_permissions' ),
            ),
        ) );
        
        // Timeline Management
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/timeline', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_timeline' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_timeline_event' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/timeline/(?P<event_id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_timeline_event' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_timeline_event' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
        ) );
        
        // Team & Assignments
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/team', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_team' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_team_member' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/team/(?P<user_id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_team_member' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'remove_team_member' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
        ) );
        
        // Notes & Comments
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/notes', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_notes' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_note' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/notes/(?P<note_id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_note' ),
                'permission_callback' => array( $this, 'check_note_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_note' ),
                'permission_callback' => array( $this, 'check_note_permissions' ),
            ),
        ) );
        
        // Deadlines & Reminders
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/deadlines', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_deadlines' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_deadline' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/deadlines/(?P<deadline_id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_deadline' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_deadline' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/deadlines/(?P<deadline_id>\d+)/complete', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'complete_deadline' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/deadlines/upcoming', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_upcoming_deadlines' ),
                'permission_callback' => array( $this, 'check_authenticated' ),
            ),
        ) );
        
        // Status & Workflow
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/status', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_status' ),
                'permission_callback' => array( $this, 'check_edit_case_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/(?P<id>\d+)/status/history', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_status_history' ),
                'permission_callback' => array( $this, 'check_case_permissions' ),
            ),
        ) );
        
        // Reference Data
        register_rest_route( 'metcalf/v1', '/cases/categories', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_categories' ),
                'permission_callback' => array( $this, 'check_authenticated' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/types', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_types' ),
                'permission_callback' => array( $this, 'check_authenticated' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/cases/statuses', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_statuses' ),
                'permission_callback' => array( $this, 'check_authenticated' ),
            ),
        ) );
        
        // Workflows
        register_rest_route( 'metcalf/v1', '/workflows', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_workflows' ),
                'permission_callback' => array( $this, 'check_authenticated' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_workflow' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
            ),
        ) );
        
        register_rest_route( 'metcalf/v1', '/workflows/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_workflow' ),
                'permission_callback' => array( $this, 'check_authenticated' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_workflow' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_workflow' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
            ),
        ) );
    }

    /**
     * Check if user is authenticated
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user is authenticated, WP_Error otherwise
     */
    public function check_authenticated( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        return true;
    }

    /**
     * Check if user has permission to view cases
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_cases_permissions( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        $user = get_user_by( 'id', $user_id );
        
        // Client portal users can only view their own cases
        if ( $user && $user->has_cap( 'client_portal_user' ) && ! $user->has_cap( 'law_firm_staff' ) ) {
            $request->set_param( 'client_id', $user_id );
        }
        
        return true;
    }

    /**
     * Check if user has permission to view a specific case
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_case_permissions( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        $case_id = $request->get_param( 'id' );
        $user = get_user_by( 'id', $user_id );
        
        // Admin and staff can access all cases
        if ( $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) ) ) {
            return true;
        }
        
        // Get the case
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return new WP_Error(
                'case_not_found',
                __( 'Case not found', 'metcalf-legal-system' ),
                array( 'status' => 404 )
            );
        }
        
        // Client portal users can only access their own cases
        if ( $user && $user->has_cap( 'client_portal_user' ) && $case->client_portal_user_id == $user_id ) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to access this case', 'metcalf-legal-system' ),
            array( 'status' => 403 )
        );
    }

    /**
     * Check if user has permission to create a case
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_create_case_permissions( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        $user = get_user_by( 'id', $user_id );
        
        // Only staff and admin can create cases
        if ( $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) ) ) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to create cases', 'metcalf-legal-system' ),
            array( 'status' => 403 )
        );
    }

    /**
     * Check if user has permission to edit a case
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_edit_case_permissions( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        $case_id = $request->get_param( 'id' );
        $user = get_user_by( 'id', $user_id );
        
        // Only staff and admin can edit cases
        if ( $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) ) ) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to edit cases', 'metcalf-legal-system' ),
            array( 'status' => 403 )
        );
    }

    /**
     * Check if user has permission to delete a case
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_delete_case_permissions( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        $user = get_user_by( 'id', $user_id );
        
        // Only admin can delete cases
        if ( $user && $user->has_cap( 'law_firm_admin' ) ) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to delete cases', 'metcalf-legal-system' ),
            array( 'status' => 403 )
        );
    }

    /**
     * Check if user has permission to edit a note
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_note_permissions( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        $case_id = $request->get_param( 'id' );
        $note_id = $request->get_param( 'note_id' );
        $user = get_user_by( 'id', $user_id );
        
        // Admin and staff can edit all notes
        if ( $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) ) ) {
            return true;
        }
        
        // Get the note
        $note = $this->case_manager->get_note( $note_id );
        
        if ( ! $note ) {
            return new WP_Error(
                'note_not_found',
                __( 'Note not found', 'metcalf-legal-system' ),
                array( 'status' => 404 )
            );
        }
        
        // Users can only edit their own notes
        if ( $note->user_id == $user_id ) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to edit this note', 'metcalf-legal-system' ),
            array( 'status' => 403 )
        );
    }

    /**
     * Check if user has admin permissions
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_admin_permissions( $request ) {
        $user_id = $this->validate_request_token( $request );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        $user = get_user_by( 'id', $user_id );
        
        // Only admin can access
        if ( $user && $user->has_cap( 'law_firm_admin' ) ) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to perform this action', 'metcalf-legal-system' ),
            array( 'status' => 403 )
        );
    }

    /**
     * Validate JWT token from request header
     *
     * @param WP_REST_Request $request Request object
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    private function validate_request_token( $request ) {
        $auth_header = $request->get_header( 'authorization' );
        
        if ( empty( $auth_header ) ) {
            return new WP_Error(
                'missing_auth_header',
                __( 'Authorization header is required', 'metcalf-legal-system' ),
                array( 'status' => 401 )
            );
        }
        
        // Check for Bearer token
        if ( strpos( $auth_header, 'Bearer ' ) !== 0 ) {
            return new WP_Error(
                'invalid_auth_header',
                __( 'Authorization header must be a Bearer token', 'metcalf-legal-system' ),
                array( 'status' => 401 )
            );
        }
        
        $token = substr( $auth_header, 7 );
        $user_id = $this->auth_manager->validate_jwt_token( $token );
        
        if ( ! $user_id ) {
            return new WP_Error(
                'invalid_token',
                __( 'Invalid or expired token', 'metcalf-legal-system' ),
                array( 'status' => 401 )
            );
        }
        
        return $user_id;
    }

    /**
     * Format API response
     *
     * @param mixed $data Response data
     * @param int $status HTTP status code
     * @return WP_REST_Response Formatted response
     */
    private function format_response( $data, $status = 200 ) {
        return new WP_REST_Response(
            array(
                'success' => $status >= 200 && $status < 300,
                'data'    => $data,
                'meta'    => array(
                    'timestamp' => current_time( 'c' ),
                    'version'   => '1.0',
                ),
            ),
            $status
        );
    }

    /**
     * Format error response
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param int $status HTTP status code
     * @return WP_REST_Response Formatted error response
     */
    private function format_error( $code, $message, $status = 400 ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'error'   => array(
                    'code'    => $code,
                    'message' => $message,
                ),
                'meta'    => array(
                    'timestamp' => current_time( 'c' ),
                    'version'   => '1.0',
                ),
            ),
            $status
        );
    }

    /**
     * Get cases
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_cases( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_cases_' . $user_id );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_cases', 'cases', null, $request );
        
        // Parse query parameters
        $args = array(
            'client_id'    => $request->get_param( 'client_id' ),
            'attorney_id'  => $request->get_param( 'attorney_id' ),
            'status'       => $request->get_param( 'status' ),
            'category'     => $request->get_param( 'category' ),
            'type'         => $request->get_param( 'type' ),
            'search'       => $request->get_param( 'search' ),
            'orderby'      => $request->get_param( 'orderby' ) ?: 'created_at',
            'order'        => $request->get_param( 'order' ) ?: 'DESC',
            'limit'        => min( 50, $request->get_param( 'limit' ) ?: 20 ),
            'offset'       => $request->get_param( 'offset' ) ?: 0,
        );
        
        // Get cases
        $cases = $this->case_manager->get_cases( $args );
        
        // Return response
        return $this->format_response( $cases );
    }

    /**
     * Get a single case
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_case( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_case_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_case', 'case', $case_id, $request );
        
        // Get case
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Return response
        return $this->format_response( $case );
    }

    /**
     * Create a new case
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function create_case( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'create_case_' . $user_id );
        
        // Sanitize input
        $data = array(
            'client_portal_user_id'    => absint( $request->get_param( 'client_portal_user_id' ) ),
            'lead_attorney_wp_user_id' => absint( $request->get_param( 'lead_attorney_wp_user_id' ) ),
            'case_title'               => sanitize_text_field( $request->get_param( 'case_title' ) ),
            'case_description'         => sanitize_textarea_field( $request->get_param( 'case_description' ) ),
            'case_type_id'             => absint( $request->get_param( 'case_type_id' ) ),
            'case_status'              => sanitize_text_field( $request->get_param( 'case_status' ) ?: 'new' ),
            'created_by'               => $user_id,
        );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'create_case', 'case', null, $request );
        
        // Validate required fields
        if ( empty( $data['client_portal_user_id'] ) || empty( $data['lead_attorney_wp_user_id'] ) || empty( $data['case_title'] ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Create case
        $case_id = $this->case_manager->create_case( $data );
        
        if ( ! $case_id ) {
            return $this->format_error( 'case_creation_failed', __( 'Failed to create case', 'metcalf-legal-system' ) );
        }
        
        // Get the created case
        $case = $this->case_manager->get_case( $case_id );
        
        // Return response
        return $this->format_response( $case, 201 );
    }

    /**
     * Update a case
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function update_case( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'update_case_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Get current case data for audit logging
        $case_before = $this->case_manager->get_case( $case_id );
        
        if ( ! $case_before ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Sanitize input
        $data = array();
        
        if ( $request->has_param( 'case_title' ) ) {
            $data['case_title'] = sanitize_text_field( $request->get_param( 'case_title' ) );
        }
        
        if ( $request->has_param( 'case_description' ) ) {
            $data['case_description'] = sanitize_textarea_field( $request->get_param( 'case_description' ) );
        }
        
        if ( $request->has_param( 'case_type_id' ) ) {
            $data['case_type_id'] = absint( $request->get_param( 'case_type_id' ) );
        }
        
        if ( $request->has_param( 'lead_attorney_wp_user_id' ) ) {
            $data['lead_attorney_wp_user_id'] = absint( $request->get_param( 'lead_attorney_wp_user_id' ) );
        }
        
        if ( $request->has_param( 'case_status' ) ) {
            $data['case_status'] = sanitize_text_field( $request->get_param( 'case_status' ) );
            $data['status_notes'] = sanitize_textarea_field( $request->get_param( 'status_notes' ) );
        }
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'update_case', 'case', $case_id, $request );
        
        // Update case
        $result = $this->case_manager->update_case( $case_id, $data );
        
        if ( ! $result ) {
            return $this->format_error( 'case_update_failed', __( 'Failed to update case', 'metcalf-legal-system' ) );
        }
        
        // Get the updated case
        $case_after = $this->case_manager->get_case( $case_id );
        
        // Log the data change
        $this->security->log_data_change( $user_id, 'update_case', 'case', $case_id, $case_before, $case_after );
        
        // Return response
        return $this->format_response( $case_after );
    }

    /**
     * Delete a case
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function delete_case( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'delete_case_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'delete_case', 'case', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Delete case
        $result = $this->case_manager->delete_case( $case_id );
        
        if ( ! $result ) {
            return $this->format_error( 'case_deletion_failed', __( 'Failed to delete case', 'metcalf-legal-system' ) );
        }
        
        // Return response
        return $this->format_response( array( 'deleted' => true ) );
    }

    /**
     * Get case timeline
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_timeline( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_timeline_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_timeline', 'timeline', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Get timeline
        $timeline = $this->case_manager->get_case_timeline( $case_id );
        
        // Return response
        return $this->format_response( $timeline );
    }

    /**
     * Add timeline event
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function add_timeline_event( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'add_timeline_event_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'add_timeline_event', 'timeline', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Sanitize input
        $title = sanitize_text_field( $request->get_param( 'event_title' ) );
        $description = sanitize_textarea_field( $request->get_param( 'event_description' ) );
        $date = sanitize_text_field( $request->get_param( 'event_date' ) );
        $is_completed = (bool) $request->get_param( 'is_completed' );
        
        // Validate required fields
        if ( empty( $title ) || empty( $date ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Add timeline event
        $event_id = $this->case_manager->add_timeline_event( $case_id, $title, $description, $date, $is_completed, $user_id );
        
        if ( ! $event_id ) {
            return $this->format_error( 'timeline_event_creation_failed', __( 'Failed to create timeline event', 'metcalf-legal-system' ) );
        }
        
        // Get the updated timeline
        $timeline = $this->case_manager->get_case_timeline( $case_id );
        
        // Return response
        return $this->format_response( $timeline, 201 );
    }

    /**
     * Update timeline event
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function update_timeline_event( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'update_timeline_event_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $event_id = $request->get_param( 'event_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'update_timeline_event', 'timeline', $event_id, $request );
        
        // Sanitize input
        $data = array();
        
        if ( $request->has_param( 'event_title' ) ) {
            $data['event_title'] = sanitize_text_field( $request->get_param( 'event_title' ) );
        }
        
        if ( $request->has_param( 'event_description' ) ) {
            $data['event_description'] = sanitize_textarea_field( $request->get_param( 'event_description' ) );
        }
        
        if ( $request->has_param( 'event_date' ) ) {
            $data['event_date'] = sanitize_text_field( $request->get_param( 'event_date' ) );
        }
        
        if ( $request->has_param( 'is_completed' ) ) {
            $data['is_completed'] = (bool) $request->get_param( 'is_completed' );
        }
        
        // Update timeline event
        $result = $this->case_manager->update_timeline_event( $event_id, $data );
        
        if ( ! $result ) {
            return $this->format_error( 'timeline_event_update_failed', __( 'Failed to update timeline event', 'metcalf-legal-system' ) );
        }
        
        // Get the updated timeline
        $timeline = $this->case_manager->get_case_timeline( $case_id );
        
        // Return response
        return $this->format_response( $timeline );
    }

    /**
     * Delete timeline event
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function delete_timeline_event( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'delete_timeline_event_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $event_id = $request->get_param( 'event_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'delete_timeline_event', 'timeline', $event_id, $request );
        
        // Delete timeline event
        $result = $this->case_manager->delete_timeline_event( $event_id );
        
        if ( ! $result ) {
            return $this->format_error( 'timeline_event_deletion_failed', __( 'Failed to delete timeline event', 'metcalf-legal-system' ) );
        }
        
        // Get the updated timeline
        $timeline = $this->case_manager->get_case_timeline( $case_id );
        
        // Return response
        return $this->format_response( $timeline );
    }

    /**
     * Get case team
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_team( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_team_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_team', 'team', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Get team
        $team = $this->case_manager->get_case_team( $case_id );
        
        // Return response
        return $this->format_response( $team );
    }

    /**
     * Add team member
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function add_team_member( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'add_team_member_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'add_team_member', 'team', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Sanitize input
        $team_user_id = absint( $request->get_param( 'user_id' ) );
        $role = sanitize_text_field( $request->get_param( 'role' ) );
        
        // Validate required fields
        if ( empty( $team_user_id ) || empty( $role ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Add team member
        $result = $this->case_manager->add_case_team_member( $case_id, $team_user_id, $role, $user_id );
        
        if ( ! $result ) {
            return $this->format_error( 'team_member_addition_failed', __( 'Failed to add team member', 'metcalf-legal-system' ) );
        }
        
        // Get the updated team
        $team = $this->case_manager->get_case_team( $case_id );
        
        // Return response
        return $this->format_response( $team );
    }

    /**
     * Update team member
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function update_team_member( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'update_team_member_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $team_user_id = $request->get_param( 'user_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'update_team_member', 'team', $case_id, $request );
        
        // Sanitize input
        $role = sanitize_text_field( $request->get_param( 'role' ) );
        
        // Validate required fields
        if ( empty( $role ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Update team member
        $result = $this->case_manager->add_case_team_member( $case_id, $team_user_id, $role, $user_id );
        
        if ( ! $result ) {
            return $this->format_error( 'team_member_update_failed', __( 'Failed to update team member', 'metcalf-legal-system' ) );
        }
        
        // Get the updated team
        $team = $this->case_manager->get_case_team( $case_id );
        
        // Return response
        return $this->format_response( $team );
    }

    /**
     * Remove team member
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function remove_team_member( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'remove_team_member_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $team_user_id = $request->get_param( 'user_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'remove_team_member', 'team', $case_id, $request );
        
        // Remove team member
        $result = $this->case_manager->remove_case_team_member( $case_id, $team_user_id );
        
        if ( ! $result ) {
            return $this->format_error( 'team_member_removal_failed', __( 'Failed to remove team member', 'metcalf-legal-system' ) );
        }
        
        // Get the updated team
        $team = $this->case_manager->get_case_team( $case_id );
        
        // Return response
        return $this->format_response( $team );
    }

    /**
     * Get case notes
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_notes( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_notes_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_notes', 'notes', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Get user
        $user = get_user_by( 'id', $user_id );
        
        // Determine if private notes should be included
        $include_private = $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) );
        
        // Get notes
        $notes = $this->case_manager->get_case_notes( $case_id, $include_private );
        
        // Return response
        return $this->format_response( $notes );
    }

    /**
     * Add case note
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function add_note( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'add_note_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'add_note', 'note', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Sanitize input
        $content = sanitize_textarea_field( $request->get_param( 'note_content' ) );
        $is_private = (bool) $request->get_param( 'is_private' );
        
        // Validate required fields
        if ( empty( $content ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Add note
        $note_id = $this->case_manager->add_case_note( $case_id, $user_id, $content, $is_private );
        
        if ( ! $note_id ) {
            return $this->format_error( 'note_creation_failed', __( 'Failed to create note', 'metcalf-legal-system' ) );
        }
        
        // Get user
        $user = get_user_by( 'id', $user_id );
        
        // Determine if private notes should be included
        $include_private = $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) );
        
        // Get the updated notes
        $notes = $this->case_manager->get_case_notes( $case_id, $include_private );
        
        // Return response
        return $this->format_response( $notes, 201 );
    }

    /**
     * Update case note
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function update_note( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'update_note_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $note_id = $request->get_param( 'note_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'update_note', 'note', $note_id, $request );
        
        // Sanitize input
        $content = sanitize_textarea_field( $request->get_param( 'note_content' ) );
        $is_private = $request->has_param( 'is_private' ) ? (bool) $request->get_param( 'is_private' ) : null;
        
        // Validate required fields
        if ( empty( $content ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Update note
        $result = $this->case_manager->update_case_note( $note_id, $content, $is_private );
        
        if ( ! $result ) {
            return $this->format_error( 'note_update_failed', __( 'Failed to update note', 'metcalf-legal-system' ) );
        }
        
        // Get user
        $user = get_user_by( 'id', $user_id );
        
        // Determine if private notes should be included
        $include_private = $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) );
        
        // Get the updated notes
        $notes = $this->case_manager->get_case_notes( $case_id, $include_private );
        
        // Return response
        return $this->format_response( $notes );
    }

    /**
     * Delete case note
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function delete_note( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'delete_note_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $note_id = $request->get_param( 'note_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'delete_note', 'note', $note_id, $request );
        
        // Delete note
        $result = $this->case_manager->delete_case_note( $note_id );
        
        if ( ! $result ) {
            return $this->format_error( 'note_deletion_failed', __( 'Failed to delete note', 'metcalf-legal-system' ) );
        }
        
        // Get user
        $user = get_user_by( 'id', $user_id );
        
        // Determine if private notes should be included
        $include_private = $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) );
        
        // Get the updated notes
        $notes = $this->case_manager->get_case_notes( $case_id, $include_private );
        
        // Return response
        return $this->format_response( $notes );
    }

    /**
     * Get case deadlines
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_deadlines( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_deadlines_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_deadlines', 'deadlines', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Get status filter
        $status = $request->get_param( 'status' ) ?: 'all';
        
        // Get deadlines
        $deadlines = $this->case_manager->get_case_deadlines( $case_id, $status );
        
        // Return response
        return $this->format_response( $deadlines );
    }

    /**
     * Add case deadline
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function add_deadline( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'add_deadline_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'add_deadline', 'deadline', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Sanitize input
        $data = array(
            'case_id'     => $case_id,
            'title'       => sanitize_text_field( $request->get_param( 'title' ) ),
            'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
            'deadline_date' => sanitize_text_field( $request->get_param( 'deadline_date' ) ),
            'priority'    => sanitize_text_field( $request->get_param( 'priority' ) ?: 'normal' ),
            'status'      => sanitize_text_field( $request->get_param( 'status' ) ?: 'pending' ),
            'created_by'  => $user_id,
            'assigned_to' => absint( $request->get_param( 'assigned_to' ) ),
        );
        
        // Get reminders
        $reminders = $request->get_param( 'reminders' );
        if ( is_array( $reminders ) ) {
            $data['reminders'] = array();
            foreach ( $reminders as $reminder ) {
                $data['reminders'][] = array(
                    'time' => sanitize_text_field( $reminder['time'] ),
                    'type' => sanitize_text_field( $reminder['type'] ),
                );
            }
        }
        
        // Validate required fields
        if ( empty( $data['title'] ) || empty( $data['deadline_date'] ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Add deadline
        $deadline_id = $this->case_manager->add_case_deadline( $data );
        
        if ( ! $deadline_id ) {
            return $this->format_error( 'deadline_creation_failed', __( 'Failed to create deadline', 'metcalf-legal-system' ) );
        }
        
        // Get the updated deadlines
        $deadlines = $this->case_manager->get_case_deadlines( $case_id );
        
        // Return response
        return $this->format_response( $deadlines, 201 );
    }

    /**
     * Update case deadline
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function update_deadline( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'update_deadline_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $deadline_id = $request->get_param( 'deadline_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'update_deadline', 'deadline', $deadline_id, $request );
        
        // Sanitize input
        $data = array();
        
        if ( $request->has_param( 'title' ) ) {
            $data['title'] = sanitize_text_field( $request->get_param( 'title' ) );
        }
        
        if ( $request->has_param( 'description' ) ) {
            $data['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
        }
        
        if ( $request->has_param( 'deadline_date' ) ) {
            $data['deadline_date'] = sanitize_text_field( $request->get_param( 'deadline_date' ) );
        }
        
        if ( $request->has_param( 'priority' ) ) {
            $data['priority'] = sanitize_text_field( $request->get_param( 'priority' ) );
        }
        
        if ( $request->has_param( 'status' ) ) {
            $data['status'] = sanitize_text_field( $request->get_param( 'status' ) );
        }
        
        if ( $request->has_param( 'assigned_to' ) ) {
            $data['assigned_to'] = absint( $request->get_param( 'assigned_to' ) );
        }
        
        // Update deadline
        $result = $this->case_manager->update_case_deadline( $deadline_id, $data );
        
        if ( ! $result ) {
            return $this->format_error( 'deadline_update_failed', __( 'Failed to update deadline', 'metcalf-legal-system' ) );
        }
        
        // Get the updated deadlines
        $deadlines = $this->case_manager->get_case_deadlines( $case_id );
        
        // Return response
        return $this->format_response( $deadlines );
    }

    /**
     * Delete case deadline
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function delete_deadline( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'delete_deadline_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $deadline_id = $request->get_param( 'deadline_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'delete_deadline', 'deadline', $deadline_id, $request );
        
        // Delete deadline
        $result = $this->case_manager->delete_case_deadline( $deadline_id );
        
        if ( ! $result ) {
            return $this->format_error( 'deadline_deletion_failed', __( 'Failed to delete deadline', 'metcalf-legal-system' ) );
        }
        
        // Get the updated deadlines
        $deadlines = $this->case_manager->get_case_deadlines( $case_id );
        
        // Return response
        return $this->format_response( $deadlines );
    }

    /**
     * Complete deadline
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function complete_deadline( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'complete_deadline_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        $deadline_id = $request->get_param( 'deadline_id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'complete_deadline', 'deadline', $deadline_id, $request );
        
        // Update deadline
        $result = $this->case_manager->update_case_deadline( $deadline_id, array( 'status' => 'completed' ) );
        
        if ( ! $result ) {
            return $this->format_error( 'deadline_completion_failed', __( 'Failed to complete deadline', 'metcalf-legal-system' ) );
        }
        
        // Get the updated deadlines
        $deadlines = $this->case_manager->get_case_deadlines( $case_id );
        
        // Return response
        return $this->format_response( $deadlines );
    }

    /**
     * Get upcoming deadlines
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_upcoming_deadlines( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_upcoming_deadlines_' . $user_id );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_upcoming_deadlines', 'deadlines', null, $request );
        
        // Sanitize input
        $args = array(
            'user_id' => $request->get_param( 'user_id' ) ?: $user_id,
            'days'    => min( 30, absint( $request->get_param( 'days' ) ?: 7 ) ),
            'status'  => sanitize_text_field( $request->get_param( 'status' ) ?: 'pending' ),
            'limit'   => min( 50, absint( $request->get_param( 'limit' ) ?: 10 ) ),
        );
        
        // Get user
        $user = get_user_by( 'id', $user_id );
        
        // Only admin and staff can view other users' deadlines
        if ( $args['user_id'] != $user_id && ! ( $user && ( $user->has_cap( 'law_firm_admin' ) || $user->has_cap( 'law_firm_staff' ) ) ) ) {
            $args['user_id'] = $user_id;
        }
        
        // Get deadlines
        $deadlines = $this->case_manager->get_upcoming_deadlines( $args );
        
        // Return response
        return $this->format_response( $deadlines );
    }

    /**
     * Update case status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function update_status( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'update_status_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'update_status', 'status', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Sanitize input
        $status = sanitize_text_field( $request->get_param( 'status' ) );
        $notes = sanitize_textarea_field( $request->get_param( 'notes' ) );
        
        // Validate required fields
        if ( empty( $status ) ) {
            return $this->format_error( 'missing_required_fields', __( 'Missing required fields', 'metcalf-legal-system' ) );
        }
        
        // Update case status
        $result = $this->case_manager->update_case( $case_id, array(
            'case_status' => $status,
            'status_notes' => $notes,
        ) );
        
        if ( ! $result ) {
            return $this->format_error( 'status_update_failed', __( 'Failed to update status', 'metcalf-legal-system' ) );
        }
        
        // Get the updated status info
        $status_info = $this->case_manager->get_case_status_info( $case_id );
        
        // Return response
        return $this->format_response( $status_info );
    }

    /**
     * Get status history
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_status_history( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_status_history_' . $user_id );
        
        $case_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_status_history', 'status', $case_id, $request );
        
        // Check if case exists
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            return $this->format_error( 'case_not_found', __( 'Case not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Get status info
        $status_info = $this->case_manager->get_case_status_info( $case_id );
        
        // Return response
        return $this->format_response( $status_info );
    }

    /**
     * Get case categories
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_categories( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_categories_' . $user_id );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_categories', 'categories', null, $request );
        
        // Get categories
        $categories = $this->case_manager->get_case_categories();
        
        // Return response
        return $this->format_response( $categories );
    }

    /**
     * Get case types
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_types( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_types_' . $user_id );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_types', 'types', null, $request );
        
        // Get category filter
        $category_id = absint( $request->get_param( 'category_id' ) );
        
        // Get types
        $types = $this->case_manager->get_case_types( $category_id );
        
        // Return response
        return $this->format_response( $types );
    }

    /**
     * Get case statuses
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_statuses( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_statuses_' . $user_id );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_statuses', 'statuses', null, $request );
        
        // Get statuses
        $statuses = $this->case_manager->get_case_statuses();
        
        // Return response
        return $this->format_response( $statuses );
    }

    /**
     * Get workflows
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_workflows( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_workflows_' . $user_id );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_workflows', 'workflows', null, $request );
        
        // Get active only filter
        $active_only = ! isset( $request['include_inactive'] ) || ! $request['include_inactive'];
        
        // Get workflows
        $workflows = $this->case_manager->get_workflows( $active_only );
        
        // Return response
        return $this->format_response( $workflows );
    }

    /**
     * Get workflow
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_workflow( $request ) {
        // Apply rate limiting
        $user_id = $this->validate_request_token( $request );
        $this->security->check_rate_limit( 'get_workflow_' . $user_id );
        
        $workflow_id = $request->get_param( 'id' );
        
        // Log the API request
        $this->security->log_api_request( $user_id, 'get_workflow', 'workflow', $workflow_id, $request );
        
        // Get workflows
        $workflows = $this->case_manager->get_workflows( false );
        
        // Find the requested workflow
        $workflow = null;
        foreach ( $workflows as $wf ) {
            if ( $wf->workflow_id == $workflow_id ) {
                $workflow = $wf;
                break;
            }
        }
        
        if ( ! $workflow ) {
            return $this->format_error( 'workflow_not_found', __( 'Workflow not found', 'metcalf-legal-system' ), 404 );
        }
        
        // Return response
        return $this->format_response( $workflow );
    }

    /**
     * Create workflow
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function create_workflow( $request ) {
        // This would be implemented in a workflow manager class
        return $this->format_error( 'not_implemented', __( 'This endpoint is not yet implemented', 'metcalf-legal-system' ), 501 );
    }

    /**
     * Update workflow
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function update_workflow( $request ) {
        // This would be implemented in a workflow manager class
        return $this->format_error( 'not_implemented', __( 'This endpoint is not yet implemented', 'metcalf-legal-system' ), 501 );
    }

    /**
     * Delete workflow
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function delete_workflow( $request ) {
        // This would be implemented in a workflow manager class
        return $this->format_error( 'not_implemented', __( 'This endpoint is not yet implemented', 'metcalf-legal-system' ), 501 );
    }
}