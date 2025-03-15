<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$options = [
    'prayer_country',
    'prayer_city',
    'prayer_district',
    'prayer_place_id',
    'prayer_custom_timezone',
    'prayer_use_custom_timezone',
    'prayer_language',
    'prayer_show_credit'
];

foreach ($options as $option) {
    delete_option($option);
}

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