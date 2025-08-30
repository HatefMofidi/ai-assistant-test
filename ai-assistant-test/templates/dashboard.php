<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/dashboard.php
 * Template Name: داشبورد کاربری
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// دریافت اطلاعات کاربر
$current_user = wp_get_current_user();
$user_credit = AI_Assistant_Payment_Handler::get_instance()->get_user_credit(get_current_user_id());
$formatted_credit = format_number_fa($user_credit);

// دریافت سرویس‌های فعال
$services = AI_Assistant_Service_Manager::get_instance()->get_active_services();
$recent_services = array_slice($services, 0, 4);

// دریافت آخرین فعالیت‌های کاربر از تاریخچه
$history_manager = AI_Assistant_History_Manager::get_instance();
$recent_history = $history_manager->get_user_history(get_current_user_id(), 3);

// تبدیل تاریخچه به فرمت مورد نیاز برای نمایش
$recent_activities = array();
foreach ($recent_history as $item) {
    $service_info = AI_Assistant_Service_Manager::get_instance()->get_service($item->service_id);
    $service_name = $service_info['name'] ?? 'سرویس ناشناخته';
    
    // تعیین آیکون بر اساس نوع سرویس
    $icon_class = 'dashicons-admin-generic'; // آیکون پیش‌فرض
    if (isset($service_info['icon'])) {
        $icon_class = $service_info['icon'];
    }
    
    $recent_activities[] = array(
        'type' => $service_name,
        'title' => 'استفاده از سرویس',
        'date' => human_time_diff(strtotime($item->created_at), current_time('timestamp')) . ' پیش',
        'icon' => $icon_class
    );
}

?>

<div class="dash-ai-dashboard-container">
    <header class="dash-ai-dashboard-header">
        <div class="dash-header-content">
            <div class="dash-welcome-section">
                <h1>خوش آمدید، <span class="dash-user-name"><?php echo number_fa(esc_html($current_user->display_name)); ?></span></h1>
                <p class="dash-welcome-message">امروز چطور می‌توانم به شما کمک کنم؟</p>
            </div>
            
            <div class="dash-wallet-section">
                <div class="dash-wallet-balance">
                    <div class="dash-balance-info">
                        <span class="dash-balance-label">موجودی کیف پول</span>
                        <span class="dash-balance-amount"><?php echo $formatted_credit; ?> <span class="dash-currency">تومان</span></span>
                    </div>
                </div>
                <a href="<?php echo esc_url(home_url('/wallet-charge')); ?>" class="dash-charge-wallet-btn">
                    <span>شارژ کیف پول</span>
                </a>
            </div>
        </div>
    </header>

    <main class="ai-dashboard-main">
        <section class="dash-dashboard-section quick-access-section">
            <h2 class="dash-section-title">
                <span class="dash-title-icon">⚡</span>
                دسترسی سریع
            </h2>
            <div class="dash-quick-access-grid">
                <a href="<?php echo esc_url(home_url('/page-user-history')); ?>" class="dash-quick-access-card">
                    <div class="dash-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="dash-card-content">
                        <h3>تاریخچه استفاده</h3>
                        <p>مشاهده تمام درخواست‌های شما</p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url(home_url('/user-wallet-history')); ?>" class="dash-quick-access-card">
                    <div class="dash-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 4H20C21.1046 4 22 4.89543 22 6V18C22 19.1046 21.1046 20 20 20H4C2.89543 20 2 19.1046 2 18V6C2 4.89543 2.89543 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 8H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 14H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="dash-card-content">
                        <h3>تاریخچه تراکنش‌ها</h3>
                        <p>جزییات تمام پرداخت‌ها و شارژها</p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url(home_url('/profile')); ?>" class="dash-quick-access-card">
                    <div class="dash-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="dash-card-content">
                        <h3>پروفایل کاربری</h3>
                        <p>مدیریت اطلاعات شخصی شما</p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="dash-quick-access-card">
                    <div class="dash-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="dash-card-content">
                        <h3>خروج از حساب</h3>
                        <p>خروج امن از حساب کاربری</p>
                    </div>
                </a>
            </div>
        </section>

        <section class="dash-dashboard-section services-section">
            <div class="dash-section-header">
                <h2 class="dash-section-title">
                    <span class="dash-title-icon">🎯</span>
                    سرویس‌های پراستفاده
                </h2>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="dash-view-all-link" style="direction: rtl; text-align: right;">
                    مشاهده همه
                </a>
            </div>
            
            <div class="dash-services-grid">
                <?php foreach ($recent_services as $service_id => $service): ?>
                <?php $service_url = home_url('/service/') . $service_id . '/';?>
                <a href="<?php echo esc_url($service_url); ?>" class="dash-service-card">
                    <div class="dash-service-icon">
                        <span class="dashicons <?php echo esc_attr($service['icon']); ?>"></span>
                    </div>
                    <div class="dash-service-content">
                        <h3><?php echo esc_html($service['name']); ?></h3>
                        <p class="dash-service-description"><?php echo esc_html($service['description'] ?? 'سرویس هوش مصنوعی'); ?></p>
                        <div class="dash-service-footer">
                            <span class="dash-service-price"><?php echo format_number_fa($service['price']); ?> تومان</span>
                            <span class="dash-service-action" style="direction: rtl;">
                                استفاده کنید
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section class="dash-dashboard-section activity-section">
            <div class="dash-section-header">
                <h2 class="dash-section-title">
                    <span class="dash-title-icon">📊</span>
                    آخرین فعالیت‌ها
                </h2>
            </div>
            
            <div class="dash-activity-list">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="dash-activity-item">
                    <div class="dash-activity-icon">
                        <span class="dashicons <?php echo esc_attr($activity['icon']); ?>"></span>
                    </div>
                    <div class="dash-activity-content">
                        <h4><?php echo esc_html($activity['title']); ?></h4>
                        <p class="dash-activity-type"><?php echo esc_html($activity['type']); ?></p>
                    </div>
                    <div class="dash-activity-meta">
                        <span class="dash-activity-date"><?php echo esc_html($activity['date']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>

<style>
/* استایل‌های عمومی */
.dash-ai-dashboard-container {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Vazir', 'Tahoma', sans-serif;
    direction: rtl;
}

/* هدر داشبورد */
.dash-ai-dashboard-header {
    background: linear-gradient(135deg, #00857a 0%, #00c9b7 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 133, 122, 0.2);
}

.dash-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.dash-welcome-section {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    text-align: right;
    flex: 1;
}

.dash-welcome-section h1 {
    margin: 0 0 8px 0;
    font-size: 22px;
    font-weight: 700;
    text-align: right;
    width: 100%;
}

.dash-welcome-section h1,
.dash-welcome-message {
    text-align: center;
}

.dash-user-name {
    color: #ffd166;
}

.dash-welcome-message {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
    width: 100%;
}

.dash-wallet-section {
    flex-direction: column;
    align-items: stretch;
    gap: 15px;
}

.dash-wallet-balance {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.15);
    padding: 12px 20px;
    border-radius: 12px;
    margin-bottom: 10px;
}

.dash-balance-info {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dash-balance-label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 4px;
}

.dash-balance-amount {
    font-size: 22px;
    font-weight: 700;
}

.dash-currency {
    font-size: 16px;
    font-weight: 500;
}

.dash-charge-wallet-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
    min-width: 120px;
}

.dash-charge-wallet-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

/* بخش‌های داشبورد */
.dash-dashboard-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.dash-section-header {
    /*display: flex;*/
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.dash-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #2d3748;
}

.dash-title-icon {
    font-size: 24px;
}

.dash-view-all-link {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #00857a;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.dash-view-all-link:hover {
    color: #005a52;
    gap: 8px;
}

/* دسترسی سریع */
.dash-quick-access-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.dash-quick-access-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    text-decoration: none;
    color: #2d3748;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}

.dash-quick-access-card:hover {
    background: #f1f5f9;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-color: #cbd5e0;
}

.dash-card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 12px;
    color: #00857a;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0, 133, 122, 0.15);
}

.dash-card-content h3 {
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 600;
}

.dash-card-content p {
    margin: 0;
    font-size: 14px;
    color: #64748b;
}

/* سرویس‌ها */
.dash-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.dash-service-card {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    text-decoration: none;
    color: #2d3748;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}

.dash-service-card:hover {
    background: #f1f5f9;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-color: #cbd5e0;
}

.dash-service-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 12px;
    color: #00857a;
    flex-shrink: 0;
    font-size: 24px;
    box-shadow: 0 4px 10px rgba(0, 133, 122, 0.15);
}

.dash-service-content {
    flex: 1;
}

.dash-service-content h3 {
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 600;
}

.dash-service-description {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
}

.dash-service-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dash-service-price {
    font-weight: 700;
    color: #00857a;
    font-size: 14px;
}

.dash-service-action {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 14px;
    font-weight: 600;
    color: #00857a;
}

/* فعالیت‌ها */
.dash-activity-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.dash-activity-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.dash-activity-item:hover {
    background: #f1f5f9;
}

.dash-activity-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 10px;
    color: #00857a;
    flex-shrink: 0;
    font-size: 20px;
    box-shadow: 0 2px 8px rgba(0, 133, 122, 0.1);
}

.dash-activity-content {
    flex: 1;
}

.dash-activity-content h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 600;
}

.dash-activity-type {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

.dash-activity-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.dash-activity-date {
    font-size: 13px;
    color: #94a3b8;
}

h2.dash-section-title {
    padding-bottom: 20px;
}

/* رسپانسیو */
@media (max-width: 768px) {
    .dash-ai-dashboard-container {
        padding: 15px;
    }
    
    .dash-header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .dash-welcome-section {
        align-items: center;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .dash-wallet-balance, .dash-charge-wallet-btn {
        width: 100%;
        justify-content: center;
        padding: 10px 0px;
    }
    
    .dash-quick-access-grid,
    .dash-services-grid {
        grid-template-columns: 1fr;
    }
    
    .dash-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .dash-view-all-link {
        align-self: flex-end;
        /*flex-direction: row-reverse;*/
    }
}

/* انیمیشن‌ها */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.dash-dashboard-section {
    animation: fadeIn 0.5s ease-out forwards;
}

.dash-dashboard-section:nth-child(1) { animation-delay: 0.1s; }
.dash-dashboard-section:nth-child(2) { animation-delay: 0.2s; }
.dash-dashboard-section:nth-child(3) { animation-delay: 0.3s; }

.dash-quick-access-card,
.dash-service-card,
.dash-activity-item {
    animation: fadeIn 0.5s ease-out forwards;
}

/* تم تاریک */
@media (prefers-color-scheme: dark) {
    .dash-dashboard-section {
        background: #2d3748;
        color: #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }
    
    .dash-section-title {
        color: #e2e8f0;
    }
    
    .dash-quick-access-card,
    .dash-service-card,
    .dash-activity-item {
        background: #4a5568;
        color: #e2e8f0;
        border-color: #4a5568;
    }
    
    .dash-quick-access-card:hover,
    .dash-service-card:hover,
    .dash-activity-item:hover {
        background: #718096;
    }
    
    .dash-card-content p,
    .dash-service-description,
    .dash-activity-type {
        color: #cbd5e0;
    }
    
    .dash-card-icon,
    .dash-service-icon,
    .dash-activity-icon {
        background: #2d3748;
        color: #00857a;
    }
}
</style>

<?php
get_footer();