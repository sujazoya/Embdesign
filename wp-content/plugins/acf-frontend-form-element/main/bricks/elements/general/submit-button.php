<?php 
namespace Frontend_Admin\Bricks\Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class SubmitButton extends \Bricks\Element_Button {
  /** 
   * How to create custom elements in Bricks
   * 
   * https://academy.bricksbuilder.io/article/create-your-own-elements
   */
  public $category     = 'custom';
  public $name         = 'fea-submit-button';
  public $icon         = 'fas fa-button'; // FontAwesome 5 icon in builder (https://fontawesome.com/icons)
  public $css_selector = '.submit-button-wrapper'; // Default CSS selector for all controls with 'css' properties
  // public $scripts      = []; // Enqueue registered scripts by their handle

  public function get_label() {
    return esc_html__( 'Submit Button', 'bricks' );
  }
  public function set_controls() {
    parent::set_controls();

    $this->controls['text'] = [
			'type'        => 'text',
			'default'     => esc_html__( 'Submit Form', 'bricks' ),
			'placeholder' => esc_html__( 'Submit Form', 'bricks' ),
		];

  }


  public function render(){
    $this->set_attribute( '_root', 'class', 'fea-submit-button' );
    parent::render();

  }
}
  