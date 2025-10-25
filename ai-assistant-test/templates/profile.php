<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/profile.php
 * Template Name: پروفایل کاربری
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/profile')));
    exit;
}

$current_user = wp_get_current_user();
$user_credit = AI_Assistant_Payment_Handler::get_instance()->get_user_credit(get_current_user_id());
$formatted_credit = format_number_fa($user_credit);

// دریافت اطلاعات بیشتر کاربر
$user_phone = get_user_meta($current_user->ID, 'billing_phone', true) ?: 'ثبت نشده';
$user_first_name = get_user_meta($current_user->ID, 'first_name', true);
$user_last_name = get_user_meta($current_user->ID, 'last_name', true);
$full_name = ($user_first_name && $user_last_name) ? $user_first_name . ' ' . $user_last_name : $current_user->display_name;

get_header();

?>

<div class="prof-profile-main-container">
    <div class="prof-profile-container">
        <div class="prof-profile-header">
            <div class="prof-profile-avatar">
                <?php echo get_avatar($current_user->ID, 80); ?>
            </div>
            <h1>پروفایل کاربری</h1>
            <div class="prof-wallet-info">
                <span class="dashicons dashicons-wallet"></span>
                <span>موجودی: </span>
                <strong><?php echo $formatted_credit; ?> تومان</strong>
            </div>
        </div>
        <?php if (isset($_GET['updated']) && $_GET['updated'] === 'success'): ?>
            <div class="prof-internal-success-message">
                <span class="dashicons dashicons-yes-alt"></span>
                اطلاعات با موفقیت به روزرسانی شد!
            </div>
        <?php endif; ?>        
        <div class="prof-profile-content">
            <div class="prof-profile-section">
                <h2>
                    <span class="dashicons dashicons-admin-users"></span>
                    اطلاعات شخصی
                </h2>
                <div class="prof-profile-info">
                    <div class="prof-info-item">
                        <label>نام کامل:</label>
                        <span class="ltr-direction"><?php echo esc_html($full_name); ?></span>
                    </div>
                    <div class="prof-info-item">
                        <label>نام کاربری:</label>
                        <span class="ltr-direction"><?php echo esc_html($current_user->user_login); ?></span>
                    </div>
                    <div class="prof-info-item">
                        <label>ایمیل:</label>
                        <span class="ltr-direction"><?php echo esc_html($current_user->user_email); ?></span>
                    </div>
                    <div class="prof-info-item">
                        <label>شماره تماس:</label>
                        <span class="ltr-direction"><?php echo esc_html($user_phone); ?></span>
                    </div>
                    <div class="prof-info-item">
                        <label>تاریخ عضویت:</label>
                        <span><?php echo date_i18n('j F Y', strtotime($current_user->user_registered)); ?></span>
                    </div>
                    <div class="prof-profile-actions">
                        <a href="<?php echo add_query_arg('nocache', time(), home_url('/account')); ?>" class="prof-edit-profile-btn">
                            <span class="dashicons dashicons-edit"></span>
                            ویرایش اطلاعات شخصی
                        </a>
                    </div>        
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.prof-profile-main-container {
    display: flex;
    justify-content: center;
    min-height: calc(100vh - 200px);
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-family: 'Vazir', 'Tahoma', sans-serif;
}

.ltr-direction {
    direction: ltr;

}

.prof-profile-container {
    max-width: 500px;
    width: 100%;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin: 20px 0;
}

.prof-profile-header {
    background: linear-gradient(135deg, #00857a 0%, #00c9b7 100%);
    color: white;
    padding: 30px 25px;
    text-align: center;
    position: relative;
}

.prof-profile-avatar {
    margin-bottom: 15px;
}

.prof-profile-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.prof-profile-header h1 {
    margin: 0 0 20px 0;
    font-size: 24px;
    font-weight: 700;
}

.prof-wallet-info {
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 20px;
    border-radius: 25px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.prof-wallet-info .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.prof-profile-content {
    padding: 25px;
}

.prof-profile-section {
    background: #f8fafc;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
}

.prof-profile-section h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #00857a;
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
}

.prof-profile-section h2 .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.prof-profile-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.prof-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: white;
    border-radius: 10px;
    border-right: 3px solid #00857a;
    box-shadow: 0 2px 8px rgba(0, 133, 122, 0.1);
}

.prof-info-item label {
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
}

.prof-info-item span {
    text-align: left;
    unicode-bidi: isolate;
    display: inline-block; /* یا inline-flex */
    width: 100%; /* یا مقدار مناسب دیگر */
    
    
    font-weight: 500;
    color: #2d3748;
    font-size: 14px;
}

@media (max-width: 540px) {
    .prof-profile-main-container {
        padding: 10px;
    }
    
    .prof-profile-container {
        margin: 10px 0;
        border-radius: 15px;
    }
    
    .prof-profile-header {
        padding: 25px 20px;
    }
    
    .prof-profile-content {
        padding: 20px;
    }
    
    .prof-profile-section {
        padding: 15px;
    }
    
    .prof-info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
        padding: 10px;
    }
}

/* انیمیشن‌ها */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.prof-profile-container {
    animation: fadeInUp 0.6s ease-out;
}

.prof-profile-section {
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.prof-profile-section:nth-child(2) {
    animation-delay: 0.3s;
}

.prof-profile-section:nth-child(3) {
    animation-delay: 0.4s;
}

.prof-profile-actions {
    margin-top: 25px;
    text-align: center;
}

.prof-edit-profile-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #00857a 0%, #00c9b7 100%);
    color: white;
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 133, 122, 0.3);
    border: none;
    cursor: pointer;
}

.prof-edit-profile-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 133, 122, 0.4);
    color: white;
}

.prof-edit-profile-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* استایل پیام موفقیت */
.prof-update-success-message {
    background: #f0fff4;
    color: #2d7d32;
    padding: 16px 20px;
    margin: 20px auto;
    max-width: 500px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #c8e6c9;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(46, 125, 50, 0.2);
    animation: fadeInUp 0.5s ease-out;
}

.prof-update-success-message .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    color: #2d7d32;
}

/* انیمیشن برای پیام */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* استایل پیام موفقیت داخلی */
.prof-internal-success-message {
    background: #f0fff4;
    color: #2d7d32;
    padding: 16px 20px;
    margin: 20px 25px 0 25px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #c8e6c9;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(46, 125, 50, 0.2);
    animation: fadeInUp 0.5s ease-out;
}

.prof-internal-success-message .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    color: #2d7d32;
}
</style>

<?php
get_footer();