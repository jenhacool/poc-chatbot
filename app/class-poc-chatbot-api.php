<?php

namespace App;

class POC_Chatbot_API
{
    const CHATBOT_ACCESS_TOKEN = 'f366d3eaeacba4f0c7a23ca752d9d615100905085ab2fb180b70afc4c3f6d9da';

    protected $access_token = '8wdT9UsxXd';

    protected $namespace = 'poc-chatbot/v1';

    public function register_rest_routes()
    {
        register_rest_route(
            $this->namespace,
            '/check_gift_code',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'check_gift_code' )
            )
        );

        register_rest_route(
            $this->namespace,
            '/get_gift_link',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_gift_link' )
            )
        );

        register_rest_route(
            $this->namespace,
            '/wincode_info',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_wincode_info' )
            )
        );

        register_rest_route(
            $this->namespace,
            '/get_sale_page',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_sale_page' )
            )
        );

        register_rest_route(
            $this->namespace,
            '/customer_info',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_customer_info' )
            )
        );

        register_rest_route(
            $this->namespace,
            '/match_order',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'match_order' )
            )
        );
    }

    /**
     * Check gift code
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function check_gift_code( $request )
    {
        $this->validate_request( $request );

        $params = $request->get_json_params();

        if( ! isset( $params['gift_code'] ) ) {
            return $this->error_response( array(
                'is_correct' => false
            ) );
        }

        $gift_code = $params['gift_code'];

        $response = wp_remote_get( "https://api.hostletter.com/api/poc_user/" . strtolower( $gift_code ) );

        if( is_wp_error( $response ) ) {
            return $this->error_response( array(
                'is_correct' => false
            ) );
        }

        $body = wp_remote_retrieve_body( $response );

        $data = json_decode( $body, true );

        if( is_null( $data ) || $data['message'] != 'success' || empty( $data['data'] ) ) {
            return $this->error_response( array(
                'is_correct' => false
            ) );
        }

        return $this->success_response( array(
            'is_correct' => true
        ) );
    }

    /**
     * Get gift link
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function get_gift_link( $request )
    {
        $data = array(
            'first_name' => '',
            'last_name' => '',
            'phone_number' => '',
            'email' => '',
            'gift_code' => '',
            'product_id' => '',
            'client_id' => '',
            'messenger_url' => ''
        );

        $params = $request->get_json_params();

        $transient_data = array_merge( $data, $params );

        $transient_key = wp_generate_password( 13, false );

        set_transient( $transient_key, $transient_data, DAY_IN_SECONDS );

        $url = rtrim( get_home_url(), '/' ) . '/poc-gift/' . $transient_key;

        return $this->success_response( array(
            'link' => $url
        ) );
    }

    /**
     * Get wincode information
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function get_wincode_info( $request )
    {
        $params = $request->get_json_params();

        $data = array(
            'client_id' => $params['client_id']
        );

        $settings = unserialize( get_option( 'poc_chatbot_settings' ) );

        $wincode_setting = null;

        foreach( $settings['wincodes'] as $wincode ) {
            if( $wincode['wincode'] === $params['wincode'] ) {
                $wincode_setting = $wincode;
                break;
            }
        }

        if( is_null( $wincode_setting ) ) {
            return $this->error_response();
        }

        $data = array_merge( $data, $wincode_setting );

        $transient_key = wp_generate_password( 13, false );

        set_transient( $transient_key, $data, HOUR_IN_SECONDS );

        $response_data = array(
            'wincode' => $params['wincode'],
            'discount' => $wincode_setting['discount'],
            'product' => ''
        );

        $product = wc_get_product( $wincode_setting['product_id'] );

        $response_data['product'] = $product->get_title();

        return $this->success_response( $response_data );
    }

    /**
     * Get sale page
     *
     * @param $request
     *
     * @return \WP_REST_Response
     */
    public function get_sale_page( $request )
    {
        $params = $request->get_json_params();

        $settings = unserialize( get_option( 'poc_chatbot_settings' ) );

        $wincode_setting = null;

        foreach( $settings['wincodes'] as $wincode ) {
            if( $wincode['wincode'] === $params['wincode'] ) {
                $wincode_setting = $wincode;
                break;
            }
        }

        if( is_null( $wincode_setting ) ) {
            return $this->error_response();
        }

        $data = array_merge( array(
            'client_id' => '',
            'wincode' => '',
        ), $params );

        $transient_key = wp_generate_password( 13, false );

        set_transient( $transient_key, $data, HOUR_IN_SECONDS );

        $url = rtrim( $wincode_setting['link'], '/' ) . '/?customer_key=' . $transient_key;

        return $this->success_response( array(
            'sale_page' => $url
        ) );
    }

    public function get_customer_info( $request )
    {
	    $params = $request->get_json_params();

	    if( ! isset( $params['customer_key'] ) ) {
		    return $this->error_response();
	    }

	    $transient_data = get_transient( $params['customer_key'] );

	    if( ! $transient_data ) {
		    return $this->error_response();
	    }

	    $settings = unserialize( get_option( 'poc_chatbot_settings' ) );

	    $wincode_setting = null;

	    foreach( $settings['wincodes'] as $wincode ) {
		    if( $wincode['wincode'] === $transient_data['wincode'] ) {
			    $wincode_setting = $wincode;
			    break;
		    }
	    }

	    if( is_null( $wincode_setting ) ) {
		    return $this->error_response('abc');
	    }

	    $data = array(
	    	'client_id' => $transient_data['client_id'],
		    'coupon' => array(
			    'code' => $wincode_setting['wincode'],
			    'discount' => $wincode_setting['discount']
		    ),
		    'product' => $this->get_product_info( $wincode_setting['product_id'] )
	    );

	    return $this->success_response( $data );
    }

    protected function get_product_info( $product_id )
    {
	    $product = wc_get_product( $product_id );

	    if( ! $product ) {
	    	return $this->error_response();
	    }

	    $data = array(
	    	'id' => $product->get_id(),
	    	'title' => $product->get_title(),
		    'image' => $this->get_product_thumbnail( $product->get_image_id() ),
		    'currency_format' => get_option( 'woocommerce_currency_pos' ),
		    'currency_symbol' => get_woocommerce_currency_symbol(),
		    'attributes' => array(),
		    'variations' => array()
	    );

	    if( ! $product->is_type( 'variable' ) ) {
	    	return $data;
	    }

	    $attributes = $product->get_attributes();

	    foreach ( $attributes as $attribute ) {
	    	$attribute_data = array();
	    	$attribute_data['name'] = $attribute->get_taxonomy_object()->attribute_label;
	    	$attribute_data['slug'] = $attribute->get_name();
	    	$terms_data = array();

	    	foreach ( $attribute->get_terms() as $term ) {
	    		$term = $term->to_array();

			    $terms_data[] = array(
	    			'name' => $term['name'],
				    'slug' => $term['slug']
			    );
		    }

	    	$attribute_data['terms'] = array_reverse( $terms_data );

		    $data['attributes'][] = $attribute_data;
	    }

	    $variations = $product->get_available_variations();

	    foreach ( $variations as $variation ) {
	    	$data['variations'][] = array(
	    		'regular_price' => $variation['display_regular_price'],
			    'attributes' => $variation['attributes']
		    );
	    }

	    return $data;
    }

    protected function get_product_thumbnail( $image_id )
    {
    	return wp_get_attachment_url( $image_id );
    }

    /**
     * Match order
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     * @throws \WC_Data_Exception
     */
    public function match_order( $request )
    {
        global $wpdb;

        $params = $request->get_json_params();

        if( ! isset( $params['customer_key'] ) ) {
            return $this->error_response();
        }

        $data = get_transient( $params['customer_key'] );

        if( ! $data ) {
            return $this->error_response();
        }

        $settings = unserialize( get_option( 'poc_chatbot_settings' ) );

        $wincode_setting = null;

        foreach( $settings['wincodes'] as $wincode ) {
            if( $wincode['wincode'] === $data['wincode'] ) {
                $wincode_setting = $wincode;
                break;
            }
        }

        if( is_null( $wincode_setting ) ) {
            return $this->error_response();
        }

        $client_id = $data['client_id'];

        $results = $wpdb->get_results(
            "select post_id, meta_key from $wpdb->postmeta where meta_value = '$client_id' ORDER BY post_id DESC LIMIT 1",
            ARRAY_A
        );

        $order = wc_get_order( $results[0]['post_id'] );

        if( ! $order ) {
            return $this->error_response();
        }

        $product = wc_get_product( $wincode_setting['product_id'] );

        if( ! $product ) {
            return $this->error_response();
        }

        $variation_id = null;

        if( ! empty( $params['attributes'] ) ) {
            $variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
                new \WC_Product_Variable( $wincode_setting['product_id'] ),
                $params['attributes']
            );
        }

        if( $variation_id ) {
            $product = new \WC_Product_Variation( $variation_id );
        }

        $order->add_product( $product, $params['quantity'] );

        foreach( $order->get_items() as $order_item ){
            if( $order_item->get_product_id() != $wincode_setting['product_id'] ) {
                continue;
            }

            $total = $order_item->get_total();
            $order_item->set_subtotal($total);
            $discount_total = (float) ( $total * ( $wincode_setting['discount'] / 100 ) );

            $order_item->set_total($total - $discount_total);
            $order_item->save();
        }

        $item = new \WC_Order_Item_Coupon();

        $item->set_props(
            array(
                'code' => $wincode_setting['wincode'],
                'discount' => $wincode_setting['discount'],
                'discount_tax' => 0
            )
        );

        $order->add_item( $item );

        $order->calculate_totals();

        $order->save();

        $this->delete_transient( $params['customer_key'] );

        return $this->success_response();
    }

    /**
     * Validate request
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    protected function validate_request( $request )
    {
        $access_token = $request->get_header( 'X-POC-Access-Token' );

        if( $access_token != $this->access_token ) {
            return $this->error_response( array(
                'message' => 'Wrong access token'
            ) );
        }
    }

    /**
     * Get Chatbot API Url
     *
     * @param $client_id
     *
     * @return string
     */
    protected function get_chatbot_api_url( $client_id )
    {
        return 'https://api.botbanhang.vn/v1.3/public/json?access_token=' . self::CHATBOT_ACCESS_TOKEN . '&psid=' . $client_id;
    }

    /**
     * Send request to Chatbot API
     *
     * @param $client_id
     * @param $body
     *
     * @return array|\WP_Error
     */
    protected function send_chatbot_api_request( $client_id, $body )
    {
        return wp_remote_post(
            $this->get_chatbot_api_url( $client_id ),
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'data_format' => 'body',
                'body' => json_encode( $body )
            )
        );
    }

    /**
     * Parse response from Chatbot API
     *
     * @param $response
     *
     * @return bool
     */
    protected function parse_chatbot_api_response( $response )
    {
        if( is_wp_error( $response ) ) {
            return false;
        }

        $response_data = json_decode( wp_remote_retrieve_body( $response ), true );

        if( ! $response_data || ! $response_data['success'] ) {
            return false;
        }

        return true;
    }

    /**
     * Response
     *
     * @param $data
     * @param  int  $status
     *
     * @return \WP_REST_Response
     */
    protected function response( $data = array(), $status = 200 )
    {
        $response = new \WP_REST_Response( $data );

        $response->set_status( $status );

        return $response;
    }

    /**
     * Success response
     *
     * @param $data
     *
     * @return \WP_REST_Response
     */
    protected function success_response( $data = array() )
    {
        return $this->response( array(
            'data' => $data,
            'success' => true
        ), 200 );
    }

    /**
     * Error response
     *
     * @param $data
     *
     * @return \WP_REST_Response
     */
    protected function error_response( $data = array() )
    {
        return $this->response( array(
            'data' => $data,
            'success' => false
        ), 400 );
    }

    /**
     * Delete transient
     *
     * @param $transient_key
     */
    protected function delete_transient( $transient_key )
    {
        delete_transient( $transient_key );
    }

    protected function get_select_attributes_block_name()
    {
        return '5edf5566525b7a0012d6c9e6';
    }

    protected function get_order_success_block()
    {
        return '5edf3117df64940012fcfada';
    }
}
