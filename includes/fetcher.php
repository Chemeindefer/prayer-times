<?php
if (!defined('ABSPATH')) {
    exit;
}

class PrayerFetcher {
    private static $api_base_url = 'https://vakit.vercel.app/api';
    
    private static $cache_expiration = 86400;
    
    public static function fetch_countries() {
        $cache_key = 'prayer_countries_list';
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $url = self::$api_base_url . "/countries";
        $response = self::make_api_request($url);
        
        if (is_wp_error($response)) {
            self::log_error('Failed to fetch countries', $response->get_error_message());
            return "<option value=''>" . esc_html__('Countries could not be loaded', 'prayer-times') . "</option>";
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data || !is_array($data)) {
            self::log_error('Invalid countries data received', wp_remote_retrieve_body($response));
            return "<option value=''>" . esc_html__('Country not found', 'prayer-times') . "</option>";
        }
        
        $countries = "<option value=''>" . esc_html__('Choose Country', 'prayer-times') . "</option>";
        foreach ($data as $country) {
            $countries .= sprintf(
                '<option value="%s">%s</option>', 
                esc_attr($country['name']), 
                esc_html($country['name'])
            );
        }
        
        set_transient($cache_key, $countries, self::$cache_expiration);
        
        return $countries;
    }
    
    public static function fetch_prayer_times($place_id) {
        if (empty($place_id)) {
            self::log_error('No place ID provided for prayer times');
            return false;
        }
        
        $cache_key = 'prayer_prayer_times_' . $place_id;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $today = date('Y-m-d');
        $days = date('t');
        
        $timezone_offset = self::get_timezone_offset();
        
        $url = sprintf(
            "%s/timesForPlace?id=%s&days=%d&timezoneOffset=%d&date=%s",
            self::$api_base_url,
            $place_id,
            $days,
            $timezone_offset,
            $today
        );
        
        $response = self::make_api_request($url, 'prayer_times');
        
        if (is_wp_error($response)) {
            self::log_error('Prayer Times API Error', $response->get_error_message());
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($data['times']) || empty($data['times'])) {
            self::log_error('No times data found in API response', wp_remote_retrieve_body($response));
            return false;
        }
        
        $times = $data['times'];
        ksort($times);
        
        $processed = [];
        foreach ($times as $date => $times_array) {
            $processed[] = [
                'date' => $date,
                'imsak' => $times_array[0] ?? '--:--',
                'gunes' => $times_array[1] ?? '--:--',
                'ogle' => $times_array[2] ?? '--:--',
                'ikindi' => $times_array[3] ?? '--:--',
                'aksam' => $times_array[4] ?? '--:--',
                'yatsi' => $times_array[5] ?? '--:--'
            ];
        }
        
        set_transient($cache_key, $processed, self::$cache_expiration);
        
        return $processed;
    }
    
    public static function fetch_cities($country) {
        if (empty($country)) {
            return "<option value=''>" . esc_html__('Invalid country', 'prayer-times') . "</option>";
        }
        
        $cache_key = 'prayer_cities_' . md5($country);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $url = self::$api_base_url . "/regions?country=" . urlencode($country);
        $response = self::make_api_request($url);
        
        if (is_wp_error($response)) {
            self::log_error('Failed to fetch cities', $response->get_error_message());
            return "<option value=''>" . esc_html__('Cities could not be loaded', 'prayer-times') . "</option>";
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data || !is_array($data)) {
            self::log_error('Invalid cities data received', wp_remote_retrieve_body($response));
            return "<option value=''>" . esc_html__('City not found', 'prayer-times') . "</option>";
        }
        
        $cities = "<option value=''>" . esc_html__('Choose City', 'prayer-times') . "</option>";
        foreach ($data as $city) {
            $cities .= sprintf(
                '<option value="%s">%s</option>', 
                esc_attr($city), 
                esc_html($city)
            );
        }
        
        set_transient($cache_key, $cities, self::$cache_expiration);
        
        return $cities;
    }
    
    public static function fetch_districts($country, $region) {
        if (empty($country) || empty($region)) {
            return "<option value=''>" . esc_html__('Invalid district', 'prayer-times') . "</option>";
        }
        
        $cache_key = 'prayer_districts_' . md5($country . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $url = sprintf(
            "%s/cities?country=%s&region=%s",
            self::$api_base_url,
            urlencode($country),
            urlencode($region)
        );
        
        $response = self::make_api_request($url);
        
        if (is_wp_error($response)) {
            self::log_error('Failed to fetch districts', $response->get_error_message());
            return "<option value=''>" . esc_html__('Districts could not be loaded', 'prayer-times') . "</option>";
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data || !is_array($data)) {
            self::log_error('Invalid districts data received', wp_remote_retrieve_body($response));
            return "<option value=''>" . esc_html__('No district found', 'prayer-times') . "</option>";
        }
        
        $districts = "<option value=''>" . esc_html__('Choose District', 'prayer-times') . "</option>";
        foreach ($data as $district) {
            $districts .= sprintf(
                '<option value="%s">%s</option>', 
                esc_attr($district), 
                esc_html($district)
            );
        }
        
        set_transient($cache_key, $districts, self::$cache_expiration);
        
        return $districts;
    }
    
    public static function fetch_place_id($country, $region, $district) {
        if (empty($country) || empty($region) || empty($district)) {
            return "invalid";
        }
        
        $cache_key = 'prayer_place_id_' . md5($country . $region . $district);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $query = urlencode("$district, $region, $country");
        $url = self::$api_base_url . "/searchPlaces?q={$query}&lang=tr";
        
        $response = self::make_api_request($url);
        
        if (is_wp_error($response)) {
            self::log_error('Failed to fetch place ID', $response->get_error_message());
            return "error";
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data || !is_array($data) || empty($data[0]['id'])) {
            self::log_error('Place ID not found', wp_remote_retrieve_body($response));
            return "not_found";
        }
        
        $place_id = $data[0]['id'];
        
        set_transient($cache_key, $place_id, self::$cache_expiration);
        
        return $place_id;
    }
    
    private static function get_timezone_offset() {
        $use_custom_timezone = get_option('prayer_use_custom_timezone', '0');
        
        if ($use_custom_timezone === '1') {
            return intval(get_option('prayer_custom_timezone', '180'));
        }
        
        return intval(get_option('gmt_offset') * 60);
    }
    
    private static function make_api_request($url, $request_type = 'default') {
        $args = [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ];
        
        if (WP_DEBUG) {
            self::log_debug("Making API request to: $url", $request_type);
        }
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            self::log_error(
                "API request failed with status code: $status_code",
                wp_remote_retrieve_body($response)
            );
            
            return new WP_Error(
                'api_error',
                sprintf(__('API request failed with status code: %d', 'prayer-times'), $status_code)
            );
        }
        
        return $response;
    }
    
    private static function log_error($message, $details = '') {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
                $log_message = "[Prayer Times Error] $message";
                if (!empty($details)) {
                    $log_message .= " | Details: $details";
                }
                error_log($log_message);
            }
        }
    }
    
    private static function log_debug($message, $context = '') {
        if (defined('WP_DEBUG') && WP_DEBUG === true && defined('PRAYER_DEBUG') && PRAYER_DEBUG === true) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
                $log_message = "[Prayer Times Debug]";
                if (!empty($context)) {
                    $log_message .= " [$context]";
                }
                $log_message .= " $message";
                error_log($log_message);
            }
        }
    }
    
    public static function clear_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_prayer_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_prayer_%'");
        
        delete_transient('prayer_prayer_times');
        
        if (class_exists('LiteSpeed_Cache_API')) {
            if (method_exists('LiteSpeed_Cache_API', 'purge_all')) {
                LiteSpeed_Cache_API::purge_all();
            } elseif (method_exists('LiteSpeed_Cache_API', 'purge')) {
                LiteSpeed_Cache_API::purge('all');
            }
            
            if (method_exists('LiteSpeed_Cache_API', 'force_cache_rebuild')) {
                LiteSpeed_Cache_API::force_cache_rebuild();
            }
            
            do_action('litespeed_purge_all');
        }
        
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        do_action('prayer_cache_cleared');
    }
}