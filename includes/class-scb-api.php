<?php
if (!defined('ABSPATH')) { exit; }

class SCB_API {
    private static function get_base_url() {
        $domain = trim(get_option(SCB_OPTION_STORE_DOMAIN, '' ));
        $ver    = trim(get_option(SCB_OPTION_API_VERSION, '2024-10'));
        if (!$domain) {
            return new WP_Error('scb_missing_domain', 'Store domain not configured.');
        }
        return "https://{$domain}/admin/api/{$ver}";
    }

    private static function headers() {
        $token = trim(get_option(SCB_OPTION_ACCESS_TOKEN, ''));
        if (!$token) {
            return new WP_Error('scb_missing_token', 'Access token not configured.');
        }
        return array(
            'X-Shopify-Access-Token' => $token,
            'Content-Type'           => 'application/json',
            'Accept'                 => 'application/json'
        );
    }

    public static function rest_create_checkout( WP_REST_Request $req ) {
        $base = self::get_base_url();
        if (is_wp_error($base)) return $base;
        $headers = self::headers();
        if (is_wp_error($headers)) return $headers;

        $data = $req->get_json_params();
        $payload = array(
            'checkout' => array(
                'email' => sanitize_email($data['email'] ?? ''),
                'line_items' => array_map(function($item) {
                    return array(
                        'variant_id' => absint($item['variant_id'] ?? 0),
                        'quantity'   => absint($item['quantity'] ?? 1),
                    );
                }, is_array($data['line_items'] ?? []) ? $data['line_items'] : []),
                'shipping_address' => self::sanitize_address($data['shipping_address'] ?? array()),
                'billing_address'  => self::sanitize_address($data['billing_address'] ?? array()),
            )
        );

        $resp = wp_remote_post( "{$base}/checkouts.json", array(
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
            'timeout' => 30
        ));

        if (is_wp_error($resp)) return $resp;
        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 400) {
            return new WP_Error('scb_checkout_error', 'Create checkout failed', array('status' => $code, 'body' => $body));
        }
        return array(
            'ok' => true,
            'checkout' => array(
                'token' => $body['checkout']['token'] ?? null,
                'id'    => $body['checkout']['id'] ?? null,
                'web_url' => $body['checkout']['web_url'] ?? null
            )
        );
    }

    public static function rest_pay( WP_REST_Request $req ) {
        $base = self::get_base_url();
        if (is_wp_error($base)) return $base;
        $headers = self::headers();
        if (is_wp_error($headers)) return $headers;

        $data = $req->get_json_params();
        $token = sanitize_text_field($data['checkout_token'] ?? '');
        $amount = sanitize_text_field($data['amount'] ?? '');
        $vault_session_id = sanitize_text_field($data['vault_session_id'] ?? '');
        $billing_first_name = sanitize_text_field($data['billing_first_name'] ?? '');
        $billing_last_name  = sanitize_text_field($data['billing_last_name'] ?? '');

        if (!$token || !$vault_session_id) {
            return new WP_Error('scb_missing_fields', 'Missing checkout_token or vault_session_id.');
        }

        // May require different structure by API version/provider
        $payload = array(
            'payment' => array(
                'amount' => $amount,
                'session_id' => $vault_session_id,
                'unique_token' => wp_generate_uuid4(),
                'payment_processing_mode' => 'immediate',
                'billing_address' => array(
                    'first_name' => $billing_first_name,
                    'last_name'  => $billing_last_name
                )
            )
        );

        $resp = wp_remote_post( "{$base}/checkouts/{$token}/payments.json", array(
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
            'timeout' => 45
        ));

        if (is_wp_error($resp)) return $resp;
        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 400) {
            return new WP_Error('scb_pay_error', 'Payment failed', array('status' => $code, 'body' => $body));
        }
        return array('ok' => true, 'payment' => $body['payment'] ?? $body);
    }

    private static function sanitize_address($addr) {
        if (!is_array($addr)) $addr = array();
        return array(
            'first_name' => sanitize_text_field($addr['first_name'] ?? ''),
            'last_name'  => sanitize_text_field($addr['last_name'] ?? ''),
            'address1'   => sanitize_text_field($addr['address1'] ?? ''),
            'address2'   => sanitize_text_field($addr['address2'] ?? ''),
            'city'       => sanitize_text_field($addr['city'] ?? ''),
            'province'   => sanitize_text_field($addr['province'] ?? ''),
            'country'    => sanitize_text_field($addr['country'] ?? ''),
            'zip'        => sanitize_text_field($addr['zip'] ?? ''),
            'phone'      => sanitize_text_field($addr['phone'] ?? '')
        );
    }
}
