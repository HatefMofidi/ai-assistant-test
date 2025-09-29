
<?php
/**
 * اضافه کردن rewrite rule برای صفحه اطلاعات سرویس
 */
function ai_assistant_add_service_info_rewrite_rule() {
    add_rewrite_rule('^service-info/([^/]+)/?', 'index.php?service_info_page=1&service_id=$matches[1]', 'top');
    add_rewrite_rule('^service-info/?', 'index.php?service_info_page=1', 'top');
}
add_action('init', 'ai_assistant_add_service_info_rewrite_rule');

// اضافه کردن query var
function ai_assistant_add_service_info_query_var($vars) {
    $vars[] = 'service_info_page';
    $vars[] = 'service_id';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_service_info_query_var');

// هدایت درخواست به تمپلیت مناسب
function ai_assistant_service_info_template($template) {
    if (get_query_var('service_info_page')) {
        $service_info_template = locate_template(['templates/service-info.php', 'service-info.php']);
        if ($service_info_template) {
            return $service_info_template;
        }
        
        // اگر تمپلیت پیدا نشد، از تمپلیت پیشفرض استفاده کن
        return get_template_directory() . '/templates/service-info.php';
    }
    return $template;
}
add_filter('template_include', 'ai_assistant_service_info_template');