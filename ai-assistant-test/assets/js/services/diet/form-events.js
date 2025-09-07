// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-events.js

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
    setupScrollIndicator('stomach-selection');
    setupScrollIndicator('hormonal-selection');
    setupScrollIndicator('additional-info-selection');
    setupScrollIndicator('food-restriction-selection');
    setupScrollIndicator('goal-selection');
    setupScrollIndicator('activity-selection');
    setupScrollIndicator('meal-selection');
    setupScrollIndicator('diet-style-selection');
    setupScrollIndicator('food-limitations-selection');
    setupScrollIndicator('food-preferences-selection');
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

// تغییر تابع window.showPaymentConfirmation
window.showPaymentConfirmation = function(formData) {
    // دریافت قیمت سرویس از طریق AJAX
    fetch(aiAssistantVars.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'get_diet_service_price',
            'security': aiAssistantVars.nonce
        })
    })
    .then(response => response.json())
    .then(priceData => {
        if (priceData.success) {
            const servicePrice = priceData.data.price;
            const formattedPrice = new Intl.NumberFormat('fa-IR').format(servicePrice);
            
            // ایجاد المان پاپ‌آپ با قیمت داینامیک
            const popup = document.createElement('div');
            popup.className = 'payment-confirmation-popup';
            popup.innerHTML = `
                <div class="payment-confirmation-content">
                    <div class="payment-header">
                        <h3>تایید پرداخت</h3>
                    </div>
                    <div class="payment-details">
                        <div class="wallet-balance-popup">
                            <span>موجودی فعلی کیف پول شما:</span>
                            <span class="balance-amount-popup" id="current-balance">در حال بارگذاری...</span>
                        </div>
                        <div class="payment-cost">
                            <span>هزینه دریافت رژیم غذایی اختصاصی:</span>
                            <span class="cost-amount">${formattedPrice} تومان</span>
                        </div>
                        <p class="payment-warning">در صورت تأیید، این مبلغ از حساب شما کسر خواهد شد.</p>
                    </div>
                    <div class="payment-buttons">
                        <button id="confirm-payment" class="confirm-btn" data-price="${servicePrice}" disabled>
                            <span class="btn-text">تأیید و پرداخت (${formattedPrice} تومان)</span>
                            <span class="btn-loading" style="display:none">در حال پردازش...</span>
                        </button>
                        <button id="cancel-payment" class="cancel-btn">انصراف</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(popup);
            
            // غیرفعال کردن کلیک خارج از پاپ‌آپ و دکمه ESC
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
            // دریافت موجودی کاربر
            fetchUserBalance(servicePrice, formData);
            
            // مدیریت رویدادهای دکمه‌ها
/*            document.getElementById('confirm-payment').addEventListener('click', function() {
                const btn = this;
                const btnText = btn.querySelector('.btn-text');
                const btnLoading = btn.querySelector('.btn-loading');
                
                btn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline-block';
                
                // ارسال فرم پس از تأیید
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('formSubmitted', {
                        detail: { formData }
                    }));
                }, 500);
            });*/
            
            document.getElementById('cancel-payment').addEventListener('click', function() {
                document.body.removeChild(popup);
                // فعال کردن مجدد دکمه سابمیت
                document.getElementById('SubmitBtn').disabled = false;
                document.getElementById('SubmitBtn').innerHTML = 'ثبت نهایی';
            });
        } else {
            // خطا در دریافت قیمت
            alert('خطا در دریافت اطلاعات قیمت. لطفاً مجدداً تلاش کنید.');
            document.getElementById('SubmitBtn').disabled = false;
        }
    })
    .catch(error => {
        console.error('Error fetching service price:', error);
        alert('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
        document.getElementById('SubmitBtn').disabled = false;
    });
};

// تغییر تابع fetchUserBalance برای دریافت قیمت و formData به عنوان پارامتر
function fetchUserBalance(servicePrice, formData) {
    fetch(aiAssistantVars.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'get_user_wallet_credit',
            'security': aiAssistantVars.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const balanceElement = document.getElementById('current-balance');
            const formattedBalance = new Intl.NumberFormat('fa-IR').format(data.data.credit);
            balanceElement.textContent = formattedBalance + ' تومان';
            
            // فعال کردن دکمه تأیید پس از دریافت موجودی
            const confirmBtn = document.getElementById('confirm-payment');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                
                // تغییر رفتار دکمه اگر موجودی کافی نیست
                if (data.data.credit < servicePrice) {
                    const neededAmount = servicePrice - data.data.credit;
                    balanceElement.style.color = '#e53935';
                    
                    // تغییر متن و عملکرد دکمه برای انتقال به صفحه افزایش موجودی
                    confirmBtn.querySelector('.btn-text').textContent = 'افزایش موجودی کیف پول';
                    confirmBtn.onclick = function() {
                        // انتقال به صفحه افزایش موجودی با پارامتر مبلغ مورد نیاز
                        window.location.href = `https://test.aidastyar.com/wallet-charge/?needed_amount=${neededAmount}`;
                    };
                } else {
                    // تنظیم عملکرد عادی دکمه اگر موجودی کافی است
                    confirmBtn.onclick = function() {
                        const btn = this;
                        const btnText = btn.querySelector('.btn-text');
                        const btnLoading = btn.querySelector('.btn-loading');
                        
                        btn.disabled = true;
                        btnText.style.display = 'none';
                        btnLoading.style.display = 'inline-block';
                        
                        // ارسال فرم پس از تأیید
                        setTimeout(() => {
                            window.dispatchEvent(new CustomEvent('formSubmitted', {
                                detail: { formData }
                            }));
                        }, 500);
                    };
                }
            }
        }
    })
    .catch(error => {
        console.error('Error fetching balance:', error);
        document.getElementById('current-balance').textContent = 'خطا در دریافت موجودی';
        
        // فعال کردن دکمه حتی در صورت خطا
        const confirmBtn = document.getElementById('confirm-payment');
        if (confirmBtn) {
            confirmBtn.disabled = false;
        }
    });
}

// در تابع window.handleFormSubmit، تغییرات زیر را اعمال کنید:
window.handleFormSubmit = function(event) {
    event.preventDefault();
    
    // 1. جمع‌آوری ساختارمند تمام داده‌ها
    const formData = {
        ...state.formData,
        // اطلاعات پایه
        gender: state.formData.gender,
        age: state.formData.age,
        height: state.formData.height,
        weight: state.formData.weight,
        targetWeight: state.formData.targetWeight,
        goal: state.formData.goal,
        activity: state.formData.activity,
        meals: state.formData.meals,
        waterIntake: state.formData.waterIntake,
        // اطلاعات پزشکی
        surgery: state.formData.surgery || [],
        hormonal: state.formData.hormonal || [],
        stomachDiscomfort: state.formData.stomachDiscomfort || [],
        
        // اطلاعات تکمیلی (به صورت جداگانه)
        diabetes: state.formData.additionalInfo?.includes('diabetes') || false,
        pressure: state.formData.additionalInfo?.includes('pressure') || false,
        thyroid: state.formData.additionalInfo?.includes('thyroid') || false,
        allergy: state.formData.additionalInfo?.includes('allergy') || false,
        vegetarian: state.formData.foodRestrictions?.includes('vegetarian') || false,
        noSeafood: state.formData.foodRestrictions?.includes('no-seafood') || false,
    };

    console.log('Form submitted:', formData);
    
    // غیرفعال کردن دکمه سابمیت
    document.getElementById('SubmitBtn').disabled = true;
    
    // نمایش پاپ‌آپ تأیید پرداخت
    window.showPaymentConfirmation(formData);
};

window.showSummary = function() {
    const summaryContainer = document.getElementById('summary-container');
    const nextButton = document.querySelector(".next-step");
    const confirmCheckbox = document.getElementById("confirm-info");
    
    nextButton.disabled = true;
    
    const { 
        firstName,
        lastName,
        gender, age, height, weight, targetWeight, goal, 
        activity, meals, waterIntake, surgery = [], hormonal = [],
        stomachDiscomfort = [], additionalInfo = [], dietStyle = [],
        foodLimitations = [], foodPreferences = []
    } = state.formData;

    const personalInfoText = [];
    if (firstName) personalInfoText.push(`نام: ${firstName}`);
    if (lastName) personalInfoText.push(`نام خانوادگی: ${lastName}`);
    
    const goalText = { 
        "weight-loss": "کاهش وزن", 
        "weight-gain": "افزایش وزن", 
        "fitness": "حفظ سلامت"
    }[goal];
    
    const activityText = { 
        "very-low": "خیلی کم (کمتر از 1 ساعت)", 
        "low": "کم (1 تا 2 ساعت)", 
        "medium": "متوسط (2 تا 4 ساعت)", 
        "high": "زیاد (بیشتر از 4 ساعت)" 
    }[activity];
    
    const mealsText = { 
        "2": "۲ وعده", 
        "3": "۳ وعده", 
        "4": "۴ وعده", 
        "5": "۵ وعده یا بیشتر",
        "irregular": "وعده‌های نامنظم" 
    }[meals];
    
    const waterText = waterIntake === null ? 
        'مشخص نیست' : 
        `${waterIntake} لیوان (≈${(waterIntake * 0.25).toFixed(1)} لیتر)`;

    // اطلاعات جراحی
    const surgeryText = [];
    if (surgery.includes('metabolic')) surgeryText.push('جراحی متابولیک');
    if (surgery.includes('gallbladder')) surgeryText.push('جراحی کیسه صفرا');
    if (surgery.includes('intestine')) surgeryText.push('جراحی روده');
    if (surgery.includes('thyroid')) surgeryText.push('جراحی تیروئید');
    if (surgery.includes('pancreas')) surgeryText.push('جراحی لوزالمعده');
    if (surgery.includes('gynecology')) surgeryText.push('جراحی زنان');
    if (surgery.includes('kidney')) surgeryText.push('پیوند کلیه');
    if (surgery.includes('liver')) surgeryText.push('پیوند کبد');
    if (surgery.includes('heart')) surgeryText.push('جراحی قلب');
    if (surgery.includes('none')) surgeryText.push('هیچکدام');

    // اطلاعات هورمونی
    const hormonalText = [];
    if (hormonal.includes('hypothyroidism')) hormonalText.push('کم کاری تیروئید');
    if (hormonal.includes('hyperthyroidism')) hormonalText.push('پرکاری تیروئید');
    if (hormonal.includes('diabetes')) hormonalText.push('دیابت');
    if (hormonal.includes('insulin-resistance')) hormonalText.push('مقاومت به انسولین');
    if (hormonal.includes('pcos')) hormonalText.push('سندرم تخمدان پلی کیستیک');
    if (hormonal.includes('menopause')) hormonalText.push('یائسگی/پیش یائسگی');
    if (hormonal.includes('cortisol')) hormonalText.push('مشکلات کورتیزول');
    if (hormonal.includes('growth')) hormonalText.push('اختلال هورمون رشد');
    if (hormonal.includes('none')) hormonalText.push('هیچکدام');

    // مشکلات معده
    const stomachDiscomfortText = [];
    if (stomachDiscomfort.includes('bloating')) stomachDiscomfortText.push('نفخ یا گاز معده');
    if (stomachDiscomfort.includes('pain')) stomachDiscomfortText.push('درد یا گرفتگی معده');
    if (stomachDiscomfort.includes('heartburn')) stomachDiscomfortText.push('سوزش سر دل');
    if (stomachDiscomfort.includes('nausea')) stomachDiscomfortText.push('حالت تهوع');
    if (stomachDiscomfort.includes('indigestion')) stomachDiscomfortText.push('سوء هاضمه مزمن');
    if (stomachDiscomfort.includes('constipation')) stomachDiscomfortText.push('یبوست');
    if (stomachDiscomfort.includes('diarrhea')) stomachDiscomfortText.push('اسهال');
    if (stomachDiscomfort.includes('food-intolerance')) stomachDiscomfortText.push('عدم تحمل غذایی');
    if (stomachDiscomfort.includes('acid-reflux')) stomachDiscomfortText.push('رفلاکس اسید معده');
    if (stomachDiscomfort.includes('slow-digestion')) stomachDiscomfortText.push('هضم کند غذا');
    if (stomachDiscomfort.includes('fullness')) stomachDiscomfortText.push('سیری زودرس');
    if (stomachDiscomfort.includes('none')) stomachDiscomfortText.push('هیچکدام');

    // اطلاعات تکمیلی سلامت
    const additionalInfoText = [];
    if (additionalInfo.includes('diabetes')) additionalInfoText.push('دیابت');
    if (additionalInfo.includes('hypertension')) additionalInfoText.push('فشار خون بالا');
    if (additionalInfo.includes('cholesterol')) additionalInfoText.push('کلسترول/تری گلیسیرید بالا');
    if (additionalInfo.includes('ibs')) additionalInfoText.push('سندرم روده تحریک پذیر');
    if (additionalInfo.includes('celiac')) additionalInfoText.push('بیماری سلیاک');
    if (additionalInfo.includes('lactose')) additionalInfoText.push('عدم تحمل لاکتوز');
    if (additionalInfo.includes('food-allergy')) additionalInfoText.push('حساسیت غذایی');
    if (additionalInfo.includes('none')) additionalInfoText.push('هیچکدام');

    // سبک‌های غذایی
    const dietStyleText = [];
    if (dietStyle.includes('vegetarian')) dietStyleText.push('گیاهخواری');
    if (dietStyle.includes('vegan')) dietStyleText.push('وگان');
    if (dietStyle.includes('halal')) dietStyleText.push('حلال');
    if (dietStyle.includes('none')) dietStyleText.push('هیچکدام');

    // محدودیت‌های غذایی
    const foodLimitationsText = [];
    if (foodLimitations.includes('no-seafood')) foodLimitationsText.push('عدم مصرف غذای دریایی');
    if (foodLimitations.includes('no-redmeat')) foodLimitationsText.push('عدم مصرف گوشت قرمز');
    if (foodLimitations.includes('no-pork')) foodLimitationsText.push('عدم مصرف گوشت خوک');
    if (foodLimitations.includes('no-gluten')) foodLimitationsText.push('عدم مصرف گلوتن');
    if (foodLimitations.includes('no-dairy')) foodLimitationsText.push('عدم مصرف لبنیات');
    if (foodLimitations.includes('no-eggs')) foodLimitationsText.push('عدم مصرف تخم‌مرغ');
    if (foodLimitations.includes('no-nuts')) foodLimitationsText.push('عدم مصرف آجیل و مغزها');
    if (foodLimitations.includes('none')) foodLimitationsText.push('هیچکدام');

    // ترجیحات غذایی
    const foodPreferencesText = [];
    if (foodPreferences.includes('low-carb')) foodPreferencesText.push('رژیم کم کربوهیدرات');
    if (foodPreferences.includes('low-fat')) foodPreferencesText.push('رژیم کم چربی');
    if (foodPreferences.includes('high-protein')) foodPreferencesText.push('رژیم پرپروتئین');
    if (foodPreferences.includes('organic')) foodPreferencesText.push('مواد غذایی ارگانیک');
    if (foodPreferences.includes('none')) foodPreferencesText.push('هیچکدام');

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
            <span class="summary-label">سابقه جراحی:</span>
            <span class="summary-value">${surgeryText.join('، ') || 'ثبت نشده'}</span>
        </div>  
        <div class="summary-item">
            <span class="summary-label">اختلالات هورمونی:</span>
            <span class="summary-value">${hormonalText.join('، ') || 'ثبت نشده'}</span>
        </div>    
        <div class="summary-item">
            <span class="summary-label">علائم معده:</span>
            <span class="summary-value">${stomachDiscomfortText.join('، ') || 'ثبت نشده'}</span>
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
            <span class="summary-label">تعداد وعده‌های غذایی:</span>
            <span class="summary-value">${mealsText}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">وضعیت سلامت:</span>
            <span class="summary-value">${additionalInfoText.join('، ') || 'ثبت نشده'}</span>
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
            <span class="summary-label">ترجیحات غذایی:</span>
            <span class="summary-value">${foodPreferencesText.join('، ') || 'ثبت نشده'}</span>
        </div>
    `;
}

// Initialize event listeners
window.addEventListener('load', preloadImages);