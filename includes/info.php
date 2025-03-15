<?php
if (!defined('ABSPATH')) {
    exit;
}

class PrayerInfo {
    public static function init() {
        add_action('admin_init', [self::class, 'register_actions']);
        add_action('admin_init', [self::class, 'hide_update_notices']);
    }
    
    public static function register_actions() {
        if (isset($_GET['prayer-action']) && $_GET['prayer-action'] === 'clear-cache' && current_user_can('manage_options')) {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'prayer_clear_cache')) {
                self::clear_cache();
                add_action('admin_notices', [self::class, 'display_cache_cleared_notice']);
                
                wp_safe_redirect(remove_query_arg(['prayer-action', '_wpnonce']));
                exit;
            }
        }
    }
    
    private static function clear_cache() {
        PrayerFetcher::clear_cache();
    }
    
    public static function display_cache_cleared_notice() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Prayer Times cache has been cleared successfully.', 'prayer-times'); ?></p>
        </div>
        <?php
    }
    
    private static function get_plugin_version() {
        if (defined('PRAYER_VERSION')) {
            return PRAYER_VERSION;
        }
        return '1.0.0';
    }
    
    private static function get_system_info() {
        return [
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'plugin_version' => self::get_plugin_version(),
            'last_update' => date_i18n(get_option('date_format'), filemtime(PRAYER_PLUGIN_DIR . 'prayer-times.php')),
            'timezone' => get_option('timezone_string'),
            'timezone_offset' => get_option('gmt_offset'),
            'has_cache' => self::has_cache()
        ];
    }
    
    private static function has_cache() {
        global $wpdb;
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
                '%\_transient\_prayer\_%'
            )
        );
        return $count > 0;
    }
    
    public static function render_info_page() {
        $system_info = self::get_system_info();
        $clear_cache_url = wp_nonce_url(
            add_query_arg(['prayer-action' => 'clear-cache']),
            'prayer_clear_cache'
        );
        ?>
        <div class="wrap">
            <div class="prayer-info-content">
                <h1 class="prayer-info-title">🌙 <?php _e('Prayer Times Plugin', 'prayer-times') ?></h1>
                
                <div class="prayer-info-card">
                    <h3>👨💻 <?php _e('Developer Information', 'prayer-times') ?></h3>
                    <p><?php _e('Hello! I am Batuhan Delice. I am actually a Flutter developer, but due to some requests, I had to develop this plugin.', 'prayer-times') ?></p>
                    <div class="developer-links">
                        <a href="https://batuhandelice.com" target="_blank" class="button button-primary">
                            🌐 <?php _e('My Personal Website', 'prayer-times') ?>
                        </a>
                        <a href="mailto:developer@batuhandelice.com" class="button button-secondary">
                            📧 <?php _e('Contact Me', 'prayer-times') ?>
                        </a>
                    </div>
                </div>

                <div class="prayer-info-card system-info">
                    <h3>⚙️ <?php _e('Technical Features', 'prayer-times') ?></h3>
                    <div class="prayer-admin-panel">
                        <div class="prayer-admin-row">
                            <div class="prayer-admin-stat">
                                <span class="stat-label"><?php _e('Plugin Version', 'prayer-times') ?></span>
                                <span class="stat-value"><?php echo esc_html($system_info['plugin_version']); ?></span>
                            </div>
                            <div class="prayer-admin-stat">
                                <span class="stat-label"><?php _e('WordPress Version', 'prayer-times') ?></span>
                                <span class="stat-value"><?php echo esc_html($system_info['wp_version']); ?></span>
                            </div>
                            <div class="prayer-admin-stat">
                                <span class="stat-label"><?php _e('PHP Version', 'prayer-times') ?></span>
                                <span class="stat-value"><?php echo esc_html($system_info['php_version']); ?></span>
                            </div>
                        </div>
                        <div class="prayer-admin-row">
                            <div class="prayer-admin-stat">
                                <span class="stat-label"><?php _e('Last Update', 'prayer-times') ?></span>
                                <span class="stat-value"><?php echo esc_html($system_info['last_update']); ?></span>
                            </div>
                            <div class="prayer-admin-stat">
                                <span class="stat-label"><?php _e('TimeZone', 'prayer-times') ?></span>
                                <span class="stat-value">
                                    <?php 
                                    if (!empty($system_info['timezone'])) {
                                        echo esc_html($system_info['timezone']);
                                    } else {
                                        printf('UTC%+d', $system_info['timezone_offset']);
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="prayer-admin-stat">
                                <span class="stat-label"><?php _e('GitHub Repo', 'prayer-times') ?></span>
                                <span class="stat-value">
                                    <a href="https://github.com/Chemeindefer/prayer-times.git" target="_blank">
                                        <?php _e('View/Contribute', 'prayer-times') ?>
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="prayer-cache-control">
                        <h4><?php _e('Cache Management', 'prayer-times') ?></h4>
                        <p>
                            <?php 
                            if ($system_info['has_cache']) {
                                _e('The plugin has cached data for faster performance. You can clear this cache to fetch fresh data from the API.', 'prayer-times');
                            } else {
                                _e('No cached data found. The plugin will fetch data from the API as needed.', 'prayer-times');
                            }
                            ?>
                        </p>
                        <a href="<?php echo esc_url($clear_cache_url); ?>" class="button button-secondary">
                            <?php _e('Clear Cache', 'prayer-times'); ?>
                        </a>
                    </div>
                </div>

                <div class="prayer-info-card api-info">
                    <h3>🔌 <?php _e('Data Source', 'prayer-times') ?></h3>
                    <div class="api-details">
                        <p><?php _e('The plugin retrieves prayer times data from the following open-source API:', 'prayer-times') ?></p>
                        <ul>
                            <li>📡 <?php _e('API: <a href="https://vakit.vercel.app" target="_blank">vakit.vercel.app</a>', 'prayer-times') ?></li>
                            <li>💻 <?php _e('API Developer: Yusuf Sait Canbaz', 'prayer-times') ?></li>
                            <li>🐙 <?php _e('Source Code: <a href="https://github.com/canbax/namaz-vakti-api" target="_blank">GitHub Repository</a>', 'prayer-times') ?></li>
                        </ul>
                        <div class="notice">
                            <p>⚠️ <?php _e('If you notice any inconsistencies in the times or cannot access the data, please check the API status.', 'prayer-times') ?></p>
                        </div>
                    </div>
                </div>

                <div class="prayer-info-card donation-info">
                    <h3>💸 <?php _e('Support Us', 'prayer-times') ?></h3>
                    <div class="donation-grid">
                        <div class="donation-item">
                            <div class="name-container">
                                <h4>Batuhan Delice</h4>
                                <button class="copy-name" onclick="copyName(this)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 4v12h12V4H8zM6 2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                                        <path d="M16 8v10H4V8"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="iban-container">
                                <p class="iban">TR20 0011 1000 0000 0098 0222 37</p>
                                <button class="copy-iban" onclick="copyIban(this)">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 4v12h12V4H8zM6 2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                                        <path d="M16 8v10H4V8"/>
                                    </svg>
                                </button>
                            </div>
                            <small><?php _e('[Plugin Developer]', 'prayer-times') ?></small>
                        </div>

                        <div class="donation-item">
                            <div class="name-container">
                                <h4>Yusuf Sait Canbaz</h4>
                                <button class="copy-name" onclick="copyName(this)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 4v12h12V4H8zM6 2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                                        <path d="M16 8v10H4V8"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="iban-container">
                                <p class="iban">TR49 0020 6001 3401 9074 7100 01</p>
                                <button class="copy-iban" onclick="copyIban(this)">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 4v12h12V4H8zM6 2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                                        <path d="M16 8v10H4V8"/>
                                    </svg>
                                </button>
                            </div>
                            <small><?php _e('[API Developer]', 'prayer-times') ?></small>
                        </div>
                    </div>
                    <p class="donation-note"><?php _e('Your support ensures the continuity of open-source projects.', 'prayer-times') ?></p>
                </div>

                <div class="prayer-info-card thank-you">
                    <h3>🌟 <?php _e('Special Thanks', 'prayer-times') ?></h3>
                    <p><?php _e('I extend my gratitude to Yusuf Sait Canbaz for his valuable contributions and for providing an open-source API. I am also grateful to all users who tested this plugin and provided feedback.', 'prayer-times') ?></p>
                </div>

                <div class="prayer-info-card shortcodes">
                    <h3>🔌 <?php _e('Available Shortcodes', 'prayer-times') ?></h3>
                    
                    <div class="shortcode-section">
                        <div class="shortcode-tabs">
                            <button class="shortcode-tab active" data-tab="widget"><?php _e('Widget', 'prayer-times'); ?></button>
                            <button class="shortcode-tab" data-tab="table"><?php _e('Table', 'prayer-times'); ?></button>
                            <button class="shortcode-tab" data-tab="banner"><?php _e('Banner', 'prayer-times'); ?></button>
                        </div>

                        <div class="shortcode-content active" id="widget-content">
                            <div class="shortcode-title"><?php _e('Prayer Times Widget', 'prayer-times'); ?></div>
                            <div class="shortcode-description">
                                <?php _e('Displays a beautiful widget showing current prayer times with countdown.', 'prayer-times'); ?>
                            </div>
                            <div class="shortcode-preview">
                                <?php echo do_shortcode('[prayer_widget]'); ?>
                            </div>
                            <p class="design-preview-description">
                                <?php _e('You can use the menu below to test how the design will look for different prayer times.', 'prayer-times'); ?>
                            </p>
                            <div class="select-wrapper">
                                <select name="prayer_time_selector" class="prayer-time-selector">
                                    <option value="<?php esc_attr_e('Fajr', 'prayer-times'); ?>"><?php esc_html_e('Fajr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Sunrise', 'prayer-times'); ?>"><?php esc_html_e('Sunrise Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Dhuhr', 'prayer-times'); ?>"><?php esc_html_e('Dhuhr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Asr', 'prayer-times'); ?>"><?php esc_html_e('Asr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Maghrib', 'prayer-times'); ?>"><?php esc_html_e('Maghrib Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Isha', 'prayer-times'); ?>"><?php esc_html_e('Isha Time', 'prayer-times'); ?></option>
                                </select>
                            </div>
                            <div class="shortcode-copy" onclick="copyShortcode(this)">
                                <code>[prayer_widget]</code>
                                <button>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 4v12h12V4H8zM6 2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                                        <path d="M16 8v10H4V8"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="shortcode-content" id="table-content">
                            <div class="shortcode-title"><?php _e('Prayer Times Table', 'prayer-times'); ?></div>
                            <div class="shortcode-description">
                                <?php _e('Shows prayer times in a responsive table format with optional parameters.', 'prayer-times'); ?>
                            </div>
                            <div class="shortcode-preview">
                                <?php echo do_shortcode('[prayer_times heading="true" days="7"]'); ?>
                            </div>
                            <p class="design-preview-description">
                                <?php _e('You can use the menu below to test how the design will look for different prayer times.', 'prayer-times'); ?>
                            </p>
                            <div class="select-wrapper">
                                <select name="prayer_time_selector" class="prayer-time-selector">
                                    <option value="<?php esc_attr_e('Fajr', 'prayer-times'); ?>"><?php esc_html_e('Fajr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Sunrise', 'prayer-times'); ?>"><?php esc_html_e('Sunrise Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Dhuhr', 'prayer-times'); ?>"><?php esc_html_e('Dhuhr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Asr', 'prayer-times'); ?>"><?php esc_html_e('Asr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Maghrib', 'prayer-times'); ?>"><?php esc_html_e('Maghrib Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Isha', 'prayer-times'); ?>"><?php esc_html_e('Isha Time', 'prayer-times'); ?></option>
                                </select>
                            </div>
                            <div class="shortcode-copy" onclick="copyShortcode(this)">
                                <code>[prayer_times heading="true" days="30"]</code>
                                <button>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 4v12h12V4H8zM6 2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                                        <path d="M16 8v10H4V8"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="shortcode-content" id="banner-content">
                            <div class="shortcode-title"><?php _e('Banner View', 'prayer-times'); ?></div>
                            <div class="shortcode-description">
                                <?php _e('Displays prayer times in a banner format. Active prayer time is indicated with a red dot and includes a countdown timer.', 'prayer-times'); ?>
                            </div>
                            <div class="shortcode-preview">
                                <?php echo do_shortcode('[prayer_banner heading="true"]'); ?>
                            </div>
                            <p class="design-preview-description">
                                <?php _e('You can use the menu below to test how the design will look for different prayer times.', 'prayer-times'); ?>
                            </p>
                            <div class="select-wrapper">
                                <select name="prayer_time_selector" class="prayer-time-selector">
                                    <option value="<?php esc_attr_e('Fajr', 'prayer-times'); ?>"><?php esc_html_e('Fajr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Sunrise', 'prayer-times'); ?>"><?php esc_html_e('Sunrise Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Dhuhr', 'prayer-times'); ?>"><?php esc_html_e('Dhuhr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Asr', 'prayer-times'); ?>"><?php esc_html_e('Asr Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Maghrib', 'prayer-times'); ?>"><?php esc_html_e('Maghrib Time', 'prayer-times'); ?></option>
                                    <option value="<?php esc_attr_e('Isha', 'prayer-times'); ?>"><?php esc_html_e('Isha Time', 'prayer-times'); ?></option>
                                </select>
                            </div>
                            <div class="shortcode-copy" onclick="copyShortcode(this)">
                                <code>[prayer_banner heading="true"]</code>
                                <button onclick="copyShortcode('[prayer_banner heading=\'true\']')">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        function copyShortcode(element) {
            const code = element.querySelector('code').innerText;
            navigator.clipboard.writeText(code);
            
            const toast = document.createElement('div');
            toast.className = 'copy-toast';
            toast.textContent = '<?php echo esc_js(__('Shortcode copied!', 'prayer-times')); ?>';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.shortcode-tab');
            const contents = document.querySelectorAll('.shortcode-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.getAttribute('data-tab');
                    
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    
                    tab.classList.add('active');
                    document.getElementById(`${target}-content`).classList.add('active');
                });
            });

            document.querySelectorAll('.prayer-time-selector').forEach(selector => {
                selector.addEventListener('change', function() {
                    updateDesignPreview(this.value);
                });
                
                updateDesignPreview(selector.value);
            });
        });
        </script>
        <?php
    }

    public static function hide_update_notices() {
        $current_screen = get_current_screen();
        if (isset($current_screen->id) && 
            ($current_screen->id === 'toplevel_page_prayer-times' || 
             $current_screen->id === 'prayer-times_page_prayer-info')) {
            
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }
}

PrayerInfo::init();
