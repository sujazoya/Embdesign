<?php

class WP_Users_CSV_Exporter {

public function __construct() {
    if( ! get_option( 'fea_csv_export' ) ) return;

    add_action('restrict_manage_users', [$this, 'add_export_button']);
    add_action('admin_init', [$this, 'handle_csv_export']);
}

public function add_export_button($which) {
    if (!current_user_can('list_users')) {
        return;
    }

    $selected_role = $_GET['role'] ?? '';
    ?>
    <input type="submit" name="export_users_csv" class="button button-primary" value="Export Users CSV" />
    <?php
}

public function handle_csv_export() {
    if (!is_admin() || !current_user_can('list_users') || !isset($_GET['export_users_csv'])) {
        return;
    }

    $role = sanitize_text_field($_GET['role'] ?? '');
    $args = [
        'role'    => $role !== '' ? $role : null,
        'orderby' => 'ID',
        'order'   => 'ASC',
        'number'  => -1,
    ];

    $users = get_users($args);

    if (empty($users)) {
        wp_die('No users found for export.');
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=users_export.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    $default_fields = ['ID', 'Username', 'Email', 'Display Name', 'Registered Date'];
    
    $acf_fields = $fields = [];
    if( function_exists('acf_get_field_groups') ) {
        $field_groups = acf_get_field_groups( [
            'user_id' => 'new',
            'user_form' => 'all'          
        ] );
        foreach( $field_groups as $field_group ) {
            $fields = array_merge( $fields, acf_get_fields( $field_group ) );
        }
        if( $fields ) {
            foreach( $fields as $field ) {
                if( !in_array($field['name'], $acf_fields, true) ) {
                    $acf_fields[] = $field['label'];
                }
            }
        }
    }

    // Headers
    $headers = array_merge($default_fields);

    if( !empty($acf_fields) ) {
        $headers = array_merge($headers, $acf_fields);
    }


    fputcsv($output, $headers);

    foreach ($users as $user) {
        $row = [
            $user->ID,
            $user->user_login,
            $user->user_email,
            $user->display_name,
            $user->user_registered,
        ];

        // Add ACF fields
        if( !empty($fields) ) {

            foreach( $fields as $field ) {
                $value = get_field($field['name'], 'user_' . $user->ID);
                if( is_array($value) ) {
                    if( ! empty( $value['url'] ) ){
                        $value = $value['url'];
                    } else {
                        $value = $this->flatten_array($value);  
                    }
                }
                $row[] = $value;
            }
        }

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

private function get_all_user_meta_keys($users) {
    $keys = [];

    foreach ($users as $user) {
        $meta = get_user_meta($user->ID);
        foreach ($meta as $key => $value) {
            if (!in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }
    }

    return $keys;
}

private function flatten_array($array, $prefix = '') {
    $flat = [];
    foreach ($array as $key => $value) {
        $full_key = $prefix ? "{$prefix}_{$key}" : $key;
        if (is_array($value)) {
            $flat[] = $this->flatten_array($value, $full_key);
        } else {
            $flat[] = "{$full_key}: {$value}";
        }
    }
    return implode(' | ', $flat);
}
}

new WP_Users_CSV_Exporter();
