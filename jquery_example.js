var pageListHandler = ( function($) {

	function switchPage(page) {
		$('.current_page').val(page);
		applyFilters();
	}

	function displayFilters() {
		$('#display_filters').css("display","none");
		$('#hide_filters').css("display","");
		$('#apply_filters').css("display","");
		$('#reset_filters').attr("class", "button action");
		$('.filters').css("display","");
	}

	function hideFilters() {
		$('#hide_filters').css("display","none");
		$('#display_filters').css("display","");
		$('#apply_filters').css("display","none");
		$('#reset_filters').attr("class", "button-primary");
		$('.filters').css("display","none");
	}

	function resetFilters() {
		$('#blog_id').val("");
		$('#datetime_from').val("");
		$('#datetime_to').val("");
		$('#m_status').val("");
		$('#send_to').val("");
		$('#subject').val("");
		if($('.filters').css("display") == "none") applyFilters();
	}

	function applyFilters() {
		hideFilters();
		$('.filters').css("display","none");
		$('#loader').fadeIn('slow');
		$('#navi_form').submit();
	}

	function displayMessage( log_id, message ) {
		var winH = window.innerHeight;
		var winW = window.innerWidth;
		var scroll = window.scrollY;
		$('#top-log-id').html( log_id );
		$('#message').html( message );
		$('#dialog').css('top',  winH/2-$('#dialog').height()/2+scroll);
		$('#dialog').css('left', winW/2-$('#dialog').width()/2);
		$('#loader').hide();
		$('#mask').show();
		$('#dialog').show();
	}

	$(document).ready(function() {
		$('.icon-detail').click(function(e) {

			$(document).on("keydown", function(e) {
				if(e.keyCode === 27) {
					$('#mask, .window').hide();
					e.preventDefault();
					$(document).off("keydown");
					return false;
				}
			});

			$('#loader').show();

			if ( $(this).parent().parent().hasClass("selected-row") ) {
				e.preventDefault();
				e.stopPropagation();
			}

			var log_id = $(this).attr('log_id');
			var data = {
			 	action: 'wp_mail_monitor_get_log_record',
			 	log_id: log_id,
			 	_ajax_nonce: '3A33EF322AB92'
			};

			$.ajax({
				url: ajaxurl,
				data: data,
				method: 'POST',
				success: function( response ){

					if (response == '') {
						message = "<span class='error-msg'>Response is empty. Please check logs.</span>";
					} else {
						var record = JSON.parse( response );
						//console.log(record);
						message = "<div class='grid-2col'>\n";
						Object.keys( record ).forEach( function(key) {
							//console.log( key, record[key] );
							var val = record[key];
							if (val == null ) val = '';
							message = message + "<div class='grid-label'>" +key + ":</div><div class='grid-value'>" + val.replace(/\n/g,'<br>') + "</div>\n";
						});
						message = message + "</div>\n";
						displayMessage( log_id, message );
					}

				},
				error: function(error){
					console.log(error);
					if ( error.statusText ) {
						message = "<span class='error-msg'>Error code: " + error.status + "<br>Error text: " + error.statusText + "</span>";
						displayMessage( log_id, message );
					}
				}
			});
		});
		$('.window .close').click(function (e) {
			e.preventDefault();
			$('#mask, .window').hide();
			$(".record-row").css('background','');
		});
		$('#mask').click(function () {
			$(this).hide();
			$('.window').hide();
			$(".record-row").css('background','');
		});
		$('.current_page').keydown(function(e) {
			if(e.keyCode === 13) {
				var newVal = $(this).val();
				$('.current_page').val(newVal);
				applyFilters();
			}
		});
		$('.per-page').on('change',function() {
			var newVal = $(this).val();
			$('.per-page').val(newVal);
			applyFilters();
		});
		$('.record-row').click(function() {
			if ( $(this).hasClass('selected-row') ) {
				$(this).removeClass('selected-row');
			} else {
				$('.record-row').removeClass('selected-row');
				$(this).addClass("selected-row");
			}
		});
	});

	return {
		switchPage: switchPage,
		displayFilters: displayFilters,
		hideFilters: hideFilters,
		resetFilters: resetFilters,
		applyFilters: applyFilters
	};

})(jQuery);
