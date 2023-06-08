<?php
class PicanovaProductAdmin {
    function __construct()
    {
        add_action('wp_ajax_getPicanovaProductVariants', array($this, 'getPicanovaProductVariants'));
        add_action('woocommerce_product_options_inventory_product_data', array($this, 'addPicanovaProductsSelect'));
        add_action( 'woocommerce_variation_options_pricing',  array($this, 'addPicanovaProductVariationSelect'), 10, 3 );
        add_action( 'woocommerce_process_product_meta', array($this,'saveProduct') );

    }
    function getPicanovaProductVariants()
    {
        $picanova = new PicanovaApi();
        echo json_encode($picanova->getVariations( intval($_POST['picanova_product']) ));
        wp_die();
    }
    function addPicanovaProductsSelect() {

        global $picanovaApi;
        $picanova_products = $picanovaApi->getProducts();
        $options[''] = __( 'Select a value', 'woocommerce');

        foreach ($picanova_products->data as $product)
            $options[$product->id] = $product->name;

        $args = array(
            'id' => 'picanova_product',
            'label' => __( 'Picanova Product', 'cfwc' ),
            'class' => 'cfwc-custom-field',
            'desc_tip' => true,
            'description' => __( 'Select picanova type product.', 'ctwc' ),
            'options' => $options
        );
        woocommerce_wp_select( $args );

    }
    function addPicanovaProductVariationSelect( $loop, $variation_data, $variation ) {

        global $picanovaApi;
        global $post;

        $id = get_post_meta($post->ID, 'picanova_product', true);

        $picanova_variations = $picanovaApi->getVariations($id);

        $options[''] = __( 'Select a value', 'woocommerce');

        foreach ($picanova_variations->data as $product)
            $options[$product->code] = $product->name.' - '.$product->price_details->formatted;

        $args = array(
            'id' => 'picanova_variation[' . $loop . ']',
            'label' => __( 'Picanova variations', 'cfwc' ),
            'class' => 'cfwc-custom-field',
            'desc_tip' => true,
            'description' => __( 'Select picanova type variations.', 'ctwc' ),
            'options' => $options,
            'value'  => get_post_meta( $variation->ID, 'picanova_variation', true ),
        );
        woocommerce_wp_select( $args );
    }

    function saveProduct( $post_id ){
        $woocommerce_select = $_POST['picanova_product'];
        if( !empty( $woocommerce_select ) )
            update_post_meta( $post_id, 'picanova_product', esc_attr( $woocommerce_select ) );
        else {
            update_post_meta( $post_id, 'picanova_product',  '' );
        }
    }
}
new PicanovaProductAdmin();
