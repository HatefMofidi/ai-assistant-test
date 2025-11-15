// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-events.js

   
// انتخاب گزینه‌های سرطان
document.querySelectorAll('.cancer-option').forEach(option => {
    option.addEventListener('click', function() {
        this.classList.toggle('selected');
    });
});

// نمایش/مخفی کردن جزئیات
document.getElementById('cancer-history').addEventListener('change', function() {
    document.getElementById('cancer-details').style.display = this.checked ? 'block' : 'none';
});

function setupScrollIndicator(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // ایجاد نشانگر اسکرول
    const scrollIndicator = document.createElement('div');
    scrollIndicator.className = 'scroll-indicator';
    scrollIndicator.innerHTML = '<div class="scroll-indicator-arrow"></div>';
    container.appendChild(scrollIndicator);

    // بررسی اولیه وضعیت اسکرول
    function checkScrollState() {
        const hasScroll = container.scrollHeight > container.clientHeight;
        const isAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 10;
        
        scrollIndicator.style.display = hasScroll ? 'flex' : 'none';
        
        if (hasScroll && !isAtBottom) {
            scrollIndicator.classList.remove('hidden');
            container.classList.remove('scrolled');
        } else {
            scrollIndicator.classList.add('hidden');
            container.classList.add('scrolled');
        }
    }

    // عملکرد کلیک برای اسکرول
    scrollIndicator.addEventListener('click', () => {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth'
        });
    });

    // رویداد اسکرول با debounce
    let scrollTimeout;
    container.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(checkScrollState, 50);
    });

    // بررسی تغییر اندازه
    const resizeObserver = new ResizeObserver(checkScrollState);
    resizeObserver.observe(container);

    // بررسی اولیه
    checkScrollState();
}

// استفاده از تابع برای تمام کانتینرهای اسکرول
document.addEventListener('DOMContentLoaded', () => {
    setupScrollIndicator('surgery-selection');
    setupScrollIndicator('goal-selection');
    setupScrollIndicator('chronic-conditions-selection');
    setupScrollIndicator('digestive-conditions-selection');
    setupScrollIndicator('activity-selection');
    setupScrollIndicator('exercise-selection'); // اضافه کردن این خط
    setupScrollIndicator('diet-style-selection');
    setupScrollIndicator('food-limitations-selection');
    setupScrollIndicator('favorite-foods-selection');
    setupScrollIndicator('medications-selection');
});

async function loadServicePrices() {
    try {
        // دریافت قیمت با تخفیف برای سرویس ai-only
        const aiOnlyResponse = await fetch(aiAssistantVars.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_service_price_with_discount',
                service_id: 'diet', // یا ID مربوط به ai-only
                include_consultant_fee: '0',
                consultant_fee: '0',
                nonce: aiAssistantVars.nonce
            })
        });

        const aiOnlyData = await aiOnlyResponse.json();
        
        if (!aiOnlyData.success) {
            throw new Error(aiOnlyData.data?.message || 'خطا در دریافت قیمت');
        }

        // دریافت هزینه مشاور از endpoint قبلی
        const consultantResponse = await fetch(aiAssistantVars.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_diet_service_prices',
                security: aiAssistantVars.nonce
            })
        });

        const consultantData = await consultantResponse.json();
        
        if (!consultantData.success) {
            throw new Error(consultantData.data?.message || 'خطا در دریافت قیمت مشاور');
        }

        const aiOnlyFinalPrice = aiOnlyData.data.final_price;
        const aiOnlyOriginalPrice = aiOnlyData.data.original_price;
        const hasAiOnlyDiscount = aiOnlyData.data.has_discount;
        const consultantFee = consultantData.data.consultant_price;

        // نمایش قیمت ai-only با تخفیف
        const aiOnlyPriceElement = document.getElementById('ai-only-price');
        if (aiOnlyPriceElement) {
            if (hasAiOnlyDiscount) {
                aiOnlyPriceElement.innerHTML = `
                    <span style="text-decoration: line-through; color: #999; font-size: 0.9em; margin-left: 8px;">
                        ${new Intl.NumberFormat('fa-IR').format(aiOnlyOriginalPrice)}
                    </span>
                    <span style="color: #00857a; font-weight: bold;">
                        ${new Intl.NumberFormat('fa-IR').format(aiOnlyFinalPrice)}
                    </span>
                `;
            } else {
                aiOnlyPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(aiOnlyFinalPrice);
            }
        }

        // نمایش قیمت with-specialist با تخفیف
        const specialistPriceElement = document.getElementById('with-specialist-price');
        if (specialistPriceElement) {
            const specialistFinalPrice = aiOnlyFinalPrice + consultantFee;
            const specialistOriginalPrice = aiOnlyOriginalPrice + consultantFee;
            
            if (hasAiOnlyDiscount) {
                specialistPriceElement.innerHTML = `
                    <span style="text-decoration: line-through; color: #999; font-size: 0.9em; margin-left: 8px;">
                        ${new Intl.NumberFormat('fa-IR').format(specialistOriginalPrice)}
                    </span>
                    <span style="color: #00857a; font-weight: bold;">
                        ${new Intl.NumberFormat('fa-IR').format(specialistFinalPrice)}
                    </span>
                `;
            } else {
                specialistPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(specialistFinalPrice);
            }
        }

        // ذخیره در state
        if (window.state && window.state.formData) {
            window.state.formData.servicePrices = {
                aiOnly: aiOnlyFinalPrice,
                aiOnlyOriginal: aiOnlyOriginalPrice,
                consultantFee: consultantFee,
                withSpecialist: aiOnlyFinalPrice + consultantFee,
                withSpecialistOriginal: aiOnlyOriginalPrice + consultantFee,
                hasDiscount: hasAiOnlyDiscount,
                loaded: true,
                error: false
            };

            console.log('قیمت‌ها با تخفیف بارگذاری شد:', {
                aiOnlyFinal: aiOnlyFinalPrice,
                aiOnlyOriginal: aiOnlyOriginalPrice,
                hasDiscount: hasAiOnlyDiscount,
                consultantFee: consultantFee
            });
        }

    } catch (error) {
        console.error('خطا در بارگذاری قیمت‌های سرویس:', error);
        
        const errorMessage = 'خطا در دریافت قیمت‌ها';
        
        const aiOnlyPriceElement = document.getElementById('ai-only-price');
        const specialistPriceElement = document.getElementById('with-specialist-price');
        
        if (aiOnlyPriceElement) aiOnlyPriceElement.textContent = errorMessage;
        if (specialistPriceElement) specialistPriceElement.textContent = errorMessage;

        // ذخیره خطا در state
        if (window.state && window.state.formData) {
            window.state.formData.servicePrices = {
                loaded: false,
                error: true,
                errorMessage: errorMessage
            };
        }

        if (typeof showNotification === 'function') {
            showNotification('خطا در بارگذاری قیمت‌ها، لطفا صفحه را رفرش کنید', 'error');
        } else {
            console.warn(errorMessage);
        }
    }
}


// /assets/js/services/diet/form-events.js
function showNotification(message, type = 'info') {
    // ایجاد یک نوتیفیکیشن ساده
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 20px;
        background: ${type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        border-radius: 4px;
        z-index: 10000;
        font-family: inherit;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // حذف خودکار پس از 5 ثانیه
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// فراخوانی تابع هنگام لود صفحه
document.addEventListener('DOMContentLoaded', function() {
    loadServicePrices();
});

window.handleNextStep = function() {
    if (window.state.currentStep < window.totalSteps) {
        window.navigateToStep(window.state.currentStep + 1);
    }
};

window.preloadImages = function() {
    const images = [
        'assets/images/webp/img_0_de-min.webp',
        'assets/images/png/img_1_de-min.png',
        'assets/images/webp/img_0-min.webp',
        'assets/images/png/img_1-min.png'
    ];
    
    images.forEach(src => {
        const img = new Image();
        img.src = src;
    });
}

window.showPaymentConfirmation = function(formData, finalPrice) {
    try {
        // محاسبه قیمت نهایی با در نظر گرفتن هزینه مشاور
        let calculatedFinalPrice = finalPrice;
        
        // اگر رژیم با متخصص انتخاب شده و مشاور انتخاب شده است
        if (formData.serviceSelection.dietType === 'with-specialist' && 
            formData.serviceSelection.selectedSpecialist) {
            
            const consultantFee = formData.serviceSelection.selectedSpecialist.consultation_price;
            calculatedFinalPrice += consultantFee;
            
            console.log('💰 قیمت نهایی با هزینه مشاور:', {
                basePrice: finalPrice,
                consultantFee: consultantFee,
                total: calculatedFinalPrice
            });
        }

        const paymentPopup = new PaymentPopup({
            serviceType: 'رژیم غذایی',
            serviceId: 'diet',
            customPrice: calculatedFinalPrice, // استفاده از قیمت محاسبه شده
            ajaxAction: 'get_diet_service_price',
            includeConsultantFee: formData.serviceSelection.dietType === 'with-specialist',
            consultantFee: formData.serviceSelection.selectedSpecialist ? 
                          formData.serviceSelection.selectedSpecialist.consultation_price : 0,
            onConfirm: (completeFormData, confirmedFinalPrice, discountDetails) => {
                const completePersianData = window.convertToCompletePersianData(completeFormData);
                completePersianData.finalPrice = confirmedFinalPrice;
                completePersianData.discountDetails = discountDetails;
                
                console.log('💰 ارسال داده‌های تخفیف به سرور:', completePersianData.discountInfo);
                
                window.dispatchEvent(new CustomEvent('formSubmitted', {
                    detail: { 
                        formData: completePersianData,
                        finalPrice: confirmedFinalPrice,
                        discountInfo: completePersianData.discountInfo
                    }
                }));
            },
            onCancel: () => {
                if (window.state && window.state.formData) {
                    window.state.formData.discountInfo = {
                        discountCode: '',
                        discountApplied: false,
                        discountAmount: 0,
                        originalPrice: finalPrice,
                        finalPrice: finalPrice,
                        discountData: null
                    };
                }
                document.getElementById('SubmitBtn').disabled = false;
            }
        });
        paymentPopup.show();
    } catch (error) {
        console.error('Error showing payment popup:', error);
        alert('خطا در نمایش پرداخت. لطفاً صفحه را رفرش کنید.');
    }    
};

function setupChronicDiabetesDetails() {
    const diabetesCheckbox = document.getElementById('chronic-diabetes');
    const diabetesDetails = document.getElementById('chronic-diabetes-details');
    const diabetesAdditional = document.getElementById('chronic-diabetes-additional');
    
    if (!diabetesCheckbox || !diabetesDetails) return;

    diabetesCheckbox.addEventListener('change', function() {
        diabetesDetails.style.display = this.checked ? 'block' : 'none';
        
        if (!this.checked) {
            state.updateFormData('userInfo.chronicDiabetesType', '');
            state.updateFormData('userInfo.chronicFastingBloodSugar', '');
            state.updateFormData('userInfo.chronicHba1c', '');
            resetChronicDiabetesSelections();
            diabetesAdditional.style.display = 'none';
        }
    });

    const diabetesOptions = document.querySelectorAll('#chronic-diabetes-details .diabetes-option');
    diabetesOptions.forEach(option => {
        option.addEventListener('click', function() {
            diabetesOptions.forEach(opt => {
                opt.classList.remove('selected');
                opt.style.backgroundColor = '';
                opt.style.borderRadius = '4px';
            });
            
            this.classList.add('selected');
            this.style.backgroundColor = '#e8f5e8';
            this.style.borderRadius = '4px';
            this.style.padding = '8px';

            const diabetesType = this.dataset.value;
            state.updateFormData('userInfo.chronicDiabetesType', diabetesType);
            
            if (diabetesType !== 'prediabetes') {
                diabetesAdditional.style.display = 'block';
            } else {
                diabetesAdditional.style.display = 'none';
            }
            
            validateChronicDiabetesStep();
        });
    });

    const fastingInput = document.getElementById('chronic-fasting-blood-sugar');
    const hba1cInput = document.getElementById('chronic-hba1c-level');
    
    if (fastingInput) {
        fastingInput.addEventListener('input', function() {
            state.updateFormData('userInfo.chronicFastingBloodSugar', this.value);
        });
    }
    
    if (hba1cInput) {
        hba1cInput.addEventListener('input', function() {
            state.updateFormData('userInfo.chronicHba1c', this.value);
        });
    }
}

function handleConflictingConditions(selectedConditionId) {
    const conflictGroups = {
        // تیروئید - فقط یکی قابل انتخاب است
        'chronic-hyperthyroidism': ['chronic-hypothyroidism', 'chronic-hashimoto'],
        'chronic-hypothyroidism': ['chronic-hyperthyroidism'],
        'chronic-hashimoto': ['chronic-hyperthyroidism'],
        
        // کیسه صفرا - فقط یکی قابل انتخاب است
        'chronic-gallbladder-stones': ['chronic-gallbladder-inflammation', 'chronic-gallbladder-issues'],
        'chronic-gallbladder-inflammation': ['chronic-gallbladder-stones', 'chronic-gallbladder-issues'],
        'chronic-gallbladder-issues': ['chronic-gallbladder-stones', 'chronic-gallbladder-inflammation']
    };

    if (conflictGroups[selectedConditionId]) {
        conflictGroups[selectedConditionId].forEach(conflictingId => {
            const conflictingCheckbox = document.getElementById(conflictingId);
            if (conflictingCheckbox && conflictingCheckbox.checked) {
                conflictingCheckbox.checked = false;
                // به روزرسانی state و UI
                conflictingCheckbox.dispatchEvent(new Event('change'));
                
                // حذف از state.formData
                const conditionKey = conflictingId.replace('chronic-', '');
                if (state.formData.chronicConditions) {
                    state.formData.chronicConditions = state.formData.chronicConditions.filter(
                        item => item !== conditionKey
                    );
                }
            }
        });
    }
}

// تابع اعتبارسنجی مرحله دیابت
function validateChronicDiabetesStep() {
    const nextButton = document.querySelector(".next-step");
    if (!nextButton) return;

    const diabetesChecked = document.getElementById('chronic-diabetes').checked;
    
    if (diabetesChecked) {
        const hasDiabetesType = state.formData.diabetesType !== '';
        nextButton.disabled = !hasDiabetesType;
    }
}

// تابع ریست انتخاب‌های دیابت
function resetChronicDiabetesSelections() {
    document.querySelectorAll('.diabetes-option.selected').forEach(opt => {
        opt.classList.remove('selected');
        opt.style.backgroundColor = '';
        opt.style.padding = '';
    });
    
    const fastingInput = document.getElementById('fasting-blood-sugar');
    const hba1cInput = document.getElementById('hba1c-level');
    
    if (fastingInput) fastingInput.value = '';
    if (hba1cInput) hba1cInput.value = '';
}

window.handleFormSubmit = function(event) {
    event.preventDefault();
    
    // 1. جمع‌آوری ساختارمند تمام داده‌ها
    const formData = {
        userInfo: { ...state.formData.userInfo },
        serviceSelection: { ...state.formData.serviceSelection },
        discountInfo: { ...state.formData.discountInfo }
    };
    
    const discountCodeInput = document.getElementById('discount-code-input');
    if (discountCodeInput && discountCodeInput.value.trim()) {
        formData.discountInfo = {
            ...formData.discountInfo,
            discountCode: discountCodeInput.value.trim(),
            discountApplied: true,
            discountAmount: state.formData.discountInfo?.discountAmount || 0,
            originalPrice: state.formData.discountInfo?.originalPrice || 0,
            finalPrice: state.formData.discountInfo?.finalPrice || 0
        };
    }
    
    const finalPrice = formData.discountInfo.finalPrice || formData.discountInfo.originalPrice;
    
    // غیرفعال کردن دکمه سابمیت
    document.getElementById('SubmitBtn').disabled = true;
    
    // نمایش پاپ‌آپ تأیید پرداخت
    window.showPaymentConfirmation(formData, finalPrice);
    
    return false;
};

window.showSummary = function() {
    const summaryContainer = document.getElementById('summary-container');
    const nextButton = document.querySelector(".next-step");
    const confirmCheckbox = document.getElementById("confirm-info");
    
    nextButton.disabled = true;
    
    const { 
        userInfo,
        serviceSelection,
        servicePrices // 🔥 استفاده از قیمت‌های ذخیره شده در state
    } = state.formData;

    const {
        firstName, lastName, gender, age, height, weight, targetWeight, goal,
        activity, exercise, waterIntake, surgery = [],
        digestiveConditions = [], dietStyle = [],
        foodLimitations = [], chronicConditions, favoriteFoods, medications,
        chronicDiabetesType, chronicFastingBloodSugar, chronicHba1c,
        cancerTreatment, cancerType
    } = userInfo;

    const { dietType, selectedSpecialist } = serviceSelection;

    const personalInfoText = [];
    if (firstName) personalInfoText.push(`نام: ${firstName}`);
    if (lastName) personalInfoText.push(`نام خانوادگی: ${lastName}`);
    
    const goalText = { 
        "weight-loss": "کاهش وزن", 
        "weight-gain": "افزایش وزن", 
        "fitness": "حفظ سلامت"
    }[goal];
    
    const activityText = { 
        "very-low": "خیلی کم (بی‌تحرک)", 
        "low": "کم (فعالیت سبک)", 
        "medium": "متوسط (فعالیت متوسط)", 
        "high": "زیاد (فعالیت شدید)" 
    }[activity];
    
    const chronicConditionsText = [];
    if (chronicConditions.includes('diabetes')) {
        const diabetesTypeText = {
            'type1': 'دیابت نوع 1',
            'type2': 'دیابت نوع 2', 
            'gestational': 'دیابت بارداری',
            'prediabetes': 'پیش‌دیابت'
        }[state.formData.chronicDiabetesType];
        
        chronicConditionsText.push(`دیابت (${diabetesTypeText || 'نوع مشخص نشده'})`);
        
        // اضافه کردن اطلاعات تکمیلی اگر موجود باشد
        if (state.formData.chronicFastingBloodSugar) {
            chronicConditionsText.push(`قند ناشتا: ${state.formData.chronicFastingBloodSugar}`);
        }
        if (state.formData.chronicHba1c) {
            chronicConditionsText.push(`HbA1c: ${state.formData.chronicHba1c}%`);
        }
    }
    if (chronicConditions.includes('hypertension')) chronicConditionsText.push('فشار خون بالا');
    if (chronicConditions.includes('cholesterol')) chronicConditionsText.push('کلسترول/تری گلیسیرید بالا');
    if (chronicConditions.includes('fattyLiver')) chronicConditionsText.push('کبد چرب');
    if (chronicConditions.includes('insulinResistance')) chronicConditionsText.push('مقاومت به انسولین');
    if (chronicConditions.includes('hypothyroidism')) chronicConditionsText.push('کم کاری تیروئید');
    if (chronicConditions.includes('hyperthyroidism')) chronicConditionsText.push('پرکاری تیروئید');
    if (chronicConditions.includes('hashimoto')) chronicConditionsText.push('هاشیموتو');
    if (chronicConditions.includes('pcos')) chronicConditionsText.push('سندرم تخمدان پلی کیستیک');
    if (chronicConditions.includes('menopause')) chronicConditionsText.push('یائسگی/پیش یائسگی');
    if (chronicConditions.includes('cortisol')) chronicConditionsText.push('مشکلات کورتیزول');
    if (chronicConditions.includes('growth')) chronicConditionsText.push('اختلال هورمون رشد');
    if (chronicConditions.includes('kidney')) chronicConditionsText.push('بیماری کلیوی مزمن');
    if (chronicConditions.includes('heart')) chronicConditionsText.push('بیماری قلبی عروقی');
    if (chronicConditions.includes('autoimmune')) chronicConditionsText.push('بیماری خودایمنی');
    if (chronicConditions.includes('none')) chronicConditionsText.push('ندارم');

    
    const medicationsText = [];
    if (medications.includes('diabetes')) medicationsText.push('داروهای دیابت');
    if (medications.includes('thyroid')) medicationsText.push('داروهای تیروئید');
    if (medications.includes('corticosteroids')) medicationsText.push('کورتون‌ها');
    if (medications.includes('anticoagulants')) medicationsText.push('داروهای ضد انعقاد');
    if (medications.includes('hypertension')) medicationsText.push('داروهای فشار خون');
    if (medications.includes('psychiatric')) medicationsText.push('داروهای اعصاب و روان');
    if (medications.includes('hormonal')) medicationsText.push('داروهای هورمونی');
    if (medications.includes('cardiac')) medicationsText.push('داروهای قلبی و عروقی');
    if (medications.includes('gastrointestinal')) medicationsText.push('داروهای گوارشی');
    if (medications.includes('supplements')) medicationsText.push('مکمل‌ها و ویتامین‌ها');
    if (medications.includes('none')) medicationsText.push('هیچ داروی خاصی مصرف نمی‌کنم');

    // اضافه کردن به تابع showSummary
    const exerciseText = { 
        "none": "هیچ ورزشی نمی‌کنم",
        "light": "سبک (۱-۲ روز در هفته)", 
        "medium": "متوسط (۳-۴ روز در هفته)", 
        "high": "زیاد (۵-۶ روز در هفته)", 
        "professional": "ورزشکار حرفه‌ای" 
    }[exercise];
    
    const waterText = waterIntake === null ? 
        'مشخص نیست' : 
        `${waterIntake} لیوان (≈${(waterIntake * 0.25).toFixed(1)} لیتر)`;

    // در تابع showSummary - بخش surgeryText
    const surgeryText = [];
    if (surgery.includes('metabolic')) surgeryText.push('جراحی متابولیک');
    if (surgery.includes('gallbladder')) surgeryText.push('جراحی کیسه صفرا');
    if (surgery.includes('intestine')) surgeryText.push('جراحی روده');
    if (surgery.includes('thyroid')) surgeryText.push('جراحی تیروئید');
    if (surgery.includes('pancreas')) surgeryText.push('جراحی لوزالمعده');
    if (surgery.includes('heart')) surgeryText.push('جراحی قلب');
    if (surgery.includes('kidney')) surgeryText.push('پیوند کلیه');
    if (surgery.includes('liver')) surgeryText.push('پیوند کبد');
    if (surgery.includes('gynecology')) surgeryText.push('جراحی زنان');
    if (surgery.includes('cancer')) surgeryText.push('سابقه سرطان');
    if (surgery.includes('none')) surgeryText.push('هیچگونه سابقه جراحی ندارم');
    
    // اضافه کردن اطلاعات سرطان اگر انتخاب شده
    if (surgery.includes('cancer')) {
        const cancerTreatmentText = {
            'chemo': 'شیمی درمانی',
            'radio': 'پرتو درمانی', 
            'surgery': 'اخیراً جراحی شده',
            'finished': 'درمان تمام شده'
        }[state.formData.cancerTreatment];
    
        const cancerTypeText = {
            'breast': 'پستان',
            'colon': 'روده',
            'prostate': 'پروستات',
            'lung': 'ریه',
            'blood': 'خون',
            'other': 'سایر'
        }[state.formData.cancerType];
    
        if (cancerTreatmentText) surgeryText.push(`درمان: ${cancerTreatmentText}`);
        if (cancerTypeText) surgeryText.push(`نوع: ${cancerTypeText}`);
    }

    const digestiveConditionsText = [];
    if (digestiveConditions.includes('ibs')) digestiveConditionsText.push('سندرم روده تحریک پذیر');
    if (digestiveConditions.includes('ibd')) digestiveConditionsText.push('بیماری التهابی روده');
    if (digestiveConditions.includes('gerd')) digestiveConditionsText.push('ریفلاکس معده-مروی');
    if (digestiveConditions.includes('bloating')) digestiveConditionsText.push('نفخ یا گاز معده');
    if (digestiveConditions.includes('pain')) digestiveConditionsText.push('درد یا گرفتگی معده');
    if (digestiveConditions.includes('heartburn')) digestiveConditionsText.push('سوزش سر دل');
    if (digestiveConditions.includes('constipation')) digestiveConditionsText.push('یبوست مزمن');
    if (digestiveConditions.includes('diarrhea')) digestiveConditionsText.push('اسهال مزمن');
    if (digestiveConditions.includes('fullness')) digestiveConditionsText.push('سیری زودرس');
    if (digestiveConditions.includes('nausea')) digestiveConditionsText.push('حالت تهوع');
    if (digestiveConditions.includes('slow-digestion')) digestiveConditionsText.push('هضم کند غذا');
    if (digestiveConditions.includes('indigestion')) digestiveConditionsText.push('سوء هاضمه مزمن');
    if (digestiveConditions.includes('helicobacter')) digestiveConditionsText.push('عفونت هلیکوباکتر پیلوری');
    if (digestiveConditions.includes('none')) digestiveConditionsText.push('ندارم');

    // سبک‌های غذایی
    const dietStyleText = [];
    if (dietStyle.includes('vegetarian')) dietStyleText.push('گیاهخواری');
    if (dietStyle.includes('vegan')) dietStyleText.push('وگان');
    if (dietStyle.includes('none')) dietStyleText.push('سبک غذایی خاصی ندارم');


    // غذاهای مورد علاقه
    const favoriteFoodsText = [];
    if (favoriteFoods.includes('ghormeh')) favoriteFoodsText.push('قرمه سبزی');
    if (favoriteFoods.includes('gheymeh')) favoriteFoodsText.push('قیمه');
    if (favoriteFoods.includes('kabab-koobideh')) favoriteFoodsText.push('کباب کوبیده');
    if (favoriteFoods.includes('joojeh-kabab')) favoriteFoodsText.push('جوجه کباب');
    if (favoriteFoods.includes('kabab-barg')) favoriteFoodsText.push('کباب برگ');
    if (favoriteFoods.includes('fesenjan')) favoriteFoodsText.push('فسنجان');
    if (favoriteFoods.includes('bademjan')) favoriteFoodsText.push('خورشت بادمجان');
    if (favoriteFoods.includes('karafs')) favoriteFoodsText.push('خورشت کرفس');
    if (favoriteFoods.includes('aloo-esfenaj')) favoriteFoodsText.push('خورشت آلواسفناج');
    if (favoriteFoods.includes('abgoosht')) favoriteFoodsText.push('آبگوشت');
    if (favoriteFoods.includes('chelo')) favoriteFoodsText.push('چلوی ساده');
    if (favoriteFoods.includes('sabzi-polo')) favoriteFoodsText.push('سبزی پلو');
    if (favoriteFoods.includes('adas-polo')) favoriteFoodsText.push('عدس پلو');
    if (favoriteFoods.includes('lobya-polo')) favoriteFoodsText.push('لوبیا پلو');
    if (favoriteFoods.includes('shevid-polo')) favoriteFoodsText.push('شوید پلو');
    if (favoriteFoods.includes('salad-shirazi')) favoriteFoodsText.push('سالاد شیرازی');
    if (favoriteFoods.includes('mast-o-khiar')) favoriteFoodsText.push('ماست و خیار');
    if (favoriteFoods.includes('borani-esfenaj')) favoriteFoodsText.push('بورانی اسفناج');
    if (favoriteFoods.includes('borani-bademjan')) favoriteFoodsText.push('بورانی بادمجان');
    if (favoriteFoods.includes('nokhod-kishmesh')) favoriteFoodsText.push('نخود و کشمش');
    if (favoriteFoods.includes('ash-reshteh')) favoriteFoodsText.push('آش رشته');
    if (favoriteFoods.includes('ash-jow')) favoriteFoodsText.push('آش جو');
    if (favoriteFoods.includes('halim')) favoriteFoodsText.push('حلیم');
    if (favoriteFoods.includes('adas')) favoriteFoodsText.push('عدسی');
    if (favoriteFoods.includes('lobya')) favoriteFoodsText.push('خوراک لوبیا');
    if (favoriteFoods.includes('omelet')) favoriteFoodsText.push('املت');
    if (favoriteFoods.includes('nimroo')) favoriteFoodsText.push('نیمرو');
    if (favoriteFoods.includes('egg-tomato')) favoriteFoodsText.push('خوراک تخم مرغ');
    if (favoriteFoods.includes('kookoo-sabzi')) favoriteFoodsText.push('کوکو سبزی');
    if (favoriteFoods.includes('kookoo-sibzamini')) favoriteFoodsText.push('کوکو سیب زمینی');
    if (favoriteFoods.includes('none')) favoriteFoodsText.push('برنامه بر اساس نیازهای غذایی');
    if (favoriteFoods.includes('pizza')) favoriteFoodsText.push('پیتزا (سالم)');
    if (favoriteFoods.includes('burger')) favoriteFoodsText.push('همبرگر (سالم)');
    if (favoriteFoods.includes('pasta')) favoriteFoodsText.push('پاستا (سالم)');
    if (favoriteFoods.includes('sandwich')) favoriteFoodsText.push('ساندویچ مرغ');
    if (favoriteFoods.includes('salad')) favoriteFoodsText.push('سالاد سزار');
    
    const foodLimitationsText = [];
    if (foodLimitations.includes('celiac')) foodLimitationsText.push('بیماری سلیاک');
    if (foodLimitations.includes('lactose')) foodLimitationsText.push('عدم تحمل لاکتوز');
    if (foodLimitations.includes('seafood-allergy')) foodLimitationsText.push('حساسیت به غذای دریایی');
    if (foodLimitations.includes('eggs-allergy')) foodLimitationsText.push('حساسیت به تخم‌مرغ');
    if (foodLimitations.includes('nuts-allergy')) foodLimitationsText.push('حساسیت به آجیل');
    // ترجیحات شخصی
    if (foodLimitations.includes('no-seafood')) foodLimitationsText.push('عدم مصرف غذای دریایی');
    if (foodLimitations.includes('no-redmeat')) foodLimitationsText.push('عدم مصرف گوشت قرمز');
    if (foodLimitations.includes('no-dairy')) foodLimitationsText.push('عدم مصرف لبنیات');
    
    if (foodLimitations.includes('none')) foodLimitationsText.push('ندارم');

    // 🔥 اصلاح بخش نمایش قیمت - تعریف متغیرهای لازم
    let dietTypeText = '';
    
    // مدیریت نمایش قیمت‌ها با در نظر گرفتن وضعیت خطا
    if (servicePrices && servicePrices.error) {
        // نمایش پیغام خطا
        dietTypeText = `نوع رژیم: ${serviceSelection.dietType === 'ai-only' ? 'رژیم هوش مصنوعی' : 'رژیم با تأیید متخصص'} - ${servicePrices.errorMessage}`;
    } else if (servicePrices && servicePrices.loaded) {
        // استفاده از قیمت‌های واقعی
        const aiOnlyPrice = servicePrices.aiOnly;
        const consultantFee = servicePrices.consultantFee || 25000; // قیمت پیش‌فرض مشاور
        
        // 🔥 تعریف متغیرهای فرمت شده
        const formattedAiOnlyPrice = new Intl.NumberFormat('fa-IR').format(aiOnlyPrice);
        
        if (serviceSelection.dietType === 'ai-only') {
            dietTypeText = `رژیم هوش مصنوعی (${formattedAiOnlyPrice} تومان)`;
        } else if (serviceSelection.dietType === 'with-specialist' && serviceSelection.selectedSpecialist) {
            // استفاده از قیمت مشاور انتخاب شده یا قیمت پیش‌فرض
            const specialistConsultationPrice = serviceSelection.selectedSpecialist.consultation_price || consultantFee;
            const totalPrice = aiOnlyPrice + specialistConsultationPrice;
            const formattedTotalPrice = new Intl.NumberFormat('fa-IR').format(totalPrice);
            
            dietTypeText = `رژیم با تأیید متخصص (${formattedTotalPrice} تومان) - ${serviceSelection.selectedSpecialist.name}`;
        } else if (serviceSelection.dietType === 'with-specialist') {
            // اگر مشاور انتخاب نشده اما نوع رژیم با مشاور است
            const totalPrice = aiOnlyPrice + consultantFee;
            const formattedTotalPrice = new Intl.NumberFormat('fa-IR').format(totalPrice);
            dietTypeText = `رژیم با تأیید متخصص (${formattedTotalPrice} تومان) - متخصص انتخاب نشده`;
        }
    } else {
        // اگر قیمت‌ها هنوز لود نشده‌اند
        dietTypeText = `نوع رژیم: ${serviceSelection.dietType === 'ai-only' ? 'رژیم هوش مصنوعی' : 'رژیم با تأیید متخصص'} - در حال دریافت قیمت...`;
    }
    
    summaryContainer.innerHTML = `
        ${personalInfoText.length > 0 ? `
        <div class="summary-section">
            <h3 class="summary-section-title">اطلاعات شخصی</h3>
            ${personalInfoText.map(item => `
                <div class="summary-item">
                    <span class="summary-label">${item.split(':')[0]}:</span>
                    <span class="summary-value">${item.split(':')[1]}</span>
                </div>
            `).join('')}
        </div>
        ` : ''}    
        <div class="summary-item">
            <span class="summary-label">جنسیت:</span>
            <span class="summary-value">${gender === "male" ? "مرد" : "زن"}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">سن:</span>
            <span class="summary-value">${age} سال</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">قد:</span>
            <span class="summary-value">${height} سانتی‌متر</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">وزن فعلی:</span>
            <span class="summary-value">${weight} کیلوگرم</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">وزن هدف:</span>
            <span class="summary-value">${targetWeight || 'ثبت نشده'} کیلوگرم</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">هدف:</span>
            <span class="summary-value">${goalText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">بیماری‌های مزمن:</span>
            <span class="summary-value">${chronicConditionsText.join('، ') || 'ثبت نشده'}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">داروهای مصرفی:</span>
            <span class="summary-value">${medicationsText.join('، ') || 'ثبت نشده'}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">مشکلات گوارشی:</span>
            <span class="summary-value">${digestiveConditionsText.join('، ') || 'ثبت نشده'}</span>
        </div>         
        <div class="summary-item">
            <span class="summary-label">سابقه جراحی:</span>
            <span class="summary-value">${surgeryText.join('، ') || 'ثبت نشده'}</span>
        </div>  
        <div class="summary-item">
            <span class="summary-label">مصرف آب روزانه:</span>
            <span class="summary-value">${waterText}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">فعالیت روزانه:</span>
            <span class="summary-value">${activityText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">فعالیت ورزشی:</span>
            <span class="summary-value">${exerciseText}</span>
        </div>             
        <div class="summary-item">
            <span class="summary-label">سبک غذایی:</span>
            <span class="summary-value">${dietStyleText.join('، ') || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">محدودیت‌های غذایی:</span>
            <span class="summary-value">${foodLimitationsText.join('، ') || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">غذاهای مورد علاقه:</span>
            <span class="summary-value">${favoriteFoodsText.join('، ') || 'ثبت نشده'}</span>
        </div>        
        <div class="summary-item">
            <span class="summary-label">نوع رژیم:</span>
            <span class="summary-value">${dietTypeText}</span>
        </div>        
        `;
}

// Initialize event listeners
window.addEventListener('load', preloadImages);