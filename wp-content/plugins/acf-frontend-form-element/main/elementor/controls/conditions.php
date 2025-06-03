<?php
namespace Frontend_Admin\Elementor\Controls;


if ( ! defined( 'ABSPATH' ) ) exit;

class Conditions_Control extends \Elementor\Base_Data_Control {
    public function get_type() {
        return 'fea_conditions_control';
    }

    public function enqueue() {
        //
    }

    public function content_template() {
        ?>
        <div class="fea-conditions-container">
            <button class="button manage-conditions"><?php esc_html_e( 'Manage Conditions', 'plugin-domain' ); ?></button>
            <textarea class="conditions-json" name="{{ data.name }}" style="display:none;"></textarea>
        </div>

        <!-- Modal -->
        <div class="fea-conditions-modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h3>Manage Conditions</h3>
                <div class="or-groups"></div>
                <button class="button add-or-group">Add OR Group</button>
                <button class="button save-conditions">Save Conditions</button>
            </div>
        </div>
        <?php
    }

    public function sanitize_settings( $settings ) {
        if ( isset( $settings['conditions'] ) && is_array( $settings['conditions'] ) ) {
            return json_encode( $settings['conditions'] );
        }
        return '';
    }
}

