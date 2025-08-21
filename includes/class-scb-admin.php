<?php
if (!defined('ABSPATH')) { exit; }

class SCB_Admin {
    public static function register_menu() {
        add_options_page(
            'Shopify Checkout Bridge',
            'Shopify Checkout',
            'manage_options',
            'scb-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting(SCB_OPTION_GROUP, SCB_OPTION_STORE_DOMAIN);
        register_setting(SCB_OPTION_GROUP, SCB_OPTION_ACCESS_TOKEN);
        register_setting(SCB_OPTION_GROUP, SCB_OPTION_API_VERSION);
        add_settings_section('scb_main', 'API Settings', function() {
            echo '<p>Configure your Shopify Admin API credentials (custom app). Keep tokens secret.</p>';
        }, 'scb-settings');

        add_settings_field(SCB_OPTION_STORE_DOMAIN, 'Store Domain', [__CLASS__, 'field_store_domain'], 'scb-settings', 'scb_main');
        add_settings_field(SCB_OPTION_ACCESS_TOKEN, 'Admin Access Token', [__CLASS__, 'field_access_token'], 'scb-settings', 'scb_main');
        add_settings_field(SCB_OPTION_API_VERSION, 'API Version', [__CLASS__, 'field_api_version'], 'scb-settings', 'scb_main');
    }

    public static function field_store_domain() {
        $v = esc_attr(get_option(SCB_OPTION_STORE_DOMAIN, 'your-store.myshopify.com'));
        echo '<input type="text" name="'.SCB_OPTION_STORE_DOMAIN.'" value="'.$v.'" class="regular-text" />';
    }
    public static function field_access_token() {
        $v = esc_attr(get_option(SCB_OPTION_ACCESS_TOKEN, 'shpat_xxx'));
        echo '<input type="password" name="'.SCB_OPTION_ACCESS_TOKEN.'" value="'.$v.'" class="regular-text" />';
    }
    public static function field_api_version() {
        $v = esc_attr(get_option(SCB_OPTION_API_VERSION, '2024-10'));
        echo '<input type="text" name="'.SCB_OPTION_API_VERSION.'" value="'.$v.'" class="small-text" />';
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>Shopify Checkout Bridge</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(SCB_OPTION_GROUP);
        do_settings_sections('scb-settings');
        submit_button();
        echo '</form>';
        echo '<hr/>';
        echo '<h2>How it works</h2>';
        echo '<ol><li>Add the shortcode <code>[shopify_checkout]</code> to a page.</li>';
        echo '<li>Card data is sent <strong>directly</strong> from the browser to Shopify Card Vault to obtain <code>vault_session_id</code>.</li>';
        echo '<li>WP calls the Admin API to create the checkout and payment.</li></ol>';
        echo '</div>';
    }
}
