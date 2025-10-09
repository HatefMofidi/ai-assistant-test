class ConsultantDietEditor {
    constructor(containerId = 'consultation-editor') {
        this.container = document.getElementById(containerId);
        this.currentRequestId = null;
        this.originalData = null;
        this.currentData = null;
        this.isEditing = false;
        this.currentEditElement = null;
        this.currentInput = null;
        
        if (!this.container) {
            console.error(`Container with ID '${containerId}' not found`);
            return;
        }
    }

    init(data, requestId) {
        this.originalData = data;
        this.currentRequestId = requestId;
        
        // استخراج و پارس داده‌های رژیم
        const { original_data, consultation_data } = data;
        const dietData = consultation_data?.final_diet_data || original_data?.ai_response;
        
        try {
            this.currentData = typeof dietData === 'string' ? JSON.parse(dietData) : dietData;
        } catch (e) {
            console.error('Error parsing diet data:', e);
            this.currentData = this.createFallbackData();
        }
        
        this.render();
    }

    createFallbackData() {
        return {
            sections: [
                {
                    title: "برنامه غذایی",
                    content: [
                        {
                            type: "paragraph",
                            text: "داده‌های رژیم غذایی در دسترس نیست. لطفاً به صورت دستی ویرایش کنید."
                        }
                    ]
                }
            ]
        };
    }

    render() {
        if (!this.originalData || !this.currentData) {
            this.container.innerHTML = this.renderErrorState();
            return;
        }

        this.container.innerHTML = this.renderStructuredEditor();
        this.setupEditorEvents();
    }

    renderErrorState() {
        return `
            <div class="consultant-editor-error">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>خطا در بارگذاری داده‌ها</h3>
                <p>داده‌های رژیم غذایی برای نمایش موجود نیست.</p>
                <button class="consultant-btn consultant-btn-primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i> بارگذاری مجدد
                </button>
            </div>
        `;
    }

    renderStructuredEditor() {
        return `
            <div class="consultant-editor-tabs">
                <!--<div class="consultant-editor-tab active" data-tab="preview"> -->
                <!--    <i class="fas fa-eye"></i> پیش‌نمایش رژیم  -->
                <!--</div>-->
                <!--<div class="consultant-editor-tab" data-tab="json">-->
                <!--    <i class="fas fa-code"></i> ویرایش JSON-->
                <!--</div>-->
            </div>

            <div class="consultant-editor-content">
                <!-- تب پیش‌نمایش -->
                <div class="consultant-editor-pane active" id="preview-pane">
                        <!--<div class="consultant-editor-actions">-->
                        <!--    <button class="consultant-btn consultant-btn-primary" id="expand-all-btn">-->
                        <!--        <i class="fas fa-expand"></i> باز کردن همه-->
                        <!--    </button>-->
                        <!--    <button class="consultant-btn consultant-btn-secondary" id="collapse-all-btn">-->
                        <!--        <i class="fas fa-compress"></i> بستن همه-->
                        <!--    </button>-->
                        <!--    <button class="consultant-btn consultant-btn-success" id="refresh-preview-btn">-->
                        <!--        <i class="fas fa-sync"></i> بروزرسانی-->
                        <!--    </button>-->
                        <!--</div>-->
                    <div class="consultant-diet-preview" id="consultant-diet-preview">
                        ${this.renderDietPlan(this.currentData)}
                    </div>
                </div>

                <!-- تب ویرایش JSON -->
                <div class="consultant-editor-pane" id="json-pane">
                    <div class="consultant-json-editor">
                        <h4><i class="fas fa-code"></i> ویرایشگر JSON پیشرفته</h4>
                        <div class="consultant-editor-actions">
                            <button class="consultant-btn consultant-btn-success" id="apply-json-btn">
                                <i class="fas fa-check"></i> اعمال تغییرات
                            </button>
                            <button class="consultant-btn consultant-btn-warning" id="validate-json-btn">
                                <i class="fas fa-check-circle"></i> اعتبارسنجی
                            </button>
                            <button class="consultant-btn consultant-btn-secondary" id="reset-json-btn">
                                <i class="fas fa-undo"></i> بازنشانی
                            </button>
                        </div>
                        <textarea id="diet-json-editor" class="consultant-json-textarea">${this.escapeHtml(JSON.stringify(this.currentData, null, 2))}</textarea>
                        <div class="consultant-json-status" id="json-status"></div>
                    </div>
                </div>

            </div>

            <!-- بخش یادداشت‌ها -->
            <div class="consultant-notes-section">
                <label for="consultant-notes">
                    <i class="fas fa-sticky-note"></i> یادداشت‌های مشاور:
                </label>
                <textarea id="consultant-notes" class="consultant-notes-textarea" 
                    placeholder="یادداشت‌ها، توضیحات و توصیه‌های خود را اینجا بنویسید...">${this.escapeHtml(this.originalData.consultation_data?.consultant_notes || '')}</textarea>
            </div>

            <!-- مودال ویرایش -->
            <div id="edit-modal" class="consultant-edit-modal" style="display: none;">
                <div class="consultant-edit-modal-content">
                    <div class="consultant-edit-modal-header">
                        <h4><i class="fas fa-edit"></i> ویرایش محتوا</h4>
                        <span class="consultant-edit-close">&times;</span>
                    </div>
                    <div class="consultant-edit-modal-body">
                        <textarea id="edit-modal-textarea" class="consultant-modal-textarea"></textarea>
                    </div>
                    <div class="consultant-edit-modal-footer">
                        <button class="consultant-btn consultant-btn-success" id="edit-modal-save">
                            <i class="fas fa-check"></i> تایید
                        </button>
                        <button class="consultant-btn consultant-btn-danger" id="edit-modal-cancel">
                            <i class="fas fa-times"></i> لغو
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    renderDietPlan(data) {
        if (!data.sections || !Array.isArray(data.sections)) {
            return this.renderRawData(data);
        }

        let html = '<div class="consultant-diet-plan">';
        
        data.sections.forEach((section, sectionIndex) => {
            html += this.renderSection(section, sectionIndex);
        });

        if (data.footer) {
            html += `<div class="consultant-footer not-editable-text" data-path="footer">${this.escapeHtml(data.footer)}</div>`;
        }

        html += '</div>';
        return html;
    }

    renderSection(section, sectionIndex) {
        const isExpanded = sectionIndex === 0; // اولین بخش باز باشد
        
        return `
            <div class="consultant-section" data-section-index="${sectionIndex}">
                <div class="consultant-section-header ${isExpanded ? 'active' : ''}">
                    <h2>
                        <i class="fas fa-${this.getSectionIcon(section.title)}"></i>
                        <span class="not-editable-text" data-path="sections.${sectionIndex}.title">${this.escapeHtml(section.title || 'بخش بدون عنوان')}</span>
                    </h2>
                    <i class="fas fa-chevron-${isExpanded ? 'up' : 'down'} consultant-accordion-icon"></i>
                </div>
                <div class="consultant-section-content" style="max-height: ${isExpanded ? 'none' : '0'};">
                    ${this.renderSectionContent(section.content, sectionIndex)}
                </div>
            </div>
        `;
    }

    renderSectionContent(content, sectionIndex) {
        if (!content) return '<p class="consultant-no-content">محتوایی موجود نیست</p>';
        
        let html = '';
        
        if (Array.isArray(content)) {
            content.forEach((sub, subIndex) => {
                html += this.renderSubContent(sub, sectionIndex, subIndex);
            });
        } else if (typeof content === 'object') {
            // برای وقتی که content یک آبجکت است، از مسیر مستقیم استفاده کن
            html += this.renderSubContent(content, sectionIndex, null);
        } else {
            html += `<div class="consultant-paragraph editable-text" data-path="sections.${sectionIndex}.content">${this.escapeHtml(content)}</div>`;
        }
        
        return html;
    }

    renderSubContent(sub, sectionIndex, subIndex) {
        let html = '';
        const basePath = subIndex !== null ? `sections.${sectionIndex}.content.${subIndex}` : `sections.${sectionIndex}.content`;
        
        if (sub.subtitle) {
            html += `
                <div class="consultant-subsection">
                    <h3 class="not-editable-text" data-path="${basePath}.subtitle">
                        ${this.escapeHtml(sub.subtitle)}
                    </h3>
            `;
        }
    
        if (sub.type === 'list' && sub.items) {
            html += this.renderList(sub.items, sectionIndex, subIndex, basePath);
        } else if (sub.type === 'table' && sub.headers && sub.rows) {
            html += this.renderTableAsCards(sub, sectionIndex, subIndex, basePath);
        } else if (sub.type === 'paragraph' && sub.text) {
            html += `<div class="consultant-paragraph editable-text" data-path="${basePath}.text">${this.escapeHtml(sub.text)}</div>`;
        } else if (sub.text) {
            html += `<div class="consultant-paragraph editable-text" data-path="${basePath}.text">${this.escapeHtml(sub.text)}</div>`;
        }
    
        if (sub.subtitle) {
            html += '</div>';
        }
    
        return html;
    }

    renderList(items, sectionIndex, subIndex, basePath) {
        // اگر basePath ارائه نشده، آن را ایجاد کن
        if (!basePath) {
            basePath = subIndex !== null ? `sections.${sectionIndex}.content.${subIndex}` : `sections.${sectionIndex}.content`;
        }
    
        let html = '<ul class="consultant-list">';
        
        items.forEach((item, itemIndex) => {
            // اگر آیتم تهی یا فضای خالی است، رد شو
            if (!item) return;
            if (typeof item === 'string' && item.trim() === '') return;
            if (typeof item === 'object' && (!item.label || !item.value || item.label.trim() === '' || item.value.trim() === '')) return;
    
            if (typeof item === 'string') {
                const trimmed = item.trim();
                if (trimmed !== '') {
                    html += `<li class="editable-text" data-path="${basePath}.items.${itemIndex}">${this.escapeHtml(trimmed)}</li>`;
                }
            }
            else if (item.label && item.value) {
                const label = this.escapeHtml(item.label.trim());
                const value = this.escapeHtml(item.value.trim());
                
                if (sectionIndex === 1) {  // برای غیرقابل ویرایش کردن بخش مشخصات کاربر
                    html += `
                        <li>
                            <span class="not-editable-text" data-path="${basePath}.items.${itemIndex}.label">${label}</span>: 
                            <span class="not-editable-text" data-path="${basePath}.items.${itemIndex}.value">${value}</span>
                        </li>
                    `;
                } else {
                    html += `
                        <li>
                            <span class="not-editable-text" data-path="${basePath}.items.${itemIndex}.label">${label}</span>: 
                            <span class="editable-text" data-path="${basePath}.items.${itemIndex}.value">${value}</span>
                        </li>
                    `;
                }
            }
        });
    
        html += '</ul>';
        return html;
    }

    
    renderTableAsCards(tableData, sectionIndex, subIndex) {
        if (!tableData.headers || !tableData.rows) {
            return '<div class="consultant-error">داده‌های جدول نامعتبر است</div>';
        }
    
        const container = document.createElement("div");
        container.className = "consultant-cards-container";
    
        tableData.rows.forEach((row, rowIndex) => {
            const dayCard = this.createDayCard(row, tableData.headers, sectionIndex, subIndex, rowIndex);
            container.appendChild(dayCard);
        });
    
        return container.outerHTML;
    }
    
    createDayCard(row, headers, sectionIndex, subIndex, rowIndex) {
        const dayCard = document.createElement("div");
        dayCard.className = "consultant-day-card";
    
        // هدر روز
        const header = document.createElement("div");
        header.className = "consultant-day-header";
        header.innerHTML = `
            <i class="fas fa-calendar-day"></i>
            <span class="not-editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.rows.${rowIndex}.0">
                ${this.escapeHtml(row[0] || `روز ${rowIndex + 1}`)}
            </span>
        `;
        dayCard.appendChild(header);
    
        // کانتینر وعده‌ها
        const mealsContainer = document.createElement("div");
        mealsContainer.className = "consultant-meals-container";
    
        for (let i = 1; i < headers.length; i++) {
            const content = (row[i] || '').trim();
            if (content) {
                const mealSection = this.createMealSection(content, headers[i], sectionIndex, subIndex, rowIndex, i);
                mealsContainer.appendChild(mealSection);
            }
        }
    
        dayCard.appendChild(mealsContainer);
        return dayCard;
    }
    
    createMealSection(mealContent, mealName, sectionIndex, subIndex, rowIndex, columnIndex) {
        const mealSection = document.createElement("div");
        mealSection.className = "consultant-meal-section";
    
        const mealTitle = document.createElement("div");
        mealTitle.className = "consultant-meal-title";
        mealTitle.innerHTML = `
            <i class="fas fa-${this.getMealIcon(columnIndex, mealName)}"></i>
            <span class="not-editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.headers.${columnIndex}">
                ${this.escapeHtml(mealName)}
            </span>
        `;
    
        const mealContentDiv = document.createElement("div");
        mealContentDiv.className = "consultant-meal-content editable-text";
        mealContentDiv.dataset.path = `sections.${sectionIndex}.content.${subIndex}.rows.${rowIndex}.${columnIndex}`;
        mealContentDiv.innerHTML = this.formatMealContent(mealContent);
    
        mealSection.appendChild(mealTitle);
        mealSection.appendChild(mealContentDiv);
    
        return mealSection;
    }


    formatMealContent(content) {
        if (!content) return '';
        // تبدیل خطوط جدید به <br> برای نمایش مناسب
        return this.escapeHtml(content).replace(/\n/g, '<br>');
    }

    setupEditorEvents() {
        this.setupTabs();
        this.setupAccordions();
        this.setupActionButtons();
        this.setupEditModal();
        this.setupDoubleClickEdit();
    }

    setupTabs() {
        const tabs = this.container.querySelectorAll('.consultant-editor-tab');
        const panes = this.container.querySelectorAll('.consultant-editor-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                tabs.forEach(t => t.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
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
                const icon = this.querySelector('.consultant-accordion-icon');
                
                // بستن همه بخش‌های دیگر
                headers.forEach(h => {
                    if (h !== this) {
                        h.classList.remove('active');
                        h.nextElementSibling.style.maxHeight = '0';
                        h.querySelector('.consultant-accordion-icon').className = 'fas fa-chevron-down consultant-accordion-icon';
                    }
                });
                
                if (isActive) {
                    this.classList.remove('active');
                    content.style.maxHeight = '0';
                    icon.className = 'fas fa-chevron-down consultant-accordion-icon';
                } else {
                    this.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                    icon.className = 'fas fa-chevron-up consultant-accordion-icon';
                }
            });
        });
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

        // بروزرسانی پیش‌نمایش
        const refreshBtn = document.getElementById('refresh-preview-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.updatePreview();
            });
        }

        // اعمال تغییرات JSON
        const applyJsonBtn = document.getElementById('apply-json-btn');
        if (applyJsonBtn) {
            applyJsonBtn.addEventListener('click', () => {
                this.updateFromJson();
            });
        }

        // اعتبارسنجی JSON
        const validateJsonBtn = document.getElementById('validate-json-btn');
        if (validateJsonBtn) {
            validateJsonBtn.addEventListener('click', () => {
                this.validateJson();
            });
        }

        // بازنشانی JSON
        const resetJsonBtn = document.getElementById('reset-json-btn');
        if (resetJsonBtn) {
            resetJsonBtn.addEventListener('click', () => {
                this.resetJson();
            });
        }

        // اعمال تغییرات متنی
        const applyTextBtn = document.getElementById('apply-text-btn');
        if (applyTextBtn) {
            applyTextBtn.addEventListener('click', () => {
                this.updateFromText();
            });
        }
    }

    setupEditModal() {
        this.editModal = document.getElementById('edit-modal');
        this.modalTextarea = document.getElementById('edit-modal-textarea');
        this.modalSaveBtn = document.getElementById('edit-modal-save');
        this.modalCancelBtn = document.getElementById('edit-modal-cancel');
        this.modalCloseBtn = document.querySelector('.consultant-edit-close');

        if (this.editModal && this.modalTextarea) {
            this.modalSaveBtn.addEventListener('click', () => this.saveModalEdit());
            this.modalCancelBtn.addEventListener('click', () => this.closeEditModal());
            this.modalCloseBtn.addEventListener('click', () => this.closeEditModal());
            
            // بستن مودال با کلیک خارج
            this.editModal.addEventListener('click', (e) => {
                if (e.target === this.editModal) {
                    this.closeEditModal();
                }
            });

            // کلیدهای میانبر در مودال
            this.modalTextarea.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    this.saveModalEdit();
                    e.preventDefault();
                } else if (e.key === 'Escape') {
                    this.closeEditModal();
                    e.preventDefault();
                }
            });
        }
    }

    setupDoubleClickEdit() {
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        if (isTouchDevice) {
            // موبایل - نگه داشتن
            let pressTimer;
            
            this.container.addEventListener('touchstart', (e) => {
                const editableElement = e.target.closest('.editable-text');
                if (editableElement && !this.isEditing) {
                    pressTimer = setTimeout(() => {
                        e.preventDefault();
                        this.openEditModal(editableElement);
                    }, 600);
                }
            });
    
            this.container.addEventListener('touchend', () => {
                clearTimeout(pressTimer);
            });
    
            this.container.addEventListener('touchmove', () => {
                clearTimeout(pressTimer);
            });
            
        } else {
            // دسکتاپ - دابل کلیک
            this.container.addEventListener('dblclick', (e) => {
                const editableElement = e.target.closest('.editable-text');
                if (editableElement && !this.isEditing) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.openEditModal(editableElement);
                }
            });
        }
    }

    openEditModal(element) {
        if (this.isEditing) return;
        
        this.isEditing = true;
        this.currentEditElement = element;
        this.currentEditPath = element.getAttribute('data-path');
        this.originalEditValue = element.textContent;

        // پر کردن مودال با متن فعلی
        this.modalTextarea.value = this.originalEditValue;
        
        // نمایش مودال
        this.editModal.style.display = 'block';
        
        // فوکوس و انتخاب متن
        setTimeout(() => {
            this.modalTextarea.focus();
            // this.modalTextarea.select();
        }, 100);
    }

    closeEditModal() {
        this.isEditing = false;
        this.currentEditElement = null;
        this.currentEditPath = null;
        this.originalEditValue = null;
        this.editModal.style.display = 'none';
    }

    saveModalEdit() {
        if (!this.isEditing || !this.currentEditElement) return;

        const newValue = this.modalTextarea.value.trim();
        
        if (newValue !== this.originalEditValue) {
            // آپدیت المنت
            this.currentEditElement.textContent = newValue;
            
            // آپدیت داده‌ها
            this.updateData(this.currentEditPath, newValue);
            
            // آپدیت پیش‌نمایش اگر لازم است
            this.updateElementStyle(this.currentEditElement, newValue);
        }

        this.closeEditModal();
    }

    updateData(path, value) {
        console.log('Updating data path:', path, 'to:', value);
        
        this.updateNestedData(this.currentData, path, value);
        this.updateJsonEditor();
    }

    updateNestedData(obj, path, value) {
        const keys = path.split('.');
        let current = obj;
        
        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            
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
        current[lastKey] = value;
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
            this.setupAccordions();
            this.showMessage('پیش‌نمایش بروزرسانی شد', 'success');
        }
    }

    updateFromJson() {
        const jsonEditor = document.getElementById('diet-json-editor');
        if (jsonEditor) {
            try {
                const newData = JSON.parse(jsonEditor.value);
                this.currentData = newData;
                this.updatePreview();
                this.showJsonStatus('JSON با موفقیت اعمال شد', 'success');
            } catch (e) {
                this.showJsonStatus('خطا در پارس کردن JSON: ' + e.message, 'error');
            }
        }
    }

    validateJson() {
        const jsonEditor = document.getElementById('diet-json-editor');
        if (jsonEditor) {
            try {
                JSON.parse(jsonEditor.value);
                this.showJsonStatus('JSON معتبر است', 'success');
            } catch (e) {
                this.showJsonStatus('JSON نامعتبر: ' + e.message, 'error');
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
            this.showJsonStatus('JSON به حالت اولیه بازگشت', 'success');
        } catch (e) {
            this.showJsonStatus('خطا در بازنشانی داده‌ها', 'error');
        }
    }

    updateFromText() {
        const textEditor = document.getElementById('diet-text-editor');
        if (textEditor) {
            // در اینجا می‌توانید منطق تبدیل متن ساده به JSON را پیاده‌سازی کنید
            this.showMessage('تغییرات متنی اعمال شد', 'success');
        }
    }

    expandAllSections() {
        const contents = this.container.querySelectorAll('.consultant-section-content');
        const headers = this.container.querySelectorAll('.consultant-section-header');
        
        headers.forEach(header => {
            header.classList.add('active');
            const icon = header.querySelector('.consultant-accordion-icon');
            icon.className = 'fas fa-chevron-up consultant-accordion-icon';
        });
        
        contents.forEach(content => {
            content.style.maxHeight = content.scrollHeight + 'px';
        });
    }

    collapseAllSections() {
        const contents = this.container.querySelectorAll('.consultant-section-content');
        const headers = this.container.querySelectorAll('.consultant-section-header');
        
        headers.forEach(header => {
            header.classList.remove('active');
            const icon = header.querySelector('.consultant-accordion-icon');
            icon.className = 'fas fa-chevron-down consultant-accordion-icon';
        });
        
        contents.forEach(content => {
            content.style.maxHeight = '0';
        });
    }

    updateElementStyle(element, newValue) {
        if (newValue.length > 50 || newValue.includes('\n')) {
            element.style.whiteSpace = 'pre-wrap';
            element.style.wordWrap = 'break-word';
            element.style.lineHeight = '1.5';
        }
    }

    formatAsText(data) {
        // تبدیل JSON به متن خوانا
        if (!data.sections) return JSON.stringify(data, null, 2);
        
        let text = '';
        data.sections.forEach(section => {
            text += `# ${section.title}\n\n`;
            if (section.content) {
                if (Array.isArray(section.content)) {
                    section.content.forEach(item => {
                        if (item.text) {
                            text += `${item.text}\n\n`;
                        }
                    });
                }
            }
            text += '\n';
        });
        
        return text;
    }

    getFinalDietData() {
        return JSON.stringify(this.currentData, null, 2);
    }

    getConsultantNotes() {
        const notesElement = document.getElementById('consultant-notes');
        return notesElement ? notesElement.value : '';
    }

    // متدهای کمکی
    getSectionIcon(sectionTitle) {
        const icons = {
            'اطلاعات کاربر': 'user',
            'اطلاعات تغذیه‌ای': 'chart-pie',
            'برنامه هفتگی': 'calendar-alt',
            'توصیه‌های تکمیلی': 'lightbulb',
            'نتایج برنامه غذایی': 'utensils',
            'برنامه غذایی': 'utensils'
        };
        
        return icons[sectionTitle] || 'file-alt';
    }

    getMealIcon(columnIndex, mealName) {
        const mealIcons = {
            'صبحانه': 'sun',
            'ناهار': 'sun', 
            'شام': 'moon',
            'میان‌وعده': 'apple-alt',
            'میان وعده': 'apple-alt',
            'میان‌وعده صبح': 'sun',
            'میان‌وعده عصر': 'apple-alt',
            'عصرانه': 'apple-alt'
        };
        
        const lowerMealName = mealName.toLowerCase();
        for (const [key, icon] of Object.entries(mealIcons)) {
            if (lowerMealName.includes(key.toLowerCase())) {
                return icon;
            }
        }
        
        const defaultIcons = ['sun', 'sun', 'moon', 'apple-alt', 'utensils'];
        return defaultIcons[columnIndex - 1] || 'utensils';
    }

    showMessage(message, type) {
        // پیاده‌سازی نمایش پیام
        console.log(`${type}: ${message}`);
    }

    showJsonStatus(message, type) {
        const statusElement = document.getElementById('json-status');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `consultant-json-status ${type}`;
            
            setTimeout(() => {
                statusElement.textContent = '';
                statusElement.className = 'consultant-json-status';
            }, 3000);
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

    renderRawData(data) {
        return `
            <div class="consultant-debug-info">
                <h3><i class="fas fa-bug"></i> داده‌های خام (برای دیباگ)</h3>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            </div>
        `;
    }
}