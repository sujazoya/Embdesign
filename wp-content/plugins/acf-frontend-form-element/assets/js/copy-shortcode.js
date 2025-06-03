(function($) {
	$( document ).on(
		'click',
		'.copy-shortcode',
		function(e){
			var copyText = "[" + $( this ).data( 'prefix' ) + "=" + $( this ).data( 'value' ) + "]";

			/* Copy the text */
			if (!navigator.clipboard) {
				alert('Clipboard API not supported');
				return;
			}
			navigator.clipboard.writeText( copyText );

			var normalText = $( this ).html();

			$( this ).addClass( 'copied-text' ).html( normalText.replace( acf.__( "Copy Code" ),acf.__( "Code Copied" ) ) ).css( {'background-color':'#4BB543','color':'#fff'} );
			setTimeout(
				function(){
					$( 'body' ).find( '.copied-text' ).removeClass( 'copied-text' ).html( normalText.replace( acf.__( "Code Copied" ),acf.__( "Copy Code" ) ) ).css( {'background-color':'#fff','color':'#000'} );
				},
				1000
			);
		}
	);

	if (typeof acf === 'undefined') {
		console.error('ACF is not defined');
		return;
	}
	acf.addAction(
		'add_field_object',
		function(newField){
			var newKey = newField.get( 'key' ).replace( "field_", "" );
			var $el    = newField.$el.find( '.copy-shortcode' );
			$el.attr( 'data-value',newKey );
			$el.siblings( 'code' ).html( $el.siblings( 'code' ).html().replace( /acfcloneindex/g,newKey ) );
		},
		12
	);

})( jQuery );
