<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/inc/class-comments-db.php
 */
if (!defined('ABSPATH')) exit;

class AI_Assistant_Comments_DB {
    private static $instance = null;
    private $table_name;

    public function get_table_name() {
        return $this->table_name;
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'service_comments';
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            comment_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id VARCHAR(100) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            comment_text TEXT NOT NULL,
            rating TINYINT(1) UNSIGNED DEFAULT 0,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (comment_id),
            KEY service_id (service_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY rating (rating)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_comment($data) {
        global $wpdb;
        
        $defaults = array(
            'service_id' => '',
            'user_id' => get_current_user_id(),
            'comment_text' => '',
            'rating' => 0,
            'status' => 'pending'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert(
            $this->table_name,
            $data,
            array('%s', '%d', '%s', '%d', '%s')
        );
    }

    public function get_comments($service_id, $status = 'approved', $limit = 10, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.user_login, u.display_name 
             FROM {$this->table_name} c 
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
             WHERE c.service_id = %s AND c.status = %s 
             ORDER BY c.created_at DESC 
             LIMIT %d OFFSET %d",
            $service_id, $status, $limit, $offset
        ));
    }

    public function get_average_rating($service_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM {$this->table_name} 
             WHERE service_id = %s AND status = 'approved'",
            $service_id
        ));
    }

    public function get_comment_count($service_id, $status = 'approved') {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE service_id = %s AND status = %s",
            $service_id, $status
        ));
    }
}