<?php
require_once plugin_dir_path(__FILE__) . 'info.php'; 
if (!defined('ABSPATH')) {
    exit;
}

class PrayerSettings {
    private static $option_group = 'prayer_settings_group';
    private static $options = [
        'prayer_country',
        'prayer_city',
        'prayer_district',
        'prayer_place_id',
        'prayer_custom_timezone',
        'prayer_use_custom_timezone',
        'prayer_language',
        'prayer_show_credit'
    ];
    
    public static function init() {
        add_action('admin_menu', [self::class, 'setup_admin_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_init', [self::class, 'hide_update_notices']);
        add_action('admin_init', [self::class, 'handle_manual_cache_clear']);
        
        foreach (self::$options as $option) {
            add_action('update_option_' . $option, [self::class, 'clear_cache_on_settings_change'], 10, 2);
        }
    }

    public static function hide_update_notices() {
        $current_screen = get_current_screen();
        if (isset($current_screen->id) && 
            ($current_screen->id === 'toplevel_page_prayer-times' || 
             $current_screen->id === 'prayer-times_page_prayer-info')) {
            
            remove_action('admin_notices', 'update_nag', 3);
            remove_action('admin_notices', 'maintenance_nag', 10);
            
            remove_action('admin_notices', 'update_notice', 10);
            remove_action('admin_notices', 'maintenance_notice', 10);
            
            global $pagenow;
            if ($pagenow === 'update.php') {
                remove_action('admin_notices', 'update_nag', 3);
            }
            
            global $wp_filter;
            if (isset($wp_filter['admin_notices'])) {
                foreach ($wp_filter['admin_notices']->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $key => $callback) {
                        if (strpos($key, 'display_update_notice') !== false || 
                            strpos($key, 'update_nag') !== false ||
                            strpos($key, 'update_notice') !== false) {
                            unset($wp_filter['admin_notices']->callbacks[$priority][$key]);
                        }
                    }
                }
            }
        }
    }

    public static function register_settings() {
        foreach (self::$options as $option) {
            register_setting(self::$option_group, $option);
        }
    }

    public static function setup_admin_menu() {
        self::add_menu_page();
        self::add_info_submenu();
    }

    private static function add_menu_page() {
        add_menu_page(
            __('Prayer Times Settings', 'prayer-times'),
            __('Prayer Times', 'prayer-times'),
            'manage_options',
            'prayer-times',
            [self::class, 'render_settings_page'],
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page(
            'prayer-times',
            __('Prayer Times Settings', 'prayer-times'),
            __('Settings', 'prayer-times'),
            'manage_options',
            'prayer-times',
            [self::class, 'render_settings_page']
        );
    }

    private static function add_info_submenu() {
        add_submenu_page(
            'prayer-times',
            __('Plugin Info', 'prayer-times'),
            __('Info', 'prayer-times'),
            'manage_options',
            'prayer-info',
            ['PrayerInfo', 'render_info_page']
        );
    }

    public static function render_settings_page() {
        $settings = self::get_all_settings();
        ?>
        <div class="wrap prayer-settings-wrapper">
            <div class="prayer-settings-header">
                <img src="<?php echo esc_url(PRAYER_PLUGIN_URL . 'assets/plugin-promo.png'); ?>" 
                     alt="Prayer Times" 
                     class="prayer-settings-image">
            </div>

            <h1 class="prayer-settings-title"><?php esc_html_e('Prayer Times Settings', 'prayer-times') ?></h1>
            
            <form method="post" action="options.php" class="prayer-settings-form">
                <?php settings_fields(self::$option_group); ?>

                <div class="prayer-settings-fields">
                    <?php self::render_language_settings($settings); ?>
                    <?php self::render_location_selectors($settings); ?>
                    <?php self::render_timezone_settings($settings); ?>
                    <?php self::render_backlink_settings($settings); ?>
                    
                    <div class="place-id-container">
                        <span id="place_id_display"><?php echo esc_html(get_option('prayer_place_id', __('Please make a selection...', 'prayer-times'))); ?></span>
                        <input type="hidden" name="prayer_place_id" id="prayer_place_id" value="<?php echo esc_attr($settings['place_id']); ?>">
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    private static function get_all_settings() {
        return [
            'country' => esc_attr(get_option('prayer_country')),
            'city' => esc_attr(get_option('prayer_city')),
            'district' => esc_attr(get_option('prayer_district')),
            'use_custom_timezone' => get_option('prayer_use_custom_timezone', '0'),
            'custom_timezone' => get_option('prayer_custom_timezone', '180'),
            'place_id' => get_option('prayer_place_id'),
            'wp_timezone_offset' => get_option('gmt_offset') * 60,
            'language' => get_option('prayer_language', 'en_US'),
            'show_credit' => get_option('prayer_show_credit', '1')
        ];
    }
    
    private static function render_location_selectors($settings) {
        ?>
        <div class="select-wrapper">
            <select name="prayer_country" id="prayer_country_id">
                <option value=""><?php esc_html_e('Select Country', 'prayer-times'); ?></option>
            </select>
            <div class="select-arrow"></div>
        </div>

        <div class="select-wrapper">
            <select name="prayer_city" id="prayer_city_id">
                <option value=""><?php esc_html_e('Select City', 'prayer-times'); ?></option>
            </select>
            <div class="select-arrow"></div>
        </div>

        <div class="select-wrapper">
            <select name="prayer_district" id="prayer_district_id">
                <option value=""><?php esc_html_e('Select District', 'prayer-times'); ?></option>
            </select>
            <div class="select-arrow"></div>
        </div>
        <?php
    }
    
    private static function render_timezone_settings($settings) {
        ?>
        <div class="select-wrapper">
            <div class="timezone-info">
                <p class="wp-timezone"><?php printf(esc_html__('System Timezone: UTC%+d', 'prayer-times'), $settings['wp_timezone_offset'] / 60); ?></p>
            </div>
            <div class="timezone-toggle">
                <label class="toggle-switch">
                    <input type="checkbox" name="prayer_use_custom_timezone" id="prayer_use_custom_timezone" 
                           value="1" data-saved-value="<?php echo $settings['use_custom_timezone']; ?>" 
                           <?php checked('1', $settings['use_custom_timezone']); ?>>
                    <span class="toggle-slider"></span>
                    <?php esc_html_e('Use Custom Timezone', 'prayer-times'); ?>
                </label>
            </div>
            <div class="custom-timezone-input" style="<?php echo $settings['use_custom_timezone'] ? '' : 'display: none;'; ?>">
                <select name="prayer_custom_timezone" id="prayer_custom_timezone" data-saved-value="<?php echo esc_attr($settings['custom_timezone']); ?>">
                    <?php
                    for ($i = -12; $i <= 14; $i++) {
                        $minutes = $i * 60;
                        $selected = $settings['custom_timezone'] == $minutes ? 'selected' : '';
                        printf(
                            '<option value="%d" %s>UTC%+d</option>',
                            $minutes,
                            $selected,
                            $i
                        );
                    }
                    ?>
                </select>
                <div class="select-arrow"></div>
            </div>
        </div>
        <?php
    }

    private static function render_language_settings($settings) {
        $available_languages = [
            'en_US' => 'English',
            'tr_TR' => 'Türkçe'
        ];
        ?>
        <div class="select-wrapper language-settings">
            <label for="prayer_language" class="setting-label"><?php esc_html_e('Plugin Language', 'prayer-times'); ?></label>
            <select name="prayer_language" id="prayer_language">
                <?php foreach ($available_languages as $code => $name): ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['language'], $code); ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="select-arrow"></div>
        </div>
        <?php
    }

    private static function render_backlink_settings($settings) {
        ?>
        <div class="select-wrapper">
            <div class="backlink-toggle">
                <label class="toggle-switch">
                    <input type="checkbox" name="prayer_show_credit" id="prayer_show_credit" 
                           value="1" data-saved-value="<?php echo $settings['show_credit']; ?>" 
                           <?php checked('1', $settings['show_credit']); ?>>
                    <span class="toggle-slider"></span>
                    <?php esc_html_e('Show Attribution Link', 'prayer-times'); ?>
                </label>
                <p class="description"><?php esc_html_e('Display "Powered by Batuhan Delice" attribution link in prayer time displays.', 'prayer-times'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public static function clear_cache_on_settings_change($old_value, $new_value) {
        if ($old_value !== $new_value) {
            self::clear_plugin_cache();
            
            add_action('admin_notices', [self::class, 'show_settings_updated_notice']);
            update_option('prayer_cache_cleared', '1');
        }
    }
    
    public static function show_settings_updated_notice() {
        if (get_option('prayer_cache_cleared', '0') === '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Prayer Times: Settings updated and cache cleared successfully.', 'prayer-times'); ?></p>
            </div>
            <?php
            delete_option('prayer_cache_cleared');
        }
    }

    public static function clear_cache_on_backlink_change($old_value, $new_value) {
        if ($old_value !== $new_value) {
            self::clear_plugin_cache();
            
            add_action('admin_notices', [self::class, 'show_backlink_updated_notice']);
            update_option('prayer_cache_cleared', '1');
        }
    }
    
    public static function show_backlink_updated_notice() {
        if (get_option('prayer_cache_cleared', '0') === '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Prayer Times: Attribution link settings updated and cache cleared successfully.', 'prayer-times'); ?></p>
            </div>
            <?php
            delete_option('prayer_cache_cleared');
        }
    }

    public static function clear_plugin_cache() {
        if (class_exists('PrayerFetcher')) {
            PrayerFetcher::clear_cache();
        }
        
        delete_transient('prayer_prayer_times');
        
        $prayer_times = prayer_times_init();
        if (method_exists($prayer_times, 'clear_cache')) {
            $prayer_times->clear_cache();
        }
        
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        if (class_exists('LiteSpeed_Cache_API')) {
            if (method_exists('LiteSpeed_Cache_API', 'purge_all')) {
                LiteSpeed_Cache_API::purge_all();
            } elseif (method_exists('LiteSpeed_Cache_API', 'purge')) {
                LiteSpeed_Cache_API::purge('all');
            }
            
            do_action('litespeed_purge_all');
        }
        
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete('prayer-ajax-nonce', 'options');
        }
        
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        nocache_headers();
        
        do_action('litespeed_purge_all');
    }

    public static function handle_manual_cache_clear() {
        if (isset($_GET['prayer_action']) && $_GET['prayer_action'] === 'clear_cache') {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'prayer-times'));
            }

            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'prayer_clear_cache_nonce')) {
                wp_die(__('Security check failed.', 'prayer-times'));
            }

            self::clear_plugin_cache();
            
            add_action('admin_notices', [self::class, 'show_cache_cleared_notice']);
            update_option('prayer_cache_cleared', '1');
            
            wp_redirect(admin_url('admin.php?page=prayer-times&cache_cleared=1'));
            exit;
        }
    }
}

PrayerSettings::init();
