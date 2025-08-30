jQuery(document).ready(function($) {
    const chatForm = $('#ai-chat-form');
    if (!chatForm.length) return;

    chatForm.on('submit', function(e) {
        e.preventDefault();
        
        const input = $('#ai-chat-input').val().trim();
        const resultDiv = $('#ai-chat-result');
        const responseContent = resultDiv.find('.ai-response-content');
        const submitBtn = chatForm.find('button[type="submit"]');
        
        // اعتبارسنجی اولیه
        if (!input) {
            console.error('AI Assistant: Input is empty');
            alert(aiAssistantVars.i18n.error + ': ' + 'لطفاً متن درخواست را وارد کنید');
            return;
        }
        
        console.log('AI Assistant: Submitting chat request', {input});
        
        // نمایش حالت لودینگ
        submitBtn.prop('disabled', true).text(aiAssistantVars.i18n.loading);
        resultDiv.hide();
        //console.log('آماده‌سازی درخواست AJAX');
        
        console.log('Sending AJAX request with data:', {
            action: 'ai_assistant_process',
            input: input,
            security: aiAssistantVars.nonce
        });          
        
        $.ajax({
            url: aiAssistantVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ai_assistant_process', // یا 'ai_assistant_process' اگر روش ۲ را انتخاب کردید
                prompt: input, // به جای service_id
                security: aiAssistantVars.nonce,
                service_id: 'chat', // یک مقدار ثابت یا از جایی بگیرید
                price: 10000000 // یا مقدار مناسب
            },
            success: function(response) {
                //alert('ok');
                console.log('AI Assistant: Chat response', response);
                
                if (response.success) {
                    
                    //resultDiv.html('<div class="plgtest-success">' + response.data + '</div>');
                    //resultDiv.fadeIn();
                    
                    const apiResponse = response.data.response; // دسترسی به متن پاسخ
                    const safeResponse = $('<div/>').text(apiResponse).html();
                    responseContent.html(safeResponse.replace(/\n/g, '<br>'));
                    
                    $('#ai-chat-result')
                        .css('display', 'block') // حذف inline style
                        .hide() // آماده‌سازی برای fadeIn
                        .fadeIn(400); // نمایش با انیمیشن
                                
        
        
                /*    
                    // به روزرسانی موجودی کاربر
                    if (response.data.remaining_credit !== undefined) {
                        $('.ai-user-credit').text(response.data.remaining_credit.toLocaleString('fa-IR'));
                    }
                */    
                
                } else {
                    
                    alert(aiAssistantVars.i18n.error + ': ' + response.data);
                }
            },
            error: function(xhr) {
             //   console.error('AI Assistant: Chat error', xhr.responseText);
                
                console.error('Full Error Response:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseText
                });                
                
                let errorMsg = 'خطا در ارتباط با سرور';
                if (xhr.responseText && xhr.responseText.trim() !== '') {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                        // اگر JSON نبود، ممکن است متن ساده باشد
                        errorMsg = xhr.responseText || errorMsg;
                    }
                }
                
                alert(aiAssistantVars.i18n.error + ': ' + errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('ارسال درخواست');
            }
        });
    });
    
    
  
});