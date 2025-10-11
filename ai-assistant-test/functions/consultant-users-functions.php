<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/consultant-users-functions.php
 */

if (!defined('ABSPATH')) exit;

// ایجاد جدول مشاورین تغذیه
function create_nutrition_consultants_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'nutrition_consultants';
    
    // چک کردن وجود جدول
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            specialty varchar(200) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('Nutrition consultants table created successfully');
    }
}

// تابع برای درج داده‌های تستی
function insert_test_nutrition_consultants() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'nutrition_consultants';
    
    // فقط در محیط sandbox و اگر جدول خالی است
    if ((defined('WP_ENV') && WP_ENV === 'sandbox') || 
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'test') !== false)) {
        
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($existing_count == 0) {
            $wpdb->insert($table_name, [
                'name' => 'دکتر مریم احمدی',
                'specialty' => 'متخصص تغذیه و رژیم درمانی',
                'is_active' => 1
            ]);
            
            $wpdb->insert($table_name, [
                'name' => 'دکتر علی رضایی', 
                'specialty' => 'متخصص غدد و متابولیسم',
                'is_active' => 1
            ]);
            
            $wpdb->insert($table_name, [
                'name' => 'دکتر سارا محمدی',
                'specialty' => 'متخصص تغذیه ورزشی',
                'is_active' => 1
            ]);
            
            error_log('Test nutrition consultants data inserted: ' . $wpdb->rows_affected . ' rows');
        }
    }
}

// ثبت هوک‌ها
add_action('after_switch_theme', 'create_nutrition_consultants_table');
add_action('init', 'create_nutrition_consultants_table');
add_action('init', 'insert_test_nutrition_consultants');


// دریافت لیست مشاورین تغذیه
function get_nutrition_consultants() {
    global $wpdb;
    
    // بررسی nonce
    if (!wp_verify_nonce($_POST['security'], 'ai_assistant_nonce')) {
        wp_die('خطای امنیتی');
    }
    
    $table_name = $wpdb->prefix . 'nutrition_consultants';
    
    $consultants = $wpdb->get_results("
        SELECT id, name, specialty 
        FROM $table_name 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
    
    wp_send_json_success([
        'consultants' => $consultants
    ]);
}
add_action('wp_ajax_get_nutrition_consultants', 'get_nutrition_consultants');
add_action('wp_ajax_nopriv_get_nutrition_consultants', 'get_nutrition_consultants');