<?php
// /inc/admin/class-discount-admin.php

class AI_Assistant_Discount_Admin {
    private static $instance;
    private $db;
    private $logger;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = AI_Assistant_Discount_DB::get_instance();
        $this->logger = AI_Assistant_Logger::get_instance();
        
        // اضافه کردن هوک‌های مدیریت
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('admin_init', [$this, 'handle_delete_action']);
        
        // اضافه کردن استایل و اسکریپت‌های ادمین
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * افزودن استایل و اسکریپت‌های ادمین
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ai-assistant-discounts') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ai-assistant-discount-admin',
            get_template_directory_uri() . '/assets/css/admin/discount-admin.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'ai-assistant-discount-admin',
            get_template_directory_uri() . '/assets/js/admin/discount-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // انتقال متغیرها به JavaScript
        wp_localize_script('ai-assistant-discount-admin', 'discountAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('load_more_users')
        ]);        
    }
    
    /**
     * افزودن منوی مدیریت تخفیف‌ها
     */
    public function add_admin_menu() {
        add_menu_page(
            'مدیریت تخفیف‌ها',
            'تخفیف‌های AI',
            'manage_options',
            'ai-assistant-discounts',
            [$this, 'render_discounts_page'],
            'dashicons-tickets-alt',
            30
        );
        
        add_submenu_page(
            'ai-assistant-discounts',
            'مدیریت تخفیف‌ها',
            'همه تخفیف‌ها',
            'manage_options',
            'ai-assistant-discounts',
            [$this, 'render_discounts_page']
        );
        
        add_submenu_page(
            'ai-assistant-discounts',
            'افزودن تخفیف جدید',
            'افزودن جدید',
            'manage_options',
            'ai-assistant-discounts-add',
            [$this, 'render_add_discount_page']
        );
    }
    
    /**
     * رندر صفحه مدیریت تخفیف‌ها
     */
    public function render_discounts_page() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $discount_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        
        echo '<div class="wrap">';
        
        switch ($action) {
            case 'edit':
                $this->render_edit_form($discount_id);
                break;
            default:
                $this->render_discounts_list();
                break;
        }
        
        echo '</div>';
    }
    
    /**
     * رندر صفحه افزودن تخفیف جدید
     */
    public function render_add_discount_page() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        echo '<div class="wrap">';
        $this->render_edit_form();
        echo '</div>';
    }
    
    /**
     * رندر لیست تخفیف‌ها
     */
    private function render_discounts_list() {
        // استفاده از فایل view جداگانه
        include get_template_directory() . '/inc/admin/views/discounts-list.php';
    }
    
    /**
     * رندر فرم ویرایش/افزودن تخفیف
     */
    private function render_edit_form($discount_id = 0) {
        // استفاده از فایل view جداگانه
        include get_template_directory() . '/inc/admin/views/discounts-edit.php';
    }
    
    /**
     * پردازش حذف تخفیف
     */
    public function handle_delete_action() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete' || !isset($_GET['id'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $discount_id = absint($_GET['id']);
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        
        if (!wp_verify_nonce($nonce, 'delete_discount_' . $discount_id)) {
            wp_die('Nonce verification failed');
        }
        
        if ($this->db->delete_discount($discount_id)) {
            $this->logger->log('تخفیف با موفقیت حذف شد: ' . $discount_id);
            wp_redirect(admin_url('admin.php?page=ai-assistant-discounts&message=deleted'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=ai-assistant-discounts&message=delete_error'));
            exit;
        }
    }
    
    /**
     * پردازش فرم‌های ارسالی
     */
    public function handle_form_submissions() {
        if (!isset($_POST['ai_discount_nonce']) || !wp_verify_nonce($_POST['ai_discount_nonce'], 'ai_assistant_discount_nonce')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
        
        if ($action === 'add_discount') {
            $this->add_discount($_POST);
        } elseif ($action === 'edit_discount') {
            $discount_id = isset($_POST['discount_id']) ? absint($_POST['discount_id']) : 0;
            if ($discount_id) {
                $this->update_discount($discount_id, $_POST);
            }
        }
        
        wp_redirect(admin_url('admin.php?page=ai-assistant-discounts'));
        exit;
    }
    
    /**
     * افزودن تخفیف جدید
     */
    private function add_discount($data) {
        $discount_data = $this->sanitize_discount_data($data);
        
        $discount_id = $this->db->add_discount($discount_data);
        
        if ($discount_id) {
            // ذخیره سرویس‌های مرتبط
            if (!empty($data['service_ids'])) {
                foreach ((array)$data['service_ids'] as $service_id) {
                    $this->db->add_discount_service($discount_id, sanitize_text_field($service_id));
                }
            }
            
            // ذخیره کاربران مرتبط
            if (!empty($data['user_ids'])) {
                foreach ((array)$data['user_ids'] as $user_id) {
                    $this->db->add_discount_user($discount_id, intval($user_id));
                }
            }
            
            $this->logger->log('تخفیف جدید با موفقیت افزوده شد: ' . $discount_data['name']);
        } else {
            $this->logger->log('خطا در افزودن تخفیف جدید', 'error');
        }
    }
    
    /**
     * به‌روزرسانی تخفیف
     */
    private function update_discount($discount_id, $data) {
        $discount_data = $this->sanitize_discount_data($data);
        
        $result = $this->db->update_discount($discount_id, $discount_data);
        
        if ($result !== false) {
            // به‌روزرسانی سرویس‌های مرتبط
            $this->db->delete_discount_services($discount_id);
            if (!empty($data['service_ids'])) {
                foreach ((array)$data['service_ids'] as $service_id) {
                    $this->db->add_discount_service($discount_id, sanitize_text_field($service_id));
                }
            }
            
            // به‌روزرسانی کاربران مرتبط
            $this->db->delete_discount_users($discount_id);
            if (!empty($data['user_ids'])) {
                foreach ((array)$data['user_ids'] as $user_id) {
                    $this->db->add_discount_user($discount_id, intval($user_id));
                }
            }
            
            $this->logger->log('تخفیف با موفقیت به‌روزرسانی شد: ' . $discount_data['name']);
        } else {
            $this->logger->log('خطا در به‌روزرسانی تخفیف', 'error');
        }
    }
    
    /**
     * پاکسازی داده‌های تخفیف
     */
    private function sanitize_discount_data($data) {
        return [
            'name' => sanitize_text_field($data['discount_name']),
            'code' => !empty($data['coupon_code']) ? sanitize_text_field($data['coupon_code']) : null,
            'type' => sanitize_text_field($data['discount_type']),
            'amount' => floatval($data['discount_value']),
            'amount_type' => sanitize_text_field($data['discount_type']),
            'start_date' => !empty($data['start_date']) ? sanitize_text_field($data['start_date']) : null,
            'end_date' => !empty($data['end_date']) ? sanitize_text_field($data['end_date']) : null,
            'usage_limit' => intval($data['usage_limit']),
            'min_order_amount' => floatval($data['min_order_amount']),
            'scope' => sanitize_text_field($data['scope']),
            'user_restriction' => !empty($data['user_restriction']) ? sanitize_text_field($data['user_restriction']) : null,
            'active' => isset($data['active']) ? 1 : 0
        ];
    }
}