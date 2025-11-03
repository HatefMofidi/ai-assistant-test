<?php
/**
 * مدیریت کدهای تخفیف در Front-end برای ادمین
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/inc/admin/class-discount-frontend-admin.php
 */

if (!defined('ABSPATH')) exit;

class AI_Assistant_Discount_Frontend_Admin {
    private static $instance = null;
    private $discount_db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // بررسی وجود کلاس Discount DB
        if (!class_exists('AI_Assistant_Discount_DB')) {
            require_once get_template_directory() . '/inc/admin/class-discount-db.php';
        }
        
        $this->discount_db = AI_Assistant_Discount_DB::get_instance();
        $this->init_hooks();
    }
        
    /**
     * دریافت لیست کاربران برای انتخاب با اطلاعات کامل
     */
    private function get_users_list() {
        $users = get_users([
            'role__not_in' => ['administrator'],
            'number' => 100,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);
        
        $users_list = [];
        foreach ($users as $user) {
            // دریافت اطلاعات متا کاربر
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            $phone = get_user_meta($user->ID, 'billing_phone', true); // شماره موبایل از اطلاعات صورتحساب
            
            // ایجاد نام کامل
            $full_name = trim($first_name . ' ' . $last_name);
            if (empty($full_name)) {
                $full_name = $user->display_name;
            }
            
            // ایجاد متن نمایشی
            $display_text = $full_name;
            if (!empty($phone)) {
                $display_text .= ' - ' . $phone;
            }
            // $display_text .= ' (' . $user->user_email . ')';
            
            $users_list[] = [
                'id' => $user->ID,
                'name' => $display_text,
                'full_name' => $full_name,
                'phone' => $phone,
                'email' => $user->user_email
            ];
        }
        
        return $users_list;
    }
    
    private function is_discount_active($discount) {
        if (!$discount->active) {
            return false;
        }
    
        $now = current_time('mysql');
        
        // بررسی تاریخ شروع
        if ($discount->start_date && $discount->start_date > $now) {
            return false;
        }
        
        // بررسی تاریخ انقضا
        if ($discount->end_date && $discount->end_date < $now) {
            return false;
        }
    
        return true;
    }    

    private function init_hooks() {
        // اضافه کردن shortcode برای نمایش پنل مدیریت
        add_shortcode('discount_codes_admin', [$this, 'render_admin_panel']);
        
        // ثبت اسکریپت‌ها و استایل‌ها
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // هندلرهای AJAX
        add_action('wp_ajax_create_discount_code', [$this, 'handle_create_discount']);
        add_action('wp_ajax_update_discount_code', [$this, 'handle_update_discount']);
        add_action('wp_ajax_delete_discount_code', [$this, 'handle_delete_discount']);
        add_action('wp_ajax_toggle_discount_status', [$this, 'handle_toggle_status']);
        add_action('wp_ajax_get_discounts_list', [$this, 'handle_get_discounts']);
        add_action('wp_ajax_get_discount_details', [$this, 'handle_get_discount_details']);
        
        // اضافه کردن به init_hooks
        add_action('wp_ajax_get_users_list', [$this, 'handle_get_users_list']);
    }
    

    // هندلر جدید
    public function handle_get_users_list() {
        $this->verify_nonce_and_permissions();
        
        $users = $this->get_users_list();
        wp_send_json_success(['users' => $users]);
    }  

    public function enqueue_assets() {
        // فقط در صفحاتی که پنل مدیریت نمایش داده می‌شود
        if (is_page() && has_shortcode(get_post()->post_content, 'discount_codes_admin')) {
            wp_enqueue_style('ai-discount-frontend-admin-css', 
                get_template_directory_uri() . '/assets/css/discount-frontend-admin.css',
                [],
                filemtime(get_template_directory() . '/assets/css/discount-frontend-admin.css')
            );

            wp_enqueue_script('ai-discount-frontend-admin-js',
                get_template_directory_uri() . '/assets/js/discount-frontend-admin.js',
                ['jquery'],
                filemtime(get_template_directory() . '/assets/js/discount-frontend-admin.js'),
                true
            );

            wp_localize_script('ai-discount-frontend-admin-js', 'discountFrontendAdminVars', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('discount_frontend_admin_nonce'),
                'i18n' => [
                    'confirm_delete' => 'آیا از حذف این کد تخفیف مطمئن هستید؟',
                    'confirm_deactivate' => 'آیا از غیرفعال کردن این کد تخفیف مطمئن هستید؟',
                    'error' => 'خطا در انجام عملیات',
                    'loading' => 'در حال بارگذاری...',
                    'copy_success' => 'کد تخفیف با موفقیت کپی شد'
                ]
            ]);
        }
    }

    public function render_admin_panel($atts) {
        // فقط برای کاربران ادمین قابل نمایش است
        if (!current_user_can('manage_options')) {
            return '<div class="discount-admin-error">شما دسترسی لازم برای مشاهده این صفحه را ندارید.</div>';
        }

        // دریافت لیست سرویس‌های فعال
        $service_manager = AI_Assistant_Service_Manager::get_instance();
        $services = $service_manager->get_active_services();

        ob_start();
        ?>
        <div class="discount-admin-panel">
            <div class="discount-admin-panel-header">
                <h2>مدیریت کدهای تخفیف</h2>
                <div class="discount-admin-user-info">
                    <span>خوش آمدید، <?php echo wp_get_current_user()->display_name; ?></span>
                </div>
            </div>

            <!-- دکمه ایجاد تخفیف جدید -->
            <div class="discount-actions-top">
                <button class="discount-btn discount-btn-primary" id="create-discount-btn">
                    <i class="fas fa-plus"></i> ایجاد کد تخفیف جدید
                </button>
                
                <div class="discount-stats-summary">
                    <span class="discount-stat-item">
                        <strong id="active-count">0</strong> فعال
                    </span>
                    <span class="discount-stat-item">
                        <strong id="inactive-count">0</strong> غیرفعال
                    </span>
                    <span class="discount-stat-item">
                        <strong id="expired-count">0</strong> منقضی شده
                    </span>
                    <span class="discount-stat-item">
                        <strong id="total-count">0</strong> کل
                    </span>
                </div>
            </div>

            <!-- فیلترها و جستجو -->
            <div class="discount-filters">
                <div class="filter-group">
                    <label for="discount-status-filter">وضعیت:</label>
                    <select id="discount-status-filter">
                        <option value="all">همه</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="discount-type-filter">نوع:</label>
                    <select id="discount-type-filter">
                        <option value="all">همه</option>
                        <option value="percentage">درصدی</option>
                        <option value="fixed">مبلغی</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="text" id="discount-search" placeholder="جستجو در کدها و نام‌ها...">
                    <button class="discount-btn discount-btn-info" id="search-discounts">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- لیست کدهای تخفیف -->
            <div class="discount-list-container">
                <div id="discounts-list" class="discounts-list">
                    <div class="discounts-loading">در حال بارگذاری کدهای تخفیف...</div>
                </div>
            </div>

            <!-- Modal برای ایجاد/ویرایش تخفیف -->
            <div id="discount-modal" class="discount-modal">
                <div class="discount-modal-content">
                    <span class="discount-close-modal">&times;</span>
                    <h3 id="discount-modal-title">ایجاد کد تخفیف جدید</h3>
                    
                    <form id="discount-form" class="discount-form">
                        <input type="hidden" id="discount-id" name="discount_id" value="">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="discount-scope">حوزه اعتبار *</label>
                                <select id="discount-scope" name="scope" required>
                                    <option value="coupon">کد کوپن</option>
                                    <option value="global">عمومی (همه سرویس‌ها)</option>
                                    <option value="service">مخصوص سرویس</option>
                                    <option value="user_based">مبتنی بر کاربر</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount-type">نوع تخفیف *</label>
                                <select id="discount-type" name="type" required>
                                    <option value="percentage">درصدی</option>
                                    <option value="fixed">مبلغی (تومان)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="discount-name">نام تخفیف *</label>
                                <input type="text" id="discount-name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount-amount">مقدار تخفیف *</label>
                                <input type="number" id="discount-amount" name="amount" min="1" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="discount-code">کد تخفیف (اختیاری)</label>
                                <div class="code-input-container">
                                    <input type="text" id="discount-code" name="code" placeholder="در صورت خالی بودن، تخفیف بدون کد اعمال می‌شود">
                                    <button type="button" id="generate-code" class="discount-btn discount-btn-secondary">
                                        <i class="fas fa-sync-alt"></i> تولید کد
                                    </button>
                                </div>
                                <small class="form-help">اگر این فیلد خالی باشد، تخفیف به صورت خودکار برای کاربران اعمال می‌شود</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount-usage-limit">حداکثر استفاده (0 = نامحدود)</label>
                                <input type="number" id="discount-usage-limit" name="usage_limit" min="0" value="0">
                            </div>
                        </div>

                        <!-- سرویس‌های مرتبط (فقط برای scope=service) -->
                        <div class="form-row" id="services-section" style="display: none;">
                            <div class="form-group full-width">
                                <label>سرویس‌های مرتبط</label>
                                <div class="services-checkbox-list">
                                    <?php foreach ($services as $service_id => $service): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="services[]" value="<?php echo esc_attr($service_id); ?>">
                                            <?php echo esc_html($service['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- محدودیت کاربر (فقط برای scope=user_based) -->
                        <div class="form-row" id="user-restriction-section" style="display: none;">
                            <div class="form-group">
                                <label for="discount-user-restriction">محدودیت کاربر</label>
                                <select id="discount-user-restriction" name="user_restriction">
                                    <option value="first_time">اولین خرید از سرویس</option>
                                    <option value="specific_users">کاربران خاص</option>
                                </select>
                            </div>
                            
                            <!-- بخش انتخاب کاربران (فقط وقتی specific_users انتخاب شود نمایش داده می‌شود) -->
                            <div class="form-group full-width" id="specific-users-section" style="display: none;">
                                <label for="discount-specific-users">انتخاب کاربران</label>
                                <div id="users-loading" class="loading" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> در حال بارگذاری کاربران...
                                </div>
                                
                                <!-- جستجو در لیست کاربران -->
                                <div class="users-search-container">
                                    <input type="text" id="users-search" placeholder="جستجو در کاربران..." style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0; border-radius: 4px;">
                                </div>
                                
                                <!-- دکمه‌های انتخاب سریع -->
                                <div class="users-quick-actions" style="margin-bottom: 10px; display: flex; gap: 10px;">
                                    <button type="button" id="select-all-users" class="discount-btn discount-btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                        انتخاب همه
                                    </button>
                                    <button type="button" id="deselect-all-users" class="discount-btn discount-btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                        لغو انتخاب همه
                                    </button>
                                </div>
                                
                                <!-- لیست چک‌باکس کاربران -->
                                <div id="users-checkbox-list" class="users-checkbox-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px;">
                                    <div class="no-users-message">در حال بارگذاری کاربران...</div>
                                </div>
                                
                                <div id="selected-users-count" style="margin-top: 10px; font-size: 12px; color: #4a5568;">
                                    هیچ کاربری انتخاب نشده است
                                </div>
                                
                                <small class="form-help">می‌توانید کاربران مورد نظر خود را انتخاب کنید</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="discount-start-date">تاریخ شروع (اختیاری)</label>
                                <input type="datetime-local" id="discount-start-date" name="start_date">
                            </div>
                            
                            <div class="form-group">
                                <label for="discount-end-date">تاریخ انقضا (اختیاری)</label>
                                <input type="datetime-local" id="discount-end-date" name="end_date">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="discount-btn discount-btn-success">
                                <i class="fas fa-save"></i> ذخیره
                            </button>
                            <button type="button" class="discount-btn discount-btn-secondary" id="cancel-discount">
                                انصراف
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal برای نمایش جزئیات -->
            <div id="discount-details-modal" class="discount-modal">
                <div class="discount-modal-content">
                    <span class="discount-close-modal">&times;</span>
                    <div id="discount-details-content"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_create_discount() {
        $this->verify_nonce_and_permissions();
        
        try {
            $data = $this->validate_discount_data($_POST);
            if (is_wp_error($data)) {
                error_log('خطا در اعتبارسنجی داده‌ها: ' . $data->get_error_message());
                wp_send_json_error($data->get_error_message());
            }
            
            error_log('داده‌های معتبر شده: ' . print_r($data, true));
            
            $discount_id = $this->discount_db->add_discount($data);
            
            if ($discount_id) {
                error_log('تخفیف با موفقیت ایجاد شد. ID: ' . $discount_id);
                
                // اضافه کردن سرویس‌های مرتبط (فقط برای scope=service)
                if ($data['scope'] === 'service' && isset($_POST['services']) && is_array($_POST['services'])) {
                    foreach ($_POST['services'] as $service_id) {
                        $result = $this->discount_db->add_discount_service($discount_id, sanitize_text_field($service_id));
                        error_log('افزودن سرویس ' . $service_id . ': ' . ($result ? 'موفق' : 'ناموفق'));
                    }
                }
                
                // اضافه کردن کاربران خاص (فقط برای scope=user_based و user_restriction=specific_users)
                if ($data['scope'] === 'user_based' && 
                    $data['user_restriction'] === 'specific_users' &&
                    isset($_POST['specific_users']) && 
                    is_array($_POST['specific_users'])) {
                    
                    foreach ($_POST['specific_users'] as $user_id) {
                        $user_id = intval($user_id);
                        if ($user_id > 0) {
                            $result = $this->discount_db->add_discount_user($discount_id, $user_id);
                            error_log('افزودن کاربر ' . $user_id . ': ' . ($result ? 'موفق' : 'ناموفق'));
                        }
                    }
                }
                
                wp_send_json_success([
                    'message' => 'کد تخفیف با موفقیت ایجاد شد.',
                    'discount_id' => $discount_id
                ]);
            } else {
                global $wpdb;
                error_log('خطای دیتابیس: ' . $wpdb->last_error);
                wp_send_json_error('خطا در ایجاد کد تخفیف در دیتابیس.');
            }
        } catch (Exception $e) {
            error_log('خطای غیرمنتظره: ' . $e->getMessage());
            wp_send_json_error('خطای غیرمنتظره در ایجاد کد تخفیف.');
        }
    }

    public function handle_update_discount() {
        $this->verify_nonce_and_permissions();
        
        try {
            $discount_id = intval($_POST['discount_id']);
            if (!$discount_id) {
                error_log('خطا: شناسه تخفیف معتبر نیست');
                wp_send_json_error('شناسه تخفیف معتبر نیست.');
            }
            
            error_log('شروع آپدیت تخفیف با ID: ' . $discount_id);
            error_log('داده‌های POST: ' . print_r($_POST, true));
            
            $data = $this->validate_discount_data($_POST);
            if (is_wp_error($data)) {
                error_log('خطا در اعتبارسنجی داده‌ها: ' . $data->get_error_message());
                wp_send_json_error($data->get_error_message());
            }
            
            error_log('داده‌های معتبر شده برای آپدیت: ' . print_r($data, true));
            
            // فقط فیلدهای اصلی را برای آپدیت بفرستیم
            $update_data = [
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'scope' => $data['scope'],
                'usage_limit' => $data['usage_limit'],
                'user_restriction' => $data['user_restriction'],
                'active' => $data['active']
            ];
            
            // اضافه کردن فیلدهای اختیاری اگر مقدار دارند
            if (!empty($data['start_date'])) {
                $update_data['start_date'] = $data['start_date'];
            } else {
                $update_data['start_date'] = null;
            }
            
            if (!empty($data['end_date'])) {
                $update_data['end_date'] = $data['end_date'];
            } else {
                $update_data['end_date'] = null;
            }
            
            error_log('داده‌های نهایی برای آپدیت: ' . print_r($update_data, true));
            
            $result = $this->discount_db->update_discount($discount_id, $update_data);
            
            error_log('نتیجه آپدیت: ' . ($result !== false ? 'موفق' : 'ناموفق'));
            
            if ($result !== false) {
                // به‌روزرسانی سرویس‌های مرتبط - فقط اگر scope = service باشد
                $this->discount_db->delete_discount_services($discount_id);
                if ($data['scope'] === 'service' && isset($_POST['services']) && is_array($_POST['services'])) {
                    foreach ($_POST['services'] as $service_id) {
                        $service_result = $this->discount_db->add_discount_service($discount_id, sanitize_text_field($service_id));
                        error_log('افزودن سرویس ' . $service_id . ': ' . ($service_result ? 'موفق' : 'ناموفق'));
                    }
                }
                
                // به‌روزرسانی کاربران خاص - فقط اگر scope = user_based باشد
                $this->discount_db->delete_discount_users($discount_id);
                if ($data['scope'] === 'user_based' && 
                    $data['user_restriction'] === 'specific_users' &&
                    isset($_POST['specific_users']) && 
                    is_array($_POST['specific_users'])) {
                    
                    foreach ($_POST['specific_users'] as $user_id) {
                        $user_id = intval($user_id);
                        if ($user_id > 0) {
                            $user_result = $this->discount_db->add_discount_user($discount_id, $user_id);
                            error_log('افزودن کاربر ' . $user_id . ': ' . ($user_result ? 'موفق' : 'ناموفق'));
                        }
                    }
                }
                
                wp_send_json_success([
                    'message' => 'کد تخفیف با موفقیت به‌روزرسانی شد.'
                ]);
            } else {
                global $wpdb;
                error_log('خطای دیتابیس در آپدیت: ' . $wpdb->last_error);
                wp_send_json_error('خطا در به‌روزرسانی کد تخفیف.');
            }
        } catch (Exception $e) {
            error_log('خطای غیرمنتظره در آپدیت: ' . $e->getMessage());
            wp_send_json_error('خطای غیرمنتظره در به‌روزرسانی کد تخفیف.');
        }
    }

    public function handle_delete_discount() {
        $this->verify_nonce_and_permissions();
        
        $discount_id = intval($_POST['discount_id']);
        $result = $this->discount_db->delete_discount($discount_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'کد تخفیف با موفقیت حذف شد.' // اضافه کردن آرایه با کلید message
            ]);
        } else {
            wp_send_json_error('خطا در حذف کد تخفیف.');
        }
    }

    public function handle_toggle_status() {
        $this->verify_nonce_and_permissions();
        
        $discount_id = intval($_POST['discount_id']);
        $current_status = $this->get_discount_status($discount_id);
        $new_status = $current_status ? 0 : 1;
        
        $result = $this->discount_db->update_discount($discount_id, ['active' => $new_status]);
        
        if ($result !== false) {
            $action = $new_status ? 'فعال' : 'غیرفعال';
            wp_send_json_success([
                'message' => "کد تخفیف با موفقیت {$action} شد." // اضافه کردن آرایه با کلید message
            ]);
        } else {
            wp_send_json_error('خطا در تغییر وضعیت کد تخفیف.');
        }
    }

    public function handle_get_discounts() {
        $this->verify_nonce_and_permissions();
        
        $filters = [
            'status' => sanitize_text_field($_POST['status'] ?? 'all'),
            'type' => sanitize_text_field($_POST['type'] ?? 'all'),
            'search' => sanitize_text_field($_POST['search'] ?? '')
        ];
        
        $discounts = $this->get_filtered_discounts($filters);
        $stats = $this->get_discounts_stats();
        
        ob_start();
        $this->render_discounts_list($discounts);
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'stats' => $stats
        ]);
    }
    
    public function handle_get_discount_details() {
        $this->verify_nonce_and_permissions();
        
        $discount_id = intval($_POST['discount_id']);
        $discount = $this->discount_db->get_discount($discount_id);
        
        if (!$discount) {
            wp_send_json_error('کد تخفیف یافت نشد.');
        }
        
        // اگر برای ویرایش درخواست شده
        if (isset($_POST['for_edit']) && $_POST['for_edit'] === 'true') {
            // دریافت کاربران مرتبط - فرمت صحیح
            $discount_users = $this->discount_db->get_discount_users($discount_id);
            $discount->users = [];
            
            foreach ($discount_users as $user_id) {
                $discount->users[] = $user_id; // فقط ID کاربر را ذخیره کن
            }
            
            wp_send_json_success(['discount' => $discount]);
        }
        
        // اگر برای نمایش جزئیات درخواست شده
        ob_start();
        $this->render_discount_details($discount);
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }

    private function verify_nonce_and_permissions() {
        check_ajax_referer('discount_frontend_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
        }
    }

    private function validate_discount_data($post_data) {
        $required_fields = ['name', 'type', 'amount', 'scope'];
        
        foreach ($required_fields as $field) {
            if (empty($post_data[$field])) {
                return new WP_Error('missing_field', "فیلد {$field} الزامی است.");
            }
        }
        
        $data = [
            'name' => sanitize_text_field($post_data['name']),
            'code' => !empty($post_data['code']) ? sanitize_text_field($post_data['code']) : '',
            'type' => sanitize_text_field($post_data['type']),
            'amount' => floatval($post_data['amount']),
            'scope' => sanitize_text_field($post_data['scope']),
            'usage_limit' => intval($post_data['usage_limit'] ?? 0),
            'user_restriction' => !empty($post_data['user_restriction']) ? sanitize_text_field($post_data['user_restriction']) : null,
            'start_date' => !empty($post_data['start_date']) ? sanitize_text_field($post_data['start_date']) : null,
            'end_date' => !empty($post_data['end_date']) ? sanitize_text_field($post_data['end_date']) : null,
            'active' => 1
        ];
        
        // اعتبارسنجی مقادیر
        if ($data['amount'] <= 0) {
            return new WP_Error('invalid_amount', 'مقدار تخفیف باید بیشتر از صفر باشد.');
        }
        
        if ($data['type'] === 'percentage' && $data['amount'] > 100) {
            return new WP_Error('invalid_percentage', 'تخفیف درصدی نمی‌تواند بیشتر از ۱۰۰٪ باشد.');
        }
        
        // اگر scope برابر user_based است، user_restriction باید تنظیم شود
        if ($data['scope'] === 'user_based' && empty($data['user_restriction'])) {
            return new WP_Error('missing_user_restriction', 'برای تخفیف مبتنی بر کاربر، نوع محدودیت کاربر باید مشخص شود.');
        }
        
        return $data;
    }

    private function get_discount_status($discount_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT active FROM {$this->discount_db->get_table_name()} WHERE id = %d",
            $discount_id
        ));
    }

    private function get_filtered_discounts($filters) {
        $all_discounts = $this->discount_db->get_all_discounts();
        $filtered = [];
        
        foreach ($all_discounts as $discount) {
            // فیلتر وضعیت
            if ($filters['status'] !== 'all') {
                $is_really_active = $this->is_discount_active($discount);
                
                if ($filters['status'] === 'active' && !$is_really_active) {
                    continue;
                }
                
                if ($filters['status'] === 'inactive' && $is_really_active) {
                    continue;
                }
            }
            
            // فیلتر نوع
            if ($filters['type'] !== 'all' && $discount->type !== $filters['type']) {
                continue;
            }
            
            // فیلتر جستجو
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $name_contains = stripos($discount->name, $search) !== false;
                $code_contains = stripos($discount->code, $search) !== false;
                
                if (!$name_contains && !$code_contains) continue;
            }
            
            $filtered[] = $discount;
        }
        
        return $filtered;
    }

    private function get_discounts_stats() {
        $all_discounts = $this->discount_db->get_all_discounts();
        
        $stats = [
            'active' => 0,
            'inactive' => 0,
            'expired' => 0,
            'total' => count($all_discounts)
        ];
        
        foreach ($all_discounts as $discount) {
            $is_really_active = $this->is_discount_active($discount);
            
            if ($is_really_active) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
                
                // شمارش تخفیف‌های منقضی شده
                $now = current_time('mysql');
                if ($discount->active && $discount->end_date && $discount->end_date < $now) {
                    $stats['expired']++;
                }
            }
        }
        
        return $stats;
    }

    private function render_discounts_list($discounts) {
        if (empty($discounts)) {
            echo '<div class="no-discounts">هیچ کد تخفیفی یافت نشد.</div>';
            return;
        }

        foreach ($discounts as $discount) {
            $this->render_discount_item($discount);
        }
    }
    
    private function render_discount_item($discount) {
        $is_really_active = $this->is_discount_active($discount);
        $status_class = $is_really_active ? 'active' : 'inactive';
        $status_text = $is_really_active ? 'فعال' : 'غیرفعال';
        
        // اضافه کردن توضیح اگر تاریخ انقضا گذشته باشد
        $status_note = '';
        $now = current_time('mysql');
        if ($discount->active && $discount->end_date && $discount->end_date < $now) {
            $status_text = 'منقضی شده';
            $status_class = 'expired';
            $status_note = ' (تاریخ انقضا گذشته)';
        }
        
        $type_text = $discount->type === 'percentage' ? '٪' : 'تومان';
        $code_display = empty($discount->code) ? '<em>بدون کد (خودکار)</em>' : esc_html($discount->code);
        $has_code = !empty($discount->code);
        
        $services = $this->discount_db->get_discount_services($discount->id);
        
        // تعیین متن مناسب بر اساس حوزه اعتبار
        $scope_info = '';
        switch ($discount->scope) {
            case 'service':
                $services_text = empty($services) ? 'همه سرویس‌ها' : implode(', ', array_slice($services, 0, 3)) . (count($services) > 3 ? '...' : '');
                $scope_info = '<div class="discount-services"><strong>سرویس‌ها:</strong> ' . esc_html($services_text) . '</div>';
                break;
                
            case 'user_based':
                $restriction_text = ($discount->user_restriction === 'first_time') ? 'اولین خرید' : 'کاربران خاص';
                $scope_info = '<div class="discount-users"><strong>محدودیت:</strong> ' . esc_html($restriction_text) . '</div>';
                break;
                
            case 'global':
                $scope_info = '<div class="discount-scope-info"><strong>حوزه:</strong> همه سرویس‌ها</div>';
                break;
                
            case 'coupon':
                $scope_info = '<div class="discount-scope-info"><strong>نوع:</strong> کد کوپن</div>';
                break;
                
        }
        ?>
        <div class="discount-item" data-discount-id="<?php echo $discount->id; ?>">
            <div class="discount-header">
                <div class="discount-info">
                    <h4 class="discount-name"><?php echo esc_html($discount->name); ?></h4>
                    <div class="discount-code-container">
                        <code class="discount-code"><?php echo $code_display; ?></code>
                        <?php if ($has_code): ?>
                        <button class="discount-copy-code" data-code="<?php echo esc_attr($discount->code); ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="discount-meta">
                    <span class="discount-status <?php echo $status_class; ?>">
                        <i class="fas fa-circle"></i> <?php echo $status_text . $status_note; ?>
                    </span>
                    <span class="discount-amount">
                        <?php echo number_format($discount->amount); ?> <?php echo $type_text; ?>
                    </span>
                    <span class="discount-usage">
                        استفاده: <?php echo $discount->usage_count; ?> از 
                        <?php echo $discount->usage_limit ?: '∞'; ?>
                    </span>
                </div>
            </div>
            
            <div class="discount-details">
                <div class="discount-scope">
                    <strong>حوزه:</strong> <?php echo $this->get_scope_text($discount->scope); ?>
                </div>
                <?php echo $scope_info; ?>
                <div class="discount-dates">
                    <?php if ($discount->start_date): ?>
                        <span class="discount-date">شروع: <?php echo $this->format_date($discount->start_date); ?></span>
                    <?php endif; ?>
                    <?php if ($discount->end_date): ?>
                        <span class="discount-date">انقضا: <?php echo $this->format_date($discount->end_date); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="discount-actions">
                <button class="discount-btn discount-btn-info discount-view-details" data-discount-id="<?php echo $discount->id; ?>">
                    <i class="fas fa-eye"></i> جزئیات
                </button>
                
                <button class="discount-btn discount-btn-warning discount-edit" data-discount-id="<?php echo $discount->id; ?>">
                    <i class="fas fa-edit"></i> ویرایش
                </button>
                
                <?php if ($discount->active): ?>
                    <button class="discount-btn discount-btn-secondary discount-toggle-status" data-discount-id="<?php echo $discount->id; ?>">
                        <i class="fas fa-pause"></i> غیرفعال
                    </button>
                <?php else: ?>
                    <button class="discount-btn discount-btn-success discount-toggle-status" data-discount-id="<?php echo $discount->id; ?>">
                        <i class="fas fa-play"></i> فعال
                    </button>
                <?php endif; ?>
                
                <button class="discount-btn discount-btn-danger discount-delete" data-discount-id="<?php echo $discount->id; ?>">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
        <?php
    }

    private function render_discount_details($discount) {
        $services = $this->discount_db->get_discount_services($discount->id);
        $type_text = $discount->type === 'percentage' ? '٪' : 'تومان';
        $date_helper = AI_Assistant_Persian_Date_Helper::get_instance();
        ?>
        <h3>جزئیات کد تخفیف</h3>
        <div class="discount-details-content">
            <div class="detail-row">
                <strong>نام:</strong> <?php echo esc_html($discount->name); ?>
            </div>
            <div class="detail-row">
                <strong>کد:</strong> <code><?php echo esc_html($discount->code); ?></code>
            </div>
            <div class="detail-row">
                <strong>نوع:</strong> <?php echo $discount->type === 'percentage' ? 'درصدی' : 'مبلغی'; ?>
            </div>
            <div class="detail-row">
                <strong>مقدار:</strong> <?php echo number_format($discount->amount); ?> <?php echo $type_text; ?>
            </div>
            <div class="detail-row">
                <strong>حوزه اعتبار:</strong> <?php echo $this->get_scope_text($discount->scope); ?>
            </div>
            <div class="detail-row">
                <strong>وضعیت:</strong> 
                <span class="discount-status <?php echo $discount->active ? 'active' : 'inactive'; ?>">
                    <?php echo $discount->active ? 'فعال' : 'غیرفعال'; ?>
                </span>
            </div>
            <div class="detail-row">
                <strong>تعداد استفاده:</strong> 
                <?php echo $discount->usage_count; ?> از <?php echo $discount->usage_limit ?: 'نامحدود'; ?>
            </div>
        
            <?php if ($discount->scope === 'service' && !empty($services)): ?>
            <div class="detail-row">
                <strong>سرویس‌های مرتبط:</strong>
                <ul class="services-list">
                    <?php foreach ($services as $service_id): 
                        $service_manager = AI_Assistant_Service_Manager::get_instance();
                        $services_list = $service_manager->get_active_services();
                        $service_name = isset($services_list[$service_id]) ? $services_list[$service_id]['name'] : $service_id;
                    ?>
                        <li><?php echo esc_html($service_name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
                        
            <?php if ($discount->scope === 'user_based' && $discount->user_restriction === 'specific_users'): ?>
            <div class="detail-row">
                <strong>کاربران خاص:</strong>
                <?php 
                $users = $this->discount_db->get_discount_users($discount->id);
                if (!empty($users)): 
                ?>
                    <ul class="users-list">
                        <?php foreach ($users as $user_id): 
                            $user = get_userdata($user_id);
                            if ($user):
                                $first_name = get_user_meta($user_id, 'first_name', true);
                                $last_name = get_user_meta($user_id, 'last_name', true);
                                $phone = get_user_meta($user_id, 'billing_phone', true);
                                
                                $full_name = trim($first_name . ' ' . $last_name);
                                if (empty($full_name)) {
                                    $full_name = $user->display_name;
                                }
                        ?>
                            <li>
                                <strong><?php echo esc_html($full_name); ?></strong>
                                <?php if (!empty($phone)): ?>
                                    <div class="user-details">
                                        <span>📱 <?php echo esc_html($phone); ?></span>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php 
                            endif;
                        endforeach; ?>
                    </ul>
                <?php else: ?>
                    <span style="color: #718096;">هیچ کاربری انتخاب نشده است.</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        
            <?php if (!empty($services)): ?>
            <div class="detail-row">
                <strong>سرویس‌های مرتبط:</strong>
                <ul class="services-list">
                    <?php foreach ($services as $service_id): 
                        $service_manager = AI_Assistant_Service_Manager::get_instance();
                        $services_list = $service_manager->get_active_services();
                        $service_name = isset($services_list[$service_id]) ? $services_list[$service_id]['name'] : $service_id;
                    ?>
                        <li><?php echo esc_html($service_name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($discount->start_date): ?>
            <div class="detail-row">
                <strong>تاریخ شروع:</strong> <?php echo $this->format_date($discount->start_date); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($discount->end_date): ?>
            <div class="detail-row">
                <strong>تاریخ انقضا:</strong> <?php echo $this->format_date($discount->end_date); ?>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <strong>تاریخ ایجاد:</strong> <?php echo $this->format_date($discount->created_at); ?>
            </div>
        </div>
        <?php
    }

    private function get_scope_text($scope) {
        $scopes = [
            'global' => 'عمومی',
            'service' => 'مخصوص سرویس',
            'coupon' => 'کد کوپن',
            'user_based' => 'مبتنی بر کاربر',
        ];
        
        return $scopes[$scope] ?? $scope;
    }

    private function format_date($date_string) {
        return date_i18n('j F Y H:i', strtotime($date_string));
    }
}

// مقداردهی اولیه سیستم
function init_ai_assistant_discount_frontend_admin() {
    AI_Assistant_Discount_Frontend_Admin::get_instance();
}
add_action('init', 'init_ai_assistant_discount_frontend_admin');