<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/functions.php
 * Functions for AI Assistant Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// اضافه کردن rewrite rule برای صفحه پروفایل
function ai_assistant_add_profile_rewrite_rule() {
    add_rewrite_rule('^profile/?', 'index.php?profile_page=1', 'top');
}
add_action('init', 'ai_assistant_add_profile_rewrite_rule');

// اضافه کردن query var
function ai_assistant_add_profile_query_var($vars) {
    $vars[] = 'profile_page';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_profile_query_var');

// هدایت درخواست به تمپلیت مناسب
function ai_assistant_profile_template($template) {
    if (get_query_var('profile_page')) {
        $profile_template = locate_template(['templates/profile.php', 'profile.php']);
        if ($profile_template) {
            return $profile_template;
        }
        
        // اگر تمپلیت پیدا نشد، از تمپلیت پیشفرض استفاده کن
        return get_template_directory() . '/templates/profile.php';
    }
    return $template;
}
add_filter('template_include', 'ai_assistant_profile_template');