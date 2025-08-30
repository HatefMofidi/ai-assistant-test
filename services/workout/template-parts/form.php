<div class="ai-service-container ai-workout-container">
    <div class="ai-card">
        <h2><?php _e('برنامه بدنسازی هوش مصنوعی', 'ai-assistant'); ?></h2>
        <p class="ai-service-price">
            <?php _e('هزینه هر درخواست:', 'ai-assistant'); ?> ۱۵۰۰ <?php _e('تومان', 'ai-assistant'); ?>
        </p>
        
        <form id="ai-workout-form" class="ai-service-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="process_workout_request">
            
            <?php if (!is_user_logged_in()): ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
            <?php endif; ?>
            
            <div class="ai-form-group">
                <label for="ai-workout-input"><?php _e('اطلاعات خود را وارد کنید:', 'ai-assistant'); ?></label>
                <textarea id="ai-workout-input" name="input" rows="5" required 
                    placeholder="<?php _e('سن، وزن، قد، سطح تجربه، اهداف و امکانات ورزشی خود را وارد کنید...', 'ai-assistant'); ?>"><?php 
                    echo isset($_POST['input']) ? esc_textarea($_POST['input']) : ''; 
                ?></textarea>
            </div>
            
            
            
            <?php if (is_user_logged_in()): ?>
                <button type="submit" class="ai-button">
                    <?php _e('دریافت برنامه', 'ai-assistant'); ?>
                </button>
            <?php else: ?>
                <a href="javascript:void(0);" onclick="saveFormAndRedirect()" class="ai-button">
                    <?php _e('ورود و دریافت برنامه', 'ai-assistant'); ?>
                </a>
            <?php endif; ?>            
            
        </form>
    </div>
    
    <div id="ai-workout-result" class="ai-card ai-service-result" style="display:none;">
        <h3><?php _e('برنامه پیشنهادی:', 'ai-assistant'); ?></h3>
        <div class="ai-response-content"></div>
    </div>
</div>



<script>
function saveFormAndRedirect() {
    // ذخیره داده‌های فرم در localStorage
    const inputData = document.getElementById('ai-workout-input').value;
    localStorage.setItem('ai_workout_input', inputData);
    
    // هدایت به صفحه لاگین
    window.location.href = '<?php echo  wp_login_url(esc_url($_SERVER['REQUEST_URI'])); ?>';
}

// بازیابی داده‌ها وقتی صفحه لود شد
document.addEventListener('DOMContentLoaded', function() {
    const savedData = localStorage.getItem('ai_workout_input');
    if (savedData) {
        document.getElementById('ai-workout-input').value = savedData;
        localStorage.removeItem('ai_workout_input'); // پاک کردن داده ذخیره شده
    }
});
</script>