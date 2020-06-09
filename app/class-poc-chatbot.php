<?php

namespace App;

use App\Utilities\SingletonTrait;

class POC_Chatbot
{
    use SingletonTrait;

    public $api;

    public $ajax;

    public $admin;

    /**
     * POC_Chatbot constructor.
     */
    protected function __construct()
    {
        $this->init_classes();

        $this->add_hooks();
    }

    /**
     * Init dependency classes
     */
    protected function init_classes()
    {
        $this->api = new POC_Chatbot_API();

        $this->ajax = new POC_Chatbot_AJAX();

        $this->admin = new POC_Chatbot_Admin();
    }

    /**
     * Add hooks
     */
    protected function add_hooks()
    {
        add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );

        add_action( 'rest_api_init', array( $this->api, 'register_rest_routes' ) );

        add_action( 'init', array( $this, 'add_rewrite_rules' ) );

        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

        add_filter( 'template_include', array( $this, 'include_template' ) );

        add_action( 'wp_ajax_poc_chatbot_get_gift', array( $this->ajax, 'get_gift' ) );

        add_action( 'wp_ajax_nopriv_poc_chatbot_get_gift', array( $this->ajax, 'get_gift' ) );

        add_filter( 'rest_pre_serve_request', function() {
            header( 'Access-Control-Allow-Origin: *' );
            header( 'Access-Control-Allow-Headers: X-POC-Access-Token', false );
        } );
    }

    /**
     * Add custom rewrite rules
     */
    public function add_rewrite_rules()
    {
        add_rewrite_rule( 'poc-gift/([^/]+)', 'index.php?pagename=poc_get_gift&customer_key=$matches[1]', 'top' );
    }

    /**
     * Add custom query vars
     *
     * @param $query_vars
     *
     * @return array
     */
    public function add_query_vars( $query_vars  )
    {
        $query_vars[] = 'customer_key';

        return $query_vars;
    }

    /**
     * Include custom template
     *
     * @param $original_template
     *
     * @return string
     */
    public function include_template( $original_template )
    {
        if( get_query_var( 'customer_key' ) && get_query_var( 'pagename' ) === 'poc_get_gift' ) {
            return POC_CHATBOT_PLUGIN_DIR . 'views/gift.php';
        }

        return $original_template;
    }

    /**
     * On activate plugin
     */
    public static function activate()
    {
        flush_rewrite_rules();
    }

    /**
     * On deactivate plugin
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}