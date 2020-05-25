<?php

/**
 * Plugin Name: POC Chatbot
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constant variables
define( 'POC_CHATBOT_PLUGIN_FILE', __FILE__ );
define( 'POC_CHATBOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POC_CHATBOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';

use App\POC_Chatbot;

// Register activation hook
register_activation_hook( POC_CHATBOT_PLUGIN_FILE, array( 'POC_Chatbot', 'activate' ) );

// Register deactivation hook
register_deactivation_hook( POC_CHATBOT_PLUGIN_FILE, array( 'POC_Chatbot', 'deactivate' ) );

// Run plugin
POC_Chatbot::instance();