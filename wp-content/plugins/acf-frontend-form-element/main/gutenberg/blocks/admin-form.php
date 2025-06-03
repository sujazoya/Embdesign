<?php
namespace Frontend_Admin\Gutenberg;

if (! defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}

if(! class_exists('Frontend_Admin\Gutenberg\Form_Select') ) :

    class Form_Select
    {    
    

        public function render($attr, $content)
        {
            $render = '';
            if ($attr['formID'] == 0 ) {
                   return $render;
            }
            if (get_post_type($attr['formID']) == 'admin_form' ) {
                ob_start();
                if(is_admin() ) {
                    $attr['editMode'] = true;
                }else{
                    $attr['editMode'] = false;
                }
                fea_instance()->form_display->render_form($attr['formID'], $attr['editMode']);
                $render = ob_get_contents();
                ob_end_clean();    
            }
            return $render;
        }
      
    }


endif;    