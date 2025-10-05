class ConsultantDashboardAdmin {
    constructor() {
        this.currentRequestId = null;
        this.originalData = null;
        this.currentTab = 'pending';
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
            tab.addEventListener('click', () => {
                // غیرفعال کردن همه تب‌ها
                tabs.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.consultant-tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });

                // فعال کردن تب انتخاب شده
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

        // استفاده از کلاس جدید برای close button
        this.closeBtn = this.modal.querySelector('.consultant-close-modal');
        this.editorContainer = document.getElementById('consultation-editor');

        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.closeModal());
        } else {
            console.error('Close button not found in modal');
        }

        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.closeModal();
        });
    }

    setupEventListeners() {
        // دکمه‌های بررسی
        document.addEventListener('click', (e) => {
            if (e.target.closest('.review-button')) {
                const requestId = e.target.closest('.review-button').getAttribute('data-request-id');
                this.openReviewModal(requestId);
            }
        });

        // دکمه‌های اقدام در مودال
        document.addEventListener('click', (e) => {
            if (e.target.id === 'save-draft-btn' || e.target.closest('#save-draft-btn')) {
                this.saveReview('save_draft');
            }
            if (e.target.id === 'approve-btn' || e.target.closest('#approve-btn')) {
                this.saveReview('approve');
            }
            if (e.target.id === 'reject-btn' || e.target.closest('#reject-btn')) {
                this.saveReview('reject');
            }
        });
    }

    async openReviewModal(requestId) {
        console.log('Opening modal for request:', requestId);
        this.currentRequestId = requestId;
        
        try {
            // نمایش لودینگ
            if (this.editorContainer) {
                this.editorContainer.innerHTML = '<div class="consultant-loading"><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری...</div>';
            }
            
            if (this.modal) {
                this.modal.style.display = 'block';
            }

            // دریافت داده‌های درخواست
            const response = await this.fetchRequestData(requestId);
            console.log('Response received:', response);
            
            if (response.success) {
                this.originalData = response.data;
                this.renderEditor();
            } else {
                this.showError(response.data || 'خطا در دریافت داده‌ها');
            }
        } catch (error) {
            console.error('Error in openReviewModal:', error);
            this.showError('خطا در ارتباط با سرور: ' + error.message);
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
            
            const data = await response.json();
            return data;
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
    }
    
    // در متد renderEditor، جایگزین کردن کد قدیمی با ویرایشگر جدید
    renderEditor() {
        if (!this.originalData || !this.editorContainer) {
            this.showError('داده‌ها برای نمایش موجود نیست');
            return;
        }
    
        // ایجاد ویرایشگر جدید
        this.dietEditor = new ConsultantDietEditor('consultation-editor');
        this.dietEditor.init(this.originalData, this.currentRequestId);
    
        // تنظیم رویدادهای دکمه‌های ذخیره
        this.setupSaveButtons();
    }
    
    setupSaveButtons() {
        // دکمه ذخیره پیش‌نویس
        const saveDraftBtn = document.getElementById('save-draft-btn');
        if (saveDraftBtn) {
            saveDraftBtn.addEventListener('click', () => this.saveReview('save_draft'));
        }
    
        // دکمه تایید نهایی
        const approveBtn = document.getElementById('approve-btn');
        if (approveBtn) {
            approveBtn.addEventListener('click', () => this.saveReview('approve'));
        }
    
        // دکمه رد درخواست
        const rejectBtn = document.getElementById('reject-btn');
        if (rejectBtn) {
            rejectBtn.addEventListener('click', () => this.saveReview('reject'));
        }
    }
    
// در متد saveReview
async saveReview(action) {
    let finalDietData;
    const consultantNotes = document.getElementById('consultant-notes')?.value || '';

    // دریافت داده‌های ویرایش شده از ویرایشگر
    if (this.dietEditor && typeof this.dietEditor.getFinalDietData === 'function') {
        finalDietData = this.dietEditor.getFinalDietData();
    } else {
        // روش fallback - بررسی ویرایشگر JSON
        const jsonEditor = document.getElementById('diet-json-editor');
        if (jsonEditor) {
            finalDietData = jsonEditor.value;
        } else {
            // روش fallback - بررسی ویرایشگر متن ساده
            const textEditor = document.getElementById('diet-text-editor');
            if (textEditor) {
                finalDietData = textEditor.value;
            } else {
                this.showError('امکان ذخیره‌سازی وجود ندارد. لطفاً صفحه را رفرش کنید.');
                return;
            }
        }
    }

    // اعتبارسنجی داده‌ها
    if (!finalDietData || finalDietData.trim() === '') {
        this.showError('داده‌های رژیم غذایی نمی‌تواند خالی باشد');
        return;
    }

    try {
        // اعتبارسنجی JSON
        JSON.parse(finalDietData);
    } catch (e) {
        this.showError('فرمت JSON نامعتبر است: ' + e.message);
        return;
    }

    try {
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

        const result = await response.json();

        if (result.success) {
            this.showSuccess('تغییرات با موفقیت ذخیره شد.');
            setTimeout(() => {
                this.closeModal();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }, 1500);
        } else {
            this.showError(result.data || 'خطا در ذخیره تغییرات');
        }
    } catch (error) {
        this.showError('خطا در ارتباط با سرور: ' + error.message);
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

    renderSimplePreview(content) {
        try {
            const parsed = JSON.parse(content);
            return '<pre>' + JSON.stringify(parsed, null, 2) + '</pre>';
        } catch (e) {
            return '<div style="white-space: pre-wrap;">' + this.escapeHtml(content) + '</div>';
        }
    }

    setupEditorTabs() {
        const tabs = this.editorContainer.querySelectorAll('.editor-tab');
        const panes = this.editorContainer.querySelectorAll('.editor-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                const pane = document.getElementById(`${tabId}-pane`);
                if (pane) {
                    pane.classList.add('active');
                }
            });
        });
    }

    setupEditorEvents() {
        const loadBtn = document.getElementById('load-original-btn');
        if (loadBtn) {
            loadBtn.addEventListener('click', () => {
                const editor = document.getElementById('diet-editor');
                if (editor) {
                    editor.value = this.originalData.original_data.ai_response;
                    this.updatePreview();
                }
            });
        }

        const editor = document.getElementById('diet-editor');
        if (editor) {
            editor.addEventListener('input', () => {
                this.updatePreview();
            });
        }
    }

    updatePreview() {
        const editor = document.getElementById('diet-editor');
        const previewContainer = document.getElementById('diet-preview');
        
        if (editor && previewContainer) {
            previewContainer.innerHTML = this.renderSimplePreview(editor.value);
        }
    }

    async saveReview(action) {
        let finalDietData;
        const consultantNotes = document.getElementById('consultant-notes')?.value || '';
    
        console.log('Starting save review process...', action);
    
        // روش اول: استفاده از ویرایشگر جدید
        if (this.dietEditor && typeof this.dietEditor.getFinalDietData === 'function') {
            console.log('Using diet editor for data');
            finalDietData = this.dietEditor.getFinalDietData();
        } 
        // روش دوم: بررسی ویرایشگر JSON
        else {
            const jsonEditor = document.getElementById('diet-json-editor');
            if (jsonEditor) {
                console.log('Using JSON editor for data');
                finalDietData = jsonEditor.value;
            } 
            // روش سوم: بررسی ویرایشگر متن ساده
            else {
                const textEditor = document.getElementById('diet-text-editor');
                if (textEditor) {
                    console.log('Using text editor for data');
                    finalDietData = textEditor.value;
                } else {
                    console.error('No editor found for diet data');
                    this.showError('امکان ذخیره‌سازی وجود ندارد. لطفاً صفحه را رفرش کنید.');
                    return;
                }
            }
        }
    
        console.log('Final diet data length:', finalDietData?.length);
        console.log('Consultant notes:', consultantNotes);
    
        // اعتبارسنجی داده‌ها
        if (!finalDietData || finalDietData.trim() === '') {
            this.showError('داده‌های رژیم غذایی نمی‌تواند خالی باشد');
            return;
        }
    
        // اعتبارسنجی JSON (اگر داده JSON است)
        if (finalDietData.trim().startsWith('{') || finalDietData.trim().startsWith('[')) {
            try {
                JSON.parse(finalDietData);
                console.log('JSON validation passed');
            } catch (e) {
                console.error('JSON validation failed:', e);
                this.showError('فرمت JSON نامعتبر است: ' + e.message);
                return;
            }
        }
    
        // نمایش وضعیت در حال ذخیره
        this.showMessage('در حال ذخیره‌سازی...', 'info');
    
        try {
            console.log('Sending request to server...');
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
    
            console.log('Response received:', response.status);
    
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
                this.showError(result.data || 'خطا در ذخیره تغییرات');
            }
        } catch (error) {
            console.error('Save review error:', error);
            this.showError('خطا در ارتباط با سرور: ' + error.message);
        }
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
}

// راه‌اندازی زمانی که DOM لود شد
document.addEventListener('DOMContentLoaded', () => {
    new ConsultantDashboardAdmin();
});