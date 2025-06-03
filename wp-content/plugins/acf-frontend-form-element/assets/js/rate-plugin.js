(function($) {
	$( document ).ready(
		function() {
			var container = $( '.fea-rate-action' );
			if (container.length) {
				container.find( 'a' ).click(
					function(e) {
						e.preventDefault();
						container.remove();
						var rateAction = $( this ).attr( 'data-rate-action' );
						var rateUrl    = $( this ).attr( 'data-href' );
						$.post(
							fa.ajaxurl,
							{
								action: 'fea-rate-plugin',
								rate_action: rateAction,
								_n: container.find( 'ul:first' ).attr( 'data-nonce' ),
							},
						);

						if ('do-rate' !== rateAction) {
							return false;
						} else {
							window.open( rateUrl, '_blank' );
						}
					}
				);
			}
		}
	);
})( jQuery );
