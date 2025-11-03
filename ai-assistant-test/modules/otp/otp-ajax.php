<?php
// /home/aidastya/public_html/test/wp-content/themes/ai-assistant/modules/otp/otp-ajax.php
if (!defined('ABSPATH')) {
    exit;
}

// تابع ارسال OTP
function send_otp_request() {
    try {
        $mobile = sanitize_text_field($_POST['mobile']);
        
        // بررسی محیط اجرا
        $is_sandbox = (defined('OTP_ENV') && OTP_ENV === 'sandbox');
        $is_bypass = (defined('OTP_ENV') && OTP_ENV === 'bypass');
        
        // غیرفعال کردن Rate Limit در محیط‌های تستی و bypass
        if (!$is_sandbox && !$is_bypass) {
            $rate_check = OTP_Handler::check_rate_limit($mobile);
            if (is_wp_error($rate_check)) {
                throw new Exception($rate_check->get_error_message());
            }
        }
        
        // اعتبارسنجی شماره موبایل
        if(empty($mobile) || !preg_match('/^09[0-9]{9}$/', $mobile)) {
            throw new Exception('شماره موبایل معتبر نیست (فرمت صحیح: 09123456789)');
        }
        
        // تولید کد تصادفی
        $otp_code = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $transient_name = 'otp_' . $mobile;
        set_transient($transient_name, $otp_code, 10 * MINUTE_IN_SECONDS);
        
        // اگر در محیط sandbox یا bypass هستیم
        if ($is_sandbox || $is_bypass) {
            wp_send_json_success([
                'message' => 'کد آزمایشی تولید شد',
                'debug_code' => $otp_code,
                'mobile' => $mobile,
                'is_test' => true
            ]);
            return;
        }

        // محیط عملیاتی - ارسال واقعی پیامک
        $response = wp_remote_post('https://api.sms.ir/v1/send/verify', [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'text/plain',
                'x-api-key' => SMS_API_KEY
            ],
            'body' => json_encode([
                'mobile' => $mobile,
                'templateId' => SMS_TEMPLATE_ID,
                'parameters' => [['name' => 'Code', 'value' => $otp_code]]
            ])
        ]);

        error_log("[OTP API REQUEST] Request: " . print_r([
            'url' => 'https://api.sms.ir/v1/send/verify',
            'headers' => ['x-api-key' => '****' . substr(SMS_API_KEY, -4)],
            'body' => ['mobile' => $mobile, 'templateId' => SMS_TEMPLATE_ID]
        ], true));

        if(is_wp_error($response)) {
            throw new Exception('خطای وردپرس: ' . $response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("[OTP API RESPONSE] HTTP Code: $http_code, Body: $body");

        if($http_code != 200) {
            throw new Exception("کد وضعیت غیرمنتظره: $http_code");
        }

        $data = json_decode($body, true);
        if(!$data) {
            throw new Exception("پاسخ نامعتبر از سرور: $body");
        }

        if(isset($data['status']) && $data['status'] == 1) {
            wp_send_json_success(['message' => 'کد تایید ارسال شد']);
        } else {
            $error = $data['message'] ?? 'خطای نامشخص از سرور پیامک';
            throw new Exception($error);
        }

    } catch (Exception $e) {
        error_log("[OTP ERROR] Mobile: $mobile, Error: " . $e->getMessage());
        wp_send_json_error('خطا در سرویس پیامک: ' . $e->getMessage());
    }
}
add_action('wp_ajax_send_otp', 'send_otp_request');
add_action('wp_ajax_nopriv_send_otp', 'send_otp_request');

// تابع تایید OTP
function verify_otp_request() {
    try {
        $mobile = sanitize_text_field($_POST['mobile']);
        $otp_code = sanitize_text_field($_POST['otp_code']);
        
        if(empty($mobile) || empty($otp_code)) {
            throw new Exception('لطفا کد تایید را وارد کنید');
        }
        
        $transient_name = 'otp_' . $mobile;
        $stored_otp = get_transient($transient_name);
        
        if($stored_otp === false) {
            throw new Exception('کد تایید منقضی شده است. لطفاً کد جدیدی دریافت کنید');
        }
        
        if($stored_otp != $otp_code) {
            // افزایش شمارنده تلاش‌های ناموفق
            $fail_key = 'otp_fails_' . md5($mobile . $_SERVER['REMOTE_ADDR']);
            $fails = (int) get_transient($fail_key);
            set_transient($fail_key, $fails + 1, 15 * MINUTE_IN_SECONDS);
            
            if ($fails >= 5) {
                throw new Exception('تعداد تلاش‌های ناموفق شما بیش از حد مجاز است. لطفاً ۱۵ دقیقه دیگر تلاش کنید.');
            }
            
            throw new Exception('کد تایید نادرست است');
        }
        
        // پیدا کردن یا ایجاد کاربر
        $user = get_user_by('login', $mobile);
        
        if(!$user) {
            $userdata = array(
                'user_login' => $mobile,
                'user_pass'  => wp_generate_password(),
                'user_email' => '', // ایمیل خالی
                'role'       => 'subscriber'
            );
            
            $user_id = wp_insert_user($userdata);
            
            if(is_wp_error($user_id)) {
                throw new Exception('خطا در ایجاد حساب کاربری: ' . $user_id->get_error_message());
            }
            
            update_user_meta($user_id, 'mobile', $mobile);
            $user = get_user_by('id', $user_id);
        }
        
        // ورود کاربر
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        delete_transient($transient_name);
        
        wp_send_json_success('ورود موفقیت‌آمیز');
        
    } catch (Exception $e) {
        error_log("[OTP VERIFY ERROR] " . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_verify_otp', 'verify_otp_request');
add_action('wp_ajax_nopriv_verify_otp', 'verify_otp_request');


// اضافه کردن این کد در انتهای فایل (قبل از بسته شدن تگ PHP اگر وجود دارد)
add_action('wp_ajax_force_logout', 'force_logout_handler');
add_action('wp_ajax_nopriv_force_logout', 'force_logout_handler');
function force_logout_handler() {
    check_ajax_referer('custom_logout_nonce', 'security');
    
    // تخریب کامل session
    wp_destroy_current_session();
    wp_clear_auth_cookie();
    wp_set_current_user(0);
    
    // پاک کردن تمام کوکی‌های مرتبط
    if (isset($_COOKIE)) {
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'wordpress') !== false || strpos($name, 'wp-settings') !== false) {
                unset($_COOKIE[$name]);
                setcookie($name, '', time() - 3600, '/');
                setcookie($name, '', time() - 3600, '/', COOKIE_DOMAIN);
            }
        }
    }
    
    // اطمینان از عدم کش شدن
    nocache_headers();
    
    wp_send_json_success(['redirect' => home_url()]);
}