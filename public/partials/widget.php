<?php
if (!defined('ABSPATH')) {
    exit;
}

function prayer_widget_shortcode() {
    $place_id = get_option('prayer_place_id', '');
    if (empty($place_id)) {
        return prayer_render_error_message(__('No location selected', 'prayer-times'));
    }

    $prayer_times = PrayerFetcher::fetch_prayer_times($place_id);
    if (!$prayer_times) {
        return prayer_render_error_message(__('Prayer times could not be loaded', 'prayer-times'));
    }

    $today = prayer_get_todays_times($prayer_times);
    
    $prayer_info = prayer_calculate_prayer_info($today);
    
    return prayer_render_widget($today, $prayer_info);
}

function prayer_get_todays_times($prayer_times) {
    $today_date = current_time('Y-m-d');
    $today = null;
    
    foreach ($prayer_times as $time) {
        if ($time['date'] === $today_date) {
            $today = $time;
            break;
        }
    }

    if (!$today && !empty($prayer_times)) {
        $today = current($prayer_times);
    }
    
    return $today;
}

function prayer_calculate_prayer_info($today) {
    if (!$today) {
        return false;
    }
    
    $current_time = current_time('H:i');
    $next_prayer = '';
    $next_prayer_time = '';
    $current_prayer = '';
    
    $prayer_times_array = [
        __('Fajr', 'prayer-times') => $today['imsak'],
        __('Sunrise', 'prayer-times') => $today['gunes'],
        __('Dhuhr', 'prayer-times') => $today['ogle'],
        __('Asr', 'prayer-times') => $today['ikindi'],
        __('Maghrib', 'prayer-times') => $today['aksam'],
        __('Isha', 'prayer-times') => $today['yatsi']
    ];

    foreach ($prayer_times_array as $prayer => $time) {
        if ($time > $current_time) {
            $next_prayer = $prayer;
            $next_prayer_time = $time;
            break;
        }
        $current_prayer = $prayer;
    }

    if (empty($next_prayer)) {
        $next_prayer = __('Fajr', 'prayer-times');
        $next_prayer_time = $today['imsak'];
        $current_prayer = __('Isha', 'prayer-times');
    }

    $now_timestamp = current_time('timestamp');
    
    list($target_hour, $target_minute) = explode(':', $next_prayer_time);
    $target_timestamp = strtotime(date('Y-m-d') . ' ' . $target_hour . ':' . $target_minute . ':00');
    
    if ($target_timestamp <= $now_timestamp) {
        $target_timestamp = strtotime('+1 day', $target_timestamp);
    }
    
    $prayer_gradients = [
        __('Fajr', 'prayer-times') => 'linear-gradient(135deg, #141E30, #243B55)',
        __('Sunrise', 'prayer-times') => 'linear-gradient(135deg, #FF512F, #F09819)',
        __('Dhuhr', 'prayer-times') => 'linear-gradient(135deg, #2193b0, #6dd5ed)',
        __('Asr', 'prayer-times') => 'linear-gradient(135deg, #FFB75E, #ED8F03)',
        __('Maghrib', 'prayer-times') => 'linear-gradient(135deg, #4B1248, #F0C27B)',
        __('Isha', 'prayer-times') => 'linear-gradient(135deg, #0F2027, #203A43)'
    ];
    
    return [
        'current_prayer' => $current_prayer,
        'next_prayer' => $next_prayer,
        'next_prayer_time' => $next_prayer_time,
        'now_timestamp' => $now_timestamp,
        'target_timestamp' => $target_timestamp,
        'current_gradient' => $prayer_gradients[$current_prayer]
    ];
}

function prayer_render_error_message($message) {
    return sprintf(
        '<div class="prayer-widget-error">⚠️ %s</div>',
        esc_html($message)
    );
}

function prayer_render_widget($today, $prayer_info) {
    if (!$today || !$prayer_info) {
        return prayer_render_error_message(__('Invalid data', 'prayer-times'));
    }
    
    $city = get_option('prayer_city', '');
    $district = get_option('prayer_district', '');
    $location = $district ? "$city / $district" : $city;

    ob_start();
    ?>
    <div class="prayer-widget">
        <div class="widget-header">
            <h3><?php echo esc_html($location); ?></h3>
        </div>
        
        <script>
        let serverTime = <?php echo $prayer_info['now_timestamp'] * 1000; ?>;
        const targetTime = <?php echo $prayer_info['target_timestamp'] * 1000; ?>;
        let lastUpdate = Date.now();

        function updateCountdown() {
            const now = Date.now();
            const timePassed = now - lastUpdate;
            serverTime += timePassed;
            lastUpdate = now;

            let diff = targetTime - serverTime;
            
            const hoursLeft = Math.floor(diff / (1000 * 60 * 60));
            const minutesLeft = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const secondsLeft = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById('countdown-hours').textContent = String(hoursLeft).padStart(2, '0');
            document.getElementById('countdown-minutes').textContent = String(minutesLeft).padStart(2, '0');
            document.getElementById('countdown-seconds').textContent = String(secondsLeft).padStart(2, '0');
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
        </script>

        <div class="prayer-countdown">
            <div><?php printf(__('Next prayer: %s (%s)', 'prayer-times'), esc_html($prayer_info['next_prayer']), esc_html($prayer_info['next_prayer_time'])); ?></div>
            <div class="countdown-timer">
                <div class="countdown-item">
                    <span id="countdown-hours">00</span>
                    <span class="countdown-label"><?php esc_html_e('Hours', 'prayer-times'); ?></span>
                </div>
                <div class="countdown-item">
                    <span id="countdown-minutes">00</span>
                    <span class="countdown-label"><?php esc_html_e('Minutes', 'prayer-times'); ?></span>
                </div>
                <div class="countdown-item">
                    <span id="countdown-seconds">00</span>
                    <span class="countdown-label"><?php esc_html_e('Seconds', 'prayer-times'); ?></span>
                </div>
            </div>
        </div>

        <div class="prayer-widget-times">
            <?php
            $prayer_times = [
                __('Fajr', 'prayer-times') => $today['imsak'],
                __('Sunrise', 'prayer-times') => $today['gunes'],
                __('Dhuhr', 'prayer-times') => $today['ogle'],
                __('Asr', 'prayer-times') => $today['ikindi'],
                __('Maghrib', 'prayer-times') => $today['aksam'],
                __('Isha', 'prayer-times') => $today['yatsi']
            ];
            
            foreach ($prayer_times as $prayer => $time) {
                $is_active = ($prayer === $prayer_info['next_prayer']) ? ' active' : '';
                $is_active_time = ($prayer === $prayer_info['next_prayer']) ? ' active-time' : '';
                
                printf(
                    '<div class="prayer-time-item%s">
                        <span class="prayer-time-label">%s</span>
                        <span class="prayer-time%s" data-prayer="%s">%s</span>
                    </div>',
                    $is_active,
                    esc_html($prayer),
                    $is_active_time,
                    esc_attr($prayer),
                    esc_html($time)
                );
            }
            ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const widget = document.querySelector('.prayer-widget');
        if (!widget) return;

        const nextPrayer = '<?php echo esc_js($prayer_info['next_prayer']); ?>';
        
        if (nextPrayer) {
            const prayerName = nextPrayer.toLowerCase();
            
            if (prayerName === 'fajr' || prayerName === 'imsak') {
                widget.style.background = 'linear-gradient(135deg, #141E30, #243B55)';
            } else if (prayerName === 'sunrise' || prayerName === 'güneş') {
                widget.style.background = 'linear-gradient(135deg, #FF512F, #F09819)';
            } else if (prayerName === 'dhuhr' || prayerName === 'öğle') {
                widget.style.background = 'linear-gradient(135deg, #2193b0, #6dd5ed)';
            } else if (prayerName === 'asr' || prayerName === 'ikindi') {
                widget.style.background = 'linear-gradient(135deg, #FFB75E, #ED8F03)';
            } else if (prayerName === 'maghrib' || prayerName === 'akşam') {
                widget.style.background = 'linear-gradient(135deg, #4B1248, #F0C27B)';
            } else if (prayerName === 'isha' || prayerName === 'yatsı') {
                widget.style.background = 'linear-gradient(135deg, #0F2027, #203A43)';
            }
            
            widget.setAttribute('data-active-prayer', nextPrayer);
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

add_shortcode('prayer_widget', 'prayer_widget_shortcode');