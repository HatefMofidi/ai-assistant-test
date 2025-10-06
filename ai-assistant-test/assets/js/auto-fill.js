// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/auto-fill.js

document.addEventListener('DOMContentLoaded', function() {
    // فقط در محیط تست اجرا شود
    if ((window.location.pathname.includes('service/diet')) && (window.location.hostname.includes('test.') || 
        (typeof aiAssistantVars !== 'undefined' && aiAssistantVars.env === 'sandbox'))) {
        
        // تابع اصلی برای پر کردن خودکار فرم
        function autoFillForm() {
            // بررسی وجود فرم
            if (!document.getElementById('multi-step-form')) {
                console.log('⚠️ فرم مورد نظر یافت نشد');
                return;
            }
            
            // داده‌های تستی به‌روزرسانی شده
            const testData = {
                firstName: "تست",
                lastName: "کاربر",
                gender: 'female',
                goal: 'weight-loss',
                age: 30,
                height: 175,
                weight: 85,
                targetWeight: 75,
                activity: 'medium',
                exercise: 'medium',
                waterIntake: 8,
                surgery: ['none'],
                digestiveConditions: ['none'], // به‌روزرسانی شده
                dietStyle: ['none'],
                foodLimitations: ['none'],
                chronicConditions: ['none'] // اضافه شده
            };

            // تابع جدید برای پر کردن مرحله مشکلات گوارشی
            function fillDigestiveConditionsStep() {
                if (state.currentStep === STEPS.DIGESTIVE_CONDITIONS) {
                    const noneCheckbox = document.getElementById('digestive-none');
                    if (noneCheckbox) {
                        noneCheckbox.checked = true;
                        noneCheckbox.dispatchEvent(new Event('change'));
                        clickNextButton(500);
                    }
                }
            }

            // تابع جدید برای پر کردن مرحله بیماری‌های مزمن
            function fillChronicConditionsStep() {
                if (state.currentStep === STEPS.CHRONIC_CONDITIONS) {
                    const noneCheckbox = document.getElementById('chronic-none');
                    if (noneCheckbox) {
                        noneCheckbox.checked = true;
                        noneCheckbox.dispatchEvent(new Event('change'));
                        clickNextButton(500);
                    }
                }
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
                        const genderOption = document.querySelector(`.gender-option[data-gender="${testData.gender}"]`);
                        if (genderOption) {
                            genderOption.click();
                            clickNextButton(1000);
                        }
                    }, 300);
                }
            }

            function fillPersonalInfoStep() {
                if (state.currentStep === STEPS.PERSONAL_INFO) {
                    const firstNameInput = document.getElementById('first-name-input');
                    if (firstNameInput) {
                        firstNameInput.value = testData.firstName;
                        firstNameInput.dispatchEvent(new Event('input'));
                    }
                    
                    const lastNameInput = document.getElementById('last-name-input');
                    if (lastNameInput) {
                        lastNameInput.value = testData.lastName;
                        lastNameInput.dispatchEvent(new Event('input'));
                    }
                    
                    clickNextButton(500);
                }
            }

            function fillGoalStep() {
                if (state.currentStep === STEPS.GOAL) {
                    const goalOption = document.querySelector(`.goal-option[data-goal="${testData.goal}"]`);
                    if (goalOption) {
                        goalOption.click();
                        clickNextButton(500);
                    }
                }
            }

            function fillNumberSteps() {
                const fieldMap = {
                    [STEPS.AGE]: {id: 'age-input', value: testData.age, name: 'سن'},
                    [STEPS.HEIGHT]: {id: 'height-input', value: testData.height, name: 'قد'},
                    [STEPS.WEIGHT]: {id: 'weight-input', value: testData.weight, name: 'وزن'},
                    [STEPS.TARGET_WEIGHT]: {id: 'target-weight-input', value: testData.targetWeight, name: 'وزن هدف'}
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
                                    clickNextButton(300);
                                } else {
                                    console.log('⚠️ خطا در اعتبارسنجی وزن هدف');
                                }
                            }, 500);
                        } else {
                            clickNextButton(500);
                        }
                    }
                }
            }

            function fillActivityStep() {
                if (state.currentStep === STEPS.ACTIVITY) {
                    const activityOption = document.querySelector(`.activity-option[data-activity="${testData.activity}"]`);
                    if (activityOption) {
                        activityOption.click();
                        clickNextButton(500);
                    }
                }
            }
                        
            function fillExerciseStep() {
                if (state.currentStep === STEPS.EXERCISE) {
                    const exerciseOption = document.querySelector(`.exercise-option[data-exercise="${testData.exercise}"]`);
                    if (exerciseOption) {
                        exerciseOption.click();
                        clickNextButton(500);
                    }
                }
            }     
            
            function fillWaterStep() {
                if (state.currentStep === STEPS.WATER_INTAKE) {
                    const waterCups = document.querySelectorAll('.water-cup');
                    if (waterCups.length >= testData.waterIntake) {
                        waterCups[testData.waterIntake - 1].click();
                        clickNextButton(500);
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
                    [STEPS.DIGESTIVE_CONDITIONS]: {prefix: 'digestive', name: 'مشکلات گوارشی'}                    
                };

                if (stepMap[state.currentStep]) {
                    const {prefix, name} = stepMap[state.currentStep];
                    const noneCheckbox = document.getElementById(`${prefix}-none`);
                    
                    if (noneCheckbox) {
                        noneCheckbox.checked = true;
                        noneCheckbox.dispatchEvent(new Event('change'));
                        clickNextButton(500);
                    }
                }
            }

            function fillGoalDisplayStep() {
                if (state.currentStep === STEPS.GOAL_DISPLAY) {
                    
                    const checkSVGLoaded = setInterval(() => {
                        const svgElement = document.querySelector('#goal-weight-display object');
                        if (svgElement && svgElement.contentDocument) {
                            clearInterval(checkSVGLoaded);
                            clickNextButton(1000);
                        }
                    }, 200);

                    setTimeout(() => {
                        if (state.currentStep === STEPS.GOAL_DISPLAY) {
                            clickNextButton(0);
                        }
                    }, 3000);
                }
            }

            function fillTermsStep() {
                if (state.currentStep === STEPS.TERMS_AGREEMENT) {
                    const agreeCheckbox = document.getElementById('agree-terms');
                    if (agreeCheckbox) {
                        agreeCheckbox.checked = true;
                        agreeCheckbox.dispatchEvent(new Event('change'));
                        clickNextButton(500);
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
                        }, 1000);
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

            // اجرای توابع پر کردن بر اساس مرحله فعلی - ترتیب جدید
            fillGenderStep();
            fillPersonalInfoStep();
            fillGoalStep();
            fillNumberSteps();
            fillChronicConditionsStep();      // اضافه شده - مرحله 9
            fillDigestiveConditionsStep();    // اضافه شده - مرحله 10
            fillCheckboxSteps();              // شامل surgery (11), diet-style (15), limitations (16)
            fillWaterStep();                  // مرحله 12
            fillActivityStep();               // مرحله 13
            fillExerciseStep();               // مرحله 14
            fillGoalDisplayStep();
            fillTermsStep();
            fillConfirmationStep();
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
                }, 500);
            });

            document.body.appendChild(btn);
        }

        // ایجاد دکمه پس از لود کامل صفحه
        setTimeout(createAutoFillButton, 1000);
    }
});