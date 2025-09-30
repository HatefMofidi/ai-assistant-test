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

wp_enqueue_script('diet-plan-js', get_template_directory_uri() . '/assets/js/services/diet/diet-plan.js', array(), null, true);

// تابع نمایش جدول درخواست‌ها - قبل از get_header()
function render_consultant_requests_table($requests, $status) {
    if (empty($requests)) {
        echo '<div class="no-requests">';
        echo '<i class="fas fa-inbox"></i>';
        echo '<p>هیچ درخواستی یافت نشد.</p>';
        echo '</div>';
        return;
    }
    ?>
    <table class="consultant-requests-table">
        <thead>
            <tr>
                <th>کاربر</th>
                <th>سرویس</th>
                <th>تاریخ درخواست</th>
                <th>مهلت بازبینی</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $request) : 
                $user = get_user_by('id', $request->user_id);
                $history_manager = AI_Assistant_History_Manager::get_instance();
                $history_item = $history_manager->get_history_item($request->service_history_id);
                
                $is_deadline_passed = strtotime($request->deadline) < time() && $status !== 'approved';
            ?>
            <tr data-request-id="<?php echo $request->id; ?>">
                <td>
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <?php echo esc_html($user->display_name); ?>
                    </div>
                </td>
                <td>
                    <?php echo esc_html($history_item ? $history_item->service_name : 'نامشخص'); ?>
                </td>
                <td>
                    <?php echo date_i18n('j F Y - H:i', strtotime($request->created_at)); ?>
                </td>
                <td class="<?php echo $is_deadline_passed ? 'deadline-passed' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <?php echo date_i18n('j F Y - H:i', strtotime($request->deadline)); ?>
                </td>
                <td>
                    <span class="status-badge status-<?php echo $status; ?>">
                        <?php 
                        $status_texts = [
                            'pending' => 'در انتظار',
                            'under_review' => 'در حال بررسی',
                            'approved' => 'تایید شده'
                        ];
                        echo $status_texts[$status];
                        ?>
                    </span>
                </td>
                <td>
                    <button class="review-button" data-request-id="<?php echo $request->id; ?>">
                        <i class="fas fa-edit"></i>
                        <?php echo $status === 'approved' ? 'مشاهده' : 'بررسی'; ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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

// بارگذاری اسکریپت‌های لازم
wp_enqueue_script('diet-plan-js', get_template_directory_uri() . '/assets/js/services/diet/diet-plan.js', array(), null, true);
wp_enqueue_script('consultant-dashboard', get_template_directory_uri() . '/assets/js/services/diet/consultant-dashboard.js', ['jquery'], null, true);
wp_localize_script('consultant-dashboard', 'consultant_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('consultation_review_nonce')
]);

?>

<div class="consultant-dashboard">
    <div class="consultant-header">
        <h1><i class="fas fa-user-md"></i> کارتابل مشاور تغذیه</h1>
        <div class="consultant-stats">
            <div class="stat-card pending">
                <span class="stat-number"><?php echo $request_counts['pending']; ?></span>
                <span class="stat-label">در انتظار بازبینی</span>
            </div>
            <div class="stat-card review">
                <span class="stat-number"><?php echo $request_counts['under_review']; ?></span>
                <span class="stat-label">در حال بازبینی</span>
            </div>
            <div class="stat-card approved">
                <span class="stat-number"><?php echo $request_counts['approved']; ?></span>
                <span class="stat-label">تایید شده</span>
            </div>
            <div class="stat-card total">
                <span class="stat-number"><?php echo $request_counts['total']; ?></span>
                <span class="stat-label">کل درخواست‌ها</span>
            </div>
        </div>
    </div>

    <div class="consultant-tabs">
        <div class="tab active" data-tab="pending">
            <i class="fas fa-clock"></i>
            در انتظار بازبینی
            <span class="tab-count">(<?php echo count($pending_requests); ?>)</span>
        </div>
        <div class="tab" data-tab="under_review">
            <i class="fas fa-edit"></i>
            در حال بازبینی
            <span class="tab-count">(<?php echo count($under_review_requests); ?>)</span>
        </div>
        <div class="tab" data-tab="approved">
            <i class="fas fa-check-circle"></i>
            تایید شده
            <span class="tab-count">(<?php echo count($approved_requests); ?>)</span>
        </div>
    </div>

    <div class="consultant-requests">
        <!-- محتوای هر تب -->
        <div class="tab-content active" id="pending-content">
            <?php render_consultant_requests_table($pending_requests, 'pending'); ?>
        </div>
        
        <div class="tab-content" id="under_review-content">
            <?php render_consultant_requests_table($under_review_requests, 'under_review'); ?>
        </div>
        
        <div class="tab-content" id="approved-content">
            <?php render_consultant_requests_table($approved_requests, 'approved'); ?>
        </div>
    </div>
</div>

<!-- مودال ویرایش رژیم -->
<div id="consultation-modal" class="consultation-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> ویرایش و بازبینی رژیم غذایی</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="consultation-editor">
                <!-- محتوای ادیتور اینجا لود می‌شود -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="save-draft-btn">
                <i class="fas fa-save"></i> ذخیره پیش‌نویس
            </button>
            <button class="btn btn-success" id="approve-btn">
                <i class="fas fa-check"></i> تایید نهایی
            </button>
            <button class="btn btn-danger" id="reject-btn">
                <i class="fas fa-times"></i> رد درخواست
            </button>
        </div>
    </div>
</div>

<?php
get_footer();