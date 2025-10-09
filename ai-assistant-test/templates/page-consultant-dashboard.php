<?php
/**
 * Template Name: کارتابل مشاور تغذیه
 */

// لود کردن کلاس‌های لازم
if (!defined('ABSPATH')) {
    exit;
}

if (
    !is_user_logged_in() || 
    ( !current_user_can('nutrition_consultant') && !current_user_can('administrator') )
) {
    wp_redirect(home_url());
    exit;
}


// غیرفعال کردن کش قبل از هر خروجی
if (!defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}

// غیرفعال کردن کش برای این صفحه
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// تابع نمایش درخواست‌ها - قبل از get_header()
function render_consultant_requests_table($requests, $status) {
    if (empty($requests)) {
        echo '<div class="consultant-no-requests">';
        echo '<i class="fas fa-inbox"></i>';
        echo '<p>هیچ درخواستی یافت نشد.</p>';
        echo '</div>';
        return;
    }
    ?>
    <div class="consultant-list-container">
        <div class="consultant-list">
            <?php foreach ($requests as $request) : 
                $user = get_user_by('id', $request->user_id);
                $history_manager = AI_Assistant_History_Manager::get_instance();
                $history_item = $history_manager->get_history_item($request->service_history_id);
                
                $is_deadline_passed = strtotime($request->deadline) < time() && $status !== 'approved';
            ?>
            <div class="consultant-item" data-request-id="<?php echo $request->id; ?>">
                <div class="consultant-header">
                    <div class="consultant-user-info">
                        <span class="consultant-user-name">
                            <i class="fas fa-user"></i>
                            <?php echo esc_html($user->display_name); ?>
                        </span>
                        <span class="consultant-service">
                            سرویس: <?php echo esc_html($history_item ? $history_item->service_name : 'نامشخص'); ?>
                        </span>
                    </div>
                    <div class="consultant-meta">
                        <span class="consultant-date">
                            <?php echo date_i18n('j F Y - H:i', strtotime($request->created_at)); ?>
                        </span>
                        <span class="consultant-deadline <?php echo $is_deadline_passed ? 'passed' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            <?php echo date_i18n('j F Y - H:i', strtotime($request->deadline)); ?>
                        </span>
                        <span class="consultant-status consultant-status-<?php echo $status; ?>">
                            <?php 
                            $status_texts = [
                                'pending' => 'در انتظار',
                                'under_review' => 'در حال بررسی',
                                'approved' => 'تایید شده'
                            ];
                            echo $status_texts[$status];
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="consultant-actions">
                    <button class="consultant-btn consultant-btn-primary review-button" data-request-id="<?php echo $request->id; ?>">
                        <i class="fas fa-edit"></i>
                        <?php echo $status === 'approved' ? 'مشاهده' : 'بررسی'; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

get_header();

$consultant_id = get_current_user_id();
$consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
$consultation_manager = AI_Assistant_Nutrition_Consultant_Manager::get_instance();

// دریافت آمار درخواست‌ها
$request_counts = $consultation_db->get_consultant_request_counts($consultant_id);

// دریافت درخواست‌ها بر اساس وضعیت
$pending_requests = $consultation_db->get_consultant_requests($consultant_id, 'pending');
$under_review_requests = $consultation_db->get_consultant_requests($consultant_id, 'under_review');
$approved_requests = $consultation_db->get_consultant_requests($consultant_id, 'approved');

// بارگذاری اسکریپت‌ها و استایل‌های جدید
wp_enqueue_style('consultant-dashboard-admin-css', 
    get_template_directory_uri() . '/assets/css/consultant-dashboard-admin.css',
    [],
    filemtime(get_template_directory() . '/assets/css/consultant-dashboard-admin.css')
);

wp_enqueue_script('consultant-dashboard-admin', 
    get_template_directory_uri() . '/assets/js/services/diet/consultant-dashboard-admin.js', 
    ['jquery'], 
    filemtime(get_template_directory() . '/assets/js/services/diet/consultant-dashboard-admin.js'), 
    true
);

wp_localize_script('consultant-dashboard-admin', 'consultant_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('consultation_review_nonce')
]);

// بارگذاری فونت آیکون و استایل‌ها
wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
wp_enqueue_style('diet-plan-css', get_template_directory_uri() . '/assets/css/services/diet-plan.css');

// بارگذاری اسکریپت کامپوننت
// در فایل page-consultant-dashboard.php، بعد از wp_enqueue_script موجود
wp_enqueue_style('consultant-diet-editor-css', 
    get_template_directory_uri() . '/assets/css/consultant-diet-editor.css',
    [],
    filemtime(get_template_directory() . '/assets/css/consultant-diet-editor.css')
);

wp_enqueue_script('consultant-diet-editor', 
    get_template_directory_uri() . '/assets/js/services/diet/consultant-diet-editor.js', 
    ['jquery'], 
    filemtime(get_template_directory() . '/assets/js/services/diet/consultant-diet-editor.js'), 
    true
);

?>

<div class="consultant-admin-panel">
    <div class="consultant-admin-panel-header">
        <h2>کارتابل مشاور تغذیه</h2>
        <div class="consultant-admin-user-info">
            <span>خوش آمدید، <?php echo wp_get_current_user()->display_name; ?></span>
        </div>
    </div>

    <div class="consultant-tabs">
        <button class="consultant-tab-button active" data-tab="pending">
            <i class="fas fa-clock"></i>
            در انتظار بازبینی
            <span class="consultant-stat-item">
                <strong><?php echo count($pending_requests); ?></strong>
            </span>
        </button>
        <button class="consultant-tab-button" data-tab="under_review">
            <i class="fas fa-edit"></i>
            در حال بازبینی
            <span class="consultant-stat-item">
                <strong><?php echo count($under_review_requests); ?></strong>
            </span>
        </button>
        <button class="consultant-tab-button" data-tab="approved">
            <i class="fas fa-check-circle"></i>
            تایید شده
            <span class="consultant-stat-item">
                <strong><?php echo count($approved_requests); ?></strong>
            </span>
        </button>
        
    </div>

    <div class="consultant-tab-content">
        <div id="pending-tab" class="consultant-tab-pane active">
            <?php render_consultant_requests_table($pending_requests, 'pending'); ?>
        </div>
        
        <div id="under_review-tab" class="consultant-tab-pane">
            <?php render_consultant_requests_table($under_review_requests, 'under_review'); ?>
        </div>
        
        <div id="approved-tab" class="consultant-tab-pane">
            <?php render_consultant_requests_table($approved_requests, 'approved'); ?>
        </div>
    </div>
</div>

<!-- مودال ویرایش رژیم -->
<div id="consultation-modal" class="consultant-modal" style="display: none;">
    <div class="consultant-modal-content">
        <span class="consultant-close-modal">&times;</span>
        <div class="consultant-modal-header">
            <h3><i class="fas fa-edit"></i> ویرایش و بازبینی رژیم غذایی</h3>
        </div>
        <div class="consultant-modal-body">
            <div id="consultation-editor">
                <!-- محتوای ادیتور اینجا لود می‌شود -->
            </div>
        </div>
        <div class="consultant-modal-footer">
            <button class="consultant-btn consultant-btn-warning" id="save-draft-btn">
                <i class="fas fa-save"></i> ذخیره پیش‌نویس
            </button>
            <button class="consultant-btn consultant-btn-success" id="approve-btn">
                <i class="fas fa-check"></i> تایید نهایی
            </button>
            <!--<button class="consultant-btn consultant-btn-danger" id="reject-btn">-->
            <!--    <i class="fas fa-times"></i> رد درخواست-->
            <!--</button>-->
        </div>
    </div>
</div>




<?php
get_footer();