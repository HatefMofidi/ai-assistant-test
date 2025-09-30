class ConsultantDashboard {
    constructor() {
        this.currentRequestId = null;
        this.originalData = null;
        this.init();
    }

    init() {
        console.log('ConsultantDashboard initialized');
        this.setupTabs();
        this.setupModal();
        this.setupEventListeners();
    }

    setupTabs() {
        const tabs = document.querySelectorAll('.consultant-tabs .tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // غیرفعال کردن همه تب‌ها
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // فعال کردن تب انتخاب شده
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                const contentElement = document.getElementById(`${tabId}-content`);
                if (contentElement) {
                    contentElement.classList.add('active');
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

        this.closeBtn = this.modal.querySelector('.modal-close');
        this.editorContainer = document.getElementById('consultation-editor');

        this.closeBtn.addEventListener('click', () => this.closeModal());
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

        // دکمه‌های اقدام
        const saveDraftBtn = document.getElementById('save-draft-btn');
        const approveBtn = document.getElementById('approve-btn');
        const rejectBtn = document.getElementById('reject-btn');

        if (saveDraftBtn) saveDraftBtn.addEventListener('click', () => this.saveReview('save_draft'));
        if (approveBtn) approveBtn.addEventListener('click', () => this.saveReview('approve'));
        if (rejectBtn) rejectBtn.addEventListener('click', () => this.saveReview('reject'));
    }

    async openReviewModal(requestId) {
        console.log('Opening modal for request:', requestId);
        this.currentRequestId = requestId;
        
        try {
            // نمایش لودینگ
            this.editorContainer.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری...</div>';
            this.modal.style.display = 'block';

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
            console.log('Fetching data for request:', requestId);
            console.log('AJAX URL:', consultant_ajax.ajax_url);
            console.log('Nonce:', consultant_ajax.nonce);

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

            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Parsed data:', data);
            return data;
        } catch (error) {
            console.error('Error in fetchRequestData:', error);
            throw error;
        }
    }

    closeModal() {
        this.modal.style.display = 'none';
        this.currentRequestId = null;
        this.originalData = null;
    }

    renderEditor() {
        if (!this.originalData) {
            this.showError('داده‌ها برای نمایش موجود نیست');
            return;
        }

        const { original_data, consultation_data } = this.originalData;
        
        this.editorContainer.innerHTML = `
            <div class="editor-tabs">
                <div class="editor-tab active" data-tab="original">رژیم اصلی</div>
                <div class="editor-tab" data-tab="edit">ویرایش</div>
                <div class="editor-tab" data-tab="preview">پیش‌نمایش</div>
            </div>

            <div class="editor-content">
                <div class="editor-pane active" id="original-pane">
                    <h4><i class="fas fa-file-alt"></i> رژیم تولید شده توسط هوش مصنوعی</h4>
                    <div class="original-content">
                        <pre>${this.escapeHtml(original_data.ai_response)}</pre>
                    </div>
                </div>

                <div class="editor-pane" id="edit-pane">
                    <h4><i class="fas fa-edit"></i> ویرایش رژیم</h4>
                    <div class="edit-controls">
                        <button class="btn btn-small" id="load-original-btn">
                            <i class="fas fa-download"></i> بارگذاری از رژیم اصلی
                        </button>
                    </div>
                    <textarea id="diet-editor" style="width: 100%; height: 300px; font-family: monospace;" placeholder="محتوای رژیم غذایی را اینجا ویرایش کنید...">${this.escapeHtml(consultation_data.final_diet_data || original_data.ai_response)}</textarea>
                </div>

                <div class="editor-pane" id="preview-pane">
                    <h4><i class="fas fa-eye"></i> پیش‌نمایش رژیم نهایی</h4>
                    <div id="diet-preview" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; min-height: 200px;">
                        ${this.renderSimplePreview(consultation_data.final_diet_data || original_data.ai_response)}
                    </div>
                </div>
            </div>

            <div class="notes-section">
                <label for="consultant-notes">
                    <i class="fas fa-sticky-note"></i> یادداشت‌های مشاور:
                </label>
                <textarea id="consultant-notes" style="width: 100%; height: 100px;" placeholder="یادداشت‌ها و توضیحات خود را اینجا بنویسید...">${this.escapeHtml(consultation_data.consultant_notes || '')}</textarea>
            </div>
        `;

        this.setupEditorTabs();
        this.setupEditorEvents();
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
            // سعی کن JSON را parse کن
            const parsed = JSON.parse(content);
            return '<pre>' + JSON.stringify(parsed, null, 2) + '</pre>';
        } catch (e) {
            // اگر JSON نیست، متن ساده نمایش بده
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
        // دکمه بارگذاری از رژیم اصلی
        const loadBtn = document.getElementById('load-original-btn');
        if (loadBtn) {
            loadBtn.addEventListener('click', () => {
                document.getElementById('diet-editor').value = this.originalData.original_data.ai_response;
                this.updatePreview();
            });
        }

        // تغییرات در ادیتور
        const editor = document.getElementById('diet-editor');
        if (editor) {
            editor.addEventListener('input', () => {
                this.updatePreview();
            });
        }
    }

    updatePreview() {
        const editorContent = document.getElementById('diet-editor').value;
        const previewContainer = document.getElementById('diet-preview');
        
        if (previewContainer) {
            previewContainer.innerHTML = this.renderSimplePreview(editorContent);
        }
    }

    async saveReview(action) {
        const finalDietData = document.getElementById('diet-editor').value;
        const consultantNotes = document.getElementById('consultant-notes').value;

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
                this.closeModal();
                setTimeout(() => {
                    location.reload(); // رفرش صفحه برای بروزرسانی وضعیت
                }, 2000);
            } else {
                this.showError(result.data || 'خطا در ذخیره تغییرات');
            }
        } catch (error) {
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
        const existingMessage = document.querySelector('.consultant-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `consultant-message ${type}`;
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
    new ConsultantDashboard();
});