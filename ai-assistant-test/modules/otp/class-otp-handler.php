<?php
/*/home/aidastya/public_html/test/wp-content/themes/ai-assistant/modules/otp/class-otp-handler.php*/
if (!defined('ABSPATH')) {
    exit;
}

class OTP_Handler {
    private static $instance = null;
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        require_once __DIR__ . '/otp-ajax.php';
        add_filter('theme_page_templates', [$this, 'add_otp_template']);
        add_filter('template_include', [$this, 'load_otp_template']);
        add_filter('wp_nav_menu_items', [$this, 'add_login_menu_item'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'load_assets']);
    }
    
    public function add_otp_template($templates) {
        $templates['custom-login.php'] = 'Custom OTP Login';
        return $templates;
    }
    
    public function load_otp_template($template) {
        if (get_page_template_slug() === 'custom-login.php') {
            return __DIR__ . '/otp-login-template.php';
        }
        return $template;
    }
    
    public function add_login_menu_item($items, $args) {
        if ($args->theme_location == 'primary') {
            if (!is_user_logged_in()) {
                $items .= '<li><a href="' . esc_url(home_url('/otp-login')) . '">ورود / ثبت‌نام</a></li>';
            } else {
                $items .= '<li><a href="#" class="logout-link" data-nonce="' . wp_create_nonce('log-out') . '">خروج</a></li>';
            }
        }
        return $items;
    }
    
    public function load_assets() {
        if (is_page_template('custom-login.php')) {
            wp_enqueue_style('otp-css', get_template_directory_uri() . '/modules/otp/otp-assets/otp.css');
            wp_enqueue_script('otp-js', get_template_directory_uri() . '/modules/otp/otp-assets/otp.js', ['jquery'], null, true);
            
            wp_localize_script('otp-js', 'otp_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'home_url' => home_url('/'),
                'nonce' => wp_create_nonce('custom_logout_nonce'),
                'is_sandbox' => defined('OTP_ENV') && OTP_ENV === 'sandbox',
                'is_bypass' => defined('OTP_ENV') && OTP_ENV === 'bypass'
            ]);
        }
    }
    
    public static function check_rate_limit($mobile) {
        if (defined('OTP_ENV') && (OTP_ENV === 'sandbox' || OTP_ENV === 'bypass')) {
            return true;
        }    
        $max_attempts = 5;
        $time_window = HOUR_IN_SECONDS;
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'otp_rate_' . md5($mobile . $ip);
        
        $attempts = (int) get_transient($transient_key);
        
        if ($attempts >= $max_attempts) {
            $remaining = get_transient_timeout($transient_key);
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    'برای جلوگیری از سوءاستفاده، تعداد درخواست‌های شما محدود شده است. لطفاً %d دقیقه دیگر مجدداً تلاش کنید.',
                    ceil($remaining / 60)
                )
            );
        }
        
        $ip_key = 'otp_ip_limit_' . md5($ip);
        $ip_attempts = (int) get_transient($ip_key);
        
        if ($ip_attempts >= $max_attempts * 4) {
            return new WP_Error(
                'ip_rate_limit',
                'تعداد درخواست‌های شما از این آدرس IP بیش از حد مجاز است.'
            );
        }
        
        set_transient($transient_key, $attempts + 1, $time_window);
        set_transient($ip_key, $ip_attempts + 1, $time_window);
        return true;
    }
    
    private function log_otp_attempt($data) {
        $log_file = WP_CONTENT_DIR . '/otp-logs/' . date('Y-m-d') . '.log';
        
        if(!file_exists(WP_CONTENT_DIR . '/otp-logs')) {
            wp_mkdir_p(WP_CONTENT_DIR . '/otp-logs');
        }
        
        $log_line = sprintf(
            "[%s] Mobile: %s, IP: %s, Status: %s, Error: %s\n",
            current_time('mysql'),
            $data['mobile'],
            $data['ip'],
            $data['status'],
            $data['error'] ?? ''
        );
        
        file_put_contents($log_file, $log_line, FILE_APPEND);
    }    
}

OTP_Handler::get_instance();