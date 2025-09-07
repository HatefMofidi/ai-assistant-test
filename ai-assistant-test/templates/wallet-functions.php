<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/wallet-functions.php
 * Functions for Wallet
 */
 
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// تعریف ثابت برای حداقل مبلغ شارژ
define('AI_WALLET_MINIMUM_CHARGE', 1000); // حداقل مبلغ شارژ به 1000 تومان
define('AI_WALLET_MAXIMUM_CHARGE', 9000000); // حداقل مبلغ شارژ به 1000 تومان

// اضافه کردن این کد در جایی مناسب در functions.php
function ai_wallet_get_minimum_charge() {
    return defined('AI_WALLET_MINIMUM_CHARGE') ? AI_WALLET_MINIMUM_CHARGE : 1000;
}

function ai_wallet_get_maximum_charge() {
    return defined('AI_WALLET_MAXIMUM_CHARGE') ? AI_WALLET_MAXIMUM_CHARGE : 9000000;
}

// تابع برای حداقل مبلغ به صورت فارسی
function ai_wallet_format_minimum_charge_fa() {
    return format_number_fa(ai_wallet_get_minimum_charge());
}

function ai_wallet_format_maximum_charge_fa() {
    return format_number_fa(ai_wallet_get_maximum_charge());
}

// افزودن rewrite rule برای wallet-checkout
function ai_assistant_add_wallet_checkout_rule() {
    add_rewrite_rule('^wallet-checkout/?$', 'index.php?wallet_checkout=1', 'top');
}
add_action('init', 'ai_assistant_add_wallet_checkout_rule');

// اضافه کردن query var برای wallet-checkout
function ai_assistant_add_wallet_checkout_query_var($vars) {
    $vars[] = 'wallet_checkout';
    return $vars;
}
add_filter('query_vars', 'ai_assistant_add_wallet_checkout_query_var');

// مدیریت تمپلیت برای wallet-checkout
function ai_assistant_wallet_checkout_template($template) {
    if (get_query_var('wallet_checkout')) {
        $new_template = locate_template(array('templates/wallet-checkout.php')); // تغییر این خط
        if (!empty($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'ai_assistant_wallet_checkout_template');

require_once get_template_directory() . '/inc/class-wallet-checkout-handler.php';

// شروع session در وردپرس
function ai_assistant_start_session() {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
}
add_action('init', 'ai_assistant_start_session', 1);

// ذخیره مبلغ در session هنگام ارسال فرم
function ai_assistant_save_charge_amount() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['charge_amount']) && !empty($_POST['charge_amount'])) {
        $_SESSION['wallet_charge_amount'] = intval($_POST['charge_amount']);
    }
}
add_action('template_redirect', 'ai_assistant_save_charge_amount');

// پاکسازی session پس از پرداخت
function ai_assistant_clear_payment_session() {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
    
    // اگر کاربر از صفحه پرداخت خارج شد، session را پاک کنید
    if (!is_page('wallet-checkout') && isset($_SESSION['wallet_charge_amount'])) {
        unset($_SESSION['wallet_charge_amount']);
    }
    
    // پس از تکمیل پرداخت، session را پاک کنید
    if (isset($_GET['payment_verify']) && $_GET['payment_verify'] == '1') {
        if (isset($_SESSION['wallet_charge_amount'])) {
            unset($_SESSION['wallet_charge_amount']);
        }
        if (isset($_SESSION['wallet_payment_amount'])) {
            unset($_SESSION['wallet_payment_amount']);
        }
        if (isset($_SESSION['wallet_payment_authority'])) {
            unset($_SESSION['wallet_payment_authority']);
        }
    }
}
add_action('template_redirect', 'ai_assistant_clear_payment_session');

// تعریف ثابت‌های زرین پال
define('ZARINPAL_MERCHANT_ID', 'd05ca4ae-fab1-49b3-8da8-2e2d07b32fc9'); // مرچنت کد خود را قرار دهید
if (!defined('ZARINPAL_SANDBOX')) {
    define('ZARINPAL_SANDBOX', defined('OTP_ENV') && OTP_ENV === 'sandbox');
}
define('ZARINPAL_SANDBOX_MERCHANT_ID', 'd05ca4ae-fab1-49b3-8da8-2e2d07b32fc9'); // مرچنت کد sandbox

// تابع برای دریافت مرچنت آیدی مناسب
function ai_assistant_get_zarinpal_merchant_id() {
    return ZARINPAL_SANDBOX ? ZARINPAL_SANDBOX_MERCHANT_ID : ZARINPAL_MERCHANT_ID;
}

// تابع برای دریافت آدرس API مناسب
function ai_assistant_get_zarinpal_api_url() {
    return ZARINPAL_SANDBOX ? 
        'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl' :
        'https://www.zarinpal.com/pg/services/WebGate/wsdl';
}

// تابع برای دریافت آدرس درگاه پرداخت مناسب
function ai_assistant_get_zarinpal_gateway_url() {
    return ZARINPAL_SANDBOX ?
        'https://sandbox.zarinpal.com/pg/StartPay/' :
        'https://www.zarinpal.com/pg/StartPay/';
}

function ai_assistant_process_payment_return() {
    if (isset($_GET['payment_verify']) && $_GET['payment_verify'] == '1') {
        error_log('🔵 [WALLET] Payment return detected');
        
        $authority = isset($_GET['Authority']) ? sanitize_text_field($_GET['Authority']) : '';
        $status = isset($_GET['Status']) ? sanitize_text_field($_GET['Status']) : 'NOK';
        
        error_log('🔵 [WALLET] Authority: ' . $authority . ', Status: ' . $status);
        
        if ($status == 'OK' && !empty($authority)) {
            $payment_handler = AI_Assistant_Wallet_Checkout_Handler::get_instance();
            
            // بازیابی اطلاعات پرداخت از دیتابیس
            $payment_data = $payment_handler->get_payment_by_authority($authority);
            
            if ($payment_data) {
                error_log('🔵 [WALLET] Payment data found: ' . print_r($payment_data, true));
                
                $amount = $payment_data->amount;
                $user_id = $payment_data->user_id;
                
                error_log('🔵 [WALLET] Verifying payment: Amount=' . $amount . ', UserID=' . $user_id);
                
                // تأیید پرداخت
                $verification_result = $payment_handler->verify_payment($authority, $amount);
                
                error_log('🔵 [WALLET] Verification result: ' . print_r($verification_result, true));
                
                if ($verification_result['status']) {
                    // شارژ کیف پول
                    $wallet_handler = AI_Assistant_Payment_Handler::get_instance();
                    $success = $wallet_handler->add_credit(
                        $user_id, 
                        $amount, 
                        'شارژ کیف پول از طریق درگاه پرداخت - کد پیگیری: ' . $verification_result['ref_id'],
                        'zarinpal_' . $verification_result['ref_id']
                    );
                    
                    if ($success) {
                        error_log('✅ [WALLET] Wallet charged successfully');
                        wp_redirect(home_url('/wallet-charge?payment=success&ref_id=' . $verification_result['ref_id']));
                    } else {
                        error_log('❌ [WALLET] Wallet charge failed');
                        wp_redirect(home_url('/wallet-charge?payment=failed&reason=wallet_charge_failed'));
                    }
                    exit;
                } else {
                    error_log('❌ [WALLET] Payment verification failed');
                    wp_redirect(home_url('/wallet-charge?payment=failed&reason=' . urlencode($verification_result['message'])));
                    exit;
                }
            } else {
                error_log('❌ [WALLET] No payment data found for authority: ' . $authority);
                wp_redirect(home_url('/wallet-charge?payment=error&reason=payment_data_not_found'));
                exit;
            }
        } else {
            error_log('❌ [WALLET] Payment cancelled or invalid status');
            wp_redirect(home_url('/wallet-charge?payment=cancelled'));
            exit;
        }
    }
}
add_action('template_redirect', 'ai_assistant_process_payment_return');


// اجرای cleanup روزانه برای پرداخت‌های pending قدیمی
function wallet_payment_cleanup() {
    $handler = AI_Assistant_Wallet_Checkout_Handler::get_instance();
    $handler->cleanup_old_payments();
}
add_action('wp_daily_cleanup', 'wallet_payment_cleanup');

// ایجاد هوک cleanup روزانه اگر وجود ندارد
if (!wp_next_scheduled('wp_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'wp_daily_cleanup');
}
