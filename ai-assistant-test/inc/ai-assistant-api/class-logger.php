<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class AI_Assistant_Logger {
    private static $instance;
    private $log_file;
    private $log_dir;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // تعیین مسیر لاگ‌ها در دایرکتوری وردپرس
        $this->log_dir = WP_CONTENT_DIR . '/ai-assistant-logs/';
        
        // ایجاد پوشه اگر وجود ندارد
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
            
            // اضافه کردن فایل امنیتی .htaccess
            $htaccess = $this->log_dir . '.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Order deny,allow\nDeny from all");
            }
            
            // اضافه کردن فایل index خالی برای امنیت
            $index = $this->log_dir . 'index.php';
            if (!file_exists($index)) {
                file_put_contents($index, "<?php\n// Silence is golden");
            }
        }
        
        $this->log_file = $this->log_dir . 'ai-assistant.log';
        
        // ایجاد فایل لاگ اگر وجود ندارد
        if (!file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
        
        // بررسی دسترسی نوشتن
        $this->check_permissions();
    }
    
    /**
     * بررسی دسترسی‌های فایل و پوشه
     */
    private function check_permissions() {
        // بررسی قابل نوشتن بودن پوشه
        if (!is_writable($this->log_dir)) {
            error_log('AI Assistant: Log directory is not writable: ' . $this->log_dir);
            throw new Exception('پوشه لاگ قابل نوشتن نیست');
        }
        
        // بررسی قابل نوشتن بودن فایل
        if (file_exists($this->log_file) && !is_writable($this->log_file)) {
            error_log('AI Assistant: Log file is not writable: ' . $this->log_file);
            throw new Exception('فایل لاگ قابل نوشتن نیست');
        }
    }
    
    /**
     * بررسی امنیتی قبل از نوشتن لاگ
     */
    private function security_check() {
        // بررسی مسیر برای جلوگیری از حملات Directory Traversal
        $real_log_dir = realpath($this->log_dir);
        $real_file_dir = realpath(dirname($this->log_file));
        
        if ($real_file_dir !== $real_log_dir) {
            error_log('AI Assistant: Potential directory traversal attempt detected');
            throw new Exception('مسیر فایل لاگ نامعتبر است');
        }
    }
    
    public function log($message, $context = []) {
        try {
            $this->security_check();
            
            $timestamp = current_time('mysql');
            $log_message = "[{$timestamp}] INFO: {$message}";
            
            if (!empty($context)) {
                $log_message .= " - " . json_encode($context, JSON_UNESCAPED_UNICODE);
            }
            
            file_put_contents($this->log_file, $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            error_log('AI Assistant Log Error: ' . $e->getMessage());
        }
    }
    
    public function log_error($message, $context = []) {
        try {
            $this->security_check();
            
            $timestamp = current_time('mysql');
            $log_message = "[{$timestamp}] ERROR: {$message}";
            
            if (!empty($context)) {
                $log_message .= " - " . json_encode($context, JSON_UNESCAPED_UNICODE);
            }
            
            file_put_contents($this->log_file, $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
            
            // برای محیط توسعه
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log($log_message);
            }
            
        } catch (Exception $e) {
            error_log('AI Assistant Log Error: ' . $e->getMessage());
        }
    }
    
    public function get_logs($limit = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($logs), 0, $limit);
    }
}