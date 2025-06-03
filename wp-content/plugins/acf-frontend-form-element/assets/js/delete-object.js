(function($) {
    $(document).ready(function() {
        $( 'body' ).on(
            'click',
            '.fea-delete-button',
            function(e){
                let button = $( this );
    
                if ( button.hasClass( 'disabled' ) ) {
                    return;
                }
    
                button.addClass( 'disabled' )
    
                let tooltip = acf.newTooltip(
                    {
                        confirm: true,
                        text: button.data( 'confirm' ),
                        target: button,
                        context: button.closest( '.acf-field' ),
                        confirm: function () {
                            deleteObject( button );
                        },
                        cancel: function () {
                            button.removeClass( 'disabled' );
                        }
                    }
                );
    
            }
        );
    
        function deleteObject(button) {
            $( window ).off( 'beforeunload' );
            button.after( '<span class="fea-loader"></span>' )
            $form = button.closest( 'form' );

            if( ! $form[0] ){
                $form = button.siblings('form');
                console.log($form);
                if( ! $form[0] ){
                    console.log('Object data not found');
                    return;
                }
            }

            $form.find( '.fea-submit-button' ).addClass( 'disabled' );
    
            let formData = new FormData( $form[0] );

            console.log(formData)
    
            let fieldWrap = button.closest( '.acf-field' );
    
            let fieldKey = $form.data('field');
            if( ! fieldKey ){
                fieldKey = fieldWrap.data('key') || button.data('key');   
            }
    
            if( ! fieldKey ){
                console.log('Field key not found');
                return;
            }
    
            formData.append( 'action','frontend_admin/delete_object' );
            formData.append( 'field',fieldKey );
            formData.append( 'delete_object',button.data( 'object' ) );
            
            $.ajax(
                {
                    url: acf.get( 'ajaxurl' ),
                    type: 'post',
                    data: formData,
                    cache: false,
                    processData: false,
                    contentType: false,
                    success: function(response){
                        if (response.success) {
                            if ( response.data ) {
                                if ( response.data?.redirect ) {
                                    let url = response.data.redirect.replace(/&amp;/g, "&");
                                    window.location = decodeURIComponent(url);
                                }                                
                            }
                        } else {
                            console.log( response );
                        }
                    }
                }
            );
        }
    });
})(jQuery);