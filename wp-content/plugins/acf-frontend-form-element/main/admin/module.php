<?php
namespace Frontend_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'Admin_Settings' ) ) :

	class Admin_Settings {


		private $tabs = array();

		public function plugin_page() {
			 global $frontend_admin_settings, $fea_instance;

			$frontend_admin_settings = add_menu_page( 'Frontend Admin', 'Frontend Admin', 'manage_options',  'fea-settings', array( $this, 'admin_settings_page' ), 'dashicons-frontend', '87.87778' );
			add_submenu_page(  'fea-settings', __( 'Settings', 'acf-frontend-form-element' ), __( 'Settings', 'acf-frontend-form-element' ), 'manage_options',  'fea-settings', '', 0 );
		}

		function admin_settings_page() {
			global $_active_tab;
			$_active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'welcome';
			?>

			<h2 class="nav-tab-wrapper">
			<?php
			$this->settings_tabs();
			?>
			</h2>
			<?php
			$this->settings_render_options_page();
		}


		public function settings_tabs() {
			global $_active_tab;
			foreach ( $this->tabs as $name => $label ) {

				?>
				<a class="nav-tab <?php echo $_active_tab == $name || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( '?page=' .  'fea-settings&tab=' . $name ) ); ?>"><?php esc_html_e( $label, 'acf-frontend-form-element' ); ?> </a>
				<?php
			}
		}

		public function get_form( $tab ){
			$fields = apply_filters( 'frontend_admin/' . $tab . '_fields', array() );
			
			if ( ! $fields ) return false;

			foreach ( $fields as $key => $field ) {
				$field['key']         = $key;
				$field['name']        = $key;
				$field['prefix']      = 'acff[admin_options]';
				$field['value']       = get_option( $field['key'] );
				$fields[ $key ] = $field;

			}
			return array(
				'id' => 'fea_admin_' . $tab,
				'admin_options'  => 1,
				'hidden_fields'  => array(
					'admin_page' => $tab,
				),
				/* 'form_attributes' => [
					'class'	=> 'fea-admin-options',
				], */
				'_screen_id'     => 'options',
				'fields'  => $fields,
				'submit_value'   => __( 'Save Settings', 'acf-frontend-form-element' ),
				'default_submit_button' => 1,
				'update_message' => __( 'Settings Saved', 'acf-frontend-form-element' ),
				'redirect'       => 'current',
				'kses'           => 0,
				'honeypot'       => 0,
				'no_record'      => 1,
			);
		}

		public function settings_render_options_page() {
			global $_active_tab, $fea_instance;

			if ( 'tools' == $_active_tab ) {
				$fea_instance->admin_tools->html();
				return;
			}
			if ( '' || 'welcome' == $_active_tab ) {
				?>
			<style>p.frontend-admin-text{font-size:20px}</style>
			<h3><?php esc_html_e( 'Hello and welcome', 'acf-frontend-form-element' ); ?></h3>

			<p class="frontend-admin-text">
				<?php printf( 
					esc_html__( 'Welcome to %s, the plugin that allows you to create and manage forms and content from the front end of your website.', 'acf-frontend-form-element' ), 
					esc_html( 'Frontend Admin' ) 
				); ?>
			</p>

			<p class="frontend-admin-text"><?php printf('Frontend Forms in Elementor Pro Off Canvas Widget');?></p>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/UII4uwpMP0I?si=wbE1l0spGseaQWXX" title="Frontend Forms in Elementor Pro Off Canvas Widget" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

			<p class="frontend-admin-text"><?php printf('Frontend Admin\'s Elementor Nestable Forms Widget');?></p>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/gxN5X54LNuk?si=xc6z6mwradx35CCt" title="Elementor Nestable Forms Widget" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>


			<p class="frontend-admin-text"><?php printf( esc_html( __( 'If this is your first time using %s, please watch this quick tutorial to help get you started.', 'acf-frontend-form-element' ) ), 'Frontend Admin' ); ?></p>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/ZR7UAegiljQ" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					
			
			<br>
			<p class="frontend-admin-text"><?php printf( wp_kses_post( __( 'If you have any questions at all please feel welcome to email support at <a href="mailto:%1$s"/>%1$s</a> or on whatsapp <a href="%2$s">%3$s</a>', 'acf-frontend-form-element' ) ), 'support@dynamiapps.com', esc_url( 'https://api.whatsapp.com/send?phone=972532323950' ), '+972-53-232-3950' ); ?> </p>
				
			<h3><?php esc_html_e( 'Frontend Admin Features', 'acf-frontend-form-element' ); ?></h3>
			<h4><?php esc_html_e( 'Enable Users To Add Content From The Front End Without Logging Into WordPress Using Frontend Admin', 'acf-frontend-form-element' ); ?></h4>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/s6FeL77i2iM?si=nOPwAK-O-JmyX9s_" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
			
			<h3><?php esc_html_e( 'WordPress Frontend Edits and Updates Using Frontend Admin', 'acf-frontend-form-element' ); ?></h3>

			<iframe width="560" height="315" src="https://www.youtube.com/embed/6yT2E2IV-JU?si=q1KDLkZ5UXEzc9UW" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
				<?php
			} else {
				foreach ( $this->tabs as $form_tab => $label ) {
					if ( $form_tab == $_active_tab ) {
						$form = $this->get_form( $form_tab );
						$admin_fields = apply_filters( 'frontend_admin/' . $form_tab . '_fields', array() );
						if ( $form ) {
							$fea_instance->form_display->render_form( $form );
						} else {
							if ( in_array( $form_tab, array( 'payments', 'pdf' ) ) ) {
								if ( isset( $_POST['action'] ) && $_POST['action'] == 'fea_install_plugin' ) {
									$this->install_addon( $form_tab );
								} else {
									$this->addon_form( $form_tab );
								}
							}
						}
					}
				}
			}
		}

		public function addon_form( $addon ) {
			?>
				<form style="margin:20px 5px;" class="frontend-admin-addon-form" method="post" action="">
			<?php
			if ( fea_is_plugin_installed( $addon ) ) {
				echo '<input type="hidden" name="action" value="frontend_admin_activate_plugin"/>';
				if( ! fea_is_plugin_active( $addon ) ){
					$submit_value = "Activate $addon module";
				}else{
					$submit_value = "Deactivate $addon module";
				}
			} else {
				echo '<input type="hidden" name="action" value="fea_install_plugin"/>';
				$submit_value = "Install $addon module";
			}
				echo '<button type="submit" class="button">'. esc_html( __( $submit_value, 'acf-frontend-form-element' ) ) .'</button>';
			?>
				<input type="hidden" name="addon" value="<?php esc_attr_e( $addon ); ?>"/>
				<input type="hidden" name="nonce" value="<?php esc_attr_e( wp_create_nonce( 'frontend-admin-addon' ) ); ?>" />
				</form>
			<?php
		}

		public function configs() {
			if ( ! get_option( 'frontend_admin_hide_wp_dashboard' ) ) {
				add_option( 'frontend_admin_hide_wp_dashboard', true );
				add_option( 'frontend_admin_hide_by', array_map( 'strval', array( 0 => 'user' ) ) );
			}
			if ( ! get_option( 'frontend_admin_save_submissions' ) ) {
				add_option( 'frontend_admin_save_submissions', true );
			}
			include_once __DIR__ . '/admin-pages/forms/custom-fields.php';
		}

		public function install_addon( $addon ) {
			$args = feadmin_parse_args(
				$_POST,
				array(
					'nonce' => '',
				)
			);

			if ( ! wp_verify_nonce( $args['nonce'], 'frontend-admin-addon' ) ) {
				esc_html_e( 'Authentication Error. Please try refreshing the page.', 'acf-frontend-form-element' );
			}

			$addon_zip = 'https://www.dynamiapps.com/wp-content/uploads/frontend-'.$addon.'.zip';

			
			$installed = fea_install_plugin( $addon_zip );
			if ( $installed ) {
				$addon_slug = fea_addon_slug( 'frontend-admin-' . $addon );

				$addon_folder = WP_PLUGIN_DIR . str_replace( '/frontend-' . $addon . '.php', '', $addon_slug );
				$fea_folder   = WP_PLUGIN_DIR . '/frontend-' . $addon;
				if ( ! file_exists( $fea_folder ) ) {
					rename( $addon_folder, $fea_folder );
				}

				$this->addon_form( $addon );
			}

		}

		public function activate_addon() {
			if ( empty( $_POST['action'] ) || $_POST['action'] != 'frontend_admin_activate_plugin' ) {
				return;
			}

			$args = feadmin_parse_args(
				$_POST,
				array(
					'nonce' => '',
					'addon' => '',
				)
			);

			if ( empty( $args['addon'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $args['nonce'], 'frontend-admin-addon' ) ) {
				esc_html_e( 'Authentication Error. Please try refreshing the page.', 'acf-frontend-form-element' );
			}

			$addon_slug = fea_addon_slug( 'frontend-admin-' . $args['addon'] );

			if ( $addon_slug ) {
				if( ! fea_is_plugin_active( $args['addon'] ) ){
					activate_plugin( $addon_slug );
				}else{
					deactivate_plugins( $addon_slug );
				}
			} else {
				esc_html_e( 'Addon Not Found', 'acf-frontend-form-element' );
			}
			wp_redirect(
				add_query_arg(
					array(
						'page' =>  'fea-settings',
						'tab'  => $args['addon'],
					),
					admin_url()
				)
			);

		}

		public function settings_sections() {
			include_once __DIR__ . '/admin-pages/submissions/crud.php';

			if( class_exists( 'Frontend_Admin_Payments' ) ){
				if( FEAP_VERSION < '3.1.2' )
					add_action( 'admin_notices', function(){
						?>
						<div class="notice notice-error">
							<p><?php _e( 'Please update Frontend Admin Payments to the latest version to use the new payment settings.', 'acf-frontend-form-element' ); ?></p>
						</div>
						<?php
					} );	
				else			
					require_once( __DIR__ . '/admin-pages/payments/settings.php');
			}
			
			include_once __DIR__ . '/admin-pages/plans/crud.php';
			include_once __DIR__ . '/admin-pages/subscriptions/crud.php'; 
			include_once __DIR__ . '/admin-pages/emails/crud.php';

			foreach ( $this->tabs as $tab => $label ) {
				if ( ! in_array( $tab, array( 'welcome', 'payments', 'pdf', 'tools', 'license' ) ) ) {
					$file_path = __DIR__ . "/admin-pages/main/$tab.php";
					if ( file_exists( $file_path ) ) {
						include_once $file_path;
					}
				}
			}
			

			//include_once __DIR__ . '/admin-pages/main/admin-tools.php';

			include_once __DIR__ . '/admin-pages/forms/settings.php';
			do_action( 'frontend_admin/admin_pages' );

		}

	
		public function scripts() {
			 $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '-min';
			wp_register_style( 'fea-modal', FEA_URL . 'assets/css/modal-min.css', array(), FEA_VERSION );
			wp_register_style( 'fea-public', FEA_URL . 'assets/css/frontend-admin' . $min . '.css', array( 'acf-input' ), FEA_VERSION );

			wp_register_script( 'fea-modal', FEA_URL . 'assets/js/modal' . $min . '.js', array( 'jquery' ), FEA_VERSION );
			wp_register_script( 'fea-public', FEA_URL . 'assets/js/frontend-admin' . $min . '.js', array( 'jquery', 'acf', 'acf-input' ), FEA_VERSION, true );

			wp_register_script( 'fea-password-strength', FEA_URL . 'assets/js/password-strength.min.js', array( 'password-strength-meter' ), FEA_VERSION, true );
			acf_localize_text( array( 'Passwords Match' => __( 'Passwords Match', 'acf-frontend-form-element' ) ) );
			add_action( 'admin_init', array( $this, 'activate_addon' ) );

			wp_register_style( 'fea-icon', FEA_URL . 'assets/css/icon' . $min . '.css', array(), FEA_VERSION );
			wp_register_script( 'fea-copy-code', FEA_URL . 'assets/js/copy-shortcode' . $min . '.js', array( 'jquery' ), FEA_VERSION, true );
			wp_register_script( 'fea-form-builder', FEA_URL . 'assets/js/admin' . $min . '.js', array( 'jquery' ), FEA_VERSION, true );

		}

		public function register_rest_endpoints() {
			register_rest_route(
				'frontend-admin/v1',
				'/frontend-forms',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_forms_list' ),
					'permission_callback' => function ( $request ) {
						return current_user_can('manage_options');
					},
				)
			);


			//acf field groups
			register_rest_route(
				'frontend-admin/v1',
				'/acf-field-groups',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_acf_field_groups' ),
					'permission_callback' => function ( $request ) {
						return current_user_can('manage_options');
					},
				)
			);

			//acf fields
			register_rest_route(
				'frontend-admin/v1',
				'/acf-fields',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_acf_fields' ),
					'permission_callback' => function ( $request ) {
						return current_user_can('manage_options');
					},
				)
			);
		}


		public function get_acf_field_groups() {
			$field_groups = acf_get_field_groups();
			$field_groups_list = [];

			if( $field_groups ){
				foreach ( $field_groups as $field_group ) {
					$field_groups_list[] = [ 'id' => $field_group['key'], 'text' => $field_group['title'] ];
				}
			}		
			return $field_groups_list;
		}

		public function get_acf_fields() {
			$field_groups = acf_get_field_groups();
			$fields_list = [];

			if( $field_groups ){
				foreach ( $field_groups as $field_group ) {
					//make select2 group
					$group_title = $field_group['title'];
					$group_children = [];

					$fields = acf_get_fields( $field_group['key'] );
					if( $fields ){
						foreach ( $fields as $field ) {
							$group_children[] = [ 'id' => $field['key'], 'text' => $field['label'] ];
						}
					}
					$fields_list[] = [ 'text' => $group_title, 'children' => $group_children ];
				}
			}		
			return $fields_list;
		}

		function get_forms_list() {
			$forms = get_posts(
				array(
					'post_type'      => 'admin_form',
					'posts_per_page' => '-1',
					'post_status'    => 'any',
				)
			);
			$forms_list = [];

			if( $forms ){
				foreach ( $forms as $form ) {
					$forms_list[] = [ 'value' => $form->ID, 'label' => $form->post_title ];
				}
			}		
			return $forms_list;
		}

		public function get_page_visibilty_fields(){
			$fields = [
				'limit_visibilty' => [
					'label' => __( 'Limit Visibilty', 'acf-frontend-form-element' ),
					'type'  => 'true_false',
					'name'  => 'limit_visibilty',
					'key'   => 'limit_visibilty',
				],
				'show_for' => [
					'label' => __( 'Show For', 'acf-frontend-form-element' ),
					'type'  => 'select',
					'choices' => [
						'logged_in' => __( 'Logged In', 'acf-frontend-form-element' ),
						'logged_out' => __( 'Logged Out', 'acf-frontend-form-element' ),
						'all' => __( 'All', 'acf-frontend-form-element' ),
					],
					'conditional_logic' => [
						[
							'field' => 'limit_visibilty',
							'operator' => '==',
							'value' => '1',
						],
					],
					'name'  => 'show_for',
					'key'   => 'show_for',
				],
				'show_for_roles' =>	[
					'label' => __( 'Show For Roles', 'acf-frontend-form-element' ),
					'type'  => 'select',
					'choices' => feadmin_get_user_roles(),
					'name'  => 'show_for_roles',
					'key'   => 'show_for_roles',
					'conditional_logic' => [
						[
							'field' => 'limit_visibilty',
							'operator' => '==',
							'value' => '1',
						],
						[
							'field' => 'show_for',
							'operator' => '==',
							'value' => 'logged_in',
						],
					],					
					'multiple' => true,
					'ui' => 1,
				],
				'redirect_to' => [
					'label' => __( 'Redirect To', 'acf-frontend-form-element' ),
					'type'  => 'url',
					'name'  => 'redirect_to',
					'key'   => 'redirect_to',
					'instructions' => __( 'Enter the URL to redirect to when the page is not visible', 'acf-frontend-form-element' ),
					'placeholder' => 'https://',
					'conditional_logic' => [
						[
							'field' => 'limit_visibilty',
							'operator' => '==',
							'value' => '1',
						],
					],
				]

			];
			return $fields;
		}

		public function hide_page_metabox( $post ) {
			
			$fields = $this->get_page_visibilty_fields();

			$fea_instance = fea_instance();

			foreach( $fields as $key => $field ){
				$field['prefix'] = 'fea';
				$field['post_id'] = $post->ID;
				$field['value'] = get_post_meta( $post->ID, 'fea_' . $key, true );
				$fea_instance->form_display->render_field_wrap( $field );
			}

		}

		public function update_page_visibilty( $post_id ){
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
			if ( ! current_user_can( 'edit_post', $post_id ) ) return;
			if ( ! isset( $_POST['fea']['limit_visibilty'] ) ) return;
			
			$fields = $this->get_page_visibilty_fields();
			foreach( $fields as $key => $field ){
				if( isset( $_POST['fea'][ $field['key'] ] ) ){
					update_post_meta( $post_id, 'fea_' . $field['key'], $_POST['fea'][ $field['key'] ] );
				}
			}
		}

		public function redirect_users(){
			global $post, $fea_limit_visibility;

			if( ! isset( $post->ID ) ) return;

			$limit_visibilty = get_post_meta( $post->ID, 'fea_limit_visibilty', true );
			if( ! $limit_visibilty ) return;

			$show_for = get_post_meta( $post->ID, 'fea_show_for', true );
			$show_for_roles = get_post_meta( $post->ID, 'fea_show_for_roles', true );
			$redirect_to = get_post_meta( $post->ID, 'fea_redirect_to', true );

			$fea_limit_visibility = [
				'who_can_see' => $show_for,
				'by_role' => $show_for_roles
			];

			if( ! is_singular() ) return;


			if( ! $redirect_to ) $redirect_to = home_url();

			$is_logged_in = is_user_logged_in();

			if( 'logged_in' == $show_for && ! $is_logged_in ){
				wp_redirect( $redirect_to );
				exit;
			}

			if( 'logged_out' == $show_for && $is_logged_in ){
				wp_redirect( $redirect_to );
				exit;
			}

			if( 'all' == $show_for ) return;

			if( 'logged_in' == $show_for&& $is_logged_in ){
				if( ! $show_for_roles )	return;
				$user = wp_get_current_user();
				$user_roles = $user->roles;
				$intersect = array_intersect( $user_roles, $show_for_roles );
				if( ! $intersect ){
					wp_redirect( $redirect_to );
					exit;
				}
				
			}
		}


		public function __construct() {
			global $fea_instance;

			$this->tabs = array(
				'welcome'         => 'Welcome',
				'submissions'     => 'Submissions',
				'local_avatar'    => 'Local Avatar',
				'uploads_privacy' => 'Uploads Privacy',
				'dashboard'       => 'Dashboard',
				'apis'            => 'APIs',
				'csv'             => 'CSV',
				//'tools'           => 'Tools',
			);

			$this->tabs = apply_filters( 'frontend_admin/admin_tabs', $this->tabs );

			$this->settings_sections();

			add_action( 'wp_loaded', array( $this, 'scripts' ) );
			add_action( 'init', array( $this, 'configs' ) );
			add_action( 'admin_menu', array( $this, 'plugin_page' ), 15 );
			add_action( 'rest_api_init', array( $this, 'register_rest_endpoints' ) );

			//add page options to Show page based on rules
			add_action( 'add_meta_boxes', function(){
				$post_types = get_post_types( array( 'public' => true ) );
				add_meta_box( 'fea-hide-page', 'Frontend Admin', array( $this, 'hide_page_metabox' ), $post_types, 'side', 'high' );
			} );
			//update page visibility
			add_action( 'save_post', [ $this, 'update_page_visibilty' ] );

			//redirect users if page is set to not visible
			add_action( 'wp', [ $this, 'redirect_users' ] );
			

			add_filter( 'frontend_admin/forms/get_form', function( $form, $key ){
				if( ! $key ) return $form;
				$key = explode( 'fea_admin_', $key );

				if( isset( $key[1] ) ) return $this->get_form( $key[1] );

				return $form;
			}, 10, 2 );


		}
	}

	fea_instance()->admin_settings = new Admin_Settings();

endif;
