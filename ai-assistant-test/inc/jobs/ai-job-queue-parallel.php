<?php
if (!defined('ABSPATH')) exit;

class AI_Job_Queue {
    private static $instance = null;
    private $table_name;
    private $max_concurrent_jobs = 3;
    private $processing_timeout = 600;
    private $logger;
    private $rest_namespace = 'ai-job-queue/v1';
    private $secret_option_key = 'ai_job_queue_secret_key';

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'job_queue';
        $this->logger = AI_Assistant_Logger::get_instance();

        add_action('init', [$this, 'maybe_create_table']);
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);

        if (!wp_next_scheduled('ai_process_job_queue')) {
            wp_schedule_event(time(), 'every_minute', 'ai_process_job_queue');
        }
        add_action('ai_process_job_queue', [$this, 'process_pending_jobs']);

        if (!wp_next_scheduled('ai_cleanup_stuck_jobs')) {
            wp_schedule_event(time(), 'every_5_minutes', 'ai_cleanup_stuck_jobs');
        }
        add_action('ai_cleanup_stuck_jobs', [$this, 'cleanup_stuck_jobs']);

        // REST route for worker processes
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // ensure secret exists
        if (!get_option($this->secret_option_key)) {
            update_option($this->secret_option_key, wp_generate_password(32, false, false));
        }
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
                service_id VARCHAR(50) NOT NULL,
                prompt LONGTEXT NOT NULL,
                final_price DECIMAL(10,2) DEFAULT 0,
                user_data LONGTEXT NULL,
                status ENUM('pending','processing','done','error') DEFAULT 'pending',
                error_message TEXT NULL,
                started_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                retry_count INT DEFAULT 0,
                last_attempt DATETIME NULL,
                processing_log LONGTEXT NULL,
                PRIMARY KEY (id),
                INDEX (status),
                INDEX (started_at),
                INDEX (service_id),
                INDEX (created_at)
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

    public function enqueue_job($user_id, $service_id, $prompt, $final_price, $user_data = []) {
        global $wpdb;

        $inserted = $wpdb->insert($this->table_name, [
            'user_id'     => $user_id,
            'service_id'  => $service_id,
            'prompt'      => $prompt,
            'final_price' => $final_price,
            'user_data'   => maybe_serialize($user_data),
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

        // try to spawn processing immediately (async)
        $this->maybe_spawn_processing();

        return $job_id;
    }

    private function maybe_spawn_processing() {
        // trigger a processing cycle quickly
        if (!wp_next_scheduled('ai_process_job_queue')) {
            wp_schedule_single_event(time()+1, 'ai_process_job_queue');
        } else {
            // still schedule a single event to speed up reaction
            wp_schedule_single_event(time()+1, 'ai_process_job_queue');
        }
        spawn_cron();
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
    }

    public function process_pending_jobs() {
        global $wpdb;

        error_log('🔄 [MASTER] Processing cycle started at: ' . current_time('mysql'));

        $this->cleanup_stuck_jobs();

        $processing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'");
        error_log('📊 [MASTER] Currently processing: ' . $processing_count . '/' . $this->max_concurrent_jobs);

        if ($processing_count >= $this->max_concurrent_jobs) {
            error_log('⏸️ [MASTER] Max concurrent jobs reached');
            return;
        }

        $available_slots = $this->max_concurrent_jobs - $processing_count;

        // get candidate pending jobs (IDs only)
        $pending_ids = $wpdb->get_col($wpdb->prepare("
            SELECT id FROM {$this->table_name}
            WHERE status = 'pending'
            ORDER BY id ASC
            LIMIT %d
        ", $available_slots));

        if (empty($pending_ids)) {
            error_log('📭 [MASTER] No pending jobs found');
            return;
        }

        $claimed = [];
        // Try to claim atomically each job (UPDATE ... WHERE id = X AND status = 'pending')
        foreach ($pending_ids as $id) {
            $now = current_time('mysql');
            $updated = $wpdb->update(
                $this->table_name,
                [
                    'status' => 'processing',
                    'started_at' => $now,
                    'last_attempt' => $now,
                    'processing_log' => $wpdb->get_var($wpdb->prepare("SELECT COALESCE(processing_log, '') FROM {$this->table_name} WHERE id = %d", $id)) . "\n[MASTER] Claimed at " . $now
                ],
                ['id' => $id, 'status' => 'pending'],
                ['%s', '%s', '%s'],
                ['%d', '%s']
            );

            // $updated returns number of rows affected; if 1 -> we claimed it
            if ($updated && $wpdb->rows_affected > 0) {
                $claimed[] = $id;
            }
        }

        if (empty($claimed)) {
            error_log('⚠️ [MASTER] No jobs could be claimed (race conditions?)');
            return;
        }

        error_log('🚀 [MASTER] Claimed ' . count($claimed) . ' jobs: ' . implode(',', $claimed));

        // For each claimed job, send an async HTTP request to our REST endpoint to process it.
        $secret = get_option($this->secret_option_key);
        foreach ($claimed as $job_id) {
            $this->fire_worker_request($job_id, $secret);
        }
    }

    private function fire_worker_request($job_id, $secret) {
        $url = rest_url($this->rest_namespace . '/process');

        $args = [
            'body' => json_encode(['job_id' => (int)$job_id, 'secret' => $secret]),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            // Non-blocking: hope server handles it in separate worker/process
            'timeout' => 0.01,
            'blocking' => false,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
        ];

        $result = wp_remote_post($url, $args);

        if (is_wp_error($result)) {
            error_log('⚠️ [MASTER] wp_remote_post failed for job #' . $job_id . ': ' . $result->get_error_message());
            // fallback: schedule via WP-Cron single event (guaranteed)
            wp_schedule_single_event(time()+2, 'ai_process_single_job_now', [$job_id]);
            spawn_cron();
            return false;
        }

        // success (or at least request queued)
        error_log('⏰ [MASTER] Fired worker request for job #' . $job_id);
        return true;
    }

    public function register_rest_routes() {
        register_rest_route($this->rest_namespace, '/process', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_process_job'],
            'permission_callback' => '__return_true', // we'll check secret manually
        ]);
    }

    public function rest_process_job(\WP_REST_Request $request) {
        $params = json_decode($request->get_body(), true);
        if (empty($params['job_id']) || empty($params['secret'])) {
            return new \WP_REST_Response(['error' => 'invalid_request'], 400);
        }

        $secret = get_option($this->secret_option_key);
        if (!hash_equals($secret, $params['secret'])) {
            return new \WP_REST_Response(['error' => 'forbidden'], 403);
        }

        $job_id = (int)$params['job_id'];

        // Process the job synchronously in this REST request
        // IMPORTANT: We do not re-claim here; claiming already happened in master.
        $ok = $this->process_single_job_direct_internal($job_id);

        if ($ok) {
            return new \WP_REST_Response(['status' => 'ok'], 200);
        } else {
            return new \WP_REST_Response(['status' => 'failed'], 500);
        }
    }

    /**
     * Public method kept for compatibility with existing cron action
     * It will try to run the internal processor directly if called via cron
     */
    public function process_single_job_direct($job_id) {
        // This was previously triggered via cron with job_id param.
        return $this->process_single_job_direct_internal($job_id);
    }

    /**
     * Internal worker logic (same as your previous code but safe for REST worker)
     */
    private function process_single_job_direct_internal($job_id) {
        global $wpdb;

        error_log('🎯 [WORKER] Starting processing for job #' . $job_id);

        // load job row
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $job_id));
        if (!$job) {
            error_log('❌ [WORKER] Job #' . $job_id . ' not found');
            return false;
        }

        // if job not processing (maybe something happened), try to set processing
        if ($job->status !== 'processing') {
            $now = current_time('mysql');
            $updated = $wpdb->update($this->table_name, [
                'status' => 'processing',
                'started_at' => $now,
                'last_attempt' => $now,
                'processing_log' => $job->processing_log . "\n[WORKER] Force-claimed at " . $now
            ], ['id' => $job_id, 'status' => $job->status]);

            if (!$updated && $wpdb->rows_affected === 0) {
                error_log('❌ [WORKER] Failed to claim job #' . $job_id);
                return false;
            }

            // reload
            $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $job_id));
        }

        try {
            // environment
            set_time_limit(300);
            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '256M');

            $this->validate_job($job);

            // simulate / call API
            error_log('📡 [WORKER] Calling API for job #' . $job_id);
            $start_time = microtime(true);

            // you had sleep(30) previously; keep a short sleep for testing if needed
          //   sleep(5);
          //  $response = "Test response for job #" . $job->id;
            $response = $this->call_deepseek_api($job->prompt);

            $api_time = round(microtime(true) - $start_time, 2);

            if (!$response || (is_array($response) && isset($response['error']))) {
                $err = is_array($response) && isset($response['error']) ? $response['error'] : 'Empty or invalid API response';
                throw new Exception("API call failed: " . $err);
            }

            $cleaned_response = $this->clean_api_response($response);

            // begin transaction
            $wpdb->query('START TRANSACTION');

            // payment
            error_log('💰 [WORKER] Deducting credit for job #' . $job_id);
            $payment_handler = AI_Assistant_Payment_Handler::get_instance();
            $credit_success = $payment_handler->deduct_credit(
                $job->user_id,
                $job->final_price,
                'استفاده از سرویس: ' . $job->service_id,
                'job_' . $job_id
            );

            if ($credit_success === false || (is_array($credit_success) && isset($credit_success['error']))) {
                $err = is_array($credit_success) && isset($credit_success['error']) ? $credit_success['error'] : 'Deduct credit failed';
                throw new Exception("Payment deduction failed: " . $err);
            }

            // save history
            error_log('📝 [WORKER] Saving history for job #' . $job_id);
            $history_manager = AI_Assistant_History_Manager::get_instance();
            $history_success = $history_manager->save_history(
                $job->user_id,
                $job->service_id,
                $job->service_id,
                maybe_unserialize($job->user_data),
                $response
            );

            if ($history_success === false || empty($history_success)) {
                throw new Exception('Failed to save history');
            }

            $wpdb->query('COMMIT');

            // mark done
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
                return true;
            } else {
                throw new Exception('Failed to update job status');
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

            return false;
        }
    }

    private function validate_job($job) {
        if (empty($job->prompt) || empty($job->service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        $user = get_user_by('ID', $job->user_id);
        if (!$user) {
            throw new Exception('کاربر یافت نشد');
        }

        $payment_handler = AI_Assistant_Payment_Handler::get_instance();
        $has_credit = $payment_handler->has_enough_credit($job->user_id, $job->final_price);

        if (is_wp_error($has_credit) || !$has_credit) {
            throw new Exception('موجودی حساب کافی نیست');
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

    // ---------- API call & helpers (unchanged / adapted) ----------
    private function call_deepseek_api($prompt) {
      //  $prompt = 'خیلی خلاصه و در یک خط بگو که برای گرفتن یک رژیم غذایی چه نکته طلایی باید رعایت کنم';
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
            'timeout' => 180 ,
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