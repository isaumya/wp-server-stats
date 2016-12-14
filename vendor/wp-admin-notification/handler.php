<?php

if(!class_exists('WP_Admin_Notifications'))
{
    class WPAdminNotifications
    {
        /**
         * @var Singleton The reference to *Singleton* instance of this class
         */
        private static $instance;
        
        private $notifications = array();
        
        private $dismissed_notices;

        private $handle;
        
        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return Singleton The *Singleton* instance.
         */
        public static function get_instance()
        {
            if( null === static::$instance ) 
            {
                static::$instance = new static();
            }
            return static::$instance;
        }
        
        public function register_notification( $handle, $options ) {
        	$this->handle = $handle;

            if( 0 === count( $this->notifications ) ) {
                $this->init();
            }
            
            if( !key_exists( $handle, $this->notifications ) )
            {
                $this->notifications[$handle] = $options;
            }
            else trigger_error( "The handle <strong>$handle</strong> has already been registered. Please choose a different handle for your notification." );
        }
        
        public function render_notifications()
        {
            foreach($this->notifications as $handle => $notification)
            {
                $this->render_notification( $handle, $notification );
            }
        }
        
        public function render_network_notifications()
        {
            foreach( $this->notifications as $handle => $notification )
            {
                if( $notification['network'] ) $this->render_notification( $handle, $notification );
            }
        }
        
        public function dismiss_notification()
        {
            $id = filter_input( INPUT_POST, 'id' );
            if( !in_array( $id, $this->dismissed_notices ) )
            {
                $this->dismissed_notices[] = $id;
                update_option( 'wp_dn_' . $this->handle, $this->dismissed_notices);
            }
            die();
        }
        
        public function render_script()
        {
            ?>
            <script>
            jQuery(document).ready(function($){
                $('.notice').on('click','.notice-dismiss',function(e){
                    $.post(ajaxurl,{
                        action: 'dismiss_admin_notification',
                        id: $(this).parent().attr('id')
                    });
                });
            });
            </script>
            <?php
        }
        
        private function init()
        {
            add_action( 'admin_notices', array( $this, 'render_notifications' ) );
            add_action( 'network_admin_notices', array( $this, 'render_network_notifications' ) );
            add_action( 'wp_ajax_dismiss_admin_notification', array( $this, 'dismiss_notification' ) );
            add_action( 'admin_head', array( $this, 'render_script' ) );
            $this->dismissed_notices = get_option('wp_dn_' . $this->handle);
            if( false === $this->dismissed_notices ) $this->dismissed_notices = array();
            //var_dump($this->dismissed_notices);
        }
        
        private function render_notification( $id, $n )
        {
            if( in_array( $id, $this->dismissed_notices ) ) return;
            
            printf( 
                '<div id="%s" class="notice notice-%s %s%s"><p>%s</p></div>',
                $id,
                $n['type'], 
                $n['dismissible']?'is-dismissible ':'', 
                $n['class'], 
                $n['html'] 
            );
        }
    }
}