// payment-popup.js - نسخه اصلاح شده
class PaymentPopup {
    constructor(options = {}) {
        this.options = {
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null,
            serviceType: options.serviceType || 'سرویس',
            basePrice: options.basePrice || null, // 🔥 اضافه شده
            customPrice: options.customPrice || null,
            ajaxAction: options.ajaxAction,
            serviceId: options.serviceId || '',
            includeConsultantFee: options.includeConsultantFee || false,
            consultantFee: options.consultantFee || 0,
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
        this.hasAutoDiscount = false;
        this.autoDiscount = null;
        this.consultantFee = this.options.consultantFee || 0;
        this.basePrice = this.options.basePrice; 
        this.focusHandler = null;
        this.visibilityHandler = null;  
    }
        
    async show() {
        if (this.isOpen) return;
        
        try {
            console.log('🔧 PaymentPopup options:', this.options);
            console.log('💰 basePrice دریافت شده:', this.basePrice);            
            console.log('🔧 PaymentPopup options:', this.options);
            
            // دریافت قیمت سرویس با اعمال تخفیف‌های خودکار
            const priceData = await this.getServicePriceWithDiscount();
            
            console.log('💰 قیمت دریافتی از سرور:', priceData);
            console.log('💼 هزینه مشاور:', this.consultantFee);
            console.log('📋 شامل هزینه مشاور:', this.options.includeConsultantFee);
            
            this.originalPrice = priceData.original_price;
            this.finalPrice = priceData.final_price;
            this.hasAutoDiscount = priceData.has_discount;
            this.autoDiscount = priceData.discount;
            this.priceData = priceData; // 🔥 ذخیره داده‌های اصلی
            
            console.log('🎯 قیمت نهایی پس از محاسبات:', {
                original: this.originalPrice,
                final: this.finalPrice,
                hasAutoDiscount: this.hasAutoDiscount,
                autoDiscountAmount: priceData.has_discount ? priceData.discount_amount : 0
            });
            
            // Create popup
            this.createPopupElement();
            
            // Fetch user balance
            await this.fetchUserBalance(this.finalPrice);
            
            this.isOpen = true;
            
            this.setupBalanceRefresh();
        } catch (error) {
            console.error('Error showing payment popup:', error);
            alert('خطا در نمایش پرداخت: ' + error.message);
        }
    }

    async getServicePriceWithDiscount() {
        try {
            const response = await fetch(aiAssistantVars.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'get_service_price_with_discount',
                    'service_id': this.options.serviceId,
                    'include_consultant_fee': this.options.includeConsultantFee ? '1' : '0',
                    'consultant_fee': this.options.consultantFee.toString(),
                    'nonce': this.getNonce()
                })
            });
    
            const data = await response.json();
            
            if (data.success) {
                console.log('💰 قیمت با تخفیف خودکار از سرور:', data.data);
                
                // 🔥 اصلاح: اضافه کردن هزینه مشاور به قیمت‌ها
                let originalPrice = parseFloat(data.data.original_price) || 0;
                let finalPrice = parseFloat(data.data.final_price) || 0;
                
                if (this.options.includeConsultantFee && this.consultantFee > 0) {
                    originalPrice += this.consultantFee;
                    finalPrice += this.consultantFee;
                    
                    console.log('💰 قیمت پس از اضافه کردن هزینه مشاور:', {
                        original: originalPrice,
                        final: finalPrice,
                        consultantFee: this.consultantFee
                    });
                    
                    // به‌روزرسانی داده‌ها با هزینه مشاور
                    data.data.original_price = originalPrice;
                    data.data.final_price = finalPrice;
                }
                
                return data.data;
            } else {
                throw new Error(data.data?.message || 'خطا در دریافت قیمت');
            }
        } catch (error) {
            console.error('Error getting service price with discount:', error);
            return await this.getServicePriceFallback();
        }
    }

    async getServicePriceFallback() {
        try {
            let basePrice = this.options.customPrice || await this.getServicePrice();
            
            console.log('🔄 استفاده از fallback price:', {
                basePrice: basePrice,
                includeConsultantFee: this.options.includeConsultantFee,
                consultantFee: this.consultantFee
            });
            
            // اگر هزینه مشاور وجود دارد، به قیمت پایه اضافه کن
            if (this.options.includeConsultantFee && this.consultantFee > 0) {
                basePrice += this.consultantFee;
                console.log('💰 قیمت fallback با هزینه مشاور:', basePrice);
            }
            
            return {
                original_price: basePrice,
                final_price: basePrice,
                discount_amount: 0,
                has_discount: false,
                discount: null
            };
        } catch (error) {
            console.error('Error in fallback price:', error);
            throw error;
        }
    }

    // در تابع getServicePrice در payment-popup.js
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
    
            const data = await response.json();
            
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

    createPopupElement() {
        // استفاده از this.priceData که در تابع show ذخیره شده
        const priceData = this.priceData;
        
        // محاسبه قیمت پایه (بدون هزینه مشاور)
        const basePrice = this.options.includeConsultantFee ? 
            (priceData.original_price - this.consultantFee) : 
            priceData.original_price;
        
        // محاسبه قیمت نهایی پایه (با تخفیف خودکار اما بدون هزینه مشاور)
        const baseFinalPrice = this.options.includeConsultantFee ? 
            (priceData.final_price - this.consultantFee) : 
            priceData.final_price;
        
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
        
        const formattedBasePrice = new Intl.NumberFormat('fa-IR').format(basePrice);
        const formattedBaseFinalPrice = new Intl.NumberFormat('fa-IR').format(baseFinalPrice);
        const formattedConsultantFee = new Intl.NumberFormat('fa-IR').format(this.consultantFee);
        const formattedFinalPrice = new Intl.NumberFormat('fa-IR').format(priceData.final_price);
        const formattedOriginalPrice = new Intl.NumberFormat('fa-IR').format(priceData.original_price);
        
        this.popup.innerHTML = `
            <div style="
                background: white;
                padding: 20px;
                border-radius: 8px;
                max-width: 450px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            ">
                <div style="margin-bottom: 15px;">
                    <h3 style="margin: 0 0 15px 0; color: #333;">تایید پرداخت</h3>
                    
                    <!-- نمایش تخفیف خودکار اگر وجود دارد -->
                    ${priceData.has_discount ? `
                    <div style="background: #e8f5e8; border: 1px solid #4caf50; border-radius: 6px; padding: 10px; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #2e7d32;">
                            <i class="fas fa-tag" style="font-size: 16px;"></i>
                            <strong>تخفیف خودکار اعمال شد!</strong>
                        </div>
                        <div style="font-size: 13px; margin-top: 5px; color: #388e3c;">
                            ${priceData.discount.name} - 
                            ${priceData.discount.type === 'percentage' ? 
                              priceData.discount.amount + '%' : 
                              new Intl.NumberFormat('fa-IR').format(priceData.discount.amount) + ' تومان'}
                        </div>
                    </div>
                    ` : ''}
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>موجودی شما:</span>
                        <span id="current-balance" style="font-weight: bold;">در حال بارگذاری...</span>
                    </div>
                    

                    <!-- بخش جزئیات قیمت (به صورت پویا پر می‌شود) -->
                    <div style="background: #f8f9fa; border-radius: 6px; padding: 15px; margin-bottom: 15px;" class="price-details-container">
                        <!-- محتوای این بخش توسط تابع updatePriceDetails پر می‌شود -->
                    </div>                   
                    <!-- بخش کد تخفیف -->
                    <div style="margin-bottom: 15px; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px;">
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <input type="text" 
                                id="discount-code-input" 
                                placeholder="کد تخفیف اضافی (اختیاری)"
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
                            ">اعمال کد تخفیف</button>
                        </div>
                        <div id="discount-message" style="font-size: 12px; min-height: 16px;"></div>
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
        this.setupEventListeners();
        
        this.updatePriceDetails(this.priceData);
    }

    setupEventListeners() {
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
                e.stopPropagation();
                return;
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                e.preventDefault();
                e.stopPropagation();
                return false;                
            }
        });
    }

    async applyDiscount() {
        const discountCode = document.getElementById('discount-code-input').value.trim();
        const messageElement = document.getElementById('discount-message');
        
        console.log('🎫 اعمال کد تخفیف:', discountCode);
        
        if (!discountCode) {
            this.showDiscountMessage('لطفا کد تخفیف را وارد کنید', 'error');
            return;
        }
        
        // ذخیره وضعیت فعلی قبل از اعمال کد تخفیف جدید
        const previousState = {
            finalPrice: this.finalPrice,
            discountApplied: this.discountApplied,
            discountAmount: this.discountAmount,
            hasAutoDiscount: this.hasAutoDiscount,
            autoDiscount: this.autoDiscount
        };
        
        const applyBtn = document.getElementById('apply-discount-btn');
        const originalText = applyBtn.innerHTML;
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
        try {
            const nonce = this.getNonce();
            if (!nonce) {
                throw new Error('خطا در تأیید هویت');
            }
    
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
    
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('پاسخ سرور نامعتبر است');
            }
    
            if (data.success) {
                console.log('✅ کد تخفیف معتبر:', data.data);
                this.handleDiscountSuccess(data.data);
            } else {
                // در صورت خطا، به وضعیت قبلی برگرد
                this.finalPrice = previousState.finalPrice;
                this.discountApplied = previousState.discountApplied;
                this.discountAmount = previousState.discountAmount;
                this.hasAutoDiscount = previousState.hasAutoDiscount;
                this.autoDiscount = previousState.autoDiscount;
                
                this.handleDiscountError(data.data?.message || 'کد تخفیف معتبر نیست');
            }
        } catch (error) {
            console.error('Error applying discount:', error);
            // در صورت خطا، به وضعیت قبلی برگرد
            this.finalPrice = previousState.finalPrice;
            this.discountApplied = previousState.discountApplied;
            this.discountAmount = previousState.discountAmount;
            this.hasAutoDiscount = previousState.hasAutoDiscount;
            this.autoDiscount = previousState.autoDiscount;
            
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
    
    handleDiscountSuccess(data) {
        console.log('🎫 داده‌های کد تخفیف دریافتی:', data);
        
        // 🔥 استفاده از basePrice که از form-events.js دریافت شده
        let basePrice = this.basePrice;
        if (!basePrice) {
            basePrice = this.options.includeConsultantFee ? 
                (this.priceData.original_price - this.consultantFee) : 
                this.priceData.original_price;
        }
        
        // 🔥 محاسبه تخفیف خودکار از داده‌های اصلی
        let autoDiscountAmount = 0;
        if (this.priceData && this.priceData.has_discount) {
            autoDiscountAmount = parseFloat(this.priceData.discount_amount) || 0;
        }
        
        // 🔥 محاسبه تخفیف کد معرف
        const manualDiscountAmount = parseFloat(data.discount_amount) || 0;
        
        // 🔥 محاسبه جمع کل تخفیف
        const totalDiscountAmount = autoDiscountAmount + manualDiscountAmount;
        
        console.log('🧮 محاسبات نهایی تخفیف:', {
            basePrice: basePrice,
            autoDiscount: autoDiscountAmount,
            manualDiscount: manualDiscountAmount,
            totalDiscount: totalDiscountAmount
        });
        
        // 🔥 محاسبه قیمت نهایی: basePrice - totalDiscountAmount + consultantFee
        let finalPrice = basePrice - totalDiscountAmount;
        if (this.options.includeConsultantFee && this.consultantFee > 0) {
            finalPrice += this.consultantFee;
        }
        
        this.finalPrice = finalPrice;
        this.discountAmount = totalDiscountAmount;
        this.originalPrice = basePrice + (this.options.includeConsultantFee ? this.consultantFee : 0);
        
        this.discountApplied = true;
        
        // ذخیره اطلاعات تخفیف در state
        if (window.state && window.state.formData) {
            window.state.formData.discountInfo = {
                discountCode: document.getElementById('discount-code-input').value.trim(),
                discountApplied: true,
                discountAmount: this.discountAmount,
                originalPrice: this.originalPrice,
                finalPrice: this.finalPrice,
                discountData: data
            };
        }
        
        // به روزرسانی نمایش قیمت
        this.updatePriceDisplay();
        this.updatePriceDetails(data);
        this.showDiscountMessage(data.message, 'success');
        
        // به روزرسانی بررسی موجودی با قیمت جدید
        this.fetchUserBalance(this.finalPrice);
    }
        
    // تابع جدید برای به‌روزرسانی جزئیات قیمت
    updatePriceDetails(discountData = null) {
        // 🔥 استفاده از basePrice که از form-events.js دریافت شده
        let basePrice = this.basePrice;
        
        // اگر basePrice وجود ندارد، از محاسبه استفاده کن
        if (!basePrice) {
            basePrice = this.options.includeConsultantFee ? 
                (this.originalPrice - this.consultantFee) : 
                this.originalPrice;
        }
        
        console.log('🔢 basePrice استفاده شده:', basePrice);
        
        // 🔥 محاسبه تخفیف خودکار از داده‌های اصلی
        let autoDiscountAmount = 0;
        if (this.priceData && this.priceData.has_discount) {
            autoDiscountAmount = parseFloat(this.priceData.discount_amount) || 0;
        }
        
        // 🔥 محاسبه تخفیف کد معرف فقط اگر کاربر کد تخفیف وارد کرده باشد
        let manualDiscountAmount = 0;
        if (discountData && discountData.discount_amount && this.discountApplied) {
            manualDiscountAmount = parseFloat(discountData.discount_amount) || 0;
        }
        
        // 🔥 جمع کل تخفیف‌ها
        const totalDiscountAmount = autoDiscountAmount + manualDiscountAmount;
        
        console.log('🔢 محاسبات تخفیف:', {
            basePrice: basePrice,
            autoDiscount: autoDiscountAmount,
            manualDiscount: manualDiscountAmount,
            totalDiscount: totalDiscountAmount,
            discountApplied: this.discountApplied
        });
        
        const formattedBasePrice = new Intl.NumberFormat('fa-IR').format(basePrice);
        const formattedConsultantFee = new Intl.NumberFormat('fa-IR').format(this.consultantFee);
        const formattedTotalDiscount = new Intl.NumberFormat('fa-IR').format(totalDiscountAmount);
        
        // پیدا کردن المان‌های جزئیات قیمت و به‌روزرسانی آنها
        const priceDetailsContainer = this.popup.querySelector('.price-details-container');
        if (priceDetailsContainer) {
            priceDetailsContainer.innerHTML = `
                <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">جزئیات هزینه:</h4>
                
                <!-- قیمت پایه رژیم -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                    <span>قیمت پایه رژیم:</span>
                    <span>${formattedBasePrice} تومان</span>
                </div>
                
                ${this.options.includeConsultantFee && this.consultantFee > 0 ? `
                <!-- هزینه مشاور -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                    <span>هزینه تأیید متخصص:</span>
                    <span style="color: #00857a;">+ ${formattedConsultantFee} تومان</span>
                </div>
                
                <!-- قیمت کل قبل از تخفیف -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #666;">
                    <span>جمع کل:</span>
                    <span style="text-decoration: line-through;">${new Intl.NumberFormat('fa-IR').format(basePrice + this.consultantFee)} تومان</span>
                </div>
                ` : ''}
                
                ${totalDiscountAmount > 0 ? `
                <!-- 🔥 نمایش جمع کل تخفیف -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #28a745;">
                    <span>مقدار تخفیف:</span>
                    <span>- ${formattedTotalDiscount} تومان</span>
                </div>
                ` : ''}
                
                <!-- خط جداکننده -->
                <div style="border-top: 1px dashed #ddd; margin: 10px 0; padding-top: 10px;">
                    <div style="display: flex; justify-content: space-between; font-weight: bold; color: #00857a;">
                        <span>مبلغ قابل پرداخت:</span>
                        <span id="final-price">${new Intl.NumberFormat('fa-IR').format(this.finalPrice)} تومان</span>
                    </div>
                </div>
            `;
        }
    }

    updateOriginalPriceDisplay() {
        const originalPriceElement = document.getElementById('original-price-display');
        const formattedOriginalPrice = new Intl.NumberFormat('fa-IR').format(this.originalPrice);
        originalPriceElement.textContent = formattedOriginalPrice + ' تومان';
    }

    // در تابع handleDiscountError این تغییرات رو اعمال کنید:
    handleDiscountError(message) {
        this.showDiscountMessage(message, 'error');
        
        // فقط کد تخفیف رو ریست کن، تخفیف عمومی رو حفظ کن
        this.resetCouponOnly();
    }
        
    resetCouponOnly() {
        this.discountApplied = false;
        
        // 🔥 بازگشت به حالت اولیه فقط با تخفیف خودکار
        this.finalPrice = this.priceData.final_price;
        
        // 🔥 اگر هزینه مشاور وجود دارد، آن را اضافه کن
        // if (this.options.includeConsultantFee && this.consultantFee > 0) {
        //     this.finalPrice += this.consultantFee;
        // }
        
        // 🔥 فقط تخفیف خودکار
        this.discountAmount = this.priceData.has_discount ? parseFloat(this.priceData.discount_amount) || 0 : 0;
        
        // به‌روزرسانی state
        if (window.state && window.state.formData) {
            if (window.state.formData.discountInfo) {
                window.state.formData.discountInfo.discountCode = '';
                window.state.formData.discountInfo.discountApplied = false;
                window.state.formData.discountInfo.discountAmount = this.discountAmount;
                window.state.formData.discountInfo.finalPrice = this.finalPrice;
                window.state.formData.discountInfo.discountData = null;
            }
        }
        
        this.updatePriceDisplay();
        this.updatePriceDetails();
        
        const discountInput = document.getElementById('discount-code-input');
        if (discountInput) {
            discountInput.value = '';
        }
        
        // به‌روزرسانی بررسی موجودی
        this.fetchUserBalance(this.finalPrice);
        this.showDiscountMessage('', 'info');
        
        console.log('🔄 تخفیف حذف شد:', {
            finalPrice: this.finalPrice,
            discountAmount: this.discountAmount
        });
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
    }

    showDiscountDetails(data) {
        const discountDisplay = document.getElementById('discount-display');
        const discountAmountElement = document.getElementById('discount-amount');
        
        if (discountDisplay && discountAmountElement) {
            const formattedDiscount = new Intl.NumberFormat('fa-IR').format(this.discountAmount);
            discountAmountElement.textContent = formattedDiscount + ' تومان';
            discountDisplay.style.display = 'block';
        }
    }

    resetDiscount() {
        this.finalPrice = this.originalPrice;
        this.discountAmount = 0;
        this.discountApplied = false;
        
        // حذف اطلاعات تخفیف از state
        if (window.state && window.state.formData) {
            window.state.formData.discountInfo = {
                discountCode: '',
                discountApplied: false,
                discountAmount: 0,
                originalPrice: this.originalPrice,
                finalPrice: this.finalPrice,
                discountData: null
            };
        }
        
        this.updatePriceDisplay();
        
        const discountDisplay = document.getElementById('discount-display');
        if (discountDisplay) {
            discountDisplay.style.display = 'none';
        }
        
        const discountInput = document.getElementById('discount-code-input');
        if (discountInput) {
            discountInput.value = '';
        }
        
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
    
    setupBalanceRefresh() {
        // ذخیره رفرنس‌های handler
        this.focusHandler = () => {
            if (this.isOpen) {
                this.fetchUserBalance(this.finalPrice);
            }
        };
        
        this.visibilityHandler = () => {
            if (!document.hidden && this.isOpen) {
                this.fetchUserBalance(this.finalPrice);
            }
        };
        
        // اضافه کردن event listenerها
        window.addEventListener('focus', this.focusHandler);
        document.addEventListener('visibilitychange', this.visibilityHandler);
    }


    updateBalanceUI(balance, servicePrice) {
        const balanceElement = document.getElementById('current-balance');
        const confirmBtn = document.getElementById('confirm-payment');
        
        let formattedBalance = '';
        if (balance !== null && balance !== undefined) {
            formattedBalance = new Intl.NumberFormat('fa-IR').format(balance);
        }        
        balanceElement.textContent = formattedBalance + ' تومان';
        
        if (balance < servicePrice) {
            balanceElement.style.color = 'red';
            confirmBtn.textContent = 'افزایش موجودی';
            confirmBtn.onclick = () => {
                const baseUrl = window.location.origin;
                const shortfall = Math.ceil(servicePrice - balance); // مقدار کمبود
                window.open(baseUrl + "/wallet-charge/?needed_amount=" + shortfall, "_blank");
            };
        } else {
            balanceElement.style.color = '#333';
            // حالت اول: موجودی کافی است
            confirmBtn.textContent = 'تأیید پرداخت';
            // ✅ راه حل: استفاده از arrow function برای حفظ this
            confirmBtn.onclick = () => {
                if (this.options.onConfirm) {
                    // 1. دریافت داده‌های کامل از state
                    const completeFormData = {
                        userInfo: { ...window.state.formData.userInfo },
                        serviceSelection: { ...window.state.formData.serviceSelection },
                        discountInfo: window.state.formData.discountInfo ? { ...window.state.formData.discountInfo } : {}
                    };
                    
                    // 2. اضافه کردن کد تخفیف از input اگر وجود دارد
                    const discountCodeInput = document.getElementById('discount-code-input');
                    if (discountCodeInput && discountCodeInput.value.trim() && completeFormData.discountInfo) {
                        completeFormData.discountInfo.discountCode = discountCodeInput.value.trim();
                    }
                    
                    // 3. ارسال داده‌های کامل
                    this.options.onConfirm(completeFormData, this.finalPrice, {
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
        this.isOpen = false;
        this.resetDiscount();
        
        if (this.popup) {
            document.body.removeChild(this.popup);
            this.popup = null;
        }
        
        this.isOpen = false;
        
        // پاک کردن event listenerها
        this.cleanupBalanceRefresh();
    }
    
    cleanupBalanceRefresh() {
        // حذف event listenerها
        if (this.focusHandler) {
            window.removeEventListener('focus', this.focusHandler);
            this.focusHandler = null;
        }
        
        if (this.visibilityHandler) {
            document.removeEventListener('visibilitychange', this.visibilityHandler);
            this.visibilityHandler = null;
        }
    }
    
    
    resetDiscount() {
        this.finalPrice = this.originalPrice;
        this.discountAmount = 0;
        this.discountApplied = false;
        
        // حذف اطلاعات تخفیف از state اصلی
        if (window.state && window.state.formData) {
            window.state.formData.discountInfo = {
                discountCode: '',
                discountApplied: false,
                discountAmount: 0,
                originalPrice: this.originalPrice,
                finalPrice: this.originalPrice, // مهم: برگشت به قیمت اصلی
                discountData: null
            };
        }
        
        this.updatePriceDisplay();
        
        const discountDisplay = document.getElementById('discount-display');
        if (discountDisplay) {
            discountDisplay.style.display = 'none';
        }
        
        const discountInput = document.getElementById('discount-code-input');
        if (discountInput) {
            discountInput.value = '';
        }
        
        if (this.isOpen) {
            this.fetchUserBalance(this.originalPrice);
        }
        
        this.showDiscountMessage('', 'info');
    }    
}

window.PaymentPopup = PaymentPopup;