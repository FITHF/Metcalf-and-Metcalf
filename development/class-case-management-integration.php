<?php
/**
 * Case Management Integration Class
 *
 * Integrates the Case Management System with the existing Metcalf Legal System plugin
 *
 * @package Metcalf_Legal_System
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Case Management Integration Class
 *
 * @since 1.0.0
 */
class Metcalf_Case_Management_Integration {

    /**
     * Case Manager instance
     *
     * @var Metcalf_Case_Manager
     */
    private $case_manager;

    /**
     * Case API Endpoints instance
     *
     * @var Metcalf_Case_API_Endpoints
     */
    private $api_endpoints;

    /**
     * Constructor
     */
    public function __construct() {
        // Register activation hook for database tables
        add_action( 'metcalf_legal_system_activate', array( $this, 'activate' ) );
        
        // Initialize on plugins loaded
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Activation hook
     *
     * @return void
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Add initial data
        $this->add_initial_data();
    }

    /**
     * Initialize the integration
     *
     * @return void
     */
    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->initialize_components();
        
        // Register hooks
        $this->register_hooks();
    }

    /**
     * Load dependencies
     *
     * @return void
     */
    private function load_dependencies() {
        // Include required files
        require_once plugin_dir_path( __FILE__ ) . 'class-case-manager.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-case-api-endpoints.php';
    }

    /**
     * Initialize components
     *
     * @return void
     */
    private function initialize_components() {
        global $metcalf_auth_manager, $metcalf_security_middleware;
        
        // Initialize Case Manager
        $this->case_manager = new Metcalf_Case_Manager();
        
        // Initialize API Endpoints
        $this->api_endpoints = new Metcalf_Case_API_Endpoints(
            $this->case_manager,
            $metcalf_auth_manager,
            $metcalf_security_middleware
        );
    }

    /**
     * Register hooks
     *
     * @return void
     */
    private function register_hooks() {
        // Add menu items
        add_action( 'admin_menu', array( $this, 'add_menu_items' ) );
        
        // Add dashboard widgets
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
        
        // Register scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
        
        // Add custom columns to user list
        add_filter( 'manage_users_columns', array( $this, 'add_user_columns' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'user_column_content' ), 10, 3 );
        
        // Add case count to admin bar
        add_action( 'admin_bar_menu', array( $this, 'add_cases_to_admin_bar' ), 999 );
        
        // Add AJAX handlers
        add_action( 'wp_ajax_metcalf_get_case_data', array( $this, 'ajax_get_case_data' ) );
        add_action( 'wp_ajax_metcalf_get_upcoming_deadlines', array( $this, 'ajax_get_upcoming_deadlines' ) );
    }

    /**
     * Create database tables
     *
     * @return void
     */
    private function create_tables() {
        global $wpdb;
        
        // Get the SQL file content
        $sql_file = plugin_dir_path( __FILE__ ) . 'case_management_schema.sql';
        
        if ( file_exists( $sql_file ) ) {
            // Read SQL file
            $sql = file_get_contents( $sql_file );
            
            // Get database name
            $cases_db_name = $wpdb->base_prefix . 'metcalf_cases';
            
            // Select the cases database
            $wpdb->query( "USE `{$cases_db_name}`" );
            
            // Split SQL by semicolon
            $statements = explode( ';', $sql );
            
            // Execute each statement
            foreach ( $statements as $statement ) {
                $statement = trim( $statement );
                if ( ! empty( $statement ) ) {
                    $wpdb->query( $statement );
                }
            }
            
            // Switch back to WordPress database
            $wpdb->select( DB_NAME );
        }
    }

    /**
     * Add initial data
     *
     * @return void
     */
    private function add_initial_data() {
        // This would be implemented to add initial categories, statuses, etc.
        // For now, the initial data is included in the SQL file
    }

    /**
     * Add menu items
     *
     * @return void
     */
    public function add_menu_items() {
        // Add Cases menu
        add_menu_page(
            __( 'Cases', 'metcalf-legal-system' ),
            __( 'Cases', 'metcalf-legal-system' ),
            'edit_posts',
            'metcalf-cases',
            array( $this, 'render_cases_page' ),
            'dashicons-portfolio',
            30
        );
        
        // Add Cases submenu
        add_submenu_page(
            'metcalf-cases',
            __( 'All Cases', 'metcalf-legal-system' ),
            __( 'All Cases', 'metcalf-legal-system' ),
            'edit_posts',
            'metcalf-cases',
            array( $this, 'render_cases_page' )
        );
        
        // Add New Case submenu
        add_submenu_page(
            'metcalf-cases',
            __( 'Add New Case', 'metcalf-legal-system' ),
            __( 'Add New Case', 'metcalf-legal-system' ),
            'publish_posts',
            'metcalf-add-case',
            array( $this, 'render_add_case_page' )
        );
        
        // Add Deadlines submenu
        add_submenu_page(
            'metcalf-cases',
            __( 'Deadlines', 'metcalf-legal-system' ),
            __( 'Deadlines', 'metcalf-legal-system' ),
            'edit_posts',
            'metcalf-deadlines',
            array( $this, 'render_deadlines_page' )
        );
        
        // Add Case Settings submenu
        add_submenu_page(
            'metcalf-cases',
            __( 'Case Settings', 'metcalf-legal-system' ),
            __( 'Case Settings', 'metcalf-legal-system' ),
            'manage_options',
            'metcalf-case-settings',
            array( $this, 'render_case_settings_page' )
        );
    }

    /**
     * Add dashboard widgets
     *
     * @return void
     */
    public function add_dashboard_widgets() {
        // Add Recent Cases widget
        wp_add_dashboard_widget(
            'metcalf_recent_cases',
            __( 'Recent Cases', 'metcalf-legal-system' ),
            array( $this, 'render_recent_cases_widget' )
        );
        
        // Add Upcoming Deadlines widget
        wp_add_dashboard_widget(
            'metcalf_upcoming_deadlines',
            __( 'Upcoming Deadlines', 'metcalf-legal-system' ),
            array( $this, 'render_upcoming_deadlines_widget' )
        );
    }

    /**
     * Register admin assets
     *
     * @param string $hook Current admin page
     * @return void
     */
    public function register_admin_assets( $hook ) {
        // Only load on our pages
        if ( strpos( $hook, 'metcalf-' ) === false ) {
            return;
        }
        
        // Register and enqueue styles
        wp_register_style(
            'metcalf-case-management-admin',
            plugin_dir_url( __FILE__ ) . 'assets/css/case-management-admin.css',
            array(),
            '1.0.0'
        );
        wp_enqueue_style( 'metcalf-case-management-admin' );
        
        // Register and enqueue scripts
        wp_register_script(
            'metcalf-case-management-admin',
            plugin_dir_url( __FILE__ ) . 'assets/js/case-management-admin.js',
            array( 'jquery', 'jquery-ui-datepicker' ),
            '1.0.0',
            true
        );
        wp_enqueue_script( 'metcalf-case-management-admin' );
        
        // Localize script
        wp_localize_script(
            'metcalf-case-management-admin',
            'metcalfCaseManagement',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'metcalf_case_management_nonce' ),
                'i18n' => array(
                    'confirmDelete' => __( 'Are you sure you want to delete this item? This action cannot be undone.', 'metcalf-legal-system' ),
                    'errorLoading' => __( 'Error loading data. Please try again.', 'metcalf-legal-system' ),
                    'successMessage' => __( 'Operation completed successfully.', 'metcalf-legal-system' ),
                    'errorMessage' => __( 'An error occurred. Please try again.', 'metcalf-legal-system' ),
                ),
            )
        );
    }

    /**
     * Register frontend assets
     *
     * @return void
     */
    public function register_frontend_assets() {
        // Only load on client portal pages
        if ( ! is_page( 'client-portal' ) && ! is_page( 'case-management' ) ) {
            return;
        }
        
        // Register and enqueue styles
        wp_register_style(
            'metcalf-case-management-frontend',
            plugin_dir_url( __FILE__ ) . 'assets/css/case-management-frontend.css',
            array(),
            '1.0.0'
        );
        wp_enqueue_style( 'metcalf-case-management-frontend' );
        
        // Register and enqueue scripts
        wp_register_script(
            'metcalf-case-management-frontend',
            plugin_dir_url( __FILE__ ) . 'assets/js/case-management-frontend.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
        wp_enqueue_script( 'metcalf-case-management-frontend' );
        
        // Localize script
        wp_localize_script(
            'metcalf-case-management-frontend',
            'metcalfCaseManagement',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'metcalf_case_management_nonce' ),
                'apiRoot' => esc_url_raw( rest_url() ),
                'apiNamespace' => 'metcalf/v1',
            )
        );
    }

    /**
     * Add user columns
     *
     * @param array $columns User columns
     * @return array Modified columns
     */
    public function add_user_columns( $columns ) {
        $columns['case_count'] = __( 'Cases', 'metcalf-legal-system' );
        return $columns;
    }

    /**
     * User column content
     *
     * @param string $output Column output
     * @param string $column_name Column name
     * @param int $user_id User ID
     * @return string Modified output
     */
    public function user_column_content( $output, $column_name, $user_id ) {
        if ( $column_name === 'case_count' ) {
            // Get case count for this user
            $args = array(
                'attorney_id' => $user_id,
            );
            $cases = $this->case_manager->get_cases( $args );
            $count = count( $cases );
            
            // Create link to filtered cases page
            $url = admin_url( 'admin.php?page=metcalf-cases&attorney_id=' . $user_id );
            $output = '<a href="' . esc_url( $url ) . '">' . $count . '</a>';
        }
        return $output;
    }

    /**
     * Add cases to admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     * @return void
     */
    public function add_cases_to_admin_bar( $wp_admin_bar ) {
        // Only show for users who can edit posts
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }
        
        // Get upcoming deadlines count
        $args = array(
            'user_id' => get_current_user_id(),
            'days' => 7,
            'status' => 'pending',
        );
        $deadlines = $this->case_manager->get_upcoming_deadlines( $args );
        $count = count( $deadlines );
        
        // Add main node
        $wp_admin_bar->add_node( array(
            'id' => 'metcalf-cases',
            'title' => sprintf(
                __( 'Cases %s', 'metcalf-legal-system' ),
                $count > 0 ? '<span class="awaiting-mod">' . $count . '</span>' : ''
            ),
            'href' => admin_url( 'admin.php?page=metcalf-cases' ),
        ) );
        
        // Add submenu items
        $wp_admin_bar->add_node( array(
            'parent' => 'metcalf-cases',
            'id' => 'metcalf-all-cases',
            'title' => __( 'All Cases', 'metcalf-legal-system' ),
            'href' => admin_url( 'admin.php?page=metcalf-cases' ),
        ) );
        
        $wp_admin_bar->add_node( array(
            'parent' => 'metcalf-cases',
            'id' => 'metcalf-add-case',
            'title' => __( 'Add New Case', 'metcalf-legal-system' ),
            'href' => admin_url( 'admin.php?page=metcalf-add-case' ),
        ) );
        
        $wp_admin_bar->add_node( array(
            'parent' => 'metcalf-cases',
            'id' => 'metcalf-deadlines',
            'title' => sprintf(
                __( 'Deadlines %s', 'metcalf-legal-system' ),
                $count > 0 ? '<span class="awaiting-mod">' . $count . '</span>' : ''
            ),
            'href' => admin_url( 'admin.php?page=metcalf-deadlines' ),
        ) );
    }

    /**
     * AJAX handler for getting case data
     *
     * @return void
     */
    public function ajax_get_case_data() {
        // Check nonce
        check_ajax_referer( 'metcalf_case_management_nonce', 'nonce' );
        
        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'metcalf-legal-system' ) ) );
        }
        
        // Get case ID
        $case_id = isset( $_POST['case_id'] ) ? intval( $_POST['case_id'] ) : 0;
        
        if ( ! $case_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid case ID.', 'metcalf-legal-system' ) ) );
        }
        
        // Get case data
        $case = $this->case_manager->get_case( $case_id );
        
        if ( ! $case ) {
            wp_send_json_error( array( 'message' => __( 'Case not found.', 'metcalf-legal-system' ) ) );
        }
        
        // Send response
        wp_send_json_success( array( 'case' => $case ) );
    }

    /**
     * AJAX handler for getting upcoming deadlines
     *
     * @return void
     */
    public function ajax_get_upcoming_deadlines() {
        // Check nonce
        check_ajax_referer( 'metcalf_case_management_nonce', 'nonce' );
        
        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'metcalf-legal-system' ) ) );
        }
        
        // Get parameters
        $days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 7;
        $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 10;
        
        // Get upcoming deadlines
        $args = array(
            'user_id' => get_current_user_id(),
            'days' => $days,
            'status' => 'pending',
            'limit' => $limit,
        );
        $deadlines = $this->case_manager->get_upcoming_deadlines( $args );
        
        // Send response
        wp_send_json_success( array( 'deadlines' => $deadlines ) );
    }

    /**
     * Render cases page
     *
     * @return void
     */
    public function render_cases_page() {
        // Get filters
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $category = isset( $_GET['category'] ) ? intval( $_GET['category'] ) : 0;
        $type = isset( $_GET['type'] ) ? intval( $_GET['type'] ) : 0;
        $search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
        $attorney_id = isset( $_GET['attorney_id'] ) ? intval( $_GET['attorney_id'] ) : 0;
        
        // Get cases
        $args = array(
            'status' => $status,
            'category' => $category,
            'type' => $type,
            'search' => $search,
            'attorney_id' => $attorney_id,
        );
        $cases = $this->case_manager->get_cases( $args );
        
        // Get reference data
        $categories = $this->case_manager->get_case_categories();
        $types = $this->case_manager->get_case_types();
        $statuses = $this->case_manager->get_case_statuses();
        
        // Include template
        include plugin_dir_path( __FILE__ ) . 'templates/cases-page.php';
    }

    /**
     * Render add case page
     *
     * @return void
     */
    public function render_add_case_page() {
        // Get reference data
        $categories = $this->case_manager->get_case_categories();
        $types = $this->case_manager->get_case_types();
        $statuses = $this->case_manager->get_case_statuses();
        
        // Include template
        include plugin_dir_path( __FILE__ ) . 'templates/add-case-page.php';
    }

    /**
     * Render deadlines page
     *
     * @return void
     */
    public function render_deadlines_page() {
        // Get filters
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'pending';
        $days = isset( $_GET['days'] ) ? intval( $_GET['days'] ) : 30;
        
        // Get upcoming deadlines
        $args = array(
            'user_id' => get_current_user_id(),
            'days' => $days,
            'status' => $status,
            'limit' => 100,
        );
        $deadlines = $this->case_manager->get_upcoming_deadlines( $args );
        
        // Include template
        include plugin_dir_path( __FILE__ ) . 'templates/deadlines-page.php';
    }

    /**
     * Render case settings page
     *
     * @return void
     */
    public function render_case_settings_page() {
        // Get workflows
        $workflows = $this->case_manager->get_workflows( false );
        
        // Include template
        include plugin_dir_path( __FILE__ ) . 'templates/case-settings-page.php';
    }

    /**
     * Render recent cases widget
     *
     * @return void
     */
    public function render_recent_cases_widget() {
        // Get recent cases
        $args = array(
            'limit' => 5,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        // If user is not admin, only show their cases
        if ( ! current_user_can( 'manage_options' ) ) {
            $args['attorney_id'] = get_current_user_id();
        }
        
        $cases = $this->case_manager->get_cases( $args );
        
        // Include template
        include plugin_dir_path( __FILE__ ) . 'templates/widget-recent-cases.php';
    }

    /**
     * Render upcoming deadlines widget
     *
     * @return void
     */
    public function render_upcoming_deadlines_widget() {
        // Get upcoming deadlines
        $args = array(
            'user_id' => get_current_user_id(),
            'days' => 7,
            'status' => 'pending',
            'limit' => 5,
        );
        $deadlines = $this->case_manager->get_upcoming_deadlines( $args );
        
        // Include template
        include plugin_dir_path( __FILE__ ) . 'templates/widget-upcoming-deadlines.php';
    }
}

// Initialize the integration
$metcalf_case_management = new Metcalf_Case_Management_Integration();