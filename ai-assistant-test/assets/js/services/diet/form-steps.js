// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-steps.js

window.setupComplexCheckboxSelection = function(step, config) {
    if (state.currentStep !== step) return;

    const elements = {
        noneCheckbox: document.getElementById(config.noneCheckboxId),
        nextButton: document.querySelector(".next-step")
    };

    // ساختاردهی گزینه‌ها
    config.options.forEach(option => {
        elements[option.key] = document.getElementById(option.id);
    });

    // مدیریت نمایش گزینه‌های زنانه
    if (config.genderDependent) {
        const femaleOnlyOptions = document.querySelectorAll('.female-only');
        if (state.formData.userInfo.gender === 'female') {
            femaleOnlyOptions.forEach(el => el.style.display = 'block');
        } else {
            femaleOnlyOptions.forEach(el => {
                el.style.display = 'none';
                const checkbox = el.querySelector('.real-checkbox');
                if (checkbox) checkbox.checked = false;
            });
        }
    }

    elements.nextButton.disabled = true;

    const validateForm = () => {
        let anyChecked = false;
        
        // بررسی انتخاب‌ها
        config.options.forEach(option => {
            if (elements[option.key]?.checked) {
                anyChecked = true;
            }
        });

        if (elements.noneCheckbox.checked) {
            anyChecked = true;
        }

        elements.nextButton.disabled = !anyChecked;
        
        // به‌روزرسانی state
        const selectedValues = [];
        config.options.forEach(option => {
            if (elements[option.key]?.checked) {
                selectedValues.push(option.key);
            }
        });

        if (elements.noneCheckbox.checked) {
            selectedValues.push('none');
        }

        state.updateFormData(config.dataKey, selectedValues);
    };

    const handleCheckboxChange = (checkbox) => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (label) {
                label.classList.add('checked-animation');
                setTimeout(() => {
                    label.classList.remove('checked-animation');
                    label.classList.toggle('checked', this.checked);
                }, 800);
            }
            validateForm();
        });
    };

    // مدیریت چک‌باکس "هیچکدام"
    elements.noneCheckbox.addEventListener('change', function() {
        if (this.checked) {
            config.options.forEach(option => {
                if (elements[option.key]) {
                    elements[option.key].checked = false;
                    const label = elements[option.key].nextElementSibling;
                    if (label) label.classList.remove('checked');
                }
            });
        }
        validateForm();
    });

    // مدیریت سایر چک‌باکس‌ها
    config.options.forEach(option => {
        if (elements[option.key]) {
            handleCheckboxChange(elements[option.key]);
            elements[option.key].addEventListener('change', function() {
                if (this.checked) {
                    elements.noneCheckbox.checked = false;
                    const label = elements.noneCheckbox.nextElementSibling;
                    if (label) label.classList.remove('checked');
                }
                validateForm();
            });
        }
    });

    validateForm();
};

window.setupActivitySelection = function(currentStep) {
    if (currentStep !== STEPS.ACTIVITY) return;

    const activityOptions = document.querySelectorAll('.activity-option');
    
    activityOptions.forEach(option => {
        option.addEventListener('click', function() {
            // حذف انتخاب از همه گزینه‌ها
            activityOptions.forEach(opt => {
                opt.classList.remove('selected');
                opt.style.transform = "";
                opt.style.boxShadow = "";
            });
            
            // انتخاب گزینه کلیک شده
            this.classList.add('selected');
            this.style.transform = "translateY(-3px)";
            this.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
            
            // ذخیره داده در state
            state.updateFormData('userInfo.activity', this.dataset.activity);
            
            // فعال کردن دکمه بعدی
            const nextButton = document.querySelector(".next-step");
            if (nextButton) nextButton.disabled = false;
        });
    });
    
    // اگر قبلاً activity انتخاب شده بود، آن را highlight کن
    if (state.formData.userInfo.activity) {
        const selectedOption = document.querySelector(`.activity-option[data-activity="${state.formData.userInfo.activity}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
            selectedOption.style.transform = "translateY(-3px)";
            selectedOption.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
        }
    }
};

window.setupSurgerySelection = function(currentStep) {
    if (state.currentStep !== currentStep) return;

    // تنظیم انتخاب‌های اصلی جراحی
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'surgery-none',
        dataKey: 'surgery',
        genderDependent: true,
        options: [
            { key: 'metabolic', id: 'surgery-metabolic' },
            { key: 'gallbladder', id: 'surgery-gallbladder' },
            { key: 'intestine', id: 'surgery-intestine' },
            { key: 'thyroid', id: 'surgery-thyroid' },
            { key: 'pancreas', id: 'surgery-pancreas' },
            { key: 'heart', id: 'surgery-heart' },
            { key: 'kidney', id: 'surgery-kidney' },
            { key: 'liver', id: 'surgery-liver' },
            { key: 'gynecology', id: 'surgery-gynecology' },
            { key: 'cancer', id: 'cancer-history' }
        ]
    });

    // مدیریت جزئیات سرطان
    setupCancerDetails();
};

window.setupMedicationsSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'medications-none',
        dataKey: 'medications',
        options: [
            { key: 'diabetes', id: 'medication-diabetes' },
            { key: 'thyroid', id: 'medication-thyroid' },
            { key: 'corticosteroids', id: 'medication-corticosteroids' },
            { key: 'anticoagulants', id: 'medication-anticoagulants' },
            { key: 'hypertension', id: 'medication-hypertension' },
            { key: 'psychiatric', id: 'medication-psychiatric' },
            { key: 'hormonal', id: 'medication-hormonal' },
            { key: 'cardiac', id: 'medication-cardiac' },
            { key: 'gastrointestinal', id: 'medication-gastrointestinal' },
            { key: 'supplements', id: 'medication-supplements' }
        ]
    });
};

window.setupChronicConditionsSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'chronic-none',
        dataKey: 'chronicConditions',
        genderDependent: true,
        options: [
            { key: 'diabetes', id: 'chronic-diabetes' },
            { key: 'hypertension', id: 'chronic-hypertension' },
            { key: 'cholesterol', id: 'chronic-cholesterol' },
            { key: 'fattyLiver', id: 'chronic-fatty-liver' },
            { key: 'insulinResistance', id: 'chronic-insulin-resistance' },
            { key: 'hypothyroidism', id: 'chronic-hypothyroidism' },
            { key: 'hyperthyroidism', id: 'chronic-hyperthyroidism' },
            { key: 'hashimoto', id: 'chronic-hashimoto' },
            { key: 'pcos', id: 'chronic-pcos' },
            { key: 'menopause', id: 'chronic-menopause' },
            { key: 'cortisol', id: 'chronic-cortisol' },
            { key: 'growth', id: 'chronic-growth' },
            { key: 'ibs', id: 'chronic-ibs' },
            { key: 'kidney', id: 'chronic-kidney' },
            { key: 'heart', id: 'chronic-heart' },
            { key: 'autoimmune', id: 'chronic-autoimmune' },
            { key: 'gallbladderStones', id: 'chronic-gallbladder-stones' },
            { key: 'gallbladderInflammation', id: 'chronic-gallbladder-inflammation' },
            { key: 'gallbladderIssues', id: 'chronic-gallbladder-issues' }            
        ]
    });
    
    // اضافه کردن event listener برای کنترل تناقض‌ها
    const conflictCheckboxes = [
        'chronic-hyperthyroidism', 'chronic-hypothyroidism', 'chronic-hashimoto',
        'chronic-gallbladder-stones', 'chronic-gallbladder-inflammation', 'chronic-gallbladder-issues'
    ];
    
    conflictCheckboxes.forEach(checkboxId => {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    handleConflictingConditions(checkboxId);
                }
            });
        }
    });
    
    setupChronicDiabetesDetails();
};

window.setupCancerDetails = function() {
    const cancerCheckbox = document.getElementById('cancer-history');
    const cancerDetails = document.getElementById('cancer-details');
    const nextButton = document.querySelector(".next-step");

    if (!cancerCheckbox || !cancerDetails) return;

    // مدیریت نمایش/مخفی کردن جزئیات سرطان
    cancerCheckbox.addEventListener('change', function() {
        cancerDetails.style.display = this.checked ? 'block' : 'none';
        
        // اگر سرطان انتخاب نشد، اطلاعات سرطان را پاک کنید
        if (!this.checked) {
            state.updateFormData('userInfo.cancerTreatment', '');
            state.updateFormData('userInfo.cancerType', '');
            resetCancerSelections();
        }
        
        // به‌روزرسانی وضعیت دکمه
        validateNextButton();
    });

    // مدیریت انتخاب وضعیت درمان و نوع سرطان
    const cancerOptions = document.querySelectorAll('.cancer-option[data-value]');
    cancerOptions.forEach(option => {
        option.addEventListener('click', function() {
            const category = this.closest('.cancer-options');
            if (!category) return;

            // فقط یک گزینه در هر دسته می‌تواند انتخاب شود
            category.querySelectorAll('.cancer-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            this.classList.add('selected');

            // تشخیص نوع داده (درمان یا نوع سرطان)
            const isTreatment = category.querySelector('.cancer-option[data-value="chemo"]');
            if (isTreatment) {
                state.updateFormData('userInfo.cancerTreatment', this.dataset.value);
            } else {
                state.updateFormData('userInfo.cancerType', this.dataset.value);
            }
            
            // به‌روزرسانی وضعیت دکمه
            validateNextButton();
        });
    });

    function validateNextButton() {
        if (cancerCheckbox.checked) {
            const hasTreatment = state.formData.userInfo.cancerTreatment !== '';
            const hasType = state.formData.userInfo.cancerType !== '';
            
            // اگر سرطان انتخاب شده، باید هر دو فیلد پر شوند
            nextButton.disabled = !(hasTreatment && hasType);
        } else {
            // اگر سرطان انتخاب نشده، وضعیت دکمه توسط تابع اصلی مدیریت می‌شود
            const surgeryConfig = {
                noneCheckboxId: 'surgery-none',
                options: [
                    { key: 'metabolic', id: 'surgery-metabolic' },
                    { key: 'gallbladder', id: 'surgery-gallbladder' },
                    { key: 'intestine', id: 'surgery-intestine' },
                    { key: 'thyroid', id: 'surgery-thyroid' },
                    { key: 'pancreas', id: 'surgery-pancreas' },
                    { key: 'heart', id: 'surgery-heart' },
                    { key: 'kidney', id: 'surgery-kidney' },
                    { key: 'liver', id: 'surgery-liver' },
                    { key: 'gynecology', id: 'surgery-gynecology' },
                    { key: 'cancer', id: 'cancer-history' }
                ]
            };
            
            const noneChecked = document.getElementById(surgeryConfig.noneCheckboxId).checked;
            const anyOtherChecked = surgeryConfig.options.some(option => {
                if (option.key === 'cancer') return false; // سرطان جداگانه بررسی می‌شود
                const element = document.getElementById(option.id);
                return element ? element.checked : false;
            });
            
            nextButton.disabled = !(noneChecked || anyOtherChecked);
        }
    }

    function resetCancerSelections() {
        document.querySelectorAll('.cancer-option.selected').forEach(opt => {
            opt.classList.remove('selected');
        });
    }

    // بررسی اولیه
    validateNextButton();
};

window.setupDigestiveConditionsSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'digestive-none',
        dataKey: 'digestiveConditions',
        options: [
            // بیماری‌های ساختاری
            { key: 'ibs', id: 'digestive-ibs' },
            { key: 'ibd', id: 'digestive-ibd' },
            { key: 'gerd', id: 'digestive-gerd' },
            
            // علائم عملکردی
            { key: 'bloating', id: 'digestive-bloating' },
            { key: 'pain', id: 'digestive-pain' },
            { key: 'heartburn', id: 'digestive-heartburn' },
            { key: 'constipation', id: 'digestive-constipation' },
            { key: 'diarrhea', id: 'digestive-diarrhea' },
            { key: 'fullness', id: 'digestive-fullness' },
            { key: 'nausea', id: 'digestive-nausea' },
            { key: 'slow-digestion', id: 'digestive-slow-digestion' },
            { key: 'indigestion', id: 'digestive-indigestion' },
            
            // عفونت‌ها و مشکلات خاص
            { key: 'helicobacter', id: 'digestive-helicobacter' },
        ]
    });
};

window.setupDietStyleSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'diet-style-none',
        dataKey: 'dietStyle',
        options: [
            { key: 'vegetarian', id: 'diet-style-vegetarian' },
            { key: 'vegan', id: 'diet-style-vegan' }
        ]
    });
};

window.setupFoodLimitationsSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'limitations-none',
        dataKey: 'foodLimitations',
        options: [
            // محدودیت‌های پزشکی
            { key: 'celiac', id: 'limitation-celiac' },
            { key: 'lactose', id: 'limitation-lactose' },
            { key: 'seafood-allergy', id: 'limitation-seafood-allergy' },
            { key: 'eggs-allergy', id: 'limitation-eggs-allergy' },
            { key: 'nuts-allergy', id: 'limitation-nuts-allergy' },
            
            // ترجیحات شخصی
            { key: 'no-seafood', id: 'limitation-no-seafood' },
            { key: 'no-redmeat', id: 'limitation-no-redmeat' },
            { key: 'no-dairy', id: 'limitation-no-dairy' }
        ]
    });
};

window.setupWaterIntakeSelection = function(currentStep) {
    if (currentStep !== STEPS.WATER_INTAKE) return;

    const waterCups = document.querySelectorAll('.water-cup');
    const waterAmountDisplay = document.getElementById('water-amount');
    const waterLiterDisplay = document.getElementById('water-liter');
    const waterAmountText = document.getElementById('water-amount-text');
    const dontKnowCheckbox = document.getElementById('water-dont-know');
    const dontKnowText = document.getElementById('water-dont-know-text');
    const nextButton = document.querySelector('.next-step');
    
    nextButton.disabled = true;

    const updateNextButtonState = () => {
        const hasSelection = document.querySelector('.water-cup.selected') !== null;
        const isDontKnowChecked = dontKnowCheckbox.checked;
        nextButton.disabled = !(hasSelection || isDontKnowChecked);
    };

    const updateWaterDisplay = (amount, isDontKnow = false) => {
        if (isDontKnow) {
            waterAmountText.style.display = 'none';
            dontKnowText.style.display = 'block';
            state.updateFormData('userInfo.waterIntake', null);
        } else {
            waterAmountDisplay.textContent = amount;
            waterLiterDisplay.textContent = (amount * 0.25).toFixed(1); // محاسبه لیتر (هر لیوان 250 سی‌سی)
            waterAmountText.style.display = 'flex';
            dontKnowText.style.display = 'none';
            state.updateFormData('userInfo.waterIntake', amount);
        }
        updateNextButtonState();
    };

    waterCups.forEach((cup, index) => {
        cup.addEventListener('click', function() {
            dontKnowCheckbox.checked = false;
            document.querySelector('.stand-alone-none .checkbox-label').classList.remove('checked');
            
            const amount = parseInt(this.dataset.amount);
            
            // Reset all cups
            waterCups.forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.water-wave')?.remove();
            });
            
            // Select cups up to clicked amount
            for (let i = 0; i < amount; i++) {
                waterCups[i].classList.add('selected');
            }
            
            updateWaterDisplay(amount);
        });
    });

    dontKnowCheckbox.addEventListener('change', function() {
        const label = this.nextElementSibling;
        if (this.checked) {
            label.classList.add('checked-animation');
            setTimeout(() => {
                label.classList.remove('checked-animation');
                label.classList.add('checked');
            }, 800);
            
            // Reset all cups
            waterCups.forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.water-wave')?.remove();
            });
            
            updateWaterDisplay(0, true);
        } else {
            label.classList.remove('checked');
            updateWaterDisplay(0, false);
            updateNextButtonState();
        }
    });

    // بررسی اولیه وضعیت دکمه
    updateNextButtonState();
};

window.setupTermsAgreement = function(currentStep) {
    if (currentStep !== STEPS.TERMS_AGREEMENT) return;

    const nextButton = document.querySelector(".next-step");
    const agreeCheckbox = document.getElementById("agree-terms");
    
    // Reset state
    agreeCheckbox.checked = false;
    nextButton.disabled = true;

    agreeCheckbox.addEventListener("change", function() {
        const label = this.nextElementSibling;
        
        if (this.checked) {
            label.classList.add("checked-animation");
            setTimeout(() => {
                label.classList.remove("checked-animation");
                label.classList.add("checked");
            }, 800);
        } else {
            label.classList.remove("checked");
        }
        
        nextButton.disabled = !this.checked;
    });
}

window.setupConfirmationCheckbox = function(currentStep) {
    const submitButton = document.querySelector(".submit-form");
    const confirmCheckbox = document.getElementById("confirm-info");
    
    if (currentStep !== STEPS.CONFIRMATION) return;

    submitButton.disabled = !confirmCheckbox.checked;
    if (confirmCheckbox.checked) {
        confirmCheckbox.nextElementSibling.classList.add("checked");
    }

    const validateForm = () => {
        submitButton.disabled = !confirmCheckbox.checked;
    };

    confirmCheckbox.addEventListener("change", function() {
        const label = this.nextElementSibling;
        
        if (this.checked) {
            label.classList.add("checked-animation");
            setTimeout(() => {
                label.classList.remove("checked-animation");
                label.classList.add("checked");
            }, 800);
        } else {
            label.classList.remove("checked");
        }
        
        validateForm();
    });

    validateForm();
}

// در تابع setupExerciseSelection
window.setupExerciseSelection = function(currentStep) {
    if (currentStep !== STEPS.EXERCISE) return;

    const exerciseOptions = document.querySelectorAll('.exercise-option');
    
    // اگر قبلاً ورزشی انتخاب شده بود، آن را highlight کن
    if (state.formData.userInfo.exercise) {
        const selectedOption = document.querySelector(`.exercise-option[data-exercise="${state.formData.userInfo.exercise}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
            selectedOption.style.transform = "translateY(-3px)";
            selectedOption.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
        }
    }
    
    exerciseOptions.forEach(option => {
        option.addEventListener('click', function() {
            // حذف انتخاب از همه گزینه‌ها
            exerciseOptions.forEach(opt => {
                opt.classList.remove('selected');
                opt.style.transform = "";
                opt.style.boxShadow = "";
            });
            
            // انتخاب گزینه کلیک شده
            this.classList.add('selected');
            this.classList.add('selected-with-effect');
            state.updateFormData('userInfo.exercise', this.dataset.exercise);
            // افکت بصری
            setTimeout(() => {
                this.classList.remove('selected-with-effect');
                this.style.transform = "translateY(-3px)";
                this.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
                
                // ذخیره داده
                // state.updateFormData('userInfo.exercise', this.dataset.exercise);
                
            }, 150);
        });
    });
};

window.showStep = function(step) {
    const stepElements = [
        "gender-selection-step",        // 1
        "personal-info-step",           // 2
        "goal-selection-step",          // 3
        "height-input-step",            // 5
        "weight-input-step",            // 6
        "target-weight-step",           // 7
        "goal-weight-display",          // 8
        "chronic-conditions-step",      // 9
        "medications-step",             // 10
        "digestive-conditions-step",    // 11
        "surgery-step",                 // 12
        "water-intake-step",            // 13
        "activity-selection-step",      // 14
        "exercise-activity-step",       // 15
        "diet-style-step",              // 16
        "food-limitations-step",        // 17
        "favorite-foods-step",          // 18
        "diet-type-selection-step",     // 19
        "terms-agreement-step",         // 20
        "confirm-submit-step"           // 21
    ];
    
    document.querySelectorAll(".step").forEach(el => {
        el.classList.remove("active");
        if (el.id === "goal-weight-display") {
            el.style.display = 'none';
            if (!el.classList.contains("active")) {
                el.querySelector('.step7-image-container').innerHTML = '';
            }
        }
    });
    
    const currentStepElement = document.getElementById(stepElements[step - 1]);
    if (currentStepElement) {
        currentStepElement.classList.add("active");
        if (currentStepElement.id === "goal-weight-display") {
            currentStepElement.style.display = 'flex';
        }
    }

    if (step === STEPS.GOAL_DISPLAY) {
        const goalTitleElement = document.getElementById('goal-title-text');
        if (goalTitleElement) {
            const goalText = {
                "weight-loss": "هدف: کاهش وزن",
                "weight-gain": "هدف: افزایش وزن", 
                "fitness": "هدف: حفظ سلامت"
            }[state.formData.userInfo.goal];
            
            goalTitleElement.textContent = goalText || "هدف: مشخص نشده";
        }
    
        const imageContainer = document.querySelector('#goal-weight-display .step7-image-container');
        let svgFile = '';
        
        if (state.formData.userInfo.goal === 'weight-loss') {
            svgFile = wpVars.themeBasePath + '/assets/images/svg/weight-loss.svg';
        } else if (state.formData.userInfo.goal === 'weight-gain' || state.formData.userInfo.goal === 'fitness') {
            svgFile = wpVars.themeBasePath + '/assets/images/svg/weight-gain.svg';
        }
        
        imageContainer.innerHTML = `
            <div class="goal-title-container">
                <h2 class="goal-title" id="goal-title-text">
                    ${state.formData.userInfo.goal === 'weight-loss' ? 'کاهش وزن' : 
                      state.formData.userInfo.goal === 'weight-gain' ? 'افزایش وزن' : 
                      'حفظ سلامت'}
                </h2>
            </div>
            <object type="image/svg+xml" data="${svgFile}" class="goal-svg"></object>
            <div class="weight-display-container">
                <div class="weight-display-box target-weight">
                    <div class="weight-value">${state.formData.userInfo.targetWeight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن هدف</div>
                </div>
                <div class="weight-display-box current-weight">
                    <div class="weight-value">${state.formData.userInfo.weight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن فعلی</div>
                </div>
            </div>
        `;
    }
    
    // مدیریت نمایش دکمه بعدی
    const nextButtonContainer = document.getElementById("next-button-container");
    if (nextButtonContainer) {
        // مخفی کردن دکمه "گام بعد" در مراحل خاص
        const hideNextButtonSteps = [
            STEPS.GENDER, 
            STEPS.GOAL,
            STEPS.WATER_INTAKE,
            STEPS.ACTIVITY, 
            STEPS.EXERCISE
        ];
        
        nextButtonContainer.style.display = hideNextButtonSteps.includes(step) ? "none" : "block";
        
        // مخفی کردن دکمه در مرحله آخر اصلی
        if (step === totalSteps) { 
            nextButtonContainer.style.display = "none";
        }
    }

    // مدیریت نمایش دکمه ارسال
    const submitButtonContainer = document.getElementById("submit-button-container");
    if (submitButtonContainer) {
        // نمایش دکمه ارسال فقط در مرحله تأیید نهایی
        submitButtonContainer.style.display = (step === STEPS.CONFIRMATION) ? "block" : "none";
    }
    
    if ([STEPS.PERSONAL_INFO, STEPS.HEIGHT, STEPS.WEIGHT, STEPS.TARGET_WEIGHT].includes(step)) {
        const inputId = `${["first-name", "last-name", "height", "weight", "target-weight"][step - 2]}-input`;
        const inputElement = document.getElementById(inputId);
        if (inputElement) inputElement.focus();
        
        const nextButton = document.querySelector(".next-step");
        if (nextButton) nextButton.disabled = true;
        
        validateStep(step);
    }    
    
    if (step === STEPS.WATER_INTAKE) {
        setupWaterIntakeSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    if (step === STEPS.DIGESTIVE_CONDITIONS) {
        setupDigestiveConditionsSelection(step);
    }
    else if (step === STEPS.SURGERY) {
        setupSurgerySelection(step);
    }
    else if (step === STEPS.EXERCISE) {
        setupExerciseSelection(step);
    }
    else if (step === STEPS.DIET_STYLE) {
        setupDietStyleSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.CHRONIC_CONDITIONS) {
        setupChronicConditionsSelection(step);
    } 
    else if (step === STEPS.MEDICATIONS) {
        setupMedicationsSelection(step);
    } 
    else if (step === STEPS.ACTIVITY) {
        setupActivitySelection(step);
        document.getElementById("next-button-container").style.display = "none";
    }    
    else if (step === STEPS.FOOD_LIMITATIONS) {
        setupFoodLimitationsSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.FAVORITE_FOODS) {
        setupFavoriteFoodsSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    }  
    else if (step === STEPS.DIET_TYPE_SELECTION) {
        setupDietTypeSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.TERMS_AGREEMENT) {
        setupTermsAgreement(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.CONFIRMATION) {
        showSummary();
        setupConfirmationCheckbox(step);
        document.getElementById("next-button-container").style.display = "none";
        document.getElementById("submit-button-container").style.display = "block";
        
        const confirmCheckbox = document.getElementById("confirm-info");
        const submitButton = document.querySelector(".submit-form");
        if (submitButton) {
            submitButton.disabled = !confirmCheckbox.checked;
        }
    }
}

window.setupFavoriteFoodsSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'foods-none',
        dataKey: 'favoriteFoods',
        options: [
            // غذاهای اصلی ایرانی
            { key: 'gheymeh', id: 'food-gheymeh' },
            { key: 'ghormeh', id: 'food-ghormeh' },
            { key: 'kabab-koobideh', id: 'food-kabab-koobideh' },
            { key: 'joojeh-kabab', id: 'food-joojeh-kabab' },
            { key: 'kabab-barg', id: 'food-kabab-barg' },
            { key: 'fesenjan', id: 'food-fesenjan' },
            { key: 'bademjan', id: 'food-bademjan' },
            { key: 'karafs', id: 'food-karafs' },
            { key: 'aloo-esfenaj', id: 'food-aloo-esfenaj' },
            { key: 'abgoosht', id: 'food-abgoosht' },
            
            // غذاهای بین‌المللی جدید
            { key: 'pizza', id: 'food-pizza' },
            { key: 'burger', id: 'food-burger' },
            { key: 'pasta', id: 'food-pasta' },
            { key: 'sandwich', id: 'food-sandwich' },
            { key: 'salad', id: 'food-salad' },      
            
            // برنج‌های سالم
            { key: 'chelo', id: 'food-chelo' },
            { key: 'sabzi-polo', id: 'food-sabzi-polo' },
            { key: 'adas-polo', id: 'food-adas-polo' },
            { key: 'lobya-polo', id: 'food-lobya-polo' },
            { key: 'shevid-polo', id: 'food-shevid-polo' },
            
            // پیش‌غذاها و مخلفات
            { key: 'salad-shirazi', id: 'food-salad-shirazi' },
            { key: 'mast-o-khiar', id: 'food-mast-o-khiar' },
            { key: 'borani-esfenaj', id: 'food-borani-esfenaj' },
            { key: 'borani-bademjan', id: 'food-borani-bademjan' },
            { key: 'nokhod-kishmesh', id: 'food-nokhod-kishmesh' },
            
            // غذاهای سنتی
            { key: 'ash-reshteh', id: 'food-ash-reshteh' },
            { key: 'ash-jow', id: 'food-ash-jow' },
            { key: 'halim', id: 'food-halim' },
            { key: 'adas', id: 'food-adas' },
            { key: 'lobya', id: 'food-lobya' },
            
            // غذاهای ساده
            { key: 'omelet', id: 'food-omelet' },
            { key: 'nimroo', id: 'food-nimroo' },
            { key: 'egg-tomato', id: 'food-egg-tomato' },
            { key: 'kookoo-sabzi', id: 'food-kookoo-sabzi' },
            { key: 'kookoo-sibzamini', id: 'food-kookoo-sibzamini' }
        ]
    });
}

window.updateStepCounter = function(step) {
    if (step <= totalSteps) {
        document.getElementById("current-step").textContent = step;
        document.getElementById("total-steps").textContent = totalSteps;
    }
    else {
        document.getElementById("current-step").textContent = totalSteps;
        document.getElementById("total-steps").textContent = totalSteps;
    }
}

window.updateProgressBar = function(step) {
    let progress;
    
    if (step <= totalSteps) {
        progress = ((step - 1) / (totalSteps - 1)) * 100;
    }
    else {
        progress = 100;
    }
    
    document.getElementById("progress-bar").style.width = `${progress}%`;
}

window.navigateToStep = function(step) {

    const maxMainStep = totalSteps;
    
    if (step >= 1 && step <= maxMainStep) {
        state.updateStep(step);
        history.pushState({ step: state.currentStep }, "", `#step-${state.currentStep}`);
    }
    
    else if (step > maxMainStep && step <= Object.keys(STEPS).length) {
        state.updateStep(step);
        history.pushState({ step: state.currentStep }, "", `#step-${state.currentStep}`);
    }
}

window.handleNextStep = function() {
    if (state.currentStep === totalSteps) { 
        navigateToStep(STEPS.DIET_TYPE_SELECTION); 
    }
    else if (state.currentStep === STEPS.DIET_TYPE_SELECTION) {
        navigateToStep(STEPS.TERMS_AGREEMENT); 
    }
    else if (state.currentStep === STEPS.TERMS_AGREEMENT) {
        navigateToStep(STEPS.CONFIRMATION); 
    }
    // در غیر این صورت به مرحله بعدی اصلی برو
    else if (state.currentStep < totalSteps) {
        navigateToStep(state.currentStep + 1);
    }
}

window.handleBackStep = function() {
    if (state.currentStep > 1) navigateToStep(state.currentStep - 1);
}

window.handleEnterKey = function(event) {
    // فقط در مراحل عددی (سن، قد، وزن، وزن هدف) و مرحله نهایی اجازه کار با Enter را بده
    const allowedSteps = [
        STEPS.HEIGHT, 
        STEPS.WEIGHT, 
        STEPS.TARGET_WEIGHT,
        STEPS.PERSONAL_INFO,
        STEPS.CONFIRMATION
    ];
    
    if (event.key === "Enter" && 
        allowedSteps.includes(state.currentStep) && 
        (event.target.matches("input[type='text']") || state.currentStep === STEPS.CONFIRMATION)) {
        
        // جلوگیری از رفتار پیش‌فرض Enter
        event.preventDefault();
        
        // در مراحل عددی، رفتن به مرحله بعد
        if (state.currentStep !== STEPS.CONFIRMATION) {
            document.querySelector(".next-step").click();
        } 
        // در مرحله نهایی، ارسال فرم
        else {
            const submitButton = document.querySelector(".submit-form:not([disabled])");
            if (submitButton) {
                submitButton.click();
            }
        }
    } else if (event.key === "Enter") {
        // جلوگیری از کار Enter در سایر مراحل
        event.preventDefault();
    }
}

// در تابع setupDietTypeSelection، بعد از انتخاب یک کارت
window.setupDietTypeSelection = function(currentStep) {
    if (currentStep !== STEPS.DIET_TYPE_SELECTION) return;

    const dietTypeCards = document.querySelectorAll('.diet-type-card');
    const nextButton = document.querySelector(".next-step");
    
    nextButton.disabled = true;

    // اعمال استایل اولیه بر روی همه کارت‌ها
    dietTypeCards.forEach(card => {
        card.classList.remove('selected');
        updateCardAppearance(card);
    });

    dietTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            // حذف انتخاب از همه کارت‌ها
            dietTypeCards.forEach(c => {
                c.classList.remove('selected');
                updateCardAppearance(c);
            });
            
            // انتخاب کارت کلیک شده
            this.classList.add('selected');
            updateCardAppearance(this);
            
            const dietType = this.dataset.dietType;
            state.updateFormData('serviceSelection.dietType', dietType);
            
            if (dietType === 'ai-only') {
                state.updateFormData('serviceSelection.selectedSpecialist', null);
                nextButton.disabled = false;
            } else if (dietType === 'with-specialist') {
                openSpecialistPopup();
                console.log('Opening specialist popup, current state:', state.formData);
            }
        });
    });
    
    // تابع برای به‌روزرسانی ظاهر کارت
    function updateCardAppearance(card) {
        if (card.classList.contains('selected')) {
            card.style.transform = "translateY(-5px)";
            card.style.opacity = "1";
            card.style.filter = "grayscale(0)";
        } else {
            card.style.transform = "scale(0.95)";
            card.style.opacity = "0.7";
            card.style.filter = "grayscale(0.3)";
        }
    }
    
    // اگر قبلاً نوع رژیم انتخاب شده بود، آن را highlight کن
    if (state.formData.serviceSelection.dietType) {
        const selectedCard = document.querySelector(`.diet-type-card[data-diet-type="${state.formData.serviceSelection.dietType}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
            updateCardAppearance(selectedCard);
        }
    }
};

// توابع جدید برای مدیریت پاپ‌آپ مشاور
window.openSpecialistPopup = function() {
    const popup = document.getElementById('specialist-popup');
    resetSpecialistPopup();
    popup.style.display = 'flex';
    loadNutritionConsultantsPopup();
};

function resetSpecialistPopup() {
    // پاک کردن انتخاب‌های قبلی در پاپ‌آپ
    document.querySelectorAll('.specialist-card-popup').forEach(card => {
        card.classList.remove('selected');
    });
    
    // مخفی کردن و خالی کردن اطلاعات متخصص انتخاب شده
    const specialistInfo = document.getElementById('selected-specialist-info');
    const specialistDetails = document.getElementById('specialist-details');
    
    specialistInfo.style.display = 'none';
    specialistDetails.innerHTML = '';
    
    // غیرفعال کردن دکمه تأیید
    const confirmBtn = document.querySelector('.popup-confirm-btn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
    }
    
    // اگر می‌خواهید state هم ریست شود (اختیاری):
    state.updateFormData('serviceSelection.selectedSpecialist', null);
}

window.closeSpecialistPopup = function() {
    console.log('closeSpecialistPopup is called');
    const popup = document.getElementById('specialist-popup');
    popup.style.display = 'none';
    // حذف انتخاب مشاور در صورت انصراف
    // state.updateFormData('selectedSpecialist', null);
    console.log('selectedSpecialist: ' + state.formData.serviceSelection.selectedSpecialist);
    // غیرفعال کردن دکمه مرحله بعد
    const nextButton = document.querySelector(".next-step");
    if (!state.formData.serviceSelection.selectedSpecialist) {
        nextButton.disabled = true;
    }
};

window.confirmSpecialistSelection = function() {
    if (state.formData.serviceSelection.selectedSpecialist) {
        console.log('Confirming specialist:', state.formData.serviceSelection.selectedSpecialist);
        closeSpecialistPopup();
        // فعال کردن دکمه مرحله بعد
        const nextButton = document.querySelector(".next-step");
        nextButton.disabled = false;
        
        // به روزرسانی نوع رژیم
        state.updateFormData('serviceSelection.dietType', 'with-specialist');
    } else {
        console.error('No specialist selected');
        alert('لطفاً یک متخصص را انتخاب کنید');
    }
};

// بارگذاری مشاورین در پاپ‌آپ
function loadNutritionConsultantsPopup() {
    const specialistSelection = document.getElementById('specialist-selection-popup');
    
    resetSpecialistPopup();
    
    specialistSelection.innerHTML = `
        <div class="loading-specialists">
            <div class="loading-spinner"></div>
            <p>در حال بارگذاری لیست متخصصین...</p>
        </div>
    `;
    
    fetch(aiAssistantVars.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'get_nutrition_consultants',
            'security': aiAssistantVars.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            specialistSelection.innerHTML = '';
            
            if (data.data.consultants && data.data.consultants.length > 0) {
                // در تابع loadNutritionConsultantsPopup - بخش ایجاد کارت مشاور
                data.data.consultants.forEach(consultant => {
                    const specialistCard = document.createElement('div');
                    specialistCard.className = 'specialist-card-popup';
                    specialistCard.dataset.specialistId = consultant.id;
                    specialistCard.innerHTML = `
                        <div class="specialist-info-popup">
                            <div class="specialist-name-popup">${consultant.name}</div>
                            <div class="specialist-specialty-popup">${consultant.specialty}</div>
                            <div class="specialist-price-popup">+${new Intl.NumberFormat('fa-IR').format(consultant.consultation_price)} تومان</div>
                        </div>
                        <button type="button" class="select-specialist-btn-popup" onclick="selectSpecialistInPopup(${consultant.id}, '${consultant.name.replace(/'/g, "\\'")}', '${consultant.specialty.replace(/'/g, "\\'")}', ${consultant.consultation_price})">
                            انتخاب
                        </button>
                    `;
                    specialistSelection.appendChild(specialistCard);
                });
            } else {
                specialistSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">هیچ متخصص فعالی یافت نشد</div>';
            }
        } else {
            specialistSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #f44336;">خطا در بارگذاری لیست متخصصین</div>';
        }
    })
    .catch(error => {
        console.error('Error loading consultants:', error);
        specialistSelection.innerHTML = '<div style="text-align: center; padding: 20px; color: #f44336;">خطا در ارتباط با سرور</div>';
    });
}

// انتخاب مشاور در پاپ‌آپ
window.selectSpecialistInPopup = function(specialistId, specialistName, specialty, consultationPrice) {
    // حذف انتخاب از همه کارت‌ها
    document.querySelectorAll('.specialist-card-popup').forEach(card => {
        card.classList.remove('selected');
    });
    
    // انتخاب کارت فعلی
    const selectedCard = document.querySelector(`.specialist-card-popup[data-specialist-id="${specialistId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    // ذخیره در state
    state.updateFormData('serviceSelection.selectedSpecialist', {
        id: parseInt(specialistId),
        name: specialistName,
        specialty: specialty,
        consultation_price: parseInt(consultationPrice)
    });
    
    // نمایش اطلاعات مشاور انتخاب شده
    const specialistInfo = document.getElementById('selected-specialist-info');
    const specialistDetails = document.getElementById('specialist-details');
    
    specialistDetails.innerHTML = `
        <div><strong>${specialistName}</strong></div>
        <div style="color: #666; font-size: 0.9em; margin: 5px 0;">${specialty}</div>
        <div style="color: #4CAF50; font-weight: bold; font-size: 0.9em;">
            +${new Intl.NumberFormat('fa-IR').format(consultationPrice)} تومان هزینه تأیید متخصص
        </div>
    `;
    specialistInfo.style.display = 'block';
    
    // فعال کردن دکمه تأیید در پاپ‌آپ
    const confirmBtn = document.querySelector('.popup-confirm-btn');
    confirmBtn.disabled = false;
    
    console.log('Specialist selected:', state.formData.serviceSelection.selectedSpecialist);
};