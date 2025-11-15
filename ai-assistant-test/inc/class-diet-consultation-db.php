<?php
// /inc/class-diet-consultation-db.php

class AI_Assistant_Diet_Consultation_DB {
    private static $instance;
    private $table_name;
    private $consultants_table;
    private $contracts_table;
    private $commissions_table;
    private $payouts_table;
    private $tables_created = false;

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'diet_consultation_requests';
        $this->consultants_table = $wpdb->prefix . 'diet_consultants';
        $this->contracts_table = $wpdb->prefix . 'consultant_contracts';
        $this->commissions_table = $wpdb->prefix . 'consultant_commissions';
        $this->payouts_table = $wpdb->prefix . 'consultant_payouts';
    }

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ایجاد تمام جداول مورد نیاز
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول درخواست‌های بازبینی (جدول اصلی موجود)
        $sql1 = "CREATE TABLE {$this->table_name} (
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
        
        // جدول مشاوران
        $sql2 = "CREATE TABLE {$this->consultants_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NULL,
            iban varchar(26) NULL,
            status enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY email (email),
            KEY status (status)
        ) {$charset_collate};";
        
        // جدول قراردادهای مشاوران
        $sql3 = "CREATE TABLE {$this->contracts_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            commission_type enum('percent','fixed') NOT NULL,
            commission_value decimal(10,2) NOT NULL,
            full_payment_hours int NOT NULL DEFAULT 48,
            delay_penalty_factor decimal(4,2) NOT NULL DEFAULT 0.50,
            active_from datetime NOT NULL,
            active_to datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY consultant_id (consultant_id),
            KEY active_from (active_from),
            KEY active_to (active_to)
        ) {$charset_collate};";
        
        // جدول کمیسیون‌های مشاوران
        $sql4 = "CREATE TABLE {$this->commissions_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id bigint(20) UNSIGNED NOT NULL,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            base_amount decimal(10,2) NOT NULL,
            commission_type enum('percent','fixed') NOT NULL,
            commission_value decimal(10,2) NOT NULL,
            approved_at datetime NULL,
            generated_at datetime NOT NULL,
            delay_hours decimal(6,2) NULL,
            penalty_multiplier decimal(6,2) NULL,
            final_commission decimal(10,2) NOT NULL,
            status enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
            payout_id bigint(20) UNSIGNED NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY request_id (request_id),
            KEY consultant_id (consultant_id),
            KEY payout_id (payout_id),
            KEY status (status),
            KEY approved_at (approved_at)
        ) {$charset_collate};";
        
        // جدول پرداخت‌ها
        $sql5 = "CREATE TABLE {$this->payouts_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            consultant_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(12,2) NOT NULL,
            period_start date NOT NULL,
            period_end date NOT NULL,
            payment_method enum('manual','api','bank_transfer') NOT NULL DEFAULT 'manual',
            reference_code varchar(100) NULL,
            status enum('pending','done','failed') NOT NULL DEFAULT 'pending',
            paid_at datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            notes longtext NULL,
            commission_ids TEXT NULL ,
            PRIMARY KEY (id),
            KEY consultant_id (consultant_id),
            KEY period_start (period_start),
            KEY period_end (period_end),
            KEY status (status)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $results = [];
        $results[] = dbDelta($sql1);
        $results[] = dbDelta($sql2);
        $results[] = dbDelta($sql3);
        $results[] = dbDelta($sql4);
        $results[] = dbDelta($sql5);
        
        // لاگ برای اشکال‌زدایی
        error_log('[Diet Consultation] Tables creation results: ' . print_r($results, true));
        
        return $results;
    }

    /**
     * بررسی وجود جداول و ایجاد در صورت عدم وجود
     */
    private function ensure_tables_exist() {
        if ($this->tables_created) {
            return true;
        }

        global $wpdb;
        
        $tables = [
            $this->table_name,
            $this->consultants_table,
            $this->contracts_table,
            $this->commissions_table,
            $this->payouts_table
        ];
        
        $all_tables_exist = true;
        
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
            if (!$table_exists) {
                $all_tables_exist = false;
                break;
            }
        }
        
        if (!$all_tables_exist) {
            $this->create_tables();
            
            // بررسی مجدد
            $all_tables_exist = true;
            foreach ($tables as $table) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
                if (!$table_exists) {
                    $all_tables_exist = false;
                    error_log('[Diet Consultation] Failed to create table: ' . $table);
                    break;
                }
            }
        }
        
        $this->tables_created = $all_tables_exist;
        return $all_tables_exist;
    }

    // متدهای موجود برای جدول درخواست‌ها (همانند قبل)
    public function add_consultation_request($data) {
        if (!$this->ensure_tables_exist()) {
            error_log('[Diet Consultation] Tables do not exist');
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

    // سایر متدهای موجود برای جدول درخواست‌ها (همانند قبل)
    public function update_consultation_request($request_id, $data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        if (isset($data['status']) && in_array($data['status'], ['approved', 'rejected'])) {
            $data['reviewed_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $request_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Update failed: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }

    public function get_consultation_request($request_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $request_id)
        );
    }
    
    public function get_consultation_by_user_id($user_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->consultants_table} WHERE user_id = %d", $user_id)
        );
    }    

    public function get_user_consultation_requests($user_id) {
        if (!$this->ensure_tables_exist()) {
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

    public function get_consultant_requests($consultant_id, $status = '') {
        if (!$this->ensure_tables_exist()) {
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

    public function get_request_by_history_id($service_history_id) {
        if (!$this->ensure_tables_exist()) {
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

    public function get_active_consultants() {
        if (!$this->ensure_tables_exist()) {
            return array();
        }
        
        global $wpdb;
        $consultants = $wpdb->get_results("
            SELECT c.id, c.name, '' as specialty, 
            ct.commission_value as consultation_price,
            ct.commission_type
            FROM {$this->consultants_table} c
            LEFT JOIN {$this->contracts_table} ct 
            ON c.id = ct.consultant_id
            AND ct.active_from <= NOW()
            AND (ct.active_to IS NULL OR ct.active_to >= NOW())
            AND ct.commission_type = 'fixed'  -- فقط قراردادهای fixed
            WHERE c.status = 'active'
            ORDER BY ct.active_from DESC, c.name ASC
        ");
        
        foreach ($consultants as $consultant) {
            // اگه قرارداد نداشته باشه یا commission_value صفر باشه
            if (!$consultant->consultation_price || $consultant->consultation_price <= 0) {
                error_log("Consultant {$consultant->id} has no valid contract!");
                $consultant->consultation_price = 150000; // یه قیمت پیش‌فرض معقول
            }
        }
        
        return $consultants;
    }

    
    /**
     * دریافت قیمت پایه سرویس رژیم غذایی
     */
    public function get_diet_service_base_price() {
        $service_db = AI_Assistant_Service_DB::get_instance();
        $service = $service_db->get_service('diet');
        
        if ($service && isset($service['price'])) {
            return (int)$service['price'];
        }
        
        return 0; // قیمت پیش‌فرض در صورت عدم یافتن
    }

    public function get_consultant_request_counts($consultant_id) {
        if (!$this->ensure_tables_exist()) {
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

    // ========== متدهای جدید برای مدیریت مشاوران ==========

    /**
     * افزودن مشاور جدید
     */
    public function add_consultant($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'name' => '',
            'email' => '',
            'phone' => '',
            'iban' => '',
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['user_id'] || !$data['name'] || !$data['email']) {
            error_log('[Diet Consultation] Missing required consultant fields');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->consultants_table,
            $data,
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Consultant insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * به‌روزرسانی اطلاعات مشاور
     */
    public function update_consultant($consultant_id, $data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $result = $wpdb->update(
            $this->consultants_table,
            $data,
            ['id' => $consultant_id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Consultant update failed: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }

    /**
     * دریافت اطلاعات مشاور
     */
    public function get_consultant($consultant_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->consultants_table} WHERE id = %d", $consultant_id)
        );
    }

    /**
     * دریافت مشاور بر اساس user_id
     */
    public function get_consultant_by_user_id($user_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->consultants_table} WHERE user_id = %d", $user_id)
        );
    }

    /**
     * دریافت لیست تمام مشاوران
     */
    public function get_consultants($status = '') {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $query = "SELECT * FROM {$this->consultants_table}";
        $params = [];
        
        if (!empty($status)) {
            $query .= " WHERE status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY name ASC";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            return $wpdb->get_results($query);
        }
    }

    // ========== متدهای جدید برای مدیریت قراردادها ==========

    /**
     * افزودن قرارداد جدید
     */
    public function add_contract($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'consultant_id' => 0,
            'commission_type' => 'percent',
            'commission_value' => 0,
            'full_payment_hours' => 48,
            'delay_penalty_factor' => 0.50,
            'active_from' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['consultant_id']) {
            error_log('[Diet Consultation] Missing consultant_id for contract');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->contracts_table,
            $data,
            ['%d', '%s', '%f', '%d', '%f', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Contract insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * دریافت قرارداد فعال یک مشاور
     */
    public function get_active_contract($consultant_id) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        $now = current_time('mysql');
        
        return $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM {$this->contracts_table} 
                WHERE consultant_id = %d 
                AND active_from <= %s 
                AND (active_to IS NULL OR active_to >= %s)
                ORDER BY active_from DESC 
                LIMIT 1
            ", $consultant_id, $now, $now)
        );
    }
    // ========== متدهای جدید برای مدیریت کمیسیون‌ها ==========
    
    
    
    
    /* ============================================================
       🔸  کمیسیون مشاور
    ============================================================ */
    public function calculate_commission($request_id) {
        global $wpdb;
        $plan = $this->get_consultation_request($request_id);
        if (! $plan || $plan->status !== 'approved') return false;

        $contract = $this->get_active_contract($plan->consultant_id);
        
        if (! $contract) return false;

        $do_time = $this->calculate_delay_hours($plan->created_at, $plan->reviewed_at);
        $delay_hours = ($do_time < $contract->full_payment_hours) ? 0 : ($do_time - $contract->full_payment_hours);
        $penalty_multiplier = $this->calculate_penalty($delay_hours, $contract->delay_penalty_factor);

        // $base_commission = ($contract->commission_type === 'percent')
        //     ? $plan->consultation_price * ($contract->commission_value / 100)
        //     : $contract->commission_value;
        
        $base_commission = $contract->commission_value;

        $final_commission = $base_commission * $penalty_multiplier;

        $wpdb->insert($this->commissions_table, [ 
            'request_id' => $request_id,
            'consultant_id' => $plan->consultant_id,
            'base_amount' => $plan->consultation_price,
            'commission_type' => $contract->commission_type,
            'commission_value' => $contract->commission_value,
            'delay_hours' => $delay_hours,
            'penalty_multiplier' => $penalty_multiplier,
            'final_commission' => $final_commission,
            'status' => 'pending',
            'generated_at' => $plan->created_at,
            'approved_at' => $plan->reviewed_at,
            'created_at' => current_time('mysql')
        ]);

        return $final_commission;
    }
    
    
    

    /**
     * افزودن رکورد کمیسیون
     */
    public function add_commission($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'request_id' => 0,
            'consultant_id' => 0,
            'base_amount' => 0,
            'commission_type' => 'percent',
            'commission_value' => 0,
            'generated_at' => current_time('mysql'),
            'final_commission' => 0,
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['request_id'] || !$data['consultant_id']) {
            error_log('[Diet Consultation] Missing required commission fields');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->commissions_table,
            $data,
            ['%d', '%d', '%f', '%s', '%f', '%s', '%s', '%f', '%f', '%f', '%s', '%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Commission insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * دریافت کمیسیون‌های یک مشاور
     */
    public function get_consultant_commissions($consultant_id, $status = '') {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $query = "
            SELECT c.*, r.service_history_id 
            FROM {$this->commissions_table} c 
            LEFT JOIN {$this->table_name} r ON c.request_id = r.id 
            WHERE c.consultant_id = %d
        ";
        $params = [$consultant_id];
        
        if (!empty($status)) {
            $query .= " AND c.status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY c.created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    // ========== متدهای جدید برای مدیریت پرداخت‌ها ==========

    /**
     * افزودن رکورد پرداخت
     */
    public function add_payout($data) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $defaults = [
            'consultant_id' => 0,
            'amount' => 0,
            'period_start' => '',
            'period_end' => '',
            'payment_method' => 'manual',
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (!$data['consultant_id'] || !$data['period_start'] || !$data['period_end']) {
            error_log('[Diet Consultation] Missing required payout fields');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->payouts_table,
            $data,
            ['%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Payout insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * دریافت پرداخت‌های یک مشاور
     */
    public function get_consultant_payouts($consultant_id, $status = '') {
        if (!$this->ensure_tables_exist()) {
            return [];
        }

        global $wpdb;
        
        $query = "SELECT * FROM {$this->payouts_table} WHERE consultant_id = %d";
        $params = [$consultant_id];
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * به‌روزرسانی وضعیت پرداخت
     */
    public function update_payout_status($payout_id, $status, $reference_code = '', $paid_at = null) {
        if (!$this->ensure_tables_exist()) {
            return false;
        }

        global $wpdb;
        
        $data = ['status' => $status];
        
        if (!empty($reference_code)) {
            $data['reference_code'] = $reference_code;
        }
        
        if ($status === 'done' && empty($paid_at)) {
            $data['paid_at'] = current_time('mysql');
        } elseif ($paid_at) {
            $data['paid_at'] = $paid_at;
        }
        
        $result = $wpdb->update(
            $this->payouts_table,
            $data,
            ['id' => $payout_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('[Diet Consultation] Payout update failed: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    
        

// در کلاس AI_Assistant_Diet_Consultation_DB

/**
 * دریافت تمام تسویه‌ها با فیلتر
 */
public function get_all_payouts($filters = [], $page = 1, $per_page = 20) {
    if (!$this->ensure_tables_exist()) {
        return ['payouts' => [], 'pagination' => [], 'summary' => []];
    }

    global $wpdb;
    
    $where = [];
    $params = [];
    
    // فیلتر وضعیت
    if (!empty($filters['status'])) {
        $where[] = "p.status = %s";
        $params[] = $filters['status'];
    }
    
    // فیلتر تاریخ
    if (!empty($filters['date_from'])) {
        $where[] = "p.period_start >= %s";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "p.period_end <= %s";
        $params[] = $filters['date_to'];
    }
    
    // فیلتر مبلغ
    if (!empty($filters['min_amount'])) {
        $where[] = "p.amount >= %d";
        $params[] = $filters['min_amount'];
    }
    
    if (!empty($filters['max_amount'])) {
        $where[] = "p.amount <= %d";
        $params[] = $filters['max_amount'];
    }
    
    // فیلتر جستجو
    if (!empty($filters['search'])) {
        $where[] = "(c.name LIKE %s OR c.email LIKE %s OR p.id LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // شمارش کل
    $count_query = "
        SELECT COUNT(*) 
        FROM {$this->payouts_table} p 
        LEFT JOIN {$this->consultants_table} c ON p.consultant_id = c.id 
        {$where_sql}
    ";
    
    $total_items = $wpdb->get_var($wpdb->prepare($count_query, $params));
    $total_pages = ceil($total_items / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // دریافت داده‌ها
    $query = "
        SELECT 
            p.*,
            c.name as consultant_name,
            c.email as consultant_email,
            (SELECT COUNT(*) FROM {$this->commissions_table} WHERE payout_id = p.id) as commissions_count
        FROM {$this->payouts_table} p 
        LEFT JOIN {$this->consultants_table} c ON p.consultant_id = c.id 
        {$where_sql}
        ORDER BY p.created_at DESC 
        LIMIT %d OFFSET %d
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $payouts = $wpdb->get_results($wpdb->prepare($query, $params));
    
    // محاسبه خلاصه
    $summary = $this->get_payouts_summary();
    
    return [
        'payouts' => $payouts,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'per_page' => $per_page,
            'start_item' => $offset + 1,
            'end_item' => min($offset + $per_page, $total_items)
        ],
        'summary' => $summary
    ];
}

/**
 * دریافت خلاصه آمار
 */
public function get_payouts_summary() {
    if (!$this->ensure_tables_exist()) {
        return [];
    }

    global $wpdb;
    
    // مجموع کمیسیون‌های پرداخت نشده
    $total_pending = $wpdb->get_var("
        SELECT SUM(final_commission) 
        FROM {$this->commissions_table} 
        WHERE status = 'pending'
    ") ?: 0;
    
    // مجموع پرداخت‌های ماه جاری
    $current_month = date('Y-m-01');
    $total_paid_month = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) 
        FROM {$this->payouts_table} 
        WHERE status = 'done' AND paid_at >= %s
    ", $current_month)) ?: 0;
    
    // تعداد مشاوران با موجودی
    $consultants_with_balance = $wpdb->get_var("
        SELECT COUNT(DISTINCT consultant_id) 
        FROM {$this->commissions_table} 
        WHERE status = 'pending' AND final_commission > 0
    ") ?: 0;
    
    // آخرین تسویه
    $last_payout = $wpdb->get_row("
        SELECT p.*, c.name as consultant_name 
        FROM {$this->payouts_table} p 
        LEFT JOIN {$this->consultants_table} c ON p.consultant_id = c.id 
        WHERE p.status = 'done' 
        ORDER BY p.paid_at DESC 
        LIMIT 1
    ");
    
    return [
        'total_pending' => $total_pending,
        'total_paid_month' => $total_paid_month,
        'consultants_with_balance' => $consultants_with_balance,
        'last_payout' => $last_payout
    ];
}

/**
 * دریافت جزئیات یک تسویه
 */
public function get_payout_details($payout_id) {
    if (!$this->ensure_tables_exist()) {
        return false;
    }

    global $wpdb;
    
    $payout = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, c.name as consultant_name, c.email as consultant_email 
        FROM {$this->payouts_table} p 
        LEFT JOIN {$this->consultants_table} c ON p.consultant_id = c.id 
        WHERE p.id = %d
    ", $payout_id));
    
    if (!$payout) {
        return false;
    }
    
    $commissions = $wpdb->get_results($wpdb->prepare("
        SELECT * 
        FROM {$this->commissions_table} 
        WHERE payout_id = %d 
        ORDER BY created_at DESC
    ", $payout_id));
    
    return [
        'payout' => $payout,
        'commissions' => $commissions
    ];
}

/**
 * علامت‌گذاری تسویه به عنوان انجام شده
 */
public function mark_payout_as_done($payout_id, $reference_code = '') {
    if (!$this->ensure_tables_exist()) {
        return false;
    }

    global $wpdb;
    
    // شروع تراکنش
    $wpdb->query('START TRANSACTION');
    
     error_log("Email sent for payout ID: " . $result);
    
    try {
        // آپدیت وضعیت تسویه
        $payout_result = $wpdb->update(
            $this->payouts_table,
            [
                'status' => 'done',
                'reference_code' => $reference_code,
                'paid_at' => current_time('mysql')
            ],
            ['id' => $payout_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($payout_result === false) {
            throw new Exception('خطا در آپدیت تسویه');
        }
        
        // آپدیت وضعیت کمیسیون‌های مرتبط
        $commissions_result = $wpdb->update(
            $this->commissions_table,
            ['status' => 'paid'],
            ['payout_id' => $payout_id],
            ['%s'],
            ['%d']
        );
        
        if ($commissions_result === false) {
            throw new Exception('خطا در آپدیت کمیسیون‌ها');
        }
        
        // کامیت تراکنش
        $wpdb->query('COMMIT');
        return true;
        
    } catch (Exception $e) {
        // رولبک در صورت خطا
        $wpdb->query('ROLLBACK');
        error_log('[Payout Manager] Error marking payout as done: ' . $e->getMessage());
        return false;
    }
}

/**
 * ایجاد تسویه جدید
 */
public function create_payout($data) {
    if (!$this->ensure_tables_exist()) {
        return false;
    }

    global $wpdb;
    
    $defaults = [
        'consultant_id' => 0,
        'amount' => 0,
        'period_start' => '',
        'period_end' => '',
        'payment_method' => 'manual',
        'reference_code' => '',
        'status' => 'pending'
    ];
    
    $data = wp_parse_args($data, $defaults);
    
    if (!$data['consultant_id'] || !$data['amount'] || !$data['period_start'] || !$data['period_end']) {
        return false;
    }
    
    // شروع تراکنش
    $wpdb->query('START TRANSACTION');
    
    try {
        // ایجاد رکورد تسویه
        $payout_result = $wpdb->insert(
            $this->payouts_table,
            $data,
            ['%d', '%f', '%s', '%s', '%s', '%s', '%s']
        );
        
        if (!$payout_result) {
            throw new Exception('خطا در ایجاد تسویه');
        }
        
        $payout_id = $wpdb->insert_id;
        
        // آپدیت کمیسیون‌های انتخاب شده
        if (!empty($data['commission_ids'])) {
            $commission_ids = array_map('intval', $data['commission_ids']);
            $placeholders = implode(',', array_fill(0, count($commission_ids), '%d'));
            
            $update_query = $wpdb->prepare("
                UPDATE {$this->commissions_table} 
                SET payout_id = %d, status = 'paid' 
                WHERE id IN ($placeholders) AND consultant_id = %d AND status = 'pending'
            ", array_merge([$payout_id], $commission_ids, [$data['consultant_id']]));
            
            $update_result = $wpdb->query($update_query);
            
            if ($update_result === false) {
                throw new Exception('خطا در آپدیت کمیسیون‌ها');
            }
        }
        
        // کامیت تراکنش
        $wpdb->query('COMMIT');
        return $payout_id;
        
    } catch (Exception $e) {
        // رولبک در صورت خطا
        $wpdb->query('ROLLBACK');
        error_log('[Payout Manager] Error creating payout: ' . $e->getMessage());
        return false;
    }
}

/**
 * دریافت کمیسیون‌های پرداخت نشده یک مشاور
 */
public function get_unpaid_commissions($consultant_id) {
    if (!$this->ensure_tables_exist()) {
        return [];
    }

    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT * 
        FROM {$this->commissions_table} 
        WHERE consultant_id = %d AND status = 'pending' 
        ORDER BY created_at DESC
    ", $consultant_id));
}

/**
 * دریافت لیست مشاوران
 */
public function get_consultants_list() {
    if (!$this->ensure_tables_exist()) {
        return [];
    }

    global $wpdb;
    
    return $wpdb->get_results("
        SELECT id, name, email 
        FROM {$this->consultants_table} 
        WHERE status = 'active' 
        ORDER BY name ASC
    ");
}


/**
 * دریافت مشاوران با کمیسیون پرداخت نشده
 */
public function get_consultants_with_pending_commissions($filters = [], $page = 1, $per_page = 20) {
    if (!$this->ensure_tables_exist()) {
        return ['consultants' => [], 'pagination' => []];
    }

    global $wpdb;
    
    $where = ["c.status = 'pending'", "c.final_commission > 0", "cons.status = 'active'"];
    $params = [];
    
    // فیلتر جستجو
    if (!empty($filters['search'])) {
        $where[] = "(cons.name LIKE %s OR cons.email LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // فیلتر حداقل مبلغ
    if (!empty($filters['min_amount'])) {
        $where[] = "c.final_commission >= %d";
        $params[] = $filters['min_amount'];
    }
    
    // فیلتر حداکثر مبلغ
    if (!empty($filters['max_amount'])) {
        $where[] = "c.final_commission <= %d";
        $params[] = $filters['max_amount'];
    }
    
    // تبدیل آرایه به رشته SQL
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
    
    // شمارش کل
    $count_query = "
        SELECT COUNT(DISTINCT c.consultant_id) 
        FROM {$this->commissions_table} c 
        INNER JOIN {$this->consultants_table} cons ON c.consultant_id = cons.id 
        WHERE c.status = 'pending' AND c.final_commission > 0
        AND cons.status = 'active'
    ";
    
    $count_query = "
        SELECT COUNT(DISTINCT cons.id)
        FROM {$this->commissions_table} c
        INNER JOIN {$this->consultants_table} cons ON c.consultant_id = cons.id
        {$where_sql}
    ";
    
    $total_items = $wpdb->get_var($wpdb->prepare($count_query, ...$params));
    $total_pages = ceil($total_items / $per_page);
    $offset = ($page - 1) * $per_page;
    
    $query = "
        SELECT 
            cons.id,
            cons.name,
            cons.email,
            COUNT(c.id) as pending_count,
            SUM(c.final_commission) as total_pending,
            AVG(c.final_commission) as average_amount,
            MIN(c.created_at) as oldest_date
        FROM {$this->commissions_table} c
        INNER JOIN {$this->consultants_table} cons ON c.consultant_id = cons.id
        {$where_sql}
        GROUP BY cons.id, cons.name, cons.email
        HAVING total_pending > 0
        ORDER BY total_pending DESC
        LIMIT %d OFFSET %d
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $consultants = $wpdb->get_results($wpdb->prepare($query, ...$params));

    
    return [
        'consultants' => $consultants,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'per_page' => $per_page,
            'start_item' => $offset + 1,
            'end_item' => min($offset + $per_page, $total_items)
        ]
    ];
}

/**
 * دریافت تعداد کل تسویه‌ها و مشاوران برای badgeها
 */
public function get_counts_for_tabs() {
    if (!$this->ensure_tables_exist()) {
        return ['payouts_count' => 0, 'consultants_count' => 0];
    }

    global $wpdb;
    
    $payouts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->payouts_table}");
    
    $consultants_count = $wpdb->get_var("
        SELECT COUNT(DISTINCT consultant_id) 
        FROM {$this->commissions_table} 
        WHERE status = 'pending' AND final_commission > 0
    ");
    
    return [
        'payouts_count' => $payouts_count,
        'consultants_count' => $consultants_count
    ];
}
        
        
    /* ============================================================
       🔸  توابع کمکی
    ============================================================ */
    private function calculate_delay_hours($generated_at, $approved_at) {
        $diff = strtotime($approved_at) - strtotime($generated_at);
        return round($diff / 3600, 2);
    }

    private function calculate_penalty($delay_hours, $factor) {
        if ((int)$delay_hours === 0) return 1;
        return max(pow($factor, $delay_hours),0.05);
        


    }    
}