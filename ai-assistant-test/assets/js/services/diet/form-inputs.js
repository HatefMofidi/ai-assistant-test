// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/form-inputs.js
function addFieldIcons() {
  const firstNameInput = document.getElementById('first-name-input');
  if (firstNameInput) {
    const icon = document.createElement('div');
    icon.className = 'input-field-icon';
    icon.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
    
    firstNameInput.parentNode.appendChild(icon);
  }
  
  // نام خانوادگی (نسخه بهبود یافته)
  const lastNameInput = document.getElementById('last-name-input');
  if (lastNameInput) {
    const icon = document.createElement('div');
    icon.className = 'input-field-icon';
    icon.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>';
    lastNameInput.parentNode.appendChild(icon);
  }
  
  // سن
  const ageInput = document.getElementById('age-input');
  if (ageInput) {
    const icon = document.createElement('div');
    icon.className = 'input-field-icon';
    icon.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
    ageInput.parentNode.appendChild(icon);
  }
  
  // قد
  const heightInput = document.getElementById('height-input');
  if (heightInput) {
    const icon = document.createElement('div');
    icon.className = 'input-field-icon';
    icon.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="2" x2="12" y2="22"></line><path d="M8 4L12 2L16 4"></path><path d="M8 20L12 22L16 20"></path></svg>`;
    heightInput.parentNode.appendChild(icon);
  }
  
  // وزن
  const weightInput = document.getElementById('weight-input');
  if (weightInput) {
    const icon = document.createElement('div');
    icon.className = 'input-field-icon';
    icon.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="5" r="3"/><path d="M7 12h10c0-3.5-2.5-5-5-5s-5 1.5-5 5Z"/><path d="M7 12v6c0 1.5 2 3 5 3s5-1.5 5-3v-6"/></svg>`;
    weightInput.parentNode.appendChild(icon);
  }
  
  // وزن هدف
  const targetWeightInput = document.getElementById('target-weight-input');
  if (targetWeightInput) {
    const icon = document.createElement('div');
    icon.className = 'input-field-icon';
    icon.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>';
    targetWeightInput.parentNode.appendChild(icon);
  }
}

function setupInputEffects() {
  const allInputs = document.querySelectorAll('input');
  const textInputs = Array.from(allInputs).filter(input => input.type === 'text');
  
  textInputs.forEach(input => {
    // افکت هنگام کلیک
    input.addEventListener('mousedown', function() {
      this.style.transform = 'scale(0.99)';
    });
    
    input.addEventListener('mouseup', function() {
      this.style.transform = '';
    });
    
    input.addEventListener('mouseleave', function() {
      this.style.transform = '';
    });
    
    // افکت هنگام تایپ
    input.addEventListener('input', function() {
      if (this.value.length > 0) {
        this.parentNode.classList.add('has-value');
      } else {
        this.parentNode.classList.remove('has-value');
      }
    });
  });
}

window.setupTextInput = function(inputId, displayId, field) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    const nextButton = document.querySelector(".next-step");

    // تنظیمات اولیه
    input.style.fontFamily = 'Vazir, Tahoma, sans-serif';
    input.style.fontSize = '16px';
    input.style.letterSpacing = '0';
    input.style.direction = 'rtl';
    input.style.textAlign = 'right';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('autocorrect', 'off');
    input.setAttribute('spellcheck', 'false');

    // تنظیم placeholder متناسب با فیلد
    const placeholderText = field === "firstName" ? "نام" : "نام خانوادگی";
    display.textContent = placeholderText;
    display.style.color = "var(--light-text-color)";
    
    // هماهنگ‌سازی عرض input و display
    const syncWidth = () => {
        display.style.width = input.offsetWidth + 'px';
    };
    syncWidth();
    window.addEventListener('resize', syncWidth);

    const updateDisplay = (value) => {
        if (value) {
            display.textContent = value;
            display.style.color = "var(--text-color)";
        } else {
            display.textContent = placeholderText;
            display.style.color = "var(--light-text-color)";
        }
        state.updateFormData(field, value);
        validateStep(state.currentStep);
        
        // هماهنگ‌سازی عرض پس از هر تغییر
        syncWidth();
    };

    let isComposing = false;
    let lastValue = '';

    input.addEventListener('compositionstart', () => {
        isComposing = true;
    });

    input.addEventListener('compositionend', () => {
        isComposing = false;
        if (input.value !== lastValue) {
            updateDisplay(input.value);
            lastValue = input.value;
        }
        setTimeout(() => {
            input.setSelectionRange(input.value.length, input.value.length);
        }, 0);
    });

    input.addEventListener("input", (e) => {
        if (!isComposing) {
            updateDisplay(e.target.value);
            lastValue = e.target.value;
            setTimeout(() => {
                input.setSelectionRange(input.value.length, input.value.length);
            }, 0);
        }
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            e.preventDefault();
            const cursorPos = input.selectionStart;
            const newPos = e.key === 'ArrowLeft' ? Math.min(cursorPos + 1, input.value.length) 
                                              : Math.max(cursorPos - 1, 0);
            input.setSelectionRange(newPos, newPos);
        }
        
        // اضافه کردن مدیریت کلید Enter
        if (e.key === "Enter") {
            e.preventDefault();
            
            if (inputId === "first-name-input") {
                // از فیلد نام به فیلد نام خانوادگی برو
                document.getElementById("last-name-input").focus();
            } else if (inputId === "last-name-input" && input.value.trim()) {
                // از فیلد نام خانوادگی به مرحله بعد برو
                if (nextButton && !nextButton.disabled) {
                    nextButton.click();
                }
            }
        }
    });

    input.addEventListener("focus", () => {
        setTimeout(() => {
            input.setSelectionRange(input.value.length, input.value.length);
        }, 0);
    });

    input.addEventListener("blur", () => {
        if (!input.value.trim()) {
            display.textContent = field === "firstName" ? "نام" : "نام خانوادگی";
            display.style.color = "var(--light-text-color)";
            syncWidth();
        }
    });

    // مقدار اولیه
    updateDisplay(input.value);
};

window.setupInput = function(inputId, displayId, field) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    const nextButton = document.querySelector(".next-step");

    if (nextButton) nextButton.disabled = true;

    const updateDisplay = (value) => {
        display.textContent = value ? 
            `${value} ${field === "age" ? "سال" : field === "height" ? "سانتی‌متر" : "کیلوگرم"}` : 
            `0 ${field === "age" ? "سال" : field === "height" ? "سانتی‌متر" : "کیلوگرم"}`;
        
        // تغییر این خط برای استفاده از متغیرهای CSS
        display.style.color = value ? "var(--text-color)" : "var(--light-text-color)";
        
        state.updateFormData(field, value ? parseInt(value) : null);
        
        if (field === "weight" && state.formData.height && value) {
            calculateBMI(state.formData.height, parseInt(value));
        }
    };

    input.addEventListener("input", () => {
        let value = input.value.replace(/\D/g, "");
        if (field === "age" && value.length > 2) value = value.slice(0, 2);
        else if ((field === "height" || field === "weight") && value.length > 3) value = value.slice(0, 3);
        
        input.value = value;
        updateDisplay(value);
        
        if (input.type === "text") {
            setTimeout(() => input.setSelectionRange(value.length, value.length), 0);
        }
        
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

document.addEventListener("DOMContentLoaded", () => {
    const confirmCheckbox = document.getElementById("confirm-terms");
    const genderOptions = document.querySelectorAll(".gender-option");
    

    addFieldIcons();
    setupInputEffects();
  
    
    const updateGenderOptionsState = () => {
        genderOptions.forEach(option => {
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
    
    confirmCheckbox.addEventListener("change", updateGenderOptionsState);
    updateGenderOptionsState();
    
    navigateToStep(state.currentStep);
    document.querySelector(".next-step").addEventListener("click", handleNextStep);
    document.getElementById("back-button").addEventListener("click", handleBackStep);
    document.getElementById("multi-step-form").addEventListener("submit", handleFormSubmit);
    window.addEventListener("popstate", (event) => {
        if (event.state?.step) state.updateStep(event.state.step);
        else navigateToStep(1);
    });

    setupInput("age-input", "age-display", "age");
    setupInput("height-input", "height-display", "height");
    setupInput("weight-input", "weight-display", "weight");
    setupInput("target-weight-input", "target-weight-display", "targetWeight");
    
    setupTextInput("first-name-input", "first-name-display", "firstName");
    setupTextInput("last-name-input", "last-name-display", "lastName");

    setupOptionSelection(".gender-option", "gender");
    setupOptionSelection(".goal-option", "goal");
    setupOptionSelection(".activity-option", "activity");
    setupOptionSelection(".exercise-option", "exercise");

    document.getElementById("multi-step-form").addEventListener("keydown", function(event) {
        // فقط اجازه کار Enter در مراحل خاص
        if (event.key === "Enter") {
            const allowedSteps = [
                STEPS.AGE, 
                STEPS.HEIGHT, 
                STEPS.WEIGHT, 
                STEPS.TARGET_WEIGHT,
                STEPS.CONFIRMATION
            ];
            
            if (!allowedSteps.includes(state.currentStep)) {
                event.preventDefault();
                return false;
            }
        }
    });
    
    document.addEventListener("keydown", handleEnterKey);
    
    document.querySelectorAll('.real-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            
            if (this.checked) {
                label.classList.add('checked-animation');
                
                // حذف انیمیشن پس از اجرا
                setTimeout(() => {
                    label.classList.remove('checked-animation');
                }, 800);
            }
        });
    });    
});