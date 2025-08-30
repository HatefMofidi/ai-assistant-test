<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/footer.php-->
</main>

<footer class="ai-footer">
    <div class="ai-footer-container">
        <div class="ai-footer-logo">
            <a href="<?php echo home_url('/'); ?>">
                <?php _e('دستیار هوش مصنوعی', 'ai-assistant'); ?>
            </a>
        </div>
        
        <div class="ai-footer-links">
            <div class="ai-footer-column">
                <h3><?php _e('لینک‌های سریع', 'ai-assistant'); ?></h3>
                <a href="<?php echo home_url('/ai-services'); ?>"><?php _e('سرویس‌ها', 'ai-assistant'); ?></a>
                <a href="<?php echo home_url('/pricing'); ?>"><?php _e('تعرفه‌ها', 'ai-assistant'); ?></a>
                <a href="<?php echo home_url('/faq'); ?>"><?php _e('سوالات متداول', 'ai-assistant'); ?></a>
            </div>
            
            <div class="ai-footer-column">
                <h3><?php _e('حساب کاربری', 'ai-assistant'); ?></h3>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/ai-dashboard'); ?>"><?php _e('داشبورد', 'ai-assistant'); ?></a>
                    <a href="<?php echo home_url('/profile'); ?>"><?php _e('پروفایل', 'ai-assistant'); ?></a>
                    <a href="<?php echo wp_logout_url(); ?>"><?php _e('خروج', 'ai-assistant'); ?></a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>"><?php _e('ورود', 'ai-assistant'); ?></a>
                    <a href="<?php echo wp_registration_url(); ?>"><?php _e('ثبت نام', 'ai-assistant'); ?></a>
                <?php endif; ?>
            </div>
            
            <div class="ai-footer-column">
                <h3><?php _e('تماس با ما', 'ai-assistant'); ?></h3>
                <a href="mailto:info@example.com">info@example.com</a>
                <a href="tel:+989123456789">۰۹۱۲۳۴۵۶۷۸۹</a>
                <a href="<?php echo home_url('/contact'); ?>"><?php _e('فرم تماس', 'ai-assistant'); ?></a>
            </div>
        </div>
    </div>
    
    <div class="ai-footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php _e('کلیه حقوق برای دستیار هوش مصنوعی محفوظ است.', 'ai-assistant'); ?></p>
    </div>
    
    <?php wp_footer(); ?>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // منوی موبایل
    const mobileMenuToggle = document.querySelector('.ai-mobile-menu-toggle');
    const navWrapper = document.querySelector('.ai-nav-wrapper');
    
    if (mobileMenuToggle && navWrapper) {
        mobileMenuToggle.addEventListener('click', function() {
            navWrapper.style.display = navWrapper.style.display === 'flex' ? 'none' : 'flex';
        });
        
        // بستن منو وقتی صفحه بزرگ می‌شود
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navWrapper.style.display = 'flex';
            } else {
                navWrapper.style.display = 'none';
            }
        });
    }
});
</script>

</body>
</html>