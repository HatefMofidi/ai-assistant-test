<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/inc/comments-functions.php
 */

if (!defined('ABSPATH')) exit;

// Include comments DB class
require_once get_template_directory() . '/inc/class-comments-db.php';

// Initialize comments DB
function ai_assistant_init_comments_db() {
    AI_Assistant_Comments_DB::get_instance();
}
add_action('init', 'ai_assistant_init_comments_db');

// AJAX handlers for comments
add_action('wp_ajax_submit_service_comment', 'handle_submit_service_comment');
add_action('wp_ajax_nopriv_submit_service_comment', 'handle_submit_service_comment_login_required');

add_action('wp_ajax_get_service_comments', 'handle_get_service_comments');
add_action('wp_ajax_nopriv_get_service_comments', 'handle_get_service_comments');

// در تابع handle_submit_service_comment() این تغییرات را اعمال کنید:
function handle_submit_service_comment() {
    check_ajax_referer('service_comment_nonce', 'security');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('لطفاً ابتدا وارد حساب کاربری خود شوید.');
        return;
    }
    
    $service_id = sanitize_text_field($_POST['service_id']);
    $comment_text = sanitize_textarea_field($_POST['comment_text']);
    $rating = intval($_POST['rating']);
    
    // اعتبارسنجی انتخاب سرویس
    if (empty($service_id) || $service_id === '') {
        wp_send_json_error('لطفاً یک سرویس انتخاب کنید.');
    }
    
    // اعتبارسنجی وجود سرویس
    $service_manager = AI_Assistant_Service_Manager::get_instance();
    $services = $service_manager->get_active_services();
    
    if (!isset($services[$service_id])) {
        wp_send_json_error('سرویس انتخاب شده معتبر نیست.');
    }
    
    if (empty($comment_text)) {
        wp_send_json_error('لطفاً متن نظر خود را وارد کنید.');
    }
    
    if ($rating < 1 || $rating > 5) {
        wp_send_json_error('امتیاز باید بین ۱ تا ۵ باشد.');
    }
    
    $comments_db = AI_Assistant_Comments_DB::get_instance();
    
    $result = $comments_db->add_comment(array(
        'service_id' => $service_id,
        'comment_text' => $comment_text,
        'rating' => $rating
    ));
    
    if ($result) {
        wp_send_json_success('نظر شما با موفقیت ثبت شد و پس از تایید نمایش داده می‌شود.');
    } else {
        wp_send_json_error('خطا در ثبت نظر. لطفاً مجدداً تلاش کنید.');
    }
}

function handle_submit_service_comment_login_required() {
    wp_send_json_error('لطفاً ابتدا وارد حساب کاربری خود شوید.');
}

function handle_get_service_comments() {
    $service_id = sanitize_text_field($_POST['service_id']);
    $page = intval($_POST['page']) ?: 1;
    $per_page = 5;
    $offset = ($page - 1) * $per_page;
    
    $comments_db = AI_Assistant_Comments_DB::get_instance();
    $comments = $comments_db->get_comments($service_id, 'approved', $per_page, $offset);
    $total_count = $comments_db->get_comment_count($service_id, 'approved');
    
    wp_send_json_success(array(
        'comments' => $comments,
        'total_pages' => ceil($total_count / $per_page),
        'current_page' => $page
    ));
}

add_action('wp_ajax_get_service_rating', 'handle_get_service_rating');
add_action('wp_ajax_nopriv_get_service_rating', 'handle_get_service_rating');

function handle_get_service_rating() {
    $service_id = sanitize_text_field($_POST['service_id']);
    
    $comments_db = AI_Assistant_Comments_DB::get_instance();
    $average_rating = $comments_db->get_average_rating($service_id);
    $comment_count = $comments_db->get_comment_count($service_id, 'approved');
    
    wp_send_json_success(array(
        'average_rating' => floatval($average_rating),
        'comment_count' => intval($comment_count)
    ));
}

// اضافه کردن فونت آیکون‌ها
function enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_font_awesome');

// Enqueue comments styles and scripts
function enqueue_comments_assets() {
    if (is_page_template('services-page.php')) {
        wp_enqueue_style('service-comments-css', get_template_directory_uri() . '/assets/css/services/comments.css');
        wp_enqueue_script('service-comments-js', get_template_directory_uri() . '/assets/js/services/comments.js', array('jquery'), null, true);
        
        // Localize script
        wp_localize_script('service-comments-js', 'serviceCommentsVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('service_comment_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_comments_assets');

// مقداردهی اولیه سیستم نظرات Front-end
function init_ai_assistant_comments_frontend_admin() {
    AI_Assistant_Comments_Frontend_Admin::get_instance();
}
add_action('init', 'init_ai_assistant_comments_frontend_admin');


// ایجاد خودکار صفحه مدیریت نظرات
function create_comments_admin_page() {
    $page_slug = 'management-comments';
    
    // بررسی وجود صفحه
    $page = get_page_by_path($page_slug);
    
    if (!$page) {
        // ایجاد صفحه جدید
        $page_id = wp_insert_post([
            'post_title' => 'مدیریت نظرات',
            'post_name' => $page_slug,
            'post_content' => '[service_comments_admin]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ]);
        
        // ذخیره ID صفحه در options برای استفاده بعدی
        if ($page_id && !is_wp_error($page_id)) {
            update_option('ai_comments_admin_page_id', $page_id);
        }
    }
}

// اجرای ایجاد صفحه هنگام فعال سازی تم
add_action('after_switch_theme', 'create_comments_admin_page');

// همچنین هنگام بارگذاری اولیه نیز بررسی شود
add_action('init', 'create_comments_admin_page');

