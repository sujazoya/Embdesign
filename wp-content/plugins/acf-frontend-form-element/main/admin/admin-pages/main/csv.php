<?php
namespace Frontend_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class CSV_Settings {



	public function get_settings_fields( $field_keys ) {
		//show CSV download button on users page
        $local_fields = array(
            'fea_csv_export' => array(
                'type'        => 'checkbox',
                'label'       => __( 'CSV Export', 'frontend-admin' ),
                'description' => __( 'Enable CSV export', 'frontend-admin' ),
                'default'     => 0,
                'choices'     => array(
                    'users' => __( 'Users Page', 'frontend-admin' ),
                ),
            ),
            
        );      
		return $local_fields;
	}

	public function __construct() {
		add_filter( 'frontend_admin/csv_fields', array( $this, 'get_settings_fields' ) );
	}

}
new CSV_Settings( $this );
