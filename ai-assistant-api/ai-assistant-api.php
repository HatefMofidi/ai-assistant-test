<?php
/**
 * Plugin Name: AI Assistant API Handler
 * Description: پردازش درخواست‌های هوش مصنوعی و ارتباط با DeepSeek API
 * Version: 1.0.0
 * Author: Your Name
 */

defined('ABSPATH') or die('دسترسی ممنوع!');

// تعریف ثابت‌ها
define('AI_ASSISTANT_API_VERSION', '1.0.0');
define('AI_ASSISTANT_API_PATH', plugin_dir_path(__FILE__));
define('AI_ASSISTANT_API_URL', plugin_dir_url(__FILE__));

// بارگذاری فایل‌های مورد نیاز
require_once AI_ASSISTANT_API_PATH . 'includes/class-api-handler.php';
require_once AI_ASSISTANT_API_PATH . 'includes/class-logger.php';

// راه‌اندازی پلاگین
AI_Assistant_Api_Handler::init();