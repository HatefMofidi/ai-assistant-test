// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/diet.js
jQuery(document).ready(function($) {
    const form = $('#multi-step-form');
    const resultDiv = $('#ai-diet-result');
    const responseContent = resultDiv.find('.ai-response-content');
    const submitBtn = form.find('.final-submit');    

    // در بخش formSubmitted event listener
    window.addEventListener('formSubmitted', function(event) {
        const receivedUserData = event.detail.formData;
    
        // نمایش loader با امکان بستن
        const loader = new AiDastyarLoader({
            message: 'در حال پردازش درخواست...',
            theme: 'light',
            size: 'medium',
            position: 'center',
            closable: false,
            overlay: true,
            onShow: function() {
                console.log('✅ AJAX Loader shown');
            },
            onHide: function() {
                console.log('✅ AJAX Loader hidden');
            }
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
                
                loader.hide();
                
                if (response.success) {
                    // مخفی کردن پاپ‌آپ پرداخت اگر باز است
                    const paymentPopup = document.querySelector('.payment-confirmation-popup');
                    if (paymentPopup) {
                        document.body.removeChild(paymentPopup);
                    }
                    
                    const successLoader = new AiDastyarLoader({
                        message: `
                            عملیات با موفقیت آغاز شد. نتیجه حداکثر تا ۱۵ دقیقه دیگر در تاریخچه سرویس‌ها قابل مشاهده خواهد بود.
                        `,
                        theme: 'light',
                        size: 'medium',
                        position: 'center',
                        closable: true,
                        overlay: true,
                        autoHide: 8000,
                        redirectOnClose: window.location.origin + '/',
                        onShow: function() {
                            console.log('✅ Success loader shown');
                        }
                    });
                    successLoader.show();
                    
                } else {
                    loader.update('خطا در پردازش درخواست. لطفاً مجدداً تلاش کنید.');
                    loader.setRedirectOnClose(window.location.href);
                }
            },
            error: function(xhr) {
                loader.hide();
                
                let errorMsg = 'خطا در ارتباط با سرور';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.data?.message || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.responseText || errorMsg;
                    }
                }
                
                loader.update(errorMsg);
                loader.setRedirectOnClose(window.location.href);
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