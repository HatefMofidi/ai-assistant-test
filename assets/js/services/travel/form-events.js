window.handleNextStep = function() {
    if (window.state.currentStep < window.totalSteps) {
        window.navigateToStep(window.state.currentStep + 1);
    }
};


// توابع مربوط به انتخاب‌های چندگانه
window.setupActivitiesSelection = function(currentStep) {
    try {
        if (currentStep !== STEPS.ACTIVITIES) return;

        const elements = {
            noneCheckbox: document.getElementById('activities-none'),
            hiking: document.getElementById('activities-hiking'),
            cultural: document.getElementById('activities-cultural'),
            beach: document.getElementById('activities-beach'),
            adventure: document.getElementById('activities-adventure'),
            shopping: document.getElementById('activities-shopping'),
            nextButton: document.querySelector('.next-step')
        };

        if (Object.values(elements).some(el => !el)) {
            console.error('Some required elements for activities step are missing');
            return;
        }

        elements.nextButton.disabled = true;

        const validateForm = () => {
            const anyChecked = [elements.hiking, elements.cultural, elements.beach, elements.adventure, elements.shopping]
                .some(option => option.checked) || elements.noneCheckbox.checked;
            
            elements.nextButton.disabled = !anyChecked;
            
            const activities = [];
            if (elements.hiking.checked) activities.push('hiking');
            if (elements.cultural.checked) activities.push('cultural');
            if (elements.beach.checked) activities.push('beach');
            if (elements.adventure.checked) activities.push('adventure');
            if (elements.shopping.checked) activities.push('shopping');
            if (elements.noneCheckbox.checked) activities.push('none');
            
            state.updateFormData('activities', activities);
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

        elements.noneCheckbox.addEventListener('change', function() {
            if (this.checked) {
                [elements.hiking, elements.cultural, elements.beach, elements.adventure, elements.shopping].forEach(option => {
                    option.checked = false;
                    const label = option.nextElementSibling;
                    if (label) label.classList.remove('checked');
                });
            }
            validateForm();
        });

        [elements.hiking, elements.cultural, elements.beach, elements.adventure, elements.shopping].forEach(option => {
            handleCheckboxChange(option);
            option.addEventListener('change', function() {
                if (this.checked) {
                    elements.noneCheckbox.checked = false;
                    const label = elements.noneCheckbox.nextElementSibling;
                    if (label) label.classList.remove('checked');
                }
                validateForm();
            });
        });

        validateForm();
    } catch (error) {
        console.error('Error in activities step:', error);
    }
};

window.setupFoodPreferencesSelection = function(currentStep) {
    const checkboxes = document.querySelectorAll('#food-preferences-selection .real-checkbox, #food-none');
    const nextButton = document.querySelector('.next-step');
    const noneCheckbox = document.getElementById('food-none');
    const foodOptions = document.querySelectorAll('#food-preferences-selection .real-checkbox');
    
    if (currentStep !== STEPS.FOOD_PREFERENCES) return;

    nextButton.disabled = true;

    const validateForm = () => {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        nextButton.disabled = !anyChecked;
        
        const foodPreferences = [];
        if (document.getElementById('food-local').checked) foodPreferences.push('local');
        if (document.getElementById('food-international').checked) foodPreferences.push('international');
        if (document.getElementById('food-vegetarian').checked) foodPreferences.push('vegetarian');
        if (document.getElementById('food-vegan').checked) foodPreferences.push('vegan');
        if (noneCheckbox.checked) foodPreferences.push('none');
        
        state.updateFormData('foodPreferences', foodPreferences);
    };

    noneCheckbox.addEventListener('change', function() {
        if (this.checked) {
            foodOptions.forEach(option => {
                option.checked = false;
                option.nextElementSibling.classList.remove('checked');
            });
            
            const label = this.nextElementSibling;
            label.classList.add('checked-animation');
            setTimeout(() => {
                label.classList.remove('checked-animation');
                label.classList.add('checked');
            }, 800);
        }
        validateForm();
    });

    foodOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.checked) {
                noneCheckbox.checked = false;
                noneCheckbox.nextElementSibling.classList.remove('checked');
                
                const label = this.nextElementSibling;
                label.classList.add('checked-animation');
                setTimeout(() => {
                    label.classList.remove('checked-animation');
                    label.classList.add('checked');
                }, 800);
            }
            validateForm();
        });
    });

    validateForm();
}

window.setupSpecialNeedsSelection = function(currentStep) {
    const checkboxes = document.querySelectorAll('#special-needs-selection .real-checkbox, #needs-none');
    const nextButton = document.querySelector('.next-step');
    const noneCheckbox = document.getElementById('needs-none');
    const needsOptions = document.querySelectorAll('#special-needs-selection .real-checkbox');
    
    if (currentStep !== STEPS.SPECIAL_NEEDS) return;

    nextButton.disabled = true;

    const validateForm = () => {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        nextButton.disabled = !anyChecked;
        
        const specialNeeds = [];
        if (document.getElementById('needs-wheelchair').checked) specialNeeds.push('wheelchair');
        if (document.getElementById('needs-dietary').checked) specialNeeds.push('dietary');
        if (document.getElementById('needs-medical').checked) specialNeeds.push('medical');
        if (document.getElementById('needs-visual').checked) specialNeeds.push('visual');
        if (document.getElementById('needs-hearing').checked) specialNeeds.push('hearing');
        if (noneCheckbox.checked) specialNeeds.push('none');
        
        state.updateFormData('specialNeeds', specialNeeds);
    };

    noneCheckbox.addEventListener('change', function() {
        if (this.checked) {
            needsOptions.forEach(option => {
                option.checked = false;
                option.nextElementSibling.classList.remove('checked');
            });
            
            const label = this.nextElementSibling;
            label.classList.add('checked-animation');
            setTimeout(() => {
                label.classList.remove('checked-animation');
                label.classList.add('checked');
            }, 800);
        }
        validateForm();
    });

    needsOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.checked) {
                noneCheckbox.checked = false;
                noneCheckbox.nextElementSibling.classList.remove('checked');
                
                const label = this.nextElementSibling;
                label.classList.add('checked-animation');
                setTimeout(() => {
                    label.classList.remove('checked-animation');
                    label.classList.add('checked');
                }, 800);
            }
            validateForm();
        });
    });

    validateForm();
}

// تابع نمایش خلاصه اطلاعات
window.showSummary = function() {
    const summaryContainer = document.getElementById('summary-container');
    const nextButton = document.querySelector('.next-step');
    const confirmCheckbox = document.getElementById('confirm-info');
    
    nextButton.disabled = true;
    
    confirmCheckbox.addEventListener('change', function() {
        nextButton.disabled = !this.checked;
    });

    const { 
        tripType, destination, travelers, duration, budget, 
        travelStyle, accommodation, transportation, activities = [], 
        foodPreferences = [], specialNeeds = []
    } = state.formData;
    
    const tripTypeText = { 
        "leisure": "تفریحی", 
        "business": "تجاری", 
        "family": "خانوادگی", 
        "honeymoon": "ماه عسل" 
    }[tripType];
    
    const travelStyleText = { 
        "luxury": "لوکس", 
        "budget": "مقرون به صرفه", 
        "backpacking": "کوله‌گردی", 
        "cultural": "فرهنگی" 
    }[travelStyle];
    
    const accommodationText = { 
        "hotel": "هتل", 
        "apartment": "آپارتمان", 
        "hostel": "هاستل", 
        "resort": "ریزورت", 
        "camping": "چادر" 
    }[accommodation];
    
    const transportationText = { 
        "plane": "هواپیما", 
        "train": "قطار", 
        "bus": "اتوبوس", 
        "car": "ماشین شخصی", 
        "cruise": "کشتی" 
    }[transportation];
    
    const activitiesText = activities.map(item => {
        switch(item) {
            case 'hiking': return 'کوهنوردی و طبیعت‌گردی';
            case 'cultural': return 'فرهنگی و تاریخی';
            case 'beach': return 'ساحلی و استراحت';
            case 'adventure': return 'ماجراجویی';
            case 'shopping': return 'خرید';
            case 'none': return 'هیچکدام';
            default: return item;
        }
    });
    
    const foodPreferencesText = foodPreferences.map(item => {
        switch(item) {
            case 'local': return 'غذای محلی';
            case 'international': return 'غذای بین‌المللی';
            case 'vegetarian': return 'گیاهخواری';
            case 'vegan': return 'وگان';
            case 'none': return 'بدون ترجیح خاص';
            default: return item;
        }
    });
    
    const specialNeedsText = specialNeeds.map(item => {
        switch(item) {
            case 'wheelchair': return 'صندلی چرخدار';
            case 'dietary': return 'محدودیت غذایی خاص';
            case 'medical': return 'نیازمندی‌های پزشکی';
            case 'visual': return 'مشکلات بینایی';
            case 'hearing': return 'مشکلات شنوایی';
            case 'none': return 'هیچکدام';
            default: return item;
        }
    });
    
    summaryContainer.innerHTML = `
        <div class="summary-item">
            <span class="summary-label">نوع سفر:</span>
            <span class="summary-value">${tripTypeText || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">مقصد:</span>
            <span class="summary-value">${destination || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">تعداد مسافران:</span>
            <span class="summary-value">${travelers || 0} نفر</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">مدت سفر:</span>
            <span class="summary-value">${duration || 0} روز</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">بودجه:</span>
            <span class="summary-value">${budget || 0} دلار</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">سبک سفر:</span>
            <span class="summary-value">${travelStyleText || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">نوع اقامت:</span>
            <span class="summary-value">${accommodationText || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">نوع حمل و نقل:</span>
            <span class="summary-value">${transportationText || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">فعالیت‌های مورد علاقه:</span>
            <span class="summary-value">${activitiesText.join('، ') || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">ترجیحات غذایی:</span>
            <span class="summary-value">${foodPreferencesText.join('، ') || 'ثبت نشده'}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">نیازمندی‌های خاص:</span>
            <span class="summary-value">${specialNeedsText.join('، ') || 'ثبت نشده'}</span>
        </div>
    `;
}




// Initialize event listeners


// Initialize event listeners
//window.addEventListener('load', preloadImages);

document.addEventListener("DOMContentLoaded", () => {
    const confirmCheckbox = document.getElementById("confirm-terms");
    const tripTypeOptions = document.querySelectorAll(".trip-type-option");
    
    // فعال/غیرفعال کردن گزینه‌ها بر اساس تیک شرایط استفاده
    const updateOptionsState = () => {
        tripTypeOptions.forEach(option => {
            if (confirmCheckbox.checked) {
                option.style.opacity = "1";
                option.style.pointerEvents = "auto";
                option.style.filter = "none";
            } else {
                option.style.opacity = "0.5";
                option.style.pointerEvents = "none";
                option.style.filter = "grayscale(80%)";
            }
        });
    };
    
    // رویداد تغییر برای چک‌باکس شرایط استفاده
    confirmCheckbox.addEventListener("change", updateOptionsState);
    updateOptionsState();
    
    // مقداردهی اولیه مراحل
    navigateToStep(state.currentStep);
    
    // رویدادهای کلیک برای دکمه‌ها
    document.querySelector(".next-step").addEventListener("click", handleNextStep);
    document.getElementById("back-button").addEventListener("click", handleBackStep);
    document.getElementById("multi-step-form").addEventListener("submit", handleFormSubmit);
    
    // مدیریت تاریخچه مرورگر
    window.addEventListener("popstate", (event) => {
        if (event.state?.step) state.updateStep(event.state.step);
        else navigateToStep(1);
    });

    // مقداردهی اولیه فیلدهای عددی
    setupInput("travelers-input", "travelers-display", "travelers");
    setupInput("duration-input", "duration-display", "duration");
    setupInput("budget-input", "budget-display", "budget");

    // مقداردهی اولیه انتخاب‌های چندگزینه‌ای
    setupOptionSelection(".trip-type-option", "tripType");
    setupOptionSelection(".destination-option", "destination");
    setupOptionSelection(".travel-style-option", "travelStyle");
    setupOptionSelection(".accommodation-option", "accommodation");
    setupOptionSelection(".transportation-option", "transportation");

    // رویداد کلید Enter برای فیلدهای متنی
    document.addEventListener("keydown", handleEnterKey);
});



// تابع ارسال فرم
window.handleFormSubmit = function(event) {
    event.preventDefault();
    
    const formData = {
        ...state.formData,
        tripType: state.formData.tripType,
        destination: state.formData.destination,
        travelers: state.formData.travelers,
        duration: state.formData.duration,
        budget: state.formData.budget,
        travelStyle: state.formData.travelStyle,
        accommodation: state.formData.accommodation,
        transportation: state.formData.transportation,
        activities: state.formData.activities || [],
        foodPreferences: state.formData.foodPreferences || [],
        specialNeeds: state.formData.specialNeeds || []
    };

    console.log('Form submitted:', formData);
    
    document.getElementById('SubmitBtn').innerHTML = 'در حال ارسال درخواست ...';
    document.getElementById('SubmitBtn').disabled = true;
     
    const formSubmittedEvent = new CustomEvent('formSubmitted', {
        detail: { formData }
    });
    window.dispatchEvent(formSubmittedEvent);      
};