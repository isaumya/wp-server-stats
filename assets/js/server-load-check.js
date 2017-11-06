(function($){

	$(function(){
		var flag = false;
		function do_ajax() {
			data = {
				action: 'process_ajax',
				wpss_nonce: server_load_check_vars.server_load_check_nonce
			};
			$.post(ajaxurl, data, function(response) {
					var load = response.cpu_load;
					$('#server-load').html( load + "%");
					$('#cpu_load_footer').html( load + "%");
					if( load < 10 ) {
						$('#server-load').css({
							"margin-left": "30px",
							"color": "#444"
						});
					} else {
						$('#server-load').css({
							"margin-left": "auto",
							"color": "#fff"
						});
					}
					if( load > 80 ) {
						var background_color = response.bg_color_average;
					} else if ( load > 95 ) {
						var background_color = response.bg_color_bad;
					} else {
						var background_color = response.bg_color_good;
					}
					$('#server-load-upper-div').css({
						"width": load + '%',
						"background-color": background_color
					});

					/*Fetching memory load in MB*/
					var memory_load_mb = response.memory_usage_MB;
					$('#mem_usage_mb').html( memory_load_mb + " MB" );
					$('#mem_usage_mb_footer').html( memory_load_mb + " MB" );

					/*Fetching memory load in percentage*/
					var memory_usage_pos = response.memory_usage_pos;
					$('#memory-usage-pos').html( memory_usage_pos + "%");
					$('#memory-usage-pos-footer').html( memory_usage_pos + "%");
					
					if( memory_usage_pos < 10 ) {
						$('#memory-usage-pos').css({
							"margin-left": "30px",
							"color": "#444"
						});
					} else {
						$('#memory-usage-pos').css({
							"margin-left": "auto",
							"color": "#fff"
						});
					}
					if( memory_usage_pos > 80 ) {
						var mem_background_color = response.bg_color_average;
					} else if ( memory_usage_pos > 95 ) {
						var mem_background_color = response.bg_color_bad;
					} else {
						var mem_background_color = response.bg_color_good;
					}
					$('#memory-load-upper-div').css({
						"width": memory_usage_pos + '%',
						"background-color": mem_background_color
					});

					/*Fetching RAM Usage*/
					$('#realtime_ram_usage').html( response.free_ram );
					$('#ram_usage_footer').html( response.free_ram );

					/*Fetching RAM load in percentage*/
					var ram_usage_pos = response.ram_usage_pos;
					$('#ram-usage').html( ram_usage_pos + "%");
					$('#ram-usage-pos-footer').html( ram_usage_pos + "%");
					
					if( ram_usage_pos < 10 ) {
						$('#ram-usage').css({
							"margin-left": "30px",
							"color": "#444"
						});
					} else {
						$('#ram-usage').css({
							"margin-left": "auto",
							"color": "#fff"
						});
					}
					if( ram_usage_pos > 80 ) {
						var ram_background_color = response.bg_color_average;
					} else if ( ram_usage_pos > 95 ) {
						var ram_background_color = response.bg_color_bad;
					} else {
						var ram_background_color = response.bg_color_good;
					}
					$('#ram-usage-upper-div').css({
						"width": ram_usage_pos + '%',
						"background-color": ram_background_color
					});

				setTimeout( do_ajax, response.refresh_interval ); //After completion of request, time to redo it after a second
				if( flag == false) {
					showUptime( response.uptime );
					flag = true;
				}
			}, 'json');
		}
		do_ajax();

		function showUptime( upsec ){
			var uptimeg = upsec;
			var clock = $('.uptime').FlipClock( uptimeg , {
				clockFace: 'DailyCounter',
				countdown: false
			});
		}

	});

	$(function() {
        // Add Color Picker to all inputs that have 'color-field' class
        $( '.wpss-color-picker' ).wpColorPicker();
    });

	$(window).resize(function () {
        var browserWidth = $( window ).width();
		//console.log('width ' + browserWidth);
		if ( browserWidth > 1800 ) {
			var zoom = ( (0.54 / 1920) * browserWidth );
		} else if ( browserWidth > 1499 && browserWidth <= 1800) {
			var zoom = ( (0.68 / 1800) * browserWidth );
		} else if ( browserWidth > 1252 && browserWidth <= 1499) {
			var zoom = ( (0.81 / 1426) * browserWidth );
		} else if ( browserWidth > 943 && browserWidth <= 1252) {
			var zoom = ( (0.48 / 947) * browserWidth );
		} else if ( browserWidth > 782 && browserWidth <= 943) {
			var zoom = ( (0.45 / 782) * browserWidth );
		} else {
			var zoom = ( (0.6 / 491) * browserWidth );
		}
		//console.log('zoom ' + zoom);
		$('.uptime').css({
		    'zoom' : ''+zoom+'',
		    '-ms-transform': 'scale('+zoom+','+zoom+')',
		    '-moz-transform': 'scale('+zoom+','+zoom+')',
		    '-ms-transform-origin': '0 0',
		    '-moz-transform-origin': '0 0',
		    'width': '-moz-max-content'
		});
    }).resize();

})(jQuery);