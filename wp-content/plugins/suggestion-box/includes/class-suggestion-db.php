<?php
/**
 * Database handler for Suggestion Box plugin
 * 
 * @package Suggestion_Box
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Suggestion_Box_DB {
    
    /**
     * Plugin activation handler
     */
    public static function activate() {
        global $wpdb;
        
        $instance = new self();
        $instance->create_table();
        flush_rewrite_rules();
        
        // Add default options if needed
        if (!get_option('suggestion_box_flush_rewrite_rules')) {
            add_option('suggestion_box_flush_rewrite_rules', true);
        }
    }

    /**
     * Plugin deactivation handler
     */
    public static function deactivate() {
        flush_rewrite_rules();
        delete_option('suggestion_box_flush_rewrite_rules');
    }

    /**
     * Create the database table
     */
    public function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggestion_box';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            suggestion text NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $result = dbDelta($sql);
        
        // Log any errors
        if (!empty($wpdb->last_error)) {
            error_log('Suggestion Box database error: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }

    /**
     * Insert a new suggestion
     * 
     * @param array $data Suggestion data
     * @return int|false The number of rows inserted, or false on error
     */
    public function insert_suggestion($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggestion_box';
        
        // Validate data
        if (empty($data['name']) || empty($data['email']) || empty($data['suggestion'])) {
            return false;
        }
        
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'suggestion' => sanitize_textarea_field($data['suggestion']),
            'status' => 'pending'
        );
        
        $format = array('%s', '%s', '%s', '%s');
        
        $result = $wpdb->insert($table_name, $insert_data, $format);
        
        if (false === $result) {
            error_log('Suggestion Box insert error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Get suggestions by status
     * 
     * @param string $status Suggestion status
     * @param int $limit Number of suggestions to return
     * @return array Array of suggestions
     */
    public function get_suggestions($status = 'pending', $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggestion_box';
        $status = in_array($status, ['pending', 'approved', 'rejected']) ? $status : 'pending';
        $limit = absint($limit);
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC LIMIT %d",
                $status,
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Update suggestion status
     * 
     * @param int $id Suggestion ID
     * @param string $status New status
     * @return bool True on success, false on failure
     */
    public function update_status($id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggestion_box';
        $id = absint($id);
        $status = in_array($status, ['pending', 'approved', 'rejected']) ? $status : 'pending';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
        
        if (false === $result) {
            error_log('Suggestion Box status update error: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }

    /**
     * Delete a suggestion
     * 
     * @param int $id Suggestion ID
     * @return bool True on success, false on failure
     */
    public function delete_suggestion($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggestion_box';
        $id = absint($id);
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
        
        if (false === $result) {
            error_log('Suggestion Box delete error: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
}