<?php
// /inc/class-service-manager.php

class AI_Assistant_Service_Manager {
    private static $instance;
    private $db;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->db = AI_Assistant_Service_DB::get_instance();
         $this->logger = AI_Assistant_Logger::get_instance();
    }
    
    // افزودن/ویرایش سرویس
    public function update_service($service_id, $data) {
        $sanitized_data = [
            'id' => sanitize_text_field($_POST['service_id']),
            'name' => sanitize_text_field($data['name']),
            'price' => absint($data['price']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
//            'system_prompt' => $data['system_prompt'], // اعتبارسنجی خاص برای این فیلد
            'system_prompt' => $this->sanitize_system_prompt($data['system_prompt'] ?? ''),
            'icon' => sanitize_text_field($data['icon']),
            'active' => isset($data['active']) ? (bool)$data['active'] : false,
            'template' => sanitize_text_field($data['template'] ?? '')
        ];
        
        // اگر سرویس وجود دارد، آن را به‌روزرسانی کن
        if ($this->db->get_service($service_id)) {
            
        $this->logger->log('service-manager.update_service', [
                'step' => $data
            ]);             
            
            return $this->db->update_service($service_id, $sanitized_data);
        }
        
        // در غیر این صورت سرویس جدید اضافه کن
        $sanitized_data['service_id'] = $service_id;
        return $this->db->add_service($sanitized_data);
    }
  
  
  
    /**
     * سانیتیزیشن تخصصی برای system_prompt
     * بدون آسیب زدن به ساختار JSON
     */
    private function sanitize_system_prompt($prompt) {
        if (empty($prompt)) {
            return '';
        }
        
        // حذف slash های اضافی ناشی از magic quotes
        $prompt = stripslashes($prompt);
        $prompt = wp_unslash($prompt);
        
        // اگر داده JSON است، آن را نرمالایز کن
        if ($this->is_valid_json($prompt)) {
            return $this->normalize_json($prompt);
        }
        
        // برای متن معمولی، حذف تگ‌های HTML خطرناک
        return wp_kses_post($prompt);
    }
    
    /**
     * بررسی معتبر بودن JSON
     */
    private function is_valid_json($string) {
        if (!is_string($string) || empty($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * نرمالایز کردن JSON - حذف escape های اضافی
     */
    private function normalize_json($json_string) {
        $decoded = json_decode($json_string, true);
        
        if ($decoded === null) {
            return $json_string; // اگر decode شکست خورد، داده اصلی را برگردان
        }
        
        // encode مجدد با حذف escape های غیرضروری
        return json_encode(
            $decoded, 
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }  
    
    // ثبت سرویس جدید
    public function register_service($service_data) {
        $defaults = [
            'service_id' => '',
            'name' => '',
            'description' => '',
            'system_prompt' => '',
            'price' => 0,
            'icon' => 'dashicons-admin-generic',
            'template' => '',
            'active' => true
        ];
        
        $service = wp_parse_args($service_data, $defaults);
        
        if (empty($service['service_id'])) {
            return false;
        }
        
        return $this->db->add_service($service);
    }
     
    // افزودن سرویس جدید از طریق رابط کاربری
    public function add_new_service() {
        $new_id = 'service_' . uniqid();
        $service_data = [
            'service_id' => $new_id,
            'name' => 'سرویس جدید',
            'price' => 0,
            'description' => '',
            'system_prompt' => '',
            'icon' => 'dashicons-admin-generic',
            'active' => true,
            'template' => ''
        ];
        
        if ($this->db->add_service($service_data)) {
            return $new_id;
        }
        
        return false;
    }

   
    
    // دریافت اطلاعات سرویس
    public function get_service($service_id) {
        return $this->db->get_service($service_id);
    }
    
    // دریافت همه سرویس‌های فعال
    public function get_active_services() {
        return $this->db->get_all_services(true);
    }
    
    // دریافت همه سرویس‌ها
    public function get_all_services() {
        return $this->db->get_all_services();
    }
    
    // دریافت قیمت سرویس
    public function get_service_price($service_id) {
        $service = $this->db->get_service($service_id);
        return $service ? (int)$service['price'] : 0;
    }
    
    
    public function delete_service($service_id) {
        // ابتدا بررسی می‌کنیم سرویس وجود دارد یا نه
        if (!isset($this->services[$service_id])) {
            return false;
        }
        
        // حذف سرویس از آرایه
        unset($this->services[$service_id]);
        
        // ذخیره تغییرات
        return $this->save_services();
    }    
}