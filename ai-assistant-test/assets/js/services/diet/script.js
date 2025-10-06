// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/script.js
window.state = {
    currentStep: 1,
    formData: {},
    
    updateStep(step) {
        this.currentStep = step;
        window.showStep(step);
        window.updateStepCounter(step);
        window.updateProgressBar(step);
        
        window.dispatchEvent(new CustomEvent('stateUpdated'));
    },
    
    updateFormData(key, value) {
        this.formData[key] = value;
        window.validateStep(this.currentStep);
    },
    
    updateFormElementsFromState() {
        // تاخیر برای اطمینان از لود کامل DOM
        setTimeout(() => {
            // به روزرسانی جنسیت
            if (this.formData.gender) {
                const genderOption = document.querySelector(`.gender-option[data-gender="${this.formData.gender}"]`);
                if (genderOption) genderOption.classList.add('selected');
            }
        
            if (this.formData.firstName) {
                const firstNameInput = document.getElementById('first-name-input');
                if (firstNameInput) {
                    firstNameInput.value = this.formData.firstName;
                    firstNameInput.dispatchEvent(new Event('input'));
                }
            }
            
            if (this.formData.lastName) {
                const lastNameInput = document.getElementById('last-name-input');
                if (lastNameInput) {
                    lastNameInput.value = this.formData.lastName;
                    lastNameInput.dispatchEvent(new Event('input'));
                }
            }    
            
            // به روزرسانی هدف
            if (this.formData.goal) {
                const goalOption = document.querySelector(`.goal-option[data-goal="${this.formData.goal}"]`);
                if (goalOption) goalOption.classList.add('selected');
            }
    
            // به روزرسانی فیلدهای عددی
            const numberFields = {
                'age-input': 'age',
                'height-input': 'height',
                'weight-input': 'weight',
                'target-weight-input': 'targetWeight'
            };
            
            Object.entries(numberFields).forEach(([id, key]) => {
                if (this.formData[key]) {
                    const input = document.getElementById(id);
                    if (input) {
                        input.value = this.formData[key];
                        // تریگر رویداد input برای به روزرسانی نمایش
                        input.dispatchEvent(new Event('input'));
                    }
                }
            });
    
            // به روزرسانی چک‌باکس‌ها
            const checkboxGroups = {
                'surgery': { prefix: 'surgery', items: this.formData.surgery || [] },
                'dietStyle': { prefix: 'diet-style', items: this.formData.dietStyle || [] },
                'foodLimitations': { prefix: 'limitation', items: this.formData.foodLimitations || [] },
                'digestiveConditions': { prefix: 'digestive', items: this.formData.digestiveConditions || [] },
                'chronicConditions': { prefix: 'chronic', items: this.formData.chronicConditions || [] }
            };
    
            Object.entries(checkboxGroups).forEach(([groupKey, groupData]) => {
                const { prefix, items } = groupData;
                
                items.forEach(item => {
                    // ساخت ID بر اساس پیشوند و آیتم
                    const checkboxId = `${prefix}-${item}`;
                    const checkbox = document.getElementById(checkboxId);
                    
                    if (checkbox) {
                        checkbox.checked = true;
                        // اعمال استایل‌های بصری
                        const label = checkbox.nextElementSibling;
                        if (label) label.classList.add('checked');
                    }
                    // مدیریت مورد خاص برای 'none'
                    else if (item === 'none') {
                        const noneCheckboxId = `${prefix}s-none`; // مثل limitations-none
                        const noneCheckbox = document.getElementById(noneCheckboxId);
                        if (noneCheckbox) {
                            noneCheckbox.checked = true;
                            const label = noneCheckbox.nextElementSibling;
                            if (label) label.classList.add('checked');
                        }
                    }
                });
            });
            
            // به روزرسانی فعالیت
            if (this.formData.activity) {
                const activityOption = document.querySelector(`.activity-option[data-activity="${this.formData.activity}"]`);
                if (activityOption) activityOption.classList.add('selected');
            }
                
            if (this.formData.exercise) {
                const exerciseOption = document.querySelector(`.exercise-option[data-exercise="${this.formData.exercise}"]`);
                if (exerciseOption) exerciseOption.classList.add('selected');
            }            
            
            // به روزرسانی مصرف آب
            if (this.formData.waterIntake !== undefined && this.formData.waterIntake !== null) {
                const waterCups = document.querySelectorAll('.water-cup');
                const waterAmount = this.formData.waterIntake;
                
                // انتخاب لیوان‌ها
                waterCups.forEach((cup, index) => {
                    const cupAmount = parseInt(cup.dataset.amount);
                    if (cupAmount <= waterAmount) {
                        cup.classList.add('selected');                    
                    }
                });
    
                // به روزرسانی نمایش مقدار آب
                const waterAmountDisplay = document.getElementById('water-amount');
                const waterLiterDisplay = document.getElementById('water-liter');
                if (waterAmountDisplay && waterLiterDisplay) {
                    waterAmountDisplay.textContent = waterAmount;
                    waterLiterDisplay.textContent = (waterAmount * 0.25).toFixed(1);
                }
                
                // غیرفعال کردن گزینه "نمی‌دانم"
                const dontKnowCheckbox = document.getElementById('water-dont-know');
                if (dontKnowCheckbox) {
                    dontKnowCheckbox.checked = false;
                    dontKnowCheckbox.nextElementSibling.classList.remove('checked');
                }
            }   
            
            // به روزرسانی چک‌باکس شرایط و قوانین
            const confirmTermsCheckbox = document.getElementById('confirm-terms');
            if (confirmTermsCheckbox) {
                confirmTermsCheckbox.checked = true;
                // تریگر رویداد change برای اعمال استایل
                confirmTermsCheckbox.dispatchEvent(new Event('change'));
                
                // فعال کردن گزینه‌های جنسیت
                document.querySelectorAll('.gender-option').forEach(option => {
                    option.style.opacity = "1";
                    option.style.pointerEvents = "auto";
                    option.style.filter = "none";
                });
            }
        }, 300);
    }
};

window.THEME = {
    init: function() {
        this.loadTheme();
        this.setupToggleButton();
    },
    
    loadTheme: function() {
        const savedTheme = localStorage.getItem('diet-theme') || 'light';
        document.body.classList.toggle('dark-mode', savedTheme === 'dark');
    },
    
    toggleTheme: function() {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('diet-theme', isDark ? 'dark' : 'light');
        this.updateToggleIcon();
    },
    
    setupToggleButton: function() {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'theme-toggle';
        toggleBtn.title = 'تغییر تم';
        toggleBtn.innerHTML = `
            <svg class="moon-icon" viewBox="0 0 24 24">
                <path d="M12,3c-4.97,0-9,4.03-9,9s4.03,9,9,9s9-4.03,9-9c0-0.46-0.04-0.92-0.1-1.36c-0.98,1.37-2.58,2.26-4.4,2.26 c-2.98,0-5.4-2.42-5.4-5.4c0-1.81,0.89-3.42,2.26-4.4C12.92,3.04,12.46,3,12,3L12,3z"/>
            </svg>        
            <svg class="sun-icon" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="7" fill="#FFD700"/>
                <line x1="12" y1="0.5" x2="12" y2="3.5" stroke="#FFD700" stroke-width="2"/>
                <line x1="12" y1="20.5" x2="12" y2="23.5" stroke="#FFD700" stroke-width="2"/>
                <line x1="0.5" y1="12" x2="3.5" y2="12" stroke="#FFD700" stroke-width="2"/>
                <line x1="20.5" y1="12" x2="23.5" y2="12" stroke="#FFD700" stroke-width="2"/>
                <line x1="3.8" y1="3.8" x2="6.2" y2="6.2" stroke="#FFD700" stroke-width="2"/>
                <line x1="17.8" y1="17.8" x2="20.2" y2="20.2" stroke="#FFD700" stroke-width="2"/>
                <line x1="3.8" y1="20.2" x2="6.2" y2="17.8" stroke="#FFD700" stroke-width="2"/>
                <line x1="17.8" y1="6.2" x2="20.2" y2="3.8" stroke="#FFD700" stroke-width="2"/>
            </svg>    
        `;
        
        toggleBtn.addEventListener('click', () => this.toggleTheme());
        document.body.appendChild(toggleBtn);
        this.updateToggleIcon();
    },
    
    updateToggleIcon: function() {
        const isDark = document.body.classList.contains('dark-mode');
        const toggleBtn = document.querySelector('.theme-toggle');
        if (toggleBtn) {
            toggleBtn.title = isDark ? 'تغییر به تم روشن' : 'تغییر به تم تاریک';
        }
    }
};

// فراخوانی در هنگام لود صفحه
document.addEventListener('DOMContentLoaded', () => {
    THEME.init();
});

window.CONSTANTS = {
    MIN_AGE: 18,
    MAX_AGE: 80,
    MIN_HEIGHT: 90,
    MAX_HEIGHT: 250,
    MIN_WEIGHT: 35,
    MAX_WEIGHT: 180
};

window.STEPS = {
    GENDER: 1,
    PERSONAL_INFO: 2,
    GOAL: 3,
    AGE: 4,
    HEIGHT: 5,
    WEIGHT: 6,
    TARGET_WEIGHT: 7,
    GOAL_DISPLAY: 8,
    CHRONIC_CONDITIONS: 9,        // بیماری‌های مزمن اصلی
    DIGESTIVE_CONDITIONS: 10,      // مرحله جدید - مشکلات گوارشی و عدم تحمل‌ها
    SURGERY: 11,                   // جابجایی به بعد
    WATER_INTAKE: 12,              // جابجایی به بعد
    ACTIVITY: 13,                  // جابجایی به بعد
    EXERCISE: 14,                  // جابجایی به بعد
    DIET_STYLE: 15,                // جابجایی به بعد
    FOOD_LIMITATIONS: 16,          // جابجایی به بعد
    TERMS_AGREEMENT: 17,
    CONFIRMATION: 18
};

window.totalSteps = Object.keys(STEPS).length;