<?php
/**
 * Uninstall AI Assistant API plugin
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

// حذف option‌ها
delete_option('ai_assistant_api_key');

// حذف فایل‌های لاگ
$upload_dir = wp_upload_dir();
$log_dir = $upload_dir['basedir'] . '/ai-assistant-logs/';

if (file_exists($log_dir)) {
    array_map('unlink', glob($log_dir . '*.log'));
    rmdir($log_dir);
}

// حذف محصولات ووکامرس
if (function_exists('wc_get_products')) {
    $products = wc_get_products([
        'meta_key' => '_ai_assistant_service',
        'meta_value' => true,
        'meta_compare' => '=',
        'limit' => -1
    ]);
    
    foreach ($products as $product) {
        wp_delete_post($product->get_id(), true);
    }
}