// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/components/payment-popup.js
class PaymentPopup {
    constructor(options = {}) {
        this.options = {
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null,
            serviceType: options.serviceType || 'سرویس',
            customPrice: options.customPrice || null,
            ajaxAction: options.ajaxAction,
            ...options
        };
        this.popup = null;
        this.isOpen = false;
    }

    async show() {
        try {
            // بررسی متغیرهای ضروری
            if (typeof aiAssistantVars === 'undefined') {
                throw new Error('aiAssistantVars is not defined. Make sure it is loaded before PaymentPopup.');
            }

            // دریافت قیمت
            const price = this.options.customPrice || await this.getServicePrice();
            
            // ایجاد پاپ‌آپ
            this.createPopupElement(price);
            
            // دریافت موجودی
            await this.fetchUserBalance(price);
            
            this.isOpen = true;
        } catch (error) {
            console.error('Error showing payment popup:', error);
            this.showError(`خطا در نمایش اطلاعات پرداخت: ${error.message}`);
        }
    }

    async getServicePrice() {
        try {
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': this.options.ajaxAction,
                    'security': aiAssistantVars.nonce
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                return data.data.price;
            } else {
                throw new Error(data.data?.message || 'Failed to get service price');
            }
        } catch (error) {
            console.error('Error in getServicePrice:', error);
            throw error;
        }
    }

    createPopupElement(price) {
        const formattedPrice = new Intl.NumberFormat('fa-IR').format(price);
        
        this.popup = document.createElement('div');
        this.popup.className = 'payment-confirmation-popup active';
        this.popup.innerHTML = `
            <div class="payment-confirmation-content">
                <div class="payment-header">
                    <h3>تایید پرداخت</h3>
                </div>
                <div class="payment-details">
                    <div class="wallet-balance-popup">
                        <span>موجودی فعلی کیف پول شما:</span>
                        <span class="balance-amount-popup" id="current-balance">در حال بارگذاری...</span>
                    </div>
                    <div class="payment-cost">
                        <span>هزینه ${this.options.serviceType}:</span>
                        <span class="cost-amount">${formattedPrice} تومان</span>
                    </div>
                    <p class="payment-warning">در صورت تأیید، این مبلغ از حساب شما کسر خواهد شد.</p>
                </div>
                <div class="payment-buttons">
                    <button id="confirm-payment" class="confirm-btn" data-price="${price}" disabled>
                        <span class="btn-text">تأیید و پرداخت (${formattedPrice} تومان)</span>
                        <span class="btn-loading" style="display:none">در حال پردازش...</span>
                    </button>
                    <button id="cancel-payment" class="cancel-btn">انصراف</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.popup);
        this.setupEventListeners(price);
    }

    async fetchUserBalance(servicePrice) {
        try {
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'get_user_wallet_credit',
                    'security': aiAssistantVars.nonce
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.updateBalanceUI(data.data.credit, servicePrice);
            } else {
                throw new Error(data.data?.message || 'Failed to get user balance');
            }
        } catch (error) {
            console.error('Error fetching balance:', error);
            document.getElementById('current-balance').textContent = 'خطا در دریافت موجودی';
            document.getElementById('confirm-payment').disabled = false;
        }
    }

    updateBalanceUI(balance, servicePrice) {
        const balanceElement = document.getElementById('current-balance');
        const confirmBtn = document.getElementById('confirm-payment');
        
        const formattedBalance = new Intl.NumberFormat('fa-IR').format(balance);
        balanceElement.textContent = formattedBalance + ' تومان';
        
        if (balance < servicePrice) {
            const neededAmount = servicePrice - balance;
            balanceElement.style.color = '#e53935';
            
            confirmBtn.querySelector('.btn-text').textContent = 'افزایش موجودی کیف پول';
            confirmBtn.onclick = () => {
                const baseUrl = (typeof siteEnv !== 'undefined' && siteEnv.baseUrl) 
                    ? siteEnv.baseUrl 
                    : window.location.origin;
                window.location.href = `${baseUrl}/wallet-charge/?needed_amount=${neededAmount}`;
            };
        } else {
            confirmBtn.onclick = () => {
                if (this.options.onConfirm) {
                    this.options.onConfirm(servicePrice);
                }
                this.hide();
            };
        }
        
        confirmBtn.disabled = false;
    }

    setupEventListeners(servicePrice) {
        const confirmBtn = this.popup.querySelector('#confirm-payment');
        const cancelBtn = this.popup.querySelector('#cancel-payment');

        cancelBtn.addEventListener('click', () => {
            if (this.options.onCancel) {
                this.options.onCancel();
            }
            this.hide();
        });

        this.popup.addEventListener('click', (e) => {
            if (e.target === this.popup) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }

    hide() {
        if (this.popup) {
            document.body.removeChild(this.popup);
            this.popup = null;
            this.isOpen = false;
        }
    }

    showError(message) {
        // استفاده از loader موجود یا alert ساده
        if (window.AiDastyarLoader) {
            const loader = new AiDastyarLoader({
                message: message,
                type: 'error',
                duration: 3000
            });
            loader.show();
        } else {
            alert(message);
        }
    }
}

window.PaymentPopup = PaymentPopup;