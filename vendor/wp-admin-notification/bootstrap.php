<?php
/**
 * WordPress Admin Notifications
 *
 * Add static/dismissible admin notifications to WordPress
 *
 * @package   wp-admin-notification
 * @author    Askupa Software <hello@askupasoftware.com>
 * @link      https://github.com/askupasoftware/wp-admin-notification
 * @copyright 2016 Askupa Software
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Prevent loading the library more than once
 */
if( defined( 'WP_ADMIN_NOTIFICATION' ) ) return;
define( 'WP_ADMIN_NOTIFICATION', true );

/**
 * Require the handler class
 */
require_once 'handler.php';

if(!function_exists('wp_admin_notification'))
{
    /**
     * 
     * @param type $handle
     * @param type $html
     * @param type $type
     * @param type $dismissible
     * @param type $class
     * @param type $network
     */
    function wp_admin_notification( $handle, $html, $type = 'success', $dismissible = false, $class = '', $network = false )
    {
        $notifier = WPAdminNotifications::get_instance();
        $notifier->register_notification($handle, array(
            'dismissible'   => $dismissible,
            'class'         => $class,
            'type'          => $type,
            'html'          => $html,
            'network'       => $network
        ));
    }
}

if(!function_exists('wp_reset_admin_notification'))
{
    /**
     * 
     * @param string $handle
     */
    function wp_reset_admin_notification( $handle )
    {
        $dismissed = get_option( 'wp_dismissed_notices' );
        $offset = 0;
        foreach($dismissed as $id)
        {
            if( $id === $handle ) break;
            $offset++;
        }
        array_splice( $dismissed, $offset, 1);
        update_option( 'wp_dismissed_notices', $dismissed );
    }
}