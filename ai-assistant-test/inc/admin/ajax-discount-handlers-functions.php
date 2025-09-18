<?php
// /inc/admin/ajax-discount-handlers.php

// فقط اگر در context وردپرس هستیم اجرا شود
if (!defined('ABSPATH')) {
    exit;
}

// Ajax handler برای بارگذاری بیشتر کاربران
add_action('wp_ajax_load_more_discount_users', 'handle_load_more_discount_users');
add_action('wp_ajax_nopriv_load_more_discount_users', 'handle_load_more_discount_users_no_priv');

function handle_load_more_discount_users() {
    // بررسی nonce برای امنیت
    if (!check_ajax_referer('load_more_users', 'security', false)) {
        wp_send_json_error('Nonce verification failed');
        return;
    }
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // پارامترهای جستجو اگر وجود دارند
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    $args = [
        'number' => $per_page,
        'offset' => $offset,
        'orderby' => 'display_name',
        'order' => 'ASC'
    ];
    
    // اگر جستجو وجود دارد، آن را اضافه کن
    if (!empty($search)) {
        $args['search'] = '*' . $search . '*';
        $args['search_columns'] = ['display_name', 'user_email', 'user_login'];
    }
    
    $users = get_users($args);
    
    $html = '';
    foreach ($users as $user) {
        $html .= '<label class="user-checkbox-label">';
        $html .= '<input type="checkbox" name="user_ids[]" value="' . esc_attr($user->ID) . '">';
        $html .= esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
        $html .= '</label>';
    }
    
    wp_send_json_success($html);
}

function handle_load_more_discount_users_no_priv() {
    wp_send_json_error('دسترسی غیرمجاز');
}