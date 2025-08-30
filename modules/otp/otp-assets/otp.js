// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/modules/otp/otp-assets/otp.js
jQuery(document).ready(function($) {

    $('#otp-request-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var mobile = $('#mobile').val().trim();
        
        // اعتبارسنجی اولیه
        if(!mobile) {
            showMessage('لطفاً شماره موبایل را وارد کنید', 'error');
            return;
        }
        
        if(!/^09\d{9}$/.test(mobile)) {
            showMessage('شماره موبایل معتبر نیست (09123456789)', 'error');
            return;
        }
        
        // نمایش وضعیت بارگذاری
        toggleFormLoading($form, true);
        
        $.ajax({
            url: otp_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'send_otp',
                mobile: mobile,
            },
            success: function(response) {
                if(response.success) {
                    handleOtpSuccess(response, mobile);
                } else {
                    showMessage(response.data || 'خطا در ارسال کد', 'error');
                }
            },
            error: function(xhr) {
                var errorMsg = 'خطا در ارتباط با سرور';
                
                // تحلیل دقیق‌تر خطا
                if(xhr.status === 0) {
                    errorMsg = 'اتصال به اینترنت قطع است';
                } else if(xhr.status === 500) {
                    errorMsg = 'خطای سرور داخلی';
                } else if(xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                }
                
                showMessage(errorMsg, 'error');
                console.error('Error details:', xhr);
            },
            complete: function() {
                toggleFormLoading($form, false);
            }
        });
    });
    
    function toggleFormLoading($form, isLoading) {
        $form.find('.btn-text').text(isLoading ? 'در حال ارسال...' : 'دریافت کد تایید');
        $form.find('.btn-loader').toggle(isLoading);
        $form.find('button').prop('disabled', isLoading);
    }
    
    function handleOtpSuccess(response, mobile) {
        $('#step1').hide();
        $('#step2').show();
        $('#verify-mobile').val(mobile);
        $('#mobile-display').text(mobile);
        startCountdown(120);
        
        // اصلاح این بخش:
        if(otp_vars.is_sandbox && response.data && response.data.debug_code) {
            const debugCode = response.data.debug_code;
            $('#otp-code').val(debugCode);
            showMessage(`کد آزمایشی: ${debugCode}`, 'success');
        } else {
            showMessage(response.data?.message || 'کد تایید ارسال شد', 'success');
        }
    }
    
    // تایید OTP
    $('#otp-verify-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var otp_code = $('#otp-code').val().trim();
        var mobile = $('#verify-mobile').val();
        
        if(!otp_code || otp_code.length !== 5) {
            showMessage('لطفا کد تایید ۵ رقمی را وارد کنید', 'error');
            return;
        }
        
        $form.find('.btn-text').text('در حال بررسی...');
        $form.find('.btn-loader').show();
        $form.find('button').prop('disabled', true);
        $('#message').hide();
        
        $.ajax({
            url: otp_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'verify_otp',
                mobile: mobile,
                otp_code: otp_code
            },
            success: function(response) {
                console.log('Raw response:', response);
                // در قسمت موفقیت‌آمیز بودن ورود OTP
                if(response.success) {
                    showMessage('ورود موفقیت‌آمیز! در حال انتقال...', 'success');
                    
                    // بررسی وجود redirect_url در sessionStorage
                    let redirectUrl = sessionStorage.getItem('diet_form_redirect_url') || otp_vars.home_url;
                    
                    // حذف پارامترهای موجود از URL
                    redirectUrl = redirectUrl.split('?')[0];
                    
                    // اضافه کردن پارامتر logged_in
                    redirectUrl += (redirectUrl.includes('?') ? '&' : '?') + 'logged_in=1';
                    
                    // اضافه کردن هش مرحله ذخیره شده
                    const savedStep = sessionStorage.getItem('diet_form_current_step');
                    if (savedStep) {
                        redirectUrl += `#step-${savedStep}`;
                    }
                    
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1500);
                } else {
                    showMessage(response.data || 'کد تایید نادرست است', 'error');
                }
            },
            error: function(xhr) {
                var errorMsg = 'خطا در ارتباط با سرور';
                if(xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                }
                showMessage(errorMsg, 'error');
            },
            complete: function() {
                $form.find('.btn-text').text('تایید و ورود');
                $form.find('.btn-loader').hide();
                $form.find('button').prop('disabled', false);
            }
        });
    });
    // نمایش پیام
    function showMessage(text, type) {
        var $message = $('#message');
        $message.css('font-family', 'Vazir, IRANSans, sans-serif');
        
        // پاکسازی متن برای جلوگیری از XSS
        text = $('<div/>').text(text).html();
        
        $message.html(text)
            .removeClass('success error')
            .addClass(type)
            .fadeIn();
        
        if(type === 'error') {
            setTimeout(function() {
                $message.fadeOut();
            }, 5000);
        }
    }
    
    // تایمر معکوس
    function startCountdown(duration) {
        var timer = duration;
        var $countdown = $('.countdown-text');
        var $circleFill = $('.circle-fill');
        var $resendBtn = $('#resend-otp');
        
        // محاسبه محیط دایره (2πr)
        var circumference = 2 * Math.PI * 15.9155;
        $circleFill.css('stroke-dasharray', circumference);
        
        var countdownInterval = setInterval(function() {
            var minutes = parseInt(timer / 60, 10);
            var seconds = parseInt(timer % 60, 10);
            
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
            
            $countdown.text(minutes + ":" + seconds);
            
            // محاسبه پیشرفت تایمر برای انیمیشن دایره
            var progress = timer / duration;
            $circleFill.css('stroke-dashoffset', circumference * progress);
            
            if (--timer < 0) {
                clearInterval(countdownInterval);
                $('.countdown-timer').hide();
                $resendBtn.fadeIn();
            }
        }, 1000);
        
        $resendBtn.off('click').on('click', function() {
            $('#otp-request-form').trigger('submit');
            $(this).hide();
            $('.countdown-timer').show();
            clearInterval(countdownInterval);
            startCountdown(120);
        });
    }

    // اضافه کردن این کد در انتهای فایل (قبل از بسته شدن document.ready)
    $(document).on('click', 'a[href*="action=logout"], .logout-link', function(e) {
        e.preventDefault();
        
        // نمایش وضعیت بارگذاری
        $(this).addClass('logging-out').text('در حال خروج...');
        
        $.post(otp_vars.ajax_url, {
            action: 'force_logout',
            security: otp_vars.nonce
        }).done(function(response) {
            // ریدایرکت اجباری حتی اگر کش شده باشد
            window.location.href = response.data.redirect + '?logout=' + Math.random().toString(36).substring(7);
        }).fail(function() {
            window.location.href = otp_vars.home_url + '?force_logout=1';
        });
    });  
});