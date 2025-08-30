<?php
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
            
        //    $service_name = sanitize_text_field($_POST['name']);
            $service_id = sanitize_text_field($_POST['service_id']);
            $userData = stripslashes($_POST['userData']);
            
            $all_services = get_option('ai_assistant_services', []);
            $system_prompt = $all_services[$service_id]['system_prompt'] ?? '';
            $prompt = $system_prompt . "\n\n" . $userData;

            $service_name = $all_services[$service_id]['name'];
            $service_manager = AI_Assistant_Service_Manager::get_instance();
            $price = $service_manager->get_service_price($service_id);
            
            if ($price === 0) {
            //    $all_services = get_option('ai_assistant_services', []);
                $price = $all_services[$service_id]['price'] ?? 0;
            }
            
            
            
            $payment_handler = AI_Assistant_Payment_Handler::get_instance();
            
        
            $this->validate_request($prompt, $service_id, $user_id, $price, $payment_handler);

            // کسر هزینه
            $payment_handler->deduct_credit($user_id, $price, $service_name);

            // ثبت لاگ
            $this->logger->log('Processing API request', [
                'user_id' => $user_id,
                'service_id' => $service_id,
                'price' => $price,
                'system_prompt:'=> $system_prompt
            ]);

            // فراخوانی API------------------------------------------------------
          //  $response = $this->call_deepseek_api($prompt);
            //-----------------------------------------------------------------
           $response  = $prompt      ;
        /*
            $response = '<h3 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 6px;">۴. هشدارها</h3>
                <ul style="padding-left: 20px;">
              <li>[مثلاً کاهش کربوهیدرات در بیماران دیابتی باید تحت نظر پزشک انجام شود.]</li>
              <li>[در صورت مصرف داروهای خاص، منع مصرف مکمل‌ها را در نظر بگیرید.]</li>
            </ul>';
        */    
            $history_manager = AI_Assistant_History_Manager::get_instance();
            // ذخیره در تاریخچه
            $saved = $history_manager->save_history($user_id, $service_id , $service_name , $response);
            


            if (is_wp_error($saved)) {
                // خطای وردپرس
                $this->logger->log('History save WP_Error', [
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'error_code' => $saved->get_error_code(),
                    'error_message' => $saved->get_error_message(),
                    'error_data' => $saved->get_error_data()
                ]);
              //  echo 'خطای سیستمی در ذخیره‌سازی: ' . $saved->get_error_message();
            } elseif ($saved) {
                // موفقیت‌آمیز
                $this->logger->log('History saved successfully', [
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'service-name' => $service_name ,
                    'history_id' => $saved,
                    'output_sample' => substr(strip_tags($response), 0, 100)
                ]);
              //  echo 'نتایج با موفقیت ذخیره شد.';
            } else {
                // خطای عمومی
                $this->logger->log('History save failed', [
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'html_size' => strlen($response),
                    'last_error' => error_get_last()
                ]);
              //  echo 'خطا در ذخیره‌سازی تاریخچه';
            }
            
            header('Content-Type: application/json; charset=utf-8');

            wp_send_json_success([
                'response' => $response,
                'remaining_credit' => $payment_handler->get_user_credit($user_id)
            ]);

        } catch (Exception $e) {

/*
            if (!empty($user_id) && !empty($price) && isset($payment_handler)) {
                $payment_handler->add_credit($user_id, $price, $description);
            }
            
*/            

            $this->logger->log_error($e->getMessage(), $_POST);
            wp_send_json_error($e->getMessage());
        }

        wp_die();
    }

    private function validate_request($prompt, $service_id, $user_id, $price, $payment_handler) {
        if (!is_user_logged_in()) {
            throw new Exception('برای استفاده از این سرویس باید وارد حساب کاربری خود شوید');
        }
        
        
        if (empty($prompt) || empty($service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        if (!$payment_handler->has_enough_credit($user_id, $price)) {
            throw new Exception('.موجودی حساب شما کافی نیست');
        } 
    }

    private function call_deepseek_api($prompt) {
        $api_url = 'https://api.deepseek.com/v1/chat/completions';

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.9,
                'max_tokens' => 500
            ]),
            'timeout' => 120 ,
            'httpversion' => '1.1' // 📡 اطمینان از نسخه HTTP سازگار
        ];

        // ارسال درخواست به api دیپ سیک
        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            $this->logger->log_error('DeepSeek API connection error', [
                'error' => $response->get_error_message(),
                'prompt' => $prompt
            ]);
            throw new Exception('خطا در ارتباط با سرور DeepSeek: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $this->logger->log('DeepSeek API response', [
            'status_code' => $response_code,
            'response' => $body
        ]);

        if ($response_code !== 200) {
            $this->logger->log_error('DeepSeek API returned error status', [
                'status_code' => $response_code,
                'response' => $body
            ]);
            throw new Exception('خطا از سمت DeepSeek API. کد وضعیت: ' . $response_code);
        }

        $decoded_body = json_decode($body, true);

        if (empty($decoded_body['choices'][0]['message']['content'])) {
            $this->logger->log_error('Invalid API response structure', [
                'response_body' => $decoded_body
            ]);
            throw new Exception('پاسخ نامعتبر از API دریافت شد. ساختار پاسخ: ' . json_encode($decoded_body));
        }

        // تولیدی با فیلتر کدهای html
        // return sanitize_textarea_field($decoded_body['choices'][0]['message']['content']);
      
        // مستقیماً HTML تولیدی را بدون فیلتر بازگردان
        return $decoded_body['choices'][0]['message']['content'];
    }

    public function handle_unauthorized() {
        wp_send_json_error([
            'message' => 'برای استفاده از این سرویس باید وارد حساب کاربری خود شوید',
            'login_url' => wp_login_url()
        ], 401);
    }
}
