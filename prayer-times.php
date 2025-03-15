<?php
/*
Plugin Name: Prayer Times
Plugin URI: https://github.com/Chemeindefer/prayer-times.git
Description: Displays Islamic prayer times using Namaz Vakti API. Includes widget and table shortcode functionality.
Version: 1.0.0
Author: Batuhan Delice
Author URI: https://batuhandelice.com
License: GPL-3.0+
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: prayer-times
Domain Path: /languages
Tags: prayer times, islamic, widget, shortcode
Tested up to: 6.7.2
Update URI: false
*/

if (!defined('ABSPATH')) {
    exit;
}

define('PRAYER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRAYER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRAYER_VERSION', '1.0.0');

class PrayerTimes {
    private static $instance = null;
    
    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_plugin_textdomain'], -999);
        add_action('plugins_loaded', [$this, 'init'], 1);
        
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $this->load_dependencies();
        $this->register_hooks();
    }
    
    private function load_dependencies() {
        $include_files = [
            'includes/settings.php',
            'includes/fetcher.php',
            'includes/ajax-handler.php',
            'includes/info.php',
            'public/partials/widget.php',
            'public/partials/table.php',
            'public/partials/banner.php'
        ];
        
        foreach ($include_files as $file) {
            require_once PRAYER_PLUGIN_DIR . $file;
        }
    }
    
    private function register_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'load_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'load_frontend_assets']);
        add_action('admin_head', [$this, 'hide_update_notifications'], 1);
        add_action('admin_notices', [$this, 'show_welcome_notice']);
    }
    
    public function load_plugin_textdomain() {
        $domain = 'prayer-times';
        
        $selected_language = get_option('prayer_language', '');
        
        if (empty($selected_language)) {
            $selected_language = determine_locale();
            
            if (strpos($selected_language, 'tr_TR') !== false) {
                $selected_language = 'tr_TR';
            }
        }
        
        add_filter('plugin_locale', function($locale) use ($selected_language) {
            return $selected_language;
        }, 999);
        
        $loaded = false;
        
        $plugin_rel_path = dirname(plugin_basename(__FILE__)) . '/languages';
        $plugin_mo_file = WP_PLUGIN_DIR . '/' . $plugin_rel_path . '/' . $domain . '-' . $selected_language . '.mo';
        
        if (file_exists($plugin_mo_file)) {
            $loaded = load_textdomain($domain, $plugin_mo_file);
        }
        
        if (!$loaded) {
            $wp_lang_dir = WP_LANG_DIR . '/plugins';
            $wp_mo_file = $wp_lang_dir . '/' . $domain . '-' . $selected_language . '.mo';
            
            if (file_exists($wp_mo_file)) {
                $loaded = load_textdomain($domain, $wp_mo_file);
            }
        }
        
        return $loaded;
    }

    public function load_admin_assets($hook) {
        $this->register_style('prayer-admin-style', 'admin/css/style.css');
        $this->register_script('prayer-script', 'admin/js/script.js', ['jquery']);

        wp_localize_script('prayer-script', 'prayer_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('prayer-ajax-nonce'),
            'translations' => [
                'loading' => __('Loading...', 'prayer-times'),
                'iban_copied' => __('IBAN copied!', 'prayer-times'),
                'name_copied' => __('Name copied!', 'prayer-times'),
            ]
        ]);
    }
    
    public function load_frontend_assets() {
        $this->register_style('prayer-style', 'public/css/style.css');
        $this->register_script('prayer-script', 'public/js/script.js', ['jquery']);
    }
    
    private function register_style($handle, $src) {
        wp_enqueue_style(
            $handle,
            PRAYER_PLUGIN_URL . $src,
            [],
            filemtime(PRAYER_PLUGIN_DIR . $src)
        );
    }
    
    private function register_script($handle, $src, $deps = []) {
        wp_enqueue_script(
            $handle,
            PRAYER_PLUGIN_URL . $src,
            $deps,
            filemtime(PRAYER_PLUGIN_DIR . $src),
            true
        );
    }

    public function hide_update_notifications() {
        $current_screen = get_current_screen();
        if (isset($current_screen->id) && 
            ($current_screen->id === 'toplevel_page_prayer-times' || 
             $current_screen->id === 'prayer-times_page_prayer-info' ||
             strpos($current_screen->id, 'prayer') !== false)) {
            
            remove_action('admin_notices', 'update_nag', 3);
            remove_action('network_admin_notices', 'update_nag', 3);
            
            echo '<style>
                .update-nag,
                .updated.plugin-update-tr,
                .notice.notice-warning.is-dismissible,
                .update-message,
                .updated-message,
                .notice.notice-info.is-dismissible,
                .notice.notice-warning,
                .update-plugins,
                .plugin-update-tr,
                #wp-admin-bar-updates,
                .update-count,
                .plugin-update,
                #setting-error-tgmpa {
                    display: none !important;
                }
                #wpbody-content .wrap > h1 {
                    margin-top: 20px;
                }
            </style>';
        }
    }

    public function show_welcome_notice() {
        if (get_option('prayer_show_welcome') !== 'yes') {
            return;
        }
        
        delete_option('prayer_show_welcome');
        ?>
        <div class="welcome-popup-overlay active" id="welcomePopup">
            <div class="welcome-popup">
                <img src="<?php echo esc_url(PRAYER_PLUGIN_URL . 'admin/images/plugin-promo.png'); ?>" 
                     alt="Prayer Times" 
                     class="welcome-popup-logo">
                
                <div class="welcome-popup-content">
                    <h2><?php esc_html_e('Welcome to Prayer Times', 'prayer-times'); ?></h2>
                    
                    <p><?php esc_html_e('This plugin is an open-source project, and the developer does not promise continuous updates or development. The data provided by the plugin is sourced from another open-source project.', 'prayer-times'); ?></p>
                    
                    <h3><?php esc_html_e('How to Use the Plugin?', 'prayer-times'); ?></h3>
                    <p><?php esc_html_e('Before starting to use the plugin, please check the "Info" section in the plugin menu.', 'prayer-times'); ?></p>
                    
                    <p><?php esc_html_e('This plugin is added to your content using shortcodes. You can use the data you want to display in pages, posts, or widgets.', 'prayer-times'); ?></p>
                    
                    <h3><?php esc_html_e('Using Shortcodes', 'prayer-times'); ?></h3>
                    <p><?php esc_html_e('Get the Shortcode:', 'prayer-times'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Go to the "Info" section in the plugin menu.', 'prayer-times'); ?></li>
                        <li><?php esc_html_e('Here you can see the shortcode you can use and the necessary parameters.', 'prayer-times'); ?></li>
                    </ul>
                    
                    <p><?php esc_html_e('Don\'t Forget to Configure Settings!', 'prayer-times'); ?></p>
                    <p><?php esc_html_e('To use the plugin efficiently, don\'t forget to configure the city and timezone settings in the "Settings" section for the shortcode you obtained from the "Info" section.', 'prayer-times'); ?></p>
                </div>
                
                <div class="welcome-popup-buttons">
                    <button class="welcome-popup-close" onclick="closeWelcomePopup()"><?php esc_html_e('Close', 'prayer-times'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=prayer-info')); ?>" class="welcome-popup-info"><?php esc_html_e('Go to Info Page', 'prayer-times'); ?></a>
                </div>
            </div>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('prayer_country')) {
            update_option('prayer_country', 'Turkey');
        }
        if (!get_option('prayer_city')) {
            update_option('prayer_city', 'Ankara');
        }
        if (!get_option('prayer_district')) {
            update_option('prayer_district', 'Çankaya');
        }
        if (!get_option('prayer_place_id')) {
            update_option('prayer_place_id', '311055');
        }
        
        if (get_option('prayer_show_credit') === false) {
            update_option('prayer_show_credit', '1');
        }

        update_option('prayer_show_welcome', 'yes');
    }

    public function deactivate() {
        $this->clear_cache();
    }

    public function clear_cache() {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/prayer-times-cache';
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($cache_dir);
        }
    }
}

function prayer_times_init() {
    return PrayerTimes::get_instance();
}

prayer_times_init();