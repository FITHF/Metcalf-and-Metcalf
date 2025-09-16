<?php
/**
 * Template for the Cases admin page
 *
 * @package Metcalf_Legal_System
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap metcalf-cases-page">
    <h1 class="wp-heading-inline"><?php _e( 'Cases', 'metcalf-legal-system' ); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=metcalf-add-case' ); ?>" class="page-title-action"><?php _e( 'Add New', 'metcalf-legal-system' ); ?></a>
    <hr class="wp-header-end">
    
    <div class="metcalf-cases-filters">
        <form method="get">
            <input type="hidden" name="page" value="metcalf-cases">
            
            <div class="metcalf-filter-row">
                <div class="metcalf-filter-item">
                    <label for="status"><?php _e( 'Status', 'metcalf-legal-system' ); ?></label>
                    <select name="status" id="status">
                        <option value=""><?php _e( 'All Statuses', 'metcalf-legal-system' ); ?></option>
                        <?php foreach ( $statuses as $status ) : ?>
                            <option value="<?php echo esc_attr( $status->status_name ); ?>" <?php selected( $status->status_name, $status ); ?>>
                                <?php echo esc_html( $status->status_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="metcalf-filter-item">
                    <label for="category"><?php _e( 'Category', 'metcalf-legal-system' ); ?></label>
                    <select name="category" id="category">
                        <option value=""><?php _e( 'All Categories', 'metcalf-legal-system' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->category_id ); ?>" <?php selected( $cat->category_id, $category ); ?>>
                                <?php echo esc_html( $cat->category_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="metcalf-filter-item">
                    <label for="type"><?php _e( 'Type', 'metcalf-legal-system' ); ?></label>
                    <select name="type" id="type">
                        <option value=""><?php _e( 'All Types', 'metcalf-legal-system' ); ?></option>
                        <?php foreach ( $types as $t ) : ?>
                            <option value="<?php echo esc_attr( $t->type_id ); ?>" <?php selected( $t->type_id, $type ); ?>>
                                <?php echo esc_html( $t->type_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="metcalf-filter-item">
                    <label for="search"><?php _e( 'Search', 'metcalf-legal-system' ); ?></label>
                    <input type="text" name="search" id="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php _e( 'Search cases...', 'metcalf-legal-system' ); ?>">
                </div>
                
                <div class="metcalf-filter-item">
                    <button type="submit" class="button"><?php _e( 'Filter', 'metcalf-legal-system' ); ?></button>
                    <a href="<?php echo admin_url( 'admin.php?page=metcalf-cases' ); ?>" class="button-link"><?php _e( 'Reset', 'metcalf-legal-system' ); ?></a>
                </div>
            </div>
        </form>
    </div>
    
    <?php if ( empty( $cases ) ) : ?>
        <div class="metcalf-no-results">
            <p><?php _e( 'No cases found.', 'metcalf-legal-system' ); ?></p>
        </div>
    <?php else : ?>
        <div class="metcalf-cases-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-id"><?php _e( 'ID', 'metcalf-legal-system' ); ?></th>
                        <th scope="col" class="column-title"><?php _e( 'Title', 'metcalf-legal-system' ); ?></th>
                        <th scope="col" class="column-client"><?php _e( 'Client', 'metcalf-legal-system' ); ?></th>
                        <th scope="col" class="column-attorney"><?php _e( 'Lead Attorney', 'metcalf-legal-system' ); ?></th>
                        <th scope="col" class="column-status"><?php _e( 'Status', 'metcalf-legal-system' ); ?></th>
                        <th scope="col" class="column-type"><?php _e( 'Type', 'metcalf-legal-system' ); ?></th>
                        <th scope="col" class="column-date"><?php _e( 'Created', 'metcalf-legal-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $cases as $case ) : 
                        // Get client name
                        $client = get_userdata( $case->client_portal_user_id );
                        $client_name = $client ? $client->display_name : __( 'Unknown', 'metcalf-legal-system' );
                        
                        // Get attorney name
                        $attorney = get_userdata( $case->lead_attorney_wp_user_id );
                        $attorney_name = $attorney ? $attorney->display_name : __( 'Unknown', 'metcalf-legal-system' );
                        
                        // Get case type
                        $case_type = '';
                        foreach ( $types as $t ) {
                            if ( isset( $case->case_type_id ) && $t->type_id == $case->case_type_id ) {
                                $case_type = $t->type_name;
                                break;
                            }
                        }
                        
                        // Format date
                        $created_date = date_i18n( get_option( 'date_format' ), strtotime( $case->created_at ) );
                    ?>
                        <tr>
                            <td class="column-id">
                                <?php echo esc_html( $case->case_id ); ?>
                            </td>
                            <td class="column-title">
                                <strong>
                                    <a href="<?php echo admin_url( 'admin.php?page=metcalf-cases&action=edit&case_id=' . $case->case_id ); ?>" class="row-title">
                                        <?php echo esc_html( $case->case_title ); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url( 'admin.php?page=metcalf-cases&action=edit&case_id=' . $case->case_id ); ?>">
                                            <?php _e( 'Edit', 'metcalf-legal-system' ); ?>
                                        </a> | 
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo admin_url( 'admin.php?page=metcalf-cases&action=view&case_id=' . $case->case_id ); ?>">
                                            <?php _e( 'View', 'metcalf-legal-system' ); ?>
                                        </a> | 
                                    </span>
                                    <span class="trash">
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=metcalf-cases&action=delete&case_id=' . $case->case_id ), 'delete_case_' . $case->case_id ); ?>" class="submitdelete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this case?', 'metcalf-legal-system' ); ?>');">
                                            <?php _e( 'Delete', 'metcalf-legal-system' ); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-client">
                                <?php echo esc_html( $client_name ); ?>
                            </td>
                            <td class="column-attorney">
                                <?php echo esc_html( $attorney_name ); ?>
                            </td>
                            <td class="column-status">
                                <span class="metcalf-status-badge status-<?php echo sanitize_html_class( $case->case_status ); ?>">
                                    <?php echo esc_html( $case->case_status ); ?>
                                </span>
                            </td>
                            <td class="column-type">
                                <?php echo esc_html( $case_type ); ?>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html( $created_date ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .metcalf-cases-filters {
        margin: 1em 0;
        padding: 1em;
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .metcalf-filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }
    
    .metcalf-filter-item {
        margin-bottom: 10px;
    }
    
    .metcalf-filter-item label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .metcalf-status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-new {
        background-color: #e7f5ff;
        color: #0066cc;
    }
    
    .status-in-progress {
        background-color: #fff4e5;
        color: #ff8800;
    }
    
    .status-pending-client {
        background-color: #ffedf0;
        color: #ff3366;
    }
    
    .status-pending-court {
        background-color: #f0f0ff;
        color: #6666ff;
    }
    
    .status-pending-third-party {
        background-color: #f0f0f0;
        color: #666666;
    }
    
    .status-on-hold {
        background-color: #fff0f0;
        color: #ff0000;
    }
    
    .status-resolved {
        background-color: #e6fff2;
        color: #00cc66;
    }
    
    .status-closed {
        background-color: #f0f0f0;
        color: #333333;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Dynamic filtering for case types based on selected category
        $('#category').on('change', function() {
            var categoryId = $(this).val();
            var typeSelect = $('#type');
            
            if (!categoryId) {
                // If no category selected, show all types
                typeSelect.find('option').show();
                return;
            }
            
            // Hide all options except the first one
            typeSelect.find('option:not(:first)').hide();
            
            // Show only types that belong to the selected category
            typeSelect.find('option[data-category="' + categoryId + '"]').show();
            
            // Reset the type selection if the current selection is now hidden
            if (typeSelect.find('option:selected').is(':hidden')) {
                typeSelect.val('');
            }
        });
        
        // Trigger change event to initialize the filtering
        $('#category').trigger('change');
    });
</script>