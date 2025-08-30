<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/templates/dashboard.php-->
<?php
/**
 * Template Name: داشبورد کاربری
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header();
?>

<div class="ai-dashboard">
    <div class="ai-dashboard-header">
        <h1><?php _e('داشبورد کاربری', 'ai-assistant'); ?></h1>
        <div class="ai-wallet-balance">
            <span class="ai-wallet-label"><?php _e('موجودی کیف پول:', 'ai-assistant'); ?></span>
            <span class="ai-wallet-value"><?php echo number_format(AI_Assistant_Payment_Handler::get_instance()->get_user_credit(get_current_user_id())); ?> <?php _e('تومان', 'ai-assistant'); ?></span>
            <a href="<?php echo esc_url(home_url('/wallet-charge')); ?>" class="ai-wallet-button">
                <?php _e('شارژ کیف پول', 'ai-assistant'); ?>
            </a>
        </div>
    </div>

    <div class="ai-dashboard-grid">
        <div class="ai-dashboard-card ai-quick-access">
            <h2><?php _e('دسترسی سریع', 'ai-assistant'); ?></h2>
            <div class="ai-quick-links">
                <a href="<?php echo esc_url(home_url('/page-user-history')); ?>" class="ai-quick-link">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('تاریخچه استفاده', 'ai-assistant'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/user-wallet-history')); ?>" class="ai-quick-link">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('تاریخچه تراکنش‌ها', 'ai-assistant'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/profile')); ?>" class="ai-quick-link">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('پروفایل کاربری', 'ai-assistant'); ?>
                </a>
            </div>
        </div>

        <div class="ai-dashboard-card ai-recent-services">
            <h2><?php _e('سرویس‌های پراستفاده', 'ai-assistant'); ?></h2>
            <div class="ai-services-grid">
                <?php
                $services = AI_Assistant_Service_Manager::get_instance()->get_active_services();
                foreach (array_slice($services, 0, 4) as $service_id => $service):
                ?>
                    <a href="<?php echo add_query_arg('service', $service_id, get_permalink()); ?>" class="ai-card ai-service-card">
                        <div class="ai-service-icon">
                            <span class="dashicons <?php echo esc_attr($service['icon']); ?>"></span>
                        </div>
                        <h3><?php echo esc_html($service['name']); ?></h3>
                        <p class="ai-service-price">
                            <?php echo number_format($service['price']); ?> <?php _e('تومان', 'ai-assistant'); ?>
                        </p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ai-dashboard-card ai-activity">
            <h2><?php _e('آخرین فعالیت‌ها', 'ai-assistant'); ?></h2>
            <div class="ai-activity-list">
                <!-- این بخش می‌تواند با AJAX پر شود -->
                <div class="ai-activity-loading">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('در حال دریافت اطلاعات...', 'ai-assistant'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();