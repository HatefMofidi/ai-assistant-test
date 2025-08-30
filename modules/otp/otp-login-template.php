<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant/modules/otp/otp-login-template.php
 * Template Name: Custom OTP Login
 */

get_header(); ?>

<div class="otp-login-wrapper">
    <div class="otp-login-container">
        <div class="otp-header">
            <div class="logo">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/login-logo.avif" alt="Logo">
            </div>
            <h2>ورود / ثبت‌نام</h2>
            <p>کد تایید به شماره موبایل شما ارسال خواهد شد</p>
        </div>
        
        <div id="step1" class="otp-step">
            <form id="otp-request-form">
                <div class="form-group floating-label">
                    <input type="text" id="mobile" name="mobile" required>
                    <label for="mobile">شماره موبایل</label>
                </div>
                <button type="submit" class="btn-primary">
                    <span class="btn-text">دریافت کد تایید</span>
                    <span class="btn-loader"></span>
                </button>
            </form>
            
            <div class="otp-footer">
                <p>با ورود یا ثبت‌نام، <a href="#">شرایط و قوانین</a> را پذیرفته‌اید.</p>
            </div>
        </div>
        
        <div id="step2" class="otp-step" style="display:none;">
            <form id="otp-verify-form">
                <div class="form-group floating-label">
                    <input type="text" id="otp-code" name="otp_code" placeholder=" " required>
                    <label for="otp-code">کد تایید</label>
                </div>
                <input type="hidden" id="verify-mobile" name="mobile">
                
                <div class="otp-input-hint">
                    <p>کد ۵ رقمی ارسال شده به شماره <span id="mobile-display"></span> را وارد کنید</p>
                </div>
                
                <button type="submit" class="btn-primary">
                    <span class="btn-text">تایید و ورود</span>
                    <span class="btn-loader"></span>
                </button>
            </form>
            
            <div class="otp-countdown">
                <div id="countdown" class="countdown-timer">
                    <svg class="countdown-circle" viewBox="0 0 36 36">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle-fill" stroke-dasharray="100, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="countdown-text">02:00</span>
                </div>
                <button id="resend-otp" class="btn-resend" style="display:none;">
                    ارسال مجدد کد
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 4v6h6"></path>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <div id="message" class="otp-message"></div>
    </div>
    
    <div class="otp-design-elements">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
    </div>
</div>

<?php get_footer(); ?>