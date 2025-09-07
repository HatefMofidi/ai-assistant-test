<?php
/* /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/inc/class-payment-handler.php */
class AI_Assistant_Payment_Handler {
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
        $this->maybe_create_tables();
        
        // اضافه کردن هوک‌های لازم
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'get_cart_item_from_session'], 10, 3);
        add_action('woocommerce_before_calculate_totals', [$this, 'before_calculate_totals'], 20, 1);
        
        // هوک‌های جدید برای محاسبه صحیح مجموع
        add_filter('woocommerce_cart_item_subtotal', [$this, 'cart_item_subtotal'], 10, 3);
        add_filter('woocommerce_cart_subtotal', [$this, 'cart_subtotal'], 10, 3);
        add_filter('woocommerce_cart_total', [$this, 'cart_total'], 10, 1);
        add_action('woocommerce_cart_totals_before_order_total', [$this, 'before_order_total']);
        
        add_action('woocommerce_checkout_order_processed', [$this, 'checkout_order_processed'], 10, 3);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_order_item_meta'], 10, 4);
        
        // هوک‌های مختلف برای وضعیت‌های سفارش
        add_action('woocommerce_order_status_processing', [$this, 'order_status_changed'], 10, 2);
        add_action('woocommerce_order_status_completed', [$this, 'order_status_changed'], 10, 2);
        add_action('woocommerce_payment_complete', [$this, 'payment_complete'], 10, 1);        
    }
    
    // هوک برای تغییر وضعیت سفارش
    public function order_status_changed($order_id, $order) {
        $this->process_wallet_charge($order_id, $order);
    }
    
    // هوک برای تکمیل پرداخت
    public function payment_complete($order_id) {
        $order = wc_get_order($order_id);
        $this->process_wallet_charge($order_id, $order);
    }
    
    // تابع اصلی برای پردازش شارژ کیف پول
    public function process_wallet_charge($order_id, $order) {
        if (!$order) {
            return;
        }
        
        AI_Assistant_Logger::get_instance()->log('Processing wallet charge for order', [
            'order_id' => $order_id,
            'status' => $order->get_status()
        ]);
        
        foreach ($order->get_items() as $item_id => $item) {
            $charge_data = $item->get_meta('ai_wallet_charge');
            
            if ($charge_data && is_array($charge_data)) {
                // بررسی که قبلاً شارژ نشده باشد
                $already_charged = $item->get_meta('ai_wallet_charged');
                
                if (!$already_charged) {
                    AI_Assistant_Logger::get_instance()->log('Charging wallet from order status change', [
                        'order_id' => $order_id,
                        'user_id' => $charge_data['user_id'],
                        'amount' => $charge_data['amount']
                    ]);
                    
                    // شارژ کیف پول
                    $success = $this->add_credit(
                        $charge_data['user_id'],
                        $charge_data['amount'],
                        'شارژ کیف پول - سفارش #' . $order_id,
                        'order_' . $order_id
                    );
                    
                    if ($success) {
                        // علامت گذاری که شارژ شده است
                        $item->update_meta_data('ai_wallet_charged', 'yes');
                        $item->save_meta_data();
                        
                        $order->add_order_note(
                            'کیف پول کاربر با مبلغ ' . number_format($charge_data['amount']) . 
                            ' تومان شارژ شد.'
                        );
                        
                        AI_Assistant_Logger::get_instance()->log('Wallet charged successfully from order status', [
                            'order_id' => $order_id,
                            'user_id' => $charge_data['user_id'],
                            'amount' => $charge_data['amount']
                        ]);
                    }
                } else {
                    AI_Assistant_Logger::get_instance()->log('Wallet already charged for this order item', [
                        'order_id' => $order_id,
                        'item_id' => $item_id
                    ]);
                }
                
                break;
            }
        }
    }
    
    // هوک برای محاسبه صحیح جمع جزء
    public function cart_item_subtotal($subtotal, $cart_item, $cart_item_key) {
        if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
            return wc_price($cart_item['ai_wallet_charge']['amount'] * $cart_item['quantity']);
        }
        return $subtotal;
    }
    
    // هوک برای محاسبه صحیح جمع کل
    public function cart_subtotal($subtotal, $compound, $cart) {
        $wallet_amount = 0;
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
                $wallet_amount += $cart_item['ai_wallet_charge']['amount'] * $cart_item['quantity'];
            }
        }
        
        if ($wallet_amount > 0) {
            return wc_price($wallet_amount);
        }
        
        return $subtotal;
    }
    
    // هوک برای محاسبه صحیح مجموع نهایی
    public function cart_total($total) {
        $cart = WC()->cart;
        $wallet_amount = 0;
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
                $wallet_amount += $cart_item['ai_wallet_charge']['amount'] * $cart_item['quantity'];
            }
        }
        
        if ($wallet_amount > 0) {
            // محاسبه مالیات و سایر هزینه‌ها اگر وجود دارند
            $taxes = $cart->get_taxes_total();
            $shipping = $cart->get_shipping_total();
            $fees = $cart->get_fee_total();
            
            $final_total = $wallet_amount + $taxes + $shipping + $fees;
            return wc_price($final_total);
        }
        
        return $total;
    }
    
    // هوک برای نمایش قبل از مجموع
    public function before_order_total() {
        $cart = WC()->cart;
        $has_wallet_item = false;
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['ai_wallet_charge'])) {
                $has_wallet_item = true;
                break;
            }
        }
        
        if ($has_wallet_item) {
            echo '<tr class="cart-wallet-notice">';
            echo '<th>نوع پرداخت</th>';
            echo '<td data-title="نوع پرداخت">';
            echo '<strong>شارژ کیف پول هوش مصنوعی</strong>';
            echo '</td>';
            echo '</tr>';
        }
    }    
    
    // هوک مهم: بازیابی داده‌های سبد خرید از session
    public function get_cart_item_from_session($cart_item, $values, $key) {
        $wallet_product_id = $this->get_wallet_product_id();
        
        if (isset($values['ai_wallet_charge']) && $cart_item['product_id'] == $wallet_product_id) {
            $cart_item['ai_wallet_charge'] = $values['ai_wallet_charge'];
        }
        
        return $cart_item;
    }
    
    // متد جدید: تنظیم داده‌های session هنگام افزودن به سبد خرید
    public function maybe_set_session_data($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $wallet_product_id = $this->get_wallet_product_id();
        
        if ($product_id == $wallet_product_id && empty($cart_item_data['ai_wallet_charge'])) {
            $charge_data = WC()->session->get('ai_wallet_charge_data');
            if ($charge_data) {
                WC()->cart->cart_contents[$cart_item_key]['ai_wallet_charge'] = $charge_data;
            }
        }
    }
    
    // متد بهبود یافته برای دریافت محصول کیف پول
    public function get_wallet_product_id() {
        $product_id = get_option('ai_assistant_wallet_product_id');
        
        // اگر محصول وجود ندارد، ایجادش کن
        if (!$product_id || !wc_get_product($product_id)) {
            $product = new WC_Product_Simple();
            $product->set_name('شارژ کیف پول هوش مصنوعی');
            $product->set_regular_price(0); // قیمت صفر
            $product->set_virtual(true);
            $product->set_sold_individually(true);
            $product->set_catalog_visibility('hidden');
            $product->set_featured(false);
            $product->set_manage_stock(false);
            $product_id = $product->save();
            
            update_option('ai_assistant_wallet_product_id', $product_id);
            
            // همچنین محصول را از دسته‌بندی‌ها و جستجو مخفی کن
            wp_set_object_terms($product_id, 'exclude-from-catalog', 'product_visibility');
            wp_set_object_terms($product_id, 'exclude-from-search', 'product_visibility');
            
            AI_Assistant_Logger::get_instance()->log('محصول ثابت کیف پول ایجاد شد', [
                'product_id' => $product_id
            ]);
        }
        
        return $product_id;
    }
    
    // هوک برای اضافه کردن متادیتا به آیتم سفارش
    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['ai_wallet_charge'])) {
            $item->add_meta_data('ai_wallet_charge', $values['ai_wallet_charge']);
        }
    }    
    
    // هوک برای اضافه کردن داده به آیتم سبد خرید
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $wallet_product_id = $this->get_wallet_product_id();
        
        if ($product_id == $wallet_product_id) {
            $charge_data = WC()->session->get('ai_wallet_charge_data');
            if ($charge_data) {
                $cart_item_data['ai_wallet_charge'] = $charge_data;
                // حذف session بعد از استفاده
                WC()->session->__unset('ai_wallet_charge_data');
            }
        }
        
        return $cart_item_data;
    }
    
    // هوک برای تغییر قیمت در سبد خرید (بهبود یافته)
    public function before_calculate_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (did_action('woocommerce_before_calculate_totals') >= 2) return;
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['ai_wallet_charge']) && !empty($cart_item['ai_wallet_charge']['amount'])) {
                // تنظیم قیمت به صورت مستقیم
                $amount = floatval($cart_item['ai_wallet_charge']['amount']);
                $cart_item['data']->set_price($amount);
                $cart_item['data']->set_regular_price($amount);
                $cart_item['data']->set_sale_price($amount);
                
                // غیرفعال کردن مالیات برای محصولات کیف پول
                $cart_item['data']->set_tax_status('none');
                $cart_item['data']->set_tax_class('zero-rate');
            }
        }
    }
    
    // هوک برای پردازش پس از ثبت سفارش
    // هوک برای پردازش پس از ثبت سفارش (تصحیح شده)
    public function checkout_order_processed($order_id, $posted_data, $order) {
        // لاگ برای دیباگ
        AI_Assistant_Logger::get_instance()->log('Order processed started', [
            'order_id' => $order_id,
            'order_status' => $order->get_status()
        ]);
        
        // بررسی آیتم‌های سفارش
        foreach ($order->get_items() as $item_id => $item) {
            $charge_data = $item->get_meta('ai_wallet_charge');
            
            if ($charge_data && is_array($charge_data)) {
                AI_Assistant_Logger::get_instance()->log('Wallet charge data found in order item', [
                    'order_id' => $order_id,
                    'item_id' => $item_id,
                    'charge_data' => $charge_data
                ]);
                
                // فقط اگر پرداخت completed شده باشد
                if ($order->get_status() === 'processing' || $order->get_status() === 'completed') {
                    // شارژ کیف پول کاربر
                    $success = $this->add_credit(
                        $charge_data['user_id'],
                        $charge_data['amount'],
                        'شارژ کیف پول از طریق درگاه پرداخت - شماره سفارش: ' . $order_id,
                        'order_' . $order_id
                    );
                    
                    if ($success) {
                        AI_Assistant_Logger::get_instance()->log('Wallet charged successfully', [
                            'order_id' => $order_id,
                            'user_id' => $charge_data['user_id'],
                            'amount' => $charge_data['amount']
                        ]);
                        
                        // اضافه کردن note به سفارش
                        $order->add_order_note(
                            'کیف پول کاربر با مبلغ ' . number_format($charge_data['amount']) . 
                            ' تومان شارژ شد. شناسه: ' . $charge_data['unique_id']
                        );
                    } else {
                        AI_Assistant_Logger::get_instance()->log_error('Wallet charge failed', [
                            'order_id' => $order_id,
                            'user_id' => $charge_data['user_id'],
                            'amount' => $charge_data['amount']
                        ]);
                    }
                } else {
                    AI_Assistant_Logger::get_instance()->log('Order status not completed, skipping wallet charge', [
                        'order_id' => $order_id,
                        'status' => $order->get_status()
                    ]);
                }
                
                break;
            }
        }
    }
    
    private function maybe_create_tables() {
        global $wpdb;
        
        // ایجاد جدول موجودی کیف پول
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                user_id bigint(20) UNSIGNED NOT NULL,
                balance decimal(15,2) NOT NULL DEFAULT 0,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id),
                INDEX (updated_at)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // انتقال داده‌های موجود از user_meta به جدول جدید
            $users = get_users([
                'meta_key' => 'ai_assistant_credit',
                'meta_compare' => 'EXISTS'
            ]);
            
            foreach ($users as $user) {
                $credit = get_user_meta($user->ID, 'ai_assistant_credit', true);
                $wpdb->replace($this->table_name, [
                    'user_id' => $user->ID,
                    'balance' => $credit
                ]);
            }
        }
        
        // ایجاد جدول تاریخچه تراکنش‌ها
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->history_table}'") != $this->history_table) {
            $charset_collate = $wpdb->get_charset_collate();
            
            // استفاده از ساختار جایگزین برای enum
            $sql = "CREATE TABLE {$this->history_table} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                amount decimal(15,2) NOT NULL,
                new_balance decimal(15,2) NOT NULL,
                type varchar(20) NOT NULL, -- تغییر از enum به varchar
                description varchar(255) NOT NULL,
                reference_id varchar(100) DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX (user_id),
                INDEX (type),
                INDEX (created_at),
                INDEX (reference_id)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // اضافه کردن محدودیت مقدار برای فیلد type
            $wpdb->query(
                "ALTER TABLE {$this->history_table} 
                 ADD CONSTRAINT chk_type CHECK (type IN ('credit', 'debit'))"
            );
        }
    }
    
    public function get_user_credit($user_id) {
        global $wpdb;
        
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        return $balance !== null ? (float) $balance : 0;
    }
    
    public function has_enough_credit($user_id, $amount) {
        return $this->get_user_credit($user_id) >= $amount;
    }
    
    public function deduct_credit($user_id, $amount, $description, $reference_id = null) {
        global $wpdb;
        
        $current = $this->get_user_credit($user_id);
        $new_balance = $current - $amount;
        
        if ($new_balance < 0) {
            return false;
        }
        
        $wpdb->replace($this->table_name, [
            'user_id' => $user_id,
            'balance' => $new_balance
        ], ['%d', '%f']);
        
        $this->save_wallet_history(
            $user_id,
            $amount,
            $new_balance,
            'debit',
            $description,
            $reference_id
        );
        
        return true;
    }
    
    public function add_credit($user_id, $amount, $description = 'شارژ کیف پول', $reference_id = null) {
        global $wpdb;
        
        $current = $this->get_user_credit($user_id);
        $new_balance = $current + $amount;
        
        $wpdb->replace($this->table_name, [
            'user_id' => $user_id,
            'balance' => $new_balance
        ], ['%d', '%f']);
        
        $this->save_wallet_history(
            $user_id,
            $amount,
            $new_balance,
            'credit',
            $description,
            $reference_id
        );
        
        AI_Assistant_Logger::get_instance()->log('کیف پول با موفقیت شارژ شد', [
            'user_id' => $user_id,
            'amount' => $amount,
            'new_balance' => $new_balance
        ]);
        
        return true;
    }
    
    private function save_wallet_history($user_id, $amount, $new_balance, $type, $description, $reference_id = null) {
        global $wpdb;
        $wpdb->query("SET time_zone = '+03:30';");
        
        $wpdb->insert($this->history_table, [
            'user_id' => $user_id,
            'amount' => $amount,
            'new_balance' => $new_balance,
            'type' => $type,
            'description' => $description,
            'reference_id' => $reference_id
        ], [
            '%d', '%f', '%f', '%s', '%s', '%s'
        ]);
    }
    
    public function get_transaction_history($user_id, $per_page = 10, $page = 1) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->history_table}
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT %d, %d",
            $user_id, $offset, $per_page
        ));
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->history_table} WHERE user_id = %d",
            $user_id
        ));
        
        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ];
    }
    
    public function delete_transaction($transaction_id, $user_id) {
        global $wpdb;
        
        // ابتدا تراکنش را پیدا می‌کنیم
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->history_table} WHERE id = %d AND user_id = %d",
            $transaction_id, $user_id
        ));
        
        if (!$transaction) {
            return false;
        }
        
        // حذف تراکنش
        $deleted = $wpdb->delete($this->history_table, [
            'id' => $transaction_id,
            'user_id' => $user_id
        ], ['%d', '%d']);
        
        return (bool) $deleted;
    }
    
    
    // public function create_payment_link($service_id, $price) {
        
    //     if (!function_exists('WC')) {
    //         AI_Assistant_Logger::get_instance()->log_error('عملیات ایجاد لینک پرداخت: ووکامرس فعال نیست');
    //         return false;
    //     }
        
    //     $product_id = $this->get_or_create_product($service_id, $price);
        
    //     if (!$product_id) {
    //         AI_Assistant_Logger::get_instance()->log_error('ایجاد محصول برای سرویس ناموفق بود', [
    //             'service_id' => $service_id,
    //             'price' => $price
    //         ]);
    //         return false;
    //     }   
        
    //     $link = add_query_arg([
    //         'add-to-cart' => $product_id,
    //         'quantity' => 1
    //     ], wc_get_page_permalink('cart'));
    
    //     AI_Assistant_Logger::get_instance()->log('لینک پرداخت ایجاد شد', [
    //         'service_id' => $service_id,
    //         'price' => $price,
    //         'product_id' => $product_id,
    //         'link' => $link
    //     ]);
    
    //     return $link;
    // }
    
    // private function get_or_create_product($service_id, $price) {
    //   // $service = AI_Assistant_Service_Manager::get_instance()->get_service($service_id);
        
    //     $service = (class_exists('AI_Assistant_Service_Manager')) 
    //         ? AI_Assistant_Service_Manager::get_instance()->get_service($service_id) 
    //         : ['name' => $service_id];
                
    //     if (!$service) return false;
        
    //     $product_id = get_option('ai_assistant_product_' . $service_id);
        
    //     if ($product_id && wc_get_product($product_id)) {
    //         return $product_id;
    //     }
        
    //     $product = new WC_Product_Simple();
    // //  $product->set_name($service['name'] . ' (AI Assistant)');
        
    //     $product_name = is_array($service) ? $service['name'] : $service;
    //     $product->set_name($product_name . ' (AI Assistant)');
        
    //     $product->set_regular_price($price);
    //     $product->set_virtual(true);
    //     $product->set_sold_individually(true);
    //     $product_id = $product->save();
        
    //     if ($product_id) {
    //         update_option('ai_assistant_product_' . $service_id, $product_id);
    //         return $product_id;
    //     }
        
    //     return false;
    // }
}

// هوک برای پرداخت آنلاین
add_action('woocommerce_payment_complete', function($order_id) {
    $order = wc_get_order($order_id);
    $wallet_handler = AI_Assistant_Payment_Handler::get_instance();
    $wallet_handler->process_wallet_charge($order_id, $order);
}, 10, 1);