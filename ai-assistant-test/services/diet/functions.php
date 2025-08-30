
<?php
// ثبت سرویس رژیم غذایی
function ai_assistant_register_diet_service() {
    $service_manager = AI_Assistant_Service_Manager::get_instance();
    
    if (!$service_manager->get_service('diet')) {
        $service_manager->register_service([
            'id' => 'diet',
            'name' => __('رژیم غذایی هوشمند', 'ai-assistant'),
            'description' => __('طراحی برنامه غذایی بر اساس مشخصات فردی', 'ai-assistant'),
            'price' => 15000,
            'icon' => 'dashicons-carrot',
            'template' => get_template_directory() . '/services/diet/template-parts/form.php',
            'active' => true
        ]);
    }
    
    // ثبت هوک‌های AJAX
    add_action('wp_ajax_ai_assistant_diet', 'ai_assistant_handle_diet_request');
    add_action('wp_ajax_nopriv_ai_assistant_diet', 'ai_assistant_handle_unauthorized');
}
add_action('after_setup_theme', 'ai_assistant_register_diet_service');