<?php
/**
 * Super Forms WooCommerce
 *
 * @package   Super Forms WooCommerce
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms WooCommerce
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Checkout with WooCommerce after form submission
 * Version:     1.0.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_WooCommerce')) :


    /**
     * Main SUPER_WooCommerce Class
     *
     * @class SUPER_WooCommerce
     */
    final class SUPER_WooCommerce {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.0.0';

        
        /**
         * @var SUPER_WooCommerce The single instance of the class
         *
         *	@since		1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_WooCommerce Instance
         *
         * Ensures only one instance of SUPER_WooCommerce is loaded or can be loaded.
         *
         * @static
         * @see SUPER_WooCommerce()
         * @return SUPER_WooCommerce - Main instance
         *
         *	@since		1.0.0
        */
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        
        /**
         * SUPER_WooCommerce Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_woocommerce_loaded');
        }

        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *	@since		1.0.0
        */
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }

        
        /**
         * What type of request is this?
         *
         * string $type ajax, frontend or admin
         * @return bool
         *
         *	@since		1.0.0
        */
        private function is_request($type){
            switch ($type){
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        
        /**
         * Hook into actions and filters
         *
         *	@since		1.0.0
        */
        private function init_hooks() {
            
            if ( $this->is_request( 'frontend' ) ) {
                
                // Filters since 1.0.0

                // Actions since 1.0.0

            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );

                // Actions since 1.0.0

            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Filters since 1.0.0

                // Actions since 1.0.0
                add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );

            }
            
        }


        /**
         * Hook into before sending email and check if we need to create or update a post or taxonomy
         *
         *  @since      1.0.0
        */
        public static function before_email_success_msg( $atts ) {

            $settings = $atts['settings'];
            if( isset( $atts['data'] ) ) {
                $data = $atts['data'];
            }else{
                if( $settings['save_contact_entry']=='yes' ) {
                    $data = get_post_meta( $atts['entry_id'], '_super_contact_entry_data', true );
                }else{
                    $data = $atts['post']['data'];
                }
            }

            if( !isset( $settings['woocommerce_action'] ) ) return true;
            if( $settings['woocommerce_action']=='none' ) return true;

            // Create a new post
            if( $settings['woocommerce_action']=='create_post' ) {
                
                // post_title and post_content are required so let's check if these are both set
                if( !isset( $data['post_title'] ) ) {
                    $msg = __( 'We couldn\'t find the <strong>post_title</strong> field which is required in order to create a new post. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again', 'super' );
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }

                // Lets check if post type exists
                if( $settings['woocommerce_post_type']=='' ) $settings['woocommerce_post_type'] = 'page';
                if ( !post_type_exists( $settings['woocommerce_post_type'] ) ) {
                    $msg = sprintf( __( 'The post type <strong>%s</strong> doesn\'t seem to exist. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super' ), $settings['woocommerce_post_type'] );
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }

                $postarr = array();
                
                // Default values from the form settings
                $postarr['post_type'] = sanitize_text_field( $settings['woocommerce_post_type'] );
                $postarr['post_status'] = sanitize_text_field( $settings['woocommerce_status'] );
                $postarr['post_parent'] = absint( $settings['woocommerce_post_parent'] );
                $postarr['comment_status'] = sanitize_text_field( $settings['woocommerce_comment_status'] );
                $postarr['ping_status'] = sanitize_text_field( $settings['woocommerce_ping_status'] );
                $postarr['menu_order'] = absint( $settings['woocommerce_menu_order'] );
                $postarr['post_password'] = $settings['woocommerce_post_password'];
                if($settings['woocommerce_author']!='') {
                    $postarr['post_author'] = absint( $settings['woocommerce_author'] );
                }else{
                    $user_id = get_current_user_id();
                    if( $user_id!=0 ) {
                        $postarr['post_author'] = $user_id;
                    }
                }
                $post_format = sanitize_text_field( $settings['woocommerce_post_format'] );
                $tax_input = sanitize_text_field( $settings['woocommerce_tax_input'] );
                $tags_input = sanitize_text_field( $settings['woocommerce_tags_input'] );
                $tag_taxonomy = sanitize_text_field( $settings['woocommerce_post_tag_taxonomy'] );
                $cat_taxonomy = sanitize_text_field( $settings['woocommerce_post_cat_taxonomy'] );

                // Override default values for form field values
                $postarr['post_title'] = $data['post_title']['value'];
                if( isset( $data['post_content'] ) ) $postarr['post_content'] = $data['post_content']['value'];
                if( isset( $data['post_excerpt'] ) ) $postarr['post_excerpt'] = $data['post_excerpt']['value'];
                if( isset( $data['post_type'] ) ) $postarr['post_type'] = sanitize_text_field( $data['post_type']['value'] );
                if( isset( $data['post_format'] ) ) $post_format = sanitize_text_field( $data['post_format']['value'] );
                if( isset( $data['tax_input'] ) ) $tax_input = sanitize_text_field( $data['tax_input']['value'] );
                if( isset( $data['tags_input'] ) ) $tags_input = sanitize_text_field( $data['tags_input']['value'] );
                if( isset( $data['tag_taxonomy'] ) ) $tag_taxonomy = sanitize_text_field( $data['tag_taxonomy']['value'] );
                if( isset( $data['cat_taxonomy'] ) ) $cat_taxonomy = sanitize_text_field( $data['cat_taxonomy']['value'] );
                if( isset( $data['post_status'] ) ) $postarr['post_status'] = sanitize_text_field( $data['post_status']['value'] );
                if( isset( $data['post_parent'] ) ) $postarr['post_parent'] = absint( $data['post_parent']['value'] );
                if( isset( $data['comment_status'] ) ) $postarr['comment_status'] = sanitize_text_field( $data['comment_status']['value'] );
                if( isset( $data['ping_status'] ) ) $postarr['ping_status'] = sanitize_text_field( $data['ping_status']['value'] );
                if( isset( $data['post_password'] ) ) $postarr['post_password'] = $data['post_password']['value'];
                if( isset( $data['menu_order'] ) ) $postarr['menu_order'] = $data['menu_order']['value'];
                if( isset( $data['post_author'] ) ) $postarr['post_author'] = absint( $data['post_author']['value'] );
                if( (isset( $data['post_date'] )) && (isset( $data['post_time'] )) ) {
                    $postarr['post_time'] = date( 'H:i:s', strtotime($data['post_time']['value'] ) ); // Must be formatted as '18:57:33';
                    $postarr['post_date'] = date( 'Y-m-d', strtotime($data['post_date']['value'] ) ); // Must be formatted as '2010-02-23';
                    $postarr['post_date'] = $postarr['post_date'] . ' ' . $postarr['post_time']; // Must be formatted as '2010-02-23 18:57:33';
                }else{
                    if( isset( $data['post_date'] ) ) {
                        $postarr['post_date'] = date( 'Y-m-d H:i:s', strtotime($data['post_date']['value'] ) ); // Must be formatted as '2010-02-23 18:57:33';
                    }
                }
                if( ($postarr['comment_status']=='open') || ($postarr['comment_status']=='1') || ($postarr['comment_status']=='yes') || ($postarr['comment_status']=='true') ) {
                    $postarr['comment_status'] = 'open';
                }elseif( ($postarr['comment_status']=='closed') || ($postarr['comment_status']=='0') || ($postarr['comment_status']=='no') || ($postarr['comment_status']=='false') ) {
                    $postarr['comment_status'] = 'closed';
                }else{
                    unset($postarr['comment_status']);
                }

                // Lets check if tax_input field exists
                // If so, let's check if the post_taxonomy exists, because this is required in order to connect the categories accordingly to the post.
                if( $tax_input!='' ) {
                    if( $cat_taxonomy=='' ) {
                        $msg = __( 'You have a field called <strong>tax_input</strong> but you haven\'t set a valid taxonomy name. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super' );
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }else{
                        if ( !taxonomy_exists( $cat_taxonomy ) ) {
                            $msg = sprintf( __( 'The taxonomy <strong>%s</strong> doesn\'t seem to exist. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super' ), $settings['woocommerce_post_cat_taxonomy'] );
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $msg,
                                $redirect = null
                            );
                        }
                    }
                }

                // Lets check if tags_input field exists
                // If so, let's check if the tag_taxonomy exists, because this is required in order to connect the categories accordingly to the post.
                if( $tags_input!='' ) {
                    if( $tag_taxonomy=='' ) {
                        $msg = __( 'You have a field called <strong>tags_input</strong> but you haven\'t set a valid taxonomy name. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super' );
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }else{
                        if ( !taxonomy_exists( $tag_taxonomy ) ) {
                            $msg = sprintf( __( 'The taxonomy <strong>%s</strong> doesn\'t seem to exist. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super' ), $tag_taxonomy );
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $msg,
                                $redirect = null
                            );
                        }
                    }
                }

                // @since 1.0.1
                $postarr = apply_filters( 'super_woocommerce_before_insert_post_filter', $postarr );

                // Get the post ID or return the error(s)
                $result = wp_insert_post( $postarr, true );
                if( isset( $result->errors ) ) {
                    $msg = '';
                    foreach( $result->errors as $v ) {
                        $msg .= '- ' . $v[0] . '<br />';
                    }
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }else{

                    $post_id = $result;

                    // Check if we need to make this post sticky
                    if( isset( $data['stick_post'] ) ) {
                        $sticky = sanitize_text_field( $data['stick_post']['value'] );
                        if( ($sticky=='1') || ($sticky=='true') || ($sticky=='yes') ) {
                            stick_post($post_id);
                        }
                    }

                    // BuddyPress functions
                    // Make Topic sticky
                    if( function_exists( 'bbp_stick_topic' ) ) {
                        if( isset( $data['_bbp_topic_type'] ) ) {
                            $stickies = array( $post_id );
                            $stickies = array_values( $stickies );
                            if( $data['_bbp_topic_type']['value']=='super' ) {
                                update_option( '_bbp_super_sticky_topics', $stickies );
                            }
                            if( $data['_bbp_topic_type']['value']=='stick' ) {
                                update_post_meta( $postarr['post_parent'], '_bbp_sticky_topics', $stickies );
                            }
                        }
                    }
                    // Set parent for topics only
                    if( function_exists( 'bbp_get_topic_post_type' ) ) {
                        if( $postarr['post_type']==bbp_get_topic_post_type() ) {
                            update_post_meta( $post_id, '_bbp_author_ip', bbp_current_author_ip() );
                            if( isset( $postarr['post_parent'] ) ) {
                                if( $postarr['post_parent']!=0 ) {
                                    update_post_meta( $post_id, '_bbp_forum_id', $postarr['post_parent'] );
                                }
                            }
                        }
                    }
                    // Subscribe to the Topic
                    if( function_exists( 'bbp_add_user_subscription' ) ) {
                        if( isset( $data['bbp_subscribe'] ) ) {
                            $bbp_subscribe = filter_var( $data['bbp_subscribe']['value'], FILTER_VALIDATE_BOOLEAN );
                            if( $bbp_subscribe===true) {
                                $result = bbp_add_user_subscription( $postarr['post_author'], $post_id );
                            }
                        }
                    }

                    // Collect categories from the field tax_input
                    if( $tax_input!='' ) {
                        $tax_input_array = array();
                        $categories = explode( ",", $tax_input );
                        foreach( $categories as $slug ) {
                            $slug = trim($slug);
                            if( !empty( $slug ) ) {
                                $tax_input_array[] = $slug;
                            }
                        }
                        wp_set_object_terms( $post_id, $tax_input_array, $cat_taxonomy );
                    }

                    // Collect tags from the field tags_input
                    if( $tags_input!='' ) {
                        $tags_input_array = array();
                        $tags = explode( ",", $tags_input );
                        foreach( $tags as $slug ) {
                            $slug = trim($slug);
                            if( !empty( $slug ) ) {
                                $tags_input_array[] = $slug;
                            }
                        }
                        wp_set_object_terms($post_id, $tags_input_array, $tag_taxonomy );
                    }

                    // Check if we are saving a WooCommerce product
                    if( $postarr['post_type']=='product' ) {

                        // Set the product type (default = simple)
                        $product_type = sanitize_text_field( $settings['woocommerce_product_type'] );
                        if( isset( $data['product_type'] ) ) $product_type = sanitize_text_field( $data['product_type']['value'] );
                        if( $product_type=='' ) $product_type = 'simple';
                        wp_set_object_terms( $post_id, $product_type, 'product_type' );

                        // Set the shipping class (default = none)
                        $shipping_class = 0;
                        if( isset( $data['product_shipping_class'] ) ) $shipping_class = absint( $data['product_shipping_class']['value'] );
                        if( $shipping_class!=0 ) {
                            wp_set_object_terms( $post_id, absint($shipping_class), 'product_shipping_class' );
                        }

                        // Save all the product meta data
                        $fields = array(
                            'product_downloadable' => '_downloadable',
                            'product_virtual' => '_virtual',
                            'product_visibility' => '_visibility',
                            'product_featured' => '_featured',
                            'product_stock_status' => '_stock_status',
                            'product_manage_stock' => '_manage_stock',
                            'product_stock' => '_stock',
                            'product_backorders' => '_backorders',
                            'product_sold_individually' => '_sold_individually',
                            'product_regular_price' => '_regular_price',
                            'product_sale_price' => '_sale_price',
                            'product_purchase_note' => '_purchase_note',
                            'product_weight' => '_weight',
                            'product_length' => '_length',
                            'product_width' => '_width',
                            'product_height' => '_height',
                            'product_sku' => '_sku',
                            'product_attributes' => '_product_attributes',
                            'product_sale_price_dates_from' => '_sale_price_dates_from',
                            'product_sale_price_dates_to' => '_sale_price_dates_to',
                            'product_price' => '_price',
                            
                            'product_downloadable_files' => '_downloadable_files',
                            'product_download_limit' => '_download_limit',
                            'product_download_expiry' => '_download_expiry',
                            'product_download_type' => '_download_type',

                            'product_url' => '_product_url',
                            'product_button_text' => '_button_text',
                            
                            'product_upsell_ids' => 'upsell_ids',
                            'product_crosssell_ids' => 'crosssell_ids',
                            
                            // Do we really need this? I don't think so, if a client requests this we will add it
                            // For now we will just comment it
                            //'product_total_sales' => 'total_sales',

                            // file paths will be stored in an array keyed off md5(file path)
                            //$downdloadArray =array('name'=>"Test", 'file' => $uploadDIR['baseurl']."/video/".$video);
                            //$file_path =md5($uploadDIR['baseurl']."/video/".$video);
                            //$_file_paths[  $file_path  ] = $downdloadArray;
                            // grant permission to any newly added files on any existing orders for this product
                            // do_action( 'woocommerce_process_product_file_download_paths', $post_id, 0, $downdloadArray );
                            //update_post_meta( $post_id, '_downloadable_files', $_file_paths);
                            //update_post_meta( $post_id, '_download_limit', '');
                            //update_post_meta( $post_id, '_download_expiry', '');
                            //update_post_meta( $post_id, '_download_type', '');

                        );
                        foreach( $fields as $k => $v ) {
                            if( ( $k=='product_sale_price_dates_from' ) || ( $k=='product_sale_price_dates_to' ) ) {
                                $field_value = '';
                                if( isset( $data[$k] ) ) {
                                    if( $data[$k]['value']!='' ) {
                                        $field_value = strtotime( $data[$k]['value'] );
                                        update_post_meta( $post_id, $v, $field_value );
                                    }
                                }
                                continue;
                            }
                            if( $k=='product_downloadable_files' ) {
                                if( isset( $data['downloadable_files'] ) ) {
                                    $files = array();
                                    $_file_paths = array();
                                    foreach( $data['downloadable_files']['files'] as $v ) {
                                        $name = get_the_title( $v['attachment'] );
                                        $url = $v['url'];
                                        $array = array( 'name'=>$name, 'file' => $url );
                                        $url = md5( $url );
                                        $_file_paths[$url] = $array;
                                    }
                                    update_post_meta( $post_id, '_downloadable_files', $_file_paths);
                                }
                                continue;
                            }
                            if( $k=='product_attributes' ) {
                                    
                                // Lets make sure we loop through all the product attributes in case a column was set to use Add more + feature
                                $_product_attributes = array();
                                foreach( $data as $dk => $dv ) {
                                    if( ( ($dk=='product_attributes') || (strpos($dk, 'product_attributes_') !== false) ) && (strpos($dk, 'product_attributes_name') === false) ) {
                                        $counter = str_replace('product_attributes_', '', $dv['name']);
                                        $counter = absint($counter);
                                        $value = '';
                                        $visible = '1';
                                        $variation = '0';
                                        $taxonomy = '0';
                                        if( $counter==0 ) {
                                            $name = 'Variation 1';
                                            if( isset( $data['product_attributes_name'] ) ) {
                                                $name = sanitize_text_field( $data['product_attributes_name']['value'] );
                                            }
                                            if( isset( $data['product_attributes'] ) ) {
                                                $value = sanitize_text_field( $data['product_attributes']['value'] );
                                            }
                                            if( isset( $data['product_attributes_is_visible'] ) ) {
                                                $visible = sanitize_text_field( $data['product_attributes_is_visible']['value'] );
                                                if( ($visible=='1') || ($visible=='true') || ($visible=='yes') ) {
                                                    $visible = '1';
                                                }
                                            }
                                            if( isset( $data['product_attributes_is_variation'] ) ) {
                                                $variation = sanitize_text_field( $data['product_attributes_is_variation']['value'] );
                                                if( ($variation=='1') || ($variation=='true') || ($variation=='yes') ) {
                                                    $variation = '1';
                                                }
                                            }
                                            if( isset( $data['product_attributes_is_taxonomy'] ) ) {
                                                $taxonomy = sanitize_text_field( $data['product_attributes_is_taxonomy']['value'] );
                                                if( ($taxonomy=='1') || ($taxonomy=='true') || ($taxonomy=='yes') ) {
                                                    $taxonomy = '1';
                                                }
                                            }
                                        }else{
                                            $name = 'Variation ' . $counter;
                                            if( isset( $data['product_attributes_name_' . $counter] ) ) {
                                                $name = sanitize_text_field( $data['product_attributes_name_' . $counter]['value'] );
                                            }
                                            if( isset( $data['product_attributes_' . $counter] ) ) {
                                                $value = sanitize_text_field( $data['product_attributes_' . $counter]['value'] );
                                            }                                            
                                            if( isset( $data['product_attributes_is_visible_' . $counter] ) ) {
                                                $visible = sanitize_text_field( $data['product_attributes_is_visible_' . $counter]['value'] );
                                                if( ($visible=='1') || ($visible=='true') || ($visible=='yes') ) {
                                                    $visible = '1';
                                                }
                                            }
                                            if( isset( $data['product_attributes_is_variation_' . $counter] ) ) {
                                                $variation = sanitize_text_field( $data['product_attributes_is_variation_' . $counter]['value'] );
                                                if( ($variation=='1') || ($variation=='true') || ($variation=='yes') ) {
                                                    $variation = '1';
                                                }
                                            }                                            
                                            if( isset( $data['product_attributes_is_taxonomy_' . $counter] ) ) {
                                                $taxonomy = sanitize_text_field( $data['product_attributes_is_taxonomy_' . $counter]['value'] );
                                                if( ($taxonomy=='1') || ($taxonomy=='true') || ($taxonomy=='yes') ) {
                                                    $taxonomy = '1';
                                                }
                                            }
                                        }
                                        $term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $name, true );
                                        $_product_attributes[$name]['name'] = $name;
                                        $_product_attributes[$name]['value'] = $value;
                                        $_product_attributes[$name]['is_visible'] = $visible;
                                        $_product_attributes[$name]['is_variation'] = $variation;
                                        $_product_attributes[$name]['is_taxonomy'] = $taxonomy;
                                        update_post_meta( $post_id, '_product_attributes', $_product_attributes);
                                    } 
                                }
                                continue;
                            }

                            $field_value = '';
                            if( isset( $settings['woocommerce_'.$k] ) ) $field_value = sanitize_text_field( $settings['woocommerce_'.$k] );
                            if( isset( $data[$k] ) ) $field_value = sanitize_text_field( $data[$k]['value'] );
                            update_post_meta( $post_id, $v, $field_value );
                        }

                        // If we are saving a WooCommerce product check if we need to add images to the gallery
                        if( isset( $data['image_gallery'] ) ) {
                            $files = array();
                            foreach( $data['image_gallery']['files'] as $v ) {
                                $files[] = $v['attachment'];
                            }
                            $files = implode( ',', $files );
                            update_post_meta( $post_id, '_product_image_gallery', $files );
                        }

                        // Sales and prices
                        if ( in_array( $product_type, array( 'variable', 'grouped' ) ) ) {
                            // Variable and grouped products have no prices
                            update_post_meta( $post_id, '_regular_price', '' );
                            update_post_meta( $post_id, '_sale_price', '' );
                            update_post_meta( $post_id, '_sale_price_dates_from', '' );
                            update_post_meta( $post_id, '_sale_price_dates_to', '' );
                            update_post_meta( $post_id, '_price', '' );
                        }else{
                            // Regular Price
                            if ( isset( $data['product_regular_price'] ) ) {
                                $regular_price = ( '' === $data['product_regular_price']['value'] ) ? '' : wc_format_decimal( $data['product_regular_price']['value'] );
                                update_post_meta( $post_id, '_regular_price', $regular_price );
                            } else {
                                $regular_price = get_post_meta( $post_id, '_regular_price', true );
                            }

                            // Sale Price
                            if ( isset( $data['product_sale_price'] ) ) {
                                $sale_price = ( '' === $data['product_sale_price']['value'] ) ? '' : wc_format_decimal( $data['product_sale_price']['value'] );
                                update_post_meta( $post_id, '_sale_price', $sale_price );
                            } else {
                                $sale_price = get_post_meta( $post_id, '_sale_price', true );
                            }
                            $date_from = isset( $data['product_sale_price_dates_from'] ) ? strtotime( $data['product_sale_price_dates_from']['value'] ) : get_post_meta( $post_id, '_sale_price_dates_from', true );
                            $date_to   = isset( $data['product_sale_price_dates_to'] ) ? strtotime( $data['product_sale_price_dates_to']['value'] ) : get_post_meta( $post_id, '_sale_price_dates_to', true );

                            // Dates
                            if ( $date_from ) {
                                update_post_meta( $post_id, '_sale_price_dates_from', $date_from );
                            } else {
                                update_post_meta( $post_id, '_sale_price_dates_from', '' );
                            }
                            if ( $date_to ) {
                                update_post_meta( $post_id, '_sale_price_dates_to', $date_to );
                            } else {
                                update_post_meta( $post_id, '_sale_price_dates_to', '' );
                            }
                            if ( $date_to && ! $date_from ) {
                                $date_from = strtotime( 'NOW', current_time( 'timestamp' ) );
                                update_post_meta( $post_id, '_sale_price_dates_from', $date_from );
                            }

                            // Update price if on sale
                            if ( '' !== $sale_price && '' == $date_to && '' == $date_from ) {
                                update_post_meta( $post_id, '_price', wc_format_decimal( $sale_price ) );
                            } else {
                                update_post_meta( $post_id, '_price', $regular_price );
                            }
                            if ( '' !== $sale_price && $date_from && $date_from <= strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
                                update_post_meta( $post_id, '_price', wc_format_decimal( $sale_price ) );
                            }
                            if ( $date_to && $date_to < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
                                update_post_meta( $post_id, '_price', $regular_price );
                                update_post_meta( $post_id, '_sale_price_dates_from', '' );
                                update_post_meta( $post_id, '_sale_price_dates_to', '' );
                            }
                        }
                    }

                    // Save custom post meta
                    $meta_data = array();
                    $custom_meta = explode( "\n", $settings['woocommerce_meta'] );
                    foreach( $custom_meta as $k ) {
                        $field = explode( "|", $k );
                        if( isset( $data[$field[0]]['value'] ) ) {
                            $meta_data[$field[1]] = $data[$field[0]]['value'];
                        }
                    }
                    foreach( $meta_data as $k => $v ) {
                        add_post_meta( $post_id, $k, $v );
                    }

                    // Set post format for the post if theme supports it and if it was set by the form settings or by one of the form fields
                    if ( current_theme_supports( 'post-formats' ) ) {
                        $post_formats = get_theme_support( 'post-formats' );
                        if ( is_array( $post_formats[0] ) ) {
                            if ( in_array( $post_format, $post_formats[0] ) ) {
                                set_post_format( $post_id , $post_format);
                            }
                        }
                    }

                    // Set the featured image if a file upload field with the name featured_image was found
                    if( isset( $data['featured_image'] ) ) {
                        set_post_thumbnail( $post_id, $data['featured_image']['files'][0]['attachment'] );
                    }

                    // @since 1.0.1
                    do_action( 'super_woocommerce_after_insert_post_action', array( 'post_id'=>$post_id, 'data'=>$data, 'atts'=>$atts ) );


                }
            }
        }


        /**
         * Hook into settings and add WooCommerce settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            $array['woocommerce_checkout'] = array(        
                'name' => __( 'WooCommerce Checkout', 'super' ),
                'label' => __( 'WooCommerce Checkout', 'super' ),
                'fields' => array(
                    'woocommerce_checkout' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Enable WooCommerce Checkout', 'super-forms' ),
                        ),
                    ),
                )
            );
            if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
                $array['woocommerce_checkout']['fields']['woocommerce_post_status'] = array(
                    'name' => __( 'Post status after payment complete', 'super' ),
                    'desc' => __( 'Only used for Front-end posting (publish, future, draft, pending, private, trash, auto-draft)?', 'super' ),
                    'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_status', $settings['settings'], 'publish' ),
                    'type' => 'select',
                    'values' => array(
                        'publish' => __( 'Publish (default)', 'super' ),
                        'future' => __( 'Future', 'super' ),
                        'draft' => __( 'Draft', 'super' ),
                        'pending' => __( 'Pending', 'super' ),
                        'private' => __( 'Private', 'super' ),
                        'trash' => __( 'Trash', 'super' ),
                        'auto-draft' => __( 'Auto-Draft', 'super' ),
                    ),
                    'filter' => true,
                    'parent' => 'woocommerce_checkout',
                    'filter_value' => 'true',
                );
            }
            return $array;

            /*
            $array['woocommerce'] = array(        
                'name' => __( 'WooCommerce', 'super' ),
                'label' => __( 'WooCommerce Settings', 'super' ),
                'fields' => array(
                    'woocommerce_post_status' => array(
                        'name' => __( 'Post status after payment complete', 'super' ),
                        'desc' => __( 'Select what the status should be (publish, future, draft, pending, private, trash, auto-draft)?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_status', $settings['settings'], 'publish' ),
                        'type' => 'select',
                        'values' => array(
                            'publish' => __( 'Publish (default)', 'super' ),
                            'future' => __( 'Future', 'super' ),
                            'draft' => __( 'Draft', 'super' ),
                            'pending' => __( 'Pending', 'super' ),
                            'private' => __( 'Private', 'super' ),
                            'trash' => __( 'Trash', 'super' ),
                            'auto-draft' => __( 'Auto-Draft', 'super' ),
                        ),
                    ),

                    /*
                    'woocommerce_action' => array(
                        'name' => __( 'Actions', 'super' ),
                        'desc' => __( 'Select what this form should do (register or login)?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_action', $settings['settings'], 'none' ),
                        'filter' => true,
                        'type' => 'select',
                        'values' => array(
                            'none' => __( 'None (do nothing)', 'super' ),
                            'create_post' => __( 'Create new Post', 'super' ), //(post, page, product etc.)
                        ),
                    ),
                    'woocommerce_post_type' => array(
                        'name' => __( 'Post type', 'super' ),
                        'desc' => __( 'Enter the name of the post type (e.g: post, page, product)', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_type', $settings['settings'], 'page' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_status' => array(
                        'name' => __( 'Status', 'super' ),
                        'desc' => __( 'Select what the status should be (publish, future, draft, pending, private, trash, auto-draft)?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_status', $settings['settings'], 'publish' ),
                        'type' => 'select',
                        'values' => array(
                            'publish' => __( 'Publish (default)', 'super' ),
                            'future' => __( 'Future', 'super' ),
                            'draft' => __( 'Draft', 'super' ),
                            'pending' => __( 'Pending', 'super' ),
                            'private' => __( 'Private', 'super' ),
                            'trash' => __( 'Trash', 'super' ),
                            'auto-draft' => __( 'Auto-Draft', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_post_parent' => array(
                        'name' => __( 'Parent ID (leave blank for none)', 'super' ),
                        'desc' => __( 'Enter a parent ID if you want the post to have a parent', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_parent', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_comment_status' => array(
                        'name' => __( 'Allow comments', 'super' ),
                        'desc' => __( 'Whether the post can accept comments', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_comment_status', $settings['settings'], '' ),
                        'type' => 'select',
                        'values' => array(
                            '' => __( 'Default (use the default_comment_status option)', 'super' ),
                            'open' => __( 'Open (allow comments)', 'super' ),
                            'closed' => __( 'Closed (disallow comments)', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_ping_status' => array(
                        'name' => __( 'Allow pings', 'super' ),
                        'desc' => __( 'Whether the post can accept pings', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_ping_status', $settings['settings'], '' ),
                        'type' => 'select',
                        'values' => array(
                            '' => __( 'Default (use the default_ping_status option)', 'super' ),
                            'open' => __( 'Open (allow pings)', 'super' ),
                            'closed' => __( 'Closed (disallow pings)', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_post_password' => array(
                        'name' => __( 'Password protect (leave blank for none)', 'super' ),
                        'desc' => __( 'The password to access the post', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_password', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_menu_order' => array(
                        'name' => __( 'Menu order (blank = 0)', 'super' ),
                        'desc' => __( 'The order the post should be displayed in', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_menu_order', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_meta' => array(
                        'name' => __( 'Save custom post meta', 'super' ),
                        'desc' => __( 'Based on your form fields you can save custom meta for your post', 'super' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_meta', $settings['settings'], "field_name|meta_key\nfield_name2|meta_key2\nfield_name3|meta_key3" ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_author' => array(
                        'name' => __( 'Author ID (default = current user ID if logged in)', 'super' ),
                        'desc' => __( 'The ID of the user where the post will belong to', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_author', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_post_cat_taxonomy' => array(
                        'name' => __( 'The cat taxonomy name (e.g: category or product_cat)', 'super' ),
                        'desc' => __( 'Required to connect the post to categories (if found)', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_cat_taxonomy', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_tax_input' => array(
                        'name' => __( 'The post categories slug(s) (e.g: books, cars)', 'super' ),
                        'desc' => __( 'Category slug separated by comma', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_tax_input', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_tags_input' => array(
                        'name' => __( 'The post tags', 'super' ),
                        'desc' => __( 'Post tags separated by comma', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_tags_input', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_post_tag_taxonomy' => array(
                        'name' => __( 'The tag taxonomy name (e.g: post_tag or product_tag)', 'super' ),
                        'desc' => __( 'Required to connect the post to categories (if found)', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_tag_taxonomy', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_post_format' => array(
                        'name' => __( 'The post format (e.g: quote, gallery, audio etc.)', 'super' ),
                        'desc' => __( 'Leave blank for no post format', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_format', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_guid' => array(
                        'name' => __( 'GUID', 'super' ),
                        'desc' => __( 'Global Unique ID for referencing the post', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_guid', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_action',
                        'filter_value' => 'create_post',
                    ),
                    'woocommerce_product_type' => array(
                        'name' => __( 'Product Type (e.g: simple, grouped, external, variable)', 'super' ),
                        'desc' => __( 'Leave blank to use the default product type: simple', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_type', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    ),
                    'woocommerce_product_featured' => array(
                        'name' => __( 'Featured product', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_featured', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super' ),
                            'yes' => __( 'Yes', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    ),
                    'woocommerce_product_stock_status' => array(
                        'name' => __( 'In stock?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_stock_status', $settings['settings'], 'yes' ),
                        'type' => 'select',
                        'values' => array(
                            'instock' => __( 'In stock (default)', 'super' ),
                            'outofstock' => __( 'Out of stock', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    ),
                    'woocommerce_product_manage_stock' => array(
                        'name' => __( 'Manage stock?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_manage_stock', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super' ),
                            'yes' => __( 'Yes', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    ),
                    'woocommerce_product_stock' => array(
                        'name' => __( 'Stock Qty', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_stock', $settings['settings'], '' ),
                        'type' => 'slider',
                        'min' => 0,
                        'max' => 100,
                        'steps' => 1,
                        'filter' => true,
                        'parent' => 'woocommerce_product_manage_stock',
                        'filter_value' => 'yes',
                    ),
                    'woocommerce_product_backorders' => array(
                        'name' => __( 'Allow Backorders?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_backorders', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'Do not allow (default)', 'super' ),
                            'notify' => __( 'Allow, but notify customer', 'super' ),
                            'yes' => __( 'Allow', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_product_manage_stock',
                        'filter_value' => 'yes',
                    ),
                    'woocommerce_product_sold_individually' => array(
                        'name' => __( 'Sold individually?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_sold_individually', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super' ),
                            'yes' => __( 'Yes', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    ),
                    'woocommerce_product_downloadable' => array(
                        'name' => __( 'Downloadable product', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_downloadable', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super' ),
                            'yes' => __( 'Yes', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    ),
                    'woocommerce_product_virtual' => array(
                        'name' => __( 'Virtual product', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_virtual', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super' ),
                            'yes' => __( 'Yes', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    ),
                    'woocommerce_product_visibility' => array(
                        'name' => __( 'Product visibility', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_product_visibility', $settings['settings'], 'visible' ),
                        'type' => 'select',
                        'values' => array(
                            'visible' => __( 'Catalog & search (default)', 'super' ),
                            'catalog' => __( 'Catalog', 'super' ),
                            'search' => __( 'Search', 'super' ),
                            'hidden' => __( 'Hidden', 'super' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_post_type',
                        'filter_value' => 'product',
                    )
                )
            );

            return $array;
            */
        }



    }
        
endif;


/**
 * Returns the main instance of SUPER_WooCommerce to prevent the need to use globals.
 *
 * @return SUPER_WooCommerce
 */
function SUPER_WooCommerce() {
    return SUPER_WooCommerce::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_WooCommerce'] = SUPER_WooCommerce();