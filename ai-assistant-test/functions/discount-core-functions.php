<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/discount-core-functions.php
 */ 
if (!defined('ABSPATH')) exit;

class AI_Assistant_Discount_Manager {
    
    public static function validate_discount($discount_code, $service_id, $user_id) {
        $discount_db = AI_Assistant_Discount_DB::get_instance();
        
        // استفاده از متدهای موجود در کلاس فعلی
        $all_discounts = $discount_db->get_all_discounts();
        $now = current_time('mysql');
        
        foreach ($all_discounts as $discount) {
            if ($discount->code === $discount_code && 
                $discount->active == 1 &&
                self::is_discount_valid($discount, $now) &&
                self::check_discount_scope($discount, $service_id, $user_id)) {
                
                return [
                    'valid' => true,
                    'discount' => $discount,
                    'message' => 'کد تخفیف اعمال شد'
                ];
            }
        }
        
        return ['valid' => false, 'message' => 'کد تخفیف معتبر نیست'];
    }
    
    private static function is_discount_valid($discount, $now) {
        if ($discount->start_date && $discount->start_date > $now) return false;
        if ($discount->end_date && $discount->end_date < $now) return false;
        if ($discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit) return false;
        return true;
    }
    
    private static function check_discount_scope($discount, $service_id, $user_id) {
        $discount_db = AI_Assistant_Discount_DB::get_instance();
        
        switch ($discount->scope) {
            case 'global':
                return true;
                
            case 'service':
                $services = $discount_db->get_discount_services($discount->id);
                return in_array($service_id, $services);
                
            case 'user_based':
                if ($discount->user_restriction === 'specific_users') {
                    $users = $discount_db->get_discount_users($discount->id);
                    return in_array($user_id, $users);
                }
                return true;
                
            default:
                return true;
        }
    }
        
    public static function calculate_discounted_price($original_price, $discount) {
        error_log("Calculating discount - Original: " . $original_price . ", Type: " . $discount->type . ", Amount: " . $discount->amount);
        
        if ($discount->type === 'percentage') {
            $discounted = $original_price - ($original_price * ($discount->amount / 100));
        } else {
            $discounted = max(0, $original_price - $discount->amount);
        }
        
        error_log("Calculated discounted price: " . $discounted);
        return $discounted;
    }
} 