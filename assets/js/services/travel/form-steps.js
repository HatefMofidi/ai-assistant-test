window.showStep = function(step) {
    const stepElements = [
        "trip-type-step",
        "destination-step",
        "travelers-step",
        "duration-step",
        "budget-step",
        "travel-style-step",
        "accommodation-step",
        "transportation-step",
        "activities-step",
        "food-preferences-step",
        "special-needs-step",
        "confirm-submit-step"
    ];
    
    document.querySelectorAll(".step").forEach(el => {
        el.classList.remove("active");
    });
    
    const currentStepElement = document.getElementById(stepElements[step - 1]);
    if (currentStepElement) {
        currentStepElement.classList.add("active");
    }

    const nextButtonContainer = document.getElementById("next-button-container");
    if (nextButtonContainer) nextButtonContainer.style.display = [STEPS.TRIP_TYPE, STEPS.DESTINATION, STEPS.TRAVEL_STYLE, STEPS.ACCOMMODATION, STEPS.TRANSPORTATION, STEPS.ACTIVITIES, STEPS.FOOD_PREFERENCES, STEPS.SPECIAL_NEEDS].includes(step) ? "none" : "block";

    if ([STEPS.TRAVELERS, STEPS.DURATION, STEPS.BUDGET].includes(step)) {
        const inputId = `${["travelers", "duration", "budget"][step - 3]}-input`;
        const inputElement = document.getElementById(inputId);
        if (inputElement) inputElement.focus();
        
        const nextButton = document.querySelector(".next-step");
        if (nextButton) nextButton.disabled = true;
        
        validateStep(step);
    }
    
    if (step === STEPS.ACTIVITIES) {
        setupActivitiesSelection(step);
    } else if (step === STEPS.FOOD_PREFERENCES) {
        setupFoodPreferencesSelection(step);
    } else if (step === STEPS.SPECIAL_NEEDS) {
        setupSpecialNeedsSelection(step);
    } else if (step === STEPS.CONFIRMATION) {
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

// بقیه توابع بدون تغییر باقی می‌مانند...


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
    if (event.key === "Enter" && event.target.matches("input[type='text']")) {
        event.preventDefault();
        document.querySelector(".next-step").click();
    }
    
}    