<?php

namespace Tests;

use App\POC_Chatbot_Admin;

class Test_Class_POC_Chatbot_Admin extends \WP_UnitTestCase
{
    public $admin;

    public $user_id;

    public function setUp()
    {
        parent::setUp();

        $this->admin = new POC_Chatbot_Admin();

        $this->user_id = $this->factory->user->create();
        $user = new \WP_User( $this->user_id );
        $user->add_role('administrator');
        wp_set_current_user( $this->user_id );
    }

    public function tearDown()
    {
        parent::tearDown();
        wp_delete_user( $this->user_id );
    }

    public function test_add_admin_menu()
    {
        global $submenu, $menu;

        $this->assertTrue( empty( $menu ) );
        $this->assertTrue( empty( $submenu ) );

        $this->admin->add_admin_menu();

        $this->assertSame( 1, count( $menu ) );
        $this->assertArrayHasKey( 'poc', $submenu );
    }
}