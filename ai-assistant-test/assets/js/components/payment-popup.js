// payment-popup-simple.js
class PaymentPopup {
    constructor(options = {}) {
        this.options = {
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null,
            serviceType: options.serviceType || 'سرویس',
            customPrice: options.customPrice || null,
            ajaxAction: options.ajaxAction,
            serviceId: options.serviceId || '',
            ...options
        };
        
        if (!this.options.serviceId) {
            console.error('PaymentPopup: serviceId is required');
        }
        
        this.popup = null;
        this.isOpen = false;
        this.originalPrice = 0;
        this.finalPrice = 0;
        this.discountApplied = false;
        
        // مقداردهی اولیه متغیرهای global
        window.discountApplied = false;
        window.appliedDiscountCode = '';
        window.discountAmount = 0;
        window.originalPrice = 0;
        window.finalPrice = 0;
    }
    
    async show() {
        if (this.isOpen) return;
        
        try {
            // Get service price
            this.originalPrice = this.options.customPrice || await this.getServicePrice();
            this.finalPrice = this.originalPrice;
            
            // به روزرسانی متغیرهای global
            window.originalPrice = this.originalPrice;
            window.finalPrice = this.finalPrice;
            
            // Create popup
            this.createPopupElement(this.originalPrice);
            
            // Fetch user balance
            await this.fetchUserBalance(this.finalPrice);
            
            this.isOpen = true;
        } catch (error) {
            console.error('Error showing payment popup:', error);
            alert('خطا در نمایش پرداخت: ' + error.message);
        }
    }

    // در تابع getServicePrice در payment-popup.js
    async getServicePrice() {
        try {
            console.log('Getting service price for:', this.options.serviceId);
            
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
    
            const data = await response.json();
            console.log('Service price response:', data);
    
            if (data.success) {
                return data.data.price;
            } else {
                throw new Error(data.data?.message || 'خطا در دریافت قیمت');
            }
        } catch (error) {
            console.error('Error getting service price:', error);
            throw error;
        }
    }

    createPopupElement(price) {
        this.popup = document.createElement('div');
        this.popup.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        `;
        
        const formattedPrice = new Intl.NumberFormat('fa-IR').format(price);
        
        this.popup.innerHTML = `
            <div style="
                background: white;
                padding: 20px;
                border-radius: 8px;
                width: 90%;
                max-width: 400px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            ">
                <div style="margin-bottom: 15px;">
                    <h3 style="margin: 0 0 15px 0; color: #333;">تایید پرداخت</h3>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>موجودی شما:</span>
                        <span id="current-balance" style="font-weight: bold;">در حال بارگذاری...</span>
                    </div>
                    
                    <!-- بخش کد تخفیف -->
                    <div style="margin-bottom: 15px; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px;">
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <input type="text" 
                                id="discount-code-input" 
                                placeholder="کد تخفیف (اختیاری)"
                                style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <button id="apply-discount-btn" style="
                                padding: 8px 12px;
                                background: #00857a;
                                color: white;
                                border: none;
                                border-radius: 4px;
                                cursor: pointer;
                                font-size: 12px;
                                white-space: nowrap;
                            ">اعمال تخفیف</button>
                        </div>
                        <div id="discount-message" style="font-size: 12px; min-height: 16px;"></div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>مبلغ قابل پرداخت:</span>
                        <span id="final-price" style="font-weight: bold; color: #00857a;">${formattedPrice} تومان</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; color: #666;">
                        <span>قیمت اصلی:</span>
                        <span id="original-price-display">${formattedPrice} تومان</span>
                    </div>                    
                    
                    <div id="discount-display" style="display: none; background: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span>مبلغ تخفیف:</span>
                            <span id="discount-amount" style="color: #28a745;"></span>
                        </div>
                    </div>
                    
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        در صورت تأیید، این مبلغ از حساب شما کسر خواهد شد.
                    </p>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button id="confirm-payment" style="
                        flex: 1;
                        padding: 12px;
                        background: #00857a;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                    " disabled>تأیید پرداخت</button>
                    
                    <button id="cancel-payment" style="
                        flex: 1;
                        padding: 12px;
                        background: #f0f0f0;
                        color: #333;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                    ">انصراف</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.popup);
        this.setupEventListeners(price);
    }

    setupEventListeners(servicePrice) {
        const cancelBtn = this.popup.querySelector('#cancel-payment');
        const applyDiscountBtn = this.popup.querySelector('#apply-discount-btn');
        const discountInput = this.popup.querySelector('#discount-code-input');

        // رویداد اعمال تخفیف
        applyDiscountBtn.addEventListener('click', () => {
            this.applyDiscount();
        });

        // اعمال تخفیف با کلید Enter
        discountInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.applyDiscount();
            }
        });

        cancelBtn.addEventListener('click', () => {
            if (this.options.onCancel) {
                this.options.onCancel();
            }
            this.hide();
        });

        this.popup.addEventListener('click', (e) => {
            if (e.target === this.popup) {
                this.hide();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.hide();
            }
        });
    }

    async applyDiscount() {
        const discountCode = document.getElementById('discount-code-input').value.trim();
        const messageElement = document.getElementById('discount-message');
        
        if (!discountCode) {
            this.showDiscountMessage('لطفا کد تخفیف را وارد کنید', 'error');
            return;
        }
        
        // اعتبارسنجی serviceId
        if (!this.options.serviceId) {
            this.showDiscountMessage('خطا در شناسایی سرویس', 'error');
            return;
        }        
    
        const applyBtn = document.getElementById('apply-discount-btn');
        const originalText = applyBtn.innerHTML;
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
        try {
            const nonce = this.getNonce();
            if (!nonce) {
                throw new Error('خطا در تأیید هویت');
            }
    
            console.log('Sending discount request:', {
                discountCode,
                serviceId: this.options.serviceId,
                nonce
            });
    
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'validate_discount_code',
                    'discount_code': discountCode,
                    'service_id': this.options.serviceId,
                    'nonce': nonce
                })
            });
    
            const responseText = await response.text();
            console.log('Raw response:', responseText);
    
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('پاسخ سرور نامعتبر است');
            }
    
            console.log('Parsed response:', data);
    
            if (data.success) {
                this.handleDiscountSuccess(data.data);
            } else {
                this.handleDiscountError(data.data?.message || 'کد تخفیف معتبر نیست');
            }
        } catch (error) {
            console.error('Error applying discount:', error);
            this.handleDiscountError(error.message || 'خطا در ارتباط با سرور');
        } finally {
            applyBtn.disabled = false;
            applyBtn.innerHTML = originalText;
        }
    }
    
    // اضافه کردن متدهای کمکی
    getNonce() {
        // اولویت‌بندی برای دریافت nonce
        if (typeof discountFrontendAdminVars !== 'undefined' && discountFrontendAdminVars.nonce) {
            return discountFrontendAdminVars.nonce;
        }
        if (typeof aiAssistantVars !== 'undefined' && aiAssistantVars.nonce) {
            return aiAssistantVars.nonce;
        }
        return null;
    }
    
    isUserLoggedIn() {
        return typeof aiAssistantVars !== 'undefined' && aiAssistantVars.user_id && aiAssistantVars.user_id !== '0';
    }

    handleDiscountSuccess = function(data) {
        console.log('Discount success data:', data);
        
        // تبدیل مقادیر به عدد برای اطمینان
        this.finalPrice = parseFloat(data.final_price) || 0;
        this.discountAmount = parseFloat(data.discount_amount) || 0;
        this.originalPrice = parseFloat(data.original_price) || this.originalPrice;
        
        this.discountApplied = true;
        
        // ذخیره اطلاعات تخفیف در متغیرهای global
        window.discountApplied = true;
        window.appliedDiscountCode = document.getElementById('discount-code-input').value;
        window.discountAmount = this.discountAmount;
        window.originalPrice = this.originalPrice;
        window.finalPrice = this.finalPrice;
        
        // به روزرسانی نمایش قیمت
        this.updatePriceDisplay();
        
        // نمایش جزئیات تخفیف
        this.showDiscountDetails(data);
        
        this.showDiscountMessage(data.message, 'success');
        
        // به روزرسانی بررسی موجودی با قیمت جدید
        this.fetchUserBalance(this.finalPrice);
        
        this.updateOriginalPriceDisplay();
    }

    updateOriginalPriceDisplay() {
        const originalPriceElement = document.getElementById('original-price-display');
        const formattedOriginalPrice = new Intl.NumberFormat('fa-IR').format(this.originalPrice);
        originalPriceElement.textContent = formattedOriginalPrice + ' تومان';
    }

    handleDiscountError(message) {
        this.showDiscountMessage(message, 'error');
        this.resetDiscount();
    }

    showDiscountMessage(message, type) {
        const messageElement = document.getElementById('discount-message');
        messageElement.textContent = message;
        messageElement.style.color = type === 'success' ? '#28a745' : 
                                   type === 'error' ? '#dc3545' : '#6c757d';
    }

    updatePriceDisplay() {
        const finalPriceElement = document.getElementById('final-price');
        const formattedPrice = new Intl.NumberFormat('fa-IR').format(this.finalPrice);
        finalPriceElement.textContent = formattedPrice + ' تومان';
        
        console.log('Final price updated:', this.finalPrice); // برای دیباگ
    }

    showDiscountDetails(data) {
        const discountDisplay = document.getElementById('discount-display');
        const discountAmountElement = document.getElementById('discount-amount');
        
        const formattedDiscount = new Intl.NumberFormat('fa-IR').format(this.discountAmount);
        discountAmountElement.textContent = formattedDiscount + ' تومان';
        
        discountDisplay.style.display = 'block';
        
        console.log('Discount details:', {
            discountAmount: this.discountAmount,
            originalPrice: this.originalPrice,
            finalPrice: this.finalPrice
        }); // برای دیباگ
    }

    resetDiscount() {
        this.finalPrice = this.originalPrice;
        this.discountAmount = 0;
        this.discountApplied = false;
        
        // ریست کردن متغیرهای global
        window.discountApplied = false;
        window.appliedDiscountCode = '';
        window.discountAmount = 0;
        window.finalPrice = this.originalPrice;
        
        this.updatePriceDisplay();
        
        const discountDisplay = document.getElementById('discount-display');
        discountDisplay.style.display = 'none';
        
        const discountInput = document.getElementById('discount-code-input');
        discountInput.value = '';
        
        // به روزرسانی بررسی موجودی
        this.fetchUserBalance(this.finalPrice);
        
        this.showDiscountMessage('تخفیف حذف شد', 'info');
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

            const data = await response.json();

            if (data.success) {
                this.updateBalanceUI(data.data.credit, servicePrice);
            } else {
                throw new Error(data.data?.message || 'خطا در دریافت موجودی');
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
            balanceElement.style.color = 'red';
            confirmBtn.textContent = 'افزایش موجودی';
            confirmBtn.onclick = () => {
                const baseUrl = window.location.origin;
                window.location.href = `${baseUrl}/wallet-charge/`;
            };
        } else {
            confirmBtn.textContent = 'تأیید پرداخت';
            confirmBtn.onclick = () => {
                if (this.options.onConfirm) {
                    // ارسال اطلاعات تخفیف به تابع onConfirm
                    this.options.onConfirm(this.finalPrice, {
                        discountApplied: this.discountApplied,
                        finalPrice: this.finalPrice,
                        originalPrice: this.originalPrice
                    });
                }
                this.hide();
            };
        }
        
        confirmBtn.disabled = false;
    }

    hide() {
        if (this.popup) {
            document.body.removeChild(this.popup);
            this.popup = null;
            this.isOpen = false;
        }
    }
}

window.PaymentPopup = PaymentPopup;