class ConsultantDashboardAdmin {
    constructor() {
        this.currentRequestId = null;
        this.originalData = null;
        this.currentTab = 'pending';
        this.isSaving = false; // فلگ برای جلوگیری از ذخیره سازی تکراری
        this.eventListeners = new Set(); // مدیریت لیستنرها
        this.init();
    }

    init() {
        console.log('ConsultantDashboardAdmin initialized');
        this.setupTabs();
        this.setupModal();
        this.setupEventListeners();
    }

    setupTabs() {
        const tabs = document.querySelectorAll('.consultant-tab-button');
        
        tabs.forEach(tab => {
            this.addEventListener(tab, 'click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.consultant-tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });

                tab.classList.add('active');
                this.currentTab = tab.dataset.tab;
                const targetPane = document.getElementById(`${this.currentTab}-tab`);
                if (targetPane) {
                    targetPane.classList.add('active');
                }
            });
        });
    }

    setupModal() {
        this.modal = document.getElementById('consultation-modal');
        if (!this.modal) {
            console.error('Modal element not found');
            return;
        }

        this.closeBtn = this.modal.querySelector('.consultant-close-modal');
        this.editorContainer = document.getElementById('consultation-editor');

        if (this.closeBtn) {
            this.addEventListener(this.closeBtn, 'click', () => this.closeModal());
        } else {
            console.error('Close button not found in modal');
        }

        this.addEventListener(this.modal, 'click', (e) => {
            if (e.target === this.modal) this.closeModal();
        });
    }

    setupEventListeners() {
        // دکمه‌های بررسی - فقط یک بار ثبت می‌شود
        this.addEventListener(document, 'click', (e) => {
            const reviewButton = e.target.closest('.review-button');
            if (reviewButton) {
                const requestId = reviewButton.getAttribute('data-request-id');
                this.openReviewModal(requestId);
            }
        });

        // دکمه‌های اقدام در مودال - مدیریت متمرکز
        this.addEventListener(document, 'click', (e) => {
            if (this.isSaving) return; // جلوگیری از کلیک مجدد هنگام ذخیره
            
            if (e.target.id === 'save-draft-btn' || e.target.closest('#save-draft-btn')) {
                this.saveReview('save_draft');
            }
            else if (e.target.id === 'approve-btn' || e.target.closest('#approve-btn')) {
                this.saveReview('approve');
            }
            else if (e.target.id === 'reject-btn' || e.target.closest('#reject-btn')) {
                this.saveReview('reject');
            }
        });
    }

    // متد برای مدیریت متمرکز event listeners
    addEventListener(element, event, handler) {
        element.addEventListener(event, handler);
        this.eventListeners.add({ element, event, handler });
    }

    // پاک کردن همه event listeners هنگام بستن
    cleanupEventListeners() {
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners.clear();
    }

    async openReviewModal(requestId) {
        if (this.isSaving) return; // جلوگیری از باز کردن مودال جدید هنگام ذخیره
        
        console.log('Opening modal for request:', requestId);
        this.currentRequestId = requestId;
        
        try {
            // نمایش لودینگ
            if (this.editorContainer) {
                this.editorContainer.innerHTML = '<div class="consultant-loading"><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری...</div>';
            }
            
            if (this.modal) {
                this.modal.style.display = 'block';
                this.disableModalButtons(true); // غیرفعال کردن دکمه‌ها هنگام لودینگ
            }

            const response = await this.fetchRequestData(requestId);
            console.log('Response received:', response);
            
            if (response.success) {
                this.originalData = response.data;
                this.renderEditor();
                this.disableModalButtons(false); // فعال کردن دکمه‌ها پس از لود
            } else {
                this.showError(response.data || 'خطا در دریافت داده‌ها');
                this.disableModalButtons(false);
            }
        } catch (error) {
            console.error('Error in openReviewModal:', error);
            this.showError('خطا در ارتباط با سرور: ' + error.message);
            this.disableModalButtons(false);
        }
    }

    async fetchRequestData(requestId) {
        try {
            const response = await fetch(consultant_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_consultation_data',
                    request_id: requestId,
                    nonce: consultant_ajax.nonce
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error in fetchRequestData:', error);
            throw error;
        }
    }

    closeModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
        }
        this.currentRequestId = null;
        this.originalData = null;
        this.dietEditor = null;
        this.isSaving = false;
        
        // پاک کردن محتوای ادیتور
        if (this.editorContainer) {
            this.editorContainer.innerHTML = '';
        }
    }

    // غیرفعال/فعال کردن دکمه‌های مودال
    disableModalButtons(disabled) {
        const buttons = ['save-draft-btn', 'approve-btn', 'reject-btn'];
        buttons.forEach(btnId => {
            const button = document.getElementById(btnId);
            if (button) {
                button.disabled = disabled;
                if (disabled) {
                    button.classList.add('disabled');
                } else {
                    button.classList.remove('disabled');
                }
            }
        });
    }

    renderEditor() {
        if (!this.originalData || !this.editorContainer) {
            this.showError('داده‌ها برای نمایش موجود نیست');
            return;
        }
    
        // ایجاد ویرایشگر جدید
        this.dietEditor = new ConsultantDietEditor('consultation-editor');
        this.dietEditor.init(this.originalData, this.currentRequestId);
    }

    async saveReview(action) {
        // جلوگیری از اجرای همزمان چندین ذخیره سازی
        if (this.isSaving) {
            console.log('Save operation already in progress, skipping...');
            return;
        }

        this.isSaving = true;
        this.disableModalButtons(true);

        console.log('Starting save review process...', action);
        
        let finalDietData;
        const consultantNotes = document.getElementById('consultant-notes')?.value || '';

        try {
            // دریافت داده‌های ویرایش شده از ویرایشگر
            if (this.dietEditor && typeof this.dietEditor.getFinalDietData === 'function') {
                finalDietData = this.dietEditor.getFinalDietData();
            } else {
                // روش fallback
                const jsonEditor = document.getElementById('diet-json-editor');
                if (jsonEditor) {
                    finalDietData = jsonEditor.value;
                } else {
                    const textEditor = document.getElementById('diet-text-editor');
                    if (textEditor) {
                        finalDietData = textEditor.value;
                    } else {
                        throw new Error('امکان ذخیره‌سازی وجود ندارد. لطفاً صفحه را رفرش کنید.');
                    }
                }
            }

            // اعتبارسنجی داده‌ها
            if (!finalDietData || finalDietData.trim() === '') {
                throw new Error('داده‌های رژیم غذایی نمی‌تواند خالی باشد');
            }

            // اعتبارسنجی JSON
            if (finalDietData.trim().startsWith('{') || finalDietData.trim().startsWith('[')) {
                try {
                    JSON.parse(finalDietData);
                } catch (e) {
                    throw new Error('فرمت JSON نامعتبر است: ' + e.message);
                }
            }

            // نمایش وضعیت در حال ذخیره
            this.showMessage('در حال ذخیره‌سازی...', 'info');

            const response = await fetch(consultant_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'submit_consultation_review',
                    request_id: this.currentRequestId,
                    action_type: action,
                    final_diet_data: finalDietData,
                    consultant_notes: consultantNotes,
                    nonce: consultant_ajax.nonce
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Server response:', result);

            if (result.success) {
                this.showSuccess('تغییرات با موفقیت ذخیره شد.');
                setTimeout(() => {
                    this.closeModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }, 1500);
            } else {
                throw new Error(result.data || 'خطا در ذخیره تغییرات');
            }

        } catch (error) {
            console.error('Save review error:', error);
            this.showError(error.message);
        } finally {
            // در هر حالت، وضعیت ذخیره سازی را بازنشانی کن
            this.isSaving = false;
            this.disableModalButtons(false);
        }
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    showError(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        // حذف پیام قبلی اگر وجود دارد
        const existingMessage = document.querySelector('.consultant-admin-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `consultant-admin-message ${type}`;
        messageDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
            ${message}
        `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }

    // تخریب کلاس و پاکسازی منابع
    destroy() {
        this.cleanupEventListeners();
        this.closeModal();
    }
}

// راه‌اندازی زمانی که DOM لود شد
document.addEventListener('DOMContentLoaded', () => {
    window.consultantDashboard = new ConsultantDashboardAdmin();
});