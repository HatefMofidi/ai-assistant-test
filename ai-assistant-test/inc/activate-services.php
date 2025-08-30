<?php
// فعال‌سازی اولیه سرویس‌ها
function ai_assistant_activate_services() {
    $db = AI_Assistant_Service_DB::get_instance();
    $db->create_table();
    
    $default_services = [
        [
            'service_id' => 'chat',
            'name' => 'چت هوشمند',
            'price' => 10000,
            'description' => 'سرویس گفتگوی هوش مصنوعی',
            'system_prompt' => '',
            'icon' => 'dashicons-format-chat',
            'active' => true,
            'template' => get_template_directory() . '/services/chat/template-parts/form.php'
        ],
        [
            'service_id' => 'diet',
            'name' => 'رژیم غذایی',
            'price' => 15000,
            'description' => 'طراحی برنامه غذایی شخصی‌سازی شده',
            'system_prompt' => '',
            'icon' => 'dashicons-carrot',
            'active' => true,
            'template' => get_template_directory() . '/services/diet/template-parts/form.php'
        ],
        [
            'service_id' => 'workout',
            'name' => 'برنامه بدنسازی',
            'price' => 15000,
            'description' => 'طراحی برنامه تمرینی شخصی‌سازی شده',
            'system_prompt' => '',
            'icon' => 'dashicons-universal-access-alt',
            'active' => true,
            'template' => get_template_directory() . '/services/workout/template-parts/form.php'
        ]
    ];
    
    // فقط اگر جدول خالی است، سرویس‌های پیش‌فرض را اضافه کن
    $existing_services = $db->get_all_services();
    if (empty($existing_services)) {
        foreach ($default_services as $service) {
            $db->add_service($service);
        }
    }
}

// ثبت هوک فعال‌سازی
register_activation_hook(__FILE__, 'ai_assistant_activate_services');