<?php
// /inc/class-notification-manager.php

class AI_Assistant_Notification_Manager {
    private static $instance;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ارسال ایمیل به مشاور برای درخواست جدید
     */
    public function send_consultation_request($consultant_id, $request_id) {
        $consultant = get_user_by('id', $consultant_id);
        if (!$consultant) {
            error_log('[Notification] Consultant not found: ' . $consultant_id);
            return false;
        }

        $consultation_url = admin_url("admin.php?page=nutrition-consultation&action=review&id={$request_id}");
        $deadline_date = date_i18n('j F Y - H:i', strtotime('+3 days'));
        
        // محتوای ایمیل با قالب جدید
        $email_content = "
            <p>سلام <strong>{$consultant->display_name}</strong> عزیز،</p>
            <p>یک درخواست جدید برای بازبینی رژیم غذایی در سامانه ثبت شده است.</p>
            
            " . AI_Assistant_Email_Template::create_deadline_box("
                <strong>⏰ مهلت بررسی:</strong><br>
                {$deadline_date}
            ") . "
            
            <p>لطفاً در اسرع وقت این درخواست را بررسی و پاسخ لازم را ارائه نمایید.</p>
            
            " . AI_Assistant_Email_Template::create_button($consultation_url, '🔍 مشاهده و بررسی درخواست') . "
            
            <p style='margin-top: 30px;'>
                برای دسترسی به تمامی درخواست‌ها می‌توانید به پنل مدیریت مراجعه کنید.
            </p>
        ";
        
        $subject = '📋 درخواست جدید بازبینی رژیم غذایی';
        $message = AI_Assistant_Email_Template::get_email_template($email_content, $subject);
        
        return $this->send_email($consultant->user_email, $subject, $message);
    }

    /**
     * ارسال ایمیل به کاربر برای نتیجه بازبینی
     */
    public function send_consultation_result($user_id, $request_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('[Notification] User not found: ' . $user_id);
            return false;
        }

        $history_url = home_url("/service-history");
        
        // محتوای ایمیل با قالب جدید
        $email_content = "
            <p>سلام <strong>{$user->display_name}</strong> عزیز،</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; display: inline-block;'>
                    <h3 style='margin: 0; color: #155724;'>✅ رژیم غذایی شما تایید شد</h3>
                </div>
            </div>
            
            <p>رژیم غذایی شما توسط مشاور تغذیه به طور کامل بررسی و تایید نهایی شد.</p>
            
            <p>هم اکنون می‌توانید رژیم نهایی و توصیه‌های مشاور را مشاهده کنید.</p>
            
            " . AI_Assistant_Email_Template::create_button($history_url, '📄 مشاهده رژیم نهایی') . "
            
            " . AI_Assistant_Email_Template::create_info_box("
                <strong>💡 نکته مهم:</strong><br>
                برای دسترسی همیشگی به رژیم غذایی خود، این صفحه را در مرورگر خود بوکمارک کنید.
            ") . "
            
            <p>در صورت وجود هرگونه سوال، تیم پشتیبانی آماده پاسخگویی به شما می‌باشد.</p>
        ";
        
        $subject = '✅ نتیجه بازبینی رژیم غذایی شما';
        $message = AI_Assistant_Email_Template::get_email_template($email_content, $subject);
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * تابع عمومی ارسال ایمیل
     */
    private function send_email($to, $subject, $message) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <info@aidastyar.com>'
        ];

        $result = wp_mail($to, $subject, trim($message), $headers);

        if (!$result) {
            error_log("[Notification] Email sending failed to {$to} (Subject: {$subject})");
        }

        return $result;
    }
}