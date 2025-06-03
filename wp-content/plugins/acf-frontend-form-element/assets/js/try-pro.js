(function($) {
	$( document ).ready(
		function() {

			var container = $( '.fea-upgrade-pro-action' );
			if (container.length) {
				container.find( 'a.fea-dismiss-notice' ).click(
					function(e) {
						e.preventDefault();
						container.remove();
						$.post(
							fa.ajaxurl,
							{
								action: 'fea-upgrade-pro-dismiss',
								_n: $( this ).attr( 'data-nonce' )
							},
							function(result) {}
						);

					}
				);
			}
		}
	);
})( jQuery );
