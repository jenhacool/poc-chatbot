<?php

namespace Tests;

use App\POC_Chatbot_API;
use Tests\Helpers\WC_Helper_Product;

class Test_Class_POC_Chatbot_API extends \WP_UnitTestCase
{
    public $api;

    public $product;

    public $helper;

    public $server;

    public $routes = array(
        '/poc-chatbot/v1/check_gift_code',
        '/poc-chatbot/v1/gift_checkout_link',
        '/poc-chatbot/v1/get_gift_link',
        '/poc-chatbot/v1/wincode_info',
        '/poc-chatbot/v1/get_sale_page',
    );

    public function setUp()
    {
        parent::setUp();

        global $wp_rest_server;

        $wp_rest_server = new \Spy_REST_Server();

        $this->server = $wp_rest_server;

        do_action( 'rest_api_init' );

        $this->api = new POC_Chatbot_API();

        $this->product = WC_Helper_Product::create_variation_product();
    }

    public function tearDown()
    {
        parent::tearDown();

        global $wp_rest_server;

        unset( $this->server );

        $wp_rest_server = null;

        wp_delete_post( $this->product->get_id() );

        delete_option( 'poc_chatbot_settings' );
    }

    public function test_register_routes()
    {
        $this->api->register_rest_routes();

        $actual_routes = $this->server->get_routes();

        $expected_routes = $this->routes;

        foreach ( $expected_routes as $expected_route ) {
            $this->assertArrayHasKey( $expected_route, $actual_routes );
        }
    }

    public function test_check_gift_code()
    {
        $request = $this->create_new_request( array(
            'gift_code' => 'haiyenhy',
        ) );

        $response = $this->api->check_gift_code( $request );

        $this->assert_success_response( $response, array(
            'is_correct' => true
        ) );
    }

    public function test_check_gift_code_with_wrong_code()
    {
        $request = $this->create_new_request( array(
            'gift_code' => 'wrong',
        ) );

        $response = $this->api->check_gift_code( $request );

        $this->assert_error_response( $response, array(
            'is_correct' => false
        ) );
    }

    public function test_create_gift_checkout_link()
    {
        $request = $this->create_new_request( array(
            'first_name' => 'Tien',
            'last_name' => 'Nguyen',
            'phone_number' => '0788338370',
            'email' => 'mrtienhp97@gmail.com',
            'gift_code' => 'haiyenhy',
            'product_id' => 11,
            'client_id' => '2980042722091199',
            'messenger_url' => 'https://m.me/492974120811420'
        ) );

        $response = $this->api->create_gift_checkout_link( $request );

        $this->assert_success_response( $response, array() );
    }

    public function test_get_gift_link()
    {
        $data = array(
            'first_name' => 'Tien',
            'last_name' => 'Nguyen',
            'phone_number' => '0788338370',
            'email' => 'mrtienhp97@gmail.com',
            'gift_code' => 'haiyenhy',
            'product_id' => 11,
            'client_id' => '2980042722091199',
            'messenger_url' => 'https://m.me/492974120811420'
        );

        $request = $this->create_new_request( $data );

        $response = $this->api->get_gift_link( $request );

        $this->assertSame( 200, $response->get_status() );

        $response_data = $response->get_data();

        $this->assertTrue( $response_data['success'] );

        $link = $response_data['data']['link'];

        $transient_key = str_replace( rtrim( get_home_url(), '/' ) . '/poc-gift/', '', $link );

        $transient_data = get_transient( $transient_key );

        $this->assertEquals( $data, $transient_data );
    }

    public function test_get_wincode_info()
    {
        $settings = array(
            'wincodes' => array(
                array(
                    'wincode' => 'TEST',
                    'product_id' => $this->product->get_id(),
                    'discount' => 10,
                    'link' => 'http://example.com'
                )
            )
        );

        update_option( 'poc_chatbot_settings', serialize( $settings ) );

        $request = $this->create_new_request( array(
            'wincode' => 'TEST',
            'client_id' => '2980042722091199'
        ) );

        $response = $this->api->get_wincode_info( $request );

        $this->assertSame( 200, $response->get_status() );

        $response_data = $response->get_data();

        $wincode_info = $response_data['data'];

        $this->assertTrue( $response_data['success'] );
        $this->assertEquals( 'TEST', $wincode_info['wincode'] );
        $this->assertEquals( 10, $wincode_info['discount'] );
        $this->assertEquals( $this->product->get_title(), $wincode_info['product'] );

        $sale_page = $wincode_info['sale_page'];

        $transient_key = str_replace( 'http://example.com/?customer_key=', '', $sale_page );

        $transient_data = get_transient( $transient_key );

        $this->assertEquals( array(
            'client_id' => '2980042722091199',
            'wincode' => 'TEST',
            'product_id' => $this->product->get_id(),
            'discount' => 10,
            'link' => 'http://example.com'
        ), $transient_data );
    }

    public function test_get_sale_page()
    {
        $settings = array(
            'wincodes' => array(
                array(
                    'wincode' => 'TEST',
                    'product_id' => $this->product->get_id(),
                    'discount' => 10,
                    'link' => 'http://example.com'
                )
            )
        );

        update_option( 'poc_chatbot_settings', serialize( $settings ) );

        $body_params = array(
            'wincode' => 'TEST',
            'client_id' => '2980042722091199'
        );

        $request = new \WP_REST_Request();

        $request->set_body_params( $body_params );

        $response = $this->api->get_sale_page( $request );

        $this->assert_success_response( $response, array() );
    }

    /**
     * Create new REST Request
     *
     * @param $data
     *
     * @return \WP_REST_Request
     */
    protected function create_new_request( $data )
    {
        $request = new \WP_REST_Request();

        $request->set_header( 'X-POC-Access-Token', '8wdT9UsxXd' );

        $request->set_header( 'Content-Type', 'application/json' );

        $request->set_body( json_encode( $data ) );

        return $request;
    }

    /**
     * Assert success response
     *
     * @param $response
     * @param $expected_data
     */
    protected function assert_success_response( $response, $expected_data )
    {
        $this->assertSame( 200, $response->get_status() );

        $response_data = $response->get_data();

        $this->assertTrue( $response_data['success'] );
        $this->assertEquals( $expected_data, $response_data['data'] );
    }

    /**
     * Assert error response
     *
     * @param $response
     * @param $expected_data
     */
    protected function assert_error_response( $response, $expected_data )
    {
        $this->assertSame( 400, $response->get_status() );

        $response_data = $response->get_data();

        $this->assertTrue( ! $response_data['success'] );
        $this->assertEquals( $expected_data, $response_data['data'] );
    }
}
