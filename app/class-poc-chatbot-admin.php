<?php

namespace App;

class POC_Chatbot_Admin
{
    public function add_admin_menu()
    {
        $this->add_menu_pages();

        $this->add_submenu_pages();
    }

    protected function add_menu_pages()
    {
        add_menu_page(
            'POC',
            'POC',
            'manage_options',
            'poc',
            ''
        );
    }

    protected function add_submenu_pages()
    {
        add_submenu_page(
            'poc',
            'Chatbot',
            'Chatbot',
            'manage_options',
            'poc-chatbot',
            array( $this, 'chatbot_page' )
        );
    }

    public function chatbot_page()
    {
        if ( isset( $_POST['poc_chatbot_save_settings'] ) && wp_verify_nonce( $_POST['poc_chatbot_save_settings'], 'poc_chatbot_save_settings' ) ) {
            update_option( 'poc_chatbot_settings', serialize( $_POST['settings'] ) );
        }

        $default_settings = serialize( array(
            'wincodes' => array()
        ) );

        $settings = get_option( 'poc_chatbot_settings' );

        $settings = ( $settings ) ? unserialize( $settings ) : $default_settings;

        include_once POC_CHATBOT_PLUGIN_DIR . 'views/admin/html-admin-page-chatbot.php';
    }
}