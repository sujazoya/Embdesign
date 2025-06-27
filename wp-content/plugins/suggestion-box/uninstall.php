<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table_name = $wpdb->prefix . SUGGESTION_BOX_TABLE;

$wpdb->query("DROP TABLE IF EXISTS $table_name");