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


// هندلر اعتبارسنجی کد تخفیف
add_action('wp_ajax_validate_discount_code', 'handle_validate_discount_code');
add_action('wp_ajax_nopriv_validate_discount_code', 'handle_validate_discount_code');

function handle_validate_discount_code() {
    // بررسی nonce
    if (!check_ajax_referer('ai_assistant_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
        return;
    }
    
    // بررسی لاگین بودن کاربر
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'برای استفاده از کد تخفیف باید وارد حساب کاربری خود شوید']);
        return;
    }
    
    $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
    $service_id = sanitize_text_field($_POST['service_id'] ?? '');
    $user_id = get_current_user_id();
    
    // بررسی وجود کد تخفیف
    if (empty($discount_code)) {
        wp_send_json_error(['message' => 'لطفا کد تخفیف را وارد کنید']);
        return;
    }
    
    // بررسی وجود service_id
    if (empty($service_id)) {
        wp_send_json_error(['message' => 'سرویس مشخص نشده است']);
        return;
    }
    
    // بررسی وجود سرویس
    if (!class_exists('AI_Assistant_Service_Manager')) {
        wp_send_json_error(['message' => 'سیستم سرویس در دسترس نیست']);
        return;
    }
    
    $service_manager = AI_Assistant_Service_Manager::get_instance();
    $service = $service_manager->get_service($service_id);
    
    if (!$service) {
        wp_send_json_error(['message' => 'سرویس مورد نظر یافت نشد']);
        return;
    }
    
    // بررسی وجود کلاس مدیریت تخفیف
    if (!class_exists('AI_Assistant_Discount_Manager')) {
        wp_send_json_error(['message' => 'سیستم تخفیف در دسترس نیست']);
        return;
    }
    
    $result = AI_Assistant_Discount_Manager::validate_discount($discount_code, $service_id, $user_id);
    
    if ($result['valid']) {
        // دریافت قیمت سرویس
        $original_price = $service_manager->get_service_price($service_id);
        
        // لاگ برای دیباگ
        error_log("Discount validation successful - Service: " . $service_id . ", Original price: " . $original_price);
        
        $final_price = AI_Assistant_Discount_Manager::calculate_discounted_price(
            $original_price, 
            $result['discount']
        );
        
        $discount_amount = $original_price - $final_price;
        
        // لاگ نتایج
        error_log("Discount calculation - Final price: " . $final_price . ", Discount amount: " . $discount_amount);
        
        wp_send_json_success([
            'message' => $result['message'],
            'original_price' => floatval($original_price),
            'discount_amount' => floatval($discount_amount),
            'final_price' => floatval($final_price),
            'discount_type' => $result['discount']->type,
            'discount_value' => floatval($result['discount']->amount)
        ]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}