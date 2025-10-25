// /home/aidastya/public_html/wp-content/themes/ai-assistant/assets/js/main.js
/**
 * AI Assistant Main JavaScript File
 */

jQuery(document).ready(function($) {
    // 1. مدیریت کلیک روی کارت‌های سرویس
    $(document).on('click', '.ai-service-card', function(e) {
        if (!$(e.target).closest('a').length) {
            window.location = $(this).find('a').attr('href');
        }
    });

    // 2. نمایش نوتیفیکیشن
    window.showAINotice = function(message, type = 'success') {
        const notice = $(`
            <div class="ai-notice ai-notice-${type}">
                ${message}
                <span class="ai-notice-close">&times;</span>
            </div>
        `);
        
        $('.ai-container').prepend(notice);
        
        // بستن دستی نوتیفیکیشن
        notice.find('.ai-notice-close').on('click', function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // بستن خودکار پس از 5 ثانیه
        setTimeout(() => {
            notice.fadeOut(500, () => notice.remove());
        }, 5000);
    };

    


    // انتخاب مبلغ از پیش تعیین شده
    $('.ai-amount-preset').click(function() {
        $('.ai-amount-preset').removeClass('active');
        $(this).addClass('active');
        $('#custom_amount').val('');
        $('#charge_amount').val($(this).data('amount'));
    });
    
    // تغییر مبلغ دلخواه
    $('#custom_amount').on('input', function() {
        if ($(this).val() !== '') {
            $('.ai-amount-preset').removeClass('active');
            $('#charge_amount').val($(this).val());
        }
    });
    
    // اعتبارسنجی فرم قبل از ارسال
    $('.ai-charge-form').submit(function(e) {
        var amount = $('#charge_amount').val();
        
        if (!amount || amount < 1000) {
            e.preventDefault();
            alert('لطفا مبلغ معتبری وارد کنید (حداقل ۱,۰۰۰ تومان)');
            return false;
        }
    });
    
    
    
    // 4. لاگ وضعیت اولیه
    // console.log('AI Assistant: Main script loaded', aiAssistantVars);
});
