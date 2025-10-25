<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/account-functions.php
 * Functions for AI Assistant Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// اضافه کردن rewrite rule برای صفحه account
function ai_assistant_add_account_rewrite_rule() {
    add_rewrite_rule('^account/?', 'index.php?account_page=1', 'top');
}
add_action('init', 'ai_assistant_add_account_rewrite_rule');

// اضافه کردن query var
function ai_assistant_add_account_query_var($vars) {
    $vars[] = 'account_page';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_account_query_var');

// هدایت درخواست به تمپلیت مناسب
function ai_assistant_account_template($template) {
    if (get_query_var('account_page')) {
        $account_template = locate_template(['templates/account.php', 'account.php']);
        if ($account_template) {
            return $account_template;
        }
        
        // اگر تمپلیت پیدا نشد، از تمپلیت پیشفرض استفاده کن
        return get_template_directory() . '/templates/account.php';
    }
    return $template;
}
add_filter('template_include', 'ai_assistant_account_template');