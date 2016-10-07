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
						var background_color = '#d35400';
					} else if ( load > 95 ) {
						var background_color = '#e74c3c';
					} else {
						var background_color = '#37BF91';
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
						var mem_background_color = '#d35400';
					} else if ( memory_usage_pos > 95 ) {
						var mem_background_color = '#e74c3c';
					} else {
						var mem_background_color = '#37BF91';
					}
					$('#memory-load-upper-div').css({
						"width": memory_usage_pos + '%',
						"background-color": mem_background_color
					});
				setTimeout(do_ajax, 200); //After completion of request, time to redo it after a second
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