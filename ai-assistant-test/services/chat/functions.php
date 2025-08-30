<?php
// ثبت سرویس چت
// ثبت سرویس چت (فقط در زمان فعال‌سازی پلاگین/تم)
function ai_assistant_register_chat_service() {
    $service_manager = AI_Assistant_Service_Manager::get_instance();
    
    // فقط اگر سرویس از قبل وجود ندارد، آن را ثبت کنیم
    if (!$service_manager->get_service('chat')) {
        $service_manager->register_service([
            'id' => 'chat',
            'name' => __('چت هوش مصنوعی', 'ai-assistant'),
            'description' => __('پرسش و پاسخ با هوش مصنوعی پیشرفته', 'ai-assistant'),
            'price' => 100000, // فقط به عنوان مقدار پیش‌فرض
            'icon' => 'dashicons-format-chat',
            'template' => get_template_directory() . '/services/chat/template-parts/form.php',
            'active' => true
        ]);
    }
    
    // ثبت هوک‌های AJAX
    add_action('wp_ajax_ai_assistant_chat', 'ai_assistant_handle_chat_request');
    add_action('wp_ajax_nopriv_ai_assistant_chat', 'ai_assistant_handle_unauthorized');
}

// فقط یک بار در زمان فعال‌سازی ثبت شود
register_activation_hook(__FILE__, 'ai_assistant_register_chat_service');

// یا اگر تم هستید، از هوک after_setup_theme استفاده کنید
add_action('after_setup_theme', 'ai_assistant_register_chat_service');





// مدیریت کاربران غیرمجاز
function ai_assistant_handle_unauthorized() {
    wp_send_json_error([
        'message' => __('برای استفاده از این سرویس باید وارد حساب کاربری خود شوید', 'ai-assistant'),
        'login_url' => wp_login_url()
    ], 401);
}

