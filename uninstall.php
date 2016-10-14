<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Let's delete the Options
delete_option( 'wpss_settings_options' );
delete_option( 'wpss_db_advanced_info' );
 
// Delete option for multisite
//delete_site_option( $option_name );

// Unregister settings
unregister_setting( 'wp_server_stats', 'wpss_settings_options' );

// Delete transients
delete_transient( 'wpss_server_location' );
delete_transient( 'wpss_cpu_count' );
delete_transient( 'wpss_cpu_core_count' );
delete_transient( 'wpss_server_os' );
delete_transient( 'wpss_db_software' );
delete_transient( 'wpss_db_version' );
delete_transient( 'wpss_db_max_connection' );
delete_transient( 'wpss_db_max_packet_size' );
delete_transient( 'wpss_db_disk_usage' );
delete_transient( 'wpss_db_index_disk_usage' );
delete_transient( 'wpss_php_max_upload_size' );
delete_transient( 'wpss_php_max_post_size' );

delete_site_transient( 'wpss-donate-notice-forever' );