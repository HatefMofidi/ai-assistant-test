 
// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/auto-fill.js
console.log('🔧 فایل auto-fill.js بارگذاری شد - حالت تست فعال');

document.addEventListener('DOMContentLoaded', function() {
    // فقط در محیط تست اجرا شود
    if (window.location.hostname.includes('test.') || 
        (typeof aiAssistantVars !== 'undefined' && aiAssistantVars.env === 'sandbox')) {
        
        console.log('🔧 محیط تست تشخیص داده شد - دکمه پر کردن خودکار اضافه خواهد شد');

        // تابع اصلی برای پر کردن خودکار فرم
        function autoFillForm() {
            console.log('🔧 شروع فرآیند پر کردن خودکار فرم...');
            
            // بررسی وجود فرم
            if (!document.getElementById('multi-step-form')) {
                console.log('⚠️ فرم مورد نظر یافت نشد');
                return;
            }
            
            // داده‌های تستی
            const testData = {
                firstName: "تست",
                lastName: "کاربر",
                gender: 'male',
                goal: 'weight-loss',
                age: 30,
                height: 175,
                weight: 85,
                targetWeight: 75,
                activity: 'medium',
                meals: '3',
                waterIntake: 8,
                surgery: ['none'],
                hormonal: ['none'],
                stomachDiscomfort: ['none'],
                additionalInfo: ['none'],
                dietStyle: ['none'],
                foodLimitations: ['none'],
                foodPreferences: ['none']
            };

            // تابع جدید برای پر کردن مرحله اطلاعات شخصی
            function fillPersonalInfoStep() {
                if (state.currentStep === STEPS.PERSONAL_INFO) {
                    // پر کردن نام
                    const firstNameInput = document.getElementById('first-name-input');
                    if (firstNameInput) {
                        firstNameInput.value = testData.firstName;
                        firstNameInput.dispatchEvent(new Event('input'));
                        console.log(`✅ نام "${testData.firstName}" وارد شد`);
                    }
                    
                    // پر کردن نام خانوادگی
                    const lastNameInput = document.getElementById('last-name-input');
                    if (lastNameInput) {
                        lastNameInput.value = testData.lastName;
                        lastNameInput.dispatchEvent(new Event('input'));
                        console.log(`✅ نام خانوادگی "${testData.lastName}" وارد شد`);
                    }
                    
                    // کلیک روی دکمه بعدی
                    clickNextButton(500);
                }
            }
            // پر کردن مرحله جنسیت
            function fillGenderStep() {
                if (state.currentStep === STEPS.GENDER) {
                    // تیک شرایط و قوانین
                    const termsCheckbox = document.getElementById('confirm-terms');
                    if (termsCheckbox && !termsCheckbox.checked) {
                        termsCheckbox.checked = true;
                        termsCheckbox.dispatchEvent(new Event('change'));
                        console.log('✅ شرایط و قوانین تائید شد');
                    }

                    // انتخاب جنسیت
                    setTimeout(() => {
                        const genderOption = document.querySelector(`.gender-option[data-gender="${testData.gender}"]`);
                        if (genderOption) {
                            genderOption.click();
                            console.log(`✅ جنسیت "${testData.gender}" انتخاب شد`);
                            
                            // کلیک روی دکمه بعدی
                            clickNextButton(1000);
                        }
                    }, 300);
                }
            }

            // پر کردن مرحله هدف
            function fillGoalStep() {
                if (state.currentStep === STEPS.GOAL) {
                    const goalOption = document.querySelector(`.goal-option[data-goal="${testData.goal}"]`);
                    if (goalOption) {
                        goalOption.click();
                        console.log(`✅ هدف "${testData.goal}" انتخاب شد`);
                        clickNextButton(500);
                    }
                }
            }

            // پر کردن مراحل عددی (سن، قد، وزن، وزن هدف)
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
                        console.log(`✅ ${name} "${value}" وارد شد`);
                        
                        // اعتبارسنجی ویژه برای وزن هدف
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

            // پر کردن مرحله فعالیت
            function fillActivityStep() {
                if (state.currentStep === STEPS.ACTIVITY) {
                    const activityOption = document.querySelector(`.activity-option[data-activity="${testData.activity}"]`);
                    if (activityOption) {
                        activityOption.click();
                        console.log(`✅ سطح فعالیت "${testData.activity}" انتخاب شد`);
                        clickNextButton(500);
                    }
                }
            }

            // پر کردن مرحله وعده‌های غذایی
            function fillMealsStep() {
                if (state.currentStep === STEPS.MEALS) {
                    const mealOption = document.querySelector(`.meal-option[data-meals="${testData.meals}"]`);
                    if (mealOption) {
                        mealOption.click();
                        console.log(`✅ تعداد وعده‌ها "${testData.meals}" انتخاب شد`);
                        clickNextButton(500);
                    }
                }
            }

            // پر کردن مرحله مصرف آب
            function fillWaterStep() {
                if (state.currentStep === STEPS.WATER_INTAKE) {
                    const waterCups = document.querySelectorAll('.water-cup');
                    if (waterCups.length >= testData.waterIntake) {
                        waterCups[testData.waterIntake - 1].click();
                        console.log(`✅ تعداد لیوان آب "${testData.waterIntake}" انتخاب شد`);
                        clickNextButton(500);
                    }
                }
            }

            // پر کردن مراحل چک‌باکسی
            function fillCheckboxSteps() {
                const stepMap = {
                    [STEPS.SURGERY]: {prefix: 'surgery', name: 'جراحی‌ها'},
                    [STEPS.HORMONAL]: {prefix: 'hormonal', name: 'مشکلات هورمونی'},
                    [STEPS.STOMACH]: {prefix: 'stomach', name: 'مشکلات معده'},
                    [STEPS.ADDITIONAL_INFO]: {prefix: 'info', name: 'اطلاعات اضافه'},
                    [STEPS.DIET_STYLE]: {prefix: 'diet-style', name: 'سبک رژیم'},
                    [STEPS.FOOD_LIMITATIONS]: {prefix: 'limitations', name: 'محدودیت‌های غذایی'},
                    [STEPS.FOOD_PREFERENCES]: {prefix: 'preferences', name: 'ترجیحات غذایی'}
                };

                if (stepMap[state.currentStep]) {
                    const {prefix, name} = stepMap[state.currentStep];
                    const noneCheckbox = document.getElementById(`${prefix}-none`);
                    
                    if (noneCheckbox) {
                        noneCheckbox.checked = true;
                        noneCheckbox.dispatchEvent(new Event('change'));
                        console.log(`✅ گزینه "هیچکدام" برای ${name} انتخاب شد`);
                        clickNextButton(500);
                    }
                }
            }

            // پر کردن مرحله نمایش هدف
            function fillGoalDisplayStep() {
                if (state.currentStep === STEPS.GOAL_DISPLAY) {
                    console.log('🔧 مرحله نمایش وزن هدف');
                    
                    const checkSVGLoaded = setInterval(() => {
                        const svgElement = document.querySelector('#goal-weight-display object');
                        if (svgElement && svgElement.contentDocument) {
                            clearInterval(checkSVGLoaded);
                            console.log('✅ SVG با موفقیت لود شد');
                            clickNextButton(1000);
                        }
                    }, 200);

                    setTimeout(() => {
                        if (state.currentStep === STEPS.GOAL_DISPLAY) {
                            console.log('⚠️ استفاده از راهکار جایگزین برای مرحله نمایش هدف');
                            clickNextButton(0);
                        }
                    }, 3000);
                }
            }

            // پر کردن مرحله شرایط و قوانین
            function fillTermsStep() {
                if (state.currentStep === STEPS.TERMS_AGREEMENT) {
                    const agreeCheckbox = document.getElementById('agree-terms');
                    if (agreeCheckbox) {
                        agreeCheckbox.checked = true;
                        agreeCheckbox.dispatchEvent(new Event('change'));
                        console.log('✅ شرایط و قوانین تائید شد');
                        clickNextButton(500);
                    }
                }
            }

            // پر کردن مرحله تأیید نهایی
            // در تابع fillConfirmationStep این تغییرات را اعمال کنید
            function fillConfirmationStep() {
                if (state.currentStep === STEPS.CONFIRMATION) {
                    const confirmCheckbox = document.getElementById('confirm-info');
                    if (confirmCheckbox) {
                        confirmCheckbox.checked = true;
                        confirmCheckbox.dispatchEvent(new Event('change'));
                        console.log('✅ اطلاعات تأیید شدند');
                        
                        // اضافه کردن تاخیر قبل از ارسال فرم
                        setTimeout(() => {
                            // پیدا کردن دکمه ارسال نهایی
                            const submitButton = document.querySelector('.final-submit');
                            if (submitButton) {
                                console.log('🔄 در حال ارسال فرم...');
                                
                                // غیرفعال کردن دکمه و تغییر متن آن (مطابق با کد diet.js)
                                submitButton.disabled = true;
                                submitButton.textContent = aiAssistantVars.i18n.loading;
                                
                                // ایجاد و ارسال رویداد formSubmitted به صورت دستی
                                const formData = state.formData; // یا هر منبع دیگری که داده‌های فرم را دارد
                                const formSubmittedEvent = new CustomEvent('formSubmitted', {
                                    detail: {
                                        formData: formData
                                    }
                                });
                                window.dispatchEvent(formSubmittedEvent);
                                
                                console.log('✅ رویداد formSubmitted ارسال شد');
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
                        console.log('🔄 رفتن به مرحله بعد');
                    }
                }, delay);
            }

            // اجرای توابع پر کردن بر اساس مرحله فعلی
            fillGenderStep();
            fillPersonalInfoStep()
            fillGoalStep();
            fillNumberSteps();
            fillActivityStep();
            fillMealsStep();
            fillWaterStep();
            fillCheckboxSteps();
            fillGoalDisplayStep();
            fillTermsStep();
            fillConfirmationStep();
        }

        // ایجاد دکمه پر کردن خودکار
        function createAutoFillButton() {
            // اگر دکمه از قبل وجود دارد، خروج
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
                console.log('🚀 دکمه پر کردن خودکار کلیک شد');
                
                // بازنشانی به مرحله اول
                if (state && typeof state.updateStep === 'function') {
                    state.updateStep(1);
                    console.log('🔄 بازنشانی به مرحله اول');
                }

                // شروع پر کردن خودکار پس از تاخیر
                setTimeout(() => {
                    autoFillForm();
                    
                    // اضافه کردن هندلر برای تغییر مراحل
                    const stateChangeHandler = function() {
                        console.log('🔄 تغییر مرحله تشخیص داده شد - ادامه پر کردن خودکار');
                        autoFillForm();
                    };

                    // حذف هندلرهای قبلی
                    window.removeEventListener('stateUpdated', stateChangeHandler);
                    
                    // اضافه کردن هندلر جدید
                    window.addEventListener('stateUpdated', stateChangeHandler);
                    
                    // حذف هندلر پس از تکمیل فرم
                    setTimeout(() => {
                        window.removeEventListener('stateUpdated', stateChangeHandler);
                        console.log('✅ پر کردن خودکار تکمیل شد - هندلر حذف شد');
                    }, 5000); // حداکثر 15 ثانیه
                }, 500);
            });

            document.body.appendChild(btn);
            console.log('✅ دکمه پر کردن خودکار اضافه شد');
        }

        // ایجاد دکمه پس از لود کامل صفحه
        setTimeout(createAutoFillButton, 1000);
    }
}); 