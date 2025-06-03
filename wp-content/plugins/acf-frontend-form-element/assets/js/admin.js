(function($) {
		/**
		 * Insert text in input at cursor position
		 *
		 * Reference: http://stackoverflow.com/questions/1064089/inserting-a-text-where-cursor-is-using-javascript-jquery
		 */
	function insert_at_caret(input, text) {
		var txtarea = input;
		if ( ! txtarea) {
			return; }

		text          = '[' + text + ']';
		var scrollPos = txtarea.scrollTop;
		var strPos    = 0;
		var br        = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
		"ff" : (document.selection ? "ie" : false ) );
		if (br == "ie") {
			txtarea.focus();
			var range = document.selection.createRange();
			range.moveStart( 'character', -txtarea.value.length );
			strPos = range.text.length;
		} else if (br == "ff") {
			strPos = txtarea.selectionStart;
		}

		var front     = (txtarea.value).substring( 0, strPos );
		var back      = (txtarea.value).substring( strPos, txtarea.value.length );
		txtarea.value = front + text + back;
		strPos        = strPos + text.length;
		if (br == "ie") {
			txtarea.focus();
			var ieRange = document.selection.createRange();
			ieRange.moveStart( 'character', -txtarea.value.length );
			ieRange.moveStart( 'character', strPos );
			ieRange.moveEnd( 'character', 0 );
			ieRange.select();
		} else if (br == "ff") {
			txtarea.selectionStart = strPos;
			txtarea.selectionEnd   = strPos;
			txtarea.focus();
		}

		txtarea.scrollTop = scrollPos;
	}

	$( document ).ready(
		function() {

			$( '.select2' ).select2(
				{
					closeOnSelect: false
				}
			);

			$( document ).on(
				'change',
				'.dynamic-values select',
				function(e) {

					e.stopPropagation();

					var $option = $( this );

					var value = $option.val();

					if ( value == '' ) {
						return;
					}

					var $editor = $option.parents( '.acf-field[data-dynamic_values]' ).first().find( '.wp-editor-area' );

					// Check if we should insert into WYSIWYG field or a regular field
					if ( $editor.length > 0 ) {

						// WYSIWYG field
						var editor = tinymce.editors[ $editor.attr( 'id' ) ];
						editor.editorCommands.execCommand( 'mceInsertContent', false, '[' + value + ']' );
						$dvOpened = false;

					} else {

						// Regular field
						var $input = $option.parents( '.dynamic-values' ).siblings( 'input[type=text], textarea' );
						insert_at_caret( $input.get( 0 ), value );

					}

					$option.removeProp( 'selected' ).closest( 'select' ).val( '' ).trigger( 'change' );

				}
			);

			// Toggle dropdown
			$( document ).on(
				'focusin click',
				'.acf-field[data-dynamic_values] input, a.dynamic-value-options, .acf-field[data-dynamic_values] textarea',
				function(e) {
					e.stopPropagation();

					var $this = $( this );
					// dynamicValues.find('.all_fields-option').addClass('acf-hidden');
					var $dynamicValues = $( '.dynamic-values' );
					$this.after( $dynamicValues );
					$dynamicValues.show();
				}
			);

	
			$( 'body' ).on(
				'change',
				'select#form-admin_form_type',
				function(e){
					var title = $( this ).parents( 'form' ).find( 'input#title' );

					if ( title.val() == '' ) {
						title.val( $( this ).find( 'option[value=' + $( this ).val() + ']' ).text() );
						title.siblings( 'label' ).addClass( 'screen-reader-text' );
					}
				}
			);
		

		}
	);

	//when a user clicks on '.add-acf-fields', add a field and change the type to fields_select
	var formFieldManager = new acf.Model({
		id: 'formFieldManager',
		events: {
		  'click .add-acf-fields': 'onClickAddFields'
		},

		onClickAddFields: function (e, $el) {
			let $list;
			if ($el.hasClass('add-first-field')) {
			  $list = $el.parents('.acf-field-list').eq(0);
			} else if ($el.parent().hasClass('acf-headerbar-actions') || $el.parent().hasClass('no-fields-message-inner')) {
			  $list = $('.acf-field-list:first');
			} else if ($el.parent().hasClass('acf-sub-field-list-header')) {
			  $list = $el.parents('.acf-input:first').find('.acf-field-list:first');
			} else {
			  $list = $el.closest('.acf-tfoot').siblings('.acf-field-list');
			}
			this.addField($list);
		},
		addField: function ($list) {
			// vars
			var html = $('#tmpl-acf-field').html();
			var $el = $(html);
			var prevId = $el.data('id');
			var newKey = acf.uniqid('field_');
	  
			// duplicate
			var $newField = acf.duplicate({
			  target: $el,
			  search: prevId,
			  replace: newKey,
			  append: function ($el, $el2) {
				$list.append($el2);
			  }
			});
	  
			// get instance
			var newField = acf.getFieldObject($newField);
	  
			// props
			newField.prop('key', newKey);
			newField.prop('ID', 0);
			newField.prop('label', 'ACF Fields');
			newField.prop('name', '');
	  
			// attr
			$newField.attr('data-key', newKey);
			$newField.attr('data-id', newKey);
	  
			// update parent prop
			newField.updateParent();
	  
			// focus type
			var $type = newField.$input('type');
	  
			// open
			newField.open();
			//change type to fields_select
			$type.val('fields_select');
			$type.change();
	  
			// set menu order
			this.renderFields($list);
	  
			// action
			acf.doAction('add_field_object', newField);
			acf.doAction('append_field_object', newField);
	  	}
	
	});

})( jQuery );

