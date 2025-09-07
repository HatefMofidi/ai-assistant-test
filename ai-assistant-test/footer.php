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
    const mobileMenuToggle = document.querySelector('.ai-mobile-menu-toggle');
    const navWrapper = document.querySelector('.ai-nav-wrapper');
    const body = document.body;
    const header = document.querySelector('.ai-header');
    
    if (mobileMenuToggle && navWrapper && header) {
        let isMenuOpen = false;
        let resizeTimeout;
        
        // تنظیم اولیه برای حالت موبایل
        const initMobileMenu = () => {
            if (window.innerWidth <= 768) {
                navWrapper.style.display = 'none';
                navWrapper.style.opacity = '0';
                navWrapper.style.transform = 'translateY(-20px)';
                navWrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                navWrapper.style.position = 'absolute';
                navWrapper.style.top = '100%';
                navWrapper.style.left = '0';
                navWrapper.style.width = '100%';
                navWrapper.style.background = '#fff';
                navWrapper.style.zIndex = '999';
                navWrapper.style.padding = '20px';
                navWrapper.style.boxSizing = 'border-box';
                navWrapper.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
                navWrapper.style.maxHeight = 'calc(100vh - 100%)';
                navWrapper.style.overflowY = 'auto';
            }else {
                // حالت دسکتاپ
                navWrapper.style.display = 'flex';
                navWrapper.style.opacity = '1';
                navWrapper.style.transform = 'none';
                navWrapper.style.transition = 'none';
                navWrapper.style.position = 'static';
                navWrapper.style.width = 'auto';
                navWrapper.style.background = 'none';
                navWrapper.style.backdropFilter = 'none';
                navWrapper.style.WebkitBackdropFilter = 'none';
                navWrapper.style.zIndex = 'auto';
                navWrapper.style.padding = '0';
                navWrapper.style.boxShadow = 'none';
                navWrapper.style.border = 'none';
                navWrapper.style.borderRadius = '0';
                navWrapper.style.overflowY = 'visible';
                navWrapper.style.maxHeight = 'none';
                mobileMenuToggle.innerHTML = '<span class="dashicons dashicons-menu"></span>';
            } 
        };
        
        // فراخوانی اولیه
        initMobileMenu();
        
        // تابع باز کردن منو
        const openMenu = () => {
            navWrapper.style.display = 'flex';
            
            setTimeout(() => {
                navWrapper.style.opacity = '1';
                navWrapper.style.transform = 'translateY(0)';
            }, 10);
            
            mobileMenuToggle.innerHTML = '<span class="dashicons dashicons-no"></span>';
            isMenuOpen = true;
            
            // اضافه کردن کلاس برای حالت باز
            document.documentElement.classList.add('menu-open');
        };
        
        // تابع بستن منو
        const closeMenu = () => {
            navWrapper.style.opacity = '0';
            navWrapper.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                navWrapper.style.display = 'none';
            }, 300);
            
            mobileMenuToggle.innerHTML = '<span class="dashicons dashicons-menu"></span>';
            isMenuOpen = false;
            
            // حذف کلاس برای حالت باز
            document.documentElement.classList.remove('menu-open');
        };
        
        // مدیریت کلیک روی دکمه منو
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation(); // جلوگیری از انتشار event
            
            if (window.innerWidth <= 768) {
                if (isMenuOpen) {
                    closeMenu();
                } else {
                    openMenu();
                }
            }
        });
        
        // مدیریت تغییر سایز صفحه
        window.addEventListener('resize', function() {
            // استفاده از debounce برای جلوگیری از فراخوانی مکرر
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                initMobileMenu();
                isMenuOpen = false;
                mobileMenuToggle.innerHTML = '<span class="dashicons dashicons-menu"></span>';
                document.documentElement.classList.remove('menu-open');
            }, 100);
        });
        
        // بستن منو با کلیک خارج از منو
        document.addEventListener('click', function(e) {
            if (isMenuOpen && window.innerWidth <= 768 && 
                !navWrapper.contains(e.target) && 
                !mobileMenuToggle.contains(e.target)) {
                closeMenu();
            }
        });
        
        // جلوگیری از بستن منو هنگام کلیک روی خود منو
        navWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>

</body>
</html>