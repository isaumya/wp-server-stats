<?php
/*
Plugin Name: WP Server Stats
Plugin URI: https://www.isaumya.com/portfolio-item/wp-server-stats/
Description: Show up the memory limit and current memory usage in the dashboard and admin footer
Author: Saumya Majumder
Author URI: https://www.isaumya.com/
Version: 1.4.8
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

/* Requiring the necessary files */
require_once plugin_dir_path( __FILE__ ) . 'vendor/persist-admin-notices-dismissal/persist-admin-notices-dismissal.php';

session_start();

if ( is_admin() ) {	
	
	if ( !class_exists("wp_server_stats") ) {

		class wp_server_stats {
			
			var $memory = false;
			// declaring the protected variables
			protected $refresh_interval, $memcache_host, $memcache_port, $use_ipapi_pro, $ipapi_pro_key, $bg_color_good, $bg_color_average, $bg_color_bad, $footer_text_color;

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
	    		// Admin notice
	    		add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );

				$this->memory = array();

	    		// Inserting the wordpress proper dismissal class
	    		add_action( 'admin_init', array( 'PAnD', 'init' ) );
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

	        public function format_filesize( $bytes ) {
		        if($bytes / 1099511627776 > 1) {
		            return number_format_i18n($bytes/1099511627776, 0).' '.__('TB', 'wp-server-stats');
		        } elseif($bytes / 1073741824 > 1) {
		            return number_format_i18n($bytes/1073741824, 0).' '.__('GB', 'wp-server-stats');
		        } elseif($bytes / 1048576 > 1) {
		            return number_format_i18n($bytes/1048576, 0).' '.__('MB', 'wp-server-stats');
		        } elseif($bytes / 1024 > 1) {
		            return number_format_i18n($bytes/1024, 0).' '.__('KB', 'wp-server-stats');
		        } elseif($bytes > 1) {
		            return number_format_i18n($bytes, 0).' '.__('bytes', 'wp-server-stats');
		        } else {
		            return __('Unknown', 'wp-server-stats');
		        }
		    }

		    public function format_php_size($size) {
			    if (!is_numeric($size)) {
			        if (strpos($size, 'M') !== false) {
			            $size = intval($size)*1024*1024;
			        } elseif (strpos($size, 'K') !== false) {
			            $size = intval($size)*1024;
			        } elseif (strpos($size, 'G') !== false) {
			            $size = intval($size)*1024*1024*1024;
			        }
			    }
			    return is_numeric($size) ? $this->format_filesize($size) : $size;
			}

	        public function check_memory_limit_cal() {
	        	return (int) ini_get('memory_limit');
	        }
			
			public function check_server_ip() {
				return trim( gethostbyname( gethostname() ) );
			}

			public function check_server_location() {
				$this->fetch_data();
				$ipapi_pro_key = trim( $this->ipapi_pro_key );
				//get the server ip
				$ip = $this->check_server_ip();

				$server_location = get_transient( 'wpss_server_location' );

				if( $server_location === FALSE ) {
					// lets validate the ip
					if( $this->validate_ip_address( $ip ) ) {
						if( $this->use_ipapi_pro == 'Yes' && !empty( $ipapi_pro_key ) ) { // Use the pro version of IP-API query
							$query = @unserialize( file_get_contents( 'https://pro.ip-api.com/php/' . $ip . '?key=' . $ipapi_pro_key ) );
						} else { // Use the free version of IP-API
							$query = @unserialize( file_get_contents( 'http://ip-api.com/php/'.$ip ) );
						}
						if( $query && $query['status'] == 'success' ) {
							$server_location = $query['city'] . ', ' . $query['country'];
							set_transient( 'wpss_server_location', $server_location, WEEK_IN_SECONDS );
						} else {
							if( empty( $query['message'] ) ) {
								if( $this->use_ipapi_pro == 'Yes' ) {
									$server_location = 'You\'ve provided a wrong IP-API Pro Key';
								} else {
									$server_location = $query['status'];
								}
							} else {
								$server_location = $query['message'];
							}
						}
					} else {
						$server_location = "ERROR IP096T";
					}
				}
				
				return $server_location;
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

				$cpu_count = get_transient( 'wpss_cpu_count' );

				if( $cpu_count === FALSE ) {
					if( $this->isShellEnabled() ) {
						$cpu_count = shell_exec('cat /proc/cpuinfo |grep "physical id" | sort | uniq | wc -l');
						set_transient( 'wpss_cpu_count', $cpu_count, WEEK_IN_SECONDS );
					} else {
						$cpu_count = 'ERROR EXEC096T';
					}
				}

				return $cpu_count;
			}

			public function check_core_count() {

				$cpu_core_count = get_transient( 'wpss_cpu_core_count' );

				if( $cpu_core_count === FALSE ) {
					if( $this->isShellEnabled() ) {
						$cpu_core_count = shell_exec("echo \"$((`cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | uniq` * `cat /proc/cpuinfo |grep 'physical id' | sort | uniq | wc -l`))\"");
						set_transient( 'wpss_cpu_core_count', $cpu_core_count, WEEK_IN_SECONDS );
					} else {
						$cpu_core_count = 'ERROR EXEC096T';
					}
				}
				
				return $cpu_core_count;
			}

			public function server_os() {
				
				$server_os = get_transient( 'wpss_server_os' );

				if( $server_os === FALSE ) {
					$os_detail = php_uname();
					$just_os_name = explode( " ", trim( $os_detail ) );
					$server_os = $just_os_name[0];
					set_transient( 'wpss_server_os', $server_os, WEEK_IN_SECONDS );
				}

				return $server_os;
			}

			public function database_software() {

				$db_software = get_transient( 'wpss_db_software' );

				if( $db_software === FALSE ) {
					global $wpdb;
					$db_software_query = $wpdb->get_row("SHOW VARIABLES LIKE 'version_comment'");
					$db_software_dump = $db_software_query->Value;
					if( !empty( $db_software_dump ) ) {
			            $db_soft_array = explode( " ", trim( $db_software_dump ) );
			        	$db_software = $db_soft_array[0];
			        	set_transient( 'wpss_db_software', $db_software, WEEK_IN_SECONDS );
			        } else {
			        	$db_software = __('N/A', 'wp-server-stats');
			        }
				}
				
		        return $db_software;
			}

			public function database_version() {

				$db_version = get_transient( 'wpss_db_version' );

				if( $db_version === FALSE ) {
					global $wpdb;
		        	$db_version_dump = $wpdb->get_var("SELECT VERSION() AS version from DUAL");
		        	if ( preg_match( '/\d+(?:\.\d+)+/', $db_version_dump, $matches ) ) { 
					    $db_version = $matches[0]; //returning the first match 
					    set_transient( 'wpss_db_version', $db_version, WEEK_IN_SECONDS );
					} else {
						$db_version = __('N/A', 'wp-server-stats');
					}
				}
				
				return $db_version;
			}

			public function database_max_no_connection() {

				$db_max_connection = get_transient( 'wpss_db_max_connection' );

				if( $db_max_connection === FALSE ) {
					global $wpdb;
			        $connection_max_query = $wpdb->get_row("SHOW VARIABLES LIKE 'max_connections'");
			        $db_max_connection = $connection_max_query->Value;
			        if( empty( $db_max_connection ) ) {
			            $db_max_connection = __('N/A', 'wp-server-stats');
			        } else {
			        	$db_max_connection = number_format_i18n( $db_max_connection, 0 );
			        	set_transient( 'wpss_db_max_connection', $db_max_connection, WEEK_IN_SECONDS );
			        }
				}

				return $db_max_connection;
			}

			public function database_max_packet_size() {

				$db_max_packet_size = get_transient( 'wpss_db_max_packet_size' );

				if( $db_max_packet_size === FALSE ) {
					global $wpdb;
			        $packet_max_query = $wpdb->get_row("SHOW VARIABLES LIKE 'max_allowed_packet'");
			        $db_max_packet_size = $packet_max_query->Value;
			        if( empty( $db_max_packet_size ) ) {
			            $db_max_packet_size = __('N/A', 'wp-server-stats');
			        } else {
			        	$db_max_packet_size = $this->format_filesize( $db_max_packet_size );
			        	set_transient( 'wpss_db_max_packet_size', $db_max_packet_size, WEEK_IN_SECONDS );
			        }
				}
				
		        return $db_max_packet_size;
			}

			public function database_disk_usage() {

				$db_disk_usage = get_transient( 'wpss_db_disk_usage' );

				if( $db_disk_usage === FALSE ) {
					global $wpdb;
			        $db_disk_usage = '';
			        $tablesstatus = $wpdb->get_results("SHOW TABLE STATUS");
			        foreach($tablesstatus as  $tablestatus) {
			            $db_disk_usage += $tablestatus->Data_length;
			        }
			        if ( empty( $db_disk_usage ) ) {
			            $db_disk_usage = __('N/A', 'wp-server-stats');
			        } else {
			        	$db_disk_usage = $this->format_filesize( $db_disk_usage );
			        	set_transient( 'wpss_db_disk_usage', $db_disk_usage, WEEK_IN_SECONDS );
			        }
				}
		        
		        return $db_disk_usage;
		    }

		    public function index_disk_usage() {

		    	$db_index_disk_usage = get_transient( 'wpss_db_index_disk_usage' );

		    	if( $db_index_disk_usage === FALSE ) {
		    		global $wpdb;
			        $db_index_disk_usage = '';
			        $tablesstatus = $wpdb->get_results("SHOW TABLE STATUS");
			        foreach( $tablesstatus as  $tablestatus ) {
			            $db_index_disk_usage +=  $tablestatus->Index_length;
			        }
			        if ( empty( $db_index_disk_usage ) ) {
			            $db_index_disk_usage = __('N/A', 'wp-server-stats');
			        } else {
			        	$db_index_disk_usage = $this->format_filesize( $db_index_disk_usage );
			        	set_transient( 'wpss_db_index_disk_usage', $db_index_disk_usage, WEEK_IN_SECONDS );
			        }
		    	}
		    	
		        return $db_index_disk_usage;
		    }

		    public function php_max_upload_size() {
		    	
		    	$php_max_upload_size = get_transient( 'wpss_php_max_upload_size' );

		    	if( $php_max_upload_size === FALSE ) {
			    	if( ini_get( 'upload_max_filesize' ) ) {
			            $php_max_upload_size = ini_get( 'upload_max_filesize' );
			            $php_max_upload_size = $this->format_php_size( $php_max_upload_size );
			            set_transient( 'wpss_php_max_upload_size', $php_max_upload_size, WEEK_IN_SECONDS );
			        } else {
			            $php_max_upload_size = __('N/A', 'wp-server-stats');
		        	}
		    	}
		        
		        return $php_max_upload_size;
		    }

		    public function php_max_post_size() {

		    	$php_max_post_size = get_transient( 'wpss_php_max_post_size' );

		    	if( $php_max_post_size === FALSE ) {
		    		if( ini_get( 'post_max_size' ) ) {
			            $php_max_post_size = ini_get( 'post_max_size' );
			            $php_max_post_size = $this->format_php_size( $php_max_post_size );
			            set_transient( 'wpss_php_max_post_size', $php_max_post_size, WEEK_IN_SECONDS );
			        } else {
			            $php_max_post_size = __('N/A', 'wp-server-stats');
			        }
		    	}
		        
		        return $php_max_post_size;
		    }

		    public function php_max_execution_time() {
		        if( ini_get( 'max_execution_time' ) ) {
		            $max_execute = ini_get('max_execution_time');
		        } else {
		            $max_execute = __('N/A', 'wp-server-stats');
		        }
		        return $max_execute;
		    }

		    public function php_short_tag() {
		        if( ini_get( 'short_open_tag' ) ) {
		            $short_tag = __('On', 'wp-server-stats');
		        } else {
		            $short_tag = __('Off', 'wp-server-stats');
		        }
		        return $short_tag;
		    }

		    public function php_safe_mode() {
		    	if( ini_get('safe_mode') ){
				   $safe_mode = __('On', 'wp-server-stats');
				}else{
				   $safe_mode = __('Off', 'wp-server-stats');
				}
				return $safe_mode;
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
							<li><strong><?php _e('Server OS', 'wp-server-stats'); ?></strong> : <span><?php echo $this->server_os(); ?>&nbsp;/&nbsp;<?php echo (PHP_INT_SIZE * 8) . __('Bit OS', 'wp-server-stats'); ?></span></li>
							<li><strong><?php _e('Server Software', 'wp-server-stats'); ?></strong> : <span><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span></li>
							<li><strong><?php _e('Server IP', 'wp-server-stats'); ?></strong> : <span><?php echo ( $this->validate_ip_address( $this->check_server_ip() ) ? $this->check_server_ip() : "ERROR IP096T" ); ?></span></li>	
							<li><strong><?php _e('Server Port', 'wp-server-stats'); ?></strong> : <span><?php echo $_SERVER['SERVER_PORT']; ?></span></li>
							<li><strong><?php _e('Server Location', 'wp-server-stats'); ?></strong> : <span><?php echo $this->check_server_location(); ?></span></li>
							<li><strong><?php _e('Server Hostname', 'wp-server-stats'); ?></strong> : <span><?php echo gethostname(); ?></span></li>
							<li><strong><?php _e('Site\'s Document Root', 'wp-server-stats'); ?></strong> : <span><?php echo $_SERVER['DOCUMENT_ROOT'] . '/'; ?></span></li>
							<li><strong><?php _e('Memcached Enabled', 'wp-server-stats'); ?></strong> : <span><?php echo ( class_exists('Memcache') ? __( 'Yes', 'wp-sever-stats' ) : __( 'No', 'wp-server-stats' ) ); ?></span></li>
							<?php if( $this->isShellEnabled() ) : ?>
							<li class="no-bottom-margin"><strong><?php _e('Total CPUs', 'wp-server-stats'); ?></strong> : <span><?php echo $this->check_cpu_count() . ' / ' . $this->check_core_count() . __('Cores', 'wp-server-stats'); ?></span></li>
							<?php endif; ?>
						<ul>
						<?php if( $this->isShellEnabled() ) : ?>
						<span style="line-height: 2.5em;"><strong><?php _e('Real Time CPU Load', 'wp-server-stats') ?>:</strong></span>
						<div class="progressbar">
							<div style="border:1px solid #DDDDDD; background-color:#F9F9F9;	border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px;">
		                        <div id="server-load-upper-div" style="padding: 0px; border-width:0px; color:#FFFFFF;text-align:right; border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px; margin-top: -1px;">
									<div id="server-load" style="padding:2px;"></div>
								</div>
							</div>
						</div>
						<?php endif; ?>
						<?php if( class_exists( 'Memcache' ) ) : ?>
							<div class="wpss_show_buttons content-center">
								<a href="<?php echo get_admin_url(); ?>admin.php?page=wpss_memcache_info" title="Checkout Memcached Info" class="wpss_btn button button-small"><?php _e( 'Check More Memcached Info', 'wp-server-stats' ); ?></a>
							</div>
						<?php endif; ?>
						<hr />
						<ul>
							<li><strong><?php _e('Database Software', 'wp-server-stats'); ?></strong> : <span><?php echo $this->database_software(); ?></span></li>
							<li><strong><?php _e('Database Version', 'wp-server-stats'); ?></strong> : <span><?php echo $this->database_version(); ?></span></li>
							<li><strong><?php _e('Maximum No. of Connections', 'wp-server-stats'); ?></strong> : <span><?php echo $this->database_max_no_connection(); ?></span></li>
							<li><strong><?php _e('Maximum Packet Size', 'wp-server-stats'); ?></strong> : <span><?php echo $this->database_max_packet_size(); ?></span></li>
							<li><strong><?php _e('Database Disk Usage', 'wp-server-stats'); ?></strong> : <span><?php echo $this->database_disk_usage(); ?></span></li>
							<li><strong><?php _e('Index Disk Usage', 'wp-server-stats'); ?></strong> : <span><?php echo $this->index_disk_usage(); ?></span></li>
						</ul>
						<div class="wpss_show_buttons content-center">
							<a href="<?php echo get_admin_url(); ?>admin.php?page=wpss_sql_info" title="Checkout More Database Info" class="wpss_btn button button-small"><?php _e( 'Check More Database Info', 'wp-server-stats' ); ?></a>
						</div>
						<hr />
						<ul>
							<li><strong><?php _e('PHP Version', 'wp-server-stats'); ?></strong> : <span><?php echo PHP_VERSION; ?></span></li>
							<li><strong><?php _e('PHP Max Upload Size', 'wp-server-stats'); ?></strong> : <span><?php echo $this->php_max_upload_size(); ?></span></li>
							<li><strong><?php _e('PHP Max Post Size', 'wp-server-stats'); ?></strong> : <span><?php echo $this->php_max_post_size(); ?></span></li>
							<li><strong><?php _e('PHP Max Execution Time', 'wp-server-stats'); ?></strong> : <span><?php echo $this->php_max_execution_time() . " " . __("sec", "wp-server-stats"); ?></span></li>
							<li><strong><?php _e('PHP Safe Mode', 'wp-server-stats'); ?></strong> : <span><?php echo $this->php_safe_mode(); ?></span></li>
							<li><strong><?php _e('PHP Short Tag', 'wp-server-stats'); ?></strong> : <span><?php echo $this->php_short_tag(); ?></span></li>
							<li><strong><?php _e('PHP Memory Limit', 'wp-server-stats'); ?></strong> : <span><?php echo $this->check_limit(); ?></span></li>
							<li><strong><?php _e('Real Time PHP Memory Usage', 'wp-server-stats'); ?></strong> : <span id="mem_usage_mb"></span></li>
						</ul>
						<div class="progressbar">
							<div style="border:1px solid #DDDDDD; background-color:#F9F9F9;	border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px;">
		                        <div id="memory-load-upper-div" style="padding: 0px; border-width:0px; color:#FFFFFF;text-align:right; border-color: rgb(223, 223, 223); box-shadow: 0px 1px 0px rgb(255, 255, 255) inset; border-radius: 3px; margin-top: -1px;">
		                        	<div id="memory-usage-pos" style="padding:2px;"></div>
								</div>
							</div>
						</div>
						<div class="wpss_show_buttons content-center">
							<a href="<?php echo get_admin_url(); ?>admin.php?page=wpss_php_info" title="Checkout More PHP Info" class="wpss_btn button button-small"><?php _e( 'Check More PHP Info', 'wp-server-stats' ); ?></a>
						</div>
						<?php if( $this->isShellEnabled() ) : ?>
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
				add_menu_page( 
					__( 'WP Server Stats', 'wp-server-stats' ), 
					__( 'WP Server Stats', 'wp-server-stats' ), 
					'manage_options', 
					'wp_server_stats', 
					'', 
					'dashicons-chart-area', 
					81
				);

				add_submenu_page( 
					'wp_server_stats', 
					__( 'WP Server Stats - General Settings', 'wp-server-stats' ), 
					__( 'General Settings', 'wp-server-stats' ), 
					'manage_options', 
					'wp_server_stats', 
					array( $this, 'admin_page_design' )
				);

				add_submenu_page( 
					'wp_server_stats', 
					__( 'WP Server Stats - PHP Information', 'wp-server-stats' ), 
					__( 'PHP Information', 'wp-server-stats' ), 
					'manage_options', 
					'wpss_php_info', 
					array( $this, 'php_details' )
				);

				add_submenu_page( 
					'wp_server_stats', 
					__( 'WP Server Stats - Database Information', 'wp-server-stats' ), 
					__( 'Database Information', 'wp-server-stats' ), 
					'manage_options', 
					'wpss_sql_info', 
					array( $this, 'sql_details' )
				);

				if( class_exists( 'Memcache' ) ) {
					add_submenu_page( 
						'wp_server_stats', 
						__( 'WP Server Stats - Memcache Information', 'wp-server-stats' ), 
						__( 'Memcache Information', 'wp-server-stats' ), 
						'manage_options', 
						'wpss_memcache_info', 
						array( $this, 'memcache_details' )
					);
				}
			}

			/* Function that will show the indepth PHP information */
			public function php_details() {
				?>
				<div class="wrap wpss_info">
					<h1><?php _e( 'PHP Information - WP Server Stats', 'wp-server-stats' ); ?></h1>
					<h3><?php _e( 'This page will show you the in-depth information about the PHP installasion on your server', 'wp-server-stats' ); ?></h3>
					<hr />
					<?php
					if( ! class_exists( 'DOMDocument' ) ) {
				        echo '<p>You need <a href="http://php.net/manual/en/class.domdocument.php" target="_blank">DOMDocument extension</a> to be enabled.</p>';
				    } else {
				        ob_start();
				        phpinfo();
				        $phpinfo = ob_get_contents();
				        ob_end_clean();

				        // Use DOMDocument to parse phpinfo()
				        libxml_use_internal_errors(true);
				        $html = new DOMDocument( '1.0', 'UTF-8' );
				        $html->loadHTML( $phpinfo );

				        // Style process
				        $tables = $html->getElementsByTagName( 'table' );
				        foreach( $tables as $table ) {
				            $table->setAttribute( 'class', 'widefat' );
				        }

				        // We only need the <body>
				        $xpath = new DOMXPath($html);
				        $body = $xpath->query('/html/body');

				        // Save HTML fragment
				        libxml_use_internal_errors(false);
				        $phpinfo_html = $html->saveXml( $body->item( 0 ) );

				        echo $phpinfo_html;
				    }
					?>
				</div>
				<?php
			}

			/* Function to show up SQL Server Information */
			public function sql_details() {
				?>
				<div class="wrap wpss_info">
					<h1><?php _e( 'Database Information - WP Server Stats', 'wp-server-stats' ); ?></h1>
					<h3><?php _e( 'This page will show you the in-depth information about your database', 'wp-server-stats' ); ?></h3>
					<hr />
					<h2><?php _e( 'Basic Database Information', 'wp-server-stats' ); ?></h2>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php _e( 'Variable Name', 'wp-server-stats' ); ?></th>
								<th><?php _e( 'Value', 'wp-server-stats' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td class="e"><?php _e( 'Variable Name', 'wp-server-stats' ); ?></td>
								<td><?php _e( 'Value', 'wp-server-stats' ); ?></td>
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td class="e"><?php _e('Database Software', 'wp-server-stats'); ?></td>
								<td class="v"><?php echo $this->database_software(); ?></td>
							</tr>
							<tr>
								<td class="e"><?php _e('Database Version', 'wp-server-stats'); ?></td>
								<td class="v"><?php echo $this->database_version(); ?></td>
							</tr>
							<tr>
								<td class="e"><?php _e('Maximum No. of Connections', 'wp-server-stats'); ?></td>
								<td class="v"><?php echo $this->database_max_no_connection(); ?></td>
							</tr>
							<tr>
								<td class="e"><?php _e('Maximum Packet Size', 'wp-server-stats'); ?></td>
								<td class="v"><?php echo $this->database_max_packet_size(); ?></td>
							</tr>
							<tr>
								<td class="e"><?php _e('Database Disk Usage', 'wp-server-stats'); ?></td>
								<td class="v"><?php echo $this->database_disk_usage(); ?></td>
							</tr>
							<tr>
								<td class="e"><?php _e('Index Disk Usage', 'wp-server-stats'); ?></td>
								<td class="v"><?php echo $this->index_disk_usage(); ?></td>
							</tr>
						</tbody>
					</table>
					<div class="clear give-some-space"></div>
					<hr />
					<h2><?php _e( 'Advanced Database Information', 'wp-server-stats' ); ?></h2>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php _e( 'Variable Name', 'wp-server-stats' ); ?></th>
								<th><?php _e( 'Value', 'wp-server-stats' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td><?php _e( 'Variable Name', 'wp-server-stats' ); ?></td>
								<td><?php _e( 'Value', 'wp-server-stats' ); ?></td>
							</tr>
						</tfoot>
						<tbody>
						<?php
							if( get_option( 'wpss_db_advanced_info' ) ) {
								$dbinfo = get_option( 'wpss_db_advanced_info' );
							} else {
								global $wpdb;
							    $dbversion = $wpdb->get_var("SELECT VERSION() AS version");
							    $dbinfo = $wpdb->get_results("SHOW VARIABLES");
							    update_option( 'wpss_db_advanced_info', $dbinfo );
							}
							
						    if( !empty( $dbinfo ) ) {
						        foreach( $dbinfo as $info ) {
						            echo '<tr><td class="e">' . $info->Variable_name . '</td><td class="v">' . htmlspecialchars($info->Value) . '</td></tr>';
						        }
						    } else {
						    	echo '<tr><td>' . __( 'Something went wrong!', 'wp-server-stats' ) . '</td><td>' . __( 'Something went wrong!', 'wp-server-stats' ) . '</td></tr>';
						    }
						?>
						</tbody>
					</table>
				</div>
				<?php
			}

			/* Function to show up Memcache details */
			public function memcache_details() {
				if( class_exists( 'Memcache' ) ) {
					$this->fetch_data(); //fetching data
					$memcached_obj = new Memcache;
					$memcached_obj->addServer( $this->memcache_host, $this->memcache_port );
					$memcachedinfo = $memcached_obj->getStats();
					if( !empty( $memcachedinfo ) ) {
					   	$cache_hit= ( ( $memcachedinfo['get_hits']/$memcachedinfo['cmd_get'] ) * 100 );
					   	$cache_hit = round( $cache_hit, 2 );
					   	$cache_miss = 100 - $cache_hit;
					   	$usage = round( ( ( $memcachedinfo['bytes']/$memcachedinfo['limit_maxbytes'] ) * 100 ), 2 );
					   	$uptime = number_format_i18n( ( $memcachedinfo['uptime']/60/60/24 ) );
					}
				
				?>
					<div class="wrap wpss_info">
						<h1><?php _e( 'Memcached Information - WP Server Stats', 'wp-server-stats' ); ?></h1>
						<h3><?php _e( 'This page will show you the in-depth information about your memcache server', 'wp-server-stats' ); ?></h3>
						<hr />
						<table class="widefat">
							<thead>
								<tr>
									<th><?php _e( 'Variable Name', 'wp-server-stats' ); ?></th>
									<th><?php _e( 'Value', 'wp-server-stats' ); ?></th>
									<th><?php _e( 'Description', 'wp-server-stats' ); ?></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td><?php _e( 'Variable Name', 'wp-server-stats' ); ?></td>
									<td><?php _e( 'Value', 'wp-server-stats' ); ?></td>
									<td><?php _e( 'Description', 'wp-server-stats' ); ?></td>
								</tr>
							</tfoot>
							<tbody>
								<tr>
									<td class="e"><?php _e( 'pid', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $memcachedinfo['pid']; ?></td>
									<td class="v"><?php _e( 'Process ID', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'vptime', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $uptime; ?></td>
									<td class="v"><?php _e( 'Number of days since the process was started', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'version', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $memcachedinfo['version']; ?></td>
									<td class="v"><?php _e( 'Memcached Version', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'rusage_user', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $memcachedinfo['rusage_user']; ?></td>
									<td class="v"><?php _e( 'Number of seconds the cpu has devoted to the process as the user', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'rusage_system', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $memcachedinfo['rusage_system']; ?></td>
									<td class="v"><?php _e( 'Number of seconds the cpu has devoted to the process as the system', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'curr_items', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['curr_items'] ); ?></td>
									<td class="v"><?php _e( 'Total number of items currently in memcached', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'total_items', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['total_items'] ); ?></td>
									<td class="v"><?php _e( 'Total number of items that have passed through memcached', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'bytes', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $this->format_filesize( $memcachedinfo['bytes'] ); ?></td>
									<td class="v"><?php _e( 'Memory size currently used by <code>curr_items</code>', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'limit_maxbytes', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $this->format_filesize( $memcachedinfo['limit_maxbytes'] ); ?></td>
									<td class="v"><?php _e( 'Maximum memory size allocated to memcached', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'curr_connections', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['curr_connections'] ); ?></td>
									<td class="v"><?php _e( 'Total number of open connections to memcached', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'total_connections', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['total_connections'] ); ?></td>
									<td class="v"><?php _e( 'Total number of connections opened since memcached started running', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'connection_structures', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['connection_structures'] ); ?></td>
									<td class="v"><?php _e( 'Number of connection structures allocated by the server', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'cmd_get', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['cmd_get'] ); ?></td>
									<td class="v"><?php _e( 'Total GET commands issued to the server', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'cmd_set', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['cmd_set'] ); ?></td>
									<td class="v"><?php _e( 'Total SET commands issued to the server', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'cmd_flush', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['cmd_flush'] ); ?></td>
									<td class="v"><?php _e( 'Total FLUSH commands issued to the server', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'get_hits', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['get_hits'] ) . '(' . $cache_hit . '%)'; ?></td>
									<td class="v"><?php _e( 'Total number of times a GET command was <strong>able</strong> to retrieve and return data', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'get_misses', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['get_misses'] ) . '(' . $cache_miss . '%)'; ?></td>
									<td class="v"><?php _e( 'Total number of times a GET command was <strong>unable</strong> to retrieve and return data', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'delete_hits', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['delete_hits'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a DELETE command was <strong>able</strong> to delete data', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'delete_misses', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['delete_misses'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a DELETE command was <strong>unable</strong> to delete data', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'incr_hits', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['incr_hits'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a INCR command was <strong>able</strong> to increment a value', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'incr_misses', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['incr_misses'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a INCR command was <strong>unable</strong> to increment a value', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'decr_hits', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['decr_hits'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a DECR command was <strong>able</strong> to decrement a value', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'decr_misses', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['decr_misses'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a DECR command was <strong>unable</strong> to decrement a value', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'cas_hits', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['cas_hits'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a CAS command was <strong>able</strong> to compare and swap data', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'cas_misses', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['cas_misses'] ); ?></td>
									<td class="v"><?php _e( 'Total number of times a CAS command was <strong>unable</strong> to compare and swap data', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'cas_badval', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['cas_badval'] ); ?></td>
									<td class="v"><?php _e( 'The "cas" command is some kind of Memcached\'s way to avoid locking. "cas" calls with bad identifier are counted in this stats key', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'bytes_read', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $this->format_filesize( $memcachedinfo['bytes_read'] ); ?></td>
									<td class="v"><?php _e( 'Total number of bytes input into the server', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'bytes_written', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo $this->format_filesize( $memcachedinfo['bytes_written'] ); ?></td>
									<td class="v"><?php _e( 'Total number of bytes written by the server', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'evictions', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['evictions'] ); ?></td>
									<td class="v"><?php _e( 'Number of valid items removed from cache to free memory for new items', 'wp-server-stats' ); ?></td>
								</tr>
								<tr>
									<td class="e"><?php _e( 'reclaimed', 'wp-server-stats' ); ?></td>
									<td class="v"><?php echo number_format_i18n( $memcachedinfo['reclaimed'] ); ?></td>
									<td class="v"><?php _e( 'Number of items reclaimed', 'wp-server-stats' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				<?php
				} // end if class_exists( 'Memcache' )
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

						            //print_r( get_option( 'wpss_settings_options') );
						        ?>
							</form>
						</div>
						<div id="wpss-sidebar">
							<h2>
								<?php
									_e('Some info about the settings options', 'wp-server-stats');
								?>
							</h2>
							<ul class="user-info">
								<li>
									<strong class="highlight"><?php _e('Refresh Interval', 'wp-server-stats'); ?></strong>
									<?php _e('This denotes the interval time after which the shell commands will execute again to give you the current load details. By default it is set to 200ms, but if you are seeing CPU load increase after instealling this plugin, try to increase the interval time to 1000ms, 2000ms, 3000ms or more until you see a normal CPU load. Generally it is not recommended to change the value unless you are having extremely high CPU load due to this plugin.', 'wp-server-stats' ); ?>
								</li>
								<li>
									<strong class="highlight"><?php _e('Status Bar & Footer Text Color', 'wp-server-stats'); ?></strong>
									<?php _e('In case you do not like the color scheme I have used on this plugin, you can easily change those colors.', 'wp-server-stats' ); ?>
								</li>
								<li>
									<strong class="highlight"><?php _e('Memcached Server Host & Port', 'wp-server-stats'); ?></strong>
									<?php _e('Memcached is a general-purpose distributed memory caching system. It is often used to speed up dynamic database-driven websites by caching data and objects in RAM to reduce the number of times an external data source must be read. But in most Shared Hosting servers Memcached will not be enabled. This generally used in personal VPS or Dedicated servers.', 'wp-server-stats'); ?>
									<br />
									<?php _e('So, if you are using a shared hosting server, chances are Memcached is not enabled on your server. In this case you don\'t need to change any of the Memcached settings on the left side. But if you are using a VPS or dedicated server which has Memcached enabled, make sure the Memcached Host & Port details has been provided properly on the settings. If you don\'t have these details, please contact your host and ask them about it.' , 'wp-server-stats'); ?>
								</li>
							</ul>
							<hr />
							<h2><?php _e('Support the plugin', 'wp-server-stats'); ?></h2>
							<p><?php _e('Believe it or not, developing a WorPress plugin really takes quite a lot of time to develop, test and to do continuous bugfix. Moreover as I\'m sharing this plugin for free, so all those times I\'ve spent coding this plugin yeild no revenue. So, overtime it become really hard to keep spending time on this plugin. So, if you like this plugin, I will really appriciate if you consider donating some amount for this plugin. Which will help me keep spending time on this plugin and make it even better. Please donate, if you can.', 'wp-server-stats'); ?></p>
							<a href="http://donate.isaumya.com/" class="content-center" target="_blank">
								<img src ="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" />
							</a>
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

			    // Files for Memcache Server Details
			    add_settings_field( 'wpss_memcache_host_field', __( 'Memcached Server Host (Only if you have Memcached installed in your server)', 'wp-server-stats' ), array( $this, 'memcache_host_field' ), 'wp_server_stats', 'wpss_section' ); // id, title, display cb, page, section
			    add_settings_field( 'wpss_memcache_port_field', __( 'Memcached Server Port (Only if you have Memcached installed in your server)', 'wp-server-stats' ), array( $this, 'memcache_port_field' ), 'wp_server_stats', 'wpss_section' ); // id, title, display cb, page, section

			    // Fields for ip-api.com pro support section
			    add_settings_field( 'wpss_use_ipapi_pro', __( 'Do you want to use the IP-API Pro key?', 'wp-server-stats' ), array( $this, 'use_ipapi_pro' ), 'wp_server_stats', 'wpss_section' ); // id, title, display cb, page, section
			    add_settings_field( 'wpss_ipapi_pro_key', __( 'Provide your IP-API Pro key', 'wp-server-stats' ), array( $this, 'ipapi_pro_key' ), 'wp_server_stats', 'wpss_section' ); // id, title, display cb, page, section
			     
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

			public function memcache_host_field() {
				echo '<input type="text" name="wpss_settings_options[memcache_host]" value="' . $this->memcache_host  . '" />';
			}

			public function memcache_port_field() {
				echo '<input type="number" name="wpss_settings_options[memcache_port]" value="' . $this->memcache_port  . '" />';
			}

			public function use_ipapi_pro() {
				$this->fetch_data();
				$options = get_option( 'wpss_settings_options' );
				?>
				<input type="radio" name="wpss_settings_options[use_ipapi_pro]" value="Yes" <?php checked( empty( $options['use_ipapi_pro'] ) ? $this->use_ipapi_pro : $options['use_ipapi_pro'], 'Yes' ) ?> /> 
				<span><?php _e( 'Yes', 'wp-server-stats' ); ?></span>
				
				<input type="radio" name="wpss_settings_options[use_ipapi_pro]" value="No" <?php checked( empty( $options['use_ipapi_pro'] ) ? $this->use_ipapi_pro : $options['use_ipapi_pro'], 'No' ) ?> /> 
				<span><?php _e( 'No', 'wp-server-stats' ); ?></span>
				<br />
				<p><?php printf( __( 'By default this plugin uses the free API from %1$sIP-API.com%2$s which allows %3$s150 requests/min%4$s. But for high traffic websites, this might be very small and may generate %3$s503 Error%4$s if you try to do more than %3$s150 req/min%4$s. To resolve this problem, you can use the %5$sPaid Version of IP-API%6$s and provide your paid key below which will allow you to do %7$sUnlimited%8$s nuber of requests.', 'wp-server-stats'),
					'<a href="http://ip-api.com/" target="_blank" rel="external nofollow">', '</a>',
					'<code>', '</code>',
					'<strong><a href="https://signup.ip-api.com/" target="_blank" rel="external nofollow">', '</a></strong>',
					'<strong>', '</strong>' ); ?>
				</p>
				<?php
			}

			public function ipapi_pro_key() {
				$this->fetch_data();
				?>
				<input type="text" name="wpss_settings_options[ipapi_pro_key]" value="<?php echo $this->ipapi_pro_key; ?>" placeholder="AbcDEFGhiJ0KL1m">
				<p><?php printf( __( 'Please provide your paid API key of ip-api.com which you have %1$sreceived over email%2$s after %3$spurchasing the paid IP-API subscription%4$s. %5$sCheck this screenshot%6$s to understand what key I\'m talking about.', 'wp-server-stats'),
					'<strong>', '</strong>',
					'<a href="https://signup.ip-api.com/" target="_blank" rel="external nofollow">', '</a>',
					'<strong><a href="https://i.imgur.com/gp2mXiH.jpg" target="_blank" rel="external nofollow">', '</a></strong>' );?>
				</p>
				<?php
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

			    $memcache_host = trim( $fields['memcache_host'] );
			    $valid_fields['memcache_host'] = strip_tags( stripslashes( $memcache_host ) );

			    $memcache_port = trim( $fields['memcache_port'] );
			    $valid_fields['memcache_port'] = strip_tags( stripslashes( $memcache_port ) );

			    $valid_fields['use_ipapi_pro'] = strip_tags( stripslashes( trim( $fields['use_ipapi_pro'] ) ) );

			    $valid_fields['ipapi_pro_key'] = strip_tags( stripslashes( trim( $fields['ipapi_pro_key'] ) ) );
			     
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

			public function show_admin_notice() {
				settings_errors( 'wpss_settings_options' );
				//Making sure the following welcome notice doesn't show up after closing it
				if( ! PAnD::is_admin_notice_active( 'wpss-donate-notice-forever' ) ) {
					return;
				}
				$class = 'notice notice-success is-dismissible donate_notice';
				$message = sprintf( 
								__('%1$sThank you%2$s for installing %1$sWP Server Stats%2$s. It took countless hours to code, design, test and include many useful server info that you like so much to show up in your WordPress dashboard. But as this is a <strong>free plugin</strong>, all of these time and effort does not generate any revenue. Also as I\'m not a very privileged person, so earning revenue matters to me for keeping my lights on and keep me motivated to do the work I love. %3$s So, if you enjoy this plugin and understand the huge effort I put into this, please consider %1$s%4$sdonating some amount%5$s (no matter how small)%2$s for keeping aliave the development of this plugin. Thank you again for using my plugin. Also if you love using this plugin, I would really appiciate if you take 2 minutes out of your busy schedule to %1$s%6$sshare your review%7$s%2$s about this plugin.', 'wp-server-stats'),
								'<strong>', '</strong>',
								'<br /> <br />',
								'<a href="http://donate.isaumya.com" target="_blank" rel="external" title="WP Server Stats - Plugin Donation">', '</a>',
								'<a href="https://wordpress.org/support/plugin/wp-server-stats/reviews/" target="_blank" rel="external" title="WP Server Stats - Post your Plugin Review">', '</a>'
							);
				printf( '<div data-dismissible="wpss-donate-notice-forever" class="%1$s"><p>%2$s</p></div>', $class, $message );
			}

			/**
			 * Function that will fetch the user entered data in the settings page from database
			**/
			public function fetch_data() {
				// assuming our wpss_settings_option entry in database's option table is already there
				// so lets try to fetch it
				$fetched_data = get_option( 'wpss_settings_options' ); // $fetched_data will be an array

				if( !empty( $fetched_data ) ) {

					// fetching the refresh_interval data
					if( !empty( $fetched_data['refresh_interval'] ) ) {
						$this->refresh_interval = $fetched_data['refresh_interval'];
					} else {
						$this->refresh_interval = 200; // default refresh interval is 200ms
					}

					// fetching memcache host
					if( !empty( $fetched_data['memcache_host'] ) ) {
						$this->memcache_host = $fetched_data['memcache_host'];
					} else {
						$this->memcache_host = 'localhost'; // default memcache host localhost
					}

					// fetching memcache port
					if( !empty( $fetched_data['memcache_port'] ) ) {
						$this->memcache_port = $fetched_data['memcache_port'];
					} else {
						$this->memcache_port = 11211; // default memcache port 11211
					}

					// fetching if using ip-api
					if( !empty( $fetched_data['use_ipapi_pro'] ) ) {
						$this->use_ipapi_pro = $fetched_data['use_ipapi_pro'];
					} else {
						$this->use_ipapi_pro = 'No';
					}

					// fetching the ip-api key
					if( !empty( $fetched_data['ipapi_pro_key'] ) ) {
						$this->ipapi_pro_key = $fetched_data['ipapi_pro_key'];
					} else {
						$this->ipapi_pro_key = '';
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
					$this->memcache_host = 'localhost';
					$this->memcache_port = 11211;
					$this->use_ipapi_pro = 'No';
					$this->ipapi_pro_key = '';
				}
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
								admin_url('admin.php')
							),
							__("Settings", "wp-server-stats")
						)
					)
				);
			}

		} //end of class
	} //end of checking if wp_server_stats already existsi or not

	// Start this plugin once all other plugins are fully loaded
	add_action( 'plugins_loaded', create_function('', '$memory = new wp_server_stats();') );
}