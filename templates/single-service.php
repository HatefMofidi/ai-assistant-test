<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/templates/single-service.php-->
<?php
/**
 * Template Name: صفحه سرویس
 */

//get_header();
get_header('service');



// روش مطمئن‌تر برای دریافت پارامتر service

//$service_id = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
// روش مطمئن‌تر برای دریافت پارامتر service
$service_id = get_query_var('service') ?: (isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '');

// دیباگ (فقط برای توسعه)
if (current_user_can('manage_options') && isset($_GET['debug'])) {
    echo '<pre>';
    echo 'Service ID: ' . $service_id . "\n";
    echo 'Query Vars: ' . print_r($GLOBALS['wp_query']->query_vars, true);
    echo 'All Services: ' . print_r(AI_Assistant_Service_Manager::get_instance()->get_active_services(), true);
    echo '</pre>';
}

$services = AI_Assistant_Service_Manager::get_instance()->get_active_services();

if ($service_id && isset($services[$service_id])) {
    $service = $services[$service_id];
    $template_path = get_template_directory() . $service['template'];
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<div class="ai-error">';
        echo __('قالب سرویس در مسیر زیر یافت نشد:', 'ai-assistant') . '<br>';
        echo '<code>' . $template_path . '</code>';
        echo '</div>';
    }
} else {
    echo '<div class="ai-error">';
    echo __('مشکل در بارگذاری سرویس. لطفاً اطلاعات زیر را به پشتیبانی ارسال کنید:', 'ai-assistant') . '<br>';
    echo '<strong>Service ID:</strong> ' . esc_html($service_id) . '<br>';
    echo '<strong>Registered Services:</strong> ' . implode(', ', array_keys($services));
    echo '</div>';
}

//get_footer();
get_footer('service');
