<?php
    /*
        Plugin Name: Picanova
        Description: Picanova Settings
        Version: 0.3.1
        Author: NextG
    */

    require_once 'inc/PicanovaApi.php';
    require_once 'inc/PicanovaProductAdmin.php';

    global $picanovaApi;
    $picanovaApi = new PicanovaApi();

    register_activation_hook( __FILE__, 'picanova_pl_activate' );
    function picanova_pl_activate () {

    }

    $picanova_changeable_options = array(
        'canvas_border' => 'Canvas border',
        'stretcher_frame' => 'Stretcher frame',
        'frame' => 'Frame',
        'easel_back' => 'Easel Back'
    );

    const PICANOVA_OPTION_PREFIX = 'picanova_option_';

add_action( 'woocommerce_save_product_variation', 'bbloomer_save_custom_field_variations', 10, 2 );

function bbloomer_save_custom_field_variations( $variation_id, $i ) {
    $custom_field = $_POST['picanova_variation'][$i];
    if ( ! empty( $custom_field ) ) {
        update_post_meta( $variation_id, 'picanova_variation', esc_attr( $custom_field ) );
    } else delete_post_meta( $variation_id, 'picanova_variation' );
}
//    add_action( 'woocommerce_process_product_meta', 'picanova_pl_variations_save', 10, 2 );
//    function picanova_pl_variations_save( $post_id ) {
//
//        $picanova_variations = $_POST['picanova_variations'];
//        if ( ! empty( $picanova_variations ) ) {
//            update_post_meta( $post_id, 'picanova_variations', esc_attr( $picanova_variations ) );
//        } else delete_post_meta( $post_id, 'picanova_variations' );
//    }


    add_action('admin_enqueue_scripts', 'picanova_pl_scripts');
    function picanova_pl_scripts() {
        wp_enqueue_script( 'picanova-pl-admin', plugins_url('/assets/js/admin.js', __FILE__), array('jquery') );
        wp_localize_script( 'picanova-pl-admin', 'ajax_object', array(
            'ajaxurl' => plugins_url('picanova/inc/picanova_functions.php'),
            //'ajaxurl' => plugins_url('picanova/picanova.php'),
        ) );
    }

    add_action('wp_enqueue_scripts', 'picanova_pl_styles');
    function picanova_pl_styles() {
        wp_register_style( 'picanova-pl-style', plugins_url('/assets/css/style.css', __FILE__) );
        wp_enqueue_style( 'picanova-pl-style' );
        wp_enqueue_script( 'picanova-pl-front', plugins_url('/assets/js/front.js', __FILE__), array('jquery') );
        if(is_product()) {

            global $post, $picanovaApi;
            $id = get_post_meta($post->ID, 'picanova_product', true);

            if($id) {
                $result = array();
                $picanonaVariations = $picanovaApi->getVariations($id);

                $variations     = wc_get_products(
                    array(
                        'status'  => array( 'private', 'publish' ),
                        'type'    => 'variation',
                        'parent'  => $post->ID,
                        'limit'   => 10,
                        'page'    => 1,
                        'orderby' => array(
                            'menu_order' => 'ASC',
                            'ID'         => 'DESC',
                        ),
                        'return'  => 'objects',
                    )
                );

                $usedVariations = array();
                foreach ($variations as $variation) {
                    $picanova_variation_code = get_post_meta( $variation->get_id(), 'picanova_variation', true );

                    if(!empty($picanova_variation_code)) {
                        $usedVariations[$picanova_variation_code] =$variation->get_id();
                    }

                }

                foreach ($picanonaVariations->data as $picanonaVariation) {
                    if(isset( $usedVariations[$picanonaVariation->code])) {
                        $result[$usedVariations[$picanonaVariation->code]] = $picanonaVariation->options;
                    }
                }

                wp_localize_script(  'picanova-pl-front', 'envData',
                    array(
                        'url' => admin_url('admin-ajax.php'),
                        'options' => $result,
                    )
                );
            }

        }
    }

  //  add_action('woocommerce_single_variation', 'test');
    function test(){
        global $post;
        global $picanovaApi;

        $id = get_post_meta($post->ID, 'picanova_product', true);

        $picanova_variations = $picanovaApi->getVariations($id);

        echo '<div class="picanova_options">' ;
        foreach ( $picanova_variations->data as $product ) {
            echo '<div class="picanova_options__item" data-value="' . $product->name .'" class="picanova_options__item">';
            foreach ($product->options as $option) {
                echo '<label><span>' . $option->name .'</span>';
                    echo '<select>';
                    foreach ($option->values as $value) {
                        echo '<option value="' . $value->name . '">' . $value->name . '</option>';
                    }
                    echo '</select>';
                echo '</label>';
            }
            echo '</div>';
        }
        echo '</div>';

    }
function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
    // Has our option been selected?

    if(isset($_POST["add_option"]) && isset($_POST["variation_id"])) {
        global $picanovaApi;
        $id = get_post_meta($_POST["product_id"], 'picanova_product', true);
        $picanonaVariations = $picanovaApi->getVariations($id);
        $picanonaVariations = $picanonaVariations->data;
        $picanova_variation_code = get_post_meta( $_POST["variation_id"], 'picanova_variation', true );
        $picanovaOptions = array();
        foreach ($picanonaVariations as $picanonaVariation) {
            if($picanonaVariation->code == $picanova_variation_code) {
                $picanovaOptions = $picanonaVariation->options;
            }

        }
        if(!empty($picanovaOptions)) {
            foreach ($_POST["add_option"] as $key=>$option) {
                foreach ($picanovaOptions->{$key}->values as $value) {
                    if($value->id == $option) {
                        $cart_item_data['addons'][$key] = array(
                            "id" => $option,
                            "price" => $value->price,
                            "item_name" => $value->name,
                            "option_name" => $picanovaOptions->{$key}->name,

                        );
                    }
                }
            }
        }

    }

   // if( ! empty( $_POST['extended_warranty'] ) ) {
//        $product = wc_get_product( $product_id );
//        $price = $product->get_price();
//        // Store the overall price for the product, including the cost of the warranty
//        $cart_item_data['warranty_price'] = $price + 250;
    //}
    return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'add_cart_item_data', 10, 3 );
function before_calculate_totals( $cart_obj ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    // Iterate through each cart item
    foreach( $cart_obj->get_cart() as $key=>$cart_item  ) {
        if(isset($cart_item["addons"])) {
            $price = $cart_item['data']->get_price();
            foreach ($cart_item["addons"] as $addon){
                $price+=$addon["price"];
            }
            $cart_item['data']->set_price(  $price  );
        }

        //var_dump($value['data']->get_price());
//        var_dump($value);
    }
}
add_action( 'woocommerce_before_calculate_totals', 'before_calculate_totals', 10, 1 );

add_filter('woocommerce_cart_item_name','add_usr_custom_session',1,3);
function add_usr_custom_session($product_name, $values, $cart_item_key ) {

    $return_string = $product_name . "<br />" ;// . "<br />" . print_r($values['_custom_options']);

    foreach ($values["addons"] as $addon) {

        $return_string .= "<b>".$addon['option_name']. ":</b> ".$addon['item_name'];
        if($addon['price'] != "") {
            $return_string .= " (+".wc_price($addon['price']).")";
        }

        $return_string .= "<br />";
    }

    return $return_string;
}

/**
 * TASK 2
 */
if ( ! function_exists( 'picanova_add_menu_page' ) ) {
    function picanova_add_menu_page() {
        global $picanova_changeable_options;

        if ( isset( $_POST['picanova_settings_nonce'] ) ) {
            if ( wp_verify_nonce( $_POST['picanova_settings_nonce'], 'picanova_settings_action' ) ) {
                foreach ( $_POST as $key => $post_data ) {
                    if ( array_key_exists( $key, $picanova_changeable_options ) ) {
                        update_option( PICANOVA_OPTION_PREFIX . $key, (float)filter_input( INPUT_POST, $key, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) );
                    }
                }
            } else {
                die ( 'Unauthorized access attempt!' );
            }
        }

        require_once 'view/settings-page.php';
    }

    add_action( 'admin_menu', function () {
        add_menu_page( 'Picanova', 'Picanova', 'administrator', 'picanova', 'picanova_add_menu_page', 'dashicons-admin-generic', '10.55' );
    });
}

/**
 * TASK 3
 */
if ( ! function_exists( 'picanova_package_rates' ) ) {
    function picanova_package_rates( $package_rates, $package ) {
        global $picanovaApi;

        $country_destination = $package['destination']['country'];
        $countries = $picanovaApi->getCountries();
        $country_id = 0;

        if ( isset( $countries->data ) && is_array( $countries->data ) ) {
            foreach ( $countries->data as $country ) {
                if ( $country->country_code == $country_destination ) {
                    $country_id = $country->country_id;
                }
            }
        }

        $cart = WC()->cart->get_cart();
        $item = array_pop($cart);
        $variation_code = get_post_meta( $item['data']->get_id(), 'picanova_variation', true );
        $variations = $picanovaApi->getVariations( $item['data']->get_parent_id() );
        $variant_id = 0;

        if ( isset( $variations->data ) && is_array( $variations->data ) ) {
            foreach ( $variations->data as $variation ) {
                if ( $variation_code == $variation->code ) {
                    $variant_id = $variation->variant_id;
                }
            }
        }

        $quantity = $item['quantity'];

        $rates = $picanovaApi->getShippingCost( $country_id, $quantity, $variant_id );

        if ( isset( $rates->data ) && is_array( $rates->data ) ) {
            return array(
                $rates->data['code'] => new WC_Shipping_Rate(
                    $rates->data['code'],
                    $rates->data['code'],
                    $rates->data['price']
                )
            );
        }

        return $package_rates;
    }

    add_filter( 'woocommerce_package_rates', 'picanova_package_rates', 10, 2 );
}
