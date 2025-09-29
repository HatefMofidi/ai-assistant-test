<?php
/* /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/discounts-functions.php */
if (!defined('ABSPATH')) exit;


// بارگذاری سیستم مدیریت تخفیف Front-end
require_once get_template_directory() . '/inc/admin/class-discount-frontend-admin.php';

// ایجاد خودکار صفحه مدیریت تخفیف‌ها
function create_discount_admin_page() {
    $page_slug = 'management-discounts';
    
    // بررسی وجود صفحه
    $page = get_page_by_path($page_slug);
    
    if (!$page) {
        // ایجاد صفحه جدید
        $page_id = wp_insert_post([
            'post_title' => 'مدیریت کدهای تخفیف',
            'post_name' => $page_slug,
            'post_content' => '[discount_codes_admin]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ]);
        
        if ($page_id && !is_wp_error($page_id)) {
            update_option('ai_discount_admin_page_id', $page_id);
        }
    }
}

// اجرای ایجاد صفحه هنگام فعال سازی تم
add_action('after_switch_theme', 'create_discount_admin_page');
add_action('init', 'create_discount_admin_page');