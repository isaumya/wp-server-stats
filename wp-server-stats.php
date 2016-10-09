<?php
/*
Plugin Name: WP Server Stats
Plugin URI: https://www.isaumya.com/portfolio-item/wp-server-stats/
Description: Show up the memory limit and current memory usage in the dashboard and admin footer
Author: Saumya Majumder
Author URI: https://www.isaumya.com/
Version: 1.3.2
Text Domain: wp-server-stats
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
Copyright 2012-2016 by Saumya Majumder 

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/* Define Constants */
define('WP_SERVER_STATS_BASE', plugin_basename(__FILE__));

session_start();

if ( is_admin() ) {	
	
	class wp_server_stats {
		
		var $memory = false;
		// declaring the protected variables
		protected $refresh_interval, $bg_color_good, $bg_color_average, $bg_color_bad, $footer_text_color;

		public function __construct() {
            add_action( 'init', array (&$this, 'check_limit') );
			add_action( 'wp_dashboard_setup', array (&$this, 'add_dashboard') );
			add_filter( 'admin_footer_text', array (&$this, 'add_footer') );
			add_action( 'admin_enqueue_scripts', array (&$this, 'load_admin_scripts') );
			add_action( 'wp_ajax_process_ajax', array (&$this, 'process_ajax') );

			// Adding the `Settings Option beside Edit & Deactivation link inside WP Dashboard's Installed Plugin Page
			add_filter( 'plugin_action_links_' .WP_SERVER_STATS_BASE, array( $this, 'add_action_link' ) );

			/* First lets initialize an admin settings link inside WP dashboard */
			/* It will show under the SETTINGS section */
			add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
			// Register page options
    		add_action( 'admin_init', array( $this, 'register_page_options' ) );

    		register_uninstall_hook( 'wp_server_stats' , array( $this, 'handle_uninstall_hook' ) );

			$this->memory = array();
		}
        
        public function check_limit() {
            $memory_limit = ini_get('memory_limit');
			if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
			    if ($matches[2] == 'G') {
			    	$memory_limit = $matches[1] . ' ' . 'GB'; // nnnG -> nnn GB
			    } else if ($matches[2] == 'M') {
			        $memory_limit = $matches[1] . ' ' . 'MB'; // nnnM -> nnn MB
			    } else if ($matches[2] == 'K') {
			        $memory_limit = $matches[1] . ' ' . 'KB'; // nnnK -> nnn KB
			    } else if ($matches[2] == 'T') {
			    	$memory_limit = $matches[1] . ' ' . 'TB'; // nnnT -> nnn TB
			    } else if ($matches[2] == 'P') {
			    	$memory_limit = $matches[1] . ' ' . 'PB'; // nnnP -> nnn PB
			    }
			}
			return $memory_limit;
        }

        public function check_memory_limit_cal() {
        	return (int) ini_get('memory_limit');
        }
		
		public function check_server_ip() {
			return gethostbyname( gethostname() );
		}

		public function check_server_location() {
			//get the server ip
			$ip = $this->check_server_ip();

			// lets validate the ip
			if( $this->validate_ip_address( $ip ) ) {
				$query = @unserialize(file_get_contents('http://ip-api.com/php/'.$ip));
				$server_location = wp_cache_get( 'server_location' );
				if( false === $server_location && $query && $query['status'] == 'success' ) {
				  $server_location = $query['city'] . ', ' . $query['country'];
				  wp_cache_set( 'server_location', $server_location );
				  return $server_location;
				} else {
				  return $query['message'];
				}
			} else {
				return "ERROR IP096T";
			}
			
		}

		//function to validate IP address
		public function validate_ip_address( $ip ) {
			if (!filter_var($ip, FILTER_VALIDATE_IP) === false) {
			    return true; // $ip is a valid IP address
			} else {
			    return false; // $ip is NOT a valid IP address
			}
		}

		public function isShellEnabled() {
			/*Check if shell_exec() is enabled on this server*/
		    if( function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(', ', ini_get('disable_functions')))) && strtolower(ini_get('safe_mode')) != 1 ) {
		    	/*If enabled, check if shell_exec() actually have execution power*/
		    	$returnVal = shell_exec('cat /proc/cpuinfo');
		    	if( !empty( $returnVal ) ) {
		    		return true;
		    	} else {
		    		return false;
		    	}
		    } else {
		    	return false;
		    }
		}

		public function check_cpu_count() {
			if( $this->isShellEnabled() ) {
				$cpu_count = wp_cache_get( 'cpu_count' );
				if( false === $cpu_count ) {
					$cpu_count = shell_exec('cat /proc/cpuinfo |grep "physical id" | sort | uniq | wc -l');
					wp_cache_set( 'cpu_count', $cpu_count );
					return $cpu_count;
				}
			} else {
				return 'ERROR EXEC096T';
			}
		}

		public function check_core_count() {
			if( $this->isShellEnabled() ) {
				$cpu_core_count = wp_cache_get( 'cpu_core_count' );
				if( false === $cpu_core_count ) {
					$cpu_core_count = shell_exec("echo \"$((`cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | uniq` * `cat /proc/cpuinfo |grep 'physical id' | sort | uniq | wc -l`))\"");
					wp_cache_set( 'cpu_core_count', $cpu_core_count );
					return $cpu_core_count;
				}
			} else {
				return 'ERROR EXEC096T';
			}
		}

		public function load_admin_scripts() {
			/* CSS Calls */
			wp_enqueue_style('flipclock', plugin_dir_url( __FILE__ ) . 'assets/css/flipclock.min.css', array(), '0.7.3');
			wp_enqueue_style('wp-server-stats-admin', plugin_dir_url( __FILE__ ) . 'assets/css/wp-server-stats-admin.min.css', array(), '1.0.0');
			// CSS rules for Color Picker
    		wp_enqueue_style( 'wp-color-picker' );

    		/* JS Calls */
		    $server_load_nonce = wp_create_nonce( 'slc_nonce' );
		    $_SESSION['server_load_check_nonce'] = $server_load_nonce;
			wp_register_script('server-load-check-ajax', plugin_dir_url( __FILE__ ) . 'assets/js/server-load-check.min.js', array( 'jquery', 'wp-color-picker' ), '2.0.0', true);
			wp_enqueue_script('server-load-check-ajax');
			wp_localize_script( 'server-load-check-ajax', 'server_load_check_vars', array(
					'server_load_check_nonce' => $server_load_nonce
				)
			);
			wp_register_script('flipclock', plugin_dir_url( __FILE__ ) . 'assets/js/flipclock.min.js', array( 'jquery' ), '0.7.3', true);
			wp_enqueue_script('flipclock');
		}

		public function process_ajax() {
			if( !isset( $_SESSION['server_load_check_nonce'] ) || !wp_verify_nonce( $_SESSION['server_load_check_nonce'], 'slc_nonce' ) )
				die( 'Permission Check Failed' );

			/* Let's call the fetch data function */
			$this->fetch_data();

			/* If Shell is enablelled then execute the CPU Load, Memory Load and Uptime */
			if( $this->isShellEnabled() ) {
				$cpu_load = trim( shell_exec("echo $((`ps aux|awk 'NR > 0 { s +=$3 }; END {print s}'| cut -d . -f 1` / `cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | wc -l`))") );
				$memory_usage_MB = function_exists('memory_get_usage') ? round(memory_get_usage() / 1024 / 1024, 2) : 0;
				$memory_usage_pos = round ($memory_usage_MB / (int) $this->check_memory_limit_cal() * 100, 0);
				$uptime = trim( shell_exec("cut -d. -f1 /proc/uptime") );
				$json_out = array (
						'cpu_load' => $cpu_load,
						'memory_usage_MB' => $memory_usage_MB,
						'memory_usage_pos' => $memory_usage_pos,
						'uptime' => $uptime,
						'refresh_interval' => $this->refresh_interval,
						'bg_color_good' => $this->bg_color_good,
						'bg_color_average' => $this->bg_color_average,
						'bg_color_bad' => $this->bg_color_bad
					);
				echo json_encode($json_out);
			/* Otherwise just run the memory load check */
			} else {
				$memory_usage_MB = function_exists('memory_get_usage') ? round(memory_get_usage() / 1024 / 1024, 2) : 0;
				$memory_usage_pos = round ($memory_usage_MB / (int) $this->check_memory_limit_cal() * 100, 0);
				$json_out = array (
						'cpu_load' => null,
						'memory_usage_MB' => $memory_usage_MB,
						'memory_usage_pos' => $memory_usage_pos,
						'uptime' => null,
						'refresh_interval' => $this->refresh_interval,
						'bg_color_good' => $this->bg_color_good,
						'bg_color_average' => $this->bg_color_average,
						'bg_color_bad' => $this->bg_color_bad
					);
				echo json_encode($json_out);
			}
			die();
		}
		
		public function dashboard_output() {
			if ( current_user_can( 'manage_options' ) ) :
				?>
					<ul>
						<li><strong><?php _e('Server IP', 'wp-server-stats'); ?></strong> : <span><?php echo ( $this->validate_ip_address( $this->check_server_ip() ) ? $this->check_server_ip() : "ERROR IP096T" ); ?></span></li>	
						<li><strong><?php _e('Server Location', 'wp-server-stats'); ?></strong> : <span><?php echo $this->check_server_location(); ?></span></li>
						<li><strong><?php _e('Server Hostname', 'wp-server-stats'); ?></strong> : <span><?php echo gethostname(); ?></span></li>
						<?php if( $this->isShellEnabled() ) : ?>
						<li><strong><?php _e('Total CPUs', 'wp-server-stats'); ?></strong> : <span><?php echo $this->check_cpu_count() . ' / ' . $this->check_core_count() . __('Cores', 'wp-server-stats'); ?></span></li>
						<?php endif; ?>
						<li><strong><?php _e('PHP Version', 'wp-server-stats'); ?></strong> : <span><?php echo PHP_VERSION; ?>&nbsp;/&nbsp;<?php echo (PHP_INT_SIZE * 8) . __('Bit OS'); ?></span></li>
						<li><strong><?php _e('Memory Limit', 'wp-server-stats'); ?></strong> : <span><?php echo $this->check_limit(); ?></span></li>
						<li><strong><?php _e('Real Time Memory Usage', 'wp-server-stats'); ?></strong> : <span id="mem_usage_mb"></span></li>
					</ul>
					<div class="progressbar">
						<div style="border:1px solid #DDDDDD; background-color:#F9F9F9;	border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px;">
	                        <div id="memory-load-upper-div" style="padding: 0px; border-width:0px; color:#FFFFFF;text-align:right; border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px; margin-top: -1px;">
	                        	<div id="memory-usage-pos" style="padding:2px;"></div>
							</div>
						</div>
					</div>
					<?php if( $this->isShellEnabled() ) : ?>
					<span style="line-height: 2.5em;"><strong><?php _e('Real Time CPU Load', 'wp-server-stats') ?>:</strong></span>
					<div class="progressbar">
						<div style="border:1px solid #DDDDDD; background-color:#F9F9F9;	border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px;">
	                        <div id="server-load-upper-div" style="padding: 0px; border-width:0px; color:#FFFFFF;text-align:right; border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px; margin-top: -1px;">
								<div id="server-load" style="padding:2px;"></div>
							</div>
						</div>
					</div>
					<hr style="margin-top: 15px; margin-bottom: 0px;" />
					<span style="line-height: 2.5em; margin-left: auto; margin-right: auto; display: table;"><strong><?php _e('Server Uptime', 'wp-server-stats') ?></strong></span>
					<div style="margin-top: 20px;">
						<div class="uptime" style="font-size: 20px;"></div>
					</div>
			<?php
					else : ?>
						<hr style="margin-top: 15px; margin-bottom: 15px;" />
						<p style="text-align: justify;"><strong><?php _e( 'Special Note', 'wp-server-stats'); ?>:</strong> <?php _e( 'Hi, please note that PHP 
						<code>shell_exec()</code> function is either not enable in your hosting environment or not been given executable permission, 
						hence you won\'t be seeing the following results above: CPU/Core count, Real Time CPU Usage, Server Uptime. To see these details, 
						please ask your host to enable <code>shell_exec()</code> function and give it executable permission.', 'wp-server-stats' ); ?></p>
					<?php endif;
			endif;
		}
		 
		public function add_dashboard() {
			wp_add_dashboard_widget( 'wp_memory_dashboard', 'Server Overview', array (&$this, 'dashboard_output') );
		}
		
		public function add_footer($content) {
			if( current_user_can( 'manage_options' ) ) :
				/* Let's call the fetch data function */
				$this->fetch_data();

				//check if the content is empty or not
				if( !empty( $content ) ) {
					$start = " | ";
				} else {
					$start = "";
				}

				if( $this->isShellEnabled() ) {
					$content .= $start . '<strong style="color: ' . $this->footer_text_color . ';">'. __('Memory', 'wp-server-stats') .' : <span id="mem_usage_mb_footer"></span>' 
					. ' ' . __('of', 'wp-server-stats') . ' ' . $this->check_limit() . ' (<span id="memory-usage-pos-footer"></span> '
					. __('used', 'wp-server-stats') .')</strong> | <strong style="color: ' . $this->footer_text_color . ';">' . __('CPU Load', 'wp-server-stats') 
					. ': <span id="cpu_load_footer"></span></strong>';
				} else {
					$content .= $start . '<strong style="color: ' . $this->footer_text_color . ';">'. __('Memory', 'wp-server-stats') .' : <span id="mem_usage_mb_footer"></span>' 
					. ' ' . __('of', 'wp-server-stats') . ' ' . $this->check_limit() . ' (<span id="memory-usage-pos-footer"></span> '
					. __('used', 'wp-server-stats') .')</strong>';
				}
				return $content;
			endif;
		}

		public function create_admin_menu() {
			/* Now adding the options page */
			/* using the add_options_page() wp function which takes 5 arguments as follows: 
			 * Arg 1: Title of the page which will; be shown inside the HTML <title></title> tags
			 * Arg 2: Name of our sub menu
			 * Arg 3: Capability (who will see this menua nd can edit the settings)
			 * Arg 4: Menu Slug, use PHP magic constant
			 * Arg 5: The function that will display our menu page
			 * For more, check WP Codex Page: https://codex.wordpress.org/Function_Reference/add_options_page
			**/
			add_options_page( 
				'WP Server Stats - Settings Page', /* Arg 1: Title of the page which will; be shown inside the HTML <title></title> tags */
				'WP Server Stats', /* Arg 2: Name of our sub menu */
				'manage_options', /* Arg 3: Capability (who will see this menua nd can edit the settings) */
				'wp_server_stats', /* Arg 4: Menu Slug, use PHP magic constant */
				array( 
					$this, 
					'admin_page_design' /* Arg 5: The function that will display our menu page */
				)
			);
		}

		/* Now lets create the function to design the admin page */
		public function admin_page_design() {
			/* Now lets do the admin page design */
			?>
				<div class="wrap">
					<h1><?php _e( 'WP Server Stats Settings', 'wp-server-stats' ); ?></h1>
					<h3><?php _e( 'On this page you will be able to change some critical settings of WP Server Stats', 'wp-server-stats' ); ?></h3>
					<h4><?php _e( 'Please note the below form uses HTML5, so, make sure you are using any of the HTML5 compliance browsers like IE v11+, Microsoft Edge, Chrome v49+, Firefix v47+, Safari v9.1+, Opera v39+', 'wp-server-stats' ); ?></h4>
					<hr />
					<div id="wpss-main">
						<form action="options.php" method="post" accept-charset="utf-8">
							<?php
								//Populate the admin settings page using WordPress Settings API
					            settings_fields('wp_server_stats');      
					            do_settings_sections('wp_server_stats');
					            submit_button();
					        ?>
						</form>
					</div>
					<div id="wpss-sidebar">
						<h2>
							<?php
								_e('Some info about the settings options', 'wp-server-stats');
							?>
						</h2>
						<ul>
							<li>
								<strong class="highlight"><?php _e('Refresh Interval', 'wp-server-stats'); ?></strong>
								<?php _e('This denotes the interval time after which the shell commands will execute again to give you the current load details. By default it is set to 200ms, but if you are seeing CPU load increase after instealling this plugin, try to increase the interval time to 1000ms, 2000ms, 3000ms or more until you see a normal CPU load. Generally it is not recommended to change the value unless you are having extremely high CPU load due to this plugin.', 'wp-server-stats'); ?>
							</li>
							<li>
								<strong class="highlight"><?php _e('Status Bar & Footer Text Color', 'wp-server-stats'); ?></strong>
								<?php _e('In case you do not like the color scheme I have used on this plugin, you can easily change those colors.', 'wp-server-stats'); ?>
							</li>
						</ul>
						<hr />
						<h2><?php _e('Support the plugin', 'wp-server-stats'); ?></h2>
						<p><?php _e('Believe it or not, developing a WorPress plugin really takes quite a lot of time to develop, test and to do continuous bugfix. Moreover as I\'m sharing this plugin for free, so all those times I\'ve spent coding this plugin yeild no revenue. So, overtime it become really hard to keep spending time on this plugin. So, if you like this plugin, I will really appriciate if you consider donating some amount for this plugin. Which will help me keep spending time on this plugin and make it even better. Please donate, if you can.', 'wp-server-stats'); ?></p>
						<div class="center">
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
						        <input type="hidden" name="cmd" value="_donations">
						        <input type="hidden" name="business" value="saumya0305@gmail.com">
						        <input type="hidden" name="lc" value="US">
						        <input type="hidden" name="item_name" value="Plugin Donation - WP Server Stats">
						        <input type="hidden" name="no_note" value="0">
						        <input type="hidden" name="currency_code" value="USD">
						        <input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
						        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					        </form>
				        </div>
					</div>
				</div>
			<?php
		}

		/**
		 * Function that will register admin page options.
		 */
		public function register_page_options() { 
			/* Let's call the fetch data function */
			$this->fetch_data();
		     
		    // Add Section for option fields
		    add_settings_section( 'wpss_section', __( 'Change the WP Server Stats Settings', 'wp-server-stats' ), array( $this, 'display_section' ), 'wp_server_stats' ); // id, title, display cb, page
		     
		    // Add Title Field
		    add_settings_field( 'wpss_refresh_interval_field', __( 'Set the realtime script refresh inverval (in ms) [1sec = 1000ms]', 'wp-server-stats' ), array( $this, 'refresh_interval_settings_field' ), 'wp_server_stats', 'wpss_section' ); // id, title, display cb, page, section
		     
		    // Add Background Color Field
		    add_settings_field( 'wpss_bg_field_good', __( 'Realtime Status Bar Background Color - For Good Status', 'wp-server-stats' ), array( $this, 'bg_settings_field_good' ), 'wp_server_stats', 'wpss_section' ); // id, title, display cb, page, section

		    add_settings_field( 'wpss_bg_field_average', __( 'Realtime Status Bar Background Color - For Near Critical Status', 'wp-server-stats' ), array( $this, 'bg_settings_field_avg' ), 'wp_server_stats', 'wpss_section' );

		    add_settings_field( 'wpss_bg_field_bad', __( 'Realtime Status Bar Background Color - For Super Critical Status', 'wp-server-stats' ), array( $this, 'bg_settings_field_bad' ), 'wp_server_stats', 'wpss_section' );

		    add_settings_field( 'wpss_footer_text_color', __( 'Footer Text Color', 'wp-server-stats' ), array( $this, 'footer_text_color' ), 'wp_server_stats', 'wpss_section' );
		     
		    // Register Settings
		    register_setting( 'wp_server_stats', 'wpss_settings_options', array( $this, 'validate_options' ) ); // option group, option name, sanitize cb 
		}

		/**
	     * Callback function for settings section
	    **/
	    public function display_section() { /* Leave blank */ } 

		/**
		 * Functions that display the fields.
		 */
		public function refresh_interval_settings_field() { 
		    echo '<input type="number" name="wpss_settings_options[refresh_interval]" value="' . $this->refresh_interval  . '" />';
		}   
		 
		public function bg_settings_field_good() { 
		    echo '<input type="text" name="wpss_settings_options[bg_color_good]" value="' . $this->bg_color_good . '" class="wpss-color-picker" />';
		}

		public function bg_settings_field_avg() { 
		    echo '<input type="text" name="wpss_settings_options[bg_color_average]" value="' . $this->bg_color_average . '" class="wpss-color-picker" />';
		}

		public function bg_settings_field_bad() { 
		    echo '<input type="text" name="wpss_settings_options[bg_color_bad]" value="' . $this->bg_color_bad . '" class="wpss-color-picker" />';
		}

		public function footer_text_color() { 
		    echo '<input type="text" name="wpss_settings_options[footer_text_color]" value="' . $this->footer_text_color . '" class="wpss-color-picker" />';
		}

		/**
		 * Function that will validate all fields.
		 */
		public function validate_options( $fields ) { 
		     
		    $valid_fields = array();
		     
		    // Validate Title Field
		    $refresh_interval = trim( $fields['refresh_interval'] );
		    $valid_fields['refresh_interval'] = strip_tags( stripslashes( $refresh_interval ) );
		     
		    // Validate color section
		    foreach ( $fields as $key => $value ) {
		    	if( preg_match('/_color/', $key) ) {
		    		$color[ $key ] = trim( $value );
		    		$color[ $key ] = strip_tags( stripslashes( $color[ $key ] ) );

		    		// Check if is a valid hex color
				    if( FALSE === $this->check_color( $color[ $key ] ) ) {
				    	
				    	if( $key == "bg_color_good" ) {
				    		$error_text = __("Insert a valid color for Realtime Status Bar Background Color - For Good Status", "wp-server-stats");
				    	} elseif ( $key == "bg_color_average" ) {
				    		$error_text = __("Insert a valid color for Realtime Status Bar Background Color - For Near Critical Status", "wp-server-stats");
				    	} elseif ( $key == "bg_color_bad" ) {
				    		$error_text = __("Insert a valid color for Realtime Status Bar Background Color - For Super Critical Status", "wp-server-stats");
				    	} elseif ( $key == "footer_text_color" ) {
				    		$error_text = __("Insert a valid color for the footer text color", "wp-server-stats");
				    	}
				        // Set the error message
				        add_settings_error( 'wpss_settings_options', 'wpss_bg_error', $error_text, 'error' ); // $setting, $code, $message, $type
				         
				        // Get the previous valid value
				        $valid_fields[ $key ] = $this->{$key};
				     
				    } else {
				     
				        $valid_fields[ $key ] = $color[ $key ];  
				     
				    }
		    	}
		    }
		     
		    return apply_filters( 'validate_options', $valid_fields, $fields);
		}
		 
		/**
		 * Function that will check if value is a valid HEX color.
		 */
		public function check_color( $value ) { 
		     
		    if ( preg_match( '/^#[a-f0-9]{6}$/i', $value ) ) { // if user insert a HEX color with #     
		        return true;
		    }
		     
		    return false;
		}

		/**
		 * Function that will fetch the user entered data in the settings page from database
		**/
		public function fetch_data() {
			// assuming our wpss_settings_option entry in database's option table is already there
			// so lets try to fetch it
			// As prior to PHP v5.5 empty() doesn't support anything but a variable, so calling a function 
			// inside empty() will yeild an error. So, first get the data into a variable then pass the var to empty()
			$fetched_data = get_option( 'wpss_settings_options' ); // $fetched_data will be an array
			if( !empty( $fetched_data ) ) {

				// fetching the refresh_interval data
				if( !empty( $fetched_data['refresh_interval'] ) ) {
					$this->refresh_interval = $fetched_data['refresh_interval'];
				} else {
					$this->refresh_interval = 200; // default refresh interval is 200ms
				}

				// fetching the bg_color_good
				if( !empty( $fetched_data['bg_color_good'] ) ) {
					$this->bg_color_good = $fetched_data['bg_color_good'];
				} else {
					$this->bg_color_good = "#37BF91";
				}

				// fetching the bg_color_average
				if( !empty( $fetched_data['bg_color_average'] ) ) {
					$this->bg_color_average = $fetched_data['bg_color_average'];
				} else {
					$this->bg_color_average = "#d35400";
				}

				// fetching the bg_color_bad
				if( !empty( $fetched_data['bg_color_bad'] ) ) {
					$this->bg_color_bad = $fetched_data['bg_color_bad'];
				} else {
					$this->bg_color_bad = "#e74c3c";
				}

				// fetching footer text color
				if( !empty( $fetched_data['footer_text_color'] ) ) {
					$this->footer_text_color = $fetched_data['footer_text_color'];
				} else {
					$this->footer_text_color = "#8e44ad";
				}	
			} else {
				$this->refresh_interval = 200; // default refresh interval is 200ms
				$this->bg_color_good = "#37BF91";
				$this->bg_color_average = "#d35400";
				$this->bg_color_bad = "#e74c3c";
				$this->footer_text_color = "#8e44ad";
			}
			return null;
		}

		// Adding the `Settings Option beside Edit & Deactivation link inside WP Dashboard's Installed Plugin Page
		public static function add_action_link( $data ) {
			// check permission
			if ( ! current_user_can('manage_options') ) {
				return $data;
			}

			return array_merge(
				$data,
				array(
					sprintf(
						'<a href="%s">%s</a>',
						add_query_arg(
							array(
								'page' => 'wp_server_stats'
							),
							admin_url('options-general.php')
						),
						__("Settings", "wp-server-stats")
					)
				)
			);
		}

		// Handel uninstall hool
		public function handle_uninstall_hook() {
			delete_option( 'wpss_settings_options' );
			delete_site_option( 'wpss_settings_options' );
			unregister_setting( 'wp_server_stats', 'wpss_settings_options' );
		}

	} //end of class

	// Start this plugin once all other plugins are fully loaded
	add_action( 'plugins_loaded', create_function('', '$memory = new wp_server_stats();') );
}