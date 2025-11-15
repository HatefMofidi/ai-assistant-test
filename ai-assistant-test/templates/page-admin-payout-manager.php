<?php
/**
 * Template Name: مدیریت تسویه حساب مشاوران
 */

if (!defined('ABSPATH')) {
    exit;
}

// فقط ادمین دسترسی دارد
if (!current_user_can('manage_options')) {
    wp_die('دسترسی غیرمجاز');
}

get_header();


// دریافت تعداد برای badgeهای تب‌ها
$consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
$tab_counts = $consultation_db->get_counts_for_tabs();
$payouts_count = $tab_counts['payouts_count'] ?? 0;
$consultants_count = $tab_counts['consultants_count'] ?? 0;


// بارگذاری استایل‌ها و اسکریپت‌ها
wp_enqueue_style('admin-payout-manager-css', 
    get_template_directory_uri() . '/assets/css/admin-payout-manager.css',
    [],
    filemtime(get_template_directory() . '/assets/css/admin-payout-manager.css')
);

wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

wp_enqueue_script('admin-payout-manager', 
    get_template_directory_uri() . '/assets/js/admin-payout-manager.js', 
    ['jquery'], 
    filemtime(get_template_directory() . '/assets/js/admin-payout-manager.js'), 
    true
);

wp_localize_script('admin-payout-manager', 'admin_payout_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('admin_payout_nonce')
]);

?>

<div class="wrap admin-payout-manager">
    <!-- هدر صفحه -->
    <div class="admin-payout-header">
        <h1>مدیریت تسویه حساب مشاوران</h1>
        <p class="admin-payout-subtitle">اینجا می‌توانید کمیسیون‌های پرداخت‌نشده را ببینید، تسویه دستی انجام دهید و تاریخچه پرداخت‌ها را مشاهده کنید.</p>
        
        <div class="admin-payout-actions">
            <button class="admin-payout-btn primary" id="create-payout">
                <i class="fas fa-plus"></i> تسویه جدید
            </button>
            <button class="admin-payout-btn secondary" id="export-csv">
                <i class="fas fa-download"></i> خروجی CSV
            </button>
            <button class="admin-payout-btn secondary" id="refresh-data">
                <i class="fas fa-sync-alt"></i> رفرش
            </button>
        </div>
    </div>

    <!-- کارت‌های خلاصه آماری -->
    <div class="payout-summary-cards">
        <div class="summary-card pending">
            <div class="summary-card-content">
                <div class="summary-card-info">
                    <h3>مجموع در انتظار</h3>
                    <p class="summary-card-amount" id="total-pending">0</p>
                    <div class="summary-card-detail">کمیسیون‌های پرداخت نشده</div>
                </div>
                <div class="summary-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="summary-card paid">
            <div class="summary-card-content">
                <div class="summary-card-info">
                    <h3>پرداخت شده (ماه جاری)</h3>
                    <p class="summary-card-amount" id="total-paid-month">0</p>
                    <div class="summary-card-detail">تسویه‌های موفق این ماه</div>
                </div>
                <div class="summary-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="summary-card consultants">
            <div class="summary-card-content">
                <div class="summary-card-info">
                    <h3>مشاوران قابل پرداخت</h3>
                    <p class="summary-card-amount" id="consultants-with-balance">0</p>
                    <div class="summary-card-detail">دارای موجودی قابل برداشت</div>
                </div>
                <div class="summary-card-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="summary-card recent">
            <div class="summary-card-content">
                <div class="summary-card-info">
                    <h3>آخرین تسویه</h3>
                    <p class="summary-card-amount" id="last-payout">---</p>
                    <div class="summary-card-detail">آخرین عملیات موفق</div>
                </div>
                <div class="summary-card-icon">
                    <i class="fas fa-history"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- فیلترها -->
    <div class="payout-filters" id="payout-filters">
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter-search">جستجو (نام، ایمیل، ID):</label>
                <input type="text" id="filter-search" placeholder="جستجو...">
            </div>
            
            <div class="filter-group">
                <label for="filter-status">وضعیت:</label>
                <select id="filter-status">
                    <option value="">همه</option>
                    <option value="pending">در انتظار</option>
                    <option value="done">پرداخت شده</option>
                    <option value="failed">ناموفق</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-date-from">از تاریخ:</label>
                <input type="date" id="filter-date-from">
            </div>
            
            <div class="filter-group">
                <label for="filter-date-to">تا تاریخ:</label>
                <input type="date" id="filter-date-to">
            </div>
        </div>
        
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter-min-amount">حداقل مبلغ:</label>
                <input type="number" id="filter-min-amount" placeholder="حداقل مبلغ">
            </div>
            
            <div class="filter-group">
                <label for="filter-max-amount">حداکثر مبلغ:</label>
                <input type="number" id="filter-max-amount" placeholder="حداکثر مبلغ">
            </div>
        </div>
        
        <div class="filter-actions">
            <button class="admin-payout-btn primary" id="apply-filters">
                <i class="fas fa-filter"></i> اعمال فیلتر
            </button>
            <button class="admin-payout-btn secondary" id="reset-filters">
                <i class="fas fa-redo"></i> ریست فیلترها
            </button>
        </div>
    </div>


    
    
     
    
<!-- تب‌های اصلی -->
<div class="payout-tabs">
    <button class="payout-tab-button active" data-tab="payouts-tab">
        <i class="fas fa-receipt"></i>
        تسویه‌ها
        <span class="tab-badge"><?php echo $payouts_count; ?></span>
    </button>
    <button class="payout-tab-button" data-tab="consultants-tab">
        <i class="fas fa-user-tie"></i>
        مشاوران قابل پرداخت
        <span class="tab-badge"><?php echo $consultants_count; ?></span>
    </button>
</div>

<!-- محتوای تب تسویه‌ها -->
<div id="payouts-tab" class="payout-tab-content active">
    <!-- محتوای فعلی صفحه (همون جدول تسویه‌ها) اینجا قرار می‌گیره -->
    <!-- جدول اصلی -->
    <div class="payout-table-container">
        <div class="payout-table-wrapper">
            <table class="payouts-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>مشاور</th>
                        <th>بازه زمانی</th>
                        <th>مبلغ کل</th>
                        <th>تعداد آیتم‌ها</th>
                        <th>وضعیت</th>
                        <th>روش پرداخت</th>
                        <th>شماره پیگیری</th>
                        <th>تاریخ ثبت / پرداخت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="payouts-table-body">
                    <!-- داده‌ها از طریق AJAX لود می‌شوند -->
                </tbody>
            </table>
        </div>
        
        <!-- پاگینیشن -->
        <div class="payout-pagination" style="display: none;">
            <div class="pagination-info">نمایش 0-0 از 0 مورد</div>
            <div class="pagination-buttons">
                <!-- دکمه‌های پاگینیشن از طریق AJAX لود می‌شوند -->
            </div>
        </div>
    </div>
</div>

<!-- محتوای تب مشاوران -->
<div id="consultants-tab" class="payout-tab-content">
    <div class="consultants-table-container">
        <div class="consultants-table-wrapper">
            <table class="consultants-table">
                <thead>
                    <tr>
                        <th>مشاور</th>
                        <th>تعداد کمیسیون‌ها</th>
                        <th>مجموع مبلغ پرداخت نشده</th>
                        <th>میانگین مبلغ</th>
                        <th>قدیمی‌ترین تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="consultants-table-body">
                    <!-- داده‌ها از طریق AJAX لود می‌شوند -->
                </tbody>
            </table>
        </div>
        
        <!-- پاگینیشن مشاوران -->
        <div class="consultants-pagination" style="display: none;">
            <div class="pagination-info">نمایش 0-0 از 0 مورد</div>
            <div class="pagination-buttons">
                <!-- دکمه‌های پاگینیشن از طریق AJAX لود می‌شوند -->
            </div>
        </div>
    </div>
</div>    
    
    
    
    
    
    
</div>

<!-- مودال جزئیات تسویه -->
<div id="payout-details-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 1000px;">
        <div class="modal-header">
            <h3><i class="fas fa-receipt"></i> جزئیات تسویه</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <!-- محتوای جزئیات از طریق AJAX لود می‌شود -->
        </div>
        <div class="modal-footer">
            <button type="button" class="admin-payout-btn secondary modal-cancel">
                <i class="fas fa-times"></i> بستن
            </button>
        </div>
    </div>
</div>

<!-- مودال تأیید پرداخت -->
<div id="confirm-payment-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle"></i> تأیید پرداخت</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="confirm-payment-form">
                <div class="form-group">
                    <label for="payment-reference">
                        <i class="fas fa-hashtag"></i> شماره پیگیری / کد مرجع:
                    </label>
                    <input type="text" id="payment-reference" name="reference_code" required 
                           placeholder="مثال: TRX-20251110-123">
                    <small>شماره پیگیری تراکنش بانکی یا کد مرجع پرداخت را وارد کنید</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="admin-payout-btn success" id="confirm-payment-btn">
                        <i class="fas fa-check"></i> تأیید پرداخت
                    </button>
                    <button type="button" class="admin-payout-btn secondary modal-cancel">
                        <i class="fas fa-times"></i> لغو
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال ایجاد تسویه جدید -->
<div id="create-payout-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> ایجاد تسویه جدید</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="create-payout-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="consultant-select">
                            <i class="fas fa-user-tie"></i> مشاور:
                        </label>
                        <select id="consultant-select" name="consultant_id" required>
                            <option value="">انتخاب مشاور...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payout-amount">
                            <i class="fas fa-money-bill-wave"></i> مبلغ کل:
                        </label>
                        <input type="number" id="payout-amount" name="amount" readonly 
                               placeholder="پس از انتخاب مشاور محاسبه می‌شود">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="period-start">
                            <i class="fas fa-calendar-alt"></i> از تاریخ:
                        </label>
                        <input type="date" id="period-start" name="period_start" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="period-end">
                            <i class="fas fa-calendar-alt"></i> تا تاریخ:
                        </label>
                        <input type="date" id="period-end" name="period_end" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment-method">
                            <i class="fas fa-credit-card"></i> روش پرداخت:
                        </label>
                        <select id="payment-method" name="payment_method" required>
                            <option value="manual">دستی</option>
                            <option value="bank_transfer">کارت به کارت</option>
                            <option value="api">API</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference-code">
                            <i class="fas fa-hashtag"></i> شماره پیگیری:
                        </label>
                        <input type="text" id="reference-code" name="reference_code" 
                               placeholder="اختیاری - برای رهگیری">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-list"></i> کمیسیون‌های پرداخت‌نشده:
                    </label>
                    <div id="unpaid-commissions">
                        <p>لطفاً ابتدا یک مشاور انتخاب کنید</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="admin-payout-btn success" id="create-payout-submit">
                        <i class="fas fa-check"></i> ایجاد تسویه
                    </button>
                    <button type="button" class="admin-payout-btn secondary modal-cancel">
                        <i class="fas fa-times"></i> لغو
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
get_footer();