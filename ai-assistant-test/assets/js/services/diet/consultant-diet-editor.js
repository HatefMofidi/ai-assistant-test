class ConsultantDietEditor {
    constructor(containerId = 'consultation-editor') {
        this.container = document.getElementById(containerId);
        this.currentRequestId = null;
        this.originalData = null;
        this.isEditing = false;
        this.currentEditElement = null;
        this.currentData = null; // داده‌های جاری
        
        if (!this.container) {
            console.error(`Container with ID '${containerId}' not found`);
            return;
        }
    }

    init(data, requestId) {
        this.originalData = data;
        this.currentRequestId = requestId;
        // استخراج داده‌های رژیم
        const { original_data, consultation_data } = data;
        const dietData = consultation_data?.final_diet_data || original_data?.ai_response;
        
        try {
            this.currentData = typeof dietData === 'string' ? JSON.parse(dietData) : dietData;
        } catch (e) {
            console.error('Error parsing diet data:', e);
            this.currentData = null;
        }        
        this.render();
    }

    render() {
        if (!this.originalData || !this.currentData) {
            this.container.innerHTML = '<div class="consultant-loading">داده‌ها برای نمایش موجود نیست</div>';
            return;
        }


        this.container.innerHTML = this.renderStructuredEditor(this.currentData);
        this.setupEditEvents();
    }

    renderStructuredEditor(data) {
        return `
            <div class="consultant-editor-tabs">
                <div class="consultant-editor-tab active" data-tab="preview">
                    <i class="fas fa-eye"></i> پیش‌نمایش
                </div>
                <div class="consultant-editor-tab" data-tab="json">
                    <i class="fas fa-code"></i> ویرایش JSON
                </div>
            </div>

            <div class="consultant-editor-content">
                <div class="consultant-editor-pane active" id="preview-pane">
                    <div class="consultant-editor-actions">
                        <button class="consultant-btn consultant-btn-primary" id="expand-all-btn">
                            <i class="fas fa-expand"></i> باز کردن همه بخش‌ها
                        </button>
                        <button class="consultant-btn consultant-btn-secondary" id="collapse-all-btn">
                            <i class="fas fa-compress"></i> بستن همه بخش‌ها
                        </button>
                    </div>
                    <div class="consultant-diet-preview" id="consultant-diet-preview">
                        ${this.renderDietPlan(data)}
                    </div>
                </div>

                <div class="consultant-editor-pane" id="json-pane">
                    <div class="consultant-json-editor">
                        <h4><i class="fas fa-edit"></i> ویرایش مستقیم JSON</h4>
                        <textarea id="diet-json-editor" style="width: 100%; height: 400px; font-family: monospace;">${JSON.stringify(this.currentData, null, 2)}</textarea>
                        <div class="consultant-json-actions">
                            <button class="consultant-btn consultant-btn-primary" id="update-from-json-btn">
                                <i class="fas fa-sync"></i> بروزرسانی پیش‌نمایش از JSON
                            </button>
                            <button class="consultant-btn consultant-btn-secondary" id="reset-json-btn">
                                <i class="fas fa-undo"></i> بازنشانی به حالت اولیه
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="consultant-notes-section">
                <label for="consultant-notes">
                    <i class="fas fa-sticky-note"></i> یادداشت‌های مشاور:
                </label>
                <textarea id="consultant-notes" style="width: 100%; height: 100px;" placeholder="یادداشت‌ها و توضیحات خود را اینجا بنویسید...">${this.escapeHtml(this.originalData.consultation_data?.consultant_notes || '')}</textarea>
            </div>
        `;
    }

    renderSimpleTextEditor(text) {
        return `
            <div class="consultant-simple-editor">
                <h4><i class="fas fa-edit"></i> ویرایش متن رژیم</h4>
                <textarea id="diet-text-editor" style="width: 100%; height: 400px; font-family: monospace;">${this.escapeHtml(text)}</textarea>
                
                <div class="consultant-notes-section">
                    <label for="consultant-notes">
                        <i class="fas fa-sticky-note"></i> یادداشت‌های مشاور:
                    </label>
                    <textarea id="consultant-notes" style="width: 100%; height: 100px;" placeholder="یادداشت‌ها و توضیحات خود را اینجا بنویسید...">${this.escapeHtml(this.originalData.consultation_data?.consultant_notes || '')}</textarea>
                </div>
            </div>
        `;
    }

    renderDietPlan(data) {
        if (!data.sections || !Array.isArray(data.sections)) {
            return `<div class="consultant-debug-info">
                <h3>داده‌های خام:</h3>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            </div>`;
        }

        let html = '<div class="consultant-diet-plan">';
        
        data.sections.forEach((section, sectionIndex) => {
            html += this.renderSection(section, sectionIndex);
        });

        if (data.footer) {
            html += `<div class="consultant-footer">${this.escapeHtml(data.footer)}</div>`;
        }

        html += '</div>';
        return html;
    }

    renderSection(section, sectionIndex) {
        return `
            <div class="consultant-section" data-section-index="${sectionIndex}">
                <div class="consultant-section-header">
                    <h2>
                        <i class="fas fa-${this.getSectionIcon(section.title)}"></i>
                        <span class="editable-text" data-path="sections.${sectionIndex}.title">${this.escapeHtml(section.title || 'بخش بدون عنوان')}</span>
                    </h2>
                    <i class="fas fa-chevron-down consultant-accordion-icon"></i>
                </div>
                <div class="consultant-section-content">
                    ${this.renderSectionContent(section.content, sectionIndex)}
                </div>
            </div>
        `;
    }

    renderSectionContent(content, sectionIndex) {
        if (!content) return '<p>محتوایی موجود نیست</p>';
        
        let html = '';
        
        if (Array.isArray(content)) {
            content.forEach((sub, subIndex) => {
                html += this.renderSubContent(sub, sectionIndex, subIndex);
            });
        } else if (typeof content === 'object') {
            html += this.renderSubContent(content, sectionIndex, 0);
        } else {
            html += `<p class="editable-text" data-path="sections.${sectionIndex}.content">${this.escapeHtml(content)}</p>`;
        }
        
        return html;
    }

    renderSubContent(sub, sectionIndex, subIndex) {
        let html = '';
        
        if (sub.subtitle) {
            html += `<h3 class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.subtitle">${this.escapeHtml(sub.subtitle)}</h3>`;
        }

        if (sub.type === 'list' && sub.items) {
            html += this.renderList(sub.items, sectionIndex, subIndex);
        } else if (sub.type === 'table' && sub.headers && sub.rows) {
            html += this.renderTable(sub, sectionIndex, subIndex);
        } else if (sub.type === 'paragraph' && sub.text) {
            html += `<p class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.text">${this.escapeHtml(sub.text)}</p>`;
        } else if (sub.text) {
            html += `<p class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.text">${this.escapeHtml(sub.text)}</p>`;
        }

        return html;
    }

    renderList(items, sectionIndex, subIndex) {
        let html = '<ul>';
        items.forEach((item, itemIndex) => {
            if (typeof item === 'string') {
                html += `<li class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.items.${itemIndex}">${this.escapeHtml(item)}</li>`;
            } else if (item.label && item.value) {
                html += `
                    <li>
                        <span class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.items.${itemIndex}.label">${this.escapeHtml(item.label)}</span>: 
                        <span class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.items.${itemIndex}.value">${this.escapeHtml(item.value)}</span>
                    </li>
                `;
            }
        });
        html += '</ul>';
        return html;
    }

    renderTable(tableData, sectionIndex, subIndex) {
        let html = `
            <div class="consultant-table-container">
                <table class="consultant-table">
                    <thead>
                        <tr>
        `;
        
        tableData.headers.forEach((header, headerIndex) => {
            html += `<th class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.headers.${headerIndex}">${this.escapeHtml(header)}</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        tableData.rows.forEach((row, rowIndex) => {
            html += '<tr>';
            row.forEach((cell, cellIndex) => {
                html += `<td class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.rows.${rowIndex}.${cellIndex}">${this.escapeHtml(cell)}</td>`;
            });
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        
        return html;
    }

    setupEditEvents() {
        // تب‌ها
        this.setupTabs();
        
        // آکاردئون‌ها
        this.setupAccordions();
        
        // دکمه‌های اکشن
        this.setupActionButtons();
        
        // ویرایش دبل کلیک
        this.setupDoubleClickEdit();
    }

    setupTabs() {
        const tabs = this.container.querySelectorAll('.consultant-editor-tab');
        const panes = this.container.querySelectorAll('.consultant-editor-pane');

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

    setupAccordions() {
        const headers = this.container.querySelectorAll('.consultant-section-header');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const isActive = this.classList.contains('active');
                const content = this.nextElementSibling;
                
                if (isActive) {
                    this.classList.remove('active');
                    content.style.maxHeight = '0';
                } else {
                    this.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });

        // باز کردن اولین بخش
        if (headers[0]) {
            headers[0].classList.add('active');
            const firstContent = headers[0].nextElementSibling;
            firstContent.style.maxHeight = firstContent.scrollHeight + 'px';
        }
    }

    setupActionButtons() {
        // باز کردن همه بخش‌ها
        const expandBtn = document.getElementById('expand-all-btn');
        if (expandBtn) {
            expandBtn.addEventListener('click', () => {
                const contents = this.container.querySelectorAll('.consultant-section-content');
                const headers = this.container.querySelectorAll('.consultant-section-header');
                
                headers.forEach(header => header.classList.add('active'));
                contents.forEach(content => {
                    content.style.maxHeight = content.scrollHeight + 'px';
                });
            });
        }

        // بستن همه بخش‌ها
        const collapseBtn = document.getElementById('collapse-all-btn');
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                const contents = this.container.querySelectorAll('.consultant-section-content');
                const headers = this.container.querySelectorAll('.consultant-section-header');
                
                headers.forEach(header => header.classList.remove('active'));
                contents.forEach(content => {
                    content.style.maxHeight = '0';
                });
            });
        }

        // بروزرسانی از JSON
        const updateJsonBtn = document.getElementById('update-from-json-btn');
        if (updateJsonBtn) {
            updateJsonBtn.addEventListener('click', () => {
                const jsonEditor = document.getElementById('diet-json-editor');
                if (jsonEditor) {
                    try {
                        const newData = JSON.parse(jsonEditor.value);
                        this.updatePreviewFromJSON(newData);
                    } catch (e) {
                        alert('خطا در پارس کردن JSON: ' + e.message);
                    }
                }
            });
        }
    }

    setupDoubleClickEdit() {
        const editableElements = this.container.querySelectorAll('.editable-text');
        
        editableElements.forEach(element => {
            element.addEventListener('dblclick', (e) => {
                e.stopPropagation();
                this.startEditing(element);
            });
        });

        // کلیک خارج از حالت ویرایش - با استفاده از event delegation
        this.container.addEventListener('click', (e) => {
            if (this.isEditing && !e.target.matches('.consultant-edit-input')) {
                this.finishEditing();
            }
        });

        // کلید Escape - روی document
        document.addEventListener('keydown', (e) => {
            if (this.isEditing && e.key === 'Escape') {
                this.cancelEditing();
            }
        });
    }

    startEditing(element) {
        if (this.isEditing) {
            this.finishEditing(); // اگر در حال ویرایش دیگری هست، اول آن را ذخیره کن
        }
        
        this.isEditing = true;
        this.currentEditElement = element;
        
        const originalText = element.textContent;
        const path = element.dataset.path;
        
        // ایجاد input
        const input = document.createElement('input');
        input.type = 'text';
        input.value = originalText;
        input.className = 'consultant-edit-input';
        
        // ذخیره reference به المنت اصلی
        input.dataset.originalElement = element.outerHTML;
        input.dataset.path = path;
        
        // جایگزینی element با input
        element.style.display = 'none';
        element.parentNode.insertBefore(input, element);
        
        // فوکوس و انتخاب متن
        input.focus();
        input.select();
        
        // مدیریت events با استفاده از arrow functions برای حفظ context
        this.handleInputKeydown = (e) => {
            if (e.key === 'Enter') {
                this.saveEdit(input);
            } else if (e.key === 'Escape') {
                this.cancelEditing();
            }
        };
        
        this.handleInputBlur = () => {
            this.saveEdit(input);
        };
        
        input.addEventListener('keydown', this.handleInputKeydown);
        input.addEventListener('blur', this.handleInputBlur);
    }

    saveEdit(input) {
        // حذف event listeners برای جلوگیری از اجرای تکراری
        input.removeEventListener('keydown', this.handleInputKeydown);
        input.removeEventListener('blur', this.handleInputBlur);
        
        const newValue = input.value.trim();
        const path = input.dataset.path;
        const originalElement = this.currentEditElement;
        
        if (originalElement && originalElement.parentNode) {
            // بازگرداندن المنت اصلی
            originalElement.textContent = newValue;
            originalElement.style.display = '';
            
            // حذف input
            if (input.parentNode) {
                input.remove();
            }
            
            // آپدیت داده‌ها
            this.updateData(path, newValue);
        }
        
        this.isEditing = false;
        this.currentEditElement = null;
        this.handleInputKeydown = null;
        this.handleInputBlur = null;
    }

    cancelEditing() {
        if (!this.isEditing || !this.currentEditElement) return;
        
        const input = this.container.querySelector('.consultant-edit-input');
        if (input && this.currentEditElement && this.currentEditElement.parentNode) {
            // حذف event listeners
            input.removeEventListener('keydown', this.handleInputKeydown);
            input.removeEventListener('blur', this.handleInputBlur);
            
            // بازگرداندن المنت اصلی
            this.currentEditElement.style.display = '';
            
            // حذف input
            if (input.parentNode) {
                input.remove();
            }
        }
        
        this.isEditing = false;
        this.currentEditElement = null;
        this.handleInputKeydown = null;
        this.handleInputBlur = null;
    }

    finishEditing() {
        if (!this.isEditing) return;
        
        const input = this.container.querySelector('.consultant-edit-input');
        if (input) {
            this.saveEdit(input);
        } else {
            this.cancelEditing();
        }
    }

    // متد کمکی برای پیدا کردن المنت اصلی
    findOriginalElement(input) {
        const path = input.dataset.path;
        return this.container.querySelector(`[data-path="${path}"]`);
    }
    
    
    updateData(path, value) {
        console.log('Updating data path:', path, 'to:', value);
        
        // آپدیت داده‌ها بر اساس مسیر
        this.updateNestedData(this.currentData, path, value);
        
        // آپدیت JSON editor
        this.updateJsonEditor();
        
        // آپدیت پیش‌نمایش
        this.updatePreview();
    }

    updateNestedData(obj, path, value) {
        const keys = path.split('.');
        let current = obj;
        
        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            // اگر کلید عددی است، به آرایه تبدیل کن
            if (!isNaN(keys[i + 1])) {
                if (!current[key] || !Array.isArray(current[key])) {
                    current[key] = [];
                }
            } else {
                if (!current[key] || typeof current[key] !== 'object') {
                    current[key] = {};
                }
            }
            current = current[key];
        }
        
        const lastKey = keys[keys.length - 1];
        
        // اگر مقدار عددی است، به عدد تبدیل کن
        if (!isNaN(value) && value.trim() !== '') {
            current[lastKey] = Number(value);
        } else {
            current[lastKey] = value;
        }
    }

    updateJsonEditor() {
        const jsonEditor = document.getElementById('diet-json-editor');
        if (jsonEditor) {
            jsonEditor.value = JSON.stringify(this.currentData, null, 2);
        }
    }

    updatePreview() {
        const previewContainer = document.getElementById('consultant-diet-preview');
        if (previewContainer) {
            previewContainer.innerHTML = this.renderDietPlan(this.currentData);
            this.setupDoubleClickEdit();
            this.setupAccordions();
        }
    }

    setupActionButtons() {
        // باز کردن همه بخش‌ها
        const expandBtn = document.getElementById('expand-all-btn');
        if (expandBtn) {
            expandBtn.addEventListener('click', () => {
                this.expandAllSections();
            });
        }

        // بستن همه بخش‌ها
        const collapseBtn = document.getElementById('collapse-all-btn');
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                this.collapseAllSections();
            });
        }

        // بروزرسانی از JSON
        const updateJsonBtn = document.getElementById('update-from-json-btn');
        if (updateJsonBtn) {
            updateJsonBtn.addEventListener('click', () => {
                this.updateFromJson();
            });
        }

        // بازنشانی JSON
        const resetJsonBtn = document.getElementById('reset-json-btn');
        if (resetJsonBtn) {
            resetJsonBtn.addEventListener('click', () => {
                this.resetJson();
            });
        }
    }

    expandAllSections() {
        const contents = this.container.querySelectorAll('.consultant-section-content');
        const headers = this.container.querySelectorAll('.consultant-section-header');
        
        headers.forEach(header => header.classList.add('active'));
        contents.forEach(content => {
            content.style.maxHeight = content.scrollHeight + 'px';
        });
    }

    collapseAllSections() {
        const contents = this.container.querySelectorAll('.consultant-section-content');
        const headers = this.container.querySelectorAll('.consultant-section-header');
        
        headers.forEach(header => header.classList.remove('active'));
        contents.forEach(content => {
            content.style.maxHeight = '0';
        });
    }

    updateFromJson() {
        const jsonEditor = document.getElementById('diet-json-editor');
        if (jsonEditor) {
            try {
                const newData = JSON.parse(jsonEditor.value);
                this.currentData = newData;
                this.updatePreview();
                this.showMessage('پیش‌نمایش با موفقیت بروزرسانی شد', 'success');
            } catch (e) {
                this.showMessage('خطا در پارس کردن JSON: ' + e.message, 'error');
            }
        }
    }

    resetJson() {
        const { original_data, consultation_data } = this.originalData;
        const dietData = consultation_data?.final_diet_data || original_data?.ai_response;
        
        try {
            this.currentData = typeof dietData === 'string' ? JSON.parse(dietData) : dietData;
            this.updateJsonEditor();
            this.updatePreview();
            this.showMessage('JSON به حالت اولیه بازنشانی شد', 'success');
        } catch (e) {
            this.showMessage('خطا در بازنشانی داده‌ها', 'error');
        }
    }

    showMessage(message, type) {
        // حذف پیام قبلی
        const existingMessage = this.container.querySelector('.consultant-editor-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `consultant-editor-message ${type}`;
        messageDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
            ${message}
        `;

        this.container.insertBefore(messageDiv, this.container.firstChild);

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 3000);
    }

    getEditedData() {
        return this.currentData;
    }

    getFinalDietData() {
        return JSON.stringify(this.currentData, null, 2);
    }

    updatePreviewFromJSON(newData) {
        const previewContainer = document.getElementById('consultant-diet-preview');
        if (previewContainer) {
            previewContainer.innerHTML = this.renderDietPlan(newData);
            this.setupDoubleClickEdit();
            this.setupAccordions();
        }
    }

    getEditedData() {
        // این متد داده‌های ویرایش شده را برمی‌گرداند
        const jsonEditor = document.getElementById('diet-json-editor');
        if (jsonEditor) {
            try {
                return JSON.parse(jsonEditor.value);
            } catch (e) {
                console.error('Error parsing edited JSON:', e);
            }
        }
        
        return this.originalData;
    }

    getSectionIcon(sectionTitle) {
        const icons = {
            'اطلاعات کاربر': 'user',
            'اطلاعات تغذیه‌ای': 'chart-pie',
            'برنامه هفتگی': 'calendar-alt',
            'توصیه‌های تکمیلی': 'lightbulb',
            'نتایج برنامه غذایی': 'utensils'
        };
        
        return icons[sectionTitle] || 'file-alt';
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
}