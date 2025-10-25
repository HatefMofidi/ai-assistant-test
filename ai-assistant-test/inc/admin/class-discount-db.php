<?php
// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/inc/admin/class-discount-db.php

require_once get_template_directory() . '/inc/class-persian-date-helper.php';

class AI_Assistant_Discount_DB {
    private static $instance;
    private $table_discounts;
    private $table_discount_services;
    private $table_discount_users;
    
    // اضافه کردن این متد به کلاس AI_Assistant_Discount_DB در فایل class-discount-db.php
    public function get_table_name() {
        return $this->table_discounts;
    }
    
    // همچنین برای دسترسی به جدول سرویس‌ها اگر نیاز باشد
    public function get_services_table_name() {
        return $this->table_discount_services;
    }

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
    
    // در تابع maybe_create_tables، جدول تخفیف‌ها را به‌روزرسانی کنید
    private function maybe_create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول تخفیف‌ها (اصلاح شده)
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_discounts}'") != $this->table_discounts) {
            $sql = "CREATE TABLE {$this->table_discounts} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                code varchar(100) DEFAULT NULL,
                type enum('percentage', 'fixed') NOT NULL DEFAULT 'fixed',
                amount decimal(10,2) NOT NULL,
                scope enum('global', 'service', 'coupon', 'user_based') NOT NULL DEFAULT 'global',
                usage_limit int(11) DEFAULT 0,
                usage_count int(11) DEFAULT 0,
                user_restriction enum('first_time', 'specific_users') DEFAULT NULL,
                start_date datetime DEFAULT NULL,
                end_date datetime DEFAULT NULL,
                active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                PRIMARY KEY (id),
                INDEX idx_code (code), 
                INDEX idx_scope_active (scope, active),
                INDEX idx_dates (start_date, end_date),
                INDEX idx_usage (usage_limit, usage_count)
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
     * افزودن تخفیف جدید - نسخه اصلاح شده
     */
    public function add_discount($data) {
        global $wpdb;
        
        // فقط فیلدهایی که در جدول وجود دارند را نگه دارید
        $table_columns = [
            'name', 'code', 'type', 'amount', 'scope', 'usage_limit', 'usage_count',
            'start_date', 'end_date', 'user_restriction', 'active'
        ];
        
        $filtered_data = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $table_columns)) {
                $filtered_data[$key] = $value;
            }
        }
        
        error_log('داده‌های فیلتر شده برای INSERT: ' . print_r($filtered_data, true));
        
        $result = $wpdb->insert(
            $this->table_discounts,
            $filtered_data,
            $this->get_discount_format($filtered_data)
        );
        
        if (!$result) {
            error_log('خطا در INSERT: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public function update_discount($discount_id, $data) {
        global $wpdb;
        
        error_log('شروع آپدیت در دیتابیس برای ID: ' . $discount_id);
        error_log('داده‌های دریافتی برای آپدیت: ' . print_r($data, true));
        
        // فقط فیلدهایی که در جدول وجود دارند را نگه دارید
        $table_columns = [
            'name', 'code', 'type', 'amount', 'scope', 'usage_limit', 'usage_count',
            'start_date', 'end_date', 'user_restriction', 'active'
        ];
        
        $filtered_data = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $table_columns)) {
                // اگر مقدار null است، آن را به صورت صریح تنظیم کنیم
                if ($value === null) {
                    $filtered_data[$key] = null;
                } else {
                    $filtered_data[$key] = $value;
                }
            }
        }
        
        error_log('داده‌های فیلتر شده برای آپدیت: ' . print_r($filtered_data, true));
        
        if (empty($filtered_data)) {
            error_log('خطا: هیچ داده‌ای برای آپدیت وجود ندارد');
            return false;
        }
        
        $result = $wpdb->update(
            $this->table_discounts,
            $filtered_data,
            ['id' => $discount_id],
            $this->get_discount_format($filtered_data),
            ['%d']
        );
        
        error_log('نتیجه آپدیت دیتابیس: ' . ($result !== false ? 'موفق - تعداد ردیف‌های affected: ' . $result : 'ناموفق'));
        error_log('خطای دیتابیس: ' . $wpdb->last_error);
        
        return $result;
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
     * دریافت یک تخفیف با اطلاعات کامل کاربران
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
        
        // دریافت کاربران مرتبط با اطلاعات کامل
        $discount->users = $this->get_discount_users_with_details($discount_id);
        
        return $discount;
    }
    
    /**
     * دریافت کاربران تخفیف با اطلاعات کامل
     */
    public function get_discount_users_with_details($discount_id) {
        global $wpdb;
        
        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$this->table_discount_users} WHERE discount_id = %d",
            $discount_id
        ));
        
        $users_with_details = [];
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $first_name = get_user_meta($user_id, 'first_name', true);
                $last_name = get_user_meta($user_id, 'last_name', true);
                $phone = get_user_meta($user_id, 'billing_phone', true);
                
                $users_with_details[] = [
                    'id' => $user_id,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone
                ];
            }
        }
        
        return $users_with_details;
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
     * فرمت داده‌های تخفیف - نسخه اصلاح شده
     */
    private function get_discount_format($data) {
        $formats = [];
        
        // تعریف فرمت برای هر فیلد موجود در جدول
        $field_formats = [
            'name' => '%s',
            'code' => '%s',
            'type' => '%s',
            'amount' => '%f',
            'scope' => '%s',
            'usage_limit' => '%d',
            'usage_count' => '%d',
            'user_restriction' => '%s',
            'start_date' => '%s',
            'end_date' => '%s',
            'active' => '%d'
        ];
        
        // فقط برای فیلدهایی که در داده‌ها وجود دارند و در جدول نیز موجود هستند
        foreach ($data as $key => $value) {
            if (isset($field_formats[$key])) {
                // اگر مقدار null است، فرمت مناسب را تنظیم کن
                if ($value === null) {
                    $formats[] = $field_formats[$key];
                } else {
                    $formats[] = $field_formats[$key];
                }
            }
        }
        
        return $formats;
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
            AND (d.usage_limit = 0 OR d.usage_count < d.usage_limit)
            ORDER BY d.priority ASC, d.amount DESC",
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