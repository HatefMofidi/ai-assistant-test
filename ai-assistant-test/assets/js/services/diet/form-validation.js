// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-validation.js
window.validateStep = function(step) {
    const nextButton = document.querySelector(".next-step");
    const errorMessages = {
        [STEPS.HEIGHT]: { 
            field: "height", 
            min: CONSTANTS.MIN_HEIGHT, 
            max: CONSTANTS.MAX_HEIGHT, 
            unit: "سانتی‌متر", 
            label: "قد", 
            errorId: "height-error" 
        },
        [STEPS.WEIGHT]: { 
            field: "weight", 
            min: CONSTANTS.MIN_WEIGHT, 
            max: CONSTANTS.MAX_WEIGHT, 
            unit: "کیلوگرم", 
            label: "وزن", 
            errorId: "weight-error" 
        },
        [STEPS.TARGET_WEIGHT]: { 
            field: "targetWeight", 
            min: CONSTANTS.MIN_WEIGHT, 
            max: CONSTANTS.MAX_WEIGHT, 
            unit: "کیلوگرم", 
            label: "وزن هدف", 
            errorId: "targetWeight-error",
            customValidation: (value) => {
                const currentWeight = state.formData.userInfo.weight;
                const goal = state.formData.userInfo.goal;
                
                if (!currentWeight) return true;
                
                if (goal === "weight-loss" && value >= currentWeight) {
                    return `برای کاهش وزن، وزن هدف باید کمتر از وزن فعلی (${currentWeight} کیلوگرم) باشد`;
                }
                
                if (goal === "weight-gain" && value <= currentWeight) {
                    return `برای افزایش وزن، وزن هدف باید بیشتر از وزن فعلی (${currentWeight} کیلوگرم) باشد`;
                }
                
                if (goal === "fitness" && Math.abs(value - currentWeight) > 20) {
                    return `برای حفظ سلامت، وزن هدف باید حداکثر ۲۰ کیلوگرم با وزن فعلی تفاوت داشته باشد`;
                }
                
                return true;
            }
        }
    };
    
    if (step === STEPS.PERSONAL_INFO) {
        const firstName = state.formData.userInfo.firstName;
        const lastName = state.formData.userInfo.lastName;
        const age = state.formData.userInfo.age;
        nextButton.disabled = !(firstName && lastName);
        
        if (!age || age < CONSTANTS.MINAGE || age > CONSTANTS.MAXAGE) {
           nextButton.disabled = true;    
        }
        return;
    }    
    
    if (errorMessages[step]) {
        const { field, min, max, unit, label, errorId, customValidation } = errorMessages[step];
        const value = state.formData.userInfo[field];
        const errorElement = document.getElementById(errorId);
        
        if (!errorElement) return;

        if (value === undefined || value === null || value === "") {
            errorElement.textContent = "";
            errorElement.classList.remove("valid");
            if (nextButton) nextButton.disabled = true;
            return;
        }
        
        if (value < min || value > max) {
            errorElement.textContent = `${label} باید بین ${min} تا ${max} ${unit} وارد شود`;
            errorElement.classList.remove("valid");
            if (nextButton) nextButton.disabled = true;
            return;
        }
        
        if (step === STEPS.TARGET_WEIGHT && customValidation) {
            const validationResult = customValidation(value);
            if (validationResult !== true) {
                errorElement.textContent = validationResult;
                errorElement.classList.remove("valid");
                if (nextButton) nextButton.disabled = true;
                return;
            }
        }
        
        errorElement.innerHTML = `<span class="tick-icon"></span> مقدار وارد شده معتبر است.`;
        errorElement.classList.add("valid");
        if (nextButton) nextButton.disabled = false;
    }
}

window.calculateBMI = function(height, weight) {
    const heightInMeters = height / 100;
    const bmiValue = document.getElementById('bmi-value');
    const bmiCategory = document.getElementById('bmi-category');
    const bmiIndicator = document.getElementById('bmi-indicator');
    const bmiContainer = document.getElementById('bmi-result-container');
    
    if (!weight || weight === 0) {
        bmiContainer.style.opacity = '0.5';
        bmiIndicator.style.display = 'none';
        bmiValue.textContent = '0';
        bmiCategory.textContent = '';
        return;
    } else {
        bmiContainer.style.opacity = '1';
        bmiIndicator.style.display = 'block';
    }
    
    const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(1);
    bmiValue.textContent = bmi;
    
    const categories = [
        { max: 18.5, text: 'کمبود وزن', color: '#4fc3f7' },
        { max: 25, text: 'وزن نرمال', color: '#66bb6a' },
        { max: 30, text: 'اضافه وزن', color: '#ffee58' },
        { max: 35, text: 'چاق', color: '#ffa726' },
        { max: Infinity, text: 'چاقی شدید', color: '#ef5350' }
    ];
    
    const category = categories.find(c => bmi < c.max);
    bmiCategory.textContent = category.text;
    bmiCategory.style.color = category.color;
    
    let position;
    if (bmi < 18.5) {
        position = (bmi / 18.5) * 20;
    } else if (bmi < 25) {
        position = 20 + ((bmi - 18.5) / 6.5) * 20;
    } else if (bmi < 30) {
        position = 40 + ((bmi - 25) / 5) * 20;
    } else if (bmi < 35) {
        position = 60 + ((bmi - 30) / 5) * 20;
    } else {
        position = 80 + ((Math.min(bmi, 50) - 35) / 15) * 20;
    }
    
    bmiIndicator.classList.add('animate-indicator');
    setTimeout(() => {
        bmiIndicator.style.left = `${Math.min(position, 100)}%`;
        bmiIndicator.style.transform = 'translateX(-50%)';
    }, 10);
    
    setTimeout(() => {
        bmiIndicator.classList.remove('animate-indicator');
    }, 800);
}