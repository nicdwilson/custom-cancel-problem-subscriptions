<?php
/**
 * Plugin Name: Custom Cancel Problem Subscriptions
 * Description: A simple tool to cancel problem subscriptions by removing schedule meta values
 * Version: 1.0.0
 * Author: nicw
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Requires Plugins: woocommerce, woocommerce-subscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add menu page under Tools
add_action('admin_menu', 'ccps_add_menu_page');

function ccps_add_menu_page() {
    add_management_page(
        'Cancel Problem Subscription',
        'Cancel Problem Subscription',
        'manage_options',
        'cancel-problem-subscription',
        'ccps_render_page'
    );
}

// Render the admin page
function ccps_render_page() {
    // Check user capabilities - require both WooCommerce and Subscriptions capabilities
    if (!current_user_can('manage_options') || !current_user_can('manage_woocommerce')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'custom-cancel-problem-subscriptions'));
    }

    // Verify we're on the correct admin page
    if (!isset($_GET['page']) || $_GET['page'] !== 'cancel-problem-subscription') {
        wp_die(__('Invalid page access.', 'custom-cancel-problem-subscriptions'));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php 
            wp_nonce_field('ccps_cancel_subscription');
            // Add a hidden field for the action
            ?>
            <input type="hidden" name="action" value="ccps_cancel_subscription">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="subscription_id"><?php esc_html_e('Subscription ID', 'custom-cancel-problem-subscriptions'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="subscription_id" 
                               id="subscription_id" 
                               class="regular-text" 
                               required 
                               min="1"
                               value="<?php echo isset($_POST['subscription_id']) ? esc_attr(absint($_POST['subscription_id'])) : ''; ?>">
                        <p class="description"><?php esc_html_e('Enter the ID of the subscription to fix to allow cancellation.', 'custom-cancel-problem-subscriptions'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Fix Subscription', 'custom-cancel-problem-subscriptions')); ?>
        </form>
    </div>
    <?php
}

// Add action to handle form submission
add_action('admin_post_ccps_cancel_subscription', 'ccps_handle_form_submission');

function ccps_handle_form_submission() {
    // Verify nonce and capabilities
    if (!current_user_can('manage_options') || !current_user_can('manage_woocommerce')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'custom-cancel-problem-subscriptions'));
    }

    if (!check_admin_referer('ccps_cancel_subscription', '_wpnonce')) {
        wp_die(__('Security check failed. Please try again.', 'custom-cancel-problem-subscriptions'));
    }

    // Handle form submission
    if (isset($_POST['subscription_id'])) {
        $subscription_id = absint($_POST['subscription_id']);
        
        if ($subscription_id > 0) {
            // Verify the subscription exists and is valid
            $subscription = wcs_get_subscription($subscription_id);
            
            if (!$subscription) {
                wp_redirect(add_query_arg(
                    array(
                        'page' => 'cancel-problem-subscription',
                        'error' => 'invalid_subscription'
                    ),
                    admin_url('tools.php')
                ));
                exit;
            } else {
                // Use the subscription's date API to properly update the dates
                $subscription->update_dates(array(
                    'end' => 0,
                    'cancelled' => 0
                ));
                $subscription->save();

                wp_redirect(add_query_arg(
                    array(
                        'page' => 'cancel-problem-subscription',
                        'success' => '1'
                    ),
                    admin_url('tools.php')
                ));
                exit;
            }
        }
    }
    
    // If we get here, something went wrong
    wp_redirect(add_query_arg(
        array(
            'page' => 'cancel-problem-subscription',
            'error' => 'invalid_request'
        ),
        admin_url('tools.php')
    ));
    exit;
}

// Add this function to display admin notices
add_action('admin_notices', 'ccps_admin_notices');

function ccps_admin_notices() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'cancel-problem-subscription') {
        return;
    }

    if (isset($_GET['success'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Subscription meta values have been cleared successfully.', 'custom-cancel-problem-subscriptions'); ?></p>
        </div>
        <?php
    }

    if (isset($_GET['error'])) {
        $message = '';
        switch ($_GET['error']) {
            case 'invalid_subscription':
                $message = __('Invalid subscription ID or subscription not found.', 'custom-cancel-problem-subscriptions');
                break;
            case 'invalid_request':
                $message = __('Invalid request. Please try again.', 'custom-cancel-problem-subscriptions');
                break;
            default:
                $message = __('An error occurred. Please try again.', 'custom-cancel-problem-subscriptions');
        }
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
} 