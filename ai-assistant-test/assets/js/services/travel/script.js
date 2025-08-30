window.state = {
    currentStep: 1,
    formData: {},
    updateStep(step) {
        this.currentStep = step;
        window.showStep(step);
        window.updateStepCounter(step);
        window.updateProgressBar(step);
    },
    updateFormData(key, value) {
        this.formData[key] = value;
        window.validateStep(this.currentStep);
    }
};

window.CONSTANTS = {
    MIN_TRAVELERS: 1,
    MAX_TRAVELERS: 20,
    MIN_DAYS: 1,
    MAX_DAYS: 90,
    MIN_BUDGET: 100,
    MAX_BUDGET: 10000
};

window.STEPS = {
    TRIP_TYPE: 1,
    DESTINATION: 2,
    TRAVELERS: 3,
    DURATION: 4,
    BUDGET: 5,
    TRAVEL_STYLE: 6,
    ACCOMMODATION: 7,
    TRANSPORTATION: 8,
    ACTIVITIES: 9,
    FOOD_PREFERENCES: 10,
    SPECIAL_NEEDS: 11,
    CONFIRMATION: 12
};

window.totalSteps = Object.keys(STEPS).length;