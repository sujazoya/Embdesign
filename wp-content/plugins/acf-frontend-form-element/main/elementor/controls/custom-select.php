<?php

namespace Frontend_Admin\Elementor\Controls;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}


class Custom_Select extends \Elementor\Control_Select2
{
	public function get_type()
	{
		return 'fea_select';
	}

	public function enqueue()
	{
		wp_enqueue_script( 'fea-editor' );
	}

	public function get_default_value()
	{
		return '';
	}

	public function content_template()
	{
		$control_uid = $this->get_control_uid();
		?>
                  <# var multiple = ( data.multiple ) ? 'multiple' : ''; #>
                  <div class="elementor-control-field">
                      <label for="<?php echo $control_uid; ?>" class="elementor-control-title">{{{ data.label }}}</label>
                      <div class="elementor-control-input-wrapper elementor-control-unit-5">
                          <select 
						  	id="<?php echo $control_uid; ?>" 
							class="custom-control-select elementor-control-tag-area" 
							style="width:100%" 
							data-children_of="{{ data.children_of }}" 
							data-change_others="{{ data.change_others }}"
							data-action="{{ data.action }}" 
							data-setting="{{ data.name }}" {{ multiple }}>
                          </select>
                          <input type="hidden" class="saved-value" data-setting="{{ data.name }}">
                      </div>
                  </div>
		<?php
	}
}