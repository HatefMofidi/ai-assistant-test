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
        
        $subject = 'درخواست جدید بازبینی رژیم غذایی';
        $message = "
            سلام {$consultant->display_name},
            
            یک درخواست جدید برای بازبینی رژیم غذایی دارید.
            
            مهلت بررسی: " . date_i18n('j F Y - H:i', strtotime('+3 days')) . "
            
            برای مشاهده و بررسی درخواست، روی لینک زیر کلیک کنید:
            {$consultation_url}
            
            با تشکر
            سیستم " . get_bloginfo('name') . "
        ";
        
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
        
        $subject = 'نتیجه بازبینی رژیم غذایی شما';
        $message = "
            سلام {$user->display_name},
            
            رژیم غذایی شما توسط مشاور تغذیه بررسی و تایید شد.
            
            برای مشاهده رژیم نهایی، به صفحه تاریخچه سرویس‌ها مراجعه کنید:
            {$history_url}
            
            با تشکر
            " . get_bloginfo('name') . "
        ";
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * ارسال ایمیل
     */
    private function send_email($to, $subject, $message) {
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        $result = wp_mail($to, $subject, trim($message), $headers);
        
        if (!$result) {
            error_log('[Notification] Email sending failed to: ' . $to);
        }
        
        return $result;
    }
}