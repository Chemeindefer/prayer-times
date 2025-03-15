<?php
if (!defined('ABSPATH')) {
    exit;
}

function prayer_banner_shortcode($atts = []) {
    $attributes = shortcode_atts([
        'heading' => true,
    ], $atts);
    
    $attributes['heading'] = filter_var($attributes['heading'], FILTER_VALIDATE_BOOLEAN);
    
    $place_id = get_option('prayer_place_id', '');
    if (empty($place_id)) {
        return prayer_render_banner_error(__('No location selected', 'prayer-times'));
    }

    $prayer_times = PrayerFetcher::fetch_prayer_times($place_id);
    if (!$prayer_times || !is_array($prayer_times)) {
        return prayer_render_banner_error(__('Prayer times could not be loaded', 'prayer-times'));
    }
    
    $today_times = null;
    $today_date = current_time('Y-m-d');
    foreach ($prayer_times as $time) {
        if ($time['date'] === $today_date) {
            $today_times = $time;
            break;
        }
    }
    
    if (!$today_times) {
        return prayer_render_banner_error(__('Today\'s prayer times not found', 'prayer-times'));
    }

    return prayer_render_banner($today_times, $attributes);
}

function prayer_render_banner_error($message) {
    return sprintf(
        '<div class="prayer-error">⚠️ %s</div>',
        esc_html($message)
    );
}

function prayer_render_banner($times, $attributes) {
    $current_time = current_time('H:i');
    
    $prayer_order = ['imsak', 'gunes', 'ogle', 'ikindi', 'aksam', 'yatsi'];
    $prayer_labels = [
        'imsak' => __('Fajr', 'prayer-times'),
        'gunes' => __('Sunrise', 'prayer-times'),
        'ogle' => __('Dhuhr', 'prayer-times'),
        'ikindi' => __('Asr', 'prayer-times'),
        'aksam' => __('Maghrib', 'prayer-times'),
        'yatsi' => __('Isha', 'prayer-times')
    ];
    
    $next_prayer = null;
    foreach ($prayer_order as $prayer) {
        if (strtotime($current_time) < strtotime($times[$prayer])) {
            $next_prayer = $prayer;
            break;
        }
    }
    
    if (!$next_prayer && strtotime($current_time) >= strtotime($times['yatsi'])) {
        $next_prayer = 'imsak';
    }
    
    $city = get_option('prayer_city', '');
    $district = get_option('prayer_district', '');
    $location = $district ? "$city / $district" : $city;

    ob_start();
    ?>
    <div class="prayer-banner">
        <div class="banner-header">
            <h3><?php echo esc_html($location); ?></h3>
        </div>
        <div class="prayer-banner-times">
            <?php foreach ($prayer_order as $prayer) : ?>
                <div class="prayer-banner-time<?php echo ($prayer === $next_prayer) ? ' active-time' : ''; ?>">
                    <span class="prayer-label"><?php echo esc_html($prayer_labels[$prayer]); ?></span>
                    <span class="prayer-time" data-prayer="<?php echo esc_attr($prayer_labels[$prayer]); ?>"><?php echo esc_html($times[$prayer]); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($next_prayer) : ?>
            <div class="prayer-banner-countdown">
                <span class="countdown-label"><?php printf(esc_html__('Time Remaining Until %s', 'prayer-times'), $prayer_labels[$next_prayer]); ?></span>
                <div class="countdown-timer" data-target-time="<?php echo esc_attr($times[$next_prayer]); ?>">
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
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const activeTime = document.querySelector('.prayer-banner .active-time .prayer-time');
        const banner = document.querySelector('.prayer-banner');
        
        if (activeTime && banner) {
            const prayer = activeTime.getAttribute('data-prayer');
            
            if (prayer) {
                const prayerName = prayer.toLowerCase();
                if (prayerName === 'fajr' || prayerName === 'imsak') {
                    banner.style.background = 'linear-gradient(135deg, #141E30, #243B55)';
                } else if (prayerName === 'sunrise' || prayerName === 'güneş') {
                    banner.style.background = 'linear-gradient(135deg, #FF512F, #F09819)';
                } else if (prayerName === 'dhuhr' || prayerName === 'öğle') {
                    banner.style.background = 'linear-gradient(135deg, #2193b0, #6dd5ed)';
                } else if (prayerName === 'asr' || prayerName === 'ikindi') {
                    banner.style.background = 'linear-gradient(135deg, #FFB75E, #ED8F03)';
                } else if (prayerName === 'maghrib' || prayerName === 'akşam') {
                    banner.style.background = 'linear-gradient(135deg, #4B1248, #F0C27B)';
                } else if (prayerName === 'isha' || prayerName === 'yatsı') {
                    banner.style.background = 'linear-gradient(135deg, #0F2027, #203A43)';
                }
            }
            
            banner.setAttribute('data-active-prayer', prayer);
        }
        
        const countdownTimer = document.querySelector('.prayer-banner .countdown-timer');
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
                
                const hoursElement = document.querySelector('.prayer-banner .countdown-item .hours');
                const minutesElement = document.querySelector('.prayer-banner .countdown-item .minutes');
                const secondsElement = document.querySelector('.prayer-banner .countdown-item .seconds');
                
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

add_shortcode('prayer_banner', 'prayer_banner_shortcode'); 