<?php
// ثبت سرویس رژیم غذایی
function ai_assistant_register_travel_service() {
    $service_manager = AI_Assistant_Service_Manager::get_instance();
    
    if (!$service_manager->get_service('travel')) {
        $service_manager->register_service([
            'id' => 'travel',
            'name' => __('برنامه‌ریز سفر هوشمند', 'ai-assistant'),
            'description' => __('طراحی برنامه سفر بر اساس ترجیحات و نیازهای شما', 'ai-assistant'),
            'price' => 20000,
            'icon' => 'dashicons-palmtree',
            'template' => get_template_directory() . '/services/travel/template-parts/form.php',
            'active' => true
        ]);
    }
    
    // ثبت هوک‌های AJAX
    add_action('wp_ajax_ai_assistant_travel', 'ai_assistant_handle_travel_request');
    add_action('wp_ajax_nopriv_ai_assistant_travel', 'ai_assistant_handle_unauthorized');
}
add_action('after_setup_theme', 'ai_assistant_register_travel_service');





