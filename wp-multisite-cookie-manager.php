<?php
/** 
 * Plugin Name: MN - WordPress Multisite Cookie Manager
 * Plugin URI: https://github.com/mnestorov/wp-multisite-cookie-manager
 * Description: Manage cookies across a multisite network.
 * Version: 1.8.1
 * Author: Martin Nestorov
 * Author URI: https://github.com/mnestorov
 * Text Domain: mn-wordpress-multisite-cookie-manager
 * Tags: wordpress, wordpress-plugin, wp, wp-plugin, wp-admin, wordpress-cookie
 */

// Enable WP_DEBUG in your WordPress configuration to catch errors during development.
// In your wp-config.php file:
// define( 'WP_DEBUG', true );
// define( 'WP_DEBUG_LOG', true );
// define( 'WP_DEBUG_DISPLAY', false ); 

// Register the uninstall hook
register_uninstall_hook(__FILE__, 'mn_custom_cookie_manager_uninstall');

// Remove the `cookie_usage` table from the database
function mn_custom_cookie_manager_uninstall() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Generate the cookie name
function mn_get_unique_cookie_name() {
    // Get the current blog ID
    $blog_id = get_current_blog_id();

    // Create a unique cookie name for this site
    $cookie_name = 'custom_cookie_' . $blog_id;

    return $cookie_name;
}

// Custom error handling function to log or display errors in a standardized way.
function mn_log_error($message, $error_type = E_USER_NOTICE) {
    if ( WP_DEBUG ) {
        if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
            error_log($message);
        }
        if ( defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ) {
            trigger_error($message, $error_type);
        }
    }
}

// Function to register a new menu page in the network admin
function mn_register_cookie_settings_page(){
    add_menu_page(
        esc_html__('Cookie Settings', 'mn-wordpress-multisite-cookie-manager'),
        esc_html__('Cookie Settings', 'mn-wordpress-multisite-cookie-manager'),
        'manage_network_options',
        'cookie-settings',
        'mn_cookie_settings_page',
        '',
        99
    );
}
add_action('network_admin_menu', 'mn_register_cookie_settings_page');

// Function to display the cookie settings page
function mn_cookie_settings_page(){
    // Get the unique cookie name
    $cookie_name = mn_get_unique_cookie_name();

    // Handle form submission for updating cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        // Sanitize and update settings
        $custom_cookie_expirations = (isset($_POST['custom_cookie_expirations']) && is_array($_POST['custom_cookie_expirations'])) ? array_map('sanitize_text_field', $_POST['custom_cookie_expirations']) : array();
        update_site_option('custom_cookie_expirations', $custom_cookie_expirations);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'mn-wordpress-multisite-cookie-manager') . '</p></div>';
    }

    // Fetch current settings
    $custom_cookie_expirations = get_site_option('custom_cookie_expirations', '');

    // Output form for managing cookie settings
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Cookie Settings', 'mn-wordpress-multisite-cookie-manager') . '</h1>';
    echo '<p>' . esc_html__('Current Blog ID:', 'mn-wordpress-multisite-cookie-manager') . ' ' . esc_html($blog_id) . '</p>';
    echo '<p>' . esc_html__('Generated Cookie Name:', 'mn-wordpress-multisite-cookie-manager') . ' ' . esc_html($cookie_name) . '</p>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('custom_cookie_nonce', 'custom_cookie_nonce');
    echo '<h2>' . esc_html__('Cookie Expirations', 'mn-wordpress-multisite-cookie-manager') . '</h2>';
    echo '<textarea name="custom_cookie_expirations" rows="5" cols="50">' . esc_textarea(json_encode($custom_cookie_expirations, JSON_PRETTY_PRINT)) . '</textarea>';
    echo '<br>';
    echo '<input type="submit" value="' . esc_attr__('Save Settings', 'mn-wordpress-multisite-cookie-manager') . '" class="button button-primary">';
    echo '<input type="submit" name="export_settings" value="' . esc_attr__('Export Settings', 'mn-wordpress-multisite-cookie-manager') . '" class="button">';
    echo '<input type="file" name="import_settings_file" accept=".json">';
    echo '<input type="submit" name="import_settings" value="' . esc_attr__('Import Settings', 'mn-wordpress-multisite-cookie-manager') . '" class="button">';
    echo '</form>';
    echo '</div>';

    // Handle export of cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_settings']) && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        $settings_json = mn_export_cookie_settings();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=cookie-settings.json');
        echo $settings_json;
        exit;
    }

    // Handle import of cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_settings']) && isset($_FILES['import_settings_file']) && $_FILES['import_settings_file']['error'] == 0 && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        $json_settings = file_get_contents($_FILES['import_settings_file']['tmp_name']);
        if (mn_import_cookie_settings($json_settings)) {
            echo '<div class="updated"><p>' . esc_html__('Settings imported successfully.', 'mn-wordpress-multisite-cookie-manager') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Failed to import settings.', 'mn-wordpress-multisite-cookie-manager') . '</p></div>';
        }
    }
}

// Function to handle the logic for cookie expiration based on user roles and login status
function mn_get_cookie_expiration($default_expiration) {
    $cookie_expirations = get_site_option('custom_cookie_expirations', array());
    $expiration = $default_expiration;
    
    if ($cookie_expirations) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if (in_array('administrator', $current_user->roles)) {
                $expiration = $default_expiration + DAY_IN_SECONDS;
            } else {
                $expiration = $default_expiration - HOUR_IN_SECONDS;
            }
        } else {
            $expiration = $default_expiration - (30 * MINUTE_IN_SECONDS);
        }
    } else {
        mn_log_error('Failed to fetch custom cookie expirations from the database.');
    }
    
    return $expiration;
}

// Function to set a custom cookie on page load
function mn_set_custom_cookie() {
    $default_expiration = 86400;  // Example default expiration of 1 day
    $cookie_expiration = mn_get_cookie_expiration($default_expiration);
    $cookie_name = mn_get_unique_cookie_name(); // Get the unique cookie name
    setcookie($cookie_name, 'cookie_value', time() + $cookie_expiration, "/");
}
add_action('init', 'mn_set_custom_cookie');

// Function to create a new database table for logging cookie usage on plugin activation
function mn_create_cookie_usage_table() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        blog_id mediumint(9) NOT NULL,
        cookie_name varchar(255) NOT NULL,
        cookie_value varchar(255) NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    if ( !empty($result['errors']) ) {
        mn_log_error(print_r($result['errors'], true));
    }
}
register_activation_hook(__FILE__, 'mn_create_cookie_usage_table');

// Function to log cookie usage on page load
function mn_log_cookie_usage() {
    $blog_id = get_current_blog_id();
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';

    foreach ($_COOKIE as $cookie_name => $cookie_value) {
        $cookie_log_entry = array(
            'blog_id' => $blog_id,
            'cookie_name' => $cookie_name,
            'cookie_value' => $cookie_value,
            'timestamp' => current_time('mysql')
        );

        // Check if the cookie entry already exists in the database to prevent duplicates
        $existing_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE blog_id = %d AND cookie_name = %s",
            $blog_id,
            $cookie_name
        ));

        if (null === $existing_entry) {
            $wpdb->insert($table_name, $cookie_log_entry);
        }

        if ( false === $insert_result ) {
            mn_log_error('Failed to insert cookie usage log entry: ' . $wpdb->last_error);
        }
    }
}
add_action('init', 'mn_log_cookie_usage');

// Function to write log entries from transient to database hourly
function mn_write_cookie_usage_log_entries() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
    $log_entries = get_transient('cookie_usage_log_entries');
    if ($log_entries && is_array($log_entries)) {
        $all_inserts_successful = true;
        foreach ($log_entries as $entry) {
            $insert_result = $wpdb->insert($table_name, $entry);
            if ($insert_result === false) {
                error_log("Failed to insert cookie usage log entry: " . $wpdb->last_error);
                $all_inserts_successful = false;
            }
        }
        if ($all_inserts_successful) {
            delete_transient('cookie_usage_log_entries');
        }
    }
}
add_action('write_cookie_usage_log_entries_hook', 'mn_write_cookie_usage_log_entries');

// Schedule hourly event to write log entries to database
if (!wp_next_scheduled('write_cookie_usage_log_entries_hook')) {
    wp_schedule_event(time(), 'hourly', 'write_cookie_usage_log_entries_hook');
}

// Function to register a submenu page for cookie usage reports
function mn_register_cookie_reporting_page(){
    add_submenu_page(
        'cookie-settings',
        esc_html__('Cookie Usage Reports', 'mn-wordpress-multisite-cookie-manager'),
        esc_html__('Cookie Usage Reports', 'mn-wordpress-multisite-cookie-manager'),
        'manage_network_options',
        'cookie-reports',
        'mn_cookie_reporting_page'
    );
}
add_action('network_admin_menu', 'mn_register_cookie_reporting_page');

// Function to display cookie usage reports
function mn_cookie_reporting_page() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
    $results = $wpdb->get_results("SELECT cookie_name, COUNT(DISTINCT blog_id) as blog_count FROM $table_name GROUP BY cookie_name", OBJECT);

    echo '<div class="wrap">';
    echo '<h1>Cookie Usage Reports</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Cookie Name</th><th>Number of Blogs</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->cookie_name) . '</td>';
        echo '<td>' . esc_html($row->blog_count) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Function to export cookie settings to a JSON file
function mn_export_cookie_settings() {
    $custom_cookie_expirations = get_site_option('custom_cookie_expirations', '');
    return json_encode($custom_cookie_expirations, JSON_PRETTY_PRINT);
}

// Function to import cookie settings from a JSON file
function mn_import_cookie_settings($json_settings) {
    $settings_array = json_decode($json_settings, true);
    if (json_last_error() == JSON_ERROR_NONE && is_array($settings_array)) {
        update_site_option('custom_cookie_expirations', $settings_array);
        return true;
    }
    return false;
}