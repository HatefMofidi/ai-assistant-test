
<?php
// ایجاد صفحه مدیریت در منوی پیشخوان
add_action('admin_menu', function() {
    add_menu_page(
        'مدیریت سرویس‌های هوش مصنوعی',
        'سرویس‌های AI',
        'manage_options',
        'ai-assistant-services',
        'ai_assistant_render_services_admin',
        'dashicons-admin-generic',
        30
    );
});



// نمایش صفحه مدیریت
function ai_assistant_render_services_admin() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }

    $service_manager = AI_Assistant_Service_Manager::get_instance();
    
     $logger = AI_Assistant_Logger::get_instance();

    // پردازش ارسال فرم (همانند قبل بدون تغییر)
    if (isset($_POST['submit_service'])) {
        check_admin_referer('ai_service_nonce');

        $service_id = sanitize_text_field($_POST['service_id']);
        $data = $_POST['service_data'];


        $logger->log('services-admin.update_service', [
                'step' => $data
            ]); 
            
            
        $current_service = $service_manager->get_service($service_id);

        $service_data = [
            
            'name' => sanitize_text_field($data['name']),
            'price' => absint($data['price']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
           // 'system_prompt' => sanitize_textarea_field($data['system_prompt'] ?? ''),
            'system_prompt' => $data['system_prompt'],
           // 'system_prompt' => wp_kses($data['system_prompt'] ?? '', $service_manager->get_allowed_html_tags()),
            'icon' => sanitize_text_field($data['icon']),
            'active' => isset($data['active']) ? (bool)$data['active'] : false,
            'template' => $current_service['template'] ??  '/services/' . $service_id . '/template-parts/form.php' // حفظ مسیر قالب
        ];




        $service_manager->update_service($service_id, $service_data);
        

        // نمایش پیام موفقیت
        add_settings_error(
            'ai_assistant_messages',
            'ai_assistant_message',
            __('تغییرات با موفقیت ذخیره شدند', 'ai-assistant'),
            'updated'
        );
        
        // نمایش پیغام‌ها
        settings_errors('ai_assistant_messages');        
    }
    
    

    // نمایش صفحه مناسب بر اساس پارامتر URL
    if (isset($_GET['edit_service'])) {
        $service_id = sanitize_text_field($_GET['edit_service']);
        
        // اگر سرویس جدید باشد
        if ($service_id === 'new') {
            $service = [
                'name' => '',
                'price' => 0,
                'description' => '',
                'system_prompt' => '',
                'icon' => 'dashicons-admin-generic',
                'active' => true,
                'template' => ''
            ];
            $service_id = 'new';
        } else {
            $service = $service_manager->get_service($service_id);
        }

        if ($service) {
            include __DIR__ . '/views/edit-service.php';
            return;
        }
    }
    
    
    
    // پردازش درخواست حذف سرویس
    if (isset($_GET['delete_service'])) {
        check_admin_referer('delete_service_' . $_GET['delete_service']);
        
        $service_id = sanitize_text_field($_GET['delete_service']);
        $service_manager = AI_Assistant_Service_Manager::get_instance();
        
        if ($service_manager->delete_service($service_id)) {
            add_settings_error(
                'ai_assistant_messages',
                'ai_assistant_message',
                __('سرویس با موفقیت حذف شد', 'ai-assistant'),
                'updated'
            );
        } else {
            add_settings_error(
                'ai_assistant_messages',
                'ai_assient_message',
                __('خطا در حذف سرویس', 'ai-assistant'),
                'error'
            );
        }
        
        // ریدایرکت به صفحه اصلی پس از حذف
        wp_redirect(admin_url('admin.php?page=ai-assistant-services'));
        exit;
    }    

    // نمایش لیست سرویس‌ها
    include __DIR__ . '/views/services-list.php';
}



// اضافه کردن استایل‌های مدیریت
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'toplevel_page_ai-assistant-services') {
        wp_enqueue_style(
            'ai-admin-css',
            get_theme_file_uri('inc/admin/assets/css/admin.css')
        );
    }
});