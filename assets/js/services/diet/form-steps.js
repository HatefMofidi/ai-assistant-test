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
        if (state.formData.gender === 'female') {
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

window.setupSurgerySelection = function(currentStep) {
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
            { key: 'gynecology', id: 'surgery-gynecology' },
            { key: 'kidney', id: 'surgery-kidney' },
            { key: 'liver', id: 'surgery-liver' },
            { key: 'heart', id: 'surgery-heart' }
        ]
    });
};

window.setupHormonalSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'hormonal-none',
        dataKey: 'hormonal',
        genderDependent: true,
        options: [
            { key: 'hypothyroidism', id: 'hormonal-hypothyroidism' },
            { key: 'hyperthyroidism', id: 'hormonal-hyperthyroidism' },
            { key: 'diabetes', id: 'hormonal-diabetes' },
            { key: 'insulin-resistance', id: 'hormonal-insulin-resistance' },
            { key: 'pcos', id: 'hormonal-pcos' },
            { key: 'menopause', id: 'hormonal-menopause' },
            { key: 'cortisol', id: 'hormonal-cortisol' },
            { key: 'growth', id: 'hormonal-growth' }
        ]
    });
};

window.setupStomachDiscomfortSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'stomach-none',
        dataKey: 'stomachDiscomfort',
        options: [
            { key: 'bloating', id: 'stomach-bloating' },
            { key: 'pain', id: 'stomach-pain' },
            { key: 'heartburn', id: 'stomach-heartburn' },
            { key: 'nausea', id: 'stomach-nausea' },
            { key: 'indigestion', id: 'stomach-indigestion' },
            { key: 'constipation', id: 'stomach-constipation' },
            { key: 'diarrhea', id: 'stomach-diarrhea' },
            { key: 'food-intolerance', id: 'stomach-food-intolerance' },
            { key: 'acid-reflux', id: 'stomach-acid-reflux' },
            { key: 'slow-digestion', id: 'stomach-slow-digestion' },
            { key: 'fullness', id: 'stomach-fullness' }
        ]
    });
};

window.setupAdditionalInfoSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'info-none',
        dataKey: 'additionalInfo',
        options: [
            { key: 'diabetes', id: 'info-diabetes' },
            { key: 'hypertension', id: 'info-hypertension' },
            { key: 'cholesterol', id: 'info-cholesterol' },
            { key: 'ibs', id: 'info-ibs' },
            { key: 'celiac', id: 'info-celiac' },
            { key: 'lactose', id: 'info-lactose' },
            { key: 'food-allergy', id: 'info-food-allergy' }
        ]
    });
};

window.setupDietStyleSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'diet-style-none',
        dataKey: 'dietStyle',
        options: [
            { key: 'vegetarian', id: 'diet-style-vegetarian' },
            { key: 'vegan', id: 'diet-style-vegan' },
            { key: 'halal', id: 'diet-style-halal' }
        ]
    });
};

window.setupFoodLimitationsSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'limitations-none',
        dataKey: 'foodLimitations',
        options: [
            { key: 'no-seafood', id: 'limitation-no-seafood' },
            { key: 'no-redmeat', id: 'limitation-no-redmeat' },
            { key: 'no-pork', id: 'limitation-no-pork' },
            { key: 'no-gluten', id: 'limitation-no-gluten' },
            { key: 'no-dairy', id: 'limitation-no-dairy' },
            { key: 'no-eggs', id: 'limitation-no-eggs' },
            { key: 'no-nuts', id: 'limitation-no-nuts' }
        ]
    });
};

window.setupFoodPreferencesSelection = function(currentStep) {
    setupComplexCheckboxSelection(currentStep, {
        noneCheckboxId: 'preferences-none',
        dataKey: 'foodPreferences',
        options: [
            { key: 'low-carb', id: 'preference-lowcarb' },
            { key: 'low-fat', id: 'preference-lowfat' },
            { key: 'high-protein', id: 'preference-highprotein' },
            { key: 'organic', id: 'preference-organic' }
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
            state.updateFormData('waterIntake', null);
        } else {
            waterAmountDisplay.textContent = amount;
            waterLiterDisplay.textContent = (amount * 0.25).toFixed(1); // محاسبه لیتر (هر لیوان 250 سی‌سی)
            waterAmountText.style.display = 'flex';
            dontKnowText.style.display = 'none';
            state.updateFormData('waterIntake', amount);
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

window.showStep = function(step) {
    const stepElements = [
        "gender-selection-step",
        "personal-info-step",
        "goal-selection-step",
        "age-input-step", 
        "height-input-step",
        "weight-input-step",
        "target-weight-step",
        "goal-weight-display",
        "surgery-step",
        "hormonal-disorders-step",
        "stomach-discomfort-step",
        "water-intake-step",
        "activity-selection-step",
        "meal-selection-step",
        "additional-info-step",
        "diet-style-step",       // مرحله 15 جدید
        "food-limitations-step", // مرحله 16 جدید
        "food-preferences-step", // مرحله 17 جدید
        "terms-agreement-step",  // مرحله 18 جدید
        "confirm-submit-step"    // مرحله 19 جدید
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
            }[state.formData.goal];
            
            goalTitleElement.textContent = goalText || "هدف: مشخص نشده";
        }
    
        const imageContainer = document.querySelector('#goal-weight-display .step7-image-container');
        let svgFile = '';
        
        if (state.formData.goal === 'weight-loss') {
            svgFile = wpVars.themeBasePath + '/assets/images/svg/weight-loss.svg';
        } else if (state.formData.goal === 'weight-gain' || state.formData.goal === 'fitness') {
            svgFile = wpVars.themeBasePath + '/assets/images/svg/weight-gain.svg';
        }
        
        imageContainer.innerHTML = `
            <div class="goal-title-container">
                <h2 class="goal-title" id="goal-title-text">
                    ${state.formData.goal === 'weight-loss' ? 'کاهش وزن' : 
                      state.formData.goal === 'weight-gain' ? 'افزایش وزن' : 
                      'حفظ سلامت'}
                </h2>
            </div>
            <object type="image/svg+xml" data="${svgFile}" class="goal-svg"></object>
            <div class="weight-display-container">
                <div class="weight-display-box target-weight">
                    <div class="weight-value">${state.formData.targetWeight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن هدف</div>
                </div>
                <div class="weight-display-box current-weight">
                    <div class="weight-value">${state.formData.weight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن فعلی</div>
                </div>
            </div>
        `;
    }
    
    const nextButtonContainer = document.getElementById("next-button-container");
    if (nextButtonContainer) {
        nextButtonContainer.style.display = [
            STEPS.GENDER, 
            STEPS.GOAL,
            STEPS.WATER_INTAKE, // اضافه شده
            STEPS.ACTIVITY, 
            STEPS.MEALS
        ].includes(step) ? "none" : "block";
    }

    if ([STEPS.PERSONAL_INFO, STEPS.AGE, STEPS.HEIGHT, STEPS.WEIGHT, STEPS.TARGET_WEIGHT].includes(step)) {
        const inputId = `${["first-name", "last-name", "age", "height", "weight", "target-weight"][step - 2]}-input`;
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
    else if (step === STEPS.STOMACH) {
        setupStomachDiscomfortSelection(step);
    } 
    else if (step === STEPS.HORMONAL) {
        setupHormonalSelection(step);
    } 
    else if (step === STEPS.SURGERY) {
        setupSurgerySelection(step);
    } 
    else if (step === STEPS.ADDITIONAL_INFO) {
        setupAdditionalInfoSelection(step);
    } 
    else if (step === STEPS.DIET_STYLE) {
        setupDietStyleSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.FOOD_LIMITATIONS) {
        setupFoodLimitationsSelection(step);
        document.getElementById("next-button-container").style.display = "block";
    } 
    else if (step === STEPS.FOOD_PREFERENCES) {
        setupFoodPreferencesSelection(step);
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

window.updateStepCounter = function(step) {
    document.getElementById("current-step").textContent = step;
    document.getElementById("total-steps").textContent = totalSteps;
}

window.updateProgressBar = function(step) {
    const progress = ((step - 1) / (totalSteps - 1)) * 100;
    document.getElementById("progress-bar").style.width = `${progress}%`;
}

window.navigateToStep = function(step) {
    if (step >= 1 && step <= totalSteps) {
        state.updateStep(step);
        history.pushState({ step: state.currentStep }, "", `#step-${state.currentStep}`);
    }
}

window.handleNextStep = function() {
    if (state.currentStep < totalSteps) navigateToStep(state.currentStep + 1);
}

window.handleBackStep = function() {
    if (state.currentStep > 1) navigateToStep(state.currentStep - 1);
}

window.handleEnterKey = function(event) {
    // فقط در مراحل عددی (سن، قد، وزن، وزن هدف) و مرحله نهایی اجازه کار با Enter را بده
    const allowedSteps = [
        STEPS.AGE, 
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