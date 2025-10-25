// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/discount-consumer.js
jQuery(document).ready(function($) {
    // تابع عمومی برای استفاده در تمام فرم‌ها
    window.initDiscountHandler = function(serviceId, priceElement) {
        const handler = {
            serviceId: serviceId,
            originalPrice: 0,
            currentDiscount: null,
            
            init: function() {
                this.originalPrice = this.extractPrice(priceElement);
                this.bindEvents();
            },
            
            bindEvents: function() {
                $(document).on('click', '.apply-discount-btn', () => this.applyDiscount());
                $(document).on('click', '.remove-discount-btn', () => this.removeDiscount());
            },
            
            applyDiscount: function() {
                const discountCode = $('.discount-code-input').val();
                if (!discountCode) return;
                
                $.ajax({
                    url: discountFrontendAdminVars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'validate_discount_code',
                        discount_code: discountCode,
                        service_id: this.serviceId,
                        nonce: discountFrontendAdminVars.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            this.handleSuccess(response.data);
                        } else {
                            this.handleError(response.data.message);
                        }
                    },
                    error: () => {
                        this.handleError('خطا در ارتباط با سرور');
                    }
                });
            },
            
            handleSuccess: function(data) {
                this.currentDiscount = data;
                this.updateDisplay(data);
                this.showMessage(data.message, 'success');
            },
            
            handleError: function(message) {
                this.showMessage(message, 'error');
            },
            
            updateDisplay: function(data) {
                // به‌روزرسانی قیمت‌ها در UI
                $('.original-price').text(this.formatPrice(data.original_price));
                $('.final-price').text(this.formatPrice(data.final_price));
                $('.discount-amount').text(this.formatPrice(data.discount_amount));
                
                // نمایش بخش تخفیف
                $('.discount-display').show();
            },
            
            removeDiscount: function() {
                this.currentDiscount = null;
                $('.final-price').text(this.formatPrice(this.originalPrice));
                $('.discount-display').hide();
                $('.discount-code-input').val('');
                this.showMessage('تخفیف حذف شد', 'info');
            },
            
            extractPrice: function(element) {
                // استخراج قیمت از المنت
                const priceText = $(element).text().replace(/[^0-9]/g, '');
                return parseInt(priceText) || 0;
            },
            
            formatPrice: function(price) {
                return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
            },
            
            showMessage: function(message, type) {
                $('.discount-message')
                    .removeClass('success error info')
                    .addClass(type)
                    .text(message)
                    .show();
                
                setTimeout(() => {
                    $('.discount-message').fadeOut();
                }, 5000);
            },
            
            // برای استفاده در مرحله پرداخت
            getFinalPrice: function() {
                return this.currentDiscount ? this.currentDiscount.final_price : this.originalPrice;
            },
            
            getDiscountData: function() {
                return this.currentDiscount;
            }
        };
        
        handler.init();
        return handler;
    };
});