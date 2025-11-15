// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/auto-fill.js

document.addEventListener('DOMContentLoaded', function() {
    // تعریف متغیر سراسری برای delay
    const MUL_VALUE = 2;
    const NEXT_BUTTON_DELAY = 200;
    const LONG_DELAY = 1000 * MUL_VALUE;
    const SHORT_DELAY = 300 * MUL_VALUE;
    
    // فقط در محیط تست اجرا شود
    if ((window.location.pathname.includes('service/diet')) && (window.location.hostname.includes('test.') || 
        (typeof aiAssistantVars !== 'undefined' && aiAssistantVars.env === 'sandbox'))) {
        
        // تابع اصلی برای پر کردن خودکار فرم
        function autoFillForm() {
            console.log('Current Step:', state.currentStep);
            
            // بررسی وجود فرم
            if (!document.getElementById('multi-step-form')) {
                console.error('⚠️ فرم مورد نظر یافت نشد');
                return;
            }
            
            window.removeEventListener('stateUpdated', handleStateChange);
            
            const testData = {
                userInfo: {
                    firstName: "هادی",
                    lastName: "بهداد", 
                    gender: 'male',
                    goal: 'weight-loss',
                    age: 40,
                    height: 174,
                    weight: 73,
                    targetWeight: 71,
                    activity: 'medium',
                    exercise: 'medium',
                    waterIntake: 14,
                    surgery: ['none'],
                    digestiveConditions: ['none'],
                    dietStyle: ['none'],
                    foodLimitations: ['none'],
                    chronicConditions: ['none'],
                    medications: ['none'],
                    favoriteFoods: ['none']
                },
                serviceSelection: {
                    dietType: "ai-only",
                    selectedSpecialist: null
                }
            };
            
            function handleStateChange() {
                console.trace();
                fillStepBasedOnCurrentState();
            }
            
            // سایر توابع موجود بدون تغییر (فقط ترتیب فراخوانی به‌روز می‌شود)
            function fillGenderStep() {
                if (state.currentStep === STEPS.GENDER) {
                    const termsCheckbox = document.getElementById('confirm-terms');
                    if (termsCheckbox && !termsCheckbox.checked) {
                        termsCheckbox.checked = true;
                        termsCheckbox.dispatchEvent(new Event('change'));
                    }

                    setTimeout(() => {
                        const genderOption = document.querySelector(`.gender-option[data-gender="${testData.userInfo.gender}"]`);
                        if (genderOption) {
                            genderOption.click();
                            clickNextButton(LONG_DELAY);
                        }
                    }, SHORT_DELAY);
                }
            }

            function fillPersonalInfoStep() {
                if (state.currentStep === STEPS.PERSONAL_INFO) {
                    const firstNameInput = document.getElementById('first-name-input');
                    if (firstNameInput) {
                        firstNameInput.value = testData.userInfo.firstName;
                        firstNameInput.dispatchEvent(new Event('input'));
                    }
                    
                    const lastNameInput = document.getElementById('last-name-input');
                    if (lastNameInput) {
                        lastNameInput.value = testData.userInfo.lastName;
                        lastNameInput.dispatchEvent(new Event('input'));
                    }
                    
                    const ageInput = document.getElementById('age-input');
                    if (ageInput) {
                        ageInput.value = testData.userInfo.age;
                        ageInput.dispatchEvent(new Event('input'));
                    }
                    
                    clickNextButton(NEXT_BUTTON_DELAY);
                }
            }

            function fillGoalStep() {
                if (state.currentStep === STEPS.GOAL) {
                    const goalOption = document.querySelector(`.goal-option[data-goal="${testData.userInfo.goal}"]`);
                    if (goalOption) {
                        goalOption.click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }

            function fillNumberSteps() {
                const fieldMap = {
                    [STEPS.PERSONAL_INFO]: {id: 'age-input', value: testData.userInfo.age, name: 'سن'},
                    [STEPS.HEIGHT]: {id: 'height-input', value: testData.userInfo.height, name: 'قد'},
                    [STEPS.WEIGHT]: {id: 'weight-input', value: testData.userInfo.weight, name: 'وزن'},
                    [STEPS.TARGET_WEIGHT]: {id: 'target-weight-input', value: testData.userInfo.targetWeight, name: 'وزن هدف'}
                };

                if (fieldMap[state.currentStep]) {
                    const {id, value, name} = fieldMap[state.currentStep];
                    const input = document.getElementById(id);
                    
                    if (input) {
                        input.value = value;
                        input.dispatchEvent(new Event('input'));
                        
                        if (state.currentStep === STEPS.TARGET_WEIGHT) {
                            setTimeout(() => {
                                const errorElement = document.getElementById('targetWeight-error');
                                if (!errorElement || errorElement.classList.contains('valid')) {
                                    clickNextButton(SHORT_DELAY);
                                } else {
                                    console.warn('⚠️ خطا در اعتبارسنجی وزن هدف');
                                }
                            }, NEXT_BUTTON_DELAY);
                        } else {
                            clickNextButton(NEXT_BUTTON_DELAY);
                        }
                    }
                }
            }

            function fillActivityStep() {
                if (state.currentStep === STEPS.ACTIVITY) {
                    const activityOption = document.querySelector(`.activity-option[data-activity="${testData.userInfo.activity}"]`);
                    if (activityOption) {
                        activityOption.click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }
                        
            function fillExerciseStep() {
                if (state.currentStep === STEPS.EXERCISE) {
                    const exerciseOption = document.querySelector(`.exercise-option[data-exercise="${testData.userInfo.exercise}"]`);
                    if (exerciseOption) {
                        exerciseOption.click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }     
            
            function fillWaterStep() {
                if (state.currentStep === STEPS.WATER_INTAKE) {
                    const waterCups = document.querySelectorAll('.water-cup');
                    if (waterCups.length >= testData.userInfo.waterIntake) {
                        waterCups[testData.userInfo.waterIntake - 1].click();
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }

            // به‌روزرسانی توابع چک‌باکسی برای مراحل جدید
            function fillCheckboxSteps() {
                const stepMap = {
                    [STEPS.SURGERY]: {prefix: 'surgery', name: 'جراحی‌ها'},
                    [STEPS.DIET_STYLE]: {prefix: 'diet-style', name: 'سبک رژیم'},
                    [STEPS.FOOD_LIMITATIONS]: {prefix: 'limitations', name: 'محدودیت‌های غذایی'},
                    [STEPS.CHRONIC_CONDITIONS]: {prefix: 'chronic', name: 'بیماری‌های مزمن'},
                    [STEPS.MEDICATIONS]: {prefix: 'medications', name: 'داروهای مصرفی'},
                    [STEPS.DIGESTIVE_CONDITIONS]: {prefix: 'digestive', name: 'مشکلات گوارشی'},
                    [STEPS.FAVORITE_FOODS]: {prefix: 'foods', name: 'غذاهای مورد علاقه'} // اضافه شده
                };

                if (stepMap[state.currentStep]) {
                    const {prefix, name} = stepMap[state.currentStep];
                    const noneCheckbox = document.getElementById(`${prefix}-none`);
                    
                    if (noneCheckbox) {
                        noneCheckbox.checked = true;
                        noneCheckbox.dispatchEvent(new Event('change'));
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }

            function fillGoalDisplayStep() {
                if (state.currentStep === STEPS.GOAL_DISPLAY) {
                    
                    const checkSVGLoaded = setInterval(() => {
                        const svgElement = document.querySelector('#goal-weight-display object');
                        if (svgElement && svgElement.contentDocument) {
                            clearInterval(checkSVGLoaded);
                            clickNextButton(LONG_DELAY);
                        }
                    }, 200);

                    setTimeout(() => {
                        if (state.currentStep === STEPS.GOAL_DISPLAY) {
                            clickNextButton(0);
                        }
                    }, 3000);
                }
            }
            
            function fillDietTypeStep() {
                if (state.currentStep === STEPS.DIET_TYPE_SELECTION) {
                    const aiOnlyOption = document.querySelector('.diet-type-card[data-diet-type="ai-only"]');
                    if (aiOnlyOption) {
                        aiOnlyOption.click();
                        // فعال کردن دکمه بعدی
                        const nextButton = document.querySelector('.next-step');
                        if (nextButton) {
                            nextButton.disabled = false;
                        }
                    }
                }
            }
            
            function fillTermsStep() {
                if (state.currentStep === STEPS.TERMS_AGREEMENT) {
                    const agreeCheckbox = document.getElementById('agree-terms');
                    if (agreeCheckbox) {
                        agreeCheckbox.checked = true;
                        agreeCheckbox.dispatchEvent(new Event('change'));
                        clickNextButton(NEXT_BUTTON_DELAY);
                    }
                }
            }

            function fillConfirmationStep() {
                if (state.currentStep === STEPS.CONFIRMATION) {
                    const confirmCheckbox = document.getElementById('confirm-info');
                    if (confirmCheckbox) {
                        confirmCheckbox.checked = true;
                        confirmCheckbox.dispatchEvent(new Event('change'));
                        
                        setTimeout(() => {
                            const submitButton = document.querySelector('.final-submit');
                            if (submitButton) {
                                
                                submitButton.disabled = true;
                                submitButton.textContent = aiAssistantVars.i18n.loading;
                                
                                const formData = state.formData;
                                const formSubmittedEvent = new CustomEvent('formSubmitted', {
                                    detail: {
                                        formData: formData
                                    }
                                });
                                window.dispatchEvent(formSubmittedEvent);
                                
                            }
                        }, LONG_DELAY);
                    }
                }
            }

            // تابع کمکی برای کلیک روی دکمه بعدی
            function clickNextButton(delay) {
                setTimeout(() => {
                    const nextButton = document.querySelector('.next-step:not([disabled])');
                    if (nextButton) {
                        nextButton.click();
                    }
                }, delay);
            }

            // تابع اصلی برای پر کردن بر اساس مرحله فعلی
            function fillStepBasedOnCurrentState() {
                switch(state.currentStep) {
                    case STEPS.GENDER:
                        fillGenderStep();
                        break;
                    case STEPS.PERSONAL_INFO:
                        fillPersonalInfoStep();
                        break;
                    case STEPS.GOAL:
                        fillGoalStep();
                        break;
                    case STEPS.HEIGHT:
                        fillNumberSteps();
                        break;
                    case STEPS.WEIGHT:
                        fillNumberSteps();
                        break;
                    case STEPS.TARGET_WEIGHT:
                        fillNumberSteps();
                        break;
                    case STEPS.ACTIVITY:
                        fillActivityStep();
                        break;
                    case STEPS.EXERCISE:
                        fillExerciseStep();
                        break;
                    case STEPS.WATER_INTAKE:
                        fillWaterStep();
                        break;
                    case STEPS.SURGERY:
                        fillCheckboxSteps();
                        break;
                    case STEPS.DIGESTIVE_CONDITIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.DIET_STYLE:
                        fillCheckboxSteps();
                        break;
                    case STEPS.FOOD_LIMITATIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.CHRONIC_CONDITIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.MEDICATIONS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.GOAL_DISPLAY:
                        fillGoalDisplayStep();
                        break;
                    case STEPS.FAVORITE_FOODS:
                        fillCheckboxSteps();
                        break;
                    case STEPS.DIET_TYPE_SELECTION:
                        fillDietTypeStep();
                        break;                        
                    case STEPS.TERMS_AGREEMENT:
                        fillTermsStep();
                        break;
                    case STEPS.CONFIRMATION:
                        fillConfirmationStep();
                        break;
                    default:
                        console.warn('مرحله ناشناخته:', state.currentStep);
                }
            }
            
            fillStepBasedOnCurrentState();
        }

        // ایجاد دکمه پر کردن خودکار (بدون تغییر)
        function createAutoFillButton() {
            if (document.getElementById('dev-auto-fill-btn')) return;

            const btn = document.createElement('button');
            btn.id = 'dev-auto-fill-btn';
            btn.innerHTML = 'پر کردن خودکار فرم (تست)';
            btn.style.position = 'fixed';
            btn.style.bottom = '20px';
            btn.style.right = '20px';
            btn.style.zIndex = '9999';
            btn.style.padding = '10px 15px';
            btn.style.backgroundColor = '#4CAF50';
            btn.style.color = 'white';
            btn.style.border = 'none';
            btn.style.borderRadius = '4px';
            btn.style.cursor = 'pointer';
            btn.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';

            btn.addEventListener('click', function() {
                
                // بازنشانی به مرحله اول
                if (state && typeof state.updateStep === 'function') {
                    state.updateStep(1);
                }

                // شروع پر کردن خودکار پس از تاخیر
                setTimeout(() => {
                    autoFillForm();
                    
                    const stateChangeHandler = function() {
                        autoFillForm();
                    };

                    window.removeEventListener('stateUpdated', stateChangeHandler);
                    window.addEventListener('stateUpdated', stateChangeHandler);
                    
                    setTimeout(() => {
                        window.removeEventListener('stateUpdated', stateChangeHandler);
                    }, 5000); 
                }, NEXT_BUTTON_DELAY);
            });

            document.body.appendChild(btn);
        }

        // ایجاد دکمه پس از لود کامل صفحه
        setTimeout(createAutoFillButton, LONG_DELAY);
    }
});