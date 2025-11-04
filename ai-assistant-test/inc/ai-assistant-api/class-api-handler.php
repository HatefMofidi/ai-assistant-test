<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class AI_Assistant_Api_Handler {
    private static $instance;
    private $api_key;
    private $logger;

    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // تغییر نام option به ai_assistant_deepseek_key
        $this->api_key = get_option('ai_assistant_deepseek_key');
        $this->logger = AI_Assistant_Logger::get_instance();
        

        $this->register_hooks();

        // اطمینان از وجود پوشه لاگ
        if (!file_exists(WP_CONTENT_DIR.'/ai-assistant-logs')) {
            wp_mkdir_p(WP_CONTENT_DIR.'/ai-assistant-logs');
        }
    }   

    private function register_hooks() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_ai_assistant_process', [$this, 'process_request']);
        add_action('wp_ajax_nopriv_ai_assistant_process', [$this, 'handle_unauthorized']);
    }

    public function add_admin_page() {
        add_options_page(
            'تنظیمات DeepSeek',
            'DeepSeek_API',
            'manage_options',
            'ai_assistant-settings', // تغییر نام منو
            [$this, 'render_admin_page']
        );
    }

    public function register_settings() {
        register_setting('ai_assistant_settings', 'ai_assistant_deepseek_key'); // تغییر نام تنظیمات

        add_settings_section(
            'ai_assistant_api_section', // تغییر نام سکشن
            'تنظیمات API',
            null,
            'ai_assistant-settings'
        );

        add_settings_field(
            'ai_assistant_deepseek_key', // تغییر نام فیلد
            'API Key',
            [$this, 'render_api_key_field'],
            'ai_assistant-settings',
            'ai_assistant_api_section'
        );
    }

    public function render_api_key_field() {
        $value = esc_attr($this->api_key);
        echo '<input type="password" name="ai_assistant_deepseek_key" value="'.$value.'" class="regular-text">'; // تغییر نام فیلد
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>تنظیمات DeepSeek API</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_assistant_settings');
                do_settings_sections('ai_assistant-settings');
                submit_button();
                ?>
            </form>

            <h2>لاگ خطاها</h2>
            <pre><?php 
                if (!current_user_can('administrator')) {
                    echo 'دسترسی مجاز نیست.';
                    return;
                }

                $log_file = WP_CONTENT_DIR . '/ai-assistant-logs/ai-assistant.log';
                echo file_exists($log_file) ? 
                     esc_html(file_get_contents($log_file)) : 
                     'لاگی وجود ندارد';
            ?></pre>
        </div>
        <?php
    }

    public function process_request() {
        check_ajax_referer('ai_assistant_nonce', 'security');         

        if (ob_get_length()) ob_clean();

        try {
            $user_id = get_current_user_id();
            if (!$user_id) {
                wp_send_json_error(__('برای استفاده از این سرویس باید وارد شوید.', 'ai-assistant'));
            }
            
            
            $service_id = sanitize_text_field($_POST['service_id']);
            $userData = stripslashes($_POST['userData']);
            
 
            //           // ثبت لاگ
            // $this->logger->log('DISCOUNT LOG::::::::::::DISCOUNT LOG:', [
            //     'original_price:' => $original_price,
            //     'discounted_price:' => $discounted_price,
            //     'discount:' => $validation_result['discount'] 
            // ]);            
            
        

            $prompt = '';
            $final_price = 0;
            
            $all_services = get_option('ai_assistant_services', []);
            $service_name = $all_services[$service_id]['name'];            
            
            // Save history

            $history_manager = AI_Assistant_History_Manager::get_instance();
            $history_id = $history_manager->save_history(
                $user_id,
                $service_id,
                $service_name,
                $userData,
                null
            );
            
            if ($history_id === false || empty($history_id)) {
                throw new Exception('Failed to save history');
            }  
            
            error_log('📝 [WORKER] Saved history for history_id #' . $history_id);            
            
            $queue = AI_Job_Queue::get_instance();
            $queue->enqueue_job($history_id , $user_id);


            header('Content-Type: application/json; charset=utf-8');

            wp_send_json_success([
                
                'response' => true 
            ]);

        } catch (Exception $e) {
            $this->logger->log_error($e->getMessage(), $_POST);
            wp_send_json_error($e->getMessage());
        }

        wp_die();
    }
    
    





    public function handle_unauthorized() {
        wp_send_json_error([
            'message' => 'برای استفاده از این سرویس باید وارد حساب کاربری خود شوید',
            'login_url' => wp_login_url()
        ], 401);
    }
    
    
     
}
