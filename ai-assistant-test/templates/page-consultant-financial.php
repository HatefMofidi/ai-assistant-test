<?php
/**
 * Template Name: داشبورد مالی مشاور تغذیه
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in() || (!current_user_can('nutrition_consultant') && !current_user_can('administrator'))) {
    wp_redirect(home_url());
    exit;
}

// غیرفعال کردن کش
if (!defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

get_header();

$consultant_id = get_current_user_id();
$consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();

// دریافت اطلاعات مشاور
$consultant = $consultation_db->get_consultant_by_user_id($consultant_id);



// دریافت قرارداد فعال
$active_contract = $consultation_db->get_active_contract($consultant->user_id ?? 0);


// دریافت آمار مالی
$pending_commissions = $consultation_db->get_consultant_commissions($consultant->user_id ?? 0, 'pending');
$paid_commissions = $consultation_db->get_consultant_commissions($consultant->user_id ?? 0, 'paid');

// محاسبه جمع مبالغ
$total_pending = array_sum(array_column($pending_commissions, 'final_commission'));
$total_paid = array_sum(array_column($paid_commissions, 'final_commission'));

// دریافت تاریخچه پرداخت‌ها
$payouts = $consultation_db->get_consultant_payouts($consultant->user_id ?? 0);

// بارگذاری استایل‌ها
wp_enqueue_style('consultant-financial-dashboard-css', 
    get_template_directory_uri() . '/assets/css/consultant-financial-dashboard.css',
    [],
    filemtime(get_template_directory() . '/assets/css/consultant-financial-dashboard.css')
);

wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
?>

<div class="consultant-financial-panel">
    <div class="consultant-financial-header">
        <h2>داشبورد مالی مشاور تغذیه</h2>
        <div class="consultant-admin-user-info">
            <span>خوش آمدید، <?php echo wp_get_current_user()->display_name; ?></span>
            <a href="<?php echo home_url('/consultant-dashboard'); ?>" class="consultant-btn consultant-btn-primary">
                <i class="fas fa-arrow-right"></i>
                بازگشت به کارتابل
            </a>
        </div>
    </div>

    <!-- کارت‌های خلاصه وضعیت مالی -->
    <div class="financial-summary-cards">
        <div class="financial-card pending">
            <div class="financial-card-content">
                <div class="financial-card-info">
                    <h3>موجودی در انتظار تصویه</h3>
                    <p class="financial-card-amount"><?php echo number_format($total_pending); ?> تومان</p>
                    <span class="payout-status pending"><?php echo count($pending_commissions); ?> کمیسیون</span>
                </div>
                <div class="financial-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="financial-card paid">
            <div class="financial-card-content">
                <div class="financial-card-info">
                    <h3>مجموع تصویه شده</h3>
                    <p class="financial-card-amount"><?php echo number_format($total_paid); ?> تومان</p>
                    <span class="payout-status done"><?php echo count($paid_commissions); ?> کمیسیون</span>
                </div>
                <div class="financial-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="financial-card contract">
            <div class="financial-card-content">
                <div class="financial-card-info">
                    <h3>قرارداد فعال</h3>
                    <p class="financial-card-amount">
                        <?php echo $active_contract ? number_format($active_contract->commission_value) . ' تومان' : 'ندارد'; ?>
                    </p>
                    <span class="payout-status <?php echo $active_contract ? 'done' : 'pending'; ?>">
                        <?php echo $active_contract ? 'فعال' : 'غیرفعال'; ?>
                    </span>
                </div>
                <div class="financial-card-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="financial-sections">
        <!-- بخش سمت راست: اطلاعات قرارداد و پرداخت‌ها -->
        <div class="financial-section">
            <div class="financial-section-header">
                <h3><i class="fas fa-file-contract"></i> اطلاعات قرارداد فعال</h3>
            </div>
            <div class="financial-section-content">
                <?php if ($active_contract): ?>
                    <div class="contract-details">
                        <div class="contract-field">
                            <span class="contract-label">نوع کمیسیون:</span>
                            <span class="contract-value <?php echo $active_contract->commission_type; ?>">
                                <?php echo $active_contract->commission_type === 'percent' ? 'درصدی' : 'ثابت'; ?>
                            </span>
                        </div>
                        <div class="contract-field">
                            <span class="contract-label">مبلغ/درصد کمیسیون:</span>
                            <span class="contract-value">
                                <?php 
                                if ($active_contract->commission_type === 'percent') {
                                    echo $active_contract->commission_value . '%';
                                } else {
                                    echo number_format($active_contract->commission_value) . ' تومان';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="contract-field">
                            <span class="contract-label">ساعت کامل پرداخت:</span>
                            <span class="contract-value"><?php echo $active_contract->full_payment_hours; ?> ساعت</span>
                        </div>
                        <div class="contract-field">
                            <span class="contract-label">ضریب تاخیر:</span>
                            <span class="contract-value"><?php echo $active_contract->delay_penalty_factor; ?></span>
                        </div>
                        <div class="contract-field">
                            <span class="contract-label">اعتبار از:</span>
                            <span class="contract-value"><?php echo date_i18n('Y/m/d - H:i', strtotime($active_contract->active_from)); ?></span>
                        </div>
                        <?php if ($active_contract->active_to): ?>
                        <div class="contract-field">
                            <span class="contract-label">اعتبار تا:</span>
                            <span class="contract-value"><?php echo date_i18n('Y/m/d - H:i', strtotime($active_contract->active_to)); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="financial-empty-state">
                        <i class="fas fa-file-contract"></i>
                        <p>هیچ قرارداد فعالی یافت نشد</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- بخش سمت چپ: تاریخچه پرداخت‌ها -->
        <div class="financial-section">
            <div class="financial-section-header">
                <h3><i class="fas fa-history"></i> تاریخچه پرداخت‌ها</h3>
            </div>
            <div class="financial-section-content">
                <?php if (!empty($payouts)): ?>
                    <div class="payouts-list">
                        <?php foreach ($payouts as $payout): ?>
                            <div class="payout-item">
                                <div class="payout-info">
                                    <span class="payout-period">
                                        <?php echo date_i18n('Y/m/d', strtotime($payout->period_start)); ?> 
                                        تا 
                                        <?php echo date_i18n('Y/m/d', strtotime($payout->period_end)); ?>
                                    </span>
                                    <span class="payout-date">
                                        <?php echo $payout->paid_at ? date_i18n('Y/m/d - H:i', strtotime($payout->paid_at)) : 'پرداخت نشده'; ?>
                                    </span>
                                    <?php if ($payout->reference_code): ?>
                                    <span class="payout-reference">
                                        کد پیگیری: <?php echo $payout->reference_code; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="payout-details">
                                    <span class="payout-amount"><?php echo number_format($payout->amount); ?> تومان</span>
                                    <span class="payout-status <?php echo $payout->status; ?>">
                                        <?php 
                                        $status_texts = [
                                            'pending' => 'در انتظار',
                                            'done' => 'پرداخت شده',
                                            'failed' => 'ناموفق'
                                        ];
                                        echo $status_texts[$payout->status];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="financial-empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>هیچ پرداختی یافت نشد</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- تب‌های کمیسیون‌ها -->
    <div class="financial-section">
        <div class="financial-section-header">
            <h3><i class="fas fa-chart-line"></i> کمیسیون‌ها</h3>
        </div>
        <div class="financial-section-content">
            <div class="financial-tabs">
                <button class="financial-tab active" data-tab="pending-commissions">در انتظار تصویه (<?php echo count($pending_commissions); ?>)</button>
                <button class="financial-tab" data-tab="paid-commissions">تصویه شده (<?php echo count($paid_commissions); ?>)</button>
            </div>

            <div id="pending-commissions" class="financial-tab-content active">
                <?php if (!empty($pending_commissions)): ?>
                    <table class="commissions-table">
                        <thead>
                            <tr>
                                <th>شناسه درخواست</th>
                                <th>مبلغ پایه</th>
                                <th>ساعت تاخیر</th>
                                <th>ضریب جریمه</th>
                                <th>کمیسیون نهایی</th>
                                <th>تاریخ تولید</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_commissions as $commission): ?>
                                <tr>
                                    <td>#<?php echo $commission->request_id; ?></td>
                                    <td><?php echo number_format($commission->base_amount); ?> تومان</td>
                                    <?php
                                    $hours = floor($commission->delay_hours);
                                    $minutes = round(($commission->delay_hours - $hours) * 60);
                                    ?>
                                    <td><?php echo sprintf('%02d:%02d', $hours, $minutes); ?></td>
                                    <td><?php echo $commission->penalty_multiplier; ?></td>
                                    <td class="commission-amount"><?php echo number_format($commission->final_commission); ?> تومان</td>
                                    <td><?php echo date_i18n('Y/m/d - H:i', strtotime($commission->generated_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="financial-empty-state">
                        <i class="fas fa-clock"></i>
                        <p>هیچ کمیسیون در انتظاری یافت نشد</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="paid-commissions" class="financial-tab-content">
                <?php if (!empty($paid_commissions)): ?>
                    <table class="commissions-table">
                        <thead>
                            <tr>
                                <th>شناسه درخواست</th>
                                <th>مبلغ پایه</th>
                                <th>کمیسیون نهایی</th>
                                <th>تاریخ تایید</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paid_commissions as $commission): ?>
                                <tr>
                                    <td>#<?php echo $commission->request_id; ?></td>
                                    <td><?php echo number_format($commission->base_amount); ?> تومان</td>
                                    <td class="commission-amount"><?php echo number_format($commission->final_commission); ?> تومان</td>
                                    <td><?php echo $commission->approved_at ? date_i18n('Y/m/d - H:i', strtotime($commission->approved_at)) : '---'; ?></td>
                                    <td>
                                        <span class="commission-status <?php echo $commission->status; ?>">
                                            <?php 
                                            $status_texts = [
                                                'pending' => 'در انتظار',
                                                'paid' => 'پرداخت شده',
                                                'cancelled' => 'لغو شده'
                                            ];
                                            echo $status_texts[$commission->status];
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="financial-empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>هیچ کمیسیون تصویه شده‌ای یافت نشد</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // مدیریت تب‌های داخلی
    $('.financial-tab').on('click', function() {
        const tabId = $(this).data('tab');
        
        // غیرفعال کردن همه تب‌ها
        $('.financial-tab').removeClass('active');
        $('.financial-tab-content').removeClass('active');
        
        // فعال کردن تب انتخاب شده
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });
});
</script>

<?php
get_footer();