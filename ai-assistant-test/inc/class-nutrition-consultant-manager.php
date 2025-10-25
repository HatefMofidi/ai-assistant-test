<?php
// /inc/class-nutrition-consultant-manager.php

class AI_Assistant_Nutrition_Consultant_Manager {
    private static $instance;
    private $consultation_db;
    private $history_manager;
    private $notification_manager;
    private $logger;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
        $this->history_manager = AI_Assistant_History_Manager::get_instance();
        $this->notification_manager = AI_Assistant_Notification_Manager::get_instance();
        $this->logger = AI_Assistant_Logger::get_instance();
        
        // ثبت هوک‌های مدیریت درخواست‌ها
        add_action('wp_ajax_submit_consultation_review', [$this, 'handle_consultation_review']);
        add_action('wp_ajax_get_consultation_data', [$this, 'get_consultation_data']);
    }

    /**
     * ثبت درخواست بازبینی جدید
     */
    public function submit_consultation_request($service_history_id, $consultation_price = 0) {
        // دریافت اطلاعات تاریخچه
        $history_item = $this->history_manager->get_history_item($service_history_id);
        if (!$history_item) {
            return new WP_Error('invalid_history', 'آیتم تاریخچه یافت نشد.');
        }

        // بررسی آیا سرویس مربوط به رژیم غذایی است
        // if (!$this->is_diet_service($history_item->service_id)) {
        //     return new WP_Error('not_diet_service', 'این سرویس مربوط به رژیم غذایی نیست.');
        // }

        // دریافت مشاور (فعلاً اولین مشاور)
        $consultant_id = $this->get_available_consultant();
        if (!$consultant_id) {
            return new WP_Error('no_consultant', 'هیچ مشاور فعالی یافت نشد.');
        }
        
        
        
         $this->logger->log('consultation_request_data', [
               'user_id' => $history_item->user_id,
                'consultant_id' => $consultant_id,
                'service_history_id' => $service_history_id,
                'consultation_price' => $consultation_price,
                'deadline' => date('Y-m-d H:i:s', strtotime('+1 days'))
            ]);

        // ثبت درخواست
        $request_data = [
            'user_id' => $history_item->user_id,
            'consultant_id' => $consultant_id,
            'service_history_id' => $service_history_id,
            'consultation_price' => $consultation_price,
            'deadline' => date('Y-m-d H:i:s', strtotime('+1 days'))
        ];

        $request_id = $this->consultation_db->add_consultation_request($request_data);
        
        if ($request_id) {
            // ارسال ایمیل به مشاور
            $this->notification_manager->send_consultation_request($consultant_id, $request_id);
            
            return $request_id;
        }

        return new WP_Error('db_error', 'خطا در ثبت درخواست بازبینی.');
    }

    /**
     * دریافت اولین مشاور فعال
     */
    private function get_available_consultant() {
        $consultants = get_users([
            'role' => 'nutrition_consultant',
            'number' => 1,
            'fields' => 'ID'
        ]);
        
        return !empty($consultants) ? $consultants[0] : false;
    }

    /**
     * بررسی آیا سرویس مربوط به رژیم غذایی است
     */
    private function is_diet_service($service_id) {
        $diet_services = ['diet_plan', 'nutrition_plan', 'diet_service']; // شناسه‌های سرویس رژیم
        return in_array($service_id, $diet_services);
    }

    /**
     * مدیریت ارسال بازبینی مشاور (AJAX)
     */
    public function handle_consultation_review() {
        
                error_log('[Diet Consultation] $contract $contract :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::');
        // بررسی nonce و دسترسی
        if (!wp_verify_nonce($_POST['nonce'], 'consultation_review_nonce') || 
            !current_user_can('nutrition_consultant')) {
            wp_die('دسترسی غیرمجاز');
        }

        $request_id = intval($_POST['request_id']);
        $consultant_id = get_current_user_id();
        $action = sanitize_text_field($_POST['action_type']);
        $consultant_notes = sanitize_textarea_field($_POST['consultant_notes'] ?? '');
        $final_diet_data = wp_unslash($_POST['final_diet_data'] ?? ''); // داده‌های JSON

        // بررسی مالکیت درخواست
        $request = $this->consultation_db->get_consultation_request($request_id);
        if (!$request || $request->consultant_id != $consultant_id) {
            wp_send_json_error('درخواست یافت نشد یا دسترسی ندارید.');
        }

        // آماده‌سازی داده‌های بروزرسانی
        $update_data = [];
        
        if ($action === 'save_draft') {
            $update_data = [
                'status' => 'under_review',
                'consultant_notes' => $consultant_notes,
                'final_diet_data' => $final_diet_data
            ];
        } elseif ($action === 'approve') {
            $update_data = [
                'status' => 'approved',
                'consultant_notes' => $consultant_notes,
                'final_diet_data' => $final_diet_data
            ];
        } elseif ($action === 'reject') {
            $update_data = [
                'status' => 'rejected',
                'consultant_notes' => $consultant_notes
            ];
        }

        // بروزرسانی درخواست
        $result = $this->consultation_db->update_consultation_request($request_id, $update_data);
        
        if ($result) {
            // اگر تایید شد، اطلاع‌رسانی به کاربر
            if ($action === 'approve') {
                
                $commission = $this->consultation_db->calculate_commission($request_id);
                $this->notification_manager->send_consultation_result($request->user_id, $request_id);
 
            }
            
            wp_send_json_success('تغییرات با موفقیت ذخیره شد.');
        } else {
            wp_send_json_error('خطا در ذخیره تغییرات.');
        }
    }

    /**
     * دریافت داده‌های درخواست برای ویرایش (AJAX)
     */
    public function get_consultation_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'consultation_review_nonce') || 
            !current_user_can('nutrition_consultant')) {
            wp_die('دسترسی غیرمجاز');
        }

        $request_id = intval($_POST['request_id']);
        $consultant_id = get_current_user_id();

        $request = $this->consultation_db->get_consultation_request($request_id);
        if (!$request || $request->consultant_id != $consultant_id) {
            wp_send_json_error('درخواست یافت نشد.');
        }

        // دریافت داده‌های اصلی از تاریخچه
        $history_item = $this->history_manager->get_history_item($request->service_history_id);
        
        $response_data = [
            'original_data' => [
                'user_data' => $history_item->user_data,
                'ai_response' => $history_item->response,
                'service_name' => $history_item->service_name
            ],
            'consultation_data' => [
                'consultant_notes' => $request->consultant_notes,
                'final_diet_data' => $request->final_diet_data,
                'status' => $request->status
            ]
        ];

        wp_send_json_success($response_data);
    }

    /**
     * بررسی وضعیت بازبینی برای یک آیتم تاریخچه
     */
    public function get_consultation_status($service_history_id) {
        return $this->consultation_db->get_request_by_history_id($service_history_id);
    }
}

// راه‌اندازی کلاس
AI_Assistant_Nutrition_Consultant_Manager::get_instance();