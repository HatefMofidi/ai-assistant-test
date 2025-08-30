<?php
// ثبت سرویس بدنسازی
function ai_assistant_register_workout_service() {
    $service_manager = AI_Assistant_Service_Manager::get_instance();
    
    if (!$service_manager->get_service('workout')) {
        $service_manager->register_service([
            'id' => 'workout',
            'name' => __('برنامه بدنسازی هوشمند', 'ai-assistant'),
            'description' => __('طراحی برنامه تمرینی بر اساس مشخصات فردی', 'ai-assistant'),
            'price' => 15000,
            'icon' => 'dashicons-universal-access-alt',
            'template' => get_template_directory() . '/services/workout/template-parts/form.php',
            'active' => true
        ]);
    }
    
    // ثبت هوک‌های AJAX
    add_action('wp_ajax_ai_assistant_workout', 'ai_assistant_handle_workout_request');
    add_action('wp_ajax_nopriv_ai_assistant_workout', 'ai_assistant_handle_unauthorized');
}
add_action('after_setup_theme', 'ai_assistant_register_workout_service');