<?php
/**
 * مدیریت تاریخچه سرویس‌های هوش مصنوعی با جدول اختصاصی (نسخه خودکار)
 */
class AI_Assistant_History_Manager {
    private static $instance;
    private $table_name;
    private $table_created = false;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'service_history';
        add_action('admin_init', [$this, 'handle_history_deletion']);
    }

    /**
     * بررسی و ایجاد جدول در صورت عدم وجود
     */
    private function maybe_create_table() {
        if ($this->table_created) {
            return true;
        }

        global $wpdb;
        
        // بررسی وجود جدول
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                service_id varchar(100) NOT NULL,
                service_name varchar(255) NOT NULL,
                response longtext NOT NULL,
                user_data longtext NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY service_id (service_id),
                KEY created_at (created_at)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // لاگ برای اشکالزدایی
            error_log('[AI History] Table created: ' . $this->table_name);
        }
        
        $this->table_created = true;
        return true;
    }

    /**
     * ذخیره یک آیتم در تاریخچه
     */
    public function save_history($user_id, $service_id, $service_name, $user_data , $response) {
        global $wpdb;
        
        
        
        // بررسی و ایجاد جدول اگر وجود نداشته باشد
        $this->maybe_create_table();
        
        if (!get_user_by('ID', $user_id)) {
            error_log('[AI History] Invalid user ID: ' . $user_id);
            return false;
        }
        
        $wpdb->query("SET time_zone = '+03:30';");
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'service_id' => $service_id,
                'service_name' => $service_name,
                'user_data' => $user_data,
                'response' => wp_kses($response, $this->get_allowed_html_tags())
            ],
            ['%d', '%s', '%s','%s', '%s']
        );
        
        if ($result === false) {
            error_log('[AI History] Database error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * دریافت تاریخچه کاربر با قابلیت صفحه‌بندی
     */
    public function get_user_history($user_id, $per_page = 10) {
        global $wpdb;
        
        // بررسی و ایجاد جدول اگر وجود نداشته باشد
        $this->maybe_create_table();
        
        $paged = get_query_var('paged') ?: 1;
        $offset = ($paged - 1) * $per_page;
        
        $query = $wpdb->prepare(
            "SELECT SQL_NO_CACHE * FROM {$this->table_name} 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d, %d",
            $user_id,
            $offset,
            $per_page
        );
        
        $items = $wpdb->get_results($query);
        
        // تبدیل به ساختار شبه-Post برای سازگاری با کدهای موجود
        return array_map(function($item) {
            return (object)[
                'ID' => $item->id,
                'user_id' => $item->user_id,
                'service_name' => 'سرویس: ' . $item->service_name,
                'response' => $item->response,
                'created_at' => $item->created_at,
                'service_id' => $item->service_id
            ];
        }, $items);
    }

    /**
     * حذف یک آیتم از تاریخچه
     */
    public function delete_history_item($item_id, $user_id) {
        global $wpdb;
        
        // بررسی و ایجاد جدول اگر وجود نداشته باشد
        $this->maybe_create_table();
        
        if (!$this->is_user_owner($item_id, $user_id)) {
            return false;
        }
        
        return (bool) $wpdb->delete(
            $this->table_name,
            ['id' => $item_id, 'user_id' => $user_id],
            ['%d', '%d']
        );
    }

    /**
     * بررسی مالکیت آیتم
     */
    public function is_user_owner($item_id, $user_id) {
        global $wpdb;
        
        // بررسی و ایجاد جدول اگر وجود نداشته باشد
        $this->maybe_create_table();
        
        $owner_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$this->table_name} WHERE id = %d",
                $item_id
            )
        );
        
        return $owner_id == $user_id;
    }

    /**
     * مدیریت درخواست‌های حذف
     */
    public function handle_history_deletion() {
        if (!isset($_GET['delete_history'], $_GET['_wpnonce'])) return;

        $post_id = absint($_GET['delete_history']);
        $user_id = get_current_user_id();

        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_history_' . $post_id)) {
            $this->delete_history_item($post_id, $user_id);
            wp_redirect(remove_query_arg(['delete_history', '_wpnonce']));
            exit;
        }
    }

    /**
     * لیست تگ‌های مجاز HTML
     */
    private function get_allowed_html_tags() {
        return [
            'html' => [],
            'head' => [],
            'body' => ['style' => true],
            'style' => [],
            'div' => ['id' => true, 'class' => true, 'style' => true],
            'span' => ['class' => true, 'style' => true],
            'p' => ['class' => true, 'style' => true],
            'a' => ['href' => true, 'title' => true, 'style' => true],
            'ul' => ['class' => true, 'style' => true],
            'ol' => ['class' => true, 'style' => true],
            'li' => ['class' => true, 'style' => true],
            'img' => ['src' => true, 'alt' => true, 'style' => true, 'width' => true, 'height' => true],
            'br' => [],
            'h1' => ['style' => true], 'h2' => ['style' => true], 'h3' => ['style' => true],
            'table' => ['class' => true, 'style' => true],
            'thead' => ['style' => true],
            'tbody' => ['style' => true],
            'tr' => ['style' => true],
            'td' => ['style' => true, 'colspan' => true, 'rowspan' => true],
            'th' => ['style' => true, 'colspan' => true],
        ];
    }
}

// راه‌اندازی کلاس
AI_Assistant_History_Manager::get_instance();