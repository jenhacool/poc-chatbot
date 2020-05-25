<?php

namespace Tests;

use App\POC_Chatbot;

class Test_Class_POC_Chatbot extends \WP_UnitTestCase
{
    public $instance;

    public function setUp()
    {
        parent::setUp();

        $this->instance = POC_Chatbot::instance();
    }

    public function test_add_hooks()
    {
        $this->assertGreaterThan(
            0,
            has_action(
                'admin_menu',
                array( $this->instance->admin, 'add_admin_menu' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_action(
                'rest_api_init',
                array( $this->instance->api, 'register_rest_routes' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_action(
                'init',
                array( $this->instance, 'add_rewrite_rules' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_filter(
                'query_vars',
                array( $this->instance, 'add_query_vars' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_filter(
                'template_include',
                array( $this->instance, 'include_template' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_action(
                'wp_ajax_poc_chatbot_get_gift',
                array( $this->instance->ajax, 'get_gift' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_action(
                'wp_ajax_nopriv_poc_chatbot_get_gift',
                array( $this->instance->ajax, 'get_gift' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_action(
                'wp_ajax_poc_chatbot_match_order',
                array( $this->instance->ajax, 'match_order' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_action(
                'wp_ajax_nopriv_poc_chatbot_match_order',
                array( $this->instance->ajax, 'match_order' )
            )
        );

        $this->assertGreaterThan(
            0,
            has_action(
                'wp_enqueue_scripts',
                array( $this->instance, 'add_scripts' )
            )
        );
    }

    public function test_add_rewrite_rules()
    {
        global $wp_rewrite;

        $this->instance->add_rewrite_rules();

        $this->assertArrayHasKey( 'poc-gift/([^/]+)', $wp_rewrite->extra_rules_top );
        $this->assertSame( 'index.php?page=poc_get_gift&customer_key=$matches[1]', $wp_rewrite->extra_rules_top['poc-gift/([^/]+)'] );
    }

    public function test_add_query_vars()
    {
        $default_query_vars = array();

        $query_vars = $this->instance->add_query_vars( $default_query_vars );

        $this->assertEquals( 'customer_key', $query_vars[0] );
    }

    public function test_include_template()
    {
        $template = $this->instance->include_template( 'original_template' );

        $this->assertEquals( 'original_template', $template );

        set_query_var( 'page', 'poc_get_gift' );
        set_query_var( 'customer_key', 'test_customer_key' );

        $template = $this->instance->include_template( 'original_template' );

        $this->assertEquals( POC_CHATBOT_PLUGIN_DIR . 'views/gift.php', $template );
    }

    public function add_scripts()
    {
        global $wp_scripts;

        $this->assertTrue( ! isset( $wp_scripts->registered['poc_chatbot'] ) );

        $this->instance->add_scripts();

        $this->assertArrayHasKey( 'poc_chatbot', $wp_scripts->registered );
    }
}