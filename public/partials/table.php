<?php
if (!defined('ABSPATH')) {
    exit;
}

function prayer_times_shortcode($atts = []) {
    $attributes = shortcode_atts([
        'heading' => true,
        'days' => 0,
    ], $atts);
    
    $attributes['heading'] = filter_var($attributes['heading'], FILTER_VALIDATE_BOOLEAN);
    $attributes['days'] = absint($attributes['days']);
    
    $place_id = get_option('prayer_place_id', '');
    if (empty($place_id)) {
        return prayer_render_table_error(__('No location selected', 'prayer-times'));
    }

    $prayer_times = PrayerFetcher::fetch_prayer_times($place_id);
    if (!$prayer_times || !is_array($prayer_times)) {
        return prayer_render_table_error(__('Prayer times could not be loaded', 'prayer-times'));
    }
    
    if ($attributes['days'] > 0 && count($prayer_times) > $attributes['days']) {
        $prayer_times = array_slice($prayer_times, 0, $attributes['days']);
    }

    return prayer_render_times($prayer_times, $attributes);
}

function prayer_render_table_error($message) {
    return sprintf(
        '<div class="prayer-error">⚠️ %s</div>',
        esc_html($message)
    );
}

function prayer_render_times($prayer_times, $attributes) {
    if (empty($prayer_times)) {
        return prayer_render_table_error(__('No prayer times data available', 'prayer-times'));
    }
    
    $today_date = current_time('Y-m-d');
    $current_time = current_time('H:i');
    
    $prayer_order = ['imsak', 'gunes', 'ogle', 'ikindi', 'aksam', 'yatsi'];
    $next_prayer = null;
    
    $today_times = null;
    foreach ($prayer_times as $time) {
        if ($time['date'] === $today_date) {
            $today_times = $time;
            break;
        }
    }
    
    if ($today_times) {
        foreach ($prayer_order as $prayer) {
            if (strtotime($current_time) < strtotime($today_times[$prayer])) {
                $next_prayer = $prayer;
                break;
            }
        }
        if (!$next_prayer && strtotime($current_time) >= strtotime($today_times['yatsi'])) {
            $next_prayer = 'imsak';
        }
    }
    
    $prayer_labels = [
        'imsak' => __('Fajr', 'prayer-times'),
        'gunes' => __('Sunrise', 'prayer-times'),
        'ogle' => __('Dhuhr', 'prayer-times'),
        'ikindi' => __('Asr', 'prayer-times'),
        'aksam' => __('Maghrib', 'prayer-times'),
        'yatsi' => __('Isha', 'prayer-times')
    ];
    
    $city = get_option('prayer_city', '');
    $district = get_option('prayer_district', '');
    $location = $district ? "$city / $district" : $city;

    ob_start();
    ?>
    <div class="prayer-times-container">
        <div class="times-header">
            <h3><?php echo esc_html($location); ?></h3>
        </div>
        <?php if ($attributes['heading']) : ?>
            <?php 
            $plugin_language = get_option('prayer_language', '');
            
            $current_locale = get_locale();
            
            if (!empty($plugin_language)) {
                switch_to_locale($plugin_language);
            }
            
            $month_year = date_i18n('F Y');
            
            if (!empty($plugin_language)) {
                switch_to_locale($current_locale);
            }
            ?>
            <h2 class="prayer-times-heading"><?php echo $month_year; ?> <?php esc_html_e('Prayer Times', 'prayer-times'); ?></h2>
        <?php endif; ?>
        
        <?php if ($next_prayer && $today_times) : ?>
            <div class="prayer-times-countdown">
                <span class="countdown-label"><?php printf(esc_html__('Time Remaining Until %s', 'prayer-times'), $prayer_labels[$next_prayer]); ?></span>
                <div class="countdown-timer" data-target-time="<?php echo esc_attr($today_times[$next_prayer]); ?>">
                    <div class="countdown-item">
                        <span class="hours">00</span>
                        <span class="countdown-label"><?php esc_html_e('Hours', 'prayer-times'); ?></span>
                    </div>
                    <div class="countdown-item">
                        <span class="minutes">00</span>
                        <span class="countdown-label"><?php esc_html_e('Minutes', 'prayer-times'); ?></span>
                    </div>
                    <div class="countdown-item">
                        <span class="seconds">00</span>
                        <span class="countdown-label"><?php esc_html_e('Seconds', 'prayer-times'); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <table class="prayer-times">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'prayer-times'); ?></th>
                    <th><?php esc_html_e('Fajr', 'prayer-times'); ?></th>
                    <th><?php esc_html_e('Sunrise', 'prayer-times'); ?></th>
                    <th><?php esc_html_e('Dhuhr', 'prayer-times'); ?></th>
                    <th><?php esc_html_e('Asr', 'prayer-times'); ?></th>
                    <th><?php esc_html_e('Maghrib', 'prayer-times'); ?></th>
                    <th><?php esc_html_e('Isha', 'prayer-times'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prayer_times as $time) : 
                    $display_date = prayer_format_date($time['date']);
                    $row_class = ($time['date'] === $today_date) ? ' class="today"' : '';
                ?>
                    <tr<?php echo $row_class; ?>>
                        <td data-label="<?php esc_attr_e('Date', 'prayer-times'); ?>"><?php echo esc_html($display_date); ?></td>
                        <td data-label="<?php esc_attr_e('Fajr', 'prayer-times'); ?>" <?php echo ($time['date'] === $today_date && $next_prayer === 'imsak') ? 'class="active-time" data-prayer="Fajr"' : ''; ?>><?php echo esc_html($time['imsak']); ?></td>
                        <td data-label="<?php esc_attr_e('Sunrise', 'prayer-times'); ?>" <?php echo ($time['date'] === $today_date && $next_prayer === 'gunes') ? 'class="active-time" data-prayer="Sunrise"' : ''; ?>><?php echo esc_html($time['gunes']); ?></td>
                        <td data-label="<?php esc_attr_e('Dhuhr', 'prayer-times'); ?>" <?php echo ($time['date'] === $today_date && $next_prayer === 'ogle') ? 'class="active-time" data-prayer="Dhuhr"' : ''; ?>><?php echo esc_html($time['ogle']); ?></td>
                        <td data-label="<?php esc_attr_e('Asr', 'prayer-times'); ?>" <?php echo ($time['date'] === $today_date && $next_prayer === 'ikindi') ? 'class="active-time" data-prayer="Asr"' : ''; ?>><?php echo esc_html($time['ikindi']); ?></td>
                        <td data-label="<?php esc_attr_e('Maghrib', 'prayer-times'); ?>" <?php echo ($time['date'] === $today_date && $next_prayer === 'aksam') ? 'class="active-time" data-prayer="Maghrib"' : ''; ?>><?php echo esc_html($time['aksam']); ?></td>
                        <td data-label="<?php esc_attr_e('Isha', 'prayer-times'); ?>" <?php echo ($time['date'] === $today_date && $next_prayer === 'yatsi') ? 'class="active-time" data-prayer="Isha"' : ''; ?>><?php echo esc_html($time['yatsi']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const activeTime = document.querySelector('.prayer-times .active-time');
        const tableContainer = document.querySelector('.prayer-times-container');
        
        if (activeTime && tableContainer) {
            const prayer = activeTime.getAttribute('data-prayer');
            
            if (prayer) {
                const prayerName = prayer.toLowerCase();
                if (prayerName === 'fajr' || prayerName === 'imsak') {
                    tableContainer.style.background = 'linear-gradient(135deg, #141E30, #243B55)';
                } else if (prayerName === 'sunrise' || prayerName === 'güneş') {
                    tableContainer.style.background = 'linear-gradient(135deg, #FF512F, #F09819)';
                } else if (prayerName === 'dhuhr' || prayerName === 'öğle') {
                    tableContainer.style.background = 'linear-gradient(135deg, #2193b0, #6dd5ed)';
                } else if (prayerName === 'asr' || prayerName === 'ikindi') {
                    tableContainer.style.background = 'linear-gradient(135deg, #FFB75E, #ED8F03)';
                } else if (prayerName === 'maghrib' || prayerName === 'akşam') {
                    tableContainer.style.background = 'linear-gradient(135deg, #4B1248, #F0C27B)';
                } else if (prayerName === 'isha' || prayerName === 'yatsı') {
                    tableContainer.style.background = 'linear-gradient(135deg, #0F2027, #203A43)';
                }
            }
            
            tableContainer.setAttribute('data-active-prayer', prayer);
        }
        
        const countdownTimer = document.querySelector('.prayer-times-container .countdown-timer');
        if (countdownTimer) {
            const targetTime = countdownTimer.getAttribute('data-target-time');
            if (targetTime) {
                const [hours, minutes] = targetTime.split(':');
                
                const now = new Date();
                let targetDate = new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    now.getDate(),
                    parseInt(hours),
                    parseInt(minutes),
                    0
                );
                
                if (targetDate <= now) {
                    targetDate.setDate(targetDate.getDate() + 1);
                }
                
                const hoursElement = document.querySelector('.prayer-times-container .countdown-item .hours');
                const minutesElement = document.querySelector('.prayer-times-container .countdown-item .minutes');
                const secondsElement = document.querySelector('.prayer-times-container .countdown-item .seconds');
                
                function updateCountdown() {
                    const now = new Date();
                    const diff = targetDate - now;
                    
                    if (diff <= 0) {
                        location.reload();
                        return;
                    }
                    
                    const hoursLeft = Math.floor(diff / (1000 * 60 * 60));
                    const minutesLeft = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const secondsLeft = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    hoursElement.textContent = String(hoursLeft).padStart(2, '0');
                    minutesElement.textContent = String(minutesLeft).padStart(2, '0');
                    secondsElement.textContent = String(secondsLeft).padStart(2, '0');
                }
                
                updateCountdown();
                
                setInterval(updateCountdown, 1000);
            }
        }
    });
    </script>
    <?php 
    $show_credit = get_option('prayer_show_credit', '1') === '1';
    
    if (isset($_GET['rtbl_hide']) && $_GET['rtbl_hide'] === '1') {
        $show_credit = false;
    } elseif (isset($_GET['rtbl_show']) && $_GET['rtbl_show'] === '1') {
        $show_credit = true;
    }
    
    if ($show_credit): 
    ?>
    <div class="prayer-credit">
        <?php esc_html_e('Powered by', 'prayer-times'); ?> <a href="https://batuhandelice.com" target="_blank" rel="nofollow">Batuhan Delice</a>
    </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

function prayer_format_date($date_string) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_string)) {
        $timestamp = strtotime($date_string);
        if ($timestamp) {
            $plugin_language = get_option('prayer_language', '');
            
            $current_locale = get_locale();
            
            if (!empty($plugin_language)) {
                switch_to_locale($plugin_language);
            }
            
            $formatted_date = date_i18n(get_option('date_format'), $timestamp);
            
            if (!empty($plugin_language)) {
                switch_to_locale($current_locale);
            }
            
            return $formatted_date;
        }
    }
    
    return $date_string;
}

add_shortcode('prayer_times', 'prayer_times_shortcode');