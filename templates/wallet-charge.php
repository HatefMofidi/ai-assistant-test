<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/wallet-charge.php
 * Template Name: شارژ کیف پول
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$wallet = AI_Assistant_Payment_Handler::get_instance();
$current_credit = $wallet->get_user_credit($user_id);

$minimum_charge = ai_wallet_get_minimum_charge();
$formatted_minimum = ai_wallet_format_minimum_charge_fa(); // تغییر به تابع فارسی

?>

<div class="ai-wallet-charge-page">
    <div class="ai-wallet-header">
        <div class="ai-header-content">
            <a href="<?php echo esc_url(home_url('/ai-dashboard')); ?>" class="ai-back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                بازگشت به داشبورد
            </a>
            <div class="ai-header-title">
                <span class="ai-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                        <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                        <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                    </svg>
                </span>
                <h1>شارژ کیف پول</h1>
            </div>
        </div>
    </div>

    <div class="ai-wallet-container">
        <div class="ai-balance-card">
            <div class="ai-balance-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                    <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                </svg>
            </div>
            <div class="ai-balance-info">
                <span class="ai-balance-label">موجودی فعلی شما</span>
                <span class="ai-balance-amount"><?php echo format_number_fa($current_credit); ?> <span class="currency">تومان</span></span>
            </div>
        </div>

        <form method="POST" class="ai-charge-form">
            <div class="ai-form-section">
                <h3 class="ai-form-title">مبلغ مورد نظر برای شارژ را انتخاب کنید</h3>
                
                <div class="ai-amount-presets">
                    <div class="ai-preset-row">
                        <input type="radio" id="amount_50000" name="preset_amount" value="50000" class="ai-amount-radio" required>
                        <label for="amount_50000" class="ai-amount-preset">
                            <span class="ai-amount-value">۵۰,۰۰۰</span>
                            <span class="ai-amount-currency">تومان</span>
                        </label>
                        
                        <input type="radio" id="amount_100000" name="preset_amount" value="100000" class="ai-amount-radio">
                        <label for="amount_100000" class="ai-amount-preset">
                            <span class="ai-amount-value">۱۰۰,۰۰۰</span>
                            <span class="ai-amount-currency">تومان</span>
                        </label>
                        
                        <input type="radio" id="amount_200000" name="preset_amount" value="200000" class="ai-amount-radio">
                        <label for="amount_200000" class="ai-amount-preset">
                            <span class="ai-amount-value">۲۰۰,۰۰۰</span>
                            <span class="ai-amount-currency">تومان</span>
                        </label>
                    </div>
                    
                    <div class="ai-preset-row">
                        <input type="radio" id="amount_custom" name="preset_amount" value="custom" class="ai-amount-radio">
                        <label for="amount_custom" class="ai-amount-preset ai-custom-preset">
                            <span class="ai-amount-value">مبلغ دلخواه</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="ai-form-section ai-custom-amount-section" id="custom_amount_section">
                <label for="custom_amount" class="ai-form-label">مبلغ دلخواه (تومان)</label>
                <div class="input-container">
                    <input type="number" 
                           id="custom_amount" 
                           name="custom_amount" 
                           min="<?php echo $minimum_charge; ?>" 
                           step="1000" 
                           placeholder="حداقل <?php echo $formatted_minimum; ?> تومان"
                           class="ai-form-input" />
                    <span class="input-field-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </span>
                </div>
            </div>

            <input type="hidden" name="charge_amount" id="charge_amount" value="" />

            <div class="ai-important-notes">
                <h4>نکات مهم:</h4>
                <ul>
                    <li>حداقل مبلغ شارژ <?php echo $formatted_minimum; ?> تومان می‌باشد.</li>
                    <li>پس از پرداخت، مبلغ بلافاصله به کیف پول شما اضافه می‌شود.</li>
                    <li>در صورت بروز مشکل در پرداخت، با پشتیبانی تماس بگیرید.</li>
                </ul>
            </div>

            <div class="ai-form-actions">
                <button type="submit" name="wallet_charge_submit" class="ai-payment-button">
                    پرداخت و شارژ کیف پول
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* فونت Vazir */
@font-face {
    font-family: 'Vazir';
    src: url('<?php echo get_template_directory_uri(); ?>/assets/fonts/Vazir.woff2') format('woff2'),
         url('<?php echo get_template_directory_uri(); ?>/assets/fonts/Vazir.woff') format('woff');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

:root {
    --primary-light: #f0faf9;
    --primary-accent: #F4C017;
    --primary-medium: #00857a;
    --primary-dark: #00665c;
    --text-color: #1f2937;
    --text-light: #6b7280;
    --background: #f9fafb;
    --card-bg: #ffffff;
    --border-color: #e5e7eb;
    --success-bg: #f0fdf4;
    --success-border: #bbf7d0;
    --success-text: #166534;
}

.ai-amount-value, .ai-balance-amount, .currency {
    font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    direction: ltr; /* برای اعداد */
    unicode-bidi: embed;
}

/* استایل های جدید برای صفحه شارژ کیف پول */
.ai-wallet-charge-page {
    max-width: 500px; /* محدودیت عرض جدید */
    margin: 0 auto; /* مرکز کردن صفحه */
    padding: 1rem;
    font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    direction: rtl;
    background-color: var(--background);
    border-radius: 12px;
}

.ai-wallet-header {
    margin-bottom: 2rem;
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.ai-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    position: relative;
}

.ai-header-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-right: auto; /* برای فاصله از دکمه بازگشت */
}

.ai-header-icon {
    display: flex;
    color: var(--primary-medium);
    background-color: var(--primary-light);
    padding: 8px;
    border-radius: 8px;
}

.ai-wallet-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin: 0;
}

.ai-back-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background-color: var(--primary-light);
    color: var(--primary-dark);
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    order: -1; /* انتقال دکمه به ابتدای محتوا */
    margin-right: auto; /* انتقال به سمت چپ */
}

.ai-back-button:hover {
    background-color: var(--primary-medium);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 101, 92, 0.2);
}

.ai-wallet-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.ai-wallet-card {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    border: 1px solid var(--border-color);
}

/* اصلاح رنگ‌بندی کارت موجودی */
.ai-balance-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #2c3e50 0%, #4a6580 100%);
    border-radius: 10px;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
}

.ai-balance-icon {
    display: flex;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
}

.ai-balance-info {
    display: flex;
    flex-direction: column;
}

.ai-balance-label {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.ai-balance-amount {
    font-size: 1.75rem;
    font-weight: 700;
    color: #ffffff;
}

.currency {
    font-size: 1rem;
    font-weight: 500;
    color: #ffffff;
}

.ai-form-section {
    margin-bottom: 2rem;
}

.ai-form-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--primary-light);
}

.ai-amount-presets {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.ai-preset-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.ai-amount-radio {
    display: none;
}

.ai-amount-preset {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    min-width: 140px;
    padding: 1rem 1.25rem;
    background: var(--primary-light);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.ai-amount-preset:hover {
    border-color: var(--primary-medium);
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 101, 92, 0.15);
}

.ai-amount-radio:checked + .ai-amount-preset {
    border-color: var(--primary-medium);
    background-color: var(--primary-light);
    color: var(--primary-dark);
    box-shadow: 0 0 0 3px rgba(0, 133, 122, 0.3);
}

.ai-custom-preset {
    background: var(--primary-light);
    border: 2px dashed var(--primary-medium);
}

.ai-custom-preset:hover {
    background: rgba(244, 192, 23, 0.1);
    border: 2px dashed var(--primary-accent);
}

.ai-amount-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-dark);
}

.ai-amount-currency {
    font-size: 0.8rem;
    opacity: 0.9;
    color: var(--primary-medium);
}

.ai-custom-amount-section {
    display: none;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

#amount_custom:checked ~ .ai-custom-amount-section {
    display: block;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.ai-form-label {
    display: block;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.75rem;
    font-size: 1rem;
}

.input-container {
    position: relative;
    margin-bottom: 1rem;
}

.ai-form-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: var(--primary-light);
    color: var(--text-color);
}

.ai-form-input:focus {
    outline: none;
    border-color: var(--primary-medium);
    box-shadow: 0 0 0 3px rgba(0, 133, 122, 0.3);
    background: linear-gradient(135deg, #e6f7f5 0%, #ffffff 100%);
}

.ai-form-input:not(:placeholder-shown) {
    background: linear-gradient(135deg, #e6f7f5 0%, #ffffff 100%);
    border-color: var(--primary-medium);
}

.input-field-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-medium);
}

/* اصلاح نکات مهم */
.ai-important-notes {
    background-color: rgba(244, 192, 23, 0.1);
    border: 1px solid var(--primary-accent);
    border-radius: 10px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.ai-important-notes h4 {
    margin: 0 0 1rem 0;
    color: var(--primary-dark);
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ai-important-notes h4:before {
    content: "⚠️";
}

.ai-important-notes ul {
    margin: 0;
    padding-right: 1.5rem;
    color: var(--primary-dark);
    text-align: justify; /* افزودن justify */
    line-height: 1.8; /* افزایش فاصله خطوط برای خوانایی بهتر */
}

.ai-important-notes li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
    position: relative;
}

.ai-important-notes li:before {
    content: "•";
    color: var(--primary-accent);
    font-weight: bold;
    display: inline-block;
    width: 1em;
    margin-right: -1em;
    margin-left: 0.5em;
}

.ai-form-actions {
    margin-top: 2rem;
}

.ai-payment-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1.25rem 2rem;
    background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    /*font-weight: 700;*/
    font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 101, 92, 0.3);
}

.ai-payment-button:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00544d 100%);
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 101, 92, 0.4);
}

.ai-payment-button:active {
    transform: translateY(-1px);
}

/* رسپانسیو برای موبایل */
@media (max-width: 768px) {
    .ai-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .ai-back-button {
        order: 0; /* بازگرداندن ترتیب طبیعی برای موبایل */
        margin-right: 0;
        margin-bottom: 1rem;
        align-self: flex-start;
    }
    
    .ai-header-title {
        margin-right: 0;
        width: 100%;
        justify-content: center;
    }
    
    .ai-preset-row {
        flex-direction: column;
    }
    
    .ai-amount-preset {
        min-width: auto;
    }
    
    .ai-wallet-card {
        padding: 1.5rem;
    }
    
    .ai-balance-card {
        flex-direction: column;
        text-align: center;
        padding: 1.25rem;
    }
    
    .ai-payment-button {
        padding: 1rem 1.5rem;
        font-size: 1rem;
    }
    
    /* اصلاح نکات مهم برای موبایل */
    .ai-important-notes ul {
        padding-right: 1rem;
    }
    
    .ai-important-notes li:before {
        margin-left: 0.3em;
    }
}

@media (max-width: 500px) {
    .ai-wallet-charge-page {
        padding: 0 1.2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // مدیریت انتخاب مبلغ از پیش تعیین شده
    const presetAmounts = document.querySelectorAll('input[name="preset_amount"]');
    const customAmountSection = document.getElementById('custom_amount_section');
    const customAmountInput = document.getElementById('custom_amount');
    const chargeAmountInput = document.getElementById('charge_amount');
    
    presetAmounts.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                customAmountSection.style.display = 'block';
                customAmountInput.focus();
            } else {
                customAmountSection.style.display = 'none';
                customAmountInput.value = '';
                chargeAmountInput.value = this.value;
            }
        });
    });
    
    // مدیریت مبلغ دلخواه
    customAmountInput.addEventListener('input', function() {
        if (this.value) {
            document.getElementById('amount_custom').checked = true;
            chargeAmountInput.value = this.value;
        }
    });
    
    // تابع برای تبدیل اعداد انگلیسی به فارسی در JavaScript
    function toPersianNumber(number) {
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return number.toString().replace(/\d/g, function(digit) {
            return persianDigits[digit];
        });
    }

    // اعتبارسنجی فرم قبل از ارسال
    const form = document.querySelector('.ai-charge-form');
    form.addEventListener('submit', function(e) {
        const minAmount = <?php echo $minimum_charge; ?>;
        if (!chargeAmountInput.value || parseInt(chargeAmountInput.value) < minAmount) {
            e.preventDefault();
            alert('لطفاً مبلغی معتبر (حداقل ' + toPersianNumber(minAmount) + ' تومان) وارد کنید.');
            return false;
        }
    });
});
</script>

<?php
// پردازش فرم شارژ
if (isset($_POST['wallet_charge_submit']) && !empty($_POST['charge_amount'])) {
    $amount = (int) $_POST['charge_amount'];
    $minimum_charge = ai_wallet_get_minimum_charge();

    if ($amount >= $minimum_charge) {
        $user_id = get_current_user_id();
        $wallet = AI_Assistant_Payment_Handler::get_instance();

        // ساخت شناسه منحصر به‌فرد
        $unique_id = 'wallet_' . $user_id . '_' . time();

        // اطمینان از فعال بودن session ووکامرس
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        // ذخیره اطلاعات شارژ در session
        WC()->session->set('ai_wallet_charge_data', [
            'unique_id' => $unique_id,
            'amount' => $amount,
            'user_id' => $user_id,
            'timestamp' => time()
        ]);

        // دریافت محصول ثابت کیف پول
        $product_id = $wallet->get_wallet_product_id();

        if ($product_id) {
            // پاک کردن سبد خرید قبلی
            WC()->cart->empty_cart();
            
            // افزودن محصول به سبد خرید با داده‌های اضافی
            $cart_item_data = [
                'ai_wallet_charge' => [
                    'unique_id' => $unique_id,
                    'amount' => $amount,
                    'user_id' => $user_id,
                    'timestamp' => time()
                ]
            ];
            
            $added = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

            if ($added) {
                // ذخیره فوری سبد خرید
                WC()->cart->set_session();
                WC()->cart->calculate_totals();
                
                wp_redirect(wc_get_checkout_url());
                exit;
            } else {
                echo '<div class="ai-alert ai-alert-error">خطا در افزودن به سبد خرید. لطفا مجددا تلاش کنید.</div>';
            }
        } else {
            echo '<div class="ai-alert ai-alert-error">خطا در ایجاد محصول پرداخت. لطفا مجددا تلاش کنید.</div>';
        }
    } else {
        echo '<div class="ai-alert ai-alert-error">مبلغ وارد شده معتبر نیست. حداقل مبلغ شارژ ' . ai_wallet_format_minimum_charge_fa() . ' تومان می‌باشد.</div>';
    }
}
get_footer();