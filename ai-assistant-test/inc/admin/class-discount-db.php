<?php
// /inc/admin/class-discount-db.php

class AI_Assistant_Discount_DB {
    private static $instance;
    private $table_discounts;
    private $table_discount_services;
    private $table_discount_users;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_discounts = $wpdb->prefix . 'assistant_discounts';
        $this->table_discount_services = $wpdb->prefix . 'assistant_discount_services';
        $this->table_discount_users = $wpdb->prefix . 'assistant_discount_users';
        
        $this->maybe_create_tables();
    }
    
    /**
     * ایجاد جداول در صورت عدم وجود
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول تخفیف‌ها
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_discounts}'") != $this->table_discounts) {
            $sql = "CREATE TABLE {$this->table_discounts} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                code varchar(100) DEFAULT NULL,
                type varchar(20) NOT NULL,
                amount decimal(10,0) NOT NULL,
                amount_type varchar(20) NOT NULL DEFAULT 'fixed',
                start_date datetime DEFAULT NULL,
                end_date datetime DEFAULT NULL,
                usage_limit int(11) DEFAULT 0,
                usage_count int(11) DEFAULT 0,
                min_order_amount decimal(15,0) DEFAULT 0,
                scope varchar(20) NOT NULL DEFAULT 'global',
                user_restriction varchar(20) DEFAULT NULL,
                active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY code (code),
                INDEX (scope),
                INDEX (active),
                INDEX (start_date),
                INDEX (end_date)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // جدول ارتباط تخفیف با سرویس‌ها
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_discount_services}'") != $this->table_discount_services) {
            $sql = "CREATE TABLE {$this->table_discount_services} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                discount_id bigint(20) NOT NULL,
                service_id varchar(100) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY discount_service (discount_id, service_id),
                INDEX (discount_id),
                INDEX (service_id)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // جدول ارتباط تخفیف با کاربران
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_discount_users}'") != $this->table_discount_users) {
            $sql = "CREATE TABLE {$this->table_discount_users} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                discount_id bigint(20) NOT NULL,
                user_id bigint(20) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY discount_user (discount_id, user_id),
                INDEX (discount_id),
                INDEX (user_id)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * افزودن تخفیف جدید
     */
    public function add_discount($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_discounts,
            $data,
            $this->get_discount_format($data)
        );
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * به‌روزرسانی تخفیف
     */
    public function update_discount($discount_id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_discounts,
            $data,
            ['id' => $discount_id],
            $this->get_discount_format($data),
            ['%d']
        );
    }
    
    /**
     * حذف تخفیف
     */
    public function delete_discount($discount_id) {
        global $wpdb;
        
        // حذف از جدول اصلی
        $result = $wpdb->delete(
            $this->table_discounts,
            ['id' => $discount_id],
            ['%d']
        );
        
        if ($result) {
            // حذف ارتباط با سرویس‌ها
            $this->delete_discount_services($discount_id);
            
            // حذف ارتباط با کاربران
            $this->delete_discount_users($discount_id);
        }
        
        return $result;
    }
    
    /**
     * حذف تمام سرویس‌های مرتبط با یک تخفیف
     */
    public function delete_discount_services($discount_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_discount_services,
            ['discount_id' => $discount_id],
            ['%d']
        );
    }
    
    /**
     * حذف تمام کاربران مرتبط با یک تخفیف
     */
    public function delete_discount_users($discount_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_discount_users,
            ['discount_id' => $discount_id],
            ['%d']
        );
    }
    
    /**
     * دریافت یک تخفیف
     */
    public function get_discount($discount_id) {
        global $wpdb;
        
        $discount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_discounts} WHERE id = %d",
            $discount_id
        ));
        
        if (!$discount) {
            return false;
        }
        
        // دریافت سرویس‌های مرتبط
        $discount->services = $this->get_discount_services($discount_id);
        
        // دریافت کاربران مرتبط
        $discount->users = $this->get_discount_users($discount_id);
        
        return $discount;
    }
    
    /**
     * دریافت تمام تخفیف‌ها
     */
    public function get_all_discounts() {
        global $wpdb;
        
        return $wpdb->get_results("SELECT * FROM {$this->table_discounts} ORDER BY created_at DESC");
    }
    
    /**
     * دریافت سرویس‌های یک تخفیف
     */
    public function get_discount_services($discount_id) {
        global $wpdb;
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT service_id FROM {$this->table_discount_services} WHERE discount_id = %d",
            $discount_id
        ));
    }
    
    /**
     * دریافت کاربران یک تخفیف
     */
    public function get_discount_users($discount_id) {
        global $wpdb;
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$this->table_discount_users} WHERE discount_id = %d",
            $discount_id
        ));
    }
    
    /**
     * افزودن سرویس به تخفیف
     */
    public function add_discount_service($discount_id, $service_id) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_discount_services,
            [
                'discount_id' => $discount_id,
                'service_id' => $service_id
            ],
            ['%d', '%s']
        );
    }
    
    /**
     * افزودن کاربر به تخفیف
     */
    public function add_discount_user($discount_id, $user_id) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_discount_users,
            [
                'discount_id' => $discount_id,
                'user_id' => $user_id
            ],
            ['%d', '%d']
        );
    }
    
    /**
     * فرمت داده‌های تخفیف
     */
    private function get_discount_format($data) {
        $formats = [
            'name' => '%s',
            'code' => '%s',
            'type' => '%s',
            'amount' => '%f',
            'amount_type' => '%s',
            'start_date' => '%s',
            'end_date' => '%s',
            'usage_limit' => '%d',
            'usage_count' => '%d',
            'min_order_amount' => '%f',
            'scope' => '%s',
            'user_restriction' => '%s',
            'active' => '%d'
        ];
        
        $result = [];
        foreach ($data as $key => $value) {
            if (isset($formats[$key])) {
                $result[] = $formats[$key];
            }
        }
        
        return $result;
    }
    


    /**
     * محاسبه تخفیف برای یک سرویس (نسخه ساده‌تر)
     */
    public function calculate_discount($service_id, $original_price, $user_id = 0, $coupon_code = '') {
        global $wpdb;
        
        $now = current_time('mysql');
        
        // ابتدا همه تخفیف‌های فعال را بگیریم
        $discounts = $wpdb->get_results($wpdb->prepare(
            "SELECT d.* 
            FROM {$this->table_discounts} d
            WHERE d.active = 1
            AND (d.start_date IS NULL OR d.start_date <= %s)
            AND (d.end_date IS NULL OR d.end_date >= %s)
            AND (d.usage_limit = 0 OR d.usage_count < d.usage_limit)",
            $now,
            $now
        ));
        
        $applicable_discounts = [];
        
        foreach ($discounts as $discount) {
            $is_applicable = false;
            
            switch ($discount->scope) {
                case 'global':
                    $is_applicable = true;
                    break;
                    
                case 'service':
                    $service_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$this->table_discount_services} 
                        WHERE discount_id = %d AND service_id = %s",
                        $discount->id,
                        $service_id
                    ));
                    $is_applicable = $service_count > 0;
                    break;
                    
                case 'coupon':
                    if ($discount->code === $coupon_code) {
                        $service_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$this->table_discount_services} 
                            WHERE discount_id = %d AND service_id = %s",
                            $discount->id,
                            $service_id
                        ));
                        // اگر سرویس خاصی تعریف نشده یا سرویس مطابقت دارد
                        $is_applicable = $service_count === 0 || $service_count > 0;
                    }
                    break;
                    
                case 'user_based':
                    if ($discount->user_restriction === 'first_time') {
                        $order_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}assistant_orders 
                            WHERE user_id = %d AND service_id = %s",
                            $user_id,
                            $service_id
                        ));
                        $is_applicable = $order_count === 0;
                    } elseif ($discount->user_restriction === 'specific_users') {
                        $user_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$this->table_discount_users} 
                            WHERE discount_id = %d AND user_id = %d",
                            $discount->id,
                            $user_id
                        ));
                        $is_applicable = $user_count > 0;
                    }
                    break;
            }
            
            if ($is_applicable) {
                $applicable_discounts[] = $discount;
            }
        }
        
        $best_discount = ['amount' => 0, 'type' => '', 'id' => 0, 'name' => ''];
        
        foreach ($applicable_discounts as $discount) {
            $discount_amount = 0;
            
            if ($discount->amount_type == 'percentage') {
                $discount_amount = $original_price * ($discount->amount / 100);
            } else {
                $discount_amount = $discount->amount;
            }
            
            // اطمینان از اینکه تخفیف بیشتر از قیمت اصلی نباشد
            $discount_amount = min($discount_amount, $original_price);
            
            if ($discount_amount > $best_discount['amount']) {
                $best_discount = [
                    'amount' => $discount_amount,
                    'type' => $discount->amount_type,
                    'id' => $discount->id,
                    'name' => $discount->name
                ];
            }
        }
        
        return $best_discount;
    }
    
    /**
     * افزایش تعداد استفاده از تخفیف
     */
    public function increment_usage($discount_id) {
        global $wpdb;
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_discounts} SET usage_count = usage_count + 1 WHERE id = %d",
            $discount_id
        ));
    }
}