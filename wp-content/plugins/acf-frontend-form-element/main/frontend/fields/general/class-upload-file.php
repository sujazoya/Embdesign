<?php
namespace Frontend_Admin\Field_Types;

if ( ! class_exists( 'upload_file' ) ) :

	class upload_file extends Field_Base {



		/*
		*  __construct
		*
		*  This function will setup the field type data
		*
		*  @type    function
		*  @date    5/03/2014
		*  @since    5.0.0
		*
		*  @param    n/a
		*  @return    n/a
		*/

		function initialize() {
			// vars
			$this->name     = 'upload_file';
			$this->label    = __( 'Upload File', 'acf-frontend-form-element' );
			$this->public   = false;
			$this->defaults = array(
				'return_format' => 'array',
				'preview_size'  => 'thumbnail',
				'library'       => 'all',
				'min_width'     => 0,
				'min_height'    => 0,
				'min_size'      => 0,
				'max_width'     => 0,
				'max_height'    => 0,
				'max_size'      => 0,
				'mime_types'    => '',
				'show_preview'  => true,
				'button_text'   => __( 'Add File', 'acf-frontend-form-element' ),
				'no_file_text'  => __( 'No file selected', 'acf-frontend-form-element' ),
			);

			// actions
			add_action( 'wp_ajax_acf/fields/upload_file/add_attachment', array( $this, 'ajax_add_attachment' ) );
			add_action( 'wp_ajax_nopriv_acf/fields/upload_file/add_attachment', array( $this, 'ajax_add_attachment' ) );

			//atachment metadata
			add_action( 'wp_ajax_acf/fields/upload_file/update_meta', array( $this, 'update_meta' ) );
			add_action( 'wp_ajax_nopriv_acf/fields/upload_file/update_meta', array( $this, 'update_meta' ) );

			add_action( 'pre_get_posts', [ $this, 'hide_uploads_media_list_view' ] );
			add_filter( 'ajax_query_attachments_args', [ $this, 'hide_uploads_media_overlay_view' ] );


			$file_fields = array( 'file', 'image', 'upload_file', 'featured_image', 'main_image', 'site_logo', 'upload_image' );
			foreach ( $file_fields as $type ) {
				add_filter( 'acf/prepare_field/type=' . $type, array( $this, 'prepare_image_or_file_field' ), 5 );
				add_filter( 'acf/pre_update_value/type=' . $type, array( $this, 'update_file_value' ), 8, 4 );
				add_filter( 'acf/validate_value/type=' . $type, array( $this, 'validate_file_value' ), 5, 4 );
				add_action( 'acf/render_field_settings/type=' . $type, array( $this, 'upload_button_text_setting' ) );
				add_action( 'acf/render_field_settings/type=' . $type, array( $this, 'extra_file_settings' ) );

				if( 'image' == $type || 'upload_image' == $type ){
					add_filter( 'acf/update_field/type='.$type, [ $this, 'update_local_avatar' ] );
					add_action( 'acf/render_field_settings/type=' . $type, array( $this, 'local_avatar_setting' ) );
				}
			}

			$file_fields = array_merge( $file_fields, array( 'gallery', 'product_images', 'upload_files' ) );
			foreach ( $file_fields as $type ) {
				add_action( 'acf/render_field_settings/type=' . $type, array( $this, 'extra_file_settings' ) );
				add_filter( 'acf/pre_update_value/type=' . $type, array( $this, 'move_folders' ), 9, 4 );
			}

			if ( defined( 'HAPPYFILES_VERSION' ) ) {
				foreach ( $file_fields as $type ) {
					add_action( 'acf/render_field_settings/type=' . $type, array( $this, 'happy_folders_setting' ) );
				}
				add_filter( 'ajax_query_attachments_args', array( $this, 'happy_files_folder' ) );
			}

			add_action('init', [$this, 'handle_file_upload_request']);


		}
	
	
		function handle_file_upload_request() {
			
			// Check if it's a file upload request.
			if ( empty($_GET['fea-file-upload']) || empty( $_GET['file'] ) ) return;
			
			$file = sanitize_text_field(wp_unslash($_GET['file']));

			//check nonce 
			if ( empty( $_GET['nonce'] ) || wp_verify_nonce( $_GET['nonce'], 'fea-file-upload_'. $file ) === false ) {
				wp_die(__('Invalid nonce.', 'acf-frontend-form-element'), '403 Forbidden', ['response' => 403]);
			}


			$upload_dir = wp_upload_dir();
			$file_path = $upload_dir['basedir'] . '/' . $file;
			$real_file_path = realpath( $file_path );
			if ($real_file_path === false || strpos($real_file_path, $upload_dir['basedir']) !== 0) {
				wp_die(__('Invalid file path.', 'acf-frontend-form-element'), '400 Bad Request', ['response' => 400]);
			}
	
			// Ensure the file exists.
			if (!file_exists( $real_file_path )) {
				wp_die(__('File not found.', 'acf-frontend-form-element'), '404 Not Found', ['response' => 404]);
			}
	
			// Check permissions.
			$attachment_id = attachment_url_to_postid($upload_dir['baseurl'] . '/' . $file);
			$uploader_id = get_post_field('post_author', $attachment_id);
			$current_user = wp_get_current_user();
	
			if (
				!$current_user->exists() || // User not logged in
				($uploader_id != $current_user->ID && !current_user_can('administrator') && !current_user_can('editor'))
			) {
				wp_die(__('Unauthorized access.', 'acf-frontend-form-element'), '403 Forbidden', ['response' => 403]);
			}
	
			// Serve the file with proper headers.
			header('Content-Type: ' . mime_content_type($real_file_path));
			header('Content-Disposition: inline; filename="' . basename($real_file_path) . '"');
			readfile($real_file_path);
			exit;
		
		}

		/**
		 * Hide attachment files from the Media Library's overlay (modal) view
		 * if they have a certain meta key set.
		 * 
		 * @param array $args An array of query variables.
		 */
		function hide_uploads_media_overlay_view( $args ) {
			// Bail if this is not the admin area.
			if ( ! is_admin() ) {
				return $args;
			}

			// Modify the query.
			$args['meta_query'] = [
				[
					'key'     => '_hide_from_library',
					'compare' => 'NOT EXISTS',
				]
			];
		
			return $args;
		}
		/**
		 * Hide attachment files from the Media Library's list view
		 * if they have a certain meta key set.
		 * 
		 * @param WP_Query $query The WP_Query instance (passed by reference).
		 */
		function hide_uploads_media_list_view( $query ) {
			// Bail if this is not the admin area.
			if ( ! is_admin() ) {
				return;
			}

			// Bail if this is not the main query.
			if ( ! $query->is_main_query() ) {
				return;
			}

			// Only proceed if this the attachment upload screen.
			$screen = get_current_screen();
			if ( ! $screen || 'upload' !== $screen->id || 'attachment' !== $screen->post_type ) {
				return;
			}
			
			// Modify the query.
			$query->set( 'meta_query', [
				[
					'key'     => '_hide_from_library',
					'compare' => 'NOT EXISTS',
				]
			]   );

			return;
		}

		function extra_file_settings( $field ) {
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Custom Directory', 'acf-frontend-form-element' ),
					'name'         => 'custom_directory',
					'type'         => 'true_false',
					'ui'           => 1,
					'instructions' => __( "Save files in a custom directory under the wp-content/uploads directory", 'acf-frontend-form-element' ),
				)
			);
			acf_render_field_setting(
				$field,
				array(
					'label'         => __( 'Folder Name', 'acf-frontend-form-element' ),
					'placeholder'   => '[post:type]',
					'type'          => 'text',
					'dynamic_value_choices' => 1,
					'name'          => 'custom_directory_name',
					'conditions' => array(
						array(
							array(
								'field'    => 'custom_directory',
								'operator' => '==',
								'value'    => 1,
							),
						),
					),
				)
			);
			if( fea_instance()->is_license_active() ){
				acf_render_field_setting(
					$field,
					array(
						'label'        => __( 'Secure Directory', 'acf-frontend-form-element' ),
						'name'         => 'secure_directory',
						'type'         => 'true_false',
						'ui'           => 1,
						'instructions' => __( "Block external access to this directory. (Takes affect when file is added. Requires .htaccess support)", 'acf-frontend-form-element' ),
						'conditions' => array(
							array(
								array(
									'field'    => 'custom_directory',
									'operator' => '==',
									'value'    => 1,
								),
							),
						),
					)
				);
			}
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Hide from library', 'acf-frontend-form-element' ),
					'name'         => 'hide_from_library',
					'type'         => 'true_false',
					'ui'           => 1,
					'instructions' => __( "Files will not appear in the WP library", 'acf-frontend-form-element' ),
				)
			);

			if( 'file' !== $field['type'] && 'image' !== $field['type'] ){

				// allowed types
				acf_render_field_setting(
					$field,
					array(
						'label'        => __( 'Allowed file types', 'acf-frontend-form-element' ),
						'instructions' => __( 'Comma separated list. Leave blank for all types', 'acf-frontend-form-element' ),
						'type'         => 'text',
						'default_value' => 'php',
						'name'         => 'mime_types',
					)
				);
			}

			
		}

	

		function happy_folders_setting( $field ) {
			acf_render_field_setting(
				$field,
				array(
					'label'         => __( 'Happy Files Folder', 'acf-frontend-form-element' ),
					'instructions'  => __( 'Limit the media library choice to specific Happy Files Categories', 'acf-frontend-form-element' ),
					'type'          => 'radio',
					'name'          => 'happy_files_folder',
					'layout'        => 'horizontal',
					'default_value' => 'all',
					'choices'       => $this->get_happy_files_folders(),
				)
			);
		}

		function get_happy_files_folders() {
			$folders    = array( 'all' => __( 'All Folders', 'acf-frontend-form-element' ) );
			$taxonomies = get_terms(
				array(
					'taxonomy'   => 'happyfiles_category',
					'hide_empty' => false,
				)
			);

			if ( empty( $taxonomies ) ) {
				return $folders;
			}

			foreach ( $taxonomies as $category ) {
				$folders[ $category->name ] = ucfirst( esc_html( $category->name ) );
			}

			return $folders;
		}

		function happy_files_folder( $query ) {
			if ( empty( $query['_acfuploader'] ) ) {
				return $query;
			}

			// load field
			$field = acf_get_field( $query['_acfuploader'] );
			if ( ! $field ) {
				return $query;
			}

			if ( ! isset( $field['happy_files_folder'] ) || $field['happy_files_folder'] == 'all' ) {
				return $query;
			}

			if ( isset( $query['tax_query'] ) ) {
				$tax_query = $query['tax_query'];
			} else {
				$tax_query = array();
			}

			$tax_query[]        = array(
				'taxonomy' => 'happyfiles_category',
				'field'    => 'name',
				'terms'    => $field['happy_files_folder'],
			);
			$query['tax_query'] = $tax_query;

			return $query;
		}

		public function upload_file( $path, $filename, $secure = false){
            $pos = strrpos($filename, '.');
            $ext = substr($filename, $pos);           
        
            $newname = substr($filename, 0, $pos);
			if( $secure ){
				$newname .= '-' . uniqid() . '';
			}
			$newpath = $path.'/'.$newname . $ext;
            $counter = 1;

            while (file_exists($newpath)) {
                $newname = $newname .'-'. $counter;

                $newpath = $path.'/'.$newname . $ext;
				
                $counter++;
            }

            return $newpath;
        }

		
		/**
		 * validate_attachment
		 *
		 * This function will validate an attachment based on a field's restrictions and return an array of errors
		 *
		 * @since   5.2.3
		 *
		 * @param   $attachment (array) attachment data. Changes based on context
		 * @param   $field (array) field settings containing restrictions
		 * @param   context (string)  $file is different when uploading / preparing
		 * @return  $errors (array)
		 */
		function validate_attachment( $attachment, $field, $context = 'prepare' ) {

			// vars
			$errors = array();
			$file   = array(
				'type'   => '',
				'width'  => 0,
				'height' => 0,
				'size'   => 0,
			);

			// upload
			if ( $context == 'upload' ) {

				// vars
				$file['type'] = pathinfo( $attachment['name'], PATHINFO_EXTENSION );
				$file['size'] = filesize( $attachment['tmp_name'] );

				if ( strpos( $attachment['type'], 'image' ) !== false ) {
					$size           = getimagesize( $attachment['tmp_name'] );
					$file['width']  = feadmin_maybe_get( $size, 0 );
					$file['height'] = feadmin_maybe_get( $size, 1 );
				}

				// prepare
			} elseif ( $context == 'prepare' ) {
				$use_path       = isset( $attachment['filename'] ) ? $attachment['filename'] : $attachment['url'];
				$file['type']   = pathinfo( $use_path, PATHINFO_EXTENSION );
				$file['size']   = feadmin_maybe_get( $attachment, 'filesizeInBytes', 0 );
				$file['width']  = feadmin_maybe_get( $attachment, 'width', 0 );
				$file['height'] = feadmin_maybe_get( $attachment, 'height', 0 );

				// custom
			} else {
				$file         = array_merge( $file, $attachment );
				$use_path     = isset( $attachment['filename'] ) ? $attachment['filename'] : $attachment['url'];
				$file['type'] = pathinfo( $use_path, PATHINFO_EXTENSION );
			}

			// image
			if ( $file['width'] || $file['height'] ) {

				// width
				$min_width = (int) feadmin_maybe_get( $field, 'min_width', 0 );
				$max_width = (int) feadmin_maybe_get( $field, 'max_width', 0 );

				if ( $file['width'] ) {
					if ( $min_width && $file['width'] < $min_width ) {

						// min width
						$errors['min_width'] = sprintf( __( 'Image width must be at least %dpx.', 'acf-frontend-form-element' ), $min_width );
					} elseif ( $max_width && $file['width'] > $max_width ) {

						// min width
						$errors['max_width'] = sprintf( __( 'Image width must not exceed %dpx.', 'acf-frontend-form-element' ), $max_width );
					}
				}

				// height
				$min_height = (int) feadmin_maybe_get( $field, 'min_height', 0 );
				$max_height = (int) feadmin_maybe_get( $field, 'max_height', 0 );

				if ( $file['height'] ) {
					if ( $min_height && $file['height'] < $min_height ) {

						// min height
						$errors['min_height'] = sprintf( __( 'Image height must be at least %dpx.', 'acf-frontend-form-element' ), $min_height );
					} elseif ( $max_height && $file['height'] > $max_height ) {

						// min height
						$errors['max_height'] = sprintf( __( 'Image height must not exceed %dpx.', 'acf-frontend-form-element' ), $max_height );
					}
				}
			}

			// file size
			if ( $file['size'] ) {
				$min_size = feadmin_maybe_get( $field, 'min_size', 0 );
				$max_size = feadmin_maybe_get( $field, 'max_size', 0 );

				if ( $min_size && $file['size'] < acf_get_filesize( $min_size ) ) {

					// min width
					$errors['min_size'] = sprintf( __( 'File size must be at least %s.', 'acf-frontend-form-element' ), acf_format_filesize( $min_size ) );
				} elseif ( $max_size && $file['size'] > acf_get_filesize( $max_size ) ) {

					// min width
					$errors['max_size'] = sprintf( __( 'File size must not exceed %s.', 'acf-frontend-form-element' ), acf_format_filesize( $max_size ) );
				}
			}

			// file type
			if ( $file['type'] ) {
				$mime_types = feadmin_maybe_get( $field, 'mime_types', '' );

				// lower case
				$file['type'] = strtolower( $file['type'] );
				$mime_types   = strtolower( $mime_types );

				// explode
				$mime_types = str_replace( array( ' ', '.' ), '', $mime_types );
				$mime_types = explode( ',', $mime_types ); // split pieces
				$mime_types = array_filter( $mime_types ); // remove empty pieces

				if ( ! empty( $mime_types ) && ! in_array( $file['type'], $mime_types ) ) {

					// glue together last 2 types
					if ( count( $mime_types ) > 1 ) {
						$last1 = array_pop( $mime_types );
						$last2 = array_pop( $mime_types );

						$mime_types[] = $last2 . ' ' . __( 'or', 'acf-frontend-form-element' ) . ' ' . $last1;
					}

					$errors['mime_types'] = sprintf( __( 'File type must be %s.', 'acf-frontend-form-element' ), implode( ', ', $mime_types ) );
				}else{
					$wp_filetype = wp_check_filetype( basename( $attachment['name'] ), null );

					$allowed_types = get_allowed_mime_types();
					if( ! in_array( $wp_filetype['type'], $allowed_types ) ){
						$errors['mime_types'] = sprintf( __( 'Cannot upload %s files.', 'acf-frontend-form-element' ), $file['type'] );
					}
				}
			}

			/**
			* Filters the errors for a file before it is uploaded or displayed in the media modal.
			*
			* @since   5.2.3
			*
			* @param   array $errors An array of errors.
			* @param   array $file An array of data for a single file.
			* @param   array $attachment An array of attachment data which differs based on the context.
			* @param   array $field The field array.
			* @param   string $context The curent context (uploading, preparing)
			*/
			$errors = apply_filters( "frontend_admin/validate_attachment/type={$field['type']}", $errors, $file, $attachment, $field, $context );
			$errors = apply_filters( "frontend_admin/validate_attachment/name={$field['_name']}", $errors, $file, $attachment, $field, $context );
			$errors = apply_filters( "frontend_admin/validate_attachment/key={$field['key']}", $errors, $file, $attachment, $field, $context );
			$errors = apply_filters( 'frontend_admin/validate_attachment', $errors, $file, $attachment, $field, $context );

			// return
			return $errors;
		}

		function ajax_add_attachment() {

			$args = acf_parse_args(
				$_POST,
				array(
					'field_key' => '',
					'nonce'     => '',
				)
			);

			// validate nonce
			if ( ! feadmin_verify_ajax() ) {
				wp_send_json_error( __( 'Authentication Error. Please try refreshing the page.', 'acf-frontend-form-element' ) );
			}

			// bail early if no attachments
			if ( empty( $_FILES['file']['name'] ) ) {
				wp_send_json_error( __( 'Missing file name', 'acf-frontend-form-element' ) );
			}

			// TO dos: validate file types, sizes, and dimensions
			// Add loading bar for each image
			if ( isset( $args['field_key'] ) ) {
				$field = fea_instance()->frontend->get_field( $args['field_key'] );
			} else {
				wp_send_json_error( __( 'Invalid Key', 'acf-frontend-form-element' ) );
			}

			$file = $_FILES['file'];

			//sanitize file name
			$file['name'] = sanitize_file_name( $file['name'] );

			// get errors
			$errors = $this->validate_attachment( $file, $field, 'upload' );

			// append error
			if ( ! empty( $errors ) ) {
				$data = implode( "\n", $errors );
				wp_send_json_error( $data );
			}

			$wp_filetype = wp_check_filetype( basename( $file['name'] ), null );

			$wp_upload_dir = wp_upload_dir();

	

			$submissions_dir = $this->maybe_mkdir( $wp_upload_dir['basedir'] . '/fea-submissions', true );	
			$url = $this->upload_file($submissions_dir,$file['name'], true);			
			move_uploaded_file( $file['tmp_name'], $url );	

			$attachment = array(
				'guid'           => $url,
				'post_mime_type' => $wp_filetype['type'],
				'post_status'    => 'inherit',
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file['name'] ) ),
			);

			$attach_id = wp_insert_attachment( $attachment, $url );
			$attach_url = wp_get_attachment_url( $attach_id );

			update_post_meta( $attach_id, '_hide_from_library', true );

			if( $attach_id instanceof WP_Error ){
				wp_send_json_error( $attach_id->get_error_message() );
			}

			if ( ! empty( $value['alt'] ) ) {
				update_post_meta( $attach_id, '_wp_attachment_image_alt', $value['alt'] );
			}

			wp_send_json_success( [ 'id' => $attach_id, 'url' => $attach_url ] );
		}

		function update_meta() {
			$args = acf_parse_args(
				$_POST,
				array(
					'attach_id' => '',
					'url'       => '',
					'nonce'     => '',
				)
			);

			// validate nonce
			if ( ! feadmin_verify_ajax() ) {
				wp_send_json_error( __( 'Authentication Error. Please try refreshing the page.', 'acf-frontend-form-element' ) );
			}

			// bail early if no attachments
			if ( empty( $args['attach_id'] ) ) {
				wp_send_json_error( __( 'Missing attachment id', 'acf-frontend-form-element' ) );
			}

			$attach_id = $args['attach_id'];

			$path = get_attached_file( $attach_id );

			$attach_data = wp_generate_attachment_metadata( $attach_id, $path );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			wp_send_json_success();
		}

		function maybe_mkdir( $submissions_dir, $secure = false ){
			if ( ! is_dir( $submissions_dir ) ) {
				mkdir( $submissions_dir );
				//add index.php file to prevent directory listing
				if( $secure ){				
					if ( ! file_exists( $submissions_dir . '/index.php' ) ) {
						touch( $submissions_dir . '/index.php' );
					}
				}else{
					
					if ( file_exists( $submissions_dir . '/index.php' ) ) {
						unlink( $submissions_dir . '/index.php' );
					}
				}
			}

			$_htaccess = $submissions_dir . '/.htaccess';
				//delete legacy fea-submissions .htaccess file	
			if ( file_exists( $_htaccess ) ) {
				unlink( $_htaccess );
			}		

			return $submissions_dir;
		}


		function prepare_image_or_file_field( $field ) {
			global $fea_form;
			if( ! $fea_form && empty( $GLOBALS['admin_form'] ) ){
				return $field;
			}
			if ( in_array( $field['type'], array( 'image', 'featured_image', 'main_image', 'site_logo', 'upload_image' ) ) ) {
				$field['type'] = 'upload_image';
			}else{
				$field['type'] = 'upload_file';
			}

			$field = $this->prepare_field( $field );

			return $field;
		}
		/**
		 *  validate_value
		 *
		 *  This function will validate a basic file input
		 *
		 * @type  function
		 * @date  14/11/2022
		 * @since 5.0.0
		 *
		 * @param  $post_id (int)
		 * @return $post_id (int)
		 */
		function validate_file_value( $valid, $value, $field, $input ) {
			// bail early if empty
			if ( empty( $value ) ) {
				return $valid;
			}

			// bail early if is numeric
			if ( is_numeric( $value ) ) {
				return $valid;
			}

			if ( isset( $value['file'] ) ) {
				if ( ! $value['file']
					&& ! $value['id']
					&& $field['required']
				) {
					return sprintf( __( '%s value is required.', 'acf-frontend-form-element' ), $field['label'] );
				} else {
					return $valid;
				}
			}
			return $valid;

		}
		/*
		*  prepare_field()
		*
		*  Prepares field setting prior to rendering field in form
		*
		*  @param    $field - an array holding all the field's data
		*  @return    $field
		*
		*  @type    action
		*  @since    3.6
		*  @date    23/01/13
		*/

		function prepare_field( $field ) {
			if( empty( $field['wrapper']['class'] ) ){
				$field['wrapper']['class'] = '';
			}
			$uploader = acf_get_setting( 'uploader' );
			if ( $uploader == 'basic' ) {
				$field['wrapper']['data-field_type'] = $field['type'];
			}

			if ( $uploader == 'basic' ) {
				$field['wrapper']['class'] .= ' acf-uploads';
			}
			$field['wrapper']['class'] .= ' image-field';

			if ( empty( $field['max_width'] ) ) {
				$field['max_width'] = 1920;
			}
			if ( empty( $field['max_height'] ) ) {
				$field['max_height'] = 1080;
			}

			return $field;
		}


		function get_accept_value( $field ){
			if ( 'image' == $field['field_type'] ) {
				return 'image/*';
			}

			if ( $field['mime_types'] ) {
				$mime_types = wp_get_mime_types();
				$allowed_types = explode( ',', $field['mime_types'] );

				foreach ( $allowed_types as $key => $type ) {
					$type = trim( $type );
					if ( ! in_array( $type, $mime_types ) ) {								
						// find an array key that resembles the type
						$found = array_filter(
							$mime_types,
							function( $mime, $key ) use ( $type ) {
								$mimes = explode( '|', $key );
								return in_array( $type, $mimes );
							},
							ARRAY_FILTER_USE_BOTH
						);
						if( $found ){
							$allowed_types[key( $found )] = $found[ key( $found ) ];
						}							

					}
				}

				if( $allowed_types ){
					return implode( ',', $allowed_types );
				}
			}
			
			$allowed_types = get_allowed_mime_types();
			$allowed_types = implode( ',', $allowed_types );
			return $allowed_types;
			
			
		}


		/*
		*  render_field()
		*
		*  Create the HTML interface for your field
		*
		*  @param    $field - an array holding all the field's data
		*
		*  @type    action
		*  @since    3.6
		*  @date    23/01/13
		*/

		function render_field( $field ) {
			if ( empty( $field['field_type'] ) ) {
				$field['field_type'] = 'file';
			}
			if ( empty( $field['destination'] ) ) {
				$field['destination'] = '';
			}

			$value = $field['value'];
	
			// vars
			$uploader = acf_get_setting( 'uploader' );

			// allow custom uploader
			$uploader = feadmin_maybe_get( $field, 'uploader', $uploader );

			// enqueue
			if ( $uploader == 'wp' && ! feadmin_edit_mode() ) {
				acf_enqueue_uploader();
			}

			// vars
			$o = array(
				'icon'     => '',
				'title'    => '',
				'url'      => '',
				'filename' => '',
				'filesize' => '',
			);

			$default_icon = wp_mime_type_icon( 'application/pdf' );

			$div = array(
				'class'              => 'acf-file-uploader',
				'data-library'       => $field['library'],
				'data-mime_types'    => $field['mime_types'],
				'data-uploader'      => $uploader,
				'data-destination'   => $field['destination'],
				'data-resize_width'  => $field['max_width'],
				'data-resize_height' => $field['max_height'],
			);

			if ( ! empty( $field['button_text'] ) ) {
				$button_text = $field['button_text'];
			} else {
				$button_text = __( 'Add File', 'acf-frontend-form-element' );
			}

			// has value?
			if ( $value ) {
				$attachment = acf_get_attachment( $value );
				if ( $attachment ) {

					// has value
					$div['class'] .= ' has-value';

					// update
					$o['icon']     = $attachment['icon'];
					$o['title']    =  $attachment['title'];
					$o['url']      = $attachment['url'];
					$o['filename'] = $attachment['filename'];
					if ( $attachment['filesize'] ) {
						   $o['filesize'] = size_format( $attachment['filesize'] );
					}
				}
			}

			?>
		<div <?php acf_esc_attr_e( $div ); ?>>
			<?php		
			acf_hidden_input(
				array(
					'data-name' => 'id',
					'name'      => $field['name'],
					'value'     => $value,
				)
			);
			?>
		<?php 
		if ( 'basic' == $uploader ) {
			?>
			<div class="frontend-admin-hidden uploads-progress"><div class="percent">0%</div><div class="bar"></div></div>
			<?php
		}
		$show_preview = true; 
		if( $show_preview ) : 
		?>
		<div class="show-if-value file-wrap">
			<?php $edit = $uploader != 'basic' ? 'edit' : 'edit-preview'; ?>
			<div class="file-icon">
				<img data-name="icon" data-default="<?php esc_attr_e( $default_icon ); ?>" src="<?php echo esc_url( $o['url'] ); ?>" alt=""/>
			</div>
			<div class="file-info">
				<p>
					<strong data-name="title"><?php echo esc_html( $o['title'] ); ?></strong>
				</p>
				<p>
					<strong><?php esc_html_e( 'File name', 'acf-frontend-form-element' ); ?>:</strong>
					<a data-name="filename" href="<?php echo esc_url( $o['url'] ); ?>" target="_blank"><?php echo esc_html( $o['filename'] ); ?></a>
				</p>
				<p>
					<strong><?php esc_html_e( 'File size', 'acf-frontend-form-element' ); ?>:</strong>
					<span data-name="filesize"><?php echo esc_html( $o['filesize'] ); ?></span>
				</p>
			</div>
			<div class="acf-actions -hover">
				<a class="acf-icon -pencil dark" data-name="<?php esc_attr_e( $edit ); ?>" href="#" title="<?php esc_attr_e( 'Edit', 'acf-frontend-form-element' ); ?>"></a>
				<a class="acf-icon -cancel dark" data-name="remove" href="#" title="<?php esc_attr_e( 'Remove', 'acf-frontend-form-element' ); ?>"></a>
			</div>
		</div>
		<div class="hide-if-value">

		<?php
		endif;

		
		?>
			<?php
			$empty_text = __( 'No file selected', 'acf-frontend-form-element' );
			if ( isset( $field['no_file_text'] ) ) {
				$empty_text = $field['no_file_text'];
			}
			if ( $uploader == 'basic' ) :
				?>
				<?php if ( $value && ! is_numeric( $value ) ) : ?>
				<div class="acf-error-message"><p><?php echo acf_esc_html( $value ); ?></p></div>
				<?php endif; ?>
		
			<div class="acf-basic-uploader file-drop">
				<?php
				$input_args = array(
					'name'  => 'upload_file_input',
					'id'    => $field['id'],
					'class' => 'file-preview',
				);
				$input_args['accept'] = $this->get_accept_value( $field );
				acf_file_input( $input_args );
				?>
				<p><?php echo esc_html( $empty_text ); ?></p>
				<a href="#" data-name="upload-file" class="upload-files">
					<?php echo esc_html( $button_text ); ?>
				</a>
			</div>
			<?php
			$prefix = 'acff[file_data][' . $field['key'] . ']';
			fea_instance()->form_display->render_meta_fields( $prefix, $value );
			
			else : ?>
			<p><?php echo esc_html( $empty_text ); ?> <a data-name="add" class="button" href="#"><?php echo esc_html( $button_text ); ?></a></p>
			
			<?php endif; ?>
			
			<?php if( $show_preview ) : ?>
			</div>
			<?php endif; ?>
		</div>
		<?php

		}

		function update_local_avatar( $field ){
			if( ! empty( $field['local_avatar'] ) ){
				update_option( 'local_avatar', $field['key'] );
				unset( $field['local_avatar'] );
			}
			return $field;
		}

		function local_avatar_setting( $field ) {
			$local_avatar = get_option('local_avatar','');
	
			//set as global Local Avatar field
			acf_render_field_setting(
				$field,
				array(
					'label' 	  => __( 'Local Avatar', 'acf-frontend-form-element' ),
					'name'		  => 'local_avatar',
					'type'        => 'true_false',
					'ui' 		  => 1,
					'value' 	  => $field['key'] == $local_avatar
				)
			);
		}
		function upload_button_text_setting( $field ) {
				acf_render_field_setting(
				$field,
				array(
					'label'       => __( 'No File Text', 'acf-frontend-form-element' ),
					'name'        => 'no_file_text',
					'type'        => 'text',
					'placeholder' => __( 'No file selected', 'acf-frontend-form-element' ),
				)
			);
			acf_render_field_setting(
				$field,
				array(
					'label'       => __( 'Button Text', 'acf-frontend-form-element' ),
					'name'        => 'button_text',
					'type'        => 'text',
					'placeholder' => __( 'Add File', 'acf-frontend-form-element' ),
				)
			);
					


		}

		function move_folders( $checked, $value, $post_id = false, $field = false ) {
			if( ! $value ) return $checked;

			global $fea_form, $fea_instance;

			if ( empty( $fea_form['record'] ) ) return $checked;

			$uploads = wp_upload_dir();
			if( ! empty( $field['custom_directory'] ) && ! empty( $field['custom_directory_name'] ) ){
				$dir_name = $field['custom_directory_name'];

				$dir_name = fea_instance()->dynamic_values->get_dynamic_values( $dir_name );

								
				$create_directory = wp_mkdir_p( $uploads['basedir'] . '/' . $dir_name );
			
				if( $create_directory ){
					$upload_dir = $uploads['basedir'] . '/' . $dir_name;
					$_htaccess = $upload_dir . '/.htaccess';			
			
					if( ! empty ( $field['secure_directory'] ) && $fea_instance->is_license_active() ){
						// Protect uploads directory for the servers that support .htaccess

						if ( ! file_exists( $_htaccess ) ) {
							$site_url = site_url();
							$nonce = wp_create_nonce( 'fea-file-upload_' . $dir_name );
							$rules = <<<HTACCESS
							<IfModule mod_rewrite.c>
								RewriteEngine On
								RewriteBase /wp-content/uploads/

								# Redirect file requests to WordPress with query parameters
								RewriteCond %{REQUEST_FILENAME} -f
								RewriteRule ^(.*)$ "{$site_url}/?fea-file-upload&nonce={$nonce}&file={$dir_name}/$1" [R=302,L]
							</IfModule>
							HTACCESS;
							file_put_contents( $_htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
						} 
					
						if ( ! file_exists( $upload_dir . '/index.php' ) ) {
							touch( $upload_dir . '/index.php' );
						}
					}else{
						if ( file_exists( $_htaccess ) ) {
							unlink( $_htaccess );
						}
						if ( file_exists( $upload_dir . '/index.php' ) ) {
							unlink( $upload_dir . '/index.php' );
						}
					}
				}		
			}

			if( empty( $upload_dir ) ){
				$upload_dir = $uploads['path'];
			}				

			$upload_dir = apply_filters( 'frontend_admin/files/folder_path', $upload_dir, $field );

			if( is_array( $value ) ){
				foreach( $value as $attach_id ){
					$this->move_file( $attach_id, $upload_dir, $field );
				}
			}else{
				if(  is_numeric( $value ) ) $this->move_file( $value, $upload_dir, $field );
			} 							
			
			return $checked;
		}

		function move_file( $attachment, $upload_dir, $field ){
			if( ! empty( $field['hide_from_library'] ) ){
				update_post_meta( $attachment, '_hide_from_library', true );
			}else{
				delete_post_meta( $attachment, '_hide_from_library' );
			}			

			$reached_dest = get_post_meta( $attachment, '_reached_dest', true );

			if( $reached_dest ) return;

			$path = get_attached_file( $attachment );

			if( $path ){

				$file_base = basename( $path );
				$new_path = $this->upload_file( $upload_dir, $file_base );

				if ( !file_exists($path) ) return;

				$moved = rename( $path, $new_path );
				if( $moved ){
					update_attached_file( $attachment, $new_path );

					$attach_data = wp_get_attachment_metadata( $attachment, $new_path );
					if( ! $attach_data ) return;
					
					if( wp_attachment_is_image( $attachment ) ){
						if( ! empty( $attach_data['sizes'] ) ){
							foreach ( $attach_data['sizes'] as $key => $image_size ) {
								// get the path for this size
								$size_path = str_replace( $file_base, $image_size['file'], $path );
						
								if( ! file_exists( $size_path ) ) continue;
								//remove unique id surrounded by double square brackets
								$new_path = $this->upload_file( $upload_dir, $image_size['file'] );
								rename( $size_path, $new_path );						
								$attach_data['sizes'][$key]['file'] = basename( $new_path );
							}
						}
					} 
					$attach_data['file'] = $upload_dir . '/' . $file_base;
					wp_update_attachment_metadata( $attachment, $attach_data );

					update_post_meta( $attachment, '_reached_dest', true );
				}

			}
		}

		function update_file_value( $checked, $value, $post_id = false, $field = false ) {
			if ( is_numeric( $post_id ) ) {
				$value = (int) $value;
				$post  = get_post( $post_id );
				if ( wp_is_post_revision( $post ) ) {
					$post_id = $post->post_parent;
				}

				acf_connect_attachment_to_post( $value, $post_id );
			}

			global $fea_form;

			if ( ! empty( $fea_form['record']['fields']['file_data'][$field['name']] ) ) {
				$meta = $fea_form['record']['fields']['file_data'][$field['name']];
				if ( isset( $meta['alt'] ) ) {
					update_post_meta( $value, '_wp_attachment_image_alt', sanitize_text_field( $meta['alt'] ) );
				}

				$edit = array( 'ID' => $value );
				if ( ! empty( $meta['title'] ) ) {
					$edit['post_title'] = sanitize_text_field( $meta['title'] );
				}

				if ( isset( $meta['description'] ) ) {
					$edit['post_content'] = sanitize_textarea_field( $meta['description'] );
				}
				if ( isset( $meta['caption'] ) ) {
					$edit['post_excerpt'] = sanitize_textarea_field( $meta['caption'] );
				}

				wp_update_post( $edit );
			}

			return $checked;
		}

	}




endif; // class_exists check

