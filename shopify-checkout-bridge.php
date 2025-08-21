<?php
/**
 * Plugin Name: Shopify Checkout Bridge
 * Description: Developer-oriented bridge to run a Shopify checkout flow on a WordPress page via shortcode + REST, using a vault-first card tokenization pattern.
 * Version: 0.1.0
 * Author: Your Name
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

define('SCB_VERSION', '0.1.0');
define('SCB_OPTION_GROUP', 'scb_settings_group');
define('SCB_OPTION_STORE_DOMAIN', 'scb_store_domain');
define('SCB_OPTION_ACCESS_TOKEN', 'scb_access_token');
define('SCB_OPTION_API_VERSION', 'scb_api_version');

require_once plugin_dir_path(__FILE__) . 'includes/class-scb-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-scb-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-scb-shortcode.php';

add_action('init', function() {
    wp_register_script(
        'scb-checkout',
        plugins_url('public/js/checkout.js', __FILE__),
        array(),
        SCB_VERSION,
        true
    );
    wp_localize_script('scb-checkout', 'SCB', array(
        'restUrl' => esc_url_raw( rest_url('shopify-checkout-bridge/v1/') ),
        'nonce'   => wp_create_nonce('wp_rest')
    ));
});

add_action('admin_menu', function() {
    SCB_Admin::register_menu();
});
add_action('admin_init', function() {
    SCB_Admin::register_settings();
});

add_action('rest_api_init', function() {
    register_rest_route('shopify-checkout-bridge/v1', '/create-checkout', array(
        'methods'  => 'POST',
        'callback' => ['SCB_API', 'rest_create_checkout'],
        'permission_callback' => function() { return wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '', 'wp_rest' ); }
    ));
    register_rest_route('shopify-checkout-bridge/v1', '/pay', array(
        'methods'  => 'POST',
        'callback' => ['SCB_API', 'rest_pay'],
        'permission_callback' => function() { return wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '', 'wp_rest' ); }
    ));
});

add_shortcode('shopify_checkout', function($atts = []) {
    wp_enqueue_script('scb-checkout');
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/form-checkout.php';
    return ob_get_clean();
});
