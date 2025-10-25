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
            
            $decodedData = json_decode($userData, true); // true برای تبدیل به آرایه
            
            
            // استخراج داده‌ها
            $userInfo = $decodedData['userInfo'] ?? [];
            $serviceSelection = $decodedData['serviceSelection'] ?? []; 
            $discountInfo = $decodedData['discountInfo'] ?? [];
           
           
            
            if ($service_id === 'diet' ){
                
                    $serviceSelectionDietType = $serviceSelection['dietType'] ?? null;
                    
                    if ( $serviceSelectionDietType === 'with-specialist'   ){

                        // استخراج داده‌های selectedSpecialist (اگر وجود دارد)
                        $selectedSpecialistId = null;
                        $selectedSpecialistName = null;
                        $selectedSpecialistSpecialty = null;
                        
                        if (isset($serviceSelection['selectedSpecialist']) && is_array($serviceSelection['selectedSpecialist'])) {
                            $selectedSpecialistId = $serviceSelection['selectedSpecialist']['id'] ?? null;
                            $selectedSpecialistName = $serviceSelection['selectedSpecialist']['name'] ?? null;
                            $selectedSpecialistSpecialty = $serviceSelection['selectedSpecialist']['specialty'] ?? null;
                        } 
                    
                    }                    

            }
            
        
            $all_services = get_option('ai_assistant_services', []);
            $service_name = $all_services[$service_id]['name'];
            $service_manager = AI_Assistant_Service_Manager::get_instance();
            $original_price = $service_manager->get_service_price($service_id);
            
            $service_info = $service_manager->get_service($service_id);
            if ($service_info && isset($service_info['system_prompt'])) {
                $system_prompt = $service_info['system_prompt'];
            } else {
                error_log('Service not found or system_prompt not set');
            }
            
            $prompt = $system_prompt . "\n\n" . $userInfo;
            $payment_handler = AI_Assistant_Payment_Handler::get_instance();
            
            
            //// DISCOUNT
                        
            try {
                $discountInfo_discount_code = $discountInfo['discountCode'] ?? null;
                $discountInfo_discountApplied = $discountInfo['discountApplied'] ?? null;
                
                // اگر کد تخفیف وارد شده بود اما معتبر نبود
                if ($discountInfo_discount_code && !empty($discountInfo_discount_code && $discountInfo_discountApplied)) {
                    // اعتبارسنجی کد تخفیف
                    $validation_result = AI_Assistant_Discount_Manager::validate_discount(
                        $discountInfo_discount_code, 
                        $service_id, 
                        $user_id
                    );
                    
                    if ($validation_result['valid']) {
                        // محاسبه قیمت با تخفیف
                        $discounted_price = AI_Assistant_Discount_Manager::calculate_discounted_price(
                            $original_price, 
                            $validation_result['discount']
                        );
                        
                        // استفاده از قیمت با تخفیف
                        $final_price = $discounted_price;
                        $discount_applied = true;
                        
                    } else {
                        throw new Exception("کد تخفیف نامعتبر: " . $validation_result['message']);
                        
                    }
                } else {
                    // اگر کد تخفیف وارد نشده بود
                    $final_price = $original_price;
                    $discount_applied = false;
                }
                
                // ادامه پردازش با $final_price
                
            } catch (Exception $e) {
                // مدیریت خطا
                error_log('Discount Error: ' . $e->getMessage());
                
                
            }
            
            
             
           
                       // ثبت لاگ
            $this->logger->log('DISCOUNT LOG::::::::::::DISCOUNT LOG:', [
                'original_price:' => $original_price,
                'discounted_price:' => $discounted_price,
                'discount:' => $validation_result['discount'] 
            ]);            
            
        


            global $wpdb;
    
            try {
                // 1. اعتبارسنجی اولیه (اطمینان از اینکه درخواست درست است، اعتبار کاربر و ...)
                $this->validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler);
    
                // 2. فراخوانی سرویس خارجی (DeepSeek یا هر API‌ای)
                $response = $this->call_deepseek_api($prompt);
                
                // if (OTP_ENV === 'production') {
                //     $response = $this->call_deepseek_api($prompt);
                // } else {
                //  //   $response  = $json_string ;
                //     $response = $this->call_deepseek_api($prompt);
                // }                
    
                // 3. بررسی موفقیت پاسخ API
                if (!$response || (is_array($response) && isset($response['error']))) {
                    // اگر API پاسخ معتبری برنگردانده، خطا بده
                    $err = is_array($response) && isset($response['error']) ? $response['error'] : 'Empty or invalid API response';
                    throw new Exception("API call failed: " . $err);
                }
                
                
                $cleaned_response = $this->clean_api_response($response);

    
                // 4. شروع تراکنش دیتابیس
                $wpdb->query('START TRANSACTION');
    
                // 5. کسر اعتبار از کاربر
                $deductResult = $payment_handler->deduct_credit($user_id, $final_price, $service_name);
                if ($deductResult === false || (is_array($deductResult) && isset($deductResult['error']))) {
                    $err = is_array($deductResult) && isset($deductResult['error']) ? $deductResult['error'] : 'Deduct credit failed';
                    throw new Exception("Payment deduction failed: " . $err);
                }
                
    
                // 6. ذخیره در تاریخچه
                $history_manager = AI_Assistant_History_Manager::get_instance();
                $saved = $history_manager->save_history($user_id, $service_id, $service_name, $userData, $cleaned_response);
                if ($saved === false || empty($saved)) {
                    // save_history باید شناسه رکورد یا true برگرداند؛ اگر false یا خالی بود، خطا می‌دهیم
                    throw new Exception('Failed to save history');
                }
                
                // ✅ افزایش usage_count برای تخفیف‌های کوپن
                if ($discount_applied && 
                    isset($validation_result['discount']) && 
                    $validation_result['discount']->scope === 'coupon') {
                    
                    $discount_db = AI_Assistant_Discount_DB::get_instance();
                    $discount_db->increment_usage($validation_result['discount']->id);
                    
                    $this->logger->log('Discount usage incremented:', [
                        'discount_id' => $validation_result['discount']->id,
                        'discount_code' => $discountInfo_discount_code,
                        'user_id' => $user_id,
                        'service_id' => $service_id,
                        'final_price' => $final_price
                    ]);
                }                
    
                // 7. در صورت نیاز، ثبت درخواست مشاوره
                $Consultant_Rec = null;
                if ($service_id === 'diet' && $serviceSelectionDietType === 'with-specialist') {
                    $Nutrition_Consultant_Manager = AI_Assistant_Nutrition_Consultant_Manager::get_instance();
                    $Consultant_Rec = $Nutrition_Consultant_Manager->submit_consultation_request($saved, 6000);
    
                    if ($Consultant_Rec === false || (is_array($Consultant_Rec) && isset($Consultant_Rec['error']))) {
                        $err = is_array($Consultant_Rec) && isset($Consultant_Rec['error']) ? $Consultant_Rec['error'] : 'submit_consultation_request failed';
                        throw new Exception("Consultation request failed: " . $err);
                    }
                }
                
                
                // 8. همه چی موفق بود -> commit
                $wpdb->query('COMMIT');
    
    
            } catch (Exception $e) {
                // هر خطایی رخ داد، rollback و لاگ
                try {
                    $wpdb->query('ROLLBACK');
                } catch (Exception $rollbackEx) {
                    // اگر rollback هم خطا داد، لاگش کن
                    error_log('Rollback failed: ' . $rollbackEx->getMessage());
                }
    
                // لاگ خطا برای دیباگ در سرور
                error_log('process_request_and_charge error: ' . $e->getMessage());
    
                // برگردوندن خطا به فراخواننده — (می‌تونی این شیوه را سفارشی کنی)
                return [
                    'success' => false,
                    'message' => 'Processing failed: ' . $e->getMessage(),
                    'exception' => $e->getMessage(),
                ];
            }

            // ثبت لاگ
            $this->logger->log('LOG::::::::::::LOG:', [
                '$prompt:' => $prompt,
                'userData:' => $userData ,
                '$userInfo' => $userInfo
            ]);
            //-----------------------------------------------------------------

            $json_string = ' test tstring ';     

            header('Content-Type: application/json; charset=utf-8');

            wp_send_json_success([
                'response' => $response,
                'remaining_credit' => $payment_handler->get_user_credit($user_id)
            ]);

        } catch (Exception $e) {

            $this->logger->log_error($e->getMessage(), $_POST);
            wp_send_json_error($e->getMessage());
        }

        wp_die();
    }
    
    

    private function validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler) {
        if (!is_user_logged_in()) {
            throw new Exception('برای استفاده از این سرویس باید وارد حساب کاربری خود شوید');
        }
        
        
        if (empty($prompt) || empty($service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        if (!$payment_handler->has_enough_credit($user_id, $final_price)) {
            throw new Exception('.موجودی حساب شما کافی نیست');
        } 
    }

    private function call_deepseek_api($prompt) {
        
      //  sleep(240); // توقف به مدت 3 ثانیه جهت تست
        $prompt = ' یک جمله خیلی کوتاه بگو';
         
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
                'temperature' => 0.2,
                'max_tokens' => 8000
            ]),
            'timeout' => 180 ,
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
            $add_credit_description='برگشت وجه بدلیل خطا ';
           // $payment_handler->add_credit($user_id, $price, $add_credit_description);
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
    
    
    private function clean_api_response($response_content) {
        // حذف markdown code blocks
        $patterns = [
            '/^```json\s*/', // ابتدای json block
            '/\s*```$/', // انتهای json block  
            '/^```\s*/', // سایر code blocks
            '/\s*```$/',
        ];
        
        $cleaned_response = preg_replace($patterns, '', $response_content);
        
        // حذف فضاهای خالی اضافی
        $cleaned_response = trim($cleaned_response);
        
        // حذف کاراکترهای غیر قابل چاپ
        $cleaned_response = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned_response);
        
        return $cleaned_response;
    }     
}
