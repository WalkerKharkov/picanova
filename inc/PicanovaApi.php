<?php

class PicanovaApi
{

    private $product_url = 'https://api.picanova.com/api/beta/products';
    private $variations_url = 'https://api.picanova.com/api/beta/products/';

    private $countries_url = 'https://api.picanova.com/api/beta/countries';
    private $shipping_url = 'https://api.picanova.com/api/beta/shipping/rates';

    private $apiCreds = array(
        'API_User'=>'filatovalex',
        'API_Key'=>'d0482176cd0c45441cdd370c09309d1f',
    );

    public function getProducts()
    {
        return json_decode($this->request($this->product_url ));
    }

    /**
     * TASK 2
     *
     */
    public function getVariations($id)
    {
        global $picanova_changeable_options;

        $percents = array();

        foreach ( $picanova_changeable_options as $key => $option ) {
            $percents[$key] = get_option( PICANOVA_OPTION_PREFIX . $key );
        }

        $variations = json_decode($this->request($this->variations_url . $id ));

        foreach ( $variations->data as $variation_key => &$variation ) {
            foreach ( $variation->options as $variation_option_key => &$option ) {
                $changeable_option_key = array_search( $option->name, $picanova_changeable_options );

                if ( $changeable_option_key !== false ) {
                    foreach ( $option->values as $variation_option_value_key => &$option_item ) {
                        $new_price = $option_item->price + $option_item->price * $percents[$changeable_option_key] / 100;
                        $new_formatted_price = (string)$new_price . ' ' . get_woocommerce_currency_symbol( $option_item->price_details->currency );
                        $option_item->price = $new_price;
                        $option_item->price_details->formatted = $new_formatted_price;
                    }

                    unset ( $option_item );
                }
            }

            unset( $option );
        }

        unset( $variation );

        return $variations;
    }

    /**
     * TASK 3
     */
    public function getCountries() {
        return json_decode( $this->request( $this->countries_url ) );
    }

    /**
     * TASK 3
     */
    public function getShippingCost( $country_id, $quantity, $variant_id ) {
        $result = "";

        try {
            $ch = curl_init();

            // Check if initialization had gone wrong*
            if ( $ch === false ) {
                throw new Exception( 'failed to initialize' );
            }

            $headers = array(
                'accept: application/json',
                'Content-type: application/json',
                'Authorization: Basic ' . base64_encode( $this->apiCreds['API_User'] . ":" . $this->apiCreds['API_Key'] )
            );

            $curl_post_data = array(
                'shipping' => array(
                    'country' => $country_id
                ),
                'items' => array(
                    'quantity' => $quantity,
                    'variant_id' => $variant_id
                )
            );


            curl_setopt( $ch, CURLOPT_URL, $this->shipping_url );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $curl_post_data );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );

            $result = curl_exec( $ch );

            // Check the return value of curl_exec(), too
            if ( $result === false ) {
                throw new Exception( curl_error( $ch ), curl_errno( $ch ) );
            }

            // Close curl handle
            curl_close( $ch );
        } catch( Exception $e ) {

            trigger_error(
                sprintf(
                    'Curl failed with error #%d: %s',
                    $e->getCode(), $e->getMessage()
                ),
                E_USER_ERROR
            );
        }

        return $result;
    }

    private function request($url)
    {
        $result = "";
        try {
            $ch = curl_init();

            // Check if initialization had gone wrong*
            if ($ch === false) {
                throw new Exception('failed to initialize');
            }
            $headers = array(
                'accept: application/json',
                'Authorization: Basic '.base64_encode($this->apiCreds['API_User'].":".$this->apiCreds['API_Key'])
            );
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         //   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            $result = curl_exec($ch);
            // Check the return value of curl_exec(), too
            if ($result === false) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }

            /* Process $content here */

            // Close curl handle
            curl_close($ch);
        } catch(Exception $e) {

            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
                E_USER_ERROR);

        }

        return $result;
    }

}
