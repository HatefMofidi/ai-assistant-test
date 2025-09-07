<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/functions/farsi-num-functions.php
 * Functions for AI Assistant Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// اضافه کردن اسکریپت تبدیل اعداد (نسخه بهینه‌تر)
function add_persian_numbers_script() {
    ?>
    <script>
    (function($) {
        // تابع تبدیل اعداد به فارسی
        function convertToPersianNumbers(input) {
            var persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return input.replace(/\d/g, function(m) {
                return persianNumbers[parseInt(m)];
            });
        }
        
        // تبدیل اعداد در کل بدنه صفحه به جز step-counter و style
        function convertAllNumbers() {
            // همه المان‌های متنی به جز آنهایی که در step-counter یا style هستند
            $('body').find('*').not('#step-counter, #step-counter *, style, style *').contents().each(function() {
                // همچنین از تبدیل محتوای المان‌های style جلوگیری کنید
                if (this.nodeType === 3 && this.textContent.trim() !== '' && 
                    this.parentNode.nodeName !== 'STYLE') {
                    this.textContent = convertToPersianNumbers(this.textContent);
                }
            });
        }
        
        // اجرا پس از لود کامل صفحه
        $(document).ready(function() {
            convertAllNumbers();
        });
        
        // برای محتوای پویا (مثلاً در AJAX)
        $(document).ajaxComplete(function() {
            setTimeout(convertAllNumbers, 100);
        });
        
    })(jQuery);
    </script>
    <?php
}
add_action('wp_footer', 'add_persian_numbers_script');