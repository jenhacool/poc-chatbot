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
        '/poc-chatbot/v1/get_gift_link',
        '/poc-chatbot/v1/wincode_info',
        '/poc-chatbot/v1/get_sale_page',
        '/poc-chatbot/v1/customer_info',
        '/poc-chatbot/v1/match_order'
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

        $request = $this->create_new_request( array(
            'wincode' => 'TEST',
            'client_id' => '2980042722091199'
        ) );

        $response = $this->api->get_sale_page( $request );

        $this->assertSame( 200, $response->get_status() );

        $response_data = $response->get_data();

        $this->assertTrue( $response_data['success'] );
        $this->assertArrayHasKey( 'sale_page', $response_data['data'] );

        $sale_page = $response_data['data']['sale_page'];

        $transient_key = str_replace( 'http://example.com/?customer_key=', '', $sale_page );

        $transient_data = get_transient( $transient_key );

        $this->assertEquals( array(
            'client_id' => '2980042722091199',
            'wincode' => 'TEST'
        ), $transient_data );
    }

    public function test_get_customer_info()
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

	    $this->product->set_image_id( 1 );
	    $this->product->save();

	    $data = array(
		    'client_id' => '872807819873162',
		    'coupon' => array(
			    'code' => 'TEST',
			    'discount' => 10
		    ),
		    'product' => array(
			    'id' => $this->product->get_id(),
			    'title' => $this->product->get_title(),
			    'image' => 'image_url',
			    'currency_format' => 'left',
			    'currency_symbol' => '&pound;',
			    'attributes' => array(
				    array(
					    'name' => 'size',
					    'slug' => 'pa_size',
					    'terms' => array(
						    array(
							    'name' => 'small',
							    'slug' => 'small'
						    ),
						    array(
							    'name' => 'large',
							    'slug' => 'large'
						    ),
						    array(
							    'name' => 'huge',
							    'slug' => 'huge'
						    ),
					    ),
				    ),
				    array(
					    'name' => 'color',
					    'slug' => 'pa_color',
					    'terms' => array(
						    array(
							    'name' => 'red',
							    'slug' => 'red'
						    ),
						    array(
							    'name' => 'blue',
							    'slug' => 'blue'
						    )
					    )
				    ),
				    array(
					    'name' => 'number',
					    'slug' => 'pa_number',
					    'terms' => array(
						    array(
							    'name' => '2',
							    'slug' => '2'
						    ),
						    array(
							    'name' => '1',
							    'slug' => '1'
						    ),
						    array(
							    'name' => '0',
							    'slug' => '0'
						    ),
					    )
				    )
			    ),
			    'variations' => array(
				    array(
					    'regular_price' => 10.00,
					    'attributes' => array(
						    'attribute_pa_size' => 'small',
						    'attribute_pa_color' => '',
						    'attribute_pa_number' => ''
					    )
				    ),
				    array(
					    'regular_price' => 15.00,
					    'attributes' => array(
						    'attribute_pa_size' => 'large',
						    'attribute_pa_color' => '',
						    'attribute_pa_number' => ''
					    )
				    ),
				    array(
					    'regular_price' => 16.00,
					    'attributes' => array(
						    'attribute_pa_size' => 'huge',
						    'attribute_pa_color' => 'red',
						    'attribute_pa_number' => '0'
					    )
				    ),
				    array(
					    'regular_price' => 17.00,
					    'attributes' => array(
						    'attribute_pa_size' => 'huge',
						    'attribute_pa_color' => 'red',
						    'attribute_pa_number' => '2'
					    )
				    )
			    )
		    )
	    );

	    $customer_key = wp_generate_password( 13, false );

	    set_transient(
		    $customer_key,
		    array(
			    'client_id' => '872807819873162',
			    'wincode' => 'TEST'
		    )
	    );

	    $api = $this->getMockBuilder( POC_Chatbot_API::class )->setMethods( array( 'get_product_thumbnail' ) )->getMock();

	    $api->expects( $this->any() )->method( 'get_product_thumbnail' )->with( 1 )->willReturn( 'image_url' );

	    $request = $this->create_new_request( array(
	    	'customer_key' => $customer_key
	    ) );

	    $response = $api->get_customer_info( $request );

	    var_dump($response);

	    $this->assert_success_response( $response, $data );
    }

    public function test_match_order()
    {
        $settings = array(
	        'wincodes' => array(
	            array(
	                'wincode' => 'OFF50',
	                'product_id' => $this->product->get_id(),
	                'discount' => 50,
	                'link' => 'http://example.com'
	            )
	        )
	    );

        update_option( 'poc_chatbot_settings', serialize( $settings ) );

        $customer_key = wp_generate_password( 13, false );

        set_transient(
            $customer_key,
            array(
                'client_id' => '872807819873162',
                'wincode' => 'OFF50'
            )
        );

        $order = wc_create_order();
        $order->add_meta_data( 'fb_client_id', '872807819873162' );
        $order->save();

        $request = $this->create_new_request( array(
            'customer_key' => $customer_key,
            'attributes' => array(
	            'pa_size'   => 'huge',
	            'pa_color' => 'red',
	            'pa_number' => '0',
            ),
	        'quantity' => 2,
        ) );

        $response = $this->api->match_order( $request );

        $this->assertEquals( '16.00', wc_get_order( $order->get_id() )->get_total() );

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
