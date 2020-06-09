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

        $url = rtrim( $data['link'], '/' ) . '/?customer_key=' . $transient_key;

        $response_data = array(
            'wincode' => $params['wincode'],
            'discount' => $wincode_setting['discount'],
            'product' => '',
            'attributes' => array()
        );

        $product = wc_get_product( $wincode_setting['product_id'] );

        $response_data['product'] = $product->get_title();

        $attributes = $product->get_attributes();

        foreach ( $attributes as $attribute ) {
            $options = array();

            $terms = $attribute->get_terms();

            foreach ($terms as $term) {
                $options[$term->slug] = $term->name;
            }

            $response_data['attributes'][$attribute->get_taxonomy()] = $options;
        }

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
            'attributes' => '',
        ), $params );

        $transient_key = wp_generate_password( 13, false );

        set_transient( $transient_key, $data, HOUR_IN_SECONDS );

        $url = rtrim( $wincode_setting['link'], '/' ) . '/?customer_key=' . $transient_key;

        return $this->success_response( array(
            'sale_page' => $url
        ) );
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
}
