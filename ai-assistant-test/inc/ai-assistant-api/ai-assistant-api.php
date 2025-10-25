<?php
/**
 * AI Assistant API Integration
 * Description: پردازش درخواست‌های هوش مصنوعی و ارتباط با DeepSeek API
 */

defined('ABSPATH') or die('دسترسی ممنوع!');

// تعریف ثابت‌ها
define('AI_ASSISTANT_API_VERSION', '1.0.0');
define('AI_ASSISTANT_API_PATH', get_template_directory() . '/inc/ai-assistant-api/');
define('AI_ASSISTANT_API_URL', get_template_directory_uri() . '/inc/ai-assistant-api/');

// بارگذاری فایل‌های مورد نیاز
require_once AI_ASSISTANT_API_PATH . 'class-api-handler.php';
require_once AI_ASSISTANT_API_PATH . 'class-logger.php';

// راه‌اندازی در هوک init
function init_ai_assistant_api() {
    AI_Assistant_Api_Handler::init();
}
add_action('init', 'init_ai_assistant_api');