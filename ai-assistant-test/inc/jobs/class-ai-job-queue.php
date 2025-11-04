<?php
if (!defined('ABSPATH')) exit;

class AI_Job_Queue {
    private static $instance = null;
    private $table_name;
    private $processing_timeout = 600;
    private $logger;
    private $lock_option_key = 'ai_job_queue_processing_lock';
    private $current_job_option_key = 'ai_job_queue_current_job';

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'job_queue';
        $this->logger = AI_Assistant_Logger::get_instance();

        add_action('init', [$this, 'maybe_create_table']);
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);

        if (!wp_next_scheduled('ai_process_job_queue')) {
            wp_schedule_event(time(), 'every_minute', 'ai_process_job_queue');
        }
        add_action('ai_process_job_queue', [$this, 'process_jobs_sequentially']);

        if (!wp_next_scheduled('ai_cleanup_stuck_jobs')) {
            wp_schedule_event(time(), 'every_5_minutes', 'ai_cleanup_stuck_jobs');
        }
        add_action('ai_cleanup_stuck_jobs', [$this, 'cleanup_stuck_jobs']);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function maybe_create_table() {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") == $this->table_name) {
            return;
        }

        $lock_file = WP_CONTENT_DIR . '/ai_job_queue_table.lock';
        $lock_handle = fopen($lock_file, 'w');

        if (!flock($lock_handle, LOCK_EX | LOCK_NB)) {
            fclose($lock_handle);
            return;
        }

        try { 
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$this->table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                history_id BIGINT UNSIGNED NOT NULL,
                status ENUM('pending','processing','done','error') DEFAULT 'pending',
                error_message TEXT NULL,
                started_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                retry_count INT DEFAULT 0,
                last_attempt DATETIME NULL,
                processing_log LONGTEXT NULL,
                priority INT DEFAULT 0,
                PRIMARY KEY (id),
                INDEX (status),
                INDEX (started_at),
                INDEX (created_at),
                INDEX (priority)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            error_log('✅ [JOB_QUEUE] Table created successfully');

        } finally {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
        }
    }

    public function add_cron_intervals($schedules) {
        if (!isset($schedules['every_minute'])) {
            $schedules['every_minute'] = ['interval' => 60,'display' => __('Every Minute')];
        }
        if (!isset($schedules['every_3_minutes'])) {
            $schedules['every_3_minutes'] = ['interval' => 180,'display' => __('Every 3 Minutes')];
        }
        if (!isset($schedules['every_5_minutes'])) {
            $schedules['every_5_minutes'] = ['interval' => 300,'display' => __('Every 5 Minutes')];
        }
        return $schedules;
    }

    public function enqueue_job($history_id , $user_id) {
        global $wpdb;

        $inserted = $wpdb->insert($this->table_name, [
            'history_id'     => $history_id,
            'user_id'     => $user_id ,
            'status'      => 'pending',
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql')
        ]);

        if ($inserted === false) {
            error_log('❌ [ENQUEUE] Failed to insert job: ' . $wpdb->last_error);
            return false;
        }

        $job_id = $wpdb->insert_id;
        error_log('✅ [ENQUEUE] Job #' . $job_id . ' added - Service: ' . $service_id);

        // Trigger processing immediately
        $this->maybe_process_jobs();

        return $job_id;
    }

    private function maybe_process_jobs() {
        // Schedule immediate processing
        if (!wp_next_scheduled('ai_process_job_queue')) {
            wp_schedule_single_event(time() + 2, 'ai_process_job_queue');
        } else {
            wp_schedule_single_event(time() + 2, 'ai_process_job_queue');
        }
        spawn_cron();
    }

    public function process_jobs_sequentially() {
        global $wpdb;

        // Check if another process is already running
        $lock = get_option($this->lock_option_key);
        if ($lock && $lock > time() - 300) { // 5-minute lock
            error_log('⏸️ [SEQUENTIAL] Processing already in progress, skipping');
            return;
        }

        // Acquire lock
        update_option($this->lock_option_key, time());

        try {
            error_log('🔄 [SEQUENTIAL] Processing cycle started at: ' . current_time('mysql'));

            $this->cleanup_stuck_jobs();

            // Check if there's already a job being processed
            $current_job_id = get_option($this->current_job_option_key);
            if ($current_job_id) {
                $current_job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $current_job_id));
                if ($current_job && $current_job->status === 'processing') {
                    error_log('⏸️ [SEQUENTIAL] Job #' . $current_job_id . ' is still processing, waiting...');
                    return;
                } else {
                    // Current job is done or failed, clear it
                    delete_option($this->current_job_option_key);
                }
            }

            // Get the oldest pending job
            $pending_job = $wpdb->get_row("
                SELECT * FROM {$this->table_name} 
                WHERE status = 'pending' 
                ORDER BY id ASC 
                LIMIT 1
            ");

            if (!$pending_job) {
                error_log('📭 [SEQUENTIAL] No pending jobs found');
                return;
            }

            error_log('🎯 [SEQUENTIAL] Starting processing for job #' . $pending_job->id);

            // Claim the job
            $now = current_time('mysql');
            $updated = $wpdb->update(
                $this->table_name,
                [
                    'status' => 'processing',
                    'started_at' => $now,
                    'last_attempt' => $now,
                    'processing_log' => ($pending_job->processing_log ?: '') . "\n[SEQUENTIAL] Claimed at " . $now
                ],
                ['id' => $pending_job->id, 'status' => 'pending'],
                ['%s', '%s', '%s', '%s'],
                ['%d', '%s']
            );

            if (!$updated || $wpdb->rows_affected === 0) {
                error_log('❌ [SEQUENTIAL] Failed to claim job #' . $pending_job->id);
                return;
            }

            // Set as current job
            update_option($this->current_job_option_key, $pending_job->id);

            // Process the job
            $this->process_single_job($pending_job->id);

        } finally {
            // Release lock
            delete_option($this->lock_option_key);
        }
    }

    public function cleanup_stuck_jobs() {
        global $wpdb;

        $timeout_threshold = date('Y-m-d H:i:s', time() - $this->processing_timeout);

        $stuck_jobs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE status = 'processing' 
            AND started_at < %s
            ORDER BY started_at ASC
        ", $timeout_threshold));

        foreach ($stuck_jobs as $job) {
            if ($job->retry_count < 2) {
                $wpdb->update($this->table_name, [
                    'status' => 'pending',
                    'started_at' => null,
                    'retry_count' => $job->retry_count + 1,
                    'last_attempt' => current_time('mysql'),
                    'error_message' => 'Timeout - Retry ' . ($job->retry_count + 1),
                    'processing_log' => $job->processing_log . "\n[RETRY] Reset at " . current_time('mysql')
                ], ['id' => $job->id]);

                error_log('🔄 [CLEANUP] Job #' . $job->id . ' reset for retry');
            } else {
                $wpdb->update($this->table_name, [
                    'status' => 'error',
                    'error_message' => 'Job stuck after 2 retries',
                    'updated_at' => current_time('mysql'),
                    'processing_log' => $job->processing_log . "\n[ERROR] Marked as stuck at " . current_time('mysql')
                ], ['id' => $job->id]);

                error_log('❌ [CLEANUP] Job #' . $job->id . ' marked as error');
            }
        }

        // Clear current job if it's stuck
        $current_job_id = get_option($this->current_job_option_key);
        if ($current_job_id) {
            $current_job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $current_job_id));
            if ($current_job && $current_job->status === 'processing' && strtotime($current_job->started_at) < time() - $this->processing_timeout) {
                delete_option($this->current_job_option_key);
                error_log('🔄 [CLEANUP] Cleared stuck current job #' . $current_job_id);
            }
        }
    }

    private function process_single_job($job_id) {
        global $wpdb;

        error_log('🎯 [WORKER] Starting processing for job #' . $job_id);

        // Load job row
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $job_id));
        if (!$job) {
            error_log('❌ [WORKER] Job #' . $job_id . ' not found');
            delete_option($this->current_job_option_key);
            return false;
        }

        try {
            // Environment setup
            set_time_limit(300);
            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '256M');

        //    $this->validate_job($job);
            
            
            // Call API
            error_log('📡 [WORKER] Starting job #' . $job_id);
            $start_time = microtime(true);
            


            $history_id = $job -> history_id;
            
            
            
            $history_manager = AI_Assistant_History_Manager::get_instance();            
            $history = $history_manager ->get_history_item($history_id);
            
            // Updateing history status
            error_log('📝 [WORKER] Updateing history to processing for job' . $job_id);
            $update_result = $history_manager->update_history(
                $history_id,
                'processing'
            );

            if ($update_result) {
                // موفق
                error_log('✅ [ACTION] Status updated successfully for ' . $history_id);
            } else {
                // ناموفق
                error_log('❌ [ACTION] Failed to update status for '  . $history_id);
                throw new Exception('Failed to update history ');
            }

            
            $user_id = $history-> user_id;
            $service_id = $history-> service_id;
            $userData = $history-> user_data;
            
            $decodedData = json_decode($userData, true); // true برای تبدیل به آرایه
            
            
            // استخراج داده‌ها
            $userInfo = $decodedData['userInfo'] ?? [];
            $serviceSelection = $decodedData['serviceSelection'] ?? []; 
            $discountInfo = $decodedData['discountInfo'] ?? [];
          
          
            if ($service_id === 'diet' ){
                
                    $serviceSelectionDietType = $serviceSelection['dietType'] ?? null;
                    
                    if ( $serviceSelectionDietType === 'with-specialist'   ){

                        error_log('📝  [DietType] serviceSelectionDietType :' . $serviceSelectionDietType);
                        
                        // استخراج داده‌های selectedSpecialist (اگر وجود دارد)
                        $selectedSpecialistId = null;
                        $selectedSpecialistName = null;
                        $selectedSpecialistSpecialty = null;
                        
                        if (isset($serviceSelection['selectedSpecialist']) && is_array($serviceSelection['selectedSpecialist'])) {
                            $selectedSpecialistId = $serviceSelection['selectedSpecialist']['id'] ?? null;
                            $selectedSpecialistName = $serviceSelection['selectedSpecialist']['name'] ?? null;
                            $selectedSpecialistSpecialty = $serviceSelection['selectedSpecialist']['specialty'] ?? null;
                            
                             error_log('📝 [DietType] $selectedSpecialistName :' . $selectedSpecialistName);
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
              
            
            $userInfoString = is_array($userInfo) ? json_encode($userInfo, JSON_UNESCAPED_UNICODE) : $userInfo;

            
            $prompt = $system_prompt . "\n\n" . $userInfoString;
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
            
    
            try {
                // 1. اعتبارسنجی اولیه (اطمینان از اینکه درخواست درست است، اعتبار کاربر و ...)
                $this->validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler);
    
                // 2. فراخوانی سرویس خارجی (DeepSeek یا هر API‌ای)
              //  $response = $this->call_deepseek_api($prompt);
                sleep(15);
                $response = '📡 [RESPONSE] Test response for job #' . $job_id;
    
    
                // 3. بررسی موفقیت پاسخ API
                if (!$response || (is_array($response) && isset($response['error']))) {
                    // اگر API پاسخ معتبری برنگردانده، خطا بده
                    $err = is_array($response) && isset($response['error']) ? $response['error'] : 'Empty or invalid API response';
                    throw new Exception("API call failed: " . $err);
                }
                
                
                $cleaned_response = $this->clean_api_response($response);

    
                // 4. شروع تراکنش دیتابیس
                $wpdb->query('START TRANSACTION');
    
                
                error_log('💰 [WORKER] Deducting credit for job #' . $job_id);
                
                $payment_handler = AI_Assistant_Payment_Handler::get_instance();
                $credit_success = $payment_handler->deduct_credit(
                    $user_id,
                    $final_price,
                    'استفاده از سرویس: ' . $service_name,
                    'job_' . $job_id
                );
    
                if ($credit_success === false || (is_array($credit_success) && isset($credit_success['error']))) {
                    $err = is_array($credit_success) && isset($credit_success['error']) ? $credit_success['error'] : 'Deduct credit failed';
                    throw new Exception("Payment deduction failed: " . $err);
                }                
                
                    
                // Updateing history
                error_log('📝 [WORKER] Updateing history for job #' . $job_id);
                // $history_manager = AI_Assistant_History_Manager::get_instance();
                $update_result = $history_manager->update_history(
                    $history_id,
                    'completed'    ,     // $service_id
                    $cleaned_response
                );
                
            if ($update_result) {
                // موفق
                error_log('✅ [ACTION] Status updated successfully for ' . $history_id);
            } else {
                // ناموفق
                error_log('❌ [ACTION] Failed to update status for '  . $history_id);
                throw new Exception('Failed to update history step 0');
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
                    $Consultant_Rec = $Nutrition_Consultant_Manager->submit_consultation_request($history_id, 6000);
    
                    if ($Consultant_Rec === false || (is_array($Consultant_Rec) && isset($Consultant_Rec['error']))) {
                        $err = is_array($Consultant_Rec) && isset($Consultant_Rec['error']) ? $Consultant_Rec['error'] : 'submit_consultation_request failed';
                        throw new Exception("Consultation request failed: " . $err);
                    }
                    
                    else if($Consultant_Rec)
                    
                    {
                        // Updateing history status
                        error_log('📝 [WORKER] Updateing history for job #' . $job_id);
                       // $history_manager = AI_Assistant_History_Manager::get_instance();
                        $history_success = $history_manager->update_history(
                            $history_id,
                            'consultant_queue'
                        );
                        
                        if ($history_success === false || empty($history_success)) {
                            throw new Exception('Failed to update history step 2');
                        }                        
                        
                        
                    }
                    
                    
                }
                
                
                // 8. همه چی موفق بود -> commit
                $wpdb->query('COMMIT');
                
                    
                $api_time = round(microtime(true) - $start_time, 2);
    
    
    
                // Mark done
                $update_success = $wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'done',
                        'updated_at' => current_time('mysql'),
                        'processing_log' => $job->processing_log . "\n[SUCCESS] Completed in " . $api_time . "s at " . current_time('mysql')
                    ],
                    ['id' => $job_id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
    
                if ($update_success) {
                    error_log('✅ [WORKER] Job #' . $job_id . ' completed successfully in ' . $api_time . 's');
                    // Clear current job
                    delete_option($this->current_job_option_key);
                    
                    // Trigger next job processing
                    $this->maybe_process_jobs();
                    
                    return true;
                } else {
                    throw new Exception('Failed to update job status');
                }                
    
    
            } catch (Exception $e) {
                // هر خطایی رخ داد، rollback و لاگ
                try {
                    $wpdb->query('ROLLBACK');
                } catch (Exception $rollbackEx) {
                    // اگر rollback هم خطا داد، لاگش کن
                    error_log('Rollback failed: ' . $rollbackEx->getMessage());
                }
    
                $error_message = $e->getMessage();
                error_log('❌ [WORKER] Job #' . $job_id . ' failed: ' . $error_message);
    
                $wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'error',
                        'error_message' => substr($error_message, 0, 500),
                        'updated_at' => current_time('mysql'),
                        'processing_log' => $job->processing_log . "\n[ERROR] " . $error_message . " at " . current_time('mysql')
                    ],
                    ['id' => $job_id],
                    ['%s', '%s', '%s', '%s'],
                    ['%d']
                );
                
                
                // Updateing history status
                error_log('📝 [WORKER] Updateing history for job #' . $job_id);
               // $history_manager = AI_Assistant_History_Manager::get_instance();
                $history_success = $history_manager->update_history(
                    $history_id,
                    'error'
                );                
    
                // Clear current job on error
                delete_option($this->current_job_option_key);
                
                // Trigger next job processing even on error
                $this->maybe_process_jobs();
                
                return false;
                // برگردوندن خطا به فراخواننده — (می‌تونی این شیوه را سفارشی کنی)
                return [
                    'success' => false,
                    'message' => 'Processing failed: ' . $e->getMessage(),
                    'exception' => $e->getMessage(),
                ];
            }
            



        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log('❌ [WORKER] Job #' . $job_id . ' failed: ' . $error_message);

            $wpdb->update(
                $this->table_name,
                [
                    'status' => 'error',
                    'error_message' => substr($error_message, 0, 500),
                    'updated_at' => current_time('mysql'),
                    'processing_log' => $job->processing_log . "\n[ERROR] " . $error_message . " at " . current_time('mysql')
                ],
                ['id' => $job_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            // Clear current job on error
            delete_option($this->current_job_option_key);
            
            // Trigger next job processing even on error
            $this->maybe_process_jobs();
            
            return false;
        }
    }

    public function get_queue_stats() {
        global $wpdb;

        return [
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'"),
            'processing' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'"),
            'done' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'done'"),
            'error' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'error'"),
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"),
        ];
    }

    public function get_job_status($job_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $job_id));
    }

    public function reset_failed_job($job_id) {
        global $wpdb;
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'pending',
                'error_message' => null,
                'started_at' => null,
                'retry_count' => 0
            ],
            ['id' => $job_id, 'status' => 'error']
        );
    }

    // ---------- API call & helpers ----------
    private function call_deepseek_api($prompt) {
        $api_key = DEEPSEEK_API_KEY;
        $api_url = 'https://api.deepseek.com/v1/chat/completions';

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
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
            'timeout' => 180,
            'httpversion' => '1.1'
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            throw new Exception('خطا در ارتباط با سرور DeepSeek: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            throw new Exception('خطا از سمت DeepSeek API. کد وضعیت: ' . $response_code);
        }

        $decoded_body = json_decode($body, true);

        if (empty($decoded_body['choices'][0]['message']['content'])) {
            throw new Exception('پاسخ نامعتبر از API دریافت شد');
        }

        return $decoded_body['choices'][0]['message']['content'];
    }

    private function clean_api_response($response_content) {
        $patterns = [
            '/^```json\s*/',
            '/\s*```$/',
            '/^```\s*/',
            '/\s*```$/',
        ];

        $cleaned_response = preg_replace($patterns, '', $response_content);
        $cleaned_response = trim($cleaned_response);
        $cleaned_response = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned_response);

        return $cleaned_response;
    }

    private function validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            throw new Exception('کاربر یافت نشد');
        }          
        
        if (empty($prompt) || empty($service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        if (!$payment_handler->has_enough_credit($user_id, $final_price)) {
            throw new Exception('موجودی حساب شما کافی نیست');
        }
        
      
    }
}

// initialize
AI_Job_Queue::get_instance();