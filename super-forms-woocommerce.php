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
            if($settings['woocommerce_checkout']=='true') {

                // No products defined to add to cart!
                if( (!isset($settings['woocommerce_checkout_products'])) || (empty($settings['woocommerce_checkout_products'])) ) {
                    $msg = __( 'You haven\'t defined what products should be added to the cart. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form settings and try again', 'super' );
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }

                $products = array();
                $woocommerce_checkout_products = explode( "\n", $settings['woocommerce_checkout_products'] );  
                foreach( $woocommerce_checkout_products as $k => $v ) {
                    $product =  explode( "|", $v );
                    $product_id = '';
                    $product_quantity = '';
                    $product_variation_id = '';
                    $product_price = '';
                    if( isset( $product[2] ) ) $product_id = SUPER_Common::email_tags( $product[0], $data, $settings );
                    if( isset( $product[2] ) ) $product_quantity = SUPER_Common::email_tags( $product[1], $data, $settings );
                    if( isset( $product[2] ) ) $product_variation_id = SUPER_Common::email_tags( $product[2], $data, $settings );
                    if( isset( $product[2] ) ) $product_price = SUPER_Common::email_tags( $product[3], $data, $settings );
                    $products[] = array(
                        'id' => absint($product_id),
                        'quantity' => $product_quantity,
                        'variation_id' => $product_variation_id,
                        'price' => $product_price,
                    );
                }

                global $woocommerce;

                // Empty the cart
                if( (isset($settings['woocommerce_checkout_empty_cart'])) && ($settings['woocommerce_checkout_empty_cart']=='true') ) {
                    $woocommerce->cart->empty_cart();
                }

                // Remove any coupons.
                if( (isset($settings['woocommerce_checkout_remove_coupons'])) && ($settings['woocommerce_checkout_remove_coupons']=='true') ) {
                    $woocommerce->cart->remove_coupons();
                }

                // Add discount
                if( (isset($settings['woocommerce_checkout_coupon'])) && ($settings['woocommerce_checkout_coupon']!='') ) {
                    $woocommerce->cart->add_discount($settings['woocommerce_checkout_coupon']);
                }

                global $wpdb;

                // Now add the product(s) to the cart
                foreach( $products as $k => $v ) {

                    // $product_id
                    // ( int ) optional – contains the id of the product to add to the cart

                    // $quantity
                    // ( int ) optional default: 1 – contains the quantity of the item to add

                    // $variation_id
                    // ( int ) optional –

                    // $variation
                    // ( array ) optional – attribute values

                    // $cart_item_data
                    // ( array ) optional – extra cart item data we want to pass into the item

                    $product = wc_get_product( $v['id'] );
                    $attributes = $product->get_variation_attributes();
                    $new_attributes = array();
                    foreach( $attributes as $ak => $av ) {
                        $new_attributes[$ak] = get_post_meta( $v['variation_id'], 'attribute_' . $ak, true );
                    }
                    $woocommerce->cart->add_to_cart( $v['id'], $v['quantity'], $v['variation_id'], $new_attributes );

                }

                // Redirect to cart / checkout page
                if( isset($settings['woocommerce_redirect']) ) {
                    $redirect = null;
                    if( $settings['woocommerce_redirect']=='checkout' ) {
                        $redirect = $woocommerce->cart->get_checkout_url();
                    }
                    if( $settings['woocommerce_redirect']=='cart' ) {
                        $redirect = $woocommerce->cart->get_cart_url();
                    }
                    if( $redirect!=null ) {
                        SUPER_Common::output_error(
                            $error = false,
                            $msg = '',
                            $redirect = $redirect
                        );
                    }
                }
                exit;

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
                    'woocommerce_checkout_send_admin_email' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_send_admin_email', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Send admin email only after payment completed', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_send_confirm_email' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_send_confirm_email', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Send confirmation email only after payment completed', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_send_complete_email' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_send_complete_email', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                        'values' => array(
                            'true' => __( 'Send a email when payment completed', 'super-forms' ),
                        ),
                    ),
                    'woocommerce_checkout_empty_cart' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_empty_cart', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Empty cart before adding products', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_products' => array(
                        'name' => __( 'Enter the product(s) ID that needs to be added to the cart', 'super' ) . '<br /><i>' . __( 'If field is inside dynamic column, system will automatically add all the products. Put each product ID with it\'s quantity on a new line separated by pipes "|".<br />Example with tags: "{product_title}|{product_quantity}"<br />Example without tags: "82921|3".<br />In case you want to use dynamic price per product you may also add the price: "{product_title}|{product_quantity}|{product_price}"', 'super' ) . '</i>',
                        'desc' => __( 'Put each on a new line, {tags} can be used to retrieve data', 'super' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_products', $settings['settings'], "{product_id}|{product_quantity}" ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_remove_coupons' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_remove_coupons', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Remove/clear coupons before redirecting to cart', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_coupon' => array(
                        'name' => __( 'Apply the following coupon code (leave blank for none):', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_coupon', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_redirect' => array(
                        'name' => __( 'Redirect to Checkout page or Shopping Cart?', 'super' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_redirect', $settings['settings'], 'checkout' ),
                        'type' => 'select',
                        'values' => array(
                            'checkout' => __( 'Checkout page (default)', 'super-forms' ),
                            'cart' => __( 'Shopping Cart', 'super-forms' ),
                            'none' => __( 'None (no redirect)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
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