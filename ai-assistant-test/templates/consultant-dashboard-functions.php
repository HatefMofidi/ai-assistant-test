<?php
/**
 * Functions for Consultant Dashboard Page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// اضافه کردن rewrite rule برای صفحه مالی مشاور
function ai_assistant_add_consultant_financial_rewrite_rule() {
    add_rewrite_rule('^consultant-financial/?', 'index.php?consultant_financial_page=1', 'top');
}
add_action('init', 'ai_assistant_add_consultant_financial_rewrite_rule');

// اضافه کردن query var
function ai_assistant_add_consultant_financial_query_var($vars) {
    $vars[] = 'consultant_financial_page';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_consultant_financial_query_var');

// هدایت درخواست به تمپلیت مناسب
function ai_assistant_consultant_financial_template($template) {
    if (get_query_var('consultant_financial_page')) {
        $financial_template = locate_template(['templates/page-consultant-financial.php', 'page-consultant-financial.php']);
        if ($financial_template) {
            return $financial_template;
        }
        
        // اگر تمپلیت پیدا نشد، از تمپلیت پیشفرض استفاده کن
        return get_template_directory() . '/templates/page-consultant-financial.php';
    }
    return $template;
}
add_filter('template_include', 'ai_assistant_consultant_financial_template');






// اضافه کردن rewrite rule برای صفحه مشاور
function ai_assistant_add_consultant_dashboard_rewrite_rule() {
    add_rewrite_rule('^consultant-dashboard/?', 'index.php?consultant_dashboard_page=1', 'top');
}
add_action('init', 'ai_assistant_add_consultant_dashboard_rewrite_rule');

// اضافه کردن query var
function ai_assistant_add_consultant_dashboard_query_var($vars) {
    $vars[] = 'consultant_dashboard_page';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_consultant_dashboard_query_var');

// هدایت درخواست به تمپلیت مناسب
function ai_assistant_consultant_dashboard_template($template) {
    if (get_query_var('consultant_dashboard_page')) {
        $dashboard_template = locate_template(['templates/page-consultant-dashboard.php', 'page-consultant-dashboard.php']);
        if ($dashboard_template) {
            return $dashboard_template;
        }
        
        // اگر تمپلیت پیدا نشد، از تمپلیت پیشفرض استفاده کن
        return get_template_directory() . '/templates/page-consultant-dashboard.php';
    }
    return $template;
}
add_filter('template_include', 'ai_assistant_consultant_dashboard_template');




// اضافه کردن rewrite rule برای صفحه مدیریت تسویه ادمین
function ai_assistant_add_admin_payout_rewrite_rule() {
    add_rewrite_rule('^admin-payout-manager/?', 'index.php?admin_payout_page=1', 'top');
}
add_action('init', 'ai_assistant_add_admin_payout_rewrite_rule');

// اضافه کردن query var
function ai_assistant_add_admin_payout_query_var($vars) {
    $vars[] = 'admin_payout_page';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_admin_payout_query_var');

// هدایت درخواست به تمپلیت مناسب
function ai_assistant_admin_payout_template($template) {
    if (get_query_var('admin_payout_page')) {
        $payout_template = locate_template(['templates/page-admin-payout-manager.php', 'page-admin-payout-manager.php']);
        if ($payout_template) {
            return $payout_template;
        }
        
        return get_template_directory() . '/templates/page-admin-payout-manager.php';
    }
    return $template;
}
add_filter('template_include', 'ai_assistant_admin_payout_template');
