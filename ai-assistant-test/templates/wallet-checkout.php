<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/wallet-checkout.php
 * Template Name: صفحه پرداخت کیف پول
 */
if (!defined('ABSPATH')) {
    exit;
}

// بررسی لاگین بودن کاربر
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// شروع session
if (!session_id() && !headers_sent()) {
    session_start();
}

// دریافت مبلغ از session یا POST
if (isset($_SESSION['wallet_charge_amount'])) {
    $amount = $_SESSION['wallet_charge_amount'];
} elseif (isset($_POST['charge_amount'])) {
    $amount = intval($_POST['charge_amount']);
    $_SESSION['wallet_charge_amount'] = $amount;
} else {
    // اگر مبلغی وجود ندارد، بازگشت به صفحه شارژ
    wp_redirect(home_url('/wallet-charge'));
    exit;
}

// پردازش درخواست پرداخت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wallet_checkout_submit'])) {
    // بررسی صحت مبلغ
    $minimum_charge = ai_wallet_get_minimum_charge();
    $maximum_charge = ai_wallet_get_maximum_charge();
    
    if ($amount < $minimum_charge || $amount > $maximum_charge) {
        $error_message = 'مبلغ پرداخت معتبر نیست.';
    } else {
        // انتقال به درگاه پرداخت
        $payment_handler = AI_Assistant_Wallet_Checkout_Handler::get_instance();
        $payment_result = $payment_handler->connect_to_zarinpal($amount);
        
        if ($payment_result['status']) {
            // ذخیره اطلاعات پرداخت در session
            $_SESSION['wallet_payment_amount'] = $amount;
            $_SESSION['wallet_payment_authority'] = $payment_result['authority'];
            
            wp_redirect($payment_result['url']);
            exit;
        } else {
            $error_message = 'خطا در اتصال به درگاه پرداخت: ' . $payment_result['message'];
        }
    }
}

get_header();
?>

<div class="wallet-checkout-container">
    <h1>پرداخت کیف پول</h1>
    
    <?php if (ZARINPAL_SANDBOX): ?>
    <div class="sandbox-notice">
        <strong>حالت آزمایشی (Sandbox)</strong> - این پرداخت تستی است و مبلغی کسر نمی‌شود.
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)) : ?>
    <div class="checkout-error">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <div class="checkout-summary">
        <h3>خلاصه سفارش</h3>
        <div class="summary-item">
            <span>مبلغ پرداخت:</span>
            <span><?php echo number_format($amount); ?> تومان</span>
        </div>
        <?php if (ZARINPAL_SANDBOX): ?>
        <div class="summary-item">
            <span>وضعیت:</span>
            <span class="sandbox-status">پرداخت آزمایشی</span>
        </div>
        <?php endif; ?>
    </div>
    
    <form method="POST" class="wallet-checkout-form">
        <input type="hidden" name="charge_amount" value="<?php echo $amount; ?>">
        
        <div class="payment-actions">
            <button type="submit" name="wallet_checkout_submit" class="payment-button">
                <?php echo ZARINPAL_SANDBOX ? 'پرداخت آزمایشی (Sandbox)' : 'پرداخت از طریق زرین پال'; ?>
            </button>
            <a href="<?php echo home_url('/wallet-charge'); ?>" class="back-button">بازگشت به صفحه شارژ</a>
        </div>
    </form>
</div>

<style>
.wallet-checkout-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    font-family: 'Vazir', sans-serif;
}

.sandbox-notice {
    background: #fff3cd;
    color: #856404;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #ffeaa7;
    text-align: center;
}

.checkout-error {
    background: #ffebee;
    color: #c62828;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #ffcdd2;
}

.checkout-summary {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.checkout-summary h3 {
    margin-top: 0;
    color: #2c3e50;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-item span:last-child {
    font-weight: bold;
}

.sandbox-status {
    color: #e67e22;
    font-weight: bold;
}

.payment-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-button {
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #00857a 0%, #00665c 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-button:hover {
    background: linear-gradient(135deg, #00665c 0%, #00544d 100%);
    transform: translateY(-2px);
}

.back-button {
    padding: 1rem 2rem;
    background: #6c757d;
    color: white;
    text-align: center;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.back-button:hover {
    background: #5a6268;
    color: white;
}
</style>

<?php
get_footer();