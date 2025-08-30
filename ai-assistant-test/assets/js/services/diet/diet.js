// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/diet.js
jQuery(document).ready(function($) {
    const form = $('#multi-step-form');
    const resultDiv = $('#ai-diet-result');
    const responseContent = resultDiv.find('.ai-response-content');
    const submitBtn = form.find('.final-submit');    

    window.addEventListener('formSubmitted', function(event) {
        const receivedUserData = event.detail.formData;
        console.log('Form data received:', receivedUserData);

        // نمایش loader با امکان بستن
        const loader = new AiDastyarLoader({
            message: 'در حال پردازش درخواست...',
            closable: true,
            persistent: true,
            redirectOnClose: '/wallet-charge/'
        });
        loader.show();

        $.ajax({
            url: aiAssistantVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ai_assistant_process',
                userData: JSON.stringify(receivedUserData),
                security: aiAssistantVars.nonce,
                service_id: 'diet',
                price: 10000000
            },
            success: function(response) {
                if (response.success) {
                    // مخفی کردن پاپ‌آپ پرداخت اگر باز است
                    const paymentPopup = document.querySelector('.payment-confirmation-popup');
                    if (paymentPopup) {
                        document.body.removeChild(paymentPopup);
                    }
                    
                    loader.hide();
                    
                    // ذخیره نتیجه در sessionStorage
                    sessionStorage.setItem('diet_form_result', JSON.stringify(response.data));
                    
                    // هدایت به صفحه نتیجه با پارامتر
                    window.location.href = '/?ai_diet_result=1';
                } else {
                    // مخفی کردن لودینگ دکمه
                    const confirmBtn = document.querySelector('#confirm-payment');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.querySelector('.btn-text').style.display = 'inline-block';
                        confirmBtn.querySelector('.btn-loading').style.display = 'none';
                    }
                    
                    // نمایش خطا
                    if (response.data && response.data.includes('اعتبار')) {
                        loader.updateMessage(response.data);
                    } else {
                        loader.updateMessage('خطا در پردازش درخواست. لطفاً مجدداً تلاش کنید.');
                    }
                    
                    // تغییر دکمه بستن برای ریدایرکت به صفحه شارژ
                    const closeBtn = document.querySelector('#aidastyar-loading-overlay .close-loader');
                    if (closeBtn) {
                        closeBtn.onclick = function() {
                            loader.hide();
                            window.location.href = home_url('/wallet-charge/');
                        };
                    }
                }
            },
            error: function(xhr) {
                let errorMsg = 'خطا در ارتباط با سرور';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.data?.message || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.responseText || errorMsg;
                    }
                }
                
                loader.updateMessage(errorMsg);
                loader.options.redirectOnClose = window.location.href;
            }
        });        
    });
    
    document.getElementById('downloadPdf').addEventListener('click', function() {
        const element = document.querySelector('.ai-response-content');
        const originalOverflow = element.style.overflow;
        const originalHeight = element.style.height;
        const originalMaxHeight = element.style.maxHeight;

        element.style.overflow = 'visible';
        element.style.height = 'auto';
        element.style.maxHeight = 'none';

        const opt = {
            margin: 0.5,
            filename: 'برنامه-تغذیه.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            element.style.overflow = originalOverflow;
            element.style.height = originalHeight;
            element.style.maxHeight = originalMaxHeight;
        });
    });
});