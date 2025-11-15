// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/script.js

window.state = {
    currentStep: 1,
    formData: {
        userInfo: {
            firstName: "",
            lastName: "",
            gender: "",
            age: "",
            height: "",
            weight: "",
            targetWeight: "",
            goal: "",
            activity: "",
            exercise: "",
            waterIntake: "",
            surgery: [],
            chronicConditions: [],
            digestiveConditions: [],
            medications: [],
            dietStyle: [],
            foodLimitations: [],
            favoriteFoods: [],
            chronicDiabetesType: "",
            chronicFastingBloodSugar: "",
            chronicHba1c: "",
            cancerTreatment: "",
            cancerType: ""
        },
        serviceSelection: {
            dietType: "",
            selectedSpecialist: null
        },
        discountInfo: {
            discountCode: "",
            discountApplied: false,
            discountAmount: 0,
            originalPrice: 0,
            finalPrice: 0,
            discountData: null
        },
        servicePrices: { // 🔥 مقداردهی اولیه برای قیمت‌ها
            aiOnly: 0,
            withSpecialist: 0
        }        
    },
    
    updateStep(step) {
        this.currentStep = step;
        window.showStep(step);
        window.updateStepCounter(step);
        window.updateProgressBar(step);
        
        window.dispatchEvent(new CustomEvent('stateUpdated'));
    },
    
    updateFormData(key, value) {
        // پشتیبانی از ساختار جدید
        if (key.startsWith('userInfo.')) {
            const userInfoKey = key.replace('userInfo.', '');
            this.formData.userInfo[userInfoKey] = value;
        } else if (key.startsWith('serviceSelection.')) {
            const serviceKey = key.replace('serviceSelection.', '');
            this.formData.serviceSelection[serviceKey] = value;
        } else if (key.startsWith('discountInfo.')) {
            const discountKey = key.replace('discountInfo.', '');
            if (!this.formData.discountInfo) {
                this.formData.discountInfo = {};
            }
            this.formData.discountInfo[discountKey] = value;
        }  else {
            // برای سازگاری با کدهای قدیمی - ذخیره در userInfo
            this.formData.userInfo[key] = value;
        }
        window.validateStep(this.currentStep);
    },
    
    updateFormElementsFromState() {
        // تاخیر برای اطمینان از لود کامل DOM
        setTimeout(() => {
            const { userInfo, serviceSelection } = this.formData;
            
            // به روزرسانی جنسیت
            if (userInfo.gender) {
                const genderOption = document.querySelector(`.gender-option[data-gender="${userInfo.gender}"]`);
                if (genderOption) genderOption.classList.add('selected');
            }
        
            if (userInfo.firstName) {
                const firstNameInput = document.getElementById('first-name-input');
                if (firstNameInput) {
                    firstNameInput.value = userInfo.firstName;
                    firstNameInput.dispatchEvent(new Event('input'));
                }
            }
            
            if (userInfo.lastName) {
                const lastNameInput = document.getElementById('last-name-input');
                if (lastNameInput) {
                    lastNameInput.value = userInfo.lastName;
                    lastNameInput.dispatchEvent(new Event('input'));
                }
            }    
            
            // به روزرسانی هدف
            if (userInfo.goal) {
                const goalOption = document.querySelector(`.goal-option[data-goal="${userInfo.goal}"]`);
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
                if (userInfo[key]) {
                    const input = document.getElementById(id);
                    if (input) {
                        input.value = userInfo[key];
                        // تریگر رویداد input برای به روزرسانی نمایش
                        input.dispatchEvent(new Event('input'));
                    }
                }
            });
    
            // به روزرسانی چک‌باکس‌ها
            const checkboxGroups = {
                'surgery': { prefix: 'surgery', items: userInfo.surgery || [] },
                'dietStyle': { prefix: 'diet-style', items: userInfo.dietStyle || [] },
                'foodLimitations': { prefix: 'limitation', items: userInfo.foodLimitations || [] },
                'digestiveConditions': { prefix: 'digestive', items: userInfo.digestiveConditions || [] },
                'chronicConditions': { prefix: 'chronic', items: userInfo.chronicConditions || [] },
                'medications': { prefix: 'medication', items: userInfo.medications || [] },
                'favoriteFoods': { prefix: 'food', items: userInfo.favoriteFoods || [] }
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
                        const noneCheckboxId = `${prefix}-none`; // مثل surgery-none, diet-style-none
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
            if (userInfo.activity) {
                const activityOption = document.querySelector(`.activity-option[data-activity="${userInfo.activity}"]`);
                if (activityOption) activityOption.classList.add('selected');
            }
                
            if (userInfo.exercise) {
                const exerciseOption = document.querySelector(`.exercise-option[data-exercise="${userInfo.exercise}"]`);
                if (exerciseOption) exerciseOption.classList.add('selected');
            }            
            
            // به روزرسانی مصرف آب
            if (userInfo.waterIntake !== undefined && userInfo.waterIntake !== null) {
                const waterCups = document.querySelectorAll('.water-cup');
                const waterAmount = userInfo.waterIntake;
                
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
            
            // به روزرسانی نوع رژیم
            if (serviceSelection.dietType) {
                const dietTypeCard = document.querySelector(`.diet-type-card[data-diet-type="${serviceSelection.dietType}"]`);
                if (dietTypeCard) {
                    dietTypeCard.classList.add('selected');
                    dietTypeCard.style.transform = "translateY(-5px)";
                    dietTypeCard.style.opacity = "1";
                    dietTypeCard.style.filter = "grayscale(0)";
                }
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
    HEIGHT: 4,
    WEIGHT: 5,
    TARGET_WEIGHT: 6,
    GOAL_DISPLAY: 7,
    CHRONIC_CONDITIONS: 8,
    MEDICATIONS: 9,
    DIGESTIVE_CONDITIONS: 10,
    SURGERY: 11,
    WATER_INTAKE: 12,
    ACTIVITY: 13,
    EXERCISE: 14,
    DIET_STYLE: 15,
    FOOD_LIMITATIONS: 16,
    FAVORITE_FOODS: 17,
    DIET_TYPE_SELECTION: 18,
    TERMS_AGREEMENT: 19,
    CONFIRMATION: 20
};

// تعداد مراحل اصلی (بدون احتساب دو مرحله آخر)
window.totalSteps = Object.keys(STEPS).length - 3; 

window.VALUE_MAPPING = {
    // جنسیت
    gender: {
        'male': 'مرد',
        'female': 'زن'
    },
    
    // هدف
    goal: {
        'weight-loss': 'کاهش وزن - برنامه‌ای برای رسیدن به وزن ایده‌آل و سالم',
        'weight-gain': 'افزایش وزن سالم - برنامه‌ای برای افزایش وزن اصولی و عضله‌سازی', 
        'fitness': 'حفظ سلامت و تناسب - برنامه‌ای برای حفظ وزن فعلی و بهبود سلامت عمومی'
    },
    
    // فعالیت روزانه
    activity: {
        'very-low': 'خیلی کم (بی‌تحرک) - بیشتر وقت پشت میز یا در خانه، تحرک بسیار کم',
        'low': 'کم (فعالیت سبک) - کارهای سبک خانه، پیاده‌روی کوتاه، ایستادن متوسط',
        'medium': 'متوسط (فعالیت متوسط) - کارهایی که نیاز به راه رفتن و حرکت مداوم دارد',
        'high': 'زیاد (فعالیت شدید) - کار فیزیکی سخت که بیشتر روز نیاز به فعالیت بدنی دارد'
    },
    
    // فعالیت ورزشی
    exercise: {
        'none': 'هیچ ورزشی نمی‌کنم',
        'light': 'سبک (۱-۲ روز در هفته، کمتر از ۳۰ دقیقه)',
        'medium': 'متوسط (۳-۴ روز در هفته، ۳۰-۶۰ دقیقه)',
        'high': 'زیاد (۵-۶ روز در هفته یا بیشتر، ۶۰+ دقیقه)',
        'professional': 'ورزشکار حرفه‌ای (تمرین سنگین روزانه و برنامه‌ریزی شده)'
    },
    
    // بیماری‌های مزمن
    chronicConditions: {
        'diabetes': 'دیابت',
        'hypertension': 'فشار خون بالا',
        'cholesterol': 'کلسترول یا تری گلیسیرید بالا',
        'fattyLiver': 'کبد چرب',
        'insulinResistance': 'مقاومت به انسولین',
        'hypothyroidism': 'کم کاری تیروئید (هیپوتیروئیدی)',
        'hyperthyroidism': 'پرکاری تیروئید (هیپرتیروئیدی)',
        'hashimoto': 'هاشیموتو (التهاب خودایمنی تیروئید)',
        'pcos': 'سندرم تخمدان پلی کیستیک (PCOS)',
        'menopause': 'یائسگی یا پیش یائسگی',
        'cortisol': 'مشکلات کورتیزول (استرس مزمن)',
        'growth': 'اختلال هورمون رشد',
        'kidney': 'بیماری کلیوی مزمن',
        'heart': 'بیماری قلبی عروقی',
        'autoimmune': 'بیماری خودایمنی',
        'none': 'هیچگونه بیماری مزمن یا زمینه‌ای ندارم'
    },
    
    // داروها
    medications: {
        'diabetes': 'داروهای دیابت (متفورمین، انسولین و...)',
        'thyroid': 'داروهای تیروئید (لووتیروکسین و...)',
        'corticosteroids': 'کورتون‌ها (پردنیزولون و...)',
        'anticoagulants': 'داروهای ضد انعقاد (وارفارین و ...)',
        'hypertension': 'داروهای فشار خون',
        'psychiatric': 'داروهای اعصاب و روان',
        'hormonal': 'داروهای هورمونی (قرص ضد بارداری، هورمون درمانی)',
        'cardiac': 'داروهای قلبی و عروقی',
        'gastrointestinal': 'داروهای گوارشی',
        'supplements': 'مکمل‌ها، ویتامین‌ها و محصولات ورزشی',
        'none': 'هیچ داروی خاصی مصرف نمی‌کنم'
    },
    
    // مشکلات گوارشی
    digestiveConditions: {
        'ibs': 'سندرم روده تحریک‌پذیر (IBS)',
        'ibd': 'بیماری التهابی روده (کرون یا کولیت اولسراتیو)',
        'gerd': 'ریفلاکس معده-مروی (GERD)',
        'bloating': 'نفخ یا گاز معده',
        'pain': 'درد یا گرفتگی معده',
        'heartburn': 'سوزش سر دل یا ترش کردن',
        'constipation': 'یبوست مزمن',
        'diarrhea': 'اسهال مزمن',
        'fullness': 'سیری زودرس',
        'nausea': 'حالت تهوع',
        'slow-digestion': 'هضم کند غذا',
        'indigestion': 'سوء هاضمه مزمن',
        'helicobacter': 'عفونت هلیکوباکتر پیلوری (H. Pylori)',
        'none': 'هیچگونه مشکل گوارشی یا عدم تحمل غذایی ندارم'
    },
    
    // جراحی‌ها
    surgery: {
        'metabolic': 'جراحی متابولیک (بایپس معده، اسلیو)',
        'gallbladder': 'جراحی کیسه صفرا',
        'intestine': 'جراحی روده',
        'thyroid': 'جراحی تیروئید/پاراتیروئید',
        'pancreas': 'جراحی لوزالمعده (پانکراس)',
        'heart': 'جراحی قلب',
        'kidney': 'پیوند کلیه',
        'liver': 'پیوند کبد',
        'gynecology': 'جراحی‌های زنان',
        'cancer': 'سابقه سرطان (همراه جزئیات نوع و درمان)',
        'none': 'هیچگونه سابقه جراحی ندارم'
    },
    
    // سبک غذایی
    dietStyle: {
        'vegetarian': 'گیاهخواری (Vegetarian)',
        'vegan': 'وگان (Vegan - بدون هیچ محصول حیوانی)',
        'none': 'سبک غذایی خاصی ندارم'
    },
    
    // محدودیت‌های غذایی
    foodLimitations: {
        'celiac': 'بیماری سلیاک (حساسیت به گلوتن)',
        'lactose': 'عدم تحمل لاکتوز',
        'seafood-allergy': 'حساسیت به غذاهای دریایی',
        'eggs-allergy': 'حساسیت به تخم‌مرغ',
        'nuts-allergy': 'حساسیت به آجیل و مغزها',
        'no-seafood': 'عدم مصرف غذاهای دریایی',
        'no-redmeat': 'عدم مصرف گوشت قرمز',
        'no-dairy': 'عدم مصرف لبنیات',
        'none': 'هیچ محدودیت غذایی ندارم'
    },
    
    // نوع دیابت
    chronicDiabetesType: {
        'type1': 'دیابت نوع 1',
        'type2': 'دیابت نوع 2',
        'gestational': 'دیابت بارداری',
        'prediabetes': 'پیش‌دیابت'
    },
    
    // درمان سرطان
    cancerTreatment: {
        'chemo': 'شیمی درمانی',
        'radio': 'پرتو درمانی',
        'surgery': 'اخیراً جراحی شده‌ام',
        'finished': 'درمانم تمام شده'
    },
    
    // نوع سرطان
    cancerType: {
        'breast': 'پستان',
        'colon': 'روده',
        'prostate': 'پروستات',
        'lung': 'ریه',
        'blood': 'خون',
        'other': 'سایر'
    },
    
    favoriteFoods: {
        'gheymeh': 'قیمه (کم‌روغن)',
        'ghormeh': 'قرمه سبزی (کم‌چرب)',
        'kabab-koobideh': 'کباب کوبیده (کم‌چرب)',
        'joojeh-kabab': 'جوجه کباب',
        'kabab-barg': 'کباب برگ',
        'fesenjan': 'فسنجان (کم‌شیرینی)',
        'bademjan': 'خورشت بادمجان (کم‌روغن)',
        'karafs': 'خورشت کرفس',
        'aloo-esfenaj': 'خورشت آلواسفناج',
        'abgoosht': 'آبگوشت (کم‌چربی)',
        'pizza': 'پیتزا (نسخه سالم)',
        'burger': 'همبرگر (نسخه سالم)',
        'pasta': 'پاستا (غلات کامل)',
        'sandwich': 'ساندویچ مرغ گریل',
        'salad': 'سالاد سزار سالم',
        'chelo': 'چلوی ساده',
        'sabzi-polo': 'سبزی پلو',
        'adas-polo': 'عدس پلو',
        'lobya-polo': 'لوبیا پلو',
        'shevid-polo': 'شوید پلو',
        'salad-shirazi': 'سالاد شیرازی',
        'mast-o-khiar': 'ماست و خیار',
        'borani-esfenaj': 'بورانی اسفناج',
        'borani-bademjan': 'بورانی بادمجان',
        'nokhod-kishmesh': 'نخود و کشمش (متعادل)',
        'ash-reshteh': 'آش رشته (کم‌روغن)',
        'ash-jow': 'آش جو',
        'halim': 'حلیم گندم (کم‌شیرینی)',
        'adas': 'عدسی',
        'lobya': 'خوراک لوبیا (کم‌روغن)',
        'omelet': 'املت (کم‌روغن)',
        'nimroo': 'نیمرو (کم‌روغن)',
        'egg-tomato': 'خوراک تخم مرغ و گوجه',
        'kookoo-sabzi': 'کوکو سبزی (فر یا گریل)',
        'kookoo-sibzamini': 'کوکو سیب زمینی (فر یا گریل)',
        'none': 'ترجیح می‌دهم برنامه بر اساس نیازهای غذایی من تنظیم شود'
    },
    // نوع رژیم
    dietType: {
        'ai-only': 'ai-only',
        'with-specialist': 'with-specialist'
    },
    discountType: {
        'percentage': 'percentage',
        'fixed': 'fixed'
    }    
};

window.KEY_MAPPING = {
    // اطلاعات شخصی
    'firstName': 'نام',
    'lastName': 'نام خانوادگی',
    'gender': 'جنسیت',
    'age': 'سن',
    'height': 'قد',
    'weight': 'وزن فعلی',
    'targetWeight': 'وزن هدف',
    
    // اطلاعات هدف و فعالیت
    'goal': 'هدف از دریافت رژیم',
    'activity': 'سطح فعالیت روزانه',
    'exercise': 'فعالیت ورزشی هفتگی',
    'waterIntake': 'مصرف آب روزانه',
    
    // اطلاعات پزشکی
    'chronicConditions': 'بیماری‌های مزمن و زمینه‌ای',
    'chronicDiabetesType': 'نوع دیابت',
    'chronicFastingBloodSugar': 'قند خون ناشتا',
    'chronicHba1c': 'سطح HbA1c',
    'medications': 'داروهای مصرفی',
    'digestiveConditions': 'مشکلات گوارشی',
    'surgery': 'سابقه جراحی',
    'cancerTreatment': 'نوع درمان سرطان',
    'cancerType': 'نوع سرطان',
    
    // اطلاعات غذایی
    'dietStyle': 'سبک غذایی',
    'foodLimitations': 'محدودیت‌های غذایی',
    'favoriteFoods': 'غذاهای مورد علاقه',
    
    // اطلاعات تخفیف
    'discountCode': 'discountCode',
    'discountApplied': 'discountApplied',
    'discountAmount': 'discountAmount',
    'originalPrice': 'originalPrice',
    'finalPrice': 'finalPrice'
};

// تابع برای تبدیل کلیدهای آبجکت به فارسی
window.convertKeysToPersian = function(obj) {
    const persianObj = {};
    
    for (const [key, value] of Object.entries(obj)) {
        const persianKey = KEY_MAPPING[key] || key;
        persianObj[persianKey] = value;
    }
    
    return persianObj;
};

// تابع برای تبدیل داده‌ها به فارسی
window.convertToPersianData = function(formData) {
    const persianData = {...formData};
    
    // تبدیل مقادیر ساده
    if (persianData.gender && VALUE_MAPPING.gender[persianData.gender]) {
        persianData.gender = VALUE_MAPPING.gender[persianData.gender];
    }
    
    if (persianData.goal && VALUE_MAPPING.goal[persianData.goal]) {
        persianData.goal = VALUE_MAPPING.goal[persianData.goal];
    }
    
    if (persianData.activity && VALUE_MAPPING.activity[persianData.activity]) {
        persianData.activity = VALUE_MAPPING.activity[persianData.activity];
    }
    
    if (persianData.exercise && VALUE_MAPPING.exercise[persianData.exercise]) {
        persianData.exercise = VALUE_MAPPING.exercise[persianData.exercise];
    }
    
    if (persianData.chronicDiabetesType && VALUE_MAPPING.chronicDiabetesType[persianData.chronicDiabetesType]) {
        persianData.chronicDiabetesType = VALUE_MAPPING.chronicDiabetesType[persianData.chronicDiabetesType];
    }
    
    if (persianData.cancerTreatment && VALUE_MAPPING.cancerTreatment[persianData.cancerTreatment]) {
        persianData.cancerTreatment = VALUE_MAPPING.cancerTreatment[persianData.cancerTreatment];
    }
    
    if (persianData.cancerType && VALUE_MAPPING.cancerType[persianData.cancerType]) {
        persianData.cancerType = VALUE_MAPPING.cancerType[persianData.cancerType];
    }
    
    if (persianData.waterIntake !== null && persianData.waterIntake !== undefined) {
        persianData.waterIntake = `${persianData.waterIntake} لیوان (≈${(persianData.waterIntake * 0.25).toFixed(1)} لیتر)`;
    }

    if (persianData.waterIntake === null) {
        persianData.waterIntake = 'مشخص نیست';
    }    
    
    // تبدیل آرایه‌ها
    const arrayFields = [
        'chronicConditions', 'medications', 'digestiveConditions', 
        'surgery', 'dietStyle', 'foodLimitations', 'favoriteFoods'
    ];
    
    arrayFields.forEach(field => {
        if (persianData[field] && Array.isArray(persianData[field])) {
            persianData[field] = persianData[field].map(item => {
                return VALUE_MAPPING[field] && VALUE_MAPPING[field][item] 
                    ? VALUE_MAPPING[field][item] 
                    : item;
            });
        }
    });
    
    return persianData;
};


// تابع کامل برای تبدیل تمام داده‌ها به فارسی با ساختار جدید
window.convertToCompletePersianData = function(formData) {
    // ایجاد یک کپی از داده‌ها
    const convertedData = {
        userInfo: {},
        serviceSelection: {},
        discountInfo: {}
    };
    
    // تبدیل userInfo
    if (formData.userInfo) {
        const persianValues = window.convertToPersianData(formData.userInfo);
        convertedData.userInfo = window.convertKeysToPersian(persianValues);
    }
    
    // تبدیل serviceSelection
    if (formData.serviceSelection) {
        // تبدیل مقادیر serviceSelection به فارسی
        const serviceData = {...formData.serviceSelection};
        
        if (serviceData.dietType && VALUE_MAPPING.dietType[serviceData.dietType]) {
            serviceData.dietType = VALUE_MAPPING.dietType[serviceData.dietType];
        }
        
        // تبدیل کلیدهای serviceSelection به فارسی
        const serviceKeyMapping = {
            'dietType': 'dietType',
            'selectedSpecialist': 'selectedSpecialist'
        };
        
        for (const [key, value] of Object.entries(serviceData)) {
            const persianKey = serviceKeyMapping[key] || key;
            convertedData.serviceSelection[persianKey] = value;
        }
    }
    
    
    // تبدیل discountInfo
    if (formData.discountInfo) {
        const discountData = {...formData.discountInfo};
        
        // تبدیل کلیدها به فارسی
        const discountKeyMapping = {
            'discountCode': 'discountCode',
            'discountApplied': 'discountApplied',
            'discountAmount': 'discountAmount',
            'originalPrice': 'originalPrice',
            'finalPrice': 'finalPrice'
        };
        
        for (const [key, value] of Object.entries(discountData)) {
            const persianKey = discountKeyMapping[key] || key;
            convertedData.discountInfo[persianKey] = value;
        }
    }    
    
    return convertedData;
};