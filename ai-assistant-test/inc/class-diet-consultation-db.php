<?php
// /inc/class-diet-consultation-db.php

class AI_Assistant_Diet_Consultation_DB {
    private static $instance;
    private $table_name;
    private $table_created = false;

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'diet_consultation_requests';
    }

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ایجاد جدول درخواست‌های بازبینی
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            service_history_id bigint(20) UNSIGNED NOT NULL,
            status enum('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            consultation_price int(11) NOT NULL DEFAULT 0,
            deadline datetime NOT NULL,
            consultant_notes longtext NULL,
            final_diet_data longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at datetime NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY consultant_id (consultant_id),
            KEY service_history_id (service_history_id),
            KEY status (status),
            KEY deadline (deadline)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // لاگ برای اشکال‌زدایی
        error_log('[Diet Consultation] Table creation result: ' . print_r($result, true));
        
        return $result;
    }

    /**
     * بررسی وجود جدول و ایجاد در صورت عدم وجود
     */
    private function ensure_table_exists() {
        if ($this->table_created) {
            return true;
        }

        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            $this->create_table();
            // بررسی مجدد
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
            
            if (!$table_exists) {
                error_log('[Diet Consultation] Failed to create table: ' . $this->table_name);
                return false;
            }
        }
        
        $this->table_created = true;
        return true;
    }

    /**
     * ثبت درخواست بازبینی جدید
     */
    public function add_consultation_request($data) {
        if (!$this->ensure_table_exists()) {
            error_log('[Diet Consultation] Table does not exist');
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'consultant_id' => 0,
            'service_history_id' => 0,
            'consultation_price' => 0,
            'deadline' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // اعتبارسنجی داده‌های ضروری
        if (!$data['user_id'] || !$data['consultant_id'] || !$data['service_history_id']) {
            error_log('[Diet Consultation] Missing required fields: ' . print_r($data, true));
            return false;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            ['%d', '%d', '%d', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * به‌روزرسانی درخواست بازبینی
     */
    public function update_consultation_request($request_id, $data) {
        if (!$this->ensure_table_exists()) {
            return false;
        }

        global $wpdb;
        
        // اضافه کردن زمان بررسی اگر وضعیت تغییر کرده باشد
        if (isset($data['status']) && in_array($data['status'], ['approved', 'rejected'])) {
            $data['reviewed_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $request_id],
            ['%s', '%s', '%s', '%s'], // status, consultant_notes, final_diet_data, reviewed_at
            ['%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Update failed: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }

    /**
     * دریافت درخواست بازبینی بر اساس ID
     */
    public function get_consultation_request($request_id) {
        if (!$this->ensure_table_exists()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $request_id)
        );
    }

    /**
     * دریافت درخواست‌های بازبینی یک کاربر
     */
    public function get_user_consultation_requests($user_id) {
        if (!$this->ensure_table_exists()) {
            return [];
        }

        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM {$this->table_name} 
                WHERE user_id = %d 
                ORDER BY created_at DESC
            ", $user_id)
        );
    }

    /**
     * دریافت درخواست‌های بازبینی یک مشاور
     */
    public function get_consultant_requests($consultant_id, $status = '') {
        if (!$this->ensure_table_exists()) {
            return [];
        }

        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name} WHERE consultant_id = %d";
        $params = [$consultant_id];
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY 
            CASE 
                WHEN status = 'pending' THEN 1
                WHEN status = 'under_review' THEN 2
                WHEN status = 'approved' THEN 3
                WHEN status = 'rejected' THEN 4
                ELSE 5
            END,
            deadline ASC,
            created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * دریافت درخواست بر اساس service_history_id
     */
    public function get_request_by_history_id($service_history_id) {
        if (!$this->ensure_table_exists()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM {$this->table_name} 
                WHERE service_history_id = %d
            ", $service_history_id)
        );
    }

    /**
     * دریافت تعداد درخواست‌های هر مشاور بر اساس وضعیت
     */
    public function get_consultant_request_counts($consultant_id) {
        if (!$this->ensure_table_exists()) {
            return [];
        }

        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare("
                SELECT status, COUNT(*) as count 
                FROM {$this->table_name} 
                WHERE consultant_id = %d 
                GROUP BY status
            ", $consultant_id),
            ARRAY_A
        );
        
        $counts = [
            'pending' => 0,
            'under_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
            $counts['total'] += (int)$row['count'];
        }
        
        return $counts;
    }
}