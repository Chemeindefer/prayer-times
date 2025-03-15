<?php
if (!defined('ABSPATH')) {
    exit;
}

class PrayerAjaxHandler {
    public static function init() {
        $endpoints = [
            'get_countries',
            'get_cities',
            'get_districts',
            'get_place_id',
            'clear_cache'
        ];
        
        foreach ($endpoints as $endpoint) {
            add_action('wp_ajax_' . $endpoint, [self::class, $endpoint]);
            
            if ($endpoint !== 'clear_cache') {
                add_action('wp_ajax_nopriv_' . $endpoint, [self::class, $endpoint]);
            }
        }

        add_action('wp_ajax_get_saved_location', 'prayer_get_saved_location');

        add_action('wp_ajax_check_welcome_popup', 'check_welcome_popup_callback');
        add_action('wp_ajax_nopriv_check_welcome_popup', 'check_welcome_popup_callback');

        add_action('wp_ajax_hide_welcome_popup', 'hide_welcome_popup_callback');
        add_action('wp_ajax_nopriv_hide_welcome_popup', 'hide_welcome_popup_callback');
    }
    
    private static function verify_request() {
        if (!is_user_logged_in()) {
            return true;
        }
        
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'prayer-ajax-nonce')) {
            self::send_error_response(__('Invalid security token', 'prayer-times'), 403);
            return false;
        }
        
        return true;
    }
    
    private static function send_error_response($message, $status = 400) {
        wp_send_json_error([
            'message' => $message
        ], $status);
    }
    
    public static function get_countries() {
        self::verify_request();
        echo PrayerFetcher::fetch_countries();
        wp_die();
    }
    
    public static function get_cities() {
        if (!self::verify_request()) {
            return;
        }
        
        if (!isset($_POST['country']) || empty($_POST['country'])) {
            echo "<option value=''>" . esc_html__('Invalid country', 'prayer-times') . "</option>";
            wp_die();
        }
        
        $country = sanitize_text_field($_POST['country']);
        echo PrayerFetcher::fetch_cities($country);
        wp_die();
    }
    
    public static function get_districts() {
        if (!self::verify_request()) {
            return;
        }
        
        $required = ['country', 'city'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                echo "<option value=''>" . esc_html__('Invalid input', 'prayer-times') . "</option>";
                wp_die();
            }
        }
        
        $country = sanitize_text_field($_POST['country']);
        $city = sanitize_text_field($_POST['city']);
        
        echo PrayerFetcher::fetch_districts($country, $city);
        wp_die();
    }
    
    public static function get_place_id() {
        if (!self::verify_request()) {
            return;
        }
        
        $required = ['country', 'city', 'district'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                echo esc_html__('Invalid input', 'prayer-times');
                wp_die();
            }
        }
        
        $country = sanitize_text_field($_POST['country']);
        $city = sanitize_text_field($_POST['city']);
        $district = sanitize_text_field($_POST['district']);
        
        echo PrayerFetcher::fetch_place_id($country, $city, $district);
        wp_die();
    }
    
    public static function clear_cache() {
        if (!self::verify_request() || !current_user_can('manage_options')) {
            return;
        }
        
        PrayerFetcher::clear_cache();
        wp_send_json_success([
            'message' => __('Cache cleared successfully', 'prayer-times')
        ]);
    }
}

PrayerAjaxHandler::init();

function prayer_get_saved_location() {
    check_ajax_referer('prayer-ajax-nonce', 'nonce');
    
    wp_send_json([
        'country' => get_option('prayer_country', ''),
        'city' => get_option('prayer_city', ''),
        'district' => get_option('prayer_district', '')
    ]);
}

function check_welcome_popup_callback() {
    check_ajax_referer('prayer-ajax-nonce', 'nonce');
    
    $show_welcome = get_option('prayer_show_welcome', 'no');
    echo $show_welcome === 'yes' ? 'show' : 'hide';
    wp_die();
}

function hide_welcome_popup_callback() {
    check_ajax_referer('prayer-ajax-nonce', 'nonce');
    
    delete_option('prayer_show_welcome');
    wp_die();
}
