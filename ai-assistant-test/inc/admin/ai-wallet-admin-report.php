<?php
/**
 * گزارش کیف پول هوش مصنوعی - برای مدیریت
 */

class AI_Assistant_Wallet_Admin_Report {
    private static $instance;
    private $table_name;
    private $history_table;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wallet_balance';
        $this->history_table = $wpdb->prefix . 'wallet_history';
        
        // اضافه کردن منوی ادمین
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // اضافه کردن استایل‌ها و اسکریپت‌ها
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        
        // پردازش فیلترها و خروجی
        add_action('admin_init', [$this, 'handle_actions']);
    }
    
    public function enqueue_admin_styles($hook) {
        if ($hook !== 'toplevel_page_ai-wallet-report') {
            return;
        }
        
        wp_enqueue_style('ai-wallet-admin', get_template_directory_uri() . '/assets/css/admin/services-admin.css', [], '1.0.0');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);
        wp_enqueue_script('ai-wallet-admin', get_template_directory_uri() . '/assets/js/admin/ai-wallet-admin.js', ['chart-js'], '1.0.0', true);
               
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'گزارش کیف پول هوش مصنوعی',
            'کیف پول AI',
            'manage_options',
            'ai-wallet-report',
            [$this, 'render_admin_page'],
            'dashicons-money-alt',
            30
        );
    }
    
    public function handle_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'ai-wallet-report') {
            return;
        }
        
        // پردازش درخواست خروجی
        if (isset($_GET['export'])) {
            $this->handle_export();
        }
        
        // پردازش فیلترها
        if (isset($_POST['apply_filters'])) {
            $this->save_filters();
        }
        
        // بازنشانی فیلترها
        if (isset($_GET['reset_filters'])) {
            $this->reset_filters();
        }
    }
    
    private function handle_export() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $export_type = sanitize_text_field($_GET['export']);
        
        switch ($export_type) {
            case 'transactions':
                $this->export_transactions_to_csv();
                break;
            case 'users':
                $this->export_users_to_csv();
                break;
            case 'summary':
                $this->export_summary_to_csv();
                break;
        }
    }
    
    private function export_transactions_to_csv() {
        global $wpdb;
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="wallet-transactions-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // افزودن BOM برای UTF-8
        echo "\xEF\xBB\xBF";
        echo "ID\tUser ID\tUser Name\tAmount\tType\tNew Balance\tDescription\tDate\n";
        
        $filters = $this->get_filters();
        $where_conditions = ['1=1'];
        $query_params = [];
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = 'h.user_id = %d';
            $query_params[] = intval($filters['user_id']);
        }
        
        if (!empty($filters['transaction_type']) && $filters['transaction_type'] !== 'all') {
            $where_conditions[] = 'h.type = %s';
            $query_params[] = sanitize_text_field($filters['transaction_type']);
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'h.created_at >= %s';
            $query_params[] = sanitize_text_field($filters['date_from']) . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'h.created_at <= %s';
            $query_params[] = sanitize_text_field($filters['date_to']) . ' 23:59:59';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT h.*, u.user_login, u.display_name 
                 FROM {$this->history_table} h 
                 LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID 
                 WHERE {$where_clause}
                 ORDER BY h.created_at DESC";
        
        if (!empty($query_params)) {
            $query = $wpdb->prepare($query, $query_params);
        }
        
        $transactions = $wpdb->get_results($query);
        
        foreach ($transactions as $transaction) {
            $user_name = !empty($transaction->display_name) ? $transaction->display_name : $transaction->user_login;
            $type = $transaction->type === 'credit' ? 'شارژ' : 'کسر';
            
            echo "{$transaction->id}\t";
            echo "{$transaction->user_id}\t";
            echo "{$user_name}\t";
            echo "{$transaction->amount}\t";
            echo "{$type}\t";
            echo "{$transaction->new_balance}\t";
            echo "{$transaction->description}\t";
            echo date('Y-m-d H:i', strtotime($transaction->created_at)) . "\n";
        }
        
        exit;
    }
    
    private function export_users_to_csv() {
        global $wpdb;
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="wallet-users-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo "\xEF\xBB\xBF";
        echo "User ID\tUser Name\tBalance\tLast Update\n";
        
        $users = $wpdb->get_results(
            "SELECT w.*, u.user_login, u.display_name 
             FROM {$this->table_name} w 
             LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID 
             ORDER BY w.balance DESC"
        );
        
        foreach ($users as $user) {
            $user_name = !empty($user->display_name) ? $user->display_name : $user->user_login;
            
            echo "{$user->user_id}\t";
            echo "{$user_name}\t";
            echo "{$user->balance}\t";
            echo date('Y-m-d H:i', strtotime($user->updated_at)) . "\n";
        }
        
        exit;
    }
    
    private function export_summary_to_csv() {
        $stats = $this->get_wallet_stats();
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="wallet-summary-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo "\xEF\xBB\xBF";
        
        echo "گزارش خلاصه کیف پول\n";
        echo "تاریخ تولید: " . date('Y-m-d H:i') . "\n\n";
        
        echo "آمار کلی\n";
        echo "مجموع موجودی کاربران: " . number_format($stats['total_balance']) . "\n";
        echo "کل شارژ شده: " . number_format($stats['total_charged']) . "\n";
        echo "کل مصرف شده: " . number_format($stats['total_spent']) . "\n";
        echo "کاربران دارای موجودی: " . number_format($stats['users_with_wallet']) . "\n\n";
        
        echo "توزیع موجودی\n";
        echo "0-10,000: " . $stats['balance_distribution']['0-10000'] . "\n";
        echo "10,000-50,000: " . $stats['balance_distribution']['10000-50000'] . "\n";
        echo "50,000-100,000: " . $stats['balance_distribution']['50000-100000'] . "\n";
        echo "100,000-500,000: " . $stats['balance_distribution']['100000-500000'] . "\n";
        echo "500,000+: " . $stats['balance_distribution']['500000+'] . "\n";
        
        exit;
    }
    
    private function save_filters() {
        $filters = [
            'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : '',
            'transaction_type' => isset($_POST['transaction_type']) ? sanitize_text_field($_POST['transaction_type']) : 'all',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'amount_min' => isset($_POST['amount_min']) ? floatval($_POST['amount_min']) : '',
            'amount_max' => isset($_POST['amount_max']) ? floatval($_POST['amount_max']) : ''
        ];
        
        update_option('ai_wallet_report_filters', $filters);
    }
    
    private function reset_filters() {
        delete_option('ai_wallet_report_filters');
    }
    
    private function get_filters() {
        return get_option('ai_wallet_report_filters', []);
        if (empty($filters)) {
            $filters['date_from'] = date('Y-m-d', strtotime('-7 days'));
            $filters['date_to'] = date('Y-m-d');
            $filters['transaction_type'] = 'all';
        }
    }
    
    private function get_wallet_stats() {
        global $wpdb;
        
        $stats = [
            'total_balance' => 0,
            'total_charged' => 0,
            'total_spent' => 0,
            'users_with_wallet' => 0,
            'balance_distribution' => [
                '0-10000' => 0,
                '10000-50000' => 0,
                '50000-100000' => 0,
                '100000-500000' => 0,
                '500000+' => 0
            ]
        ];
        
        // جمع موجودی
        $total_balance = $wpdb->get_var("SELECT SUM(balance) FROM {$this->table_name}");
        if ($total_balance) {
            $stats['total_balance'] = floatval($total_balance);
        }
        
        // جمع شارژ شده
        $total_charged = $wpdb->get_var(
            "SELECT SUM(amount) FROM {$this->history_table} WHERE type = 'credit'"
        );
        if ($total_charged) {
            $stats['total_charged'] = floatval($total_charged);
        }
        
        // جمع مصرف شده
        $total_spent = $wpdb->get_var(
            "SELECT SUM(amount) FROM {$this->history_table} WHERE type = 'debit'"
        );
        if ($total_spent) {
            $stats['total_spent'] = floatval($total_spent);
        }
        
        // تعداد کاربران دارای موجودی
        $users_with_wallet = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE balance > 0"
        );
        if ($users_with_wallet) {
            $stats['users_with_wallet'] = intval($users_with_wallet);
        }
        
        // توزیع موجودی
        $distribution = $wpdb->get_results(
            "SELECT 
                SUM(CASE WHEN balance BETWEEN 0 AND 10000 THEN 1 ELSE 0 END) as range1,
                SUM(CASE WHEN balance BETWEEN 10000 AND 50000 THEN 1 ELSE 0 END) as range2,
                SUM(CASE WHEN balance BETWEEN 50000 AND 100000 THEN 1 ELSE 0 END) as range3,
                SUM(CASE WHEN balance BETWEEN 100000 AND 500000 THEN 1 ELSE 0 END) as range4,
                SUM(CASE WHEN balance >= 500000 THEN 1 ELSE 0 END) as range5
             FROM {$this->table_name} WHERE balance > 0"
        );
        
        if ($distribution) {
            $stats['balance_distribution'] = [
                '0-10000' => intval($distribution[0]->range1),
                '10000-50000' => intval($distribution[0]->range2),
                '50000-100000' => intval($distribution[0]->range3),
                '100000-500000' => intval($distribution[0]->range4),
                '500000+' => intval($distribution[0]->range5)
            ];
        }
        
        return $stats;
    }
    
    private function get_filtered_transactions($per_page = 20, $page = 1) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $filters = $this->get_filters();
        
        $where_conditions = ['1=1'];
        $query_params = [];
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = 'h.user_id = %d';
            $query_params[] = intval($filters['user_id']);
        }
        
        if (!empty($filters['transaction_type']) && $filters['transaction_type'] !== 'all') {
            $where_conditions[] = 'h.type = %s';
            $query_params[] = sanitize_text_field($filters['transaction_type']);
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'h.created_at >= %s';
            $query_params[] = sanitize_text_field($filters['date_from']) . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'h.created_at <= %s';
            $query_params[] = sanitize_text_field($filters['date_to']) . ' 23:59:59';
        }
        
        if (!empty($filters['amount_min'])) {
            $where_conditions[] = 'h.amount >= %f';
            $query_params[] = floatval($filters['amount_min']);
        }
        
        if (!empty($filters['amount_max'])) {
            $where_conditions[] = 'h.amount <= %f';
            $query_params[] = floatval($filters['amount_max']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT SQL_CALC_FOUND_ROWS h.*, u.user_login, u.display_name 
                 FROM {$this->history_table} h 
                 LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID 
                 WHERE {$where_clause}
                 ORDER BY h.created_at DESC 
                 LIMIT %d, %d";
        
        $query_params[] = $offset;
        $query_params[] = $per_page;
        
        $transactions = $wpdb->get_results($wpdb->prepare($query, $query_params));
        $total = $wpdb->get_var('SELECT FOUND_ROWS()');
        
        return [
            'items' => $transactions,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ];
    }
    
    private function render_filters() {
        $filters = $this->get_filters();
        
        echo '<div class="wallet-filters">';
        echo '<h3>فیلترهای پیشرفته</h3>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="page" value="ai-wallet-report">';
        
        echo '<div class="filter-grid">';
        
        // فیلتر کاربر
        echo '<div class="wallet-filter-group">';
        echo '<label for="user_id">کاربر (ID):</label>';
        echo '<input type="number" name="user_id" id="user_id" value="' . (!empty($filters['user_id']) ? esc_attr($filters['user_id']) : '') . '" placeholder="ID کاربر">';
        echo '</div>';
        
        // فیلتر نوع تراکنش
        echo '<div class="wallet-filter-group">';
        echo '<label for="transaction_type">نوع تراکنش:</label>';
        echo '<select name="transaction_type" id="transaction_type">';
        echo '<option value="all"' . (empty($filters['transaction_type']) || $filters['transaction_type'] === 'all' ? ' selected' : '') . '>همه</option>';
        echo '<option value="credit"' . (!empty($filters['transaction_type']) && $filters['transaction_type'] === 'credit' ? ' selected' : '') . '>شارژ</option>';
        echo '<option value="debit"' . (!empty($filters['transaction_type']) && $filters['transaction_type'] === 'debit' ? ' selected' : '') . '>کسر</option>';
        echo '</select>';
        echo '</div>';
        
        // فیلتر تاریخ از
        echo '<div class="wallet-filter-group">';
        echo '<label for="date_from">از تاریخ:</label>';
        echo '<input type="date" name="date_from" id="date_from" value="' . (!empty($filters['date_from']) ? esc_attr($filters['date_from']) : '') . '">';
        echo '</div>';
        
        // فیلتر تاریخ تا
        echo '<div class="wallet-filter-group">';
        echo '<label for="date_to">تا تاریخ:</label>';
        echo '<input type="date" name="date_to" id="date_to" value="' . (!empty($filters['date_to']) ? esc_attr($filters['date_to']) : '') . '">';
        echo '</div>';
        
        // فیلتر حداقل مبلغ
        echo '<div class="wallet-filter-group">';
        echo '<label for="amount_min">حداقل مبلغ:</label>';
        echo '<input type="number" name="amount_min" id="amount_min" value="' . (!empty($filters['amount_min']) ? esc_attr($filters['amount_min']) : '') . '" placeholder="تومان">';
        echo '</div>';
        
        // فیلتر حداکثر مبلغ
        echo '<div class="wallet-filter-group">';
        echo '<label for="amount_max">حداکثر مبلغ:</label>';
        echo '<input type="number" name="amount_max" id="amount_max" value="' . (!empty($filters['amount_max']) ? esc_attr($filters['amount_max']) : '') . '" placeholder="تومان">';
        echo '</div>';
        
        echo '</div>';
        
        echo '<div class="filter-buttons">';
        echo '<input type="submit" name="apply_filters" value="اعمال فیلترها" class="button button-primary">';
        echo '<a href="' . add_query_arg(['page' => 'ai-wallet-report', 'reset_filters' => 1]) . '" class="button">بازنشانی فیلترها</a>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }
    
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        global $wpdb;
        
        // آمار کلی
        $stats = $this->get_wallet_stats();
        
        // 10 کاربر برتر با بیشترین موجودی
        $top_users = $wpdb->get_results(
            "SELECT w.*, u.user_login, u.display_name 
             FROM {$this->table_name} w 
             LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID 
             WHERE w.balance > 0 
             ORDER BY w.balance DESC 
             LIMIT 10"
        );
        
        // دریافت تراکنش‌ها با فیلتر
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $transactions_data = $this->get_filtered_transactions(20, $current_page);
        $transactions = $transactions_data['items'];
        
        echo '<div class="wrap">';
        echo '<h1>گزارش کیف پول هوش مصنوعی</h1>';
        
        // کارت‌های آماری
        echo '<div class="ai-wallet-stats">';
        echo '<div class="stat-card">';
        echo '<h3>مجموع موجودی کاربران</h3>';
        echo '<p>' . number_format($stats['total_balance']) . ' تومان</p>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<h3>کل شارژ شده</h3>';
        echo '<p>' . number_format($stats['total_charged']) . ' تومان</p>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<h3>کل مصرف شده</h3>';
        echo '<p>' . number_format($stats['total_spent']) . ' تومان</p>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<h3>کاربران دارای موجودی</h3>';
        echo '<p>' . number_format($stats['users_with_wallet']) . ' کاربر</p>';
        echo '</div>';
        echo '</div>';
        
        // دکمه‌های خروجی
        echo '<div class="export-buttons">';
        echo '<h3>دریافت خروجی</h3>';
        echo '<a href="' . add_query_arg(['export' => 'transactions']) . '" class="button button-primary">خروجی تراکنش‌ها (csv)</a>';
        echo '<a href="' . add_query_arg(['export' => 'users']) . '" class="button button-primary">خروجی کاربران (csv)</a>';
        echo '<a href="' . add_query_arg(['export' => 'summary']) . '" class="button button-primary">خروجی خلاصه (csv)</a>';
        echo '</div>';
        
        // نمودارها
        echo '<div class="ai-wallet-charts">';
        echo '<div class="chart-container">';
        echo '<h2>نسبت مصرف به شارژ</h2>';
        echo '<canvas id="usageChart" width="400" height="300"></canvas>';
        echo '</div>';
        
        echo '<div class="chart-container">';
        echo '<h2>توزیع موجودی کاربران</h2>';
        echo '<canvas id="distributionChart" width="400" height="300"></canvas>';
        echo '</div>';
        echo '</div>';
        
        // نمایش فیلترها
        $this->render_filters();
        
        // کاربران برتر
        echo '<div class="ai-wallet-top-users">';
        echo '<h2>کاربران برتر (بیشترین موجودی)</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
                <th>رتبه</th>
                <th>کاربر</th>
                <th>موجودی</th>
                <th>آخرین بروزرسانی</th>
              </tr></thead>';
        echo '<tbody>';
        
        if ($top_users) {
            $rank = 1;
            foreach ($top_users as $user) {
                $user_name = !empty($user->display_name) ? $user->display_name : $user->user_login;
                
                echo '<tr>';
                echo '<td>' . $rank . '</td>';
                echo '<td>' . esc_html($user_name) . ' (ID: ' . $user->user_id . ')</td>';
                echo '<td>' . number_format($user->balance) . ' تومان</td>';
                echo '<td>' . date_i18n('Y/m/d H:i', strtotime($user->updated_at)) . '</td>';
                echo '</tr>';
                
                $rank++;
            }
        } else {
            echo '<tr><td colspan="4">کاربری یافت نشد</td></tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        // جدول تراکنش‌ها
        echo '<div class="ai-wallet-recent">';
        echo '<h2>تراکنش‌ها (' . $transactions_data['total'] . ' مورد)</h2>';
        
        // صفحه‌بندی
        if ($transactions_data['pages'] > 1) {
            echo '<div class="tablenav">';
            echo '<div class="tablenav-pages">';
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo; قبلی',
                'next_text' => 'بعدی &raquo;',
                'total' => $transactions_data['pages'],
                'current' => $current_page
            ]);
            echo '</div>';
            echo '</div>';
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
                <th>کاربر</th>
                <th>مبلغ</th>
                <th>نوع</th>
                <th>موجودی جدید</th>
                <th>توضیحات</th>
                <th>تاریخ</th>
              </tr></thead>';
        echo '<tbody>';
        
        if ($transactions) {
            foreach ($transactions as $transaction) {
                $user_name = !empty($transaction->display_name) ? $transaction->display_name : $transaction->user_login;
                $amount_class = $transaction->type === 'credit' ? 'amount-credit' : 'amount-debit';
                
                echo '<tr>';
                echo '<td>' . esc_html($user_name) . ' (ID: ' . $transaction->user_id . ')</td>';
                echo '<td class="' . $amount_class . '">' . number_format($transaction->amount) . ' تومان</td>';
                echo '<td>' . ($transaction->type === 'credit' ? 'شارژ' : 'کسر') . '</td>';
                echo '<td>' . number_format($transaction->new_balance) . ' تومان</td>';
                echo '<td>' . esc_html($transaction->description) . '</td>';
                echo '<td>' . date_i18n('Y/m/d H:i', strtotime($transaction->created_at)) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">تراکنشی یافت نشد</td></tr>';
        }
        
        echo '</tbody></table>';
        
        // صفحه‌بندی پایین جدول
        if ($transactions_data['pages'] > 1) {
            echo '<div class="tablenav">';
            echo '<div class="tablenav-pages">';
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo; قبلی',
                'next_text' => 'بعدی &raquo;',
                'total' => $transactions_data['pages'],
                'current' => $current_page
            ]);
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // داده‌های نمودار برای اسکریپت
        echo '<script type="text/javascript">';
        echo 'var walletChartData = ' . json_encode([
            'charged' => $stats['total_charged'],
            'spent' => $stats['total_spent'],
            'remaining' => $stats['total_balance'],
            'distribution' => array_values($stats['balance_distribution'])
        ]) . ';';
        echo '</script>';
        
        echo '</div>';
    }
}

// مقداردهی اولیه گزارش ادمین
function init_ai_wallet_admin_report() {
    if (is_admin()) {
        AI_Assistant_Wallet_Admin_Report::get_instance();
    }
}
add_action('init', 'init_ai_wallet_admin_report');