// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/components/aidastyar-loader.js
class AiDastyarLoader {
    constructor(options = {}) {
        // Singleton pattern
        if (window.AiDastyarLoader.instance) {
            return window.AiDastyarLoader.instance;
        }

        this.defaultOptions = {
            message: 'در حال پردازش درخواست...',
            theme: 'light',
            size: 'medium',
            position: 'center',
            closable: false,
            overlay: true,
            autoHide: null,
            persistent: false,
            redirectUrl: null, 
            redirectDelay: 1500,
            redirectOnClose: null,
            onShow: null,
            onHide: null,
            onRedirect: null,
            onClose: null 
        };

        this.options = { ...this.defaultOptions, ...options };
        this.isShowing = false;
        this.redirectTimeout = null;
        
        this.init();
        window.AiDastyarLoader.instance = this;
    }

    init() {
        this.createLoader();
        this.injectStyles();
        this.bindEvents();
    }

    createLoader() {
        this.destroy();

        this.loader = document.createElement('div');
        this.loader.className = `aidastyar-loader ${this.options.theme} ${this.options.size}`;
        this.loader.innerHTML = this.getTemplate();
        
        document.body.appendChild(this.loader);
    }

    getTemplate() {
        const closeBtn = this.options.closable ? 
            `<button class="loader-close" aria-label="بستن">×</button>` : '';

        return `
            ${this.options.overlay ? '<div class="loader-overlay"></div>' : ''}
            <div class="loader-content">
                ${closeBtn}
                <div class="brand-text">
                    <span class="letter">A</span>
                    <span class="letter">i</span>
                    <span class="letter">D</span>
                    <span class="letter">A</span>
                    <span class="letter">S</span>
                    <span class="letter">T</span>
                    <span class="letter">Y</span>
                    <span class="letter">A</span>
                    <span class="letter">R</span>
                </div>
                <div class="loader-spinner">
                    <div class="spinner"></div>
                </div>
                <div class="loader-message">${this.options.message}</div>
                ${this.options.redirectUrl ? '<div class="redirect-countdown"></div>' : ''}
            </div>
        `;
    }

    show() {
        if (this.isShowing) return;

        this.loader.classList.add('active');
        this.isShowing = true;

        // مدیریت persistent
        if (this.options.persistent) {
            document.body.style.overflow = 'hidden';
        }

        // Callback اجرای
        if (typeof this.options.onShow === 'function') {
            this.options.onShow();
        }

        // اتوماتیک پنهان شدن (فقط اگر persistent نباشد)
        if (this.options.autoHide && !this.options.persistent) {
            setTimeout(() => this.hide(), this.options.autoHide);
        }

        // تنظیم redirect
        if (this.options.redirectUrl) {
            this.setupRedirect();
        }
    }

    hide() {
        if (!this.isShowing) return;

        const redirectOnClose = this.options.redirectOnClose;
        const onCloseCallback = this.options.onClose;

        // پاک کردن timeoutهای فعال
        if (this.redirectTimeout) {
            clearTimeout(this.redirectTimeout);
            this.redirectTimeout = null;
        }

        this.loader.classList.remove('active');
        this.isShowing = false;
        document.body.style.overflow = '';

        // اجرای callbackهای مربوط به بستن
        if (typeof this.options.onHide === 'function') {
            this.options.onHide();
        }

        if (typeof onCloseCallback === 'function') {
            onCloseCallback();
        }

        if (redirectOnClose) {
            setTimeout(() => {
                window.location.href = redirectOnClose;
            }, 150);
        }
    }

    // 🔥 جدید: متد redirect اختصاصی
    redirect(url, delay = 1500, message = 'در حال انتقال...') {
        this.update(message);
        this.options.redirectUrl = url;
        this.options.redirectDelay = delay;
        this.show();
        this.setupRedirect();
    }

    // 🔥 جدید: تنظیمات redirect
    setupRedirect() {
        if (!this.options.redirectUrl) return;

        const countdownEl = this.loader.querySelector('.redirect-countdown');
        let remaining = this.options.redirectDelay / 1000;

        if (countdownEl) {
            countdownEl.textContent = `انتقال در ${remaining} ثانیه...`;
        }

        const countdownInterval = setInterval(() => {
            remaining--;
            if (countdownEl && remaining > 0) {
                countdownEl.textContent = `انتقال در ${remaining} ثانیه...`;
            }
        }, 1000);

        this.redirectTimeout = setTimeout(() => {
            clearInterval(countdownInterval);
            
            // Callback اجرای
            if (typeof this.options.onRedirect === 'function') {
                this.options.onRedirect(this.options.redirectUrl);
            }
            
            window.location.href = this.options.redirectUrl;
        }, this.options.redirectDelay);
    }

    update(message) {
        const messageEl = this.loader.querySelector('.loader-message');
        if (messageEl) {
            messageEl.textContent = message;
        }
    }

    // 🔥 جدید: تغییر options در حین اجرا
    setOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        
        if (this.isShowing) {
            this.destroy();
            this.createLoader();
            this.show();
        }
    }

    destroy() {
        if (this.redirectTimeout) {
            clearTimeout(this.redirectTimeout);
            this.redirectTimeout = null;
        }

        if (this.loader && this.loader.parentNode) {
            this.loader.parentNode.removeChild(this.loader);
        }
        this.isShowing = false;
        document.body.style.overflow = '';
    }

    bindEvents() {
        // بستن با کلید ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.options.closable && this.isShowing) {
                this.hide();
            }
        });

        // کلیک روی overlay برای بستن
        document.addEventListener('click', (e) => {
            if (this.isShowing && this.options.closable && e.target.classList.contains('loader-overlay')) {
                this.hide();
            }
        });

        // دکمه بستن
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('loader-close')) {
                this.hide();
            }
        });
    }

    injectStyles() {
        if (document.getElementById('aidastyar-loader-styles')) return;

        const styles = `
            .aidastyar-loader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                display: none;
            }
            
            .aidastyar-loader.active {
                display: block;
            }
            
            .loader-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(2px);
            }
            
            .loader-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                text-align: center;
                min-width: 200px;
            }
            
            .aidastyar-loader.dark .loader-content {
                background: #2d3748;
                color: white;
            }
            
            .loader-close {
                position: absolute;
                top: 10px;
                left: 10px;
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #6b7280;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .brand-text {
                font-size: 2rem;
                color: #212529;
                margin-bottom: 25px;
                letter-spacing: -5px;
                direction: ltr;
                font-family: BordeauxBlack, sans-serif;
            }     
            
            .letter {
                display: inline-block;
                opacity: 0;
                transform: translateY(20px);
                animation: letter-appear 0.4s forwards;
            }
            
            .letter:nth-child(1) { animation-delay: 0.1s; color: #00857a; }
            .letter:nth-child(2) { animation-delay: 0.2s; }
            .letter:nth-child(3) { animation-delay: 0.3s; color: #00857a; }
            .letter:nth-child(4) { animation-delay: 0.4s; }
            .letter:nth-child(5) { animation-delay: 0.5s; }
            .letter:nth-child(6) { animation-delay: 0.6s; }
            .letter:nth-child(7) { animation-delay: 0.7s; color: #00857a; }
            .letter:nth-child(8) { animation-delay: 0.8s; }
            .letter:nth-child(9) { animation-delay: 0.9s; }
            
            @keyframes letter-appear {
                to {
                  opacity: 1;
                  transform: translateY(0);
                }
            }
            
            .loader-close:hover {
                color: #ef4444;
            }
            
            .loader-spinner {
                margin-bottom: 1rem;
            }
            
            .spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #00857a;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto;
            }
            
            .aidastyar-loader.dark .spinner {
                border: 4px solid #4b5563;
                border-top: 4px solid #10b981;
            }
            
            .loader-message {
                color: #4b5563;
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }
            
            .aidastyar-loader.dark .loader-message {
                color: #e5e7eb;
            }
            
            .redirect-countdown {
                color: #6b7280;
                font-size: 0.8rem;
                margin-top: 0.5rem;
            }
            
            .aidastyar-loader.dark .redirect-countdown {
                color: #9ca3af;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* سایزهای مختلف */
            .aidastyar-loader.small .loader-content {
                padding: 1rem;
                min-width: 150px;
            }
            
            .aidastyar-loader.small .spinner {
                width: 30px;
                height: 30px;
            }
            
            .aidastyar-loader.large .loader-content {
                padding: 3rem;
                min-width: 300px;
            }
            
            .aidastyar-loader.large .spinner {
                width: 60px;
                height: 60px;
            }
        `;

        const styleSheet = document.createElement('style');
        styleSheet.id = 'aidastyar-loader-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
}

// ایجاد instance گلوبال
window.AiDastyarLoader = AiDastyarLoader;