<div class="ai-service-container ai-chat-container">
    <?php if (!is_user_logged_in()): ?>
        <div class="ai-card ai-notice">
            <p><?php _e('برای استفاده از سرویس چت باید وارد حساب کاربری خود شوید.', 'ai-assistant'); ?></p>
            <a href="<?php echo wp_login_url(); ?>" class="ai-button">
                <?php _e('ورود به حساب کاربری', 'ai-assistant'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="ai-card">
            <h2><?php _e('چت هوش مصنوعی', 'ai-assistant'); ?></h2>
            <p class="ai-service-price">
                <?php _e('هزینه هر درخواست:', 'ai-assistant'); ?> ۱۰۰۰ <?php _e('تومان', 'ai-assistant'); ?>
            </p>
            
            <form id="ai-chat-form" class="ai-service-form">
                <div class="ai-form-group">
                    <label for="ai-chat-input"><?php _e('پرسش خود را وارد کنید:', 'ai-assistant'); ?></label>
                    <textarea id="ai-chat-input" name="input" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="ai-button">
                    <?php _e('ارسال درخواست', 'ai-assistant'); ?>
                </button>
            </form>
        </div>
        
        <div id="ai-chat-result" class="ai-card ai-service-result" style="display:none;">
            <h3><?php _e('پاسخ:', 'ai-assistant'); ?></h3>
            <div class="ai-response-content"></div>
        </div>
    <?php endif; ?>
</div>