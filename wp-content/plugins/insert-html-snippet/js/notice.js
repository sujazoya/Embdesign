	jQuery(document).ready(function() {
		jQuery('#xyz_ihs_system_notice_area').animate({
			opacity : 'show',
			height : 'show'
		}, 500);

		jQuery('#xyz_ihs_system_notice_area_dismiss').click(function() {
			jQuery('#xyz_ihs_system_notice_area').animate({
				opacity : 'hide',
				height : 'hide'
			}, 500);

		});

	});
