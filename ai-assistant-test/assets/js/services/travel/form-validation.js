window.validateStep = function(step) {
    const nextButton = document.querySelector(".next-step");
    const errorMessages = {
        [STEPS.TRAVELERS]: { 
            field: "travelers", 
            min: CONSTANTS.MIN_TRAVELERS, 
            max: CONSTANTS.MAX_TRAVELERS, 
            unit: "نفر", 
            label: "تعداد مسافران", 
            errorId: "travelers-error" 
        },
        [STEPS.DURATION]: { 
            field: "duration", 
            min: CONSTANTS.MIN_DAYS, 
            max: CONSTANTS.MAX_DAYS, 
            unit: "روز", 
            label: "مدت سفر", 
            errorId: "duration-error" 
        },
        [STEPS.BUDGET]: { 
            field: "budget", 
            min: CONSTANTS.MIN_BUDGET, 
            max: CONSTANTS.MAX_BUDGET, 
            unit: "دلار", 
            label: "بودجه", 
            errorId: "budget-error" 
        }
    };
    
    if (errorMessages[step]) {
        const { field, min, max, unit, label, errorId } = errorMessages[step];
        const value = state.formData[field];
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
        
        errorElement.innerHTML = `<span class="tick-icon"></span> مقدار وارد شده معتبر است.`;
        errorElement.classList.add("valid");
        if (nextButton) nextButton.disabled = false;
    }
}