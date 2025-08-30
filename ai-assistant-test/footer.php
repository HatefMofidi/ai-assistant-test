<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/footer.php-->
</main>

<footer class="simple-footer">
    <div class="footer-content">
        <!-- نماد اعتماد -->


        <!-- شبکه‌های اجتماعی -->
        <div class="footer-social">
            <a href="https://instagram.com/yourusername" class="social-icon instagram" target="_blank" rel="noopener" title="اینستاگرام">
                <span class="dashicons dashicons-instagram"></span>
            </a>
            
            <a href="https://t.me/yourusername" class="social-icon telegram" target="_blank" rel="noopener" title="تلگرام">
                <span class="dashicons dashicons-phone"></span>
            </a>
            
            <a href="https://twitter.com/yourusername" class="social-icon twitter" target="_blank" rel="noopener" title="توییتر">
                <span class="dashicons dashicons-twitter"></span>
            </a>
            
            <a href="https://linkedin.com/company/yourcompany" class="social-icon linkedin" target="_blank" rel="noopener" title="لینکدین">
                <span class="dashicons dashicons-linkedin"></span>
            </a>
        </div>

        <!-- کپی رایت -->
        <div class="copyright">
            <p>© ۲۰۲۴ <?php bloginfo('name'); ?> - تمام حقوق محفوظ است</p>
        </div>
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