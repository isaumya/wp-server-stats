<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$option_name = 'wpss_settings_options';
 
delete_option($option_name);
 
// for site options in Multisite
delete_site_option($option_name);

unregister_setting( 'wp_server_stats', $option_name );