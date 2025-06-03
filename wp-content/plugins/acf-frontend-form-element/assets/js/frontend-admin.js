(function($){
	$.extend(
		{
			bytesToMegaBytes(bytes) { 
				return bytes / (1024*1024); 
			},
			
		}
	);


	$( window ).scroll(
		function() {
			var $loadMores = $( '.load-more-results' );
			var $scroll    = $( this );

			$.each(
				$loadMores,
				function(){
					var $loadMore = $( this );
					var hT        = $loadMore.offset().top,
					hH            = $loadMore.outerHeight(),
					wH            = $( window ).height(),
					wS            = $scroll.scrollTop();
					if (wS > (hT + hH - wH) && (hT > wS) && (wS + wH > hT + hH)) {
						if ( $loadMore.hasClass( 'loading' ) ) {
							return;
						}
						$loadMore.addClass( 'loading' ).find( 'span' ).removeClass( 'acf-hidden' );

						var nextPage = parseInt( $loadMore.attr( 'data-page' ) ) + 1;
						var items    = parseInt( $loadMore.attr( 'data-count' ) );
						var total    = parseInt( $loadMore.attr( 'data-total' ) );
						var ajaxData = {
							action:		'frontend_admin/forms/get_submissions',
							item_count: items,
							current_page: nextPage,
							form_id:	$loadMore.data( 'form' ),
							load_more: 1,
						};
						// get HTML
						$.ajax(
							{
								url: acf.get( 'ajaxurl' ),
								data: acf.prepareForAjax( ajaxData ),
								type: 'post',
								dataType: 'html',
								cache: false,
								success: function(response){
									$loadMore.before( response );
									$loadMore.attr( 'data-page',nextPage ).attr( 'data-count',$loadMore.siblings( '.fea-list-item' ).length );
									$loadMore.removeClass( 'loading' ).find( 'span' ).addClass( 'acf-hidden' );

									if ( nextPage == total ) {
										$loadMore.remove();
									}

								}
							}
						);
					}
				}
			);

		}
	);

	feaUpdateFileMeta = function( id, url ) {
		$.ajax({
			url: acf.get('ajaxurl'),
			data: {
				action: 'acf/fields/upload_file/update_meta',
				attach_id: id,
				url: url,
				nonce: acf.data.nonce
			},
			type: 'post'
		});
	}

	var Popup = acf.models.TooltipConfirm.extend(
		{
			events: {
				'click [data-block]': 'onConfirm',
				'click [data-event="cancel"]': 'onCancel'
			},
			render: function () {
				// set HTML
				this.html( this.get( 'html' ) ); // add class

				this.$el.addClass( 'fea-edit-popup' );
			}
		}
	);
	$( 'body' ).on(
		'click',
		'.acf-field.has-prev-value .fea-view-changes',
		function(e){
			var $el = $( this ).closest('.acf-field');

			$el.children('.acf-input').hide();
			$el.children('.fea-prev-value').show();
			$el.children('.fea-edit-changes').show();
			$(this).hide();

		}
	);
	$( 'body' ).on(
		'click',
		'.acf-field.has-prev-value .fea-edit-changes',
		function(e){
			var $el = $( this ).closest('.acf-field');

			$el.children('.acf-input').show();
			$el.children('.fea-prev-value').hide();
			$el.children('.fea-view-changes').show();
			$(this).hide();

		}
	);

	$( 'body' ).on(
		'click',
		'.fea-inline-edit',
		function(e){
			var $el = $( this ).closest('.fea-display-field');
			acf.showModal( $el ); // ajax
			currentModal.attr( 'data-source', $el.data( 'source' ) );
			currentModal.attr( 'data-key', $el.data( 'field' ) );
			currentModal.find( '.fea-close' ).remove();

			$( document ).on(
				'click',
				currentModal,
				function(e){
					if (e.target == this) {
						$( this ).hide();
					}
				}
			);

			var form = currentModal.find( 'form' );

			if ( form.length == 0 ) {
				const field = $el.children( '.acf-field' );
				field.removeClass( 'frontend-admin-hidden' );
				const html = `
					<form class="fea-inline-form">
						<button type="button" class="fea-inline-save button">${acf.__('Save')}</button>
						<button type="button" class="fea-inline-cancel button">${acf.__('Cancel')}</button>
					</form>
				`;
				acf.showModalContent(html);
				form = currentModal.find( 'form' );
				form.prepend( field );
				
				
			}

		}
	);
	$( 'body' ).on(
		'submit',
		'.fea-inline-form',
		function(e){
			e.preventDefault();
		}
	);

	$( 'body' ).on(
		'click',
		'.fea-inline-save',
		function(e){
			if (typeof acf.data == 'undefined') {
				return;
			}

			e.preventDefault();

			var $el = $( this );

			var spinner = $el.siblings( '.fea-loader' );
			if ( spinner.length > 0 ) {
				spinner.show();
			} else {
				spinner = $( '<span class="fea-loader"></span>' );
				$el.after( spinner );
			}

			var $form = $el.closest( '.fea-inline-form' );

			var formData = new FormData( $form[0] );

			formData.append( 'action', 'frontend_admin/forms/update_field' );
			formData.append( 'nonce', acf.data.nonce );
			acf.lockForm( $form );
			$form.addClass( 'lock-form' );

			$.ajax(
				{
					url: acf.get( 'ajaxurl' ),
					type: 'post',
					data: acf.prepareForAjax( formData ),
					cache: false,
					processData: false,
					contentType: false,
					success: function(response){
						$form.removeClass( 'lock-form' );
						$form.find('button').removeAttr('disabled');
						$form.find('button').removeClass( 'disabled' );
						acf.unlockForm( $form );
						if ( response.success ) {
							if ( response.data.errors ) {
								response.data.errors.map( acf.showErrors, $form );
							} else {
								if ( response.data.updates ) {
									$el.parents( '.fea-modal' ).hide();
									var updates = response.data.updates;
									updates.forEach(
										function (update) {
											var modal  = $el.parents( '.fea-modal' );
											var fields = $( '.fea-display-field[data-source=' + modal.attr( 'data-source' ) + '][data-field=' + modal.attr( 'data-key' ) + ']' );
											if (fields.length > 0) {
												fields.find( '.fea-value' ).html( update.html );
											}
										}
									);
								}
							}
							spinner.hide();
						}
					},
				}
			);
		}
	);
	$( 'body' ).on(
		'click',
		'.fea-inline-cancel',
		function(e){
			var $el    = $( this );
			var modal  = $el.parents( '.fea-modal' );
			var fields = $( '.fea-display-field[data-source=' + modal.attr( 'data-source' ) + '][data-field=' + modal.attr( 'data-key' ) + ']' );
			modal.hide();
		}
	);

	$( 'body' ).on(
		'click',
		'a[data-name=remove]',
		function(e){
			if ( typeof imagePreview != undefined ) {
				$( this ).parents( '.show-if-value' ).removeClass( 'show' ).siblings( '.hide-if-value' ).removeClass( 'frontend-admin-hidden' ).find( 'input.image-preview' ).val( '' );
			}
		}
	);

	$( 'body' ).on(
		'click',
		'.fea-new-form-window',
		function(e){
			e.preventDefault();
			var clicked = $( this );
			acf.getForm( clicked, 'admin_form' );
		}
	);

	acf.addFilter(
		'relationship_ajax_data',
		function(ajaxData, $el){
			if ( $el.$control().data( 'product_id' ) != '' ) {
				ajaxData.product_id = $el.$control().data( 'product_id' );
			}
			return ajaxData;
		}
	);

	$( document ).on(
		'elementor/popup/show',
		(event, id, instance) => {
        acf.doAction( 'append',$( '#elementor-popup-modal-' + id ) )
		}
	);
	$( "body" ).on(
		'click',
		'span.close-msg',
		function(a){
			$( this ).parents( '.frontend-admin-message' ).remove();
		}
	);

	$( "body" ).on(
		'input click',
		function(a){
			$( '.acf-success-message' ).remove();
		}
	);

	$( 'body' ).on(
		'mouseenter',
		'.choices a.edit-rel-post',
		function(event){
			var item = $( this ).parents( '.acf-rel-item' );
			if ( ! item.hasClass( 'disabled' ) ) {
				item.addClass( 'disabled temporary' );
			}
		}
	);
	$( 'body' ).on(
		'mouseleave',
		'.choices a.edit-rel-post',
		function(event){
			var item = $( this ).parents( '.acf-rel-item' );
			if ( item.hasClass( 'temporary' ) ) {
				item.removeClass( 'disabled temporary' );
			}
		}
	);

	$( 'body' ).on(
		'click',
		'.render-form',
		function(e){
			e.preventDefault();
			var clicked = $( this );
			acf.getForm( clicked );
		}
	);

	$( '.post-slug-field input' ).on(
		'input keyup',
		function() {
			var c = this.selectionStart,
			r     = /[`~!@#$%^&*()|+=?;:..’“'"<>,€£¥•،٫؟»«\s\{\}\[\]\\\/] + /gi,
			v     = $( this ).val();
			$( this ).val( v.replace( r,'' ).toLowerCase() );
			this.setSelectionRange( c, c );
		}
	);

	$( 'body' ).on(
		'click',
		'button.edit-password',
		function(){
			$( this ).addClass( 'acf-hidden' ).parents( '.acf-field-user-password' ).removeClass( 'edit_password' ).addClass( 'editing_password' ).siblings( '.acf-field-user-password-confirm' ).removeClass( 'acf-hidden' );
			$( this ).after( '<input type="hidden" name="edit_user_password" value="1"/>' );
			$( this ).siblings( '.pass-strength-result' ).removeClass( 'acf-hidden' )
		}
	);

	$( 'body' ).on(
		'click',
		'button.cancel-edit',
		function(){
			$( this ).siblings( 'button.edit-password' ).removeClass( 'acf-hidden' ).parents( '.acf-field-user-password' ).addClass( 'edit_password' ).removeClass( 'editing_password' ).siblings( '.acf-field-user-password-confirm' ).addClass( 'acf-hidden' );
			$( this ).parents( 'acf-input-wrap' ).siblings( 'acf-notice' );
			$( this ).siblings( 'input[name=edit_user_password]' ).remove();
			$( this ).siblings( '.pass-strength-result' ).addClass( 'acf-hidden' );
		}
	);

	$(
		function(){
			var forceEdit = $( '.acf-field-user-password-confirm' ).siblings( '.acf-field-user-password' ).hasClass( 'edit_password' );

			if ( ! forceEdit ) {
				$( '.acf-field-user-password-confirm' ).removeClass( 'acf-hidden' );
			}
		}
	);

	var modalLevel = 0;
	var narrowfy   = 0;
	var $controls  = [];

	acf.showModal = function( $el, $width, $location ){
		$location = $location || $( 'body' );
		$width    = $width || 600;
		var $key;
		if ( $el.data( 'modal_id' ) ) {
			$key = $el.data( 'modal_id' );
		} else {
			$key = acf.uniqid() + '-' + modalLevel;
		}
		var margin   = 9 + modalLevel;
		currentModal = $( '#modal_' + $key );
		if (currentModal.length) {
			currentModal.show();
			return false;
		} else {
			currentModal = $( '<div id="modal_' + $key + '" class="fea-modal edit-modal" data-clear="1"><div class="fea-modal-content" style="margin:' + margin + '% auto;width:' + parseInt( $width ) + 'px"><div class="fea-modal-inner"><span class="acf-icon -cancel fea-close"></span><div class="content-container"><div class="loading"><span class="fea-loader"></span></div></div></div></div></div>' );
			$location.append( currentModal );
			currentModal.show();
			$el.attr( 'data-modal_id',$key );
			return true;
		}
	}

	$( 'body' ).on(
		'click',
		'.fea-modal .fea-close',
		function(e){
			var modal = $( this ).closest( '.fea-modal' );
			if (modal.data( 'clear' ) == 1) {
				modal.hide();
				modalLevel--;
				narrowfy -= 20;
			}
		}
	);

	acf.validateFrontendForm = function (args) {
		return acf.getFrontendValidator( args.form ).validate( args );
	};

	acf.getFrontendValidator = function ($el) {
		// instantiate
		var validator = $el.data( 'acf' );

		if ( ! validator) {
			validator = new FrontendValidator( $el );
		} // return

		return validator;
	};

	acf.showErrors = function (error) {
		// get input
		var $input = this.find( '[name="' + error.input + '"]' ).first(); // if $_POST value was an array, this $input may not exist

		if ( ! $input.length) {
			$input = this.find( '[name^="' + error.input + '"]' ).first();
		} // bail early if input doesn't exist

		if ( ! $input.length) {
			return;
		} // increase


		//check if error message should be displayed or not
		if ( $input.closest('.acf-field').hasClass( 'fea-no-error' ) ) {
			return;
		}

		var field = acf.getClosestField( $input );  // show error

		field.showError( error.message ); // set $scrollTo

		return field.$el;
	}

	  /**
	   *  Frontend Validator
	   *
	   *  The model for validating frontend forms
	   *
	   *  @date	4/9/18
	   *  @since	5.7.5
	   *
	   *  @param	void
	   *  @return	void
	   */
	var FrontendValidator = acf.Model.extend(
		{
				/** @var string The model identifier. */
			id: 'FrontendValidator',

				/** @var object The model data. */
			data: {
				/** @var array The form errors. */
				errors: [],

				/** @var object The form notice. */
				notice: null,

				/** @var string The form status. loading, invalid, valid */
				status: ''
			},

				/** @var object The model events. */
			events: {
				'changed:status': 'onChangeStatus'
			},

				/**
				 *  addErrors
				 *
				 *  Adds errors to the form.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	array errors An array of errors.
				 *  @return	void
				 */
			addErrors: function (errors) {
				errors.map( this.addError, this );
			},

				/**
				 *  addError
				 *
				 *  Adds and error to the form.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	object error An error object containing input and message.
				 *  @return	void
				 */
			addError: function (error) {
				this.data.errors.push( error );
			},

				/**
				 *  hasErrors
				 *
				 *  Returns true if the form has errors.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	void
				 *  @return	bool
				 */
			hasErrors: function () {
				return this.data.errors.length;
			},

				/**
				 *  clearErrors
				 *
				 *  Removes any errors.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	void
				 *  @return	void
				 */
			clearErrors: function () {
				return this.data.errors = [];
			},

				/**
				 *  getErrors
				 *
				 *  Returns the forms errors.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	void
				 *  @return	array
				 */
			getErrors: function () {
				return this.data.errors;
			},

				/**
				 *  getFieldErrors
				 *
				 *  Returns the forms field errors.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	void
				 *  @return	array
				 */
			getFieldErrors: function () {
				// vars
				var errors = [];
				var inputs = []; // loop

				this.getErrors().map(
					function (error) {
						// bail early if global
						if ( ! error.input) {
							return; // update if exists
						}

						var i = inputs.indexOf( error.input );

						if (i > -1) {
							  errors[i] = error; // update
						} else {
							errors.push( error );
							inputs.push( error.input );
						}
					}
				); // return

				return errors;
			},

				/**
				 *  getGlobalErrors
				 *
				 *  Returns the forms global errors (errors without a specific input).
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	void
				 *  @return	array
				 */
			getGlobalErrors: function () {
				// return array of errors that contain no input
				return this.getErrors().filter(
					function (error) {
						return ! error.input;
					}
				);
			},

				/**
				 *  showErrors
				 *
				 *  Displays all errors for this form.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	void
				 *  @return	void
				 */
			showErrors: function () {
				// bail early if no errors
				if ( ! this.hasErrors()) {
					return;
				} // vars

				let fieldErrors  = this.getFieldErrors();
				let globalErrors = this.getGlobalErrors(); // vars

				let $scrollTo = false;

				let errors     = fieldErrors.map( acf.showErrors, this.$el ); // errorMessage
				let errorCount = errors.length;
				
				let customErrorMessage = this.$el.data( 'error-message' );

				let errorMessage = acf.__( 'Validation failed' );
				if (errorCount == 1) {
					errorMessage += '. ' + acf.__( '1 field requires attention' );
				} else if (errorCount > 1) {
					errorMessage += '. ' + acf.__( '%d fields require attention' ).replace( '%d', errorCount );
				} // notice

				if (customErrorMessage) {
					errorMessage = customErrorMessage;					
				} 

				if( globalErrors ){
					globalErrors.map(
						function (error) {
								errorMessage = '. ' + error.message;
						}
					);
				}
				

				

				if (this.has( 'notice' )) {
					this.get( 'notice' ).update(
						{
							type: 'error',
							text: errorMessage
							}
					);
				} else {
					let notice = acf.newNotice(
						{
							type: 'error',
							text: errorMessage,
							target: this.$el,
							location: 'after'
							}
					);
					this.set( 'notice', notice );
				} // if no $scrollTo, set to message

				if ( ! $scrollTo) {
					if( errors.length > 0 ){
						$scrollTo = errors[0];
					}else{
						$scrollTo = this.get( 'notice' ).$el;
					}
				} // timeout

				setTimeout(
					function () {
						$( 'html, body' ).animate(
							{
								scrollTop: $scrollTo.offset().top - $( window ).height() / 2
							},
							500
						);
					},
					10
				);
			},

				/**
				 *  onChangeStatus
				 *
				 *  Update the form class when changing the 'status' data
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	object e The event object.
				 *  @param	jQuery $el The form element.
				 *  @param	string value The new status.
				 *  @param	string prevValue The old status.
				 *  @return	void
				 */
			onChangeStatus: function (e, $el, value, prevValue) {
				this.$el.removeClass( 'is-' + prevValue ).addClass( 'is-' + value );
			},

				/**
				 *  validate
				 *
				 *  Vaildates the form via AJAX.
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	object args A list of settings to customize the validation process.
				 *  @return	bool True if the form is valid.
				 */
			validate: function (args) {
				// default args
				args = acf.parseArgs(
					args,
					{
						// trigger event
						event: false,
						// reset the form after submit
						reset: false,
						// whether to limit the validation to an inner div
						limit: false,
						// loading callback
						loading: function () {},
						// complete callback
						complete: function () {},
						// failure callback
						failure: function () {},
						// success callback
						success: function ($form) {
							  // $form.submit();
						}
						}
				); // return true if is valid - allows form submit

				if (this.get( 'status' ) == 'valid') {
					return true;
				} // return false if is currently validating - prevents form submit

				if (this.get( 'status' ) == 'validating') {
					return false;
				} // return true if no ACF fields exist (no need to validate)

				if ( ! this.$( '.acf-field' ).length) {
					return true;
				} // if event is provided, create a new success callback.

				if (args.event) {
					var event = $.Event( null, args.event );

					args.success = function () {
						acf.enableSubmit( $( event.target ) ).trigger( event );
					};
				} // action for 3rd party

				acf.doAction( 'validation_begin', this.$el ); // lock form

				acf.lockForm( this.$el ); // loading callback
				this.$el.addClass( 'lock-form' );

				args.loading( this.$el, this ); // update status

				this.set( 'status', 'validating' ); // success callback

				var onSuccess      = function (json) {
					// validate
					if ( ! acf.isAjaxSuccess( json )) {
						return;
					} // filter

						var data = acf.applyFilters( 'validation_complete', json.data, this.$el, this ); // add errors

						acf.unlockForm( this.$el );

						this.$el.find( '.acf-notice' ).remove();

					if ( ! data.valid) {
						// update status
						this.set( 'status', 'invalid' );
						if ( data.errors ) {
							  this.addErrors( data.errors );
							  acf.doAction( 'validation_failure', this.$el, this ); // display errors
							  this.showErrors(); // failure callback
						}

					} else {

						this.set( 'status', 'valid' ); // remove previous error message

						if (this.has( 'notice' )) {
									this.get( 'notice' ).update(
										{
											type: 'success',
											text: acf.__( 'Validation successful' ),
											timeout: 1000
											}
									);
						} // action

						acf.doAction( 'validation_success', this.$el, this );
						acf.doAction( 'submit', this.$el ); // success callback (submit form)

						// lock form

						acf.lockForm( this.$el ); // reset

						if (args.reset) {
							  this.reset();
						}
							this.clearErrors();
					}

				}; // complete
					var onComplete = function () {
						args.complete( this.$el,this );
					}


				if ( args.limit ) {
					var formData = acf.serialize( args.limit );

					//add _acf_form
					formData['_acf_form'] = this.$el.find( 'input[name=_acf_form]' ).val();
				} else {
					var formData = acf.serialize( this.$el );
				}
					formData['action'] = 'frontend_admin/validate_form_submit'; // ajax

					$.ajax(
						{
							url: acf.get( 'ajaxurl' ),
							data: acf.prepareForAjax( formData ),
							type: 'post',
							dataType: 'json',
							context: this,
							success: onSuccess,
							complete: onComplete,
						}
					); // return false to fail validation and allow AJAX

				return false;
			},

				/**
				 *  setup
				 *
				 *  Called during the constructor function to setup this instance
				 *
				 *  @date	4/9/18
				 *  @since	5.7.5
				 *
				 *  @param	jQuery $form The form element.
				 *  @return	void
				 */
			setup: function ($form) {
				// set $el
				this.$el = $form;
			},

				/**
				 *  reset
				 *
				 *  Rests the validation to be used again.
				 *
				 *  @date	6/9/18
				 *  @since	5.7.5
				 *
				 *  @param	void
				 *  @return	void
				 */
			reset: function () {
				// reset data
				this.set( 'errors', [] );
				this.set( 'notice', null );
				this.set( 'status', '' ); // unlock form

				acf.unlockForm( this.$el );
				this.$el.removeClass('lock-form');

			}
		}
	);

	acf.getForm = function( $clicked, $type ){
		$type = $type || $clicked.data( 'name' );

		if ( $type == 'plans' ) {
			var $dataType = 'plan';
			var $form_action = 'add_item';
			var $el = $clicked;
			if( $el.hasClass('edit-plan') ){
				$form_action = $el.parents('.fea-single-plan').data('plan');
			}
		}else{
			var inList = $clicked.closest( '.fea-list-item' );
			if ( inList.length > 0 ) {
				var $el = inList;
				if ($type == 'edit_item') {
					var $form_action = $el.data( 'id' );
				} else {
					var $form_action = 'add_item';
				}

				$dataType = $el.data( 'item' );

			} else {
				if ($type == 'admin_form') {
					var $el          = $clicked;
					var $form_action = 'admin_form';
				} else {
					var $el = $clicked.parents( '.acf-field' );
					if ($type == 'edit_item') {
						var $form_action = $clicked.parents( '.acf-rel-item' ).data( 'id' );
					} else {
						var $form_action = 'add_item';
					}
				}
				
				if ( $el.data( 'type' ) == 'related_terms' ) {
					$dataType = 'term';
				} else {
					$dataType = 'post';
				}
			}
		}
		var formWidth;
		if( $el.data('form-width') ){
			formWidth = $el.data('form-width');
		}else{
			formWidth = 600;
		}
		modalLevel++;
		var request = acf.showModal( $clicked, formWidth - narrowfy );
		$controls[modalLevel] = $clicked.parents( '.acf-field' );
		narrowfy += 20;

		if ( request ) {

			var ajaxData = {
				action:		'frontend_admin/forms/add_form',
				field_key:	$el.data( 'key' ),
				data_type: $dataType,
				parent_form: $el.parents( 'form' ).attr( 'id' ),
				form_action: $form_action,
			};

			if ($el.data( 'form' )) {
				ajaxData.form = $el.data( 'form' );
			}
			// get HTML
			$.ajax(
				{
					url: acf.get( 'ajaxurl' ),
					data: acf.prepareForAjax( ajaxData ),
					type: 'post',
					dataType: 'html',
					success: acf.showModalContent
				}
			);
		}
	}

	acf.showModalContent = function( html ){
		// update popup
		currentModal.find( '.content-container' ).html( html );
		acf.doAction( 'append',currentModal );

		var event = new CustomEvent('renderModalContent');
		// Dispatch/Trigger/Fire the event
		document.dispatchEvent(event);
	};

	
	$( 'body' ).on(
		'change',
		'.frontend-form input',
		function(e){
			//check if input was autofilled by browser
			if( $(this).is(":-webkit-autofill") ){
				window.onbeforeunload = null;
				$( window ).off( 'beforeunload' );
				return;
			}
			

			//get form
			var $form = $( this ).closest( 'form' );
			//get '_acf_changed' input
			var $changed = $form.find( 'input[name=_acf_changed]' );
			//set value to 1
			$changed.val( 1 );

			if( ! $form.data('allow_leave_page') ){
				window.onbeforeunload = function() {
					return true;
				};	
			}
		}
	);

	//when user presses enter on a text field within .frontend-form, it should go to the next field
	//if this os the last field, it should submit the form
	$( 'body' ).on(
		'keydown',
		'.frontend-form input',
		function(e){
			if ( e.keyCode == 13 ) {
				e.preventDefault();

				//any inputs or textareas or selects or buttons
				var $form = $( this ).closest( 'form' );
				var $inputs = $form.find( 'input,textarea,select,button' );
				var $index  = $inputs.index( this );

				var $nextInput = $inputs.eq( $index + 1 );

				//check if next input is a button. If so, submit the form

				if ( $nextInput.is( 'button' ) ) {
					$nextInput.click();
				} else {
					//focus on the next input
					$nextInput.focus();

					//if the next input is a select, open it
					if ( $nextInput.is( 'select' ) ) {
						$nextInput.select();
					}else{
						$nextInput[0].setSelectionRange( $nextInput.val().length, $nextInput.val().length );
						$nextInput.val( $nextInput.val() );
					}

				}
			}
		}
	);

	$( 'body' ).on(
		'click',
		'.frontend-form .fea-submit-button',
		function(e){
			e.preventDefault();

			var button = $( this );

			if ( button.hasClass( 'disabled' )) {
				return;
			}

			$form      = $( this ).closest( 'form' );
			//remove all 'clicked-on' classes
			$( '.fea-submit-button',$form ).removeClass( 'clicked-on' );

			button.addClass( 'disabled clicked-on' );

			var spinner = button.siblings( '.fea-loader' );
			if ( spinner.length > 0 ) {
				spinner.removeClass( 'acf-hidden' )
			} else {
				spinner = $( '<span class="fea-loader"></span>' );
				button.after( spinner );
			}

			var $message = button.data( 'message' );
			if ( $message ) {
				$form.find( 'input[name=_acf_message]' ).val( $message );
			}
			

			$form = acf.applyFilters( 'frontend_admin/submit_form',$form );
			if ( typeof $form == 'undefined' || ! $form ) {
				spinner.removeClass( 'acf-hidden' )
				$( this ).removeClass( 'disabled clicked-on' );
				return;
			}

			
			if( button.data( 'success' ) ){
				$form.find( 'input[name=_acf_message]' ).val( button.data( 'success' ) );
			}

			if ( button.data( 'state' ) == 'save' ) {
				//find _acf_status and change to save
				$form.find( 'input[name=_acf_status]' ).val('save');
				acf.submitFrontendForm( $form );
				return;
			} else {
				//find _acf_status and change to submit
				$form.find( 'input[name=_acf_status]' ).val('');
			}

			acf.disableFileInputs( $form );
			acf.lockForm( $form );
			$form.addClass( 'lock-form' );

			args = {
				form: $form,
				reset: false,
				complete: acf.submitFrontendForm,
			}
			acf.validateFrontendForm( args );
			

		}
	);

	acf.disableFileInputs = function( $form ){
		let $fileInputs = $( 'input[type="file"]:not([disabled])', $form )
		$fileInputs.each(
			function (i, input) {
				$( input ).attr( 'disabled', true );
				//add class 'temp-disabled' to be able to remove the disabled attribute later
				$( input ).addClass( 'temp-disabled' );
			}
		);

	}

	acf.enableFileInputs = function( $form ){
		let $fileInputs = $( 'input.temp-disabled', $form )
		$fileInputs.each(
			function (i, input) {
				$( input ).attr( 'disabled', false );
				$( input ).removeClass( 'temp-disabled' );
			}
		);
	}

	acf.submitFrontendForm = function( $form, $validator ){
		//if is string convert to jQuery object
		$form = typeof $form == 'string' ? $( $form ) : $form;

		$validator = $validator || false;
		if ( $validator ) {
			if ( $validator.hasErrors() ) {
				$( '.fea-loader',$form ).addClass( 'acf-hidden' );
				$( '.fea-submit-button',$form ).removeClass( 'disabled' );
				var formModal = $form.closest( 'div.edit-modal' );
				if ( typeof formModal != 'undefined' ) {
					$( formModal ).animate( {scrollTop:$form.offset().top - 50}, 'slow' );
				}
				$validator.reset();
				acf.enableFileInputs( $form );
				$form.removeClass('lock-form');
				acf.unlockForm( $form );
				return;
			}

		}
		
		var formData = new FormData( $form[0] );

		formData.append( 'action','frontend_admin/form_submit' );

		let currentButton = $form.find( '.clicked-on' );

		if ( currentButton.data( 'redirect' ) ) {
			formData.append( 'redirect',currentButton.data( 'redirect' ) );
		}

		formData = acf.applyFilters( 'frontend_admin/form_submit/form_data', formData, $form, currentButton );	


		$.ajax(
			{
				url: acf.get( 'ajaxurl' ),
				type: 'post',
				data: acf.prepareForAjax( formData ),
				cache: false,
				processData: false,
				contentType: false,
				success: function(response){
					acf.frontendFormSuccess( response, $form );
				},
				error: function(response){
					acf.frontendFormSuccess( response, $form );
				},
			}
		);
	}

	acf.frontendFormSuccess = function(response, $form = null){
		$form = $form || $( 'form[data-id=' + response?.data?.form_element + ']' );

		if (response.success && response.data?.form_element) {
			window.onbeforeunload = null;
			$( window ).off( 'beforeunload' );
			//acf.doAction( 'frontend_form_success', response );

			var data = response.data;
			if( ! data ) {
				return;
			}

			if ( data?.redirect ) {

				$( window ).off( 'beforeunload' );
				var url = data?.redirect.replace(/&amp;/g, "&");

				let currentButton = $form.find( 'button.clicked-on' );
				
				let reloadParams = currentButton.attr( 'data-reload' ); 
				if ( reloadParams ) {
					
					reloadParams.split(",").forEach(param => {
						let [key, value] = param.split("=");
						let params = new URLSearchParams(window.location.search);
						params.set(key, value);
						url = window.location.pathname + "?" + params.toString();
					});
					
				}

				window.location = decodeURIComponent(url);
				return;
			} else {

				if( ! $form.length ) {
					window.location.reload();
					return;
				}


				acf.unlockForm( $form );
				$form.removeClass('lock-form');

				if (data.modal) {
					// modal window ajax form
					modalLevel--;
					narrowfy -= 20;
					$form.parents( '.fea-modal' ).remove();
					$( '.fea-loader' ).addClass( 'acf-hidden' );
				} else {
					if (data.submission) {
						acf.updateSubmission( data );
						$form.parents( '.fea-modal' ).hide();
					}
					if (data.reload_form) {
						var $newForm = $( data.reload_form );
						$form.replaceWith( $newForm );
						acf.doAction( 'append',$newForm );

						let successMessage = successMessageHtml( data );
						if( successMessage ){
							$newForm.prepend( successMessage );	
						}					

						var formModal = $newForm.closest( 'div.edit-modal' );

						if ( formModal.length > 0 ) {
							formModal.scrollTop( 0 );
							if ( data.close_modal ) {
								formModal.hide();
							}
						} else {
							$( 'body, html' ).animate( {scrollTop:$newForm.offset().top - 50}, 'slow' );
						}
					}else{
						//prepend success message
						let successMessage = successMessageHtml( data );
						if( successMessage ) $form.prepend( successMessage );

						//find _acf_message and empty 
						$form.find( 'input[name=_acf_message]' ).val( '' );
					}
					
					if( data.objects ){
						$form.find( 'input[name=_acf_objects]' ).val( data.objects );
					}

					acf.doAction( 'frontend_form_success', data, $form );

				}
			}
		} else {
			$form.find( '.acf-notice' ).remove();
			let message = response?.data?.message || acf.__( 'An error occurred. Please try again later.' );
			$form.append( '<div class="frontend-admin-message"><div class="acf-notice -error acf-error-message"><p class="error-msg">' + message + '</p><span class="frontend-admin-dismiss close-msg acf-notice-dismiss acf-icon -cancel small"></span></div></div>' );	
		}
		acf.unlockForm( $form );
		$form.removeClass('lock-form');

		window.onbeforeunload = null;
		$( window ).off( 'beforeunload' );
		var $validator = acf.getFrontendValidator( $form );
		$validator.reset();
		$( '.fea-loader' ).addClass( 'acf-hidden' );
		$( '.fea-submit-button' ).removeClass( 'disabled' );
	}

	acf.updateSubmission = function(data){
		var $submission = $( '.fea-list-item[data-id=' + data.submission + ']' );

		if( $submission ){
			let successMessage = successMessageHtml( data );

			$submission.find( '.item-title' ).html( data.submission_title );

			if( successMessage ) $submission.prepend( successMessage );
		}
	}

	function successMessageHtml(data){
		if( data.success_message ){
			return '<div class="frontend-admin-message"><div class="acf-notice -success acf-success-message"><p class="success-msg">' + data.success_message + '</p><span class="frontend-admin-dismiss close-msg acf-notice-dismiss acf-icon -cancel small"></span></div></div>';
		}
		return false;
	}

	acf.addAction(
		'frontend_form_success',
		function(response){
			if( ! response.data ) return;
			var postData = response.data.post_info;
			if( ! postData ) return;
			if (postData.action == 'edit') {
				$( '.acf-field div.values' ).find( 'span[data-id=' + postData.id + ']' ).html( postData.text + '<a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a>' );
				$( '.acf-field div.choices' ).find( 'span[data-id=' + postData.id + ']' ).html( postData.text );
			} else {
				var thisField = $controls[modalLevel];
				var fieldObject = acf.getField( thisField );
				if ( postData.field_type == 'relationship' ) {
					thisField.find( 'div.values ul' ).append( '<li><input type="hidden" name="' + thisField.find( 'div.selection' ).siblings( 'input' ).attr( 'name' ) + '[]" value="' + postData.id + '" /><span data-id="' + postData.id + '" class="acf-rel-item">' + postData.text + '<a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a></span></li>' );
	
					thisField.find( 'div.choices ul' ).prepend( '<li><span class="acf-rel-item disabled" data-id="' + postData.id + '">' + postData.text + '</span></li>' );
				}
				if ( postData.field_type == 'post_object' ) {
					var fieldObject = acf.getField( thisField );
					fieldObject.select2.addOption(
						{
							id:			postData.id,
							text:		postData.text,
						}
					);
					fieldObject.select2.selectOption( postData.id );
				}
	
			}
		}
	);


	var Field = acf.Field.extend(
		{

			type: 'upload_files',

			events: {
				'click .fea-uploads-add':			'onClickAdd',
				'click .fea-uploads-edit':			'onClickEdit',
				'click .fea-uploads-remove':		'onClickRemove',
				'click .fea-uploads-attachment:not([data-id="0"])': 'onClickSelect',
				'click .fea-uploads-close': 		'onClickClose',
				'change .fea-uploads-sort': 		'onChangeSort',
				'click .fea-uploads-update': 		'onUpdate',
				'mouseover': 						'onHover',
				'showField': 						'render',
				'input .images-preview': 			'filePreviews',
			},

			actions: {
				'resize':				'onResize'
			},

			$control: function(){
				return this.$( '.fea-uploads' );
			},
			$uploader: function (){
				return this.$control().data( 'uploader' );
			},
			$collection: function(){
				return this.$( '.fea-uploads-attachments' );
			},

			$attachments: function(){
				return this.$( '.fea-uploads-attachment:not(.not-valid)' );
			},

			$clone: function(){
				return this.$( '.image-preview-clone' );
			},

			$attachment: function( id ){
				return this.$( '.fea-uploads-attachment[data-id="' + id + '"]' );
			},

			$active: function(){
				return this.$( '.fea-uploads-attachment.active' );
			},

			$inValid: function(){
				return this.$( '.fea-uploads-attachment.not-valid' );
			},

			$main: function(){
				return this.$( '.fea-uploads-main' );
			},

			$side: function(){
				return this.$( '.fea-uploads-side' );
			},

			$sideData: function(){
				return this.$( '.fea-uploads-side-data' );
			},

			isFull: function(){
				var max   = parseInt( this.get( 'max' ) );
				var count = this.$attachments().length;
				return ( max && count >= max );
			},

			getValue: function(){

				// vars
				var val = [];

				// loop
				this.$attachments().each(
					function(){
						val.push( $( this ).data( 'id' ) );
					}
				);

				// return
				return val.length ? val : false;
			},

			addUnscopedEvents: function( self ){

				return;
			},

			reorderFiles: function(){
				var self = this;
				self.$attachments().each(
					function(index,file){
						var oldIndex = $( this ).data( 'index' );
						index++;

						let re      = new RegExp( `file - ${oldIndex}`, 'gi' );
						var newHtml = $( this ).html().replace( re,'file-' + index );

						$( this ).data( 'index',index );
						$( this ).html( newHtml );

						if ( $( file ).hasClass( 'active' ) ) {
							var side    = self.$side();
							var newHtml = side.html().replace( re,'file-' + index );
							side.html( newHtml );
						}

					}
				);
			},

			addSortable: function( self ){

				// add sortable
				this.$collection().sortable(
					{
						items: '.fea-uploads-attachment:not([data-id="0"])',
						forceHelperSize: true,
						forcePlaceholderSize: true,
						scroll: true,
						start: function (event, ui) {
							ui.placeholder.html( ui.item.html() );
							ui.placeholder.removeAttr( 'style' );
						},
						update: function(event, ui) {
							self.$input().trigger( 'change' );
						}
					}
				);

			},

			initialize: function(){

				this.files = [];
				// add unscoped events
				this.addUnscopedEvents( this );

				// render
				this.render();
			},

			render: function(){
				// vars
				var $sort = this.$( '.fea-uploads-sort' );
				var $add  = this.$( '.fea-uploads-add' );
				var count = this.$attachments().length;

				// disable add
				if ( this.isFull() ) {
					$add.addClass( 'disabled' );
				} else {
					$add.removeClass( 'disabled' );
				}

				// disable select
				if ( ! count ) {
					$sort.addClass( 'disabled' );
				} else {
					$sort.removeClass( 'disabled' );
				}

				// resize
				this.resize();
			},

			resize: function(){

				// vars
				var width   = this.$control().width();
				var target  = 150;
				var columns = Math.round( width / target );

				// max columns = 8
				columns = Math.min( columns, 8 );

				// update data
				this.$control().attr( 'data-columns', columns );
			},

			onResize: function(){
				this.resize();
			},

			openSidebar: function(){

				// add class
				this.$control().addClass( '-open' );

				// hide bulk actions
				// should be done with CSS
				// this.$main().find('.fea-uploads-sort').hide();

				// vars
				var width = this.$control().width() / 3;
				width     = parseInt( width );
				width     = Math.max( width, 350 );

				// animate
				this.$( '.fea-uploads-side-inner' ).css( { 'width' : width - 1 } );
				this.$side().animate( { 'width' : width - 1 }, 250 );
				this.$main().animate( { 'right' : width }, 250 );
			},

			closeSidebar: function(){
				var $sideData = this.$( '.fea-uploads-side-data' );

				// remove class
				this.$control().removeClass( '-open' );

				// clear selection
				this.$active().append( $sideData.find( '.file-meta-data' ) );
				this.$active().removeClass( 'active' );

				// disable sidebar
				acf.disable( this.$side() );

				// animate

				this.$main().animate( { right: 0 }, 250 );
				this.$side().animate(
					{ width: 0 },
					250,
					function(){
						$sideData.html( '' );
					}
				);
			},

			onClickAdd: function( e, $el ){
				//check the uploader type
				var uploader = this.get( 'uploader' );
				if ( 'basic' == uploader ) {

					this.onClickUpload( e, $el );
					return;
				}

				// validate
				if ( this.isFull() ) {
					this.showNotice(
						{
							text: acf.__( 'Maximum selection reached' ),
							type: 'warning'
						}
					);
					return;
				}

				// new frame
				var frame = acf.newMediaPopup(
					{
						mode:			'select',
						title:			acf.__( 'Add Image to Gallery' ),
						field:			this.get( 'key' ),
						multiple:		'add',
						library:		this.get( 'library' ),
						allowedTypes:	this.get( 'mime_types' ),
						selected:		this.val(),
						select:			$.proxy(
							function( attachment, i ) {
								this.appendAttachment( attachment, i );
							},
							this
						)
					}
				);
			},

	
			addAttachment: function( $el ){
				$el.appendTo( this.$collection() );
				$el.data(
					'index',
					acf.uniqid( 'file_' )
				);
			},
			filePreviews: function( e, $el ){
				var self = this;
				var control = this.$control();					
				control.removeClass( 'acf-hidden' );

				var numAttachments = this.$attachments().length;
				var maxNum         = this.$control().data( 'max' );
				const files        = e.currentTarget.files;
				Object.keys( files ).forEach(
					i => {
						if (maxNum > 0 && numAttachments >= maxNum) {
							return false;
						}
						const file    = files[i];
						// check acf.data.server_upload_size and see if it allows the file upload						
						var container = this.$clone().clone();
						let maxSize   = control.data( 'max_size' );

						var fileSize = $.bytesToMegaBytes( file.size );
						if ((maxSize && fileSize > maxSize ) 
							|| fileSize > acf.data.server_upload_size 
							|| fileSize > acf.data.server_post_size) 
						{						
							self.showNotice(
								{
									text: acf.__( 'File size exceeds the maximum allowed' ),
									type: 'warning'
								}
							);
							container.addClass( 'not-valid' ).attr( 'data-id',0 );
							container.find( '.actions' ).remove();
							container.find( '.uploads-progress' ).remove();
							return;
						}
						container.removeClass( 'acf-hidden image-preview-clone' ).addClass( 'fea-uploads-attachment' );
						self.addAttachment( container );
						
						const reader  = new FileReader();
						reader.onload = (e) => {
							
							if (file.type == 'application/pdf') {
								container.find( '.margin' ).append( '<span class="gi-file-name">' + file.name + '</span>' );
							}
							var img = container.find( 'img' );
							if (file.type != 'image/png' && file.type != 'image/jpg' && file.type != 'image/jpeg') {
								img.attr( 'src',img.data( 'default' ) );
								self.validateFile(file, container);
							} else {
								img.attr( 'src',reader.result );
								var valid = feaResizeFile( file, reader.result, self, container );
								if( !valid ){
									container.addClass( 'not-valid' ).attr( 'data-id',0 );
									//remove .actions and .upload-progress
									container.find( '.actions' ).remove();
									container.find( '.uploads-progress' ).remove();
								}
							}
						}
						numAttachments++;
						reader.readAsDataURL( file );
					}
				);
				self.$( '.images-preview' ).val( '' );
				if (numAttachments >= maxNum && maxNum > 0) {
					this.$( 'input.images-preview' ).prop( 'disabled',true );
				}
			},

			validateFile: function(file,container){
				

				var self    = this;
				var progPrc = container.find( '.uploads-progress .percent' );
				var progBar = container.find( '.uploads-progress .bar' );
				
				var fieldKey = this.get( 'key' );
				var fileData = new FormData();
				fileData.append( 'action','acf/fields/upload_file/add_attachment' );
				fileData.append( 'file',file );
				fileData.append( 'field_key',fieldKey );
				fileData.append( 'nonce',acf.data.nonce );


				$.ajax(
					{
						url: acf.get( 'ajaxurl' ),
						data: acf.prepareForAjax( fileData ),
						type: 'post',
						processData: false,
						contentType: false,
						cache: false,	
						xhr: function(){
							var xhr = $.ajaxSettings.xhr();
							xhr.upload.onprogress = function(e){
								if(e.lengthComputable){
									var percent = Math.round((e.loaded / e.total) * 100);
									if( percent < 100 ){
										progPrc.text(percent + '%');
										progBar.css('width', percent + '%');
									}
									
								}
							}
							return xhr;
						},					
						error: function(e){

							self.showNotice(
								{
									text: acf.__( 'Error Uploading Files' ),
									type: 'warning'
								}
							);
							container.find( '.uploads-progress' ).remove();
							container.addClass( 'not-valid' ).append( '<p class="errors">' + acf.__( 'Could not upload file' ) + '</p>' );
						}
					}
				).done(
					function(response){
						if (response.success) {
							var fileID = response.data.id;
							var fileUrl = response.data.url;
	
							//send another request to update the meta data
							feaUpdateFileMeta( fileID, fileUrl );
							var idInput = $( '<input>' ).attr(
								{
									type:"hidden",
									name:self.$input().attr( 'name' ) + "[" + container.data( 'index' ) + "]",
									value:fileID
								}
							);
							container.prepend( idInput ).removeClass( 'acf-uploading' );
							progPrc.text('100%');
							progBar.css('width', '100%');
							self.$input().trigger( 'change' );

							setTimeout(
								function(){
									container.find( '.uploads-progress' ).remove();
								},
								1000
							);
						} else {
							self.showNotice(
								{
									text: acf.__( 'Error Uploading Files' ),
									type: 'warning'
								}
							);

							container.find( '.uploads-progress' ).remove();
							container.addClass( 'not-valid' ).append( '<p class="errors">' + response.data + '</p>' ).removeClass( 'has-value' );
						}

					}
				);
			},

			onClickUpload: function( e, $el ){
				this.$( '.not-valid' ).remove();
				this.$( '.acf-notice' ).remove();

				// validate
				if ( this.isFull() ) {
					this.showNotice(
						{
							text: acf.__( 'Maximum selection reached: ' + this.$control().data( 'max' ) ),
							type: 'warning'
						}
					);
					return;
				}
				if (this.$inValid()) {
					this.$inValid().remove();
					this.$( 'input.images-preview' ).prop( 'disabled',false );
				}
				this.$( '.images-preview' ).click();
			},

			appendAttachment: function( attachment, i ){
				// vars
				attachment = this.validateAttachment( attachment );

				// bail early if is full
				if ( this.isFull() ) {
					return;
				}

				// bail early if already exists
				if ( this.$attachment( attachment.id ).length ) {
					return;
				}
				this.$control().removeClass('acf-hidden');

				// html
				var html  = [
				'<div class="fea-uploads-attachment" data-id="' + attachment.id + '">',
				'<input type="hidden" value="' + attachment.id + '" name="' + this.getInputName() + '[]">',
					'<div class="thumbnail">',
						'<img src="" alt="">',
					'</div>',
					'<div class="filename"></div>',
				'<div class="actions">',
					'<a href="#" class="acf-icon small -cancel dark fea-uploads-remove" data-id="' + attachment.id + '"></a>',
				'</div>',
				'</div>'].join( '' );
				var $html = $( html );

				// append
				this.$collection().append( $html );

				// move to beginning
				if ( this.get( 'insert' ) === 'prepend' ) {
					var $before = this.$attachments().eq( i );
					if ( $before.length ) {
						$before.before( $html );
					}
				}

				// render attachment
				this.renderAttachment( attachment );

				// render
				this.render();

				// trigger change
				this.$input().trigger( 'change' );
			},

			validateAttachment: function( attachment ){

				// defaults
				attachment = acf.parseArgs(
					attachment,
					{
						id: '',
						url: '',
						alt: '',
						title: '',
						filename: '',
						type: 'image'
					}
				);

				// WP attachment
				if ( attachment.attributes ) {
					attachment = attachment.attributes;

					// preview size
					var url = acf.isget( attachment, 'sizes', this.get( 'preview_size' ), 'url' );
					if ( url !== null ) {
						attachment.url = url;
					}
				}

				// return
				return attachment;
			},

			renderAttachment: function( attachment ){

				// vars
				attachment = this.validateAttachment( attachment );

				// vars
				var $el = this.$attachment( attachment.id );

				// Image type.
				if ( attachment.type == 'image' ) {

					// Remove filename.
					$el.find( '.filename' ).remove();

					// Other file type.
				} else {

					// Check for attachment featured image.
					var image = acf.isget( attachment, 'image', 'src' );
					if ( image !== null ) {
						attachment.url = image;
					}

					// Update filename text.
					$el.find( '.filename' ).text( attachment.filename );
				}

				// Default to mimetype icon.
				if ( ! attachment.url ) {
					attachment.url = acf.get( 'mimeTypeIcon' );
					$el.addClass( '-icon' );
				}

				// update els
				$el.find( 'img' ).attr(
					{
						src:	attachment.url,
						alt:	attachment.alt,
						title:	attachment.title
					}
				);

				// update val
				acf.val( $el.find( 'input' ), attachment.id );
			},

			editAttachment: function( id ){

				// new frame
				var frame = acf.newMediaPopup(
					{
						mode:		'edit',
						title:		acf.__( 'Edit Image' ),
						button:		acf.__( 'Update Image' ),
						attachment:	id,
						field:		this.get( 'key' ),
						select:		$.proxy(
							function( attachment, i ) {
								this.renderAttachment( attachment );
								// todo - render sidebar
							},
							this
						)
					}
				);
			},

			onClickEdit: function( e, $el ){
				var id = $el.data( 'id' );
				if ( id ) {
					this.editAttachment( id );
				}
			},

			removeAttachment: function( id ){

				// close sidebar (if open)
				this.closeSidebar();

				// remove attachment
				this.$attachment( id ).remove();

				// render
				this.render();

				// trigger change
				this.$input().trigger( 'change' );
			},

			onClickRemove: function( e, $el ){

				// prevent event from triggering click on attachment
				e.preventDefault();
				e.stopPropagation();

				// remove
				var id = $el.data( 'id' );
				if ( id ) {
					this.removeAttachment( id );
				} else {
					var container = $el.parents( '.fea-uploads-attachment' );
					container.remove();
				}
				var numAttachments = this.$attachments().length;
				var maxNum         = this.$control().data( 'max' );
				if (numAttachments < maxNum) {
					this.$( 'input.images-preview' ).prop( 'disabled',false );
				}
			},

			selectAttachment: function( $el, e ){
				// bail early if already active
				const clickEvent = this.get('click_event');
				const openLightBox = $el.attr('data-lightbox');

				if( openLightBox ){					
					return;
				}

				const download = $el.attr('data-download');
				if( download ){
					const url = $el.attr('data-href');
					var link = document.createElement("a");

					link.setAttribute('download', '');
					link.href = url;
					document.body.appendChild(link);
					link.click();
					link.remove();
					return;
				}

				//if data-open_in_lightbox, then open in lgithbox
				
				if ( $el.hasClass( 'active' ) ) {
					return;
				}

				var filemeta;
				if ( $el.find( '.file-meta-data' ).length > 0 ) {
					filemeta = $el.find( '.file-meta-data' );
				} else {
					filemeta    = this.$( '.file-meta-data.clone' ).clone();
					var newHtml = filemeta.html().replace( /{file-index}/g,$el.data( 'index' ) );
					filemeta.html( newHtml );
					filemeta.removeClass( 'clone' );
				}
				filemeta.find('.fea-file-meta').removeAttr( 'disabled' );

				var side     = this.$side();
				var sideData = this.$sideData();
				if ( this.$control().hasClass( '-open' ) ) {
					side.find( '.file-meta-data' ).hide().appendTo( this.$active() );
				}
				this.$side().find( ':focus' ).trigger( 'blur' );
				sideData.append( filemeta )
				filemeta.show();
				// clear selection
				this.$active().removeClass( 'active' );

				// add selection
				$el.addClass( 'active' );

				// open sidebar
				this.openSidebar();

				return;

			},

			onClickSelect: function( e, $el ){
				this.selectAttachment( $el );
			},

			onClickClose: function( e, $el ){
				this.$side().find( '.file-meta-data' ).hide().appendTo( this.$active() );
				this.closeSidebar();
			},

			onChangeSort: function( e, $el ){

				// Bail early if is disabled.
				if ( $el.hasClass( 'disabled' ) ) {
					return;
				}

				// Get sort val.
				var val = $el.val();
				if ( ! val ) {
					return;
				}

				// find ids
				var ids = [];
				this.$attachments().each(
					function(){
						ids.push( $( this ).data( 'id' ) );
					}
				);

				// step 1
				var step1 = this.proxy(
					function(){

						// vars
						var ajaxData = {
							action: 'acf/fields/gallery/get_sort_order',
							field_key: this.get( 'key' ),
							ids: ids,
							sort: val
						};

						// get results
						var xhr = $.ajax(
							{
								url:		acf.get( 'ajaxurl' ),
								dataType:	'json',
								type:		'post',
								cache:		false,
								data:		acf.prepareForAjax( ajaxData ),
								success:	step2
							}
						);
					}
				);

				// step 2
				var step2 = this.proxy(
					function( json ){

						// validate
						if ( ! acf.isAjaxSuccess( json ) ) {
							return;
						}

						// reverse order
						json.data.reverse();

						// loop
						json.data.map(
							function(id){
								this.$collection().prepend( this.$attachment( id ) );
							},
							this
						);
					}
				);

				// call step 1
				step1();
			},

			onUpdate: function( e, $el ){
				var metaFields = this.$side().find( '.file-meta-data' );
				metaFields.hide().appendTo( this.$active() );
				this.closeSidebar();
			},

			onHover: function(){

				// add sortable
				this.addSortable( this );

				// remove event
				this.off( 'mouseover' );
			}
		}
	);

	acf.registerFieldType( Field );

			/*
	 * Field: Recaptcha
	 */
	var Recaptcha = acf.Field.extend(
		{

			type: 'recaptcha',

			wait: 'load',

			actions: {
				'validation_failure': 'validationFailure'
			},

			$control: function() {
				return this.$( '.frontend-admin-recaptcha' );
			},

			$input: function() {
				return this.$( 'input[type="hidden"]' );
			},

			$selector: function() {
				return this.$control().find( '> div' );
			},

			selector: function() {
				return this.$selector()[0];
			},

			onLoad: function() {
				if (this.get( 'version' ) === 'v2') {
					this.renderV2( this );
				}

			},
			initialize: function() {
				if (this.get( 'version' ) === 'v3') {
					this.renderV3();
				}

			},

			renderV2: function(self) {
				if (this.recaptcha) {
					return;
				}
				if ( typeof grecaptcha == 'undefined' ) {
					return;
				}
				// selectors
				var selector = this.selector();
				var $input   = this.$input();

				// vars
				var sitekey = this.get( 'siteKey' );
				var theme   = this.get( 'theme' );
				var size    = this.get( 'size' );
				// request
				this.recaptcha = grecaptcha.render(
					selector,
					{
						'sitekey': sitekey,
						'theme': theme,
						'size': size,

						'callback': function(response) {
							acf.val( $input, response, true );
							self.removeError();
						},

						'error-callback': function() {
							acf.val( $input, '', true );
							self.showError( 'An error has occured' );
						},

						'expired-callback': function() {
							acf.val( $input, '', true );
							self.showError( 'reCaptcha has expired' );
						}
					}
				);

			},

			renderV3: function() {

				// vars
				var $input  = this.$input();
				var sitekey = this.get( 'siteKey' );

				// request
				grecaptcha.ready(
					function() {
						grecaptcha.execute(
							sitekey,
							{
								action: 'homepage'
							}
						).then(
							function(response) {

								acf.val( $input, response, true );

							}
						);
					}
				);

			},

			validationFailure: function($form) {

				if (this.get( 'version' ) === 'v2') {
					grecaptcha.reset( this.recaptcha );
				}

			}

		}
	);

	acf.registerFieldType( Recaptcha );

	// acf.registerConditionForFieldType('hasValue', 'recaptcha');
	// acf.registerConditionForFieldType('hasNoValue', 'recaptcha');

	var Field = acf.Field.extend(
		{

			type: 'custom_terms',

			select2: false,

			wait: 'load',

			events: {
				'removeField': 'onRemove',
				'duplicateField': 'onDuplicate'
			},

			$input: function(){
				return this.$( 'select' );
			},

			initialize: function(){

				// vars
				var $select = this.$input();

				// inherit data
				this.inherit( $select );

				// select2
				if ( this.get( 'ui' ) ) {

					// populate ajax_data (allowing custom attribute to already exist)
					var ajaxAction = this.get( 'ajax_action' );
					if ( ! ajaxAction ) {
						ajaxAction = 'acf/fields/' + this.get( 'type' ) + '/query';
					}

					// select2
					this.select2 = acf.newSelect2(
						$select,
						{
							field: this,
							ajax: this.get( 'ajax' ),
							multiple: this.get( 'multiple' ),
							placeholder: this.get( 'placeholder' ),
							allowNull: this.get( 'allow_null' ),
							ajaxAction: ajaxAction,
						}
					);
				}
			},

			onRemove: function(){
				if ( this.select2 ) {
					this.select2.destroy();
				}
			},

			onDuplicate: function( e, $el, $duplicate ){
				if ( this.select2 ) {
					$duplicate.find( '.select2-container' ).remove();
					$duplicate.find( 'select' ).removeClass( 'select2-hidden-accessible' );
				}
			}
		}
	);

	acf.registerFieldType( Field );

	/**
	 * Detecting vertical squash in loaded image.
	 * Fixes a bug which squash image vertically while drawing into canvas for some images.
	 * This is a bug in iOS6 devices. This function from https://github.com/stomita/ios-imagefile-megapixel
	 * 
	 */
	function detectVerticalSquash(img) {
		var iw = img.naturalWidth, ih = img.naturalHeight;
		var canvas = document.createElement('canvas');
		canvas.width = 1;
		canvas.height = ih;
		var ctx = canvas.getContext('2d');
		ctx.drawImage(img, 0, 0);
		var data = ctx.getImageData(0, 0, 1, ih).data;
		// search image edge pixel position in case it is squashed vertically.
		var sy = 0;
		var ey = ih;
		var py = ih;
		while (py > sy) {
			var alpha = data[(py - 1) * 4 + 3];
			if (alpha === 0) {
				ey = py;
			} else {
				sy = py;
			}
			py = (ey + sy) >> 1;
		}
		var ratio = (py / ih);
		return (ratio===0)?1:ratio;
	}

	/**
	 * A replacement for context.drawImage
	 * (args are for source and destination).
	 */
	function drawImageIOSFix(ctx, img, sx, sy, sw, sh) {
		var vertSquashRatio = detectVerticalSquash(img);
	// Works only if whole image is displayed:
	// ctx.drawImage(img, sx, sy, sw, sh, dx, dy, dw, dh / vertSquashRatio);
	// The following works correct also when only a part of the image is displayed:
		ctx.drawImage(img, sx * vertSquashRatio, sy * vertSquashRatio, 
						sw * vertSquashRatio, sh * vertSquashRatio );
	}

	feaResizeFile = function( file, result, field, container ){
		container     = container || '';
		let control   = field.$control();
		let resize 	  = control.data( 'resize' ) || false; 
		let maxWidth  = control.data( 'max_width' );
		let maxHeight = control.data( 'max_height' );
		let quality   = true;
	
		
		if ( !resize 
			|| ( file.type != 'image/png' && file.type != 'image/jpg' && file.type != 'image/jpeg' ) )
		{
			//check the result's height and width
			if( maxWidth && result.width > maxWidth ){
				field.showNotice(
					{
						text: acf.__( 'Image width is too wide' ),
						type: 'warning'
					}
				);
				return;
			}

			if( maxHeight && result.height > maxHeight ){
				field.showNotice(
					{
						text: acf.__( 'Image height is too tall' ),
						type: 'warning'
					}
				);
				return;
			}

			// Resize not required
			field.validateFile( file, container );
			return true;
		}

		let img       = document.createElement( 'img' );
		let canvas    = document.createElement( 'canvas' );
		img.onload = function () {
			

			//Phoung Luu
			const userAgent = navigator.userAgent;

						//check if the device is iOS and that MegaPixImage is available\
			if ( ( userAgent.match(/iPad/i) || userAgent.match(/iPhone/i) ) && 'undefined' != typeof MegaPixImage ){
			   let mpImg = new MegaPixImage(img);
			   window.setTimeout(function(){
				 	mpImg.render(canvas, { maxWidth: maxWidth, maxHeight: maxHeight, quality: 0.8});
					/*var result = canvas.toDataURL("image/jpeg", 0.8);
					document.getElementById("outputimg").src = result;  */
					canvas.toBlob(
							function (blob) {
								var resizedFile = new File( [blob], 'resized_' + file.name, {type: "image/jpeg"} );
								field.validateFile( resizedFile, container );
							},
							'image/jpeg',
							0.8
						);
			  	}, 3000);
			}else{
				const ratio  = Math.min( maxWidth / img.width, maxHeight / img.height );
				const width  = Math.round( img.width * ratio );
				const height = Math.round( img.height * ratio );

				canvas.width  = width;
				canvas.height = height;

				var ctx = canvas.getContext( '2d' );
				drawImageIOSFix( ctx, img, 0, 0, width, height );
				canvas.toBlob(
					function (blob) {
						// get new file name and strip malicious characters
						let newFileName = file.name.replace( /[^a-z0-9.]/gi, '_' );
						var resizedFile = new File( [blob], newFileName, file );
						field.validateFile( resizedFile, container );
					},
					'image/jpeg',
					quality
				);
			}
		}
		img.src    = result;
		return true;
	}

	// register existing conditions
	acf.registerConditionForFieldType( 'hasValue', 'upload_files' );
	acf.registerConditionForFieldType( 'hasNoValue', 'upload_files' );
	acf.registerConditionForFieldType( 'selectionLessThan', 'upload_files' );
	acf.registerConditionForFieldType( 'selectionGreaterThan', 'upload_files' );

	var Field = acf.models.RelationshipField.extend(
		{
			type: 'product_grouped',
		}
	);
	acf.registerFieldType( Field );
	var Field = acf.models.RelationshipField.extend(
		{
			type: 'product_cross_sells',
		}
	);
	acf.registerFieldType( Field );
	var Field = acf.models.RelationshipField.extend(
		{
			type: 'product_upsells',
		}
	);
	acf.registerFieldType( Field );

	var Field = acf.Field.extend(
		{
			type: 'upload_image',
			$control: function () {
				return this.$( '.acf-image-uploader' );
			},
			$uploader: function (){
				return this.$control().data( 'uploader' );
			},
			$img: function(){
				return this.$( '.image-wrap > img' );
			},
			$id: function () {
				return this.$( 'input[data-name="id"]' );
			},
			events: {
				'click a[data-name="add"]': 'onClickAdd',
				'click a[data-name="edit"]': 'onClickEdit',
				'click a[data-name="remove"]': 'onClickRemove',
				'click a[data-name="upload-file"]': 'onClickUploadButton',
				'input .image-preview': 'imagePreview',
				'click a[data-name="edit-preview"]': 'onClickEditPreview',
				'click button.close-edit': 'closeEdit',
				'click .update-meta': 'onChangeMeta',
			},
			initialize: function () {
				this.files = [];
				// add attribute to form
				if (this.get( 'uploader' ) === 'basic') {
					this.$el.closest( 'form' ).attr( 'enctype', 'multipart/form-data' );
				}
			},

			onClickUploadButton: function (e, $el) {
				this.$( 'input[type="file"]' ).trigger( 'click' );
			},
			validateAttachment: function (attachment) {
				// Use WP attachment attributes when available.
				if (attachment && attachment.attributes) {
					attachment = attachment.attributes;
				} // Apply defaults.

				attachment = acf.parseArgs(
					attachment,
					{
						id: 0,
						url: '',
						alt: '',
						title: '',
						caption: '',
						description: '',
						width: 0,
						height: 0
					}
				); // Override with "preview size".

				var size = acf.isget( attachment, 'sizes', this.get( 'preview_size' ) );

				if (size) {
					attachment.url    = size.url;
					attachment.width  = size.width;
					attachment.height = size.height;
				} // Return.

				return attachment;
			},
			render: function (attachment) {
				attachment = this.validateAttachment( attachment ); // Update DOM.

				var dest = this.get( 'destination' );

				if ( dest ) {
					var parent = this.$el.parents( 'form' );
					var row    = this.$el.parent( '.acf-row' );
					if ( typeof row != 'undefined' ) {
						parent = row;
					}

					parent.find( '[data-key="' + this.get( 'destination' ) + '"' ).find( '.acf-url' ).addClass( '-valid' ).find( 'input' ).val( attachment.url );
				} else {

					this.updatePreview( attachment );

					if (attachment.id) {
						this.val( attachment.id );
						this.trigger('change');
						this.$control().addClass( 'has-value' );
					} else {
						this.val( '' );
						this.$control().removeClass( 'has-value' );
						this.$( '.fea-file-meta' ).val( '' );
						this.$( '.edit-modal' ).find( 'img' ).remove();
					}
				}

			},
			updatePreview: function (file) {
				const { url, alt } = file;
			 
				this.$img()?.attr(
					{
						src: url,
						alt: alt
					}
				);
				var control = this.$control();
				if( control.data('preview_element') ){
					var previewElement = $('body').find(control.data('preview_element'));

					var $previewType = control.data('preview_type') || 'img';
					//if is img tag, add src attribute, other find img tag inside preview element
					if( 'img' == $previewType ){
						if( previewElement.is('img') ){
							previewElement.attr('src',url).attr('srcset',url);
						}else{
							previewElement.find('img').attr('src',url).attr('srcset',url);
							previewElement.find('source').attr('src',url).attr('srcset',url);
						}
					}else{
						previewElement.style.backgroundImage = 'url(' + attachment.url + ')';
					}
					
				}
			},
			// create a new repeater row and render value
			append: function (attachment, parent) {
				// create function to find next available field within parent
				var getNext = function (field, parent) {
					// find existing file fields within parent
					var fields = acf.getFields(
						{
							key: field.get( 'key' ),
							parent: parent.$el
						}
					); // find the first field with no value

					for (var i = 0; i < fields.length; i++) {
						if ( ! fields[i].val()) {
							return fields[i];
						}
					} // return

					return false;
				}; // find existing file fields within parent

				var field = getNext( this, parent ); // add new row if no available field

				if ( ! field) {
					parent.$( '.acf-button:last' ).trigger( 'click' );
					field = getNext( this, parent );
				} // render

				if (field) {
					field.render( attachment );
				}
			},
			selectAttachment: function () {
				// vars
				var parent   = this.parent();
				var multiple = parent && parent.get( 'type' ) === 'repeater'; // new frame

				var frame = acf.newMediaPopup(
					{
						mode: 'select',
						type: 'image',
						title: acf.__( 'Select Image' ),
						field: this.get( 'key' ),
						multiple: multiple,
						library: this.get( 'library' ),
						allowedTypes: this.get( 'mime_types' ),
						select: $.proxy(
							function (attachment, i) {
								if (i > 0) {
									this.append( attachment, parent );
								} else {
									this.render( attachment );
								}
							},
							this
						)
					}
				);
			},
			editAttachment: function () {
				// vars
				var val = this.val(); // bail early if no val

				if ( ! val) {
					return; // popup
				}

				var frame = acf.newMediaPopup(
					{
						mode: 'edit',
						title: acf.__( 'Edit Image' ),
						button: acf.__( 'Update Image' ),
						attachment: val,
						field: this.get( 'key' ),
						select: $.proxy(
							function (attachment, i) {
								this.render( attachment );
							},
							this
						)
					}
				);
			},
			removeAttachment: function () {
				this.render( false );
			},
			onClickAdd: function (e, $el) {
				var uploader = this.$uploader();
				if ('basic' === uploader) {
					this.$( 'input[type="file"]' ).trigger( 'click' );
				}else{
					this.selectAttachment();
				}
			},
			onClickEdit: function (e, $el) {
				this.editAttachment();
			},
		
			onChangeMeta: function(e,$el){				
				$el.closest( '.edit-modal' ).hide();
			},
			onClickEditPreview: function(e,$el){
				acf.showModal( $el,600,this.$el );
				var $fileData = this.$( '.file-meta-data' );
				if ( this.$( '.edit-modal' ).find( '.file-meta-data' ).length == '0' ) {
					this.$( '.edit-modal' ).find( '.content-container' ).html( $fileData );
				}
				if ( this.$( '.edit-modal' ).find( 'img' ).length == '0' ) {
					$fileData.prepend( this.$img().clone() ).show();
				}
				this.$( '.fea-file-meta' ).removeAttr( 'disabled' );

			},

			closeEdit: function(){
				this.$( '.edit-modal' ).hide();
			},
			onClickRemove: function(){
				this.files.pop();

				this.render( false );
			},

			getRelatedType: function () {
				// vars
				let fieldType = this.get( 'field_type' );
				return fieldType;
			},
			getRelatedPrototype: function () {
				return acf.getFieldType( this.getRelatedType() ).prototype;
			},
			imagePreview: function( e, $el ){
				
				this.removeError();
				
				let file    = e.target.files[0];

				// check acf.data.server_upload_size and see if it allows the file upload
				
				let reader  = new FileReader();
				let control = this.$control();
				let field   = this;
				control.find( 'p.errors' ).remove();
				
				let maxSize   = control.data( 'max_size' );

				var fileSize = $.bytesToMegaBytes( file.size );
				if ((maxSize && fileSize > maxSize ) 
					|| fileSize > acf.data.server_upload_size 
					|| fileSize > acf.data.server_post_size) 
				{		
					field.showNotice(
						{
							text: acf.__( 'File size is too large' ),
							type: 'warning'
						}
					);
					return;
				}

				control.addClass( 'has-value' );
				control.addClass( 'not-valid' ).find( '.uploads-progress' ).removeClass( 'frontend-admin-hidden' );

				reader.onload = function()
				{

					const attachment = {
						url: reader.result,
						alt: file.name,
						title: file.name,
					};
					field.updatePreview( attachment );

					field.$('.file-meta-data .fea-file-meta.title').val(file.name);
					field.$('.file-meta-data .fea-file-meta.alt').val(file.name);

					

					feaResizeFile( file, reader.result, field );
					field.$( '.image-preview' ).val( '' );
				}
				imagePreview  = true;
				reader.readAsDataURL( file );

			},

			validateFile: function(file){
				let self    = this;
				let control = this.$control();
				let form    = control.parents( 'form' );

				let $progressBar = control.find( '.uploads-progress' );
				$progressBar.removeClass( 'frontend-admin-hidden' );
				let progPrc = $progressBar.find( '.percent' );
				let progBar = $progressBar.find( '.bar' );
				
				let fieldKey = this.get( 'key' );
				let fileData = new FormData();
				fileData.append( 'action','acf/fields/upload_file/add_attachment' );
				fileData.append( 'file',file );
				fileData.append( 'field_key',fieldKey );
				fileData.append( 'nonce',acf.data.nonce );
				form.find( '.fea-submit-button' ).addClass( 'disabled' );
				control.find( '.acf-actions' ).hide();

				$.ajax({
					url: acf.get('ajaxurl'),
					data: acf.prepareForAjax(fileData),
					type: 'post',
					processData: false,
					contentType: false,
					cache: false,
					xhr: function(){
						var xhr = $.ajaxSettings.xhr();
						xhr.upload.onprogress = function(e){
							if(e.lengthComputable){
								var percent = Math.round((e.loaded / e.total) * 100);
								
								if( percent < 100 ){
									progPrc.text(percent + '%');
									progBar.css('width', percent + '%');
								}
							}
						}
						return xhr;
					}
				}).done(function(response){
					if (response.success) {
						var fileID = response.data.id;
						var fileUrl = response.data.url;

						//send another request to update the meta data
						feaUpdateFileMeta( fileID, fileUrl );

						acf.doAction( 'feaUploadFile', file, fileID, fileUrl, self.$el);

						self.$id().val(fileID);
						form.find('.fea-submit-button').removeClass('disabled');
						progPrc.text('100%');
						progBar.css('width', '100%');
						setTimeout(function() {
							control.find('.uploads-progress').addClass('frontend-admin-hidden');
							progPrc.text('0%');
							progBar.css('width', '0');
							control.find('.acf-actions').show();
						}, 1000);
						control.removeClass('not-valid');
					} else {
						self.$('.show-if-value').find('img').attr('src', '');
						progPrc.text('0%');
						progBar.css('width', '0');
						control.find('.acf-actions').hide();
						$progressBar.addClass('frontend-admin-hidden');
						control.removeClass('has-value');
						self.showNotice({
							text: response.data,
							type: 'warning'
						});
					}

				});
					
			},
		}
	);
	acf.registerFieldType( Field );

	var Field = acf.Field.extend(
		{
			type: 'text_editor',
			wait: 'load',
			events: {
				'mousedown .acf-editor-wrap.delay': 'onMousedown',
				unmountField: 'disableEditor',
				remountField: 'enableEditor',
				removeField: 'disableEditor'
			},
			$control: function () {
				return this.$( '.acf-editor-wrap' );
			},
			$input: function () {
				return this.$( 'textarea' );
			},
			getMode: function () {
				return this.$control().hasClass( 'tmce-active' ) ? 'visual' : 'text';
			},
			initialize: function () {
				// initializeEditor if no delay
				if ( ! this.$control().hasClass( 'delay' )) {
					this.initializeEditor();
				}
			},
			initializeEditor: function () {
				// vars
				var $wrap     = this.$control();
				var $textarea = this.$input();
				var args      = {
					tinymce: true,
					quicktags: true,
					toolbar: this.get( 'toolbar' ),
					mode: this.getMode(),
					field: this
				}; // generate new id

				var oldId = $textarea.attr( 'id' );
				var newId = acf.uniqueId( 'acf-editor-' ); // Backup textarea data.

				var inputData = $textarea.data();
				var inputVal  = $textarea.val(); // rename

				acf.rename(
					{
						target: $wrap,
						search: oldId,
						replace: newId,
						destructive: true
					}
				); // update id

				this.set( 'id', newId, true ); // apply data to new textarea (acf.rename creates a new textarea element due to destructive mode)
				// fixes bug where conditional logic "disabled" is lost during "screen_check"

				this.$input().data( inputData ).val( inputVal ); // initialize

				acf.tinymce.initialize( newId, args );
			},
			onMousedown: function (e) {
				// prevent default
				e.preventDefault(); // remove delay class

				var $wrap = this.$control();
				$wrap.removeClass( 'delay' );
				$wrap.find( '.acf-editor-toolbar' ).remove(); // initialize

				this.initializeEditor();
			},
			enableEditor: function () {
				if (this.getMode() == 'visual') {
					acf.tinymce.enable( this.get( 'id' ) );
				}
			},
			disableEditor: function () {
				acf.tinymce.destroy( this.get( 'id' ) );
			}
		}
	);
	acf.registerFieldType( Field );

	var Field = acf.models.UploadImageField.extend(
		{
			type: 'upload_file',
			$control: function () {
				return this.$( '.acf-file-uploader' );
			},
			$uploader: function (){
				return this.$control().data( 'uploader' );
			},
			$img: function(){
				return this.$( '.file-icon > img' );
			},
			$id: function () {
				return this.$( 'input[data-name="id"]' );
			},
			events: {
				'click a[data-name="add"]': 'onClickAdd',
				'click a[data-name="edit"]': 'onClickEdit',
				'click a[data-name="remove"]': 'onClickRemove',
				'click a[data-name="upload-file"]': 'onClickUploadButton',
				'click a[data-name="edit-preview"]': 'onClickEditPreview',
				'input .file-preview': 'filePreview',
				'click .update-meta': 'onChangeMeta',
			},

			onClickEditPreview: function(e,$el){
				acf.showModal( $el,600,this.$el );
				var $fileData = this.$( '.file-meta-data' );
				if ( this.$( '.edit-modal' ).find( '.file-meta-data' ).length == '0' ) {
					this.$( '.edit-modal' ).find( '.content-container' ).html( $fileData );
				}
				if ( this.$( '.edit-modal' ).find( 'img' ).length == '0' ) {
					$fileData.prepend( this.$img().clone() ).show();
				}
				this.$( '.file-meta-data' ).removeAttr( 'disabled' );
			},
			getRelatedType: function () {
				// vars
				var fieldType = this.get( 'field_type' );
				return fieldType;
			},
			getRelatedPrototype: function () {
				return acf.getFieldType( this.getRelatedType() ).prototype;
			},
			filePreview: function( e, $el ){
				var field   = this;
				field.removeError();
				var reader  = new FileReader();
				var control = this.$control();
				control.find( 'p.errors' ).remove();
				const file = e.target.files[0];

				let maxSize   = control.data( 'max_size' );

				var fileSize = $.bytesToMegaBytes( file.size );
				if ((maxSize && fileSize > maxSize ) 
					|| fileSize > acf.data.server_upload_size 
					|| fileSize > acf.data.server_post_size) 
				{		
					field.showNotice(
						{
							text: acf.__( 'File size is too large' ),
							type: 'warning'
						}
					);
					return;
				}

				control.addClass( 'has-value' );
				control.addClass( 'not-valid' );

				reader.onload = function()
				{
					
					control.addClass( 'has-value' );
					var img = field.$img();
					if( img ){
						if (file.type != 'image/png' && file.type != 'image/jpg' && file.type != 'image/jpeg') {
							img.attr( 'src',img.data( 'default' ) );
							field.validateFile(file);
						} else {
							img.attr( 'src',reader.result );
							feaResizeFile( file, reader.result, field );
						}
					}
					

					field.$( '.file-preview' ).val( '' );

					control.find( '[data-name=filename]' ).html( file.name ).attr( 'href','#' );
					if (file.size < 1000000) {
						var _size = Math.floor( file.size / 1000 ) + 'KB';
					} else {
						var _size = Math.floor( file.size / 1000000 ) + 'MB';
					}
					control.find( '[data-name=filesize]' ).html( _size );
				}
				imagePreview  = true;
				reader.readAsDataURL( file );
			},
			validateAttachment: function (attachment) {
				// defaults
				attachment = attachment || {}; // WP attachment
		  
				if (attachment.id !== undefined) {
				  attachment = attachment.attributes;
				} // args
		  
		  
				attachment = acf.parseArgs(attachment, {
				  url: '',
				  alt: '',
				  title: '',
				  filename: '',
				  filesizeHumanReadable: '',
				  icon: '/wp-includes/images/media/default.png'
				}); // return
		  
				return attachment;
			  },
			  render: function (attachment) {
				// vars
				attachment = this.validateAttachment(attachment); // update image
		  
				this.$('img').attr({
				  src: attachment.icon,
				  alt: attachment.alt,
				  title: attachment.title
				}); // update elements
		  
				this.$('[data-name="title"]').text(attachment.title);
				this.$('[data-name="filename"]').text(attachment.filename).attr('href', attachment.url);
				this.$('[data-name="filesize"]').text(attachment.filesizeHumanReadable); // vars
		  
				var val = attachment.id || ''; // update val
		  
				acf.val(this.$input(), val); // update class
				
				//this.$el.find('input[data-name=id]').trigger('change');
		  
				if (val) {
				  this.$control().addClass('has-value');
				} else {
				  this.$control().removeClass('has-value');
				}
			  },
			  selectAttachment: function () {
				// vars
				var parent = this.parent();
				var multiple = parent && parent.get('type') === 'repeater'; // new frame
		  
				var frame = acf.newMediaPopup({
				  mode: 'select',
				  title: acf.__('Select File'),
				  field: this.get('key'),
				  multiple: multiple,
				  library: this.get('library'),
				  allowedTypes: this.get('mime_types'),
				  select: $.proxy(function (attachment, i) {
					if (i > 0) {
					  this.append(attachment, parent);
					} else {
					  this.render(attachment);
					}
				  }, this)
				});
			  },
			  editAttachment: function () {
				// vars
				var val = this.val(); // bail early if no val
		  
				if (!val) {
				  return false;
				} // popup
		  
		  
				var frame = acf.newMediaPopup({
				  mode: 'edit',
				  title: acf.__('Edit File'),
				  button: acf.__('Update File'),
				  attachment: val,
				  field: this.get('key'),
				  select: $.proxy(function (attachment, i) {
					this.render(attachment);
				  }, this)
				});
			  }

		}
	);
	acf.registerFieldType( Field );

	var imageFields = ['post_content','product_description','text_editor','featured_image','main_image','site_logo', 'site_favicon', 'upload_image'];

	$.each(
		imageFields,
		function(index, value){
			if ( value != 'upload_image' ) {
				var Field = acf.models.UploadImageField.extend(
					{
						type: value,
					}
				);
				acf.registerFieldType( Field );
			}
			acf.registerConditionForFieldType( 'hasValue', value );
			acf.registerConditionForFieldType( 'hasNoValue', value );
		}
	);
})( jQuery );

(function($, undefined){

	var Field = acf.Field.extend(
		{

			type: 'related_terms',

			data: {
				'ftype': 'select'
			},

			select2: false,

			wait: 'load',

			events: {
				'click a[data-name="add"]': 'onClickAdd',
				'click input[type="radio"]': 'onClickRadio',
				'click input[type="checkbox"]': 'onClickCheckbox',
				'click .tax-btn': 'onClickButton',
			},

			$control: function(){
				return this.$( '.acf-related-terms-field' );
			},

			$input: function(){
				return this.getRelatedPrototype().$input.apply( this, arguments );
			},

			getRelatedType: function(){

				// vars
				var fieldType = this.get( 'ftype' );

				// normalize
				if ( fieldType == 'multi_select' ) {
					fieldType = 'select';
				}

				// return
				return fieldType;

			},

			getRelatedPrototype: function(){
				return acf.getFieldType( this.getRelatedType() ).prototype;
			},

			getValue: function(){
				return this.getRelatedPrototype().getValue.apply( this, arguments );
			},

			setValue: function(){
				return this.getRelatedPrototype().setValue.apply( this, arguments );
			},

			initialize: function(){

				// vars
				var $select = this.$input();

				// inherit data
				this.inherit( $select );

				// select2
				if ( this.get( 'ui' ) ) {

					// populate ajax_data (allowing custom attribute to already exist)
					ajaxAction = 'acf/fields/related_terms/query';

					// select2
					this.select2 = acf.newSelect2(
						$select,
						{
							field: this,
							ajax: this.get( 'ajax' ),
							multiple: this.get( 'multiple' ),
							placeholder: this.get( 'placeholder' ),
							allowNull: this.get( 'allow_null' ),
							ajaxAction: ajaxAction,
						}
					);
				}
			},

			onRemove: function(){
				if ( this.select2 ) {
					this.select2.destroy();
				}
			},

			onClickAdd: function( e, $el ){

				// vars
				var field    = this;
				var popup    = false;
				var $form    = false;
				var $name    = false;
				var $parent  = false;
				var $button  = false;
				var $message = false;
				var notice   = false;

				// step 1.
				var step1 = function(){

					// popup
					popup = acf.newPopup(
						{
							title: $el.attr( 'title' ),
							loading: true,
							width: '300px'
						}
					);

					// ajax
					var ajaxData = {
						action:		'acf/fields/related_terms/add_term',
						field_key:	field.get( 'key' ),
						taxonomy:	field.get( 'taxonomy' ),
					};

					// get HTML
					$.ajax(
						{
							url: acf.get( 'ajaxurl' ),
							data: acf.prepareForAjax( ajaxData ),
							type: 'post',
							dataType: 'html',
							success: step2
						}
					);
				};

				// step 2.
				var step2 = function( html ){

					// update popup
					popup.loading( false );
					popup.content( html );

					// vars
					$form   = popup.$( 'form' );
					$name   = popup.$( 'input[name="term_name"]' );
					$parent = popup.$( 'select[name="term_parent"]' );
					$button = popup.$( '.acf-submit-button' );

					// focus
					$name.focus();

					// submit form
					popup.on( 'submit', 'form', step3 );
				};

				// step 3.
				var step3 = function( e, $el ){

					// prevent
					e.preventDefault();
					e.stopImmediatePropagation();

					// basic validation
					if ( $name.val() === '' ) {
						$name.focus();
						return false;
					}

					// disable
					acf.startButtonLoading( $button );

					// ajax
					var ajaxData = {
						action: 		'acf/fields/related_terms/add_term',
						field_key:		field.get( 'key' ),
						taxonomy: 		field.get( 'taxonomy' ),
						term_name:		$name.val(),
						term_parent:	$parent.length ? $parent.val() : 0
					};

					$.ajax(
						{
							url: acf.get( 'ajaxurl' ),
							data: acf.prepareForAjax( ajaxData ),
							type: 'post',
							dataType: 'json',
							success: step4
						}
					);
				};

				// step 4.
				var step4 = function( json ){

					// enable
					acf.stopButtonLoading( $button );

					// remove prev notice
					if ( notice ) {
						notice.remove();
					}

					// success
					if ( acf.isAjaxSuccess( json ) ) {

						// clear name
						$name.val( '' );

						// update term lists
						step5( json.data );

						// notice
						notice = acf.newNotice(
							{
								type: 'success',
								text: acf.getAjaxMessage( json ),
								target: $form,
								timeout: 2000,
								dismiss: false
							}
						);

					} else {

						// notice
						notice = acf.newNotice(
							{
								type: 'error',
								text: acf.getAjaxError( json ),
								target: $form,
								timeout: 2000,
								dismiss: false
							}
						);
					}

					// focus
					$name.focus();
				};

				// step 5.
				var step5 = function( term ){

					// update parent dropdown
					var $option = $( '<option value="' + term.term_id + '">' + term.term_label + '</option>' );
					if ( term.term_parent ) {
						$parent.children( 'option[value="' + term.term_parent + '"]' ).after( $option );
					} else {
						$parent.append( $option );
					}

					// add this new term to all taxonomy field
					var fields = acf.getFields(
						{
							type: 'related_terms'
						}
					);

					fields.map(
						function( otherField ){
							if ( otherField.get( 'taxonomy' ) == field.get( 'taxonomy' ) ) {
								otherField.appendTerm( term );
							}
						}
					);

					// select
					field.selectTerm( term.term_id );
				};

				// run
				step1();
			},

			appendTerm: function( term ){

				if ( this.getRelatedType() == 'select' ) {
					this.appendTermSelect( term );
				} else {
					this.appendTermCheckbox( term );
				}
			},

			appendTermSelect: function( term ){

				this.select2.addOption(
					{
						id:			term.term_id,
						text:		term.term_label
					}
				);

			},

			appendTermCheckbox: function( term ){

				// vars
				var name = this.$( '[name]:first' ).attr( 'name' );
				var $ul  = this.$( 'ul:first' );

				// allow multiple selection
				if ( this.getRelatedType() == 'checkbox' ) {
					name += '[]';
				}

				// create new li
				var $li = $(
					[
					'<li data-id="' + term.term_id + '">',
					'<label>',
						'<input type="' + this.get( 'ftype' ) + '" value="' + term.term_id + '" name="' + name + '" /> ',
						'<span>' + term.term_name + '</span>',
					'</label>',
					'</li>'
					].join( '' )
				);

				// find parent
				if ( term.term_parent ) {

					// vars
					var $parent = $ul.find( 'li[data-id="' + term.term_parent + '"]' );

					// update vars
					$ul = $parent.children( 'ul' );

					// create ul
					if ( ! $ul.exists() ) {
						$ul = $( '<ul class="children acf-bl"></ul>' );
						$parent.append( $ul );
					}
				}

				// append
				$ul.append( $li );
			},

			selectTerm: function( id ){
				if ( this.getRelatedType() == 'select' ) {
					this.select2.selectOption( id );
				} else {
					var $input = this.$( 'input[value="' + id + '"]' );
					$input.prop( 'checked', true ).trigger( 'change' );
				}
			},

			onClickRadio: function( e, $el ){

				// vars
				var $label   = $el.parent( 'label' );
				var selected = $label.hasClass( 'selected' );


				// remove previous selected
				this.$( '.selected' ).removeClass( 'selected' );

				// add active class
				$label.addClass( 'selected' );

				// allow null
				if ( this.get( 'allow_null' ) && selected ) {
					$label.removeClass( 'selected' );
					$el.prop( 'checked', false ).trigger( 'change' );
				}
			},

			onClickCheckbox: function( e, $el ){		
				// vars
				var $label = $el.parent( 'label' );
				// toggle active class
				$label.toggleClass( 'selected' );
			},

			onClickButton: function( e, $el ){
				//find the taxonomy field
				var $label = $el.closest( 'label' );
				var $input = $label.find( 'input' );

				//trigger the click event
				$input.trigger( 'click' );
			}
		}
	);

	acf.registerFieldType( Field );
	acf.registerConditionForFieldType( 'hasValue', 'related_terms' );
	acf.registerConditionForFieldType( 'hasNoValue', 'related_terms' );
	acf.registerConditionForFieldType( 'equalTo', 'related_terms' );
	acf.registerConditionForFieldType( 'notEqualTo', 'related_terms' );
	acf.registerConditionForFieldType( 'patternMatch', 'related_terms' );
	acf.registerConditionForFieldType( 'contains', 'related_terms' );
	acf.registerConditionForFieldType( 'selectionLessThan', 'related_terms' );
	acf.registerConditionForFieldType( 'selectionGreaterThan', 'related_terms' );
	/* acf.registerConditionForFieldType('hasValue', 'post_status');
	acf.registerConditionForFieldType('hasNoValue', 'post_status');
	acf.registerConditionForFieldType('selectEqualTo', 'post_status');
	acf.registerConditionForFieldType('selectNotEqualTo', 'post_status'); */
})( jQuery );

acf.add_filter(
	'select2_ajax_data',
	function( data, args, $input, field, instance ){

		if (field != false) {
			$field_taxonomy = field.find( '.acf-related-terms-field' ).data( 'taxonomy' );
			data.taxonomy   = $field_taxonomy;
		}
		return data;

	}
);

(function($, undefined){

	var Field = acf.Field.extend(
		{

			type: 'display_name',

			select2: false,

			wait: 'load',

			events: {
				'removeField': 'onRemove',
				'duplicateField': 'onDuplicate'
			},

			$input: function(){
				return this.$( 'select' );
			},

			initialize: function(){

				// vars
				var $select = this.$input();

				// inherit data
				this.inherit( $select );

				// select2
				if ( this.get( 'ui' ) ) {

					// populate ajax_data (allowing custom attribute to already exist)
					var ajaxAction = this.get( 'ajax_action' );
					if ( ! ajaxAction ) {
						ajaxAction = 'acf/fields/' + this.get( 'type' ) + '/query';
					}

					// select2
					this.select2 = acf.newSelect2(
						$select,
						{
							field: this,
							ajax: this.get( 'ajax' ),
							multiple: this.get( 'multiple' ),
							placeholder: this.get( 'placeholder' ),
							allowNull: this.get( 'allow_null' ),
							ajaxAction: ajaxAction,
						}
					);
				}
			},

			onRemove: function(){
				if ( this.select2 ) {
					this.select2.destroy();
				}
			},

			onDuplicate: function( e, $el, $duplicate ){
				if ( this.select2 ) {
					$duplicate.find( '.select2-container' ).remove();
					$duplicate.find( 'select' ).removeClass( 'select2-hidden-accessible' );
				}
			}
		}
	);

	acf.registerFieldType( Field );

	var tfFields = ['allow_comments'];

	$.each(
		tfFields,
		function(index, value){
			var Field = acf.models.TrueFalseField.extend(
				{
					type: value,
				}
			);
			acf.registerFieldType( Field );
			acf.registerConditionForFieldType( 'equalTo', value );
			acf.registerConditionForFieldType( 'notEqualTo', value );
		}
	);
	// state
	var preference = new acf.Model(
		{

			name: 'this.collapsedRows',

			key: function( key, context ){

				// vars
				var count = this.get( key + context ) || 0;

				// update
				count++;
				this.set( key + context, count, true );

				// modify fieldKey
				if ( count > 1 ) {
					key += '-' + count;
				}

				// return
				return key;
			},

			load: function( key ){

				// vars
				var key  = this.key( key, 'load' );
				var data = acf.getPreference( this.name );

				// return
				if ( data && data[key] ) {
					return data[key]
				} else {
					return false;
				}
			},

			save: function( key, value ){

				// vars
				var key  = this.key( key, 'save' );
				var data = acf.getPreference( this.name ) || {};

				// delete
				if ( value === null ) {
					delete data[ key ];

					// append
				} else {
					data[ key ] = value;
				}

				// allow null
				if ( $.isEmptyObject( data ) ) {
					data = null;
				}

				// save
				acf.setPreference( this.name, data );
			}
		}
	);

	var Field = acf.Field.extend(
		{

			type: 'list_items',
			wait: '',

			events: {
				'click a[data-event="add-row"]': 		'onClickAdd',
				'click a[data-event="duplicate-row"]':	'onClickDuplicate',
				'click a[data-event="remove-row"]': 	'onClickRemove',
				'click [data-event="collapse-row"]': 	'onClickCollapse',
				'showField':							'onShow',
				'unloadField':							'onUnload',
				'mouseover': 							'onHover',
			},

			$control: function(){
				return this.$( '.acf-list-items:first' );
			},

			$table: function(){
				return this.$( 'table:first' );
			},

			$tbody: function(){
				return this.$( 'tbody:first' );
			},

			$rows: function(){
				return this.$( 'tbody:first > tr' ).not( '.acf-clone' );
			},

			$row: function( index ){
				return this.$( 'tbody:first > tr:eq(' + index + ')' );
			},

			$clone: function(){
				return this.$( 'tbody:first > tr.acf-clone' );
			},

			$actions: function(){
				return this.$( '.acf-actions:last' );
			},

			$button: function(){
				return this.$( '.acf-actions:last .button' );
			},

			getValue: function(){
				return this.$rows().length;
			},

			allowRemove: function(){
				var min = parseInt( this.get( 'min' ) );
				return ( ! min || min < this.val() );
			},

			allowAdd: function(){
				var max = parseInt( this.get( 'max' ) );
				return ( ! max || max > this.val() );
			},

			addSortable: function( self ){

				// bail early if max 1 row
				if ( this.get( 'max' ) == 1 ) {
					return;
				}

				// add sortable
				this.$tbody().sortable(
					{
						items: '> tr',
						handle: '> td.order',
						forceHelperSize: true,
						forcePlaceholderSize: true,
						scroll: true,
						stop: function(event, ui) {
							self.render();
						},
						update: function(event, ui) {
							self.$input().trigger( 'change' );
						}
					}
				);
			},

			addCollapsed: function(){

				// vars
				var indexes = preference.load( this.get( 'key' ) );

				// bail early if no collapsed
				if ( ! indexes ) {
					return false;
				}

				// loop
				this.$rows().each(
					function( i ){
						if ( indexes.indexOf( i ) > -1 ) {
							$( this ).addClass( '-collapsed' );
						}
					}
				);
			},

			addUnscopedEvents: function( self ){

				// invalidField
				this.on(
					'invalidField',
					'.acf-row',
					function(e){
						var $row = $( this );
						if ( self.isCollapsed( $row ) ) {
							self.expand( $row );
						}
					}
				);
			},

			initialize: function(){

				// add unscoped events
				this.addUnscopedEvents( this );

				// add collapsed
				// this.addCollapsed();

				// disable clone
				acf.disable( this.$clone(), this.cid );

				// render
				this.render();
			},

			render: function(){

				// update order number
				this.$rows().each(
					function( i ){
						$( this ).find( '> .order:not(.ids) > span' ).html( i + 1 );
					}
				);
				

				// Extract vars.
				var $controll = this.$control();
				var $button   = this.$button();

				// empty
				if ( this.val() == 0 ) {
					$controll.addClass( '-empty' );
				} else {
					$controll.removeClass( '-empty' );
				}

				// Reached max rows.
				if ( ! this.allowAdd() ) {
					$controll.addClass( '-max' );
					$button.addClass( 'disabled' );
				} else {
					$controll.removeClass( '-max' );
					$button.removeClass( 'disabled' );
				}

				// Reached min rows (not used).
				// if( !this.allowRemove() ) {
				// $controll.addClass('-min');
				// } else {
				// $controll.removeClass('-min');
				// }
			},

			validateAdd: function(){

				// return true if allowed
				if ( this.allowAdd() ) {
					return true;
				}

				// vars
				var max  = this.get( 'max' );
				var text = acf.__( 'Maximum rows reached ({max} rows)' );

				// replace
				text = text.replace( '{max}', max );

				// add notice
				this.showNotice(
					{
						text: text,
						type: 'warning'
					}
				);

				// return
				return false;
			},

			onClickAdd: function( e, $el ){

				// validate
				if ( ! this.validateAdd() ) {
					return false;
				}

				// add above row
				if ( $el.hasClass( 'acf-icon' ) ) {
					this.add(
						{
							before: $el.closest( '.acf-row' )
						}
					);

					// default
				} else {
					this.add();
				}
			},

			add: function( args ){

				// validate
				if ( ! this.allowAdd() ) {
					return false;
				}

				// defaults
				args = acf.parseArgs(
					args,
					{
						before: false
					}
				);

				// add row
				var $el = acf.duplicate(
					{
						target: this.$clone(),
						append: this.proxy(
							function( $el, $el2 ){

								// append
								if ( args.before ) {
									args.before.before( $el2 );
								} else {
									$el.before( $el2 );
								}

								// remove clone class
								$el2.removeClass( 'acf-clone' );

								// enable
								acf.enable( $el2, this.cid );

								// render
								this.render();

								$( 'html, body' ).animate(
									{
										scrollTop: $( $el2 ).offset().top - 75,
									}
								);
							}
						)
					}
				);

				// trigger change for validation errors
				this.$input().trigger( 'change' );

				// return
				return $el;
			},

			onClickDuplicate: function( e, $el ){

				// Validate with warning.
				if ( ! this.validateAdd() ) {
					return false;
				}

				// get layout and duplicate it.
				var $row = $el.closest( '.acf-row' );
				this.duplicateRow( $row );
			},

			duplicateRow: function( $row ){

				// Validate without warning.
				if ( ! this.allowAdd() ) {
					return false;
				}

				// Vars.
				var fieldKey = this.get( 'key' );

				// Duplicate row.
				var $el = acf.duplicate(
					{
						target: $row,

						// Provide a custom renaming callback to avoid renaming parent row attributes.
						rename: function( name, value, search, replace ){

							// Rename id attributes from "field_1-search" to "field_1-replace".
							if ( name === 'data-id' ) {
								return value.replace( fieldKey + '-' + search, fieldKey + '-' + replace );

								// Rename name and for attributes from "[field_1][search]" to "[field_1][replace]".
							} else {
								return value.replace( fieldKey + '][' + search, fieldKey + '][' + replace );
							}
						},
						before: function( $el ){
							acf.doAction( 'unmount', $el );
						},
						after: function( $el, $el2 ){
							acf.doAction( 'remount', $el );
						},
					}
				);

				// trigger change for validation errors
				this.$input().trigger( 'change' );

				// Update order numbers.
				this.render();

				// Focus on new row.
				acf.focusAttention( $el );

				// Return new layout.
				return $el;
			},

			validateRemove: function(){

				// return true if allowed
				if ( this.allowRemove() ) {
					return true;
				}

				// vars
				var min  = this.get( 'min' );
				var text = acf.__( 'Minimum rows reached ({min} rows)' );

				// replace
				text = text.replace( '{min}', min );

				// add notice
				this.showNotice(
					{
						text: text,
						type: 'warning'
					}
				);

				// return
				return false;
			},

			onClickRemove: function( e, $el ){
				var $row = $el.closest( '.acf-row' );

				// Bypass confirmation when holding down "shift" key.
				if ( e.shiftKey ) {
					return this.remove( $row );
				}

				// add class
				$row.addClass( '-hover' );

				// add tooltip
				var tooltip = acf.newTooltip(
					{
						confirmRemove: true,
						target: $el,
						context: this,
						confirm: function(){
							this.remove( $row );
						},
						cancel: function(){
							$row.removeClass( '-hover' );
						}
					}
				);
			},

			remove: function( $row ){

				// reference
				var self = this;

				// remove
				acf.remove(
					{
						target: $row,
						endHeight: 0,
						complete: function(){

							// trigger change to allow attachment save
							self.$input().trigger( 'change' );

							// render
							self.render();

							// sync collapsed order
							// self.sync();
						}
					}
				);
			},

			isCollapsed: function( $row ){
				return $row.hasClass( '-collapsed' );
			},

			collapse: function( $row ){
				$row.addClass( '-collapsed' );
				acf.doAction( 'hide', $row, 'collapse' );
			},

			expand: function( $row ){
				$row.removeClass( '-collapsed' );
				acf.doAction( 'show', $row, 'collapse' );
				$( 'html, body' ).animate(
					{
						scrollTop: $( $row ).closest( '.acf-row' ).offset().top - 75,
					}
				);
			},

			onClickCollapse: function( e, $el ){

				// vars
				var $row        = $el.closest( '.acf-row' );
				var isCollpased = this.isCollapsed( $row );

				// shift
				if ( e.shiftKey ) {
					$row = this.$rows();
				}

				// toggle
				if ( isCollpased ) {
					this.expand( $row );
				} else {
					this.collapse( $row );
				}
			},

			onShow: function( e, $el, context ){

				// get sub fields
				var fields = acf.getFields(
					{
						is: ':visible',
						parent: this.$el,
					}
				);

				// trigger action
				// - ignore context, no need to pass through 'conditional_logic'
				// - this is just for fields like google_map to render itself
				acf.doAction( 'show_fields', fields );
			},

			onUnload: function(){

				// vars
				var indexes = [];

				// loop
				this.$rows().each(
					function( i ){
						if ( $( this ).hasClass( '-collapsed' ) ) {
							indexes.push( i );
						}
					}
				);

				// allow null
				indexes = indexes.length ? indexes : null;

				// set
				if ( typeof preference != 'undefined' ) {
					preference.save( this.get( 'key' ), indexes );
				}
			},

			onHover: function(){

				// add sortable
				this.addSortable( this );

				// remove event
				this.off( 'mouseover' );
			}
		}
	);

	acf.registerFieldType( Field );
	acf.registerConditionForFieldType( 'hasValue', 'list_items' );
	acf.registerConditionForFieldType( 'hasNoValue', 'list_items' );
	acf.registerConditionForFieldType( 'lessThan', 'list_items' );
	acf.registerConditionForFieldType( 'greaterThan', 'list_items' );

})( jQuery );

(function($, undefined){
	var Field = acf.models.PostObjectField.extend(
		{
			type: 'post_to_edit',
			events: {
				'change .acf-input > select': 	'onChangePost',
			},
			getType: function(){
				return 'post';
			},
			onChangePost: function( e, $el ){
				// Bail early if is disabled.
				if ( $el.hasClass( 'disabled' ) ) {
					return;
				}

				if( ! $el.val() ){
					return;
				}

				let $form = $el.parents( '.frontend-form' );
				$form.addClass( 'disabled' );
				let field = this;

				$el.after( '<span class="fea-loader"></span>' );
				let formData = $form.find( 'input[name=_acf_form]' ).val();
				const url = new URL( window.location.href );

				const url_query = field.$el.data( 'url_query' ) || 'post_id';

				url.searchParams.set( url_query, $el.val() );
				console.log(url.href);

				let ajaxData = {
					action:		'frontend_admin/forms/change_form',
					item_id: 		$el.val(),
					type: this.getType(),
					form_data:	formData,
					current_url: url.href,
					field_key: this.get( 'key' ),
				};

				console.log(this.get('key'));
				

				// get HTML
				$.ajax(
					{
						url: acf.get( 'ajaxurl' ),
						data: acf.prepareForAjax( ajaxData ),
						type: 'post',
						dataType: 'json',
						cache: false,
						success: function(response){
							if (response.success && response.data.reload_form) {
								$form.removeClass( 'disabled' );
								field.$( '.fea-loader' ).remove();
								let newForm = $( response.data.reload_form );
								$form.replaceWith( newForm );
								acf.doAction( 'append',newForm );

								
								window.history.pushState(  { post_id: $el.val() }, '', url );
							}
						}
					}
				);

			},

		}
	);
	
	acf.registerFieldType( Field );
	var Field = acf.models.PostToEditField.extend(
		{
			type: 'product_to_edit',
			events: {
				'change .acf-input > select': 	'onChangePost',
			},
			getType: function(){
				return 'product';
			},

		}
	);
	acf.registerFieldType( Field );

	var Field = acf.models.UserField.extend(
		{
			type: 'user_to_edit',
			events: {
				'change .acf-input > select': 	'onChangeUser',
			},
			getType: function(){
				return 'user';
			},
			onChangeUser: function( e, $el ){
				// Bail early if is disabled.
				if ( $el.hasClass( 'disabled' ) ) {
					return;
				}

				if( ! $el.val() ){
					return;
				}

				let $form = $el.parents( '.frontend-form' );
				$form.addClass( 'disabled' );
				let field = this;

				$el.after( '<span class="fea-loader"></span>' );
				let formData = $form.find( 'input[name=_acf_form]' ).val();
				let url = $form.find( 'input[name=_acf_current_url]' ).val();

				let ajaxData = {
					action:		'frontend_admin/forms/change_form',
					item_id: 		$el.val(),
					type: this.getType(),
					form_data:	formData,
					current_url: url,
				};

				// get HTML
				$.ajax(
					{
						url: acf.get( 'ajaxurl' ),
						data: acf.prepareForAjax( ajaxData ),
						type: 'post',
						dataType: 'json',
						cache: false,
						success: function(response){
							if (response.success && response.data.reload_form) {
								$form.removeClass( 'disabled' );
								field.$( '.fea-loader' ).remove();
								let newForm = $( response.data.reload_form );
								$form.replaceWith( newForm );
								acf.doAction( 'append',newForm );

								const url = new URL( window.location.href );

								const url_query = field.$el.data( 'url_query' ) || 'user_id';

								url.searchParams.set( url_query, $el.val() );
								window.history.pushState(  { user_id: $el.val() }, '', url );
							}
						}
					}
				);

			},

		}
	);
	acf.registerFieldType( Field );


	var Field = acf.models.ImageField.extend(
		{

			type: 'url_upload',

			$control: function(){
				return this.$( '.acf-file-uploader' );
			},

			$input: function(){
				return this.$( 'input[type="hidden"]' );
			},

			validateAttachment: function( attachment ){

				// defaults
				attachment = attachment || {};

				// WP attachment
				if ( attachment.id !== undefined ) {
					attachment = attachment.attributes;
				}

				// args
				attachment = acf.parseArgs(
					attachment,
					{
						url: '',
						alt: '',
						title: '',
						filename: '',
						filesizeHumanReadable: '',
						icon: '/wp-includes/images/media/default.png'
					}
				);

				// return
				return attachment;
			},

			render: function( attachment ){

				// vars
				attachment = this.validateAttachment( attachment );
				var parent = this.$el.parents( 'form' );
				var row    = this.$el.parent( '.acf-row' );
				if ( typeof row != 'undefined' ) {
					parent = row;
				}

				parent.find( '[data-key="' + this.get( 'destination' ) + '"' ).find( '.acf-url' ).addClass( '-valid' ).find( 'input' ).val( attachment.url );
			},

			selectAttachment: function(){

				// vars
				var parent   = this.parent();
				var multiple = (parent && parent.get( 'type' ) === 'repeater');

				// new frame
				var frame = acf.newMediaPopup(
					{
						mode:			'select',
						title:			acf.__( 'Select File' ),
						field:			this.get( 'key' ),
						multiple:		multiple,
						library:		this.get( 'library' ),
						allowedTypes:	this.get( 'mime_types' ),
						select:			$.proxy(
							function( attachment, i ) {
								if ( i > 0 ) {
									this.append( attachment, parent );
								} else {
									this.render( attachment );
								}
							},
							this
						)
					}
				);
			},

			editAttachment: function(){

				// vars
				var val = this.val();

				// bail early if no val
				if ( ! val ) {
					return false;
				}

				// popup
				var frame = acf.newMediaPopup(
					{
						mode:		'edit',
						title:		acf.__( 'Edit File' ),
						button:		acf.__( 'Update File' ),
						attachment:	val,
						field:		this.get( 'key' ),
						select:		$.proxy(
							function( attachment, i ) {
								this.render( attachment );
							},
							this
						)
					}
				);
			}
		}
	);

	acf.registerFieldType( Field );

	var textFields = ['post_title','product_title','site_title','site_tagline','term_name','username', 'first_name','last_name','nickname'];
	var textLogic  = ['hasValue', 'hasNoValue', 'equalTo', 'notEqualTo', 'patternMatch', 'contains'];

	$.each(
		textFields,
		function(index, value){
			$.each(
				textLogic,
				function(ind, logic){
					acf.registerConditionForFieldType( logic, value );
				}
			);
		}
	);

	var Field = acf.models.SelectField.extend(
		{
			type: 'post_author'
		}
	);
	acf.registerFieldType( Field );
	var Field = acf.models.SelectField.extend(
		{
			type: 'product_author'
		}
	);
	acf.registerFieldType( Field );
	/* acf.addFilter(
		'select2_ajax_data',
		function (data, args, $input, field, select2) {

			if ( ! field) {
				return data;
			}

			const query_nonce = field.get( 'queryNonce' );

			if (query_nonce && query_nonce.length) {
				data.author_query_nonce = query_nonce;
			}

			return data;
		}
	);
 */
	acf.addFilter(
		'select2_ajax_data/name=mailchimp_lists',
		function (data, args, $input, field, select2) {

			if ( ! field) {
				return data;
			}

			var $el = field.$el;

			var $api = $el.siblings( '.acf-field[data-key=api_key]' ).find( 'input[type=text]' ).val();

			if ( $api ) {
				data.api_key = $api;
			}
			return data;
		}
	);
	acf.addFilter(
		'select2_ajax_data/name=fields_exclude',
		function (data, args, $input, field, select2) {

			if ( ! field) {
				return data;
			}

			var $el = field.$el;

			var $groupsSelect  = $el.siblings( '.acf-field[data-key=fields_select]' ).find( 'select.fields-and-groups' );
			var $fieldsExclude = $el.find( '.field-group-fields' );

			if ( $groupsSelect ) {
				var $selected = $groupsSelect.select2( 'val' );
				var groups    = [];
				if ($selected) {
					$.each(
						$selected,
						function(index,value){
							if (value.indexOf( "group" ) >= 0) {
								groups.push( value );
							}
						}
					);
				}
				if (groups.length < 1) {
					$fieldsExclude.empty().trigger( 'change' );
					return data;
				} else {
					data.groups = groups;
				}
			}
			return data;
		}
	);
		   /*
	 * Field: Select2
	 */
	new acf.Model(
		{

			filters: {
				'select2_args': 'select2Args',
				'select2_ajax_data': 'select2Ajax',
			},

			select2Args: function(options, $select, data, field, instance) {
				if( ! field ) return options;

				if (field.get( 'closeOnSelect' )) {
					options.closeOnSelect = false;
				};

				// Allow Custom tags
				if (field.get( 'allowCustom' )) {

					options.tags = true;

					options.createTag = function(params) {

						var term = $.trim( params.term );

						if (term === '') {
							return null;
						}

						var optionsMatch = false;

						this.$element.find( 'option' ).each(
							function() {

								if (this.value.toLowerCase() !== term.toLowerCase()) {
									return;
								}

								optionsMatch = true;
								return false;

							}
						);

						if (optionsMatch) {
							return null;
						}

						return {
							id: term,
							text: term
						};

					};

					options.insertTag = function(data, tag) {

						var found = false;

						$.each(
							data,
							function() {

								if ($.trim( tag.text ).toUpperCase() !== $.trim( this.text ).toUpperCase()) {
									return;
								}

								found = true;
								return false;

							}
						);

						if ( ! found) {
							data.unshift( tag );
						}

					};

				}

				options = acf.applyFilters( 'select2_args/type=' + field.get( 'type' ), options, $select, data, field, instance );
				options = acf.applyFilters( 'select2_args/name=' + field.get( 'name' ), options, $select, data, field, instance );
				options = acf.applyFilters( 'select2_args/key=' + field.get( 'key' ), options, $select, data, field, instance );

				return options;

			},

			select2Ajax: function(ajaxData, data, $el, field, instance) {
				ajaxData = acf.applyFilters( 'select2_ajax_data/type=' + field.get( 'type' ), ajaxData, data, $el, field, instance );
				ajaxData = acf.applyFilters( 'select2_ajax_data/name=' + field.get( 'name' ), ajaxData, data, $el, field, instance );
				ajaxData = acf.applyFilters( 'select2_ajax_data/key=' + field.get( 'key' ), ajaxData, data, $el, field, instance );

				if (ajaxData.action) {
					ajaxData = acf.applyFilters( 'select2_ajax_data/action=' + ajaxData.action, ajaxData, data, $el, field, instance );
				}

				return ajaxData;

			}

		}
	);

	var Field = acf.Field.extend(
		{

			type: 'fea_plans',

			select2: false,

			wait: 'load',

			events: {
				'click .add-plan': 'addEditPlan',
				'click .edit-plan': 'addEditPlan',
				'click .delete-plan': 'deletePlan'
			},

			actions: {
				'frontend_form_success': 'showPlan',	
			},

			$input: function(){
				return this.$( 'select' );
			},

			initialize: function(){

				// vars
				var $select = this.$input();

				// inherit data
				this.inherit( $select );

				
			},

			addEditPlan: function( e, $el ){
				acf.getForm( $el, 'plans' );
			},

			deleteObject: function($el) {
				var plan = $el.parents('.fea-single-plan');
				plan.append( '<span class="fea-loader"></span>' );
				var ajaxData = {
					action:	'frontend_admin/plans/delete',
					plan: plan.data('plan')		
				};
				$.ajax(
					{
						url: acf.get( 'ajaxurl' ),
						type: 'post',
						data: acf.prepareForAjax( ajaxData ),
						cache: false,
						success: function(response){
							if (response.success) {
								plan.remove();
							} else {
								console.log( response );
								plan.find('.fea-loader').remove();
								$el.removeClass( 'disabled' )
							}
						}
					}
				);
			},

			showPlan: function( response ){
				if( response.success ){
					var data = response.data;
					var planData = data.plan || false;
					if( !planData ){
						return;
					}
					if( data.new ){
						var plan = this.$('.fea-single-plan.clone').clone().removeClass('acf-hidden clone').data('plan', planData.id).attr('data-plan',planData.id);
						plan.find('input').val(planData.id).removeAttr('disabled');
						this.$('.fea-plans').append(plan);
					}else{
						var plan = this.$('[data-plan='+planData.id+']');
					}
					plan.find('.fea-plan-title').text(planData.title + ' - ' + planData.pricing + ' ' + planData.currency);
				}
			},

			deletePlan: function( e, $el ){
				if ( $el.hasClass( 'disabled' ) ) {
					return;
				}

				$el.addClass( 'disabled' )

				var field = this;

				var tooltip = acf.newTooltip(
					{
						confirm: true,
						text: $el.data( 'confirm' ),
						target: $el,
						context: $el.parents( 'acf-field' ),
						confirm: function () {
							field.deleteObject( $el );
						},
						cancel: function () {
							$el.removeClass( 'disabled' );
						}
					}
				);
			},

		}
	);

	acf.registerFieldType( Field );

})( jQuery );


//vanilla js
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('fea-password-toggle')) {
        let container = e.target.closest('.acf-field');
        let input = container.querySelector('input.fea-password');
        if (input.type === 'password') {
            input.type = 'text';
            e.target.classList.remove('dashicons-visibility');
            e.target.classList.add('dashicons-hidden');
        } else {
            input.type = 'password';
            e.target.classList.remove('dashicons-hidden');
            e.target.classList.add('dashicons-visibility');
        }
    }
});

document.addEventListener("DOMContentLoaded", function () {
    function getFieldValue(field) {
        if (!field) return null;

        if (field.type === "radio" || field.type === "checkbox") {
            const checked = document.querySelector(`[name="${field.name}"]:checked`);
            return checked ? checked.value : null;
        }
        
        return field.value || null;
    }

	function evaluateConditions(field) {
		console.log(field.getAttribute('data-name'));
		const conditionsData = field.getAttribute("data-conditions");
		if (!conditionsData) return true;
	
		const conditions = JSON.parse(conditionsData);
		let fieldShouldBeVisible = false;
	
		// Find the nearest .acf-row parent (if applicable)
		const parentRow = field.closest(".acf-row");
	
		conditions.forEach((conditionGroup) => {
			let groupMet = true;
	
			if (!conditionGroup || conditionGroup.length < 1) return;
	
			conditionGroup.forEach((condition) => {
				let dependentFields;
	
				// If the condition field is inside an acf-row, check only within the same row
				if (parentRow && document.querySelector(`[data-key="${condition.field}"]`).closest(".acf-row")) {
					dependentFields = parentRow.querySelectorAll(`[data-key="${condition.field}"] input, 
																  [data-key="${condition.field}"] select, 
																  [data-key="${condition.field}"] textarea`);
				} else {
					// Otherwise, check globally (e.g., post title or fields outside repeaters)
					dependentFields = document.querySelectorAll(`[data-key="${condition.field}"] input, 
																 [data-key="${condition.field}"] select, 
																 [data-key="${condition.field}"] textarea`);
				}
	
				if (!dependentFields.length) return;
	
				let valueMatches = false;
				dependentFields.forEach((dependentField) => {
					const value = getFieldValue(dependentField);
					console.log('condition.value', condition.value);
					console.log(value);
	
					switch (condition.operator) {
						case "!=empty":
							if (value) valueMatches = true;
							break;
						case "==empty":
							if (!value) valueMatches = true;
							break;
						case "==":
							if (value == condition.value) valueMatches = true;
							break;
						case "!=":
							if (value != condition.value) valueMatches = true;
							break;
					}
				});
	
				if (!valueMatches) groupMet = false;
			});
	
			if (groupMet) fieldShouldBeVisible = true;
		});
	
		return fieldShouldBeVisible;
	}

    function toggleFieldState(field, showField) {
		// Check if the parent is visible
		let parent = field.parent().closest('[data-conditions]');
		while (parent) {
			const parentStyle = window.getComputedStyle(parent);
			if (parentStyle.display === "none" || parentStyle.visibility === "hidden") {
				showField = false;
				break;
			}
			parent = field.parent().closest('[data-conditions]');
		}
	
		// Toggle field visibility
		field.style.display = showField ? "block" : "none";
	
		const inputs = field.querySelectorAll("input, select, textarea");
	
		inputs.forEach((input) => {
			if (showField) {
				input.removeAttribute("disabled");
			} else {
				input.setAttribute("disabled", "disabled");
			}
		});
	}
	

    function checkAndApplyConditions() {
        document.querySelectorAll("[data-conditions]").forEach((field) => {
            const shouldShow = evaluateConditions(field);
            toggleFieldState(field, shouldShow);
        });
    }

    document.addEventListener("change", function (event) {
        if (event.target.matches("input, select, textarea")) {
            checkAndApplyConditions();
        }
    });

    checkAndApplyConditions();
});

   