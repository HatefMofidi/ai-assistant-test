<?php
/* /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/inc/class-persian-date-helper.php */
if (!defined('ABSPATH')) exit;

class AI_Assistant_Persian_Date_Helper {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * تبدیل تاریخ شمسی به میلادی
     */
    public function jalali_to_gregorian($j_y, $j_m, $j_d) {
        if (class_exists('Morilog\Jalali\Jalalian')) {
            try {
                $jalali = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $j_y . '/' . $j_m . '/' . $j_d);
                $gregorian = $jalali->toCarbon();
                return $gregorian->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                error_log('خطا در تبدیل تاریخ شمسی: ' . $e->getMessage());
            }
        }
        
        // Fallback: استفاده از تابع ساده (دقیق نیست اما کار میکند)
        return $this->simple_jalali_to_gregorian($j_y, $j_m, $j_d);
    }

    /**
     * تبدیل ساده تاریخ شمسی به میلادی (برای مواقعی که کتابخانه موجود نیست)
     */
    private function simple_jalali_to_gregorian($j_y, $j_m, $j_d) {
        // این یک تبدیل ساده است - برای دقت بیشتر از کتابخانه استفاده کنید
        $g_y = $j_y + 621;
        $days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        
        // محاسبه روز از ابتدای سال
        $day_of_year = $j_d;
        for ($i = 0; $i < $j_m - 1; $i++) {
            $day_of_year += $days_in_month[$i];
        }
        
        // تاریخ میلادی تقریبی
        $gregorian_date = date('Y-m-d H:i:s', strtotime("{$g_y}-03-21 +" . ($day_of_year - 1) . " days"));
        return $gregorian_date;
    }

    /**
     * دریافت تاریخ شمسی امروز
     */
    public function get_current_jalali() {
        if (class_exists('Morilog\Jalali\Jalalian')) {
            $jalali = \Morilog\Jalali\Jalalian::now();
            return [
                'year' => $jalali->getYear(),
                'month' => $jalali->getMonth(),
                'day' => $jalali->getDay()
            ];
        }
        
        // Fallback
        $current_time = current_time('timestamp');
        $jalali_year = date('Y', $current_time) - 621;
        return [
            'year' => $jalali_year,
            'month' => date('n', $current_time),
            'day' => date('j', $current_time)
        ];
    }

    /**
     * بررسی آیا امروز تاریخ مناسبت است (بر اساس تاریخ شمسی)
     */
    public function is_occasion_today($occasion_month, $occasion_day) {
        $today = $this->get_current_jalali();
        return ($today['month'] == $occasion_month && $today['day'] == $occasion_day);
    }

    /**
     * دریافت نام ماه شمسی
     */
    public function get_jalali_month_name($month) {
        $months = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];
        return $months[$month] ?? $month;
    }
}