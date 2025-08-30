window.setupInput = function(inputId, displayId, field) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    const nextButton = document.querySelector(".next-step");

    if (nextButton) nextButton.disabled = true;

    const updateDisplay = (value) => {
        display.textContent = value ? `${value} ${field === "travelers" ? "نفر" : field === "duration" ? "روز" : "دلار"}` : `0 ${field === "travelers" ? "نفر" : field === "duration" ? "روز" : "دلار"}`;
        display.style.color = value ? "#000" : "#999";
        state.updateFormData(field, value ? parseInt(value) : null);
    };

    input.addEventListener("input", () => {
        let value = input.value.replace(/\D/g, "");
        if (field === "travelers" && value.length > 2) value = value.slice(0, 2);
        else if ((field === "duration" || field === "budget") && value.length > 3) value = value.slice(0, 3);
        input.value = value;
        updateDisplay(value);
        if (input.type === "text") setTimeout(() => input.setSelectionRange(value.length, value.length), 0);
        validateStep(state.currentStep);
    });

    input.addEventListener("click", () => {
        const value = input.value.replace(/\D/g, "");
        if (input.type === "text") input.setSelectionRange(value.length, value.length);
    });

    input.addEventListener("blur", () => {
        const value = input.value.trim();
        if (!value) updateDisplay("");
        validateStep(state.currentStep);
    });
}

window.setupOptionSelection = function(selector, key) {
    document.querySelectorAll(selector).forEach(el => {
        el.addEventListener("click", () => {
            const confirmCheckbox = document.getElementById("confirm-terms");
            if (!confirmCheckbox.checked) {
                alert("لطفاً ابتدا شرایط استفاده را تأیید کنید");
                return;
            }

            document.querySelectorAll(selector).forEach(opt => {
                opt.classList.remove("selected");
                opt.style.transform = "";
                opt.style.boxShadow = "";
            });
            
            el.classList.add("selected");
            el.classList.add("selected-with-effect");
            
            setTimeout(() => {
                el.classList.remove("selected-with-effect");
                state.updateFormData(key, el.dataset[key]);
                
                el.style.transform = "translateY(-3px)";
                el.style.boxShadow = "0 10px 20px rgba(0, 133, 122, 0.2)";
                
                setTimeout(() => {
                    navigateToStep(state.currentStep + 1);
                }, 250);
            }, 150);
        });
    });
}