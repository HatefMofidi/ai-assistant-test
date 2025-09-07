class DietPlanRenderer {
    constructor(options = {}) {
        this.options = {
            containerId: 'diet-plan-container',
            showHeader: true,
            showActions: true,
            showBackButton: true,
            backButtonUrl: '/',
            backButtonCallback: null,
            ...options
        };
        
        this.container = document.getElementById(this.options.containerId);
        if (!this.container) {
            console.error(`Container with ID '${this.options.containerId}' not found`);
            return;
        }
        
        this.init();
    }
    
    init() {
        // اضافه کردن کلاس به container برای محدود کردن scope استایل‌ها
        this.container.classList.add('diet-plan-container');
    }
    
    render(data) {
        if (!this.container) return;
        
        // ایجاد ساختار HTML
        this.container.innerHTML = this.generateHTML();
        
        // رندر محتوا
        this.renderContent(data);
        
        // راه‌اندازی آکاردئون‌ها
        setTimeout(() => this.initAccordions(), 100);
        
        // تنظیم رویدادها
        this.setupEvents();
    }
    
    generateHTML() {
        return `
            <div class="result-container">
                ${this.options.showHeader ? `
                <div class="header">
                    <h1><i class="fas fa-utensils"></i> برنامه تغذیه‌ای بالینی</h1>
                    <p>برنامه غذایی شخصی شده بر اساس مشخصات شما</p>
                </div>
                ` : ''}
                
                ${this.options.showActions ? `
                <div class="action-controls">
                    <button class="action-btn print" id="diet-print-btn">
                        <i class="fas fa-print"></i> چاپ برنامه
                    </button>
                </div>
                ` : ''}
                
                <div id="diet-debug-info"></div>
                <div id="diet-content"></div>
                
                ${this.options.showBackButton ? `
                <div style="text-align: center;">
                    <a href="${this.options.backButtonUrl}" class="back-button" id="diet-back-button">
                        <i class="fas fa-home"></i>
                        بازگشت به صفحه اصلی
                    </a>
                </div>
                ` : ''}
            </div>
        `;
    }
    
    setupEvents() {
        // رویداد چاپ
        const printBtn = document.getElementById('diet-print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', () => this.printDocument());
        }
        
        // رویداد بازگشت
        const backButton = document.getElementById('diet-back-button');
        if (backButton) {
            backButton.addEventListener('click', (e) => {
                if (this.options.backButtonCallback && typeof this.options.backButtonCallback === 'function') {
                    e.preventDefault();
                    this.options.backButtonCallback();
                }
            });
        }
    }
    
    expandAllForPrint() {
        const accordionHeaders = this.container.querySelectorAll('.accordion-header');
        const accordionContents = this.container.querySelectorAll('.accordion-content');
        const accordionContentInners = this.container.querySelectorAll('.accordion-content-inner');
        
        accordionHeaders.forEach(header => {
            header.classList.add('active');
        });
        
        accordionContents.forEach(content => {
            content.classList.add('active');
            content.style.maxHeight = 'none';
            content.style.opacity = '1';
        });
        
        accordionContentInners.forEach(inner => {
            inner.style.opacity = '1';
        });
    }
    
    printDocument() {
        this.expandAllForPrint();
        
        setTimeout(() => {
            window.print();
        }, 100);
    }
    
    initAccordions() {
        const accordionHeaders = this.container.querySelectorAll('.accordion-header');
        
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const isActive = this.classList.contains('active');
                const content = this.nextElementSibling;
                
                if (isActive) {
                    // اگر باز است، بسته شود
                    this.classList.remove('active');
                    content.classList.remove('active');
                    content.style.maxHeight = '0';
                } else {
                    // اگر بسته است، باز شود
                    this.classList.add('active');
                    content.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });
        
        // باز کردن اولین بخش به صورت پیش‌فرض
        if (accordionHeaders[0]) {
            accordionHeaders[0].classList.add('active');
            const firstContent = accordionHeaders[0].nextElementSibling;
            firstContent.classList.add('active');
            firstContent.style.maxHeight = firstContent.scrollHeight + 'px';
        }
    }
    
    renderContent(data) {
        const container = this.container.querySelector("#diet-content");
        if (!container) return;
        
        container.innerHTML = '';
        
        // اگر داده ساختار مورد انتظار را ندارد
        if (!data.sections || !Array.isArray(data.sections)) {
            container.innerHTML = '<div class="accordion-section"><div class="accordion-header"><h2><i class="fas fa-utensils"></i> نتایج برنامه غذایی</h2><i class="fas fa-chevron-down accordion-icon"></i></div><div class="accordion-content"><div class="accordion-content-inner"><div class="debug">' + JSON.stringify(data, null, 2) + '</div></div></div></div>';
            return;
        }

        // ایجاد بخش‌های مختلف با آکاردئون
        data.sections.forEach((section, index) => {
            const sectionDiv = document.createElement("div");
            sectionDiv.className = "accordion-section";

            const headerDiv = document.createElement("div");
            headerDiv.className = "accordion-header";
            headerDiv.innerHTML = '<h2><i class="fas fa-' + this.getSectionIcon(section.title) + '"></i> ' + (section.title || "بخش بدون عنوان") + '</h2><i class="fas fa-chevron-down accordion-icon"></i>';
            
            const contentDiv = document.createElement("div");
            contentDiv.className = "accordion-content";
            
            const contentInnerDiv = document.createElement("div");
            contentInnerDiv.className = "accordion-content-inner";

            // بررسی نوع محتوا
            if (section.content) {
                if (Array.isArray(section.content)) {
                    section.content.forEach(sub => {
                        if (sub && sub.subtitle) {
                            const h3 = document.createElement("h3");
                            h3.textContent = sub.subtitle;
                            contentInnerDiv.appendChild(h3);
                        }

                        if (sub && sub.type === "list" && sub.items) {
                            const ul = document.createElement("ul");
                            sub.items.forEach(item => {
                                const li = document.createElement("li");
                                if (item && item.label && item.value) {
                                    li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                                } else if (typeof item === "string") {
                                    li.textContent = item;
                                }
                                ul.appendChild(li);
                            });
                            contentInnerDiv.appendChild(ul);
                        }
                        
                        if (sub && sub.type === "table" && sub.headers && sub.rows) {
                            // ایجاد جدول برای دسکتاپ
                            const table = document.createElement("table");
                            const thead = document.createElement("thead");
                            const headRow = document.createElement("tr");
                            
                            sub.headers.forEach(h => {
                                const th = document.createElement("th");
                                th.textContent = h;
                                headRow.appendChild(th);
                            });
                            
                            thead.appendChild(headRow);
                            table.appendChild(thead);

                            const tbody = document.createElement("tbody");
                            sub.rows.forEach(row => {
                                const tr = document.createElement("tr");
                                row.forEach(cell => {
                                    const td = document.createElement("td");
                                    td.textContent = cell;
                                    tr.appendChild(td);
                                });
                                tbody.appendChild(tr);
                            });
                            
                            table.appendChild(tbody);
                            contentInnerDiv.appendChild(table);
                            
                            // ایجاد کارت‌های موبایل
                            const mobileCards = document.createElement("div");
                            mobileCards.className = "mobile-cards";
                            
                            sub.rows.forEach((row, index) => {
                                const dayCard = document.createElement("div");
                                dayCard.className = "day-card";
                                
                                const dayHeader = document.createElement("div");
                                dayHeader.className = "day-header";
                                dayHeader.innerHTML = '<i class="fas fa-calendar-day"></i> <span>' + row[0] + '</span>';
                                dayCard.appendChild(dayHeader);
                                
                                // اضافه کردن وعده‌های غذایی
                                for (let i = 1; i < row.length; i++) {
                                    if (row[i]) {
                                        const mealSection = document.createElement("div");
                                        mealSection.className = "meal-section";
                                        
                                        const mealTitle = document.createElement("div");
                                        mealTitle.className = "meal-title";
                                        mealTitle.innerHTML = '<i class="fas fa-' + this.getMealIcon(i) + '"></i> <span>' + sub.headers[i] + '</span>';
                                        
                                        const mealContent = document.createElement("div");
                                        mealContent.textContent = row[i];
                                        
                                        mealSection.appendChild(mealTitle);
                                        mealSection.appendChild(mealContent);
                                        dayCard.appendChild(mealSection);
                                    }
                                }
                                
                                mobileCards.appendChild(dayCard);
                            });
                            
                            contentInnerDiv.appendChild(mobileCards);
                        }
                        
                        if (sub && sub.type === "paragraph" && sub.text) {
                            const p = document.createElement("p");
                            p.textContent = sub.text;
                            contentInnerDiv.appendChild(p);
                        }
                    });
                } else if (typeof section.content === "object") {
                    // پردازش محتوای ساده
                    if (section.content.type === "list" && section.content.items) {
                        const ul = document.createElement("ul");
                        section.content.items.forEach(item => {
                            const li = document.createElement("li");
                            if (typeof item === "string") {
                                li.textContent = item;
                            } else if (item && item.label && item.value) {
                                li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                            }
                            ul.appendChild(li);
                        });
                        contentInnerDiv.appendChild(ul);
                    }
                    
                    if (section.content.type === "paragraph" && section.content.text) {
                        const p = document.createElement("p");
                        p.textContent = section.content.text;
                        contentInnerDiv.appendChild(p);
                    }
                    
                    if (section.content.type === "nested_list" && section.content.items) {
                        const ul = document.createElement("ul");
                        section.content.items.forEach(item => {
                            const li = document.createElement("li");
                            if (item && item.label && item.value) {
                                li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                            }
                            ul.appendChild(li);
                        });
                        contentInnerDiv.appendChild(ul);
                    }
                    
                    if (section.content.type === "table" && section.content.headers && section.content.rows) {
                        // ایجاد جدول برای دسکتاپ
                        const table = document.createElement("table");
                        const thead = document.createElement("thead");
                        const headRow = document.createElement("tr");
                        
                        section.content.headers.forEach(h => {
                            const th = document.createElement("th");
                            th.textContent = h;
                            headRow.appendChild(th);
                        });
                        
                        thead.appendChild(headRow);
                        table.appendChild(thead);

                        const tbody = document.createElement("tbody");
                        section.content.rows.forEach(row => {
                            const tr = document.createElement("tr");
                            row.forEach(cell => {
                                const td = document.createElement("td");
                                td.textContent = cell;
                                tr.appendChild(td);
                            });
                            tbody.appendChild(tr);
                        });
                        
                        table.appendChild(tbody);
                        contentInnerDiv.appendChild(table);
                        
                        // ایجاد کارت‌های موبایل
                        const mobileCards = document.createElement("div");
                        mobileCards.className = "mobile-cards";
                        
                        section.content.rows.forEach((row, index) => {
                            const dayCard = document.createElement("div");
                            dayCard.className = "day-card";
                            
                            const dayHeader = document.createElement("div");
                            dayHeader.className = "day-header";
                            dayHeader.innerHTML = '<i class="fas fa-calendar-day"></i> <span>' + row[0] + '</span>';
                            dayCard.appendChild(dayHeader);
                            
                            // اضافه کردن وعده‌های غذایی
                            for (let i = 1; i < row.length; i++) {
                                if (row[i]) {
                                    const mealSection = document.createElement("div");
                                    mealSection.className = "meal-section";
                                    
                                    const mealTitle = document.createElement("div");
                                    mealTitle.className = "meal-title";
                                    mealTitle.innerHTML = '<i class="fas fa-' + this.getMealIcon(i) + '"></i> <span>' + section.content.headers[i] + '</span>';
                                    
                                    const mealContent = document.createElement("div");
                                    mealContent.textContent = row[i];
                                    
                                    mealSection.appendChild(mealTitle);
                                    mealSection.appendChild(mealContent);
                                    dayCard.appendChild(mealSection);
                                }
                            }
                            
                            mobileCards.appendChild(dayCard);
                        });
                        
                        contentInnerDiv.appendChild(mobileCards);
                    }
                } else if (typeof section.content === "string") {
                    // اگر محتوا یک رشته ساده است
                    const p = document.createElement("p");
                    p.textContent = section.content;
                    contentInnerDiv.appendChild(p);
                }
            }

            contentDiv.appendChild(contentInnerDiv);
            sectionDiv.appendChild(headerDiv);
            sectionDiv.appendChild(contentDiv);
            container.appendChild(sectionDiv);
        });

        // ایجاد فوتر
        if (data.footer) {
            const footer = document.createElement("div");
            footer.className = "footer";
            footer.textContent = data.footer;
            container.appendChild(footer);
        }
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

    getMealIcon(mealIndex) {
        const icons = {
            1: 'sun', // صبحانه
            2: 'sun', // ناهار
            3: 'moon', // شام
            4: 'apple-alt' // میان وعده
        };
        
        return icons[mealIndex] || 'utensils';
    }
}

// تابع کمکی برای بارگذاری از sessionStorage
function loadDietPlanFromSession(containerId = 'diet-plan-container', options = {}) {
    const resultData = sessionStorage.getItem('diet_form_result');
    if (resultData) {
        try {
            const parsedData = JSON.parse(resultData);
            
            // اگر response وجود دارد، آن را پردازش کن
            let finalData = parsedData;
            if (parsedData.response) {
                try {
                    finalData = JSON.parse(parsedData.response);
                } catch (e) {
                    console.error('Error parsing response data:', e);
                }
            }
            
            const renderer = new DietPlanRenderer({ containerId, ...options });
            renderer.render(finalData);
            return renderer;
        } catch (e) {
            console.error('Error parsing diet plan data:', e);
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> خطا در پردازش داده‌ها: ' + e.message + '</div>';
            }
        }
    } else {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> هیچ داده‌ای در sessionStorage یافت نشد</div>';
        }
    }
    return null;
}

// تابع کمکی برای بارگذاری مستقیم از JSON
function renderDietPlanFromJSON(data, containerId = 'diet-plan-container', options = {}) {
    const renderer = new DietPlanRenderer({ containerId, ...options });
    renderer.render(data);
    return renderer;
}