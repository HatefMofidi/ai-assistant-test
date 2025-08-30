<?php
// /inc/class-service-db.php

class AI_Assistant_Service_DB {
    private static $instance;
    private $table_name;
    private $logger;
    private $table_checked = false; // برای جلوگیری از چک کردن مکرر

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'assistant_services';
        $this->logger = AI_Assistant_Logger::get_instance();
    }

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ایجاد جدول در دیتابیس
     */
    public function create_table() {
        global $wpdb;
        
        // بررسی اولیه: فقط ادمین‌ها می‌توانند جدول ایجاد کنند
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'permission_denied',
                'فقط کاربران مدیر می‌توانند جدول را ایجاد کنند.'
            );
        }        
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            price int(11) NOT NULL DEFAULT 0,
            description text,
            system_prompt longtext,
            icon varchar(100) NOT NULL DEFAULT 'dashicons-admin-generic',
            active tinyint(1) NOT NULL DEFAULT 1,
            template varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY service_id (service_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * بررسی وجود جدول
     */
    private function ensure_table_exists() {
        if ($this->table_checked) return true;
        

        global $wpdb;
        $table = $this->table_name;
        
        $exists = ($wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", $table
        )) === $table);

        if (!$exists) {
            $this->create_table();
            // دوباره چک کن که ایجاد شده باشه
            $exists = ($wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s", $table
            )) === $table);
        }

        $this->table_checked = true;
        return $exists;
    }

    public function add_service($service_data) {
        if (!$this->ensure_table_exists()) {
            return new WP_Error('table_missing', 'جدول سرویس‌ها وجود ندارد و ایجاد آن موفق نبود.');
        }
        
        // بررسی اولیه: فقط ادمین‌ها می‌توانند جدول اضافه کنند
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'permission_denied',
                'فقط کاربران مدیر می‌توانند به جدول اضافه کنند.'
            );
        }        
        
        global $wpdb;
        
        $defaults = [
            'service_id' => '',
            'name' => '',
            'price' => 0,
            'description' => '',
            'system_prompt' => '',
            'icon' => 'dashicons-admin-generic',
            'active' => true,
            'template' => ''
        ];
        
        $data = wp_parse_args($service_data, $defaults);
        
        if (empty($data['service_id'])) {
            return new WP_Error('missing_id', 'شناسه سرویس الزامی است.');
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            ['%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    public function update_service($service_id, $data) {
        if (!$this->ensure_table_exists()) {
            return new WP_Error('table_missing', 'جدول سرویس‌ها وجود ندارد.');
        }
        
        // بررسی اولیه: فقط ادمین‌ها می‌توانند جدول را ویرایش کنند
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'permission_denied',
                'فقط کاربران مدیر می‌توانند جدول را ویرایش کنند'
            );
        }         

        global $wpdb;
        
        $this->logger->log('class-service-db.update_service', [
            'step' => $data,
            'service_id' => $service_id
        ]);   
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['service_id' => $service_id],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s'],
            ['%s']
        );
        
        return $result !== false;
    }

    public function get_service($service_id) {
        if (!$this->ensure_table_exists()) {
            return false;
        }

        global $wpdb;
        
        $service = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE service_id = %s",
                $service_id
            ),
            ARRAY_A
        );
        
        return $service ?: false;
    }

    public function get_all_services($only_active = false) {
        if (!$this->ensure_table_exists()) {
            return [];
        }

        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name}";
        if ($only_active) {
            $query .= " WHERE active = 1";
        }
        
        $services = $wpdb->get_results($query, ARRAY_A);
        
        $result = [];
        foreach ($services as $service) {
            $result[$service['service_id']] = $service;
        }
        
        return $result;
    }

    public function delete_service($service_id) {
        if (!$this->ensure_table_exists()) {
            return false;
        }

        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            ['service_id' => $service_id],
            ['%s']
        );
        
        return $result !== false;
    }
}
