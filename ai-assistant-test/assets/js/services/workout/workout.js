jQuery(document).ready(function($) {
    const workoutForm = $('#ai-workout-form');
    if (!workoutForm.length) return;

    workoutForm.on('submit', function(e) {
        e.preventDefault();
        
        const input = $('#ai-workout-input').val().trim();
        const resultDiv = $('#ai-workout-result');
        const responseContent = resultDiv.find('.ai-response-content');
        const submitBtn = workoutForm.find('button[type="submit"]');
        
        if (!input) {
            alert(aiAssistantVars.i18n.error + ': ' + 'لطفاً اطلاعات لازم را وارد کنید');
            return;
        }
        
        submitBtn.prop('disabled', true).text(aiAssistantVars.i18n.loading);
        resultDiv.hide();
        
        $.ajax({
            url: aiAssistantVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ai_assistant_process',
                prompt: input,
                security: aiAssistantVars.nonce,
                service_id: 'workout',
                price: 15000000
            },
            success: function(response) {
                if (response.success) {
                    const apiResponse = response.data.response;
                    const safeResponse = $('<div/>').text(apiResponse).html();
                    responseContent.html(safeResponse.replace(/\n/g, '<br>'));
                    resultDiv.hide().fadeIn(400);
                } else {
                    alert(aiAssistantVars.i18n.error + ': ' + response.data);
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
                alert(aiAssistantVars.i18n.error + ': ' + errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('دریافت برنامه');
            }
        });
    });
});