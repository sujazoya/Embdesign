<?php
namespace Frontend_Admin;

if (! defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}

if(! class_exists('Frontend_Admin_Gutenberg') ) :

    class Gutenberg
    {

        public function register_blocks()
        {
            $blocks = [ 
                'form' => 'Form',
                'admin-form' => 'Form_Select',
                'submissions' => 'Submissions_Select'
               // 'field' => 'field'
            ];

            foreach( $blocks as $block => $className ){
                include_once( FEA_DIR . 'main/gutenberg/blocks/' . $block . '.php' );
                $className = "\Frontend_Admin\Gutenberg\\".$className;
                $class = new $className;
                $name = str_replace( '_', '-', $block );
                register_block_type(
                    FEA_DIR . "/assets/build/blocks/$name", [
                    'render_callback' => [ $class, 'render' ],
                    ] 
                );
            }

            $field_types = fea_instance()->frontend->field_types;
            

            if( $field_types ){
                foreach( $field_types as $type ){
                    
                    if ( $type instanceof Field_Types\Field_Base ) {
                        $name = str_replace( '_', '-', $type->name );
                        if( ! empty( $name ) && file_exists( FEA_DIR . "/assets/build/$name/index.js" ) ){
                            register_block_type(
                                FEA_DIR . "/assets/build/blocks/$name", [
                                'render_callback' => [ $this, 'render_field_block' ],
                                ] 
                            );        
                        }
                    }
                }
            }
        }

        public function render_field_block( $attr, $content, $block )
        {
            global $fea_instance, $fea_form, $post;
            $form_display = $fea_instance->form_display;
            
            $render = '';        
            $field = acf_get_valid_field($attr);

            $field_key = $field['field_key'] ?? uniqid();
            $field['key'] = $fea_form['id'] . '_' . $field_key;
            $field['builder'] = 'gutenberg';
           
            $field['type'] = str_replace(
                array( 'frontend-admin/', '-field', '-' ),
                array( '', '', '_' ),
                $block->name
            );

            $field['name'] = $attr['name'] ?? 'fea_' . $field['type'];

            $field = $form_display->get_field_data_type( $field, $fea_form );


            if( ! $field ) return false;

            if ( ! isset( $field['value'] )
                || $field['value'] === null
            ) {
                $field = $form_display->get_field_value( $field, $fea_form );
                
            }

           
                
            //fix options. They have to be an array of key => values, not array of index => [key,label]
            if( isset( $field['choices'] ) && is_array( $field['choices'] ) ){
                $choices = [];
                foreach( $field['choices'] as $key => $choice ){
                    if( is_array( $choice ) ){
                        $choices[ $choice['value'] ] = $choice['label'];
                    }else{
                        $choices[ $key ] = $choice;
                    }
                }
                $field['choices'] = $choices;
            }



            ob_start();
            
            fea_instance()->form_display->render_field_wrap( $field );

            $render = ob_get_contents();
            ob_end_clean();    
            return $render;
        }
        
      

        


        function add_block_categories( $block_categories )
        {
            return array_merge(
                $block_categories,
                [
                [
                'slug'  => 'frontend-admin',
                'title' => 'Frontend Admin',
                'icon'  => 'feedback', 
                ],
                ]
            );
        }
        /**
         *  enqueue_block_editor_assets
         *
         *  Allows a safe way to customize Guten-only functionality.
         *
         * @date  14/11/22
         * @since 5.8.0
         *
         * @param  void
         * @return void
         */
        function enqueue_block_editor_assets()
        {
            // Load the compiled blocks into the editor.
            wp_enqueue_script(
                'fea-block-settings',
                FEA_URL . '/assets/build/block-settings/index.js',
                ['wp-edit-post'],
                '1.0',
                true
            );

        }

        function block_render( $block_content, $block ) {  
            global $fea_form;

            if( 'core/button' == $block['blockName'] ){
                $submit = $block['attrs']['submitButton'] ?? false;
               if( $submit ) {
                    if( ! $fea_form ){
                        return $block_content;
                    }else{                      
                        if (!empty($block_content)) {
                            $block_content = preg_replace('/<a\s+([^>]*?)class="([^"]*)"/is', '<a $1class="fea-submit-button $2"', $block_content);
                        }                        
                        return $block_content;
                    }
               }

            }

        

            return $block_content;
        }

       

        public function get_the_block( $ids, $type = 'form' ) {
            $post_id = $ids[0];
            $key = $ids[1];

            $post_content = get_post_field( 'post_content', $post_id );

            if ( empty( $post_content ) ) {
                return null;
            }

            $blocks = parse_blocks( $post_content );

            foreach ( $blocks as $block ) {
                $block = $this->recursive_block_search( $block, $key, $type );
                if ( $block ) {
                    return $block;
                }
            }

            return null;
        }

        public function recursive_block_search( $block, $key, $type = 'form' ) {
            if ( ! is_array( $block ) ) {
                return null;
            }

            if ( ! empty( $block['attrs'][$type.'_key'] ) && $block['attrs'][$type.'_key'] === $key ) {
                return $block;
            }

            if ( ! empty( $block['innerBlocks'] ) ) {

                foreach ( $block['innerBlocks'] as $inner_block ) {
                    $result = $this->recursive_block_search( $inner_block, $key, $type );
                    if ( $result ) {
                        return $result;
                    }
                }
            }

            return null;
        }

      
      


        public function __construct()
        {
            add_filter('block_categories_all', array( $this, 'add_block_categories' ));
            add_action('init', array( $this, 'register_blocks' ), 20);

            add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

            add_filter( 'render_block', [ $this, 'block_render' ], 10, 2 );


        }
    }

    fea_instance()->gutenberg = new Gutenberg();

endif;    