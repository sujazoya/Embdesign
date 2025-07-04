<?php
namespace Frontend_Admin\Field_Types;

if ( ! class_exists( 'upload_files' ) ) :

	class upload_files extends Field_Base {



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
			$this->name     = 'upload_files';
			$this->label    = __( 'Upload Files', 'acf-frontend-form-element' );
			$this->category = 'content';
			$this->defaults = array(
				'library'     => 'all',
				'min'         => 0,
				'max'         => 0,
				'min_width'   => 0,
				'min_height'  => 0,
				'min_size'    => 0,
				'max_width'   => 0,
				'max_height'  => 0,
				'max_size'    => 0,
				'resize_file' => 0,
				'mime_types'  => '',
				'insert'      => 'append',			
				'button_text' => __( 'Add Files', 'acf-frontend-form-element' ),
				'add_button_locations' => array( 'bottom' ),
				'click_event' => 'edit'
			);

			// actions
			add_action( 'wp_ajax_acf/fields/gallery/get_attachment', array( $this, 'ajax_get_attachment' ) );
			add_action( 'wp_ajax_nopriv_acf/fields/gallery/get_attachment', array( $this, 'ajax_get_attachment' ) );


			add_action( 'wp_ajax_acf/fields/gallery/get_sort_order', array( $this, 'ajax_get_sort_order' ) );
			add_action( 'wp_ajax_nopriv_acf/fields/gallery/get_sort_order', array( $this, 'ajax_get_sort_order' ) );

			// add_filter
			add_filter( 'acf/prepare_field/type=gallery', array( $this, 'prepare_gallery_field' ), 5 );

			$multiple_files = array( 'gallery', 'upload_files', 'product_images' );
			foreach ( $multiple_files as $type ) {
				add_filter( 'acf/pre_update_value/type=' . $type, array( $this, 'update_attachments_value' ), 10, 4 );
				add_action( 'acf/render_field_settings/type=' . $type, array( $this, 'upload_button_text_setting' ) );
				add_filter( 'acf/validate_value/type=' . $type, array( $this, 'validate_files_value' ), 5, 4 );

			}
		}

		function prepare_gallery_field( $field ) {
			global $fea_form;
			if( ! $fea_form ) return $field;
			
	
				$field['type'] = 'upload_files';
				$field         = $this->prepare_field( $field );

			return $field;
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
			$uploader = acf_get_setting( 'uploader' );
		
			// enqueue
			if ( $uploader == 'basic' ) {
				if ( isset( $field['wrapper']['class'] ) ) {
					$field['wrapper']['class'] .= ' acf-uploads';
				} else {
					$field['wrapper']['class'] = 'acf-uploads';
				}
			}
			$field['wrapper']['class'] .= ' image-field';
			if ( empty( $field['max_width'] ) ) {
				$field['max_width'] = 1920;
			}
			if ( empty( $field['max_height'] ) ) {
				$field['max_height'] = 1080;
			}

			//if  no button text, add the default
			if ( empty( $field['button_text'] ) ) {
				$field['button_text'] = __( 'Add Images', 'acf-frontend-form-element' );
			}
			return $field;
		}
		/*
		*  input_admin_enqueue_scripts
		*
		*  description
		*
		*  @type    function
		*  @date    16/12/2015
		*  @since    5.3.2
		*
		*  @param    $post_id (int)
		*  @return    $post_id (int)
		*/

		function input_admin_enqueue_scripts() {
			// localize
			acf_localize_text(
				array(
					'Add Image to Gallery'      => __( 'Add Image to Gallery', 'acf-frontend-form-element' ),
					'Maximum selection reached' => __( 'Maximum selection reached', 'acf-frontend-form-element' ),
				)
			);
		}
		function update_attachments_value( $checked, $value, $post_id = false, $field = false ) {
			if ( isset( $value['{file-index}'] ) ) {
				unset( $value['{file-index}'] );
			}

			if ( ! is_array( $value ) || ! $value ) {
				return $checked;
			}

			if ( is_numeric( $post_id ) && $value ) {
				$post = get_post( $post_id );
				if ( wp_is_post_revision( $post ) ) {
					$post_id = $post->post_parent;
				}
			}
			$new_value = array();
			global $fea_form;
			foreach ( $value as $index => $attachment ) {
				$meta = $fea_form['record']['fields']['file_data'][$field['name']][$attachment] ?? false;

				if( empty( $meta ) ) $meta = $fea_form['record']['fields']['file_data'][$field['name']][$index] ?? false;

				if( empty( $meta ) ){
					continue;
				}
				
				if ( isset( $meta['alt'] ) ) {
					update_post_meta( $attachment, '_wp_attachment_image_alt', $meta['alt'] );
				}

				$edit = array( 'ID' => $attachment );
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
				
				$attachment = (int) $attachment;
				acf_connect_attachment_to_post( $attachment, $post_id );
			}

			return $checked;
		}

		/*
		*  ajax_get_attachment
		*
		*  description
		*
		*  @type    function
		*  @date    13/12/2013
		*  @since    5.0.0
		*
		*  @param    $post_id (int)
		*  @return    $post_id (int)
		*/

		function ajax_get_attachment() {
			// options
			$options = acf_parse_args(
				$_POST,
				array(
					'post_id'    => 0,
					'attachment' => 0,
					'id'         => 0,
					'field_key'  => '',
					'nonce'      => '',
				)
			);

			// validate
			if ( ! feadmin_verify_ajax() ) {
				die();
			}

			// bail early if no id
			if ( ! $options['id'] ) {
				die();
			}

			// load field
			$field = acf_get_field( $options['field_key'] );

			// bali early if no field
			if ( ! $field ) {
				die();
			}

			// render
			$this->render_attachment( $field, $options['id'] );
			die;

		}


		/*
		*  ajax_get_sort_order
		*
		*  description
		*
		*  @type    function
		*  @date    13/12/2013
		*  @since    5.0.0
		*
		*  @param    $post_id (int)
		*  @return    $post_id (int)
		*/

		function ajax_get_sort_order() {
			// vars
			$r     = array();
			$order = 'DESC';
			$args  = acf_parse_args(
				$_POST,
				array(
					'ids'       => 0,
					'sort'      => 'date',
					'field_key' => '',
					'nonce'     => '',
				)
			);

			// validate
			if ( ! wp_verify_nonce( $args['nonce'], 'acf_nonce' ) ) {

				wp_send_json_error();

			}

			// reverse
			if ( $args['sort'] == 'reverse' ) {

				$ids = array_reverse( $args['ids'] );

				wp_send_json_success( $ids );

			}

			if ( $args['sort'] == 'title' ) {

				$order = 'ASC';

			}

			// find attachments (DISTINCT POSTS)
			$ids = get_posts(
				array(
					'post_type'   => 'attachment',
					'numberposts' => -1,
					'post_status' => 'any',
					'post__in'    => $args['ids'],
					'order'       => $order,
					'orderby'     => $args['sort'],
					'fields'      => 'ids',
				)
			);

			// success
			if ( ! empty( $ids ) ) {

				wp_send_json_success( $ids );

			}

			// failure
			wp_send_json_error();

		}

		function upload_button_text_setting( $field ) {
			acf_render_field_setting(
				$field,
				array(
					'label'       => __( 'Button Text' ),
					'name'        => 'button_text',
					'type'        => 'text',
					'placeholder' => __( 'Add Image', 'acf-frontend-form-element' ),
				)
			);
		
		}

		/*
		*  render_attachment
		*
		*  description
		*
		*  @type    function
		*  @date    13/12/2013
		*  @since    5.0.0
		*
		*  @param    $field (array)
		*  @param    $post_id (int)
		*/

		function render_attachment( $field, $id = 0 ) {
			// vars
			$attachment = wp_prepare_attachment_for_js( $id );
			$compat     = get_compat_media_markup( $id );
			$compat     = $compat['item'];
			$prefix     = 'attachments[' . $id . ']';
			$thumb      = '';
			$dimentions = '';

			// thumb
			if ( isset( $attachment['thumb']['src'] ) ) {

				// video
				$thumb = $attachment['thumb']['src'];

			} elseif ( isset( $attachment['sizes']['thumbnail']['url'] ) ) {

				// image
				$thumb = $attachment['sizes']['thumbnail']['url'];

			} elseif ( $attachment['type'] === 'image' ) {

				// svg
				$thumb = $attachment['url'];

			} else {

				// fallback (perhaps attachment does not exist)
				$thumb = wp_mime_type_icon();

			}

			// dimentions
			if ( $attachment['type'] === 'audio' ) {

				$dimentions = __( 'Length', 'acf-frontend-form-element' ) . ': ' . $attachment['fileLength'];

			} elseif ( ! empty( $attachment['width'] ) ) {

				$dimentions = $attachment['width'] . ' x ' . $attachment['height'];

			}

			if ( ! empty( $attachment['filesizeHumanReadable'] ) ) {

				$dimentions .= ' (' . $attachment['filesizeHumanReadable'] . ')';

			}

			?>
		<div class="fea-uploads-side-info">
			<img src="<?php esc_attr_e( $thumb ); ?>" alt="<?php esc_attr_e( $attachment['alt'] ); ?>" />
			<p class="filename"><strong><?php esc_html_e( $attachment['filename'] ); ?></strong></p>
			<p class="uploaded"><?php esc_html_e( $attachment['dateFormatted'] ); ?></p>
			<p class="dimensions"><?php esc_html_e( $dimentions ); ?></p>
			<p class="actions">
				<a href="#" class="fea-uploads-edit" data-id="<?php esc_attr_e( $id ); ?>"><?php esc_html_e( 'Edit', 'acf-frontend-form-element' ); ?></a>
				<a href="#" class="fea-uploads-remove" data-id="<?php esc_attr_e( $id ); ?>"><?php esc_html_e( 'Remove', 'acf-frontend-form-element' ); ?></a>
			</p>
		</div>
		<table class="form-table">
			<tbody>
				<?php

				fea_instance()->form_display->render_field_wrap(
					array(
						// 'key'        => "{$field['key']}-title",
						'name'   => 'title',
						'prefix' => $prefix,
						'type'   => 'text',
						'label'  => __( 'Title', 'acf-frontend-form-element' ),
						'value'  => $attachment['title'],
					),
					'tr'
				);

				fea_instance()->form_display->render_field_wrap(
					array(
						// 'key'        => "{$field['key']}-caption",
						'name'   => 'capt',
						'prefix' => $prefix,
						'type'   => 'textarea',
						'label'  => __( 'Caption', 'acf-frontend-form-element' ),
						'value'  => $attachment['caption'],
					),
					'tr'
				);

				fea_instance()->form_display->render_field_wrap(
					array(
						// 'key'        => "{$field['key']}-alt",
						'name'   => 'alt',
						'prefix' => $prefix,
						'type'   => 'text',
						'label'  => __( 'Alt Text', 'acf-frontend-form-element' ),
						'value'  => $attachment['alt'],
					),
					'tr'
				);

				fea_instance()->form_display->render_field_wrap(
					array(
						// 'key'        => "{$field['key']}-description",
						'name'   => 'description',
						'prefix' => $prefix,
						'type'   => 'textarea',
						'label'  => __( 'Description', 'acf-frontend-form-element' ),
						'value'  => $attachment['description'],
					),
					'tr'
				);

				?>
			</tbody>
		</table>
			<?php

			esc_html_e( $compat );

		}


		/*
		*  get_attachments
		*
		*  This function will return an array of attachments for a given field value
		*
		*  @type    function
		*  @date    13/06/2014
		*  @since    5.0.0
		*
		*  @param    $value (array)
		*  @return    $value
		*/

		function get_attachments( $value ) {
			// bail early if no value
			if ( empty( $value ) ) {
				return false;
			}

			if ( is_array( $value ) ) {
				$ids = array();
				foreach ( $value as $attachment ) {
					if ( isset( $attachment['id'] ) ) {
						   $ids[] = $attachment['id'];
					}
				}
				if ( ! $ids ) {
					return false;
				}
				$post__in = $ids;
			} else {
				// force value to array
				$post__in = acf_get_array( $value );
			}

			// get posts
			$posts = acf_get_posts(
				array(
					'post_type' => 'attachment',
					'post__in'  => $post__in,
				)
			);

			// return
			return $posts;

		}

		public function add_item( $field ){
			//add a square div with a plus icon
			$placeholder = $field['placeholder_image'] ?? '';
			if( $placeholder ){
				$placeholder = wp_get_attachment_image_url( $placeholder, 'thumb' );
			}

			?>
			<div class="fea-uploads-attachment fea-uploads-add" data-id="0">
				<div class="thumbnail">
					<?php if( $placeholder ): ?>
					<img src="<?php echo $placeholder; ?>" />
					<?php else: ?>
					<i class="acf-icon -plus"></i>
					<?php endif; ?>
				</div>
			</div>
			<?php
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
			$uploader = acf_get_setting( 'uploader' );

			if ( $uploader == 'wp' && ! feadmin_edit_mode() ) {
				acf_enqueue_uploader();
			}

			$value = $field['value'];

			$click_event = $field['click_event'] ?? 'edit';

			// vars
			$atts = array(
				'id'                 => $field['id'],
				'class'              => "fea-uploads {$field['class']}",
				'data-library'       => $field['library'],
				'data-uploader'      => $uploader,
				'data-min'           => $field['min'],
				'data-max'           => $field['max'],
				'data-mime_types'    => $field['mime_types'],
				'data-insert'        => $field['insert'],
				'data-columns'       => 4,
				'data-allowed_types' => 'image, pdf',
				'data-resize'        => $field['resize_file'] ?? 0,
				'data-min_size'      => $field['min_size'],
				'data-min_width'     => $field['min_width'],
				'data-min_height'    => $field['min_height'],
				'data-max_size'      => $field['max_size'],
				'data-max_width'     => $field['max_width'],
				'data-max_height'    => $field['max_height'],
				'data-click_event'   => $click_event,
				'data-open_in_lightbox' => $field['open_in_lightbox'] ?? 0,
			);

			if ( ! empty( $field['open_in_lightbox'] ) ) {
				$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '-min';
				wp_enqueue_script( 'fea-lightbox', FEA_URL . 'assets/js/lightbox' . $min . '.js', array(), FEA_VERSION, true );
				wp_enqueue_style( 'fea-lightbox', FEA_URL . 'assets/css/lightbox' . $min . '.css', array(), FEA_VERSION );
			}


			$button_locations = $field['add_button_locations'] ?? array( 'bottom' );

			if( is_string( $button_locations ) ){
				$button_locations = explode( ',', $button_locations );
			}

			if ( ! $value && ! in_array( 'item', $button_locations ) ) {
				$atts['class'] .= 'acf-hidden';
			}

			$button_text = $field['button_text'] ?? __( 'Add Images', 'acf-frontend-form-element' );

			if ( isset( $value['{file-index}'] ) ) {
				unset( $value['{file-index}'] );
			}
			$default_icon = wp_mime_type_icon( 'application/pdf' );


			?>
		<div <?php acf_esc_attr_e( $atts ); ?>>
			
			<div class="acf-hidden">
				<?php
				acf_hidden_input(
					array(
						'name'  => $field['name'],
						'value' => '',
					)
				);
				?>
			</div>

			<?php if ( in_array( 'top', $button_locations ) ) : ?>
				<a href="#" class="acf-button button button-primary fea-uploads-add"><?php esc_html_e( $button_text ); ?></a>
			<?php endif; ?>
			
			<div class="fea-uploads-main">
				
				<div class="fea-uploads-attachments">
				<?php if( 'append' == $field['insert'] && in_array( 'item', $button_locations ) ): ?>
					<?php $this->add_item( $field ); ?>
				<?php endif; ?>
					
				<?php if ( $value ) : ?>

				<?php
				foreach ( $value as $i => $v ) :
					$i++;
					// bail early if no value
					if ( ! $v ) {
						continue;
					}

					if ( is_numeric( $v ) ) {
						$a = acf_get_attachment( $v );
						if ( ! $a ) {
							continue;
						}
					} else {
						if ( isset( $v['id'] ) ) {
							$a = acf_get_attachment( $v['id'] );
							if ( ! $a ) {
								continue;
							}
						} else {
							$a = $v;
						}
								$a['filename'] = wp_basename( $a['title'] );
								$a['type']     = '';
					}

					$a['class'] = 'fea-uploads-attachment';

					// thumbnail
					$thumbnail = acf_get_post_thumbnail( $a['id'], 'medium' );

					// remove filename if is image
					if ( 'image' == $a['type'] ) {
						$a['filename'] = '';
					}else{
						$thumbnail['url'] = $default_icon;
					}

					// class
					$a['class'] .= ' -' . $a['type'];

					if ( 'icon' == $thumbnail['type'] ) {

						$a['class'] .= ' -icon';

					}

					$extra_attr = 'data-href="'.esc_attr($a['url']).'"';
					if ( !empty( $field['open_in_lightbox'] ) && 'image' == $a['type'] ) {
						$extra_attr .= ' data-lightbox="'.esc_attr( $field['key'] ).'"';
						$extra_attr .= ' data-title="'.esc_attr( $a['title'] ).'"';						
					}

					if( 'download' == $click_event ){
						$extra_attr .= ' data-download=true';
					}
					?>
							<div <?php echo $extra_attr; ?> class="<?php esc_attr_e( $a['class'] ); ?>" data-id="<?php esc_attr_e( $a['id'] ); ?>">
					
					<?php
					acf_hidden_input(
						array(
							'name'  => $field['name'] . '[' . $a['id'] . ']',
							'value' => $a['id'],
						)
					);
					?>
					<div class="thumbnail">
						
						<img src="<?php esc_attr_e( $thumbnail['url'] ); ?>" alt="" title="<?php esc_attr_e( $a['title'] ); ?>"/>
						
					</div>
					<?php if ( $a['filename'] ) : ?>
									<div class="filename"><?php esc_html_e( acf_get_truncated( $a['filename'], 30 ) ); ?></div>    
					<?php endif; ?>
						<div class="actions">
							<a class="acf-icon -cancel small dark fea-uploads-remove" href="#" data-id="<?php esc_attr_e( $a['id'] ); ?>" title="<?php esc_attr_e( 'Remove', 'acf-frontend-form-element' ); ?>"></a>
						</div>
					<?php
					if( 'edit' == $click_event ){ 
					
						$prefix = 'acff[file_data][' . $field['key'] . '][' . $a['id'] . ']';
				
						fea_instance()->form_display->render_meta_fields( $prefix, $a['id'], false );
					}
					
					?>
					</div>
				<?php endforeach; ?>
						
				
				<?php endif; ?>
							
				<?php if( 'prepend' == $field['insert'] && in_array( 'item', $button_locations ) ): ?>
					<?php $this->add_item( $field ); ?>
				<?php endif; ?>
					
				</div>

				<div class="image-preview-clone acf-hidden">
						<div class="thumbnail">
							<img data-default="<?php esc_attr_e( $default_icon ); ?>" src="" alt="" title=""/>
						</div>
					<div class="actions">
						<a class="acf-icon -cancel dark small fea-uploads-remove" href="#" title="<?php esc_attr_e( 'Remove', 'acf-frontend-form-element' ); ?>"></a>
					</div>
					<?php if ( $uploader == 'basic' ) { ?>
						<div class="uploads-progress"><div class="percent">0%</div><div class="bar"></div></div>
					<?php } ?>
				</div>

				<?php
				if( 'edit' == $click_event ){ 
					
					$prefix = 'acff[file_data][' . $field['key'] . '][{file-index}]';
					fea_instance()->form_display->render_meta_fields( $prefix, 'clone', false );
				}
				?>
				
			</div>
			
			<div class="fea-uploads-side">
			<div class="fea-uploads-side-inner">
					
				<div class="fea-uploads-side-data"></div>
								
				<div class="fea-uploads-toolbar">
					
					<ul class="acf-hl">
						<li>
							<a href="#" class="acf-button button fea-uploads-close"><?php esc_html_e( 'Close', 'acf-frontend-form-element' ); ?></a>
						</li>
						<li class="acf-fr">
							<a class="acf-button button button-primary fea-uploads-update" href="#"><?php esc_html_e( 'Update', 'acf-frontend-form-element' ); ?></a>
						</li>
					</ul>
					
				</div>
				
			</div>    
			</div>
			
		</div>

		<div class="fea-uploads-toolbar">
					
			<ul class="acf-hl">
				<?php if ( $uploader == 'basic' ) : ?>
					<li>
						<label class="acf-basic-uploader file-drop">
					<?php
					$file_attrs = array(
						'name'     => 'upload_files_input',
						'id'       => $field['id'],
						'class'    => 'images-preview',
						'multiple' => 'true',
					);
					if ( $field['max'] && is_array( $value ) && count( $value ) >= $field['max'] ) {
						$file_attrs['disabled'] = 'disabled';
					}
					if ( $field['mime_types'] ) {
						$file_attrs['accept'] = $field['mime_types'];
					}else{
						$file_attrs['accept'] = 'image/*,application/pdf';
					}

					acf_file_input( $file_attrs );
					?>
							
						</label>
					</li>
				<?php endif; ?>

					<?php if ( in_array( 'bottom', $button_locations ) ) : ?>
					<li>
						<a href="#" class="acf-button button button-primary fea-uploads-add"><?php esc_html_e( $button_text ); ?></a>
					</li>
					<?php endif; ?>
					<?php $this->render_order( $field ); ?>
			</ul>
			
		</div>
			<?php

		}


		/*
		*  render order
		*
		*  This function will render the order input
		*
		*/
		public function render_order( $field ) {
			$show_sorting_field = $field['show_sorting_field'] ?? true;
			if ( ! $show_sorting_field ) {
				return;
			}
			?>
				<li class="acf-fr <?php if( ! $field['value'] ) echo 'acf-hidden' ?>">
					<select class="fea-uploads-sort">
						<option value=""><?php esc_html_e( 'Bulk actions', 'acf-frontend-form-element' ); ?></option>
						<option value="date"><?php esc_html_e( 'Sort by date uploaded', 'acf-frontend-form-element' ); ?></option>
						<option value="modified"><?php esc_html_e( 'Sort by date modified', 'acf-frontend-form-element' ); ?></option>
						<option value="title"><?php esc_html_e( 'Sort by title', 'acf-frontend-form-element' ); ?></option>
						<option value="reverse"><?php esc_html_e( 'Reverse current order', 'acf-frontend-form-element' ); ?></option>
					</select>
				</li>
			<?php
		}

		/*
		*  render_field_settings()
		*
		*  Create extra options for your field. This is rendered when editing a field.
		*  The value of $field['name'] can be used (like bellow) to save extra data to the $field
		*
		*  @type    action
		*  @since    3.6
		*  @date    23/01/13
		*
		*  @param    $field    - an array holding all the field's data
		*/

		function render_field_settings( $field ) {
			// clear numeric settings
			$clear = array(
				'min',
				'max',
				'min_width',
				'min_height',
				'min_size',
				'max_width',
				'max_height',
				'max_size',
			);

			foreach ( $clear as $k ) {

				if ( empty( $field[ $k ] ) ) {
					$field[ $k ] = '';
				}
			}

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Add Image Button Locations', 'acf' ),
					'instructions' => '',
					'type'         => 'checkbox',
					'name'         => 'add_button_locations',
					'choices'      => array(
						'top'    => __( 'Top', 'acf' ),
						'bottom' => __( 'Bottom', 'acf' ),
						'item'   => __( 'Item', 'acf' ),
					),
				
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'When User Clicks Image...', 'acf' ),
					'instructions' => '',
					'type'         => 'radio',
					'name'         => 'click_event',
					'choices'      => array(
						'edit' => __( 'Edit Image', 'acf-frontend-form-element' ),
						'download'    => __( 'Download Image', 'acf-frontend-form-element' ),
					),
				
				)
			);

			//open images in lightbox
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Open Images in Lightbox', 'acf' ),
					'instructions' => '',
					'type'         => 'true_false',
					'name'         => 'open_in_lightbox',
					'ui'           => 1,
					'ui_on_text'   => __( 'Yes', 'acf-frontend-form-element' ),
					'ui_off_text'  => __( 'No', 'acf-frontend-form-element' ),
				)
			);

			// min
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Minimum Selection', 'acf-frontend-form-element' ),
					'instructions' => '',
					'type'         => 'number',
					'name'         => 'min',
				)
			);

			// max
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Maximum Selection', 'acf-frontend-form-element' ),
					'instructions' => '',
					'type'         => 'number',
					'name'         => 'max',
				)
			);

			// insert
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Insert', 'acf-frontend-form-element' ),
					'instructions' => __( 'Specify where new attachments are added', 'acf-frontend-form-element' ),
					'type'         => 'select',
					'name'         => 'insert',
					'choices'      => array(
						'append'  => __( 'Append to the end', 'acf-frontend-form-element' ),
						'prepend' => __( 'Prepend to the beginning', 'acf-frontend-form-element' ),
					),
				)
			);
			// library
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Library', 'acf-frontend-form-element' ),
					'instructions' => __( 'Limit the media library choice', 'acf-frontend-form-element' ),
					'type'         => 'radio',
					'name'         => 'library',
					'layout'       => 'horizontal',
					'choices'      => array(
						'all'        => __( 'All', 'acf-frontend-form-element' ),
						'uploadedTo' => __( 'Uploaded to post', 'acf-frontend-form-element' ),
					),
				)
			);

			// min
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Minimum', 'acf-frontend-form-element' ),
					'instructions' => __( 'Restrict which images can be uploaded', 'acf-frontend-form-element' ),
					'type'         => 'text',
					'name'         => 'min_width',
					'prepend'      => __( 'Width', 'acf-frontend-form-element' ),
					'append'       => 'px',
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'   => '',
					'type'    => 'text',
					'name'    => 'min_height',
					'prepend' => __( 'Height', 'acf-frontend-form-element' ),
					'append'  => 'px',
					'_append' => 'min_width',
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'   => '',
					'type'    => 'text',
					'name'    => 'min_size',
					'prepend' => __( 'File size', 'acf-frontend-form-element' ),
					'append'  => 'MB',
					'_append' => 'min_width',
				)
			);

			// max
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Maximum', 'acf-frontend-form-element' ),
					'instructions' => __( 'Restrict which images can be uploaded', 'acf-frontend-form-element' ),
					'type'         => 'text',
					'name'         => 'max_width',
					'prepend'      => __( 'Width', 'acf-frontend-form-element' ),
					'append'       => 'px',
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'   => '',
					'type'    => 'text',
					'name'    => 'max_height',
					'prepend' => __( 'Height', 'acf-frontend-form-element' ),
					'append'  => 'px',
					'_append' => 'max_width',
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'   => '',
					'type'    => 'text',
					'name'    => 'max_size',
					'prepend' => __( 'File size', 'acf-frontend-form-element' ),
					'append'  => 'MB',
					'_append' => 'max_width',
				)
			);

		
			//resize or throw error
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Resize Image or Throw Error', 'acf-frontend-form-element' ),
					'instructions' => __( 'Resize the image to fit within the maximum dimensions', 'acf-frontend-form-element' ),
					'type'         => 'true_false',
					'name'         => 'resize_file',
					'ui'           => 1,
					'ui_on_text'   => __( 'Resize', 'acf-frontend-form-element' ),
					'ui_off_text'  => __( 'Throw Error', 'acf-frontend-form-element' ),
				)
			);
		}


		/*
		*  format_value()
		*
		*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
		*
		*  @type    filter
		*  @since    3.6
		*  @date    23/01/13
		*
		*  @param    $value (mixed) the value which was loaded from the database
		*  @param    $post_id (mixed) the $post_id from which the value was loaded
		*  @param    $field (array) the field array holding all the field options
		*
		*  @return    $value (mixed) the modified value
		*/

		/*
			 function format_value( $value, $post_id, $field ) {

		// bail early if no value
		if( empty($value) ) return false;

		// get posts
		$posts = $this->get_attachments($value);


		// update value to include $post
		foreach( array_keys($posts) as $i ) {

			$posts[ $i ] = acf_get_attachment( $posts[ $i ] );

		}


		// return
		return $posts;

		} */


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
		function validate_files_value( $valid, $value, $field, $input ) {
			if ( isset( $value['{file-index}'] ) ) {
				unset( $value['{file-index}'] );
			}

			if ( empty( $value ) || ! is_array( $value ) ) {
				if ( $field['required'] ) {
					return sprintf( __( '%s value is required.', 'acf-frontend-form-element' ), $field['label'] );
				}
				$value = array();
			}

			if ( is_array( $value ) && count( $value ) < $field['min'] ) {

				$valid = _n( '%1$s requires at least %2$s selection', '%1$s requires at least %2$s selections', $field['min'], 'acf' );
				$valid = sprintf( $valid, $field['label'], $field['min'] );

			}

			return $valid;

		}





		/*
		*  update_single_value()
		*
		*  This filter is appied to the $value before it is updated in the db
		*
		*  @type    filter
		*  @since    3.6
		*  @date    23/01/13
		*
		*  @param    $value - the value which will be saved in the database
		*  @param    $post_id - the $post_id of which the value will be saved
		*  @param    $field - the field array holding all the field options
		*
		*  @return    $value - the modified value
		*/

		function update_single_value( $value ) {
			// numeric
			if ( is_numeric( $value ) ) {
				return $value;
			}

			// array?
			if ( is_array( $value ) && isset( $value['ID'] ) ) {
				return $value['ID'];
			}

			// object?
			if ( is_object( $value ) && isset( $value->ID ) ) {
				return $value->ID;
			}

			// return
			return $value;

		}


	}

endif; // class_exists check
