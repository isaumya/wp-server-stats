<?php
/*
Plugin Name: WP Server Stats
Plugin URI: https://www.isaumya.com/portfolio-item/wp-server-stats/
Description: Show up the memory limit and current memory usage in the dashboard and admin footer
Donate link: http://donate.isaumya.com/
Author: Saumya Majumder
Author URI: https://www.isaumya.com/
Version: 1.2.1
Tags: dashboard, widget, server, stats, information, admin
Text Domain: wp-server-stats
Requires at least: 4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

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
session_start();

if ( is_admin() ) {	
	
	class wp_server_stats {
		
		var $memory = false;
		
		/*function wp_server_stats() {
			return $this->__construct();
		}*/

		function __construct() {
            add_action( 'init', array (&$this, 'check_limit') );
			add_action( 'wp_dashboard_setup', array (&$this, 'add_dashboard') );
			add_filter( 'admin_footer_text', array (&$this, 'add_footer') );
			add_action( 'admin_enqueue_scripts', array (&$this, 'load_admin_scripts') );
			add_action( 'admin_enqueue_scripts', array (&$this, 'load_admin_styles') );
			add_action( 'wp_ajax_process_ajax', array (&$this, 'process_ajax') );

			$this->memory = array();
		}
        
        function check_limit() {
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

        function check_memory_limit_cal() {
        	return (int) ini_get('memory_limit');
        }
		
		function check_server_ip() {
			return gethostbyname( gethostname() );
		}

		function check_server_location() {
			//$ip = $_REQUEST['REMOTE_ADDR'];
			$ip = gethostbyname( gethostname() );
			$query = @unserialize(file_get_contents('http://ip-api.com/php/'.$ip));
			$server_location = wp_cache_get( 'server_location' );
			if( false === $server_location && $query && $query['status'] == 'success' ) {
			  $server_location = $query['city'] . ', ' . $query['country'];
			  wp_cache_set( 'server_location', $server_location );
			  return $server_location;
			} else {
			  return $query['message'];
			}
		}

		function isShellEnabled() {
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

		function check_cpu_count() {
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

		function check_core_count() {
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

		function load_admin_styles() {
			wp_enqueue_style('flipclock', plugin_dir_url( __FILE__ ) . 'assets/css/flipclock.min.css', array(), '0.7.3');
		}

		function load_admin_scripts() {
		    $server_load_nonce = wp_create_nonce( 'slc_nonce' );
		    $_SESSION['server_load_check_nonce'] = $server_load_nonce;
			wp_register_script('server-load-check-ajax', plugin_dir_url( __FILE__ ) . 'assets/js/server-load-check.min.js', array(jquery), '1.0.0', true);
			wp_enqueue_script('server-load-check-ajax');
			wp_localize_script( 'server-load-check-ajax', 'server_load_check_vars', array(
					'server_load_check_nonce' => $server_load_nonce
				)
			);
			wp_register_script('flipclock', plugin_dir_url( __FILE__ ) . 'assets/js/flipclock.min.js', array(jquery), '0.7.3', true);
			wp_enqueue_script('flipclock');
		}

		function process_ajax() {
			if( !isset( $_SESSION['server_load_check_nonce'] ) || !wp_verify_nonce( $_SESSION['server_load_check_nonce'], 'slc_nonce' ) )
				die( 'Permission Check Failed' );

			if( $this->isShellEnabled() ) {
				$cpu_load = trim( shell_exec("echo $((`ps aux|awk 'NR > 0 { s +=$3 }; END {print s}'| cut -d . -f 1` / `cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | wc -l`))") );
				$memory_usage_MB = function_exists('memory_get_usage') ? round(memory_get_usage() / 1024 / 1024, 2) : 0;
				$memory_usage_pos = round ($memory_usage_MB / (int) $this->check_memory_limit_cal() * 100, 0);
				$uptime = trim( shell_exec("cut -d. -f1 /proc/uptime") );
				$json_out = array (
						'cpu_load' => $cpu_load,
						'memory_usage_MB' => $memory_usage_MB,
						'memory_usage_pos' => $memory_usage_pos,
						'uptime' => $uptime
					);
				echo json_encode($json_out);
			} else {
				$memory_usage_MB = function_exists('memory_get_usage') ? round(memory_get_usage() / 1024 / 1024, 2) : 0;
				$memory_usage_pos = round ($memory_usage_MB / (int) $this->check_memory_limit_cal() * 100, 0);
				$json_out = array (
						'cpu_load' => null,
						'memory_usage_MB' => $memory_usage_MB,
						'memory_usage_pos' => $memory_usage_pos,
						'uptime' => null
					);
				echo json_encode($json_out);
			}
			die();
		}
		
		function dashboard_output() {
			if ( current_user_can( 'manage_options' ) ) :
				?>
					<ul>
						<li><strong><?php _e('Server IP', 'wp-server-stats'); ?></strong> : <span><?php echo $this->check_server_ip(); ?></span></li>	
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
						<p style="text-align: justify;"><strong>Special Note:</strong> Hi, please note that PHP <code>shell_exec()</code> function 
						is either not enable in your hosting environment or not been given executable permission, hence you won't be seeing 
						the following results above: CPU/Core count, Real Time CPU Usage, Server Uptime. To see these details, please ask your host 
						to enable <code>shell_exec()</code> function and give it executable permission.</p>
					<?php endif;
			endif;
		}
		 
		function add_dashboard() {
			wp_add_dashboard_widget( 'wp_memory_dashboard', 'Server Overview', array (&$this, 'dashboard_output') );
		}
		
		function add_footer($content) {
			if( current_user_can( 'manage_options' ) ) :
				if( $this->isShellEnabled() ) {
					$content .= ' | <strong style="color: #8e44ad;">'. __('Memory', 'wp-server-stats') .' : <span id="mem_usage_mb_footer"></span>' 
					. ' ' . __('of', 'wp-server-stats') . ' ' . $this->check_limit() . ' (<span id="memory-usage-pos-footer"></span> '
					. __('used', 'wp-server-stats') .')</strong> | <strong style="color: #8e44ad;">' . __('CPU Load', 'wp-server-stats') 
					. ': <span id="cpu_load_footer"></span></strong>';
				} else {
					$content .= ' | <strong style="color: #8e44ad;">'. __('Memory', 'wp-server-stats') .' : <span id="mem_usage_mb_footer"></span>' 
					. ' ' . __('of', 'wp-server-stats') . ' ' . $this->check_limit() . ' (<span id="memory-usage-pos-footer"></span> '
					. __('used', 'wp-server-stats') .')</strong>';
				}
				return $content;
			endif;
		}

	}

	// Start this plugin once all other plugins are fully loaded
	add_action( 'plugins_loaded', create_function('', '$memory = new wp_server_stats();') );
}