<?php
/**
 * مدیریت نظرات در Front-end برای ادمین
 */

if (!defined('ABSPATH')) exit;

class AI_Assistant_Comments_Frontend_Admin {
    private static $instance = null;
    private $comments_db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // در متد __construct کلاس AI_Assistant_Comments_Frontend_Admin
    private function __construct() {
        // بررسی وجود کلاس Comments DB
        if (!class_exists('AI_Assistant_Comments_DB')) {
            require_once get_template_directory() . '/inc/class-comments-db.php';
        }
        
        $this->comments_db = AI_Assistant_Comments_DB::get_instance();
        $this->init_hooks();
    }

    private function init_hooks() {
        // اضافه کردن shortcode برای نمایش پنل مدیریت
        add_shortcode('service_comments_admin', [$this, 'render_admin_panel']);
        
        // ثبت اسکریپت‌ها و استایل‌ها
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // هندلرهای AJAX
        add_action('wp_ajax_approve_service_comment', [$this, 'handle_approve_comment']);
        add_action('wp_ajax_reject_service_comment', [$this, 'handle_reject_comment']);
        add_action('wp_ajax_delete_service_comment', [$this, 'handle_delete_comment']);
        add_action('wp_ajax_get_comments_for_admin', [$this, 'handle_get_comments']);
    }

    public function enqueue_assets() {
        // فقط در صفحاتی که پنل مدیریت نمایش داده می‌شود
        if (is_page() && has_shortcode(get_post()->post_content, 'service_comments_admin')) {
            wp_enqueue_style('ai-comments-frontend-admin-css', 
                get_template_directory_uri() . '/assets/css/comments-frontend-admin.css',
                [],
                filemtime(get_template_directory() . '/assets/css/comments-frontend-admin.css')
            );

            wp_enqueue_script('ai-comments-frontend-admin-js',
                get_template_directory_uri() . '/assets/js/comments-frontend-admin.js',
                ['jquery'],
                filemtime(get_template_directory() . '/assets/js/comments-frontend-admin.js'),
                true
            );

            wp_localize_script('ai-comments-frontend-admin-js', 'commentsFrontendAdminVars', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('comments_frontend_admin_nonce'),
                'i18n' => [
                    'confirm_delete' => 'آیا از حذف این نظر مطمئن هستید؟',
                    'error' => 'خطا در انجام عملیات',
                    'loading' => 'در حال بارگذاری...'
                ]
            ]);
        }
    }

    public function render_admin_panel($atts) {
        // فقط برای کاربران ادمین قابل نمایش است
        if (!current_user_can('manage_options')) {
            return '<div class="comments-admin-error">شما دسترسی لازم برای مشاهده این صفحه را ندارید.</div>';
        }

        ob_start();
        ?>
        <div class="comments-admin-panel">
            <div class="comments-admin-panel-header">
                <h2>مدیریت نظرات سرویس‌ها</h2>
                <div class="comments-admin-user-info">
                    <span>خوش آمدید، <?php echo wp_get_current_user()->display_name; ?></span>
                </div>
            </div>

            <div class="comments-tabs">
                <button class="comments-tab-button active" data-tab="pending">نظرات در انتظار تایید</button>
                <button class="comments-tab-button" data-tab="approved">نظرات تایید شده</button>
                <button class="comments-tab-button" data-tab="rejected">نظرات رد شده</button>
                
                <div class="comments-stats-summary">
                    <span class="comments-stat-item">
                        <strong id="pending-count">0</strong> در انتظار
                    </span>
                    <span class="comments-stat-item">
                        <strong id="approved-count">0</strong> تایید شده
                    </span>
                    <span class="comments-stat-item">
                        <strong id="rejected-count">0</strong> رد شده
                    </span>
                </div>
            </div>

            <div class="comments-tab-content">
                <div id="pending-tab" class="comments-tab-pane active">
                    <div class="comments-list-container">
                        <div id="pending-comments-list" class="comments-list">
                            <div class="comments-loading">در حال بارگذاری نظرات...</div>
                        </div>
                    </div>
                </div>
                
                <div id="approved-tab" class="comments-tab-pane">
                    <div class="comments-list-container">
                        <div id="approved-comments-list" class="comments-list"></div>
                    </div>
                </div>
                
                <div id="rejected-tab" class="comments-tab-pane">
                    <div class="comments-list-container">
                        <div id="rejected-comments-list" class="comments-list"></div>
                    </div>
                </div>
            </div>

            <!-- Modal برای نمایش جزئیات نظر -->
            <div id="comments-modal" class="comments-modal">
                <div class="comments-modal-content">
                    <span class="comments-close-modal">&times;</span>
                    <div id="comments-modal-comment-details"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // هندلرهای AJAX
    public function handle_approve_comment() {
        $this->verify_nonce_and_permissions();
        
        $comment_id = intval($_POST['comment_id']);
        $result = $this->update_comment_status($comment_id, 'approved');
        
        if ($result) {
            // آمار به روز شده را برگردان
            $stats = $this->get_comments_stats();
            wp_send_json_success([
                'message' => 'نظر با موفقیت تایید شد.',
                'stats' => $stats
            ]);
        } else {
            wp_send_json_error('خطا در تایید نظر.');
        }
    }

    public function handle_reject_comment() {
        $this->verify_nonce_and_permissions();
        
        $comment_id = intval($_POST['comment_id']);
        $result = $this->update_comment_status($comment_id, 'rejected');
        
        if ($result) {
            $stats = $this->get_comments_stats();
            wp_send_json_success([
                'message' => 'نظر با موفقیت رد شد.',
                'stats' => $stats
            ]);
        } else {
            wp_send_json_error('خطا در رد نظر.');
        }
    }

    public function handle_delete_comment() {
        $this->verify_nonce_and_permissions();
        
        $comment_id = intval($_POST['comment_id']);
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->comments_db->get_table_name(),
            ['comment_id' => $comment_id],
            ['%d']
        );
        
        if ($result) {
            $stats = $this->get_comments_stats();
            wp_send_json_success([
                'message' => 'نظر با موفقیت حذف شد.',
                'stats' => $stats
            ]);
        } else {
            wp_send_json_error('خطا در حذف نظر.');
        }
    }

    public function handle_get_comments() {
        $this->verify_nonce_and_permissions();
        
        $status = sanitize_text_field($_POST['status']);
        $comments = $this->get_comments_by_status($status);
        $stats = $this->get_comments_stats();
        
        ob_start();
        $this->render_comments_list($comments, $status);
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'stats' => $stats
        ]);
    }

    private function verify_nonce_and_permissions() {
        check_ajax_referer('comments_frontend_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
        }
    }

    private function update_comment_status($comment_id, $status) {
        global $wpdb;
        
        return $wpdb->update(
            $this->comments_db->get_table_name(),
            ['status' => $status],
            ['comment_id' => $comment_id],
            ['%s'],
            ['%d']
        );
    }

    private function get_comments_by_status($status) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.user_login, u.display_name 
             FROM {$this->comments_db->get_table_name()} c 
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
             WHERE c.status = %s 
             ORDER BY c.created_at DESC",
            $status
        ));
    }

    private function get_comments_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM {$this->comments_db->get_table_name()} 
            GROUP BY status
        ");
        
        $result = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];
        
        foreach ($stats as $stat) {
            $result[$stat->status] = intval($stat->count);
        }
        
        return $result;
    }

    private function render_comments_list($comments, $status) {
        if (empty($comments)) {
            echo '<div class="no-comments">هیچ نظری یافت نشد.</div>';
            return;
        }

        foreach ($comments as $comment) {
            $this->render_comment_item($comment, $status);
        }
    }

    private function render_comment_item($comment, $status) {
        $service_manager = AI_Assistant_Service_Manager::get_instance();
        $services = $service_manager->get_active_services();
        $service_name = isset($services[$comment->service_id]) ? 
            $services[$comment->service_id]['name'] : $comment->service_id;
        
        $author_name = $comment->display_name ?: $comment->user_login;
        // اگر شماره موبایل باشد، فرمت شود
        if (preg_match('/^09\d{9}$/', $author_name)) {
            $author_name = substr($author_name, 0, 4) . '***' . substr($author_name, 7);
        }
        ?>
        <div class="comments-item" data-comment-id="<?php echo $comment->comment_id; ?>">
            <div class="comments-header">
                <?php
                $author_name = $comment->display_name ?: $comment->user_login;
                ?>
                
                <div class="comments-author-info">
                    <span class="comments-author"><?php echo esc_html($author_name); ?></span>
                    <span class="comments-service">سرویس: <?php echo esc_html($service_name); ?></span>
                </div>
                <div class="comments-meta">
                    <span class="comments-date"><?php echo date_i18n('j F Y', strtotime($comment->created_at)); ?></span>
                    <span class="comments-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="comments-star <?php echo $i <= $comment->rating ? 'active' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </span>
                </div>
            </div>
            
            <div class="comments-text"><?php echo esc_html($comment->comment_text); ?></div>
            
            <div class="comments-actions">
                <?php if ($status === 'pending'): ?>
                    <button class="comments-btn comments-btn-success comments-approve-comment" data-comment-id="<?php echo $comment->comment_id; ?>">
                        <i class="fas fa-check"></i> تایید
                    </button>
                    <button class="comments-btn comments-btn-warning comments-reject-comment" data-comment-id="<?php echo $comment->comment_id; ?>">
                        <i class="fas fa-times"></i> رد
                    </button>
                <?php elseif ($status === 'approved'): ?>
                    <button class="comments-btn comments-btn-warning comments-reject-comment" data-comment-id="<?php echo $comment->comment_id; ?>">
                        <i class="fas fa-times"></i> برگشت به انتظار
                    </button>
                <?php elseif ($status === 'rejected'): ?>
                    <button class="comments-btn comments-btn-success comments-approve-comment" data-comment-id="<?php echo $comment->comment_id; ?>">
                        <i class="fas fa-check"></i> تایید
                    </button>
                <?php endif; ?>
                
                <button class="comments-btn comments-btn-danger comments-delete-comment" data-comment-id="<?php echo $comment->comment_id; ?>">
                    <i class="fas fa-trash"></i> حذف
                </button>
                
                <button class="comments-btn comments-btn-info comments-view-comment-details" data-comment-id="<?php echo $comment->comment_id; ?>">
                    <i class="fas fa-eye"></i> جزئیات
                </button>
            </div>
        </div>
        <?php
    }

    private function format_date($date_string) {
        $timestamp = strtotime($date_string);
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 3600) { // کمتر از 1 ساعت
            return ceil($diff / 60) . ' دقیقه پیش';
        } elseif ($diff < 86400) { // کمتر از 1 روز
            return ceil($diff / 3600) . ' ساعت پیش';
        } else {
            return date('Y/m/d H:i', $timestamp);
        }
    }
}